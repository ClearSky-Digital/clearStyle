#!/usr/bin/env python3
# SPDX-License-Identifier: GPL-2.0-or-later
# Copyright (C) 2026 ClearSky Digital
"""Build the release zip of the ClearStyle plugin and check it.

The zip holds only the clearstyle/ plugin folder (the folder name is the plugin slug, so it unpacks to wp-content/plugins/clearstyle/).
Examples, tests, workflows and other development files are not included.

Usage (from anywhere, Python 3.8 or later, no packages needed):
  python3 bin/build-zip.py                      builds dist/clearstyle-<version>.zip
  python3 bin/build-zip.py --tag v1.5.1         also fails unless the tag matches the version in the plugin
  python3 bin/build-zip.py --out some/folder    puts the zip in another folder

The zip is reproducible: files are sorted, there are no directory entries, and every file gets the same timestamp
(the time of the last git commit, or SOURCE_DATE_EPOCH, or a fixed date when neither is available).
The script prints the zip path, size and sha256, and exits with a non-zero status when any check fails.
"""
import argparse
import hashlib
import os
import re
import subprocess
import sys
import time
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SLUG = "clearstyle"
PLUGIN = ROOT / SLUG
ALLOWED_SUFFIXES = {".php", ".html", ".txt"}


def fail(message):
    print("FAIL " + message, file=sys.stderr)
    sys.exit(1)


def header_version():
    text = (PLUGIN / "clearstyle.php").read_text(encoding="utf-8")
    m = re.search(r"^\s*\*\s*Version:\s*(\S+)\s*$", text, re.M)
    if not m:
        fail("no Version line in the plugin header")
    const = re.search(r"define\(\s*'CSD_SITE_STYLES_VERSION',\s*'([^']+)'", text)
    stable = re.search(r"^Stable tag:\s*(\S+)\s*$", (PLUGIN / "readme.txt").read_text(encoding="utf-8"), re.M)
    if not const or const.group(1) != m.group(1):
        fail("CSD_SITE_STYLES_VERSION does not match the plugin header version")
    if not stable or stable.group(1) != m.group(1):
        fail("Stable tag in readme.txt does not match the plugin header version")
    return m.group(1)


def timestamp():
    if os.environ.get("SOURCE_DATE_EPOCH", "").isdigit():
        epoch = int(os.environ["SOURCE_DATE_EPOCH"])
    else:
        try:
            out = subprocess.run(["git", "-C", str(ROOT), "log", "-1", "--format=%ct"], capture_output=True, text=True, check=True).stdout.strip()
            epoch = int(out)
        except (OSError, subprocess.CalledProcessError, ValueError):
            epoch = 1767225600  # 2026-01-01
    return time.gmtime(max(epoch, 315532800))[:6]  # a zip cannot hold a date before 1980


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--tag", default="", help="a release tag such as v1.5.1; must match the plugin version")
    ap.add_argument("--out", default=str(ROOT / "dist"), help="folder for the zip")
    args = ap.parse_args()

    version = header_version()
    if args.tag and args.tag != "v" + version:
        fail(f"tag {args.tag} does not match the plugin version {version}")

    files = sorted(str(p.relative_to(PLUGIN)).replace("\\", "/") for p in PLUGIN.rglob("*") if p.is_file())
    bad = [f for f in files if Path(f).suffix.lower() not in ALLOWED_SUFFIXES or any(part.startswith(".") for part in f.split("/"))]
    if bad:
        fail("files that do not belong in the plugin zip (the plugin ships no styling, scripts or images): " + ", ".join(bad))
    for need in ("clearstyle.php", "readme.txt", "license.txt", "uninstall.php"):
        if need not in files:
            fail("missing " + need)

    out_dir = Path(args.out)
    out_dir.mkdir(parents=True, exist_ok=True)
    zip_path = out_dir / f"{SLUG}-{version}.zip"
    stamp = timestamp()
    with zipfile.ZipFile(zip_path, "w", zipfile.ZIP_DEFLATED, compresslevel=9) as z:
        for f in files:
            info = zipfile.ZipInfo(f"{SLUG}/{f}", stamp)
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = 0o644 << 16
            info.create_system = 3
            z.writestr(info, (PLUGIN / f).read_bytes(), compress_type=zipfile.ZIP_DEFLATED, compresslevel=9)

    # Read it back and check it.
    with zipfile.ZipFile(zip_path) as z:
        names = z.namelist()
        if z.testzip() is not None:
            fail("a zip entry is damaged")
        if not all(n.startswith(SLUG + "/") for n in names):
            fail("an entry is outside the " + SLUG + "/ folder")
        if sorted(n[len(SLUG) + 1:] for n in names) != files:
            fail("zip entries differ from the plugin folder")
        if any(n.endswith("/") for n in names):
            fail("the zip has a directory entry")
        if any(z.read(n) != (PLUGIN / n[len(SLUG) + 1:]).read_bytes() for n in names):
            fail("an entry's bytes differ from the plugin folder")
        main = z.read(f"{SLUG}/clearstyle.php").decode("utf-8")
        if not re.search(r"^\s*\*\s*Version:\s*" + re.escape(version) + r"\s*$", main, re.M):
            fail("the plugin header inside the zip does not read Version " + version)
        listing = [(i.filename, i.file_size) for i in z.infolist()]

    raw = zip_path.read_bytes()
    print(f"zip: {zip_path}")
    print(f"size: {len(raw)} bytes")
    print(f"sha256: {hashlib.sha256(raw).hexdigest()}")
    print(f"version: {version}")
    print("contents:")
    for name, size in listing:
        print(f"  {size:8d}  {name}")
    print("OK")


if __name__ == "__main__":
    main()
