#!/usr/bin/env python3
# SPDX-License-Identifier: GPL-2.0-or-later
# Copyright (C) 2026 ClearSky Digital
"""Render check for a CSD Site Styles stylesheet (default: examples/demo-site.css).

It draws the plugin's gallery content (every registered block style, the eight patterns and the core blocks) with the stylesheet in a
headless Chromium, at 1440 and 390 pixels wide, and reports:
  - sideways overflow of the page,
  - text that is below WCAG AA contrast (4.5 to 1, or 3 to 1 for large text) against the background it sits on,
  - text that is invisible.
The block styles are read from csd-site-styles/includes/block-styles.php and the patterns from csd-site-styles/patterns/, so a new style
or pattern is picked up without editing this script. The check fails when the plugin does not register 24 block styles and 8 patterns.

It is a static page, not WordPress: block markup is written out as WordPress prints it, and the block library stylesheet is optional.
Pass --block-css with a copy of wp-includes/css/dist/block-library/style.min.css to include WordPress's own block styles.

Usage:
  python -m pip install playwright && python -m playwright install chromium
  python tests/render_check.py [--css examples/demo-site.css] [--block-css block-library.css] [--shots DIR]
Exit status 0 only when there are no problems.
"""
import argparse
import html
import re
import sys
import tempfile
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parent.parent
PLUGIN = ROOT / "csd-site-styles"

# Used only when no block library stylesheet is given: the few layout rules the blocks need to be drawn sensibly.
FALLBACK_LIBRARY = (
    "body{margin:0;font-family:Arial,sans-serif}"
    "ul,ol{margin:0 0 1em}.wp-block-buttons{display:flex;flex-wrap:wrap}.wp-block-button__link{display:inline-block;text-decoration:none}"
    ".wp-block-columns{display:flex;flex-wrap:wrap;gap:2em}.wp-block-column{flex:1 1 200px;min-width:0}"
    ".wp-block-image img{max-width:100%;height:auto}figure{margin:0 0 1em}"
    ".wp-block-cover{position:relative;display:flex;align-items:center;justify-content:center;min-height:430px;padding:1em;overflow:hidden}"
    ".wp-block-cover__image-background{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:-1}"
    ".wp-block-cover__background{position:absolute;inset:0;background-color:#000;opacity:.5}.has-background-dim-0{opacity:0}"
    ".wp-block-cover__inner-container{position:relative;z-index:1;width:100%}"
)
STOP_MOTION = "*,*::before,*::after{animation:none!important;transition:none!important}"
PH = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADElEQVR4nGNoaGgAAAMEAYFL09IQAAAAAElFTkSuQmCC"


def registered_styles():
    """Read the block style names from block-styles.php, grouped by block, in file order."""
    src = (PLUGIN / "includes" / "block-styles.php").read_text(encoding="utf-8")
    out = {}
    for block, body in re.findall(r"'(core/[a-z]+)'\s*=>\s*array\((.*?)\n\t\t\),", src, flags=re.S):
        out[block] = re.findall(r"'(csd-[a-z-]+)'\s*=>\s*__\(\s*'([^']+)'", body)
    return out


def strip_comments(s):
    return re.sub(r"<!--.*?-->", "", s, flags=re.S)


def photos(markup):
    """The plugin's gallery gives each Cover block a placeholder image; do the same here."""
    return markup.replace('</span><div class="wp-block-cover__inner-container">',
                          f'</span><img class="wp-block-cover__image-background" alt="" src="{PH}" data-object-fit="cover"/><div class="wp-block-cover__inner-container">')


def sample(block, name, label):
    cls = "is-style-" + name
    t = html.escape(label)
    if block == "core/cover":
        return photos(f'<div class="wp-block-cover {cls}"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><h3 class="wp-block-heading">{t}</h3><p>Sample text over the photo.</p></div></div>')
    if block == "core/image":
        return f'<figure class="wp-block-image {cls}"><img src="{PH}" alt=""/></figure>'
    if block == "core/heading":
        return f'<h2 class="wp-block-heading {cls}">{t}</h2>'
    if block == "core/paragraph":
        return f'<p class="{cls}"><a href="#">{t}</a></p>'
    if block == "core/list":
        return f'<ul class="wp-block-list {cls}"><li>{t}</li><li>A second item</li></ul>'
    if block == "core/quote":
        return f'<blockquote class="wp-block-quote {cls}"><p>{t}</p><cite>Source</cite></blockquote>'
    return f'<div class="wp-block-group {cls}"><h3 class="wp-block-heading">{t}</h3><p>Sample text inside the block.</p></div>'


CORE = (
    '<h1 class="wp-block-heading">Heading 1</h1><h2 class="wp-block-heading">Heading 2</h2><h3 class="wp-block-heading">Heading 3</h3><h4 class="wp-block-heading">Heading 4</h4>'
    '<p>A paragraph with a <a href="#">link</a>, <strong>bold text</strong> and <em>emphasis</em>.</p>'
    '<ul class="wp-block-list"><li>First item</li><li>Second item</li></ul><ol class="wp-block-list"><li>First step</li><li>Second step</li></ol>'
    '<blockquote class="wp-block-quote"><p>A quotation.</p><cite>Source</cite></blockquote>'
    '<div class="wp-block-buttons"><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">Button</a></div>'
    '<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#">Outline</a></div></div>'
    '<hr class="wp-block-separator has-alpha-channel-opacity"/>'
    '<figure class="wp-block-table"><table><thead><tr><th>Heading</th><th>Heading</th></tr></thead><tbody><tr><td>Cell</td><td>Cell</td></tr></tbody></table></figure>'
    '<div class="wp-block-columns"><div class="wp-block-column"><p>First column</p></div><div class="wp-block-column"><p>Second column</p></div></div>'
)


def gallery_body(styles):
    b = "<h2>Block styles</h2>"
    for block, items in styles.items():
        b += f"<h3>{block}</h3>"
        for name, label in items:
            b += f"<p>{name}</p>" + sample(block, name, label)
    b += "<h2>Patterns</h2>"
    for p in sorted((PLUGIN / "patterns").glob("*.html")):
        b += f"<h3>{p.stem}</h3>" + photos(strip_comments(p.read_text(encoding="utf-8")))
    b += "<h2>Core blocks by type</h2>" + CORE
    return '<div class="csd-page">' + b + "</div>"


def extra_body():
    """Combinations the gallery does not show: five cards in a Pentagon on an Accent band, a Facts panel, a card on a Plain band."""
    cards = "".join(f'<article class="wp-block-group is-style-csd-card"><h4>{i}. Card</h4><p>Short description for card {i}.</p></article>' for i in range(1, 6))
    return (
        '<div class="wp-block-group alignfull csd-page csd-page--bleed">'
        f'<section class="wp-block-group is-style-csd-dark"><div class="wp-block-group is-style-csd-column"><h2>Label</h2><h3>Five cards</h3><div class="wp-block-group is-style-csd-pentagon">{cards}</div>'
        '<div class="wp-block-group is-style-csd-facts"><h4>Facts</h4><ul><li>First fact</li><li>Second fact</li></ul></div>'
        '<div class="wp-block-group is-style-csd-listing"><p>Listing frame content.</p></div></div></section>'
        '<section class="wp-block-group is-style-csd-light"><div class="wp-block-group is-style-csd-column"><h2>Label</h2><h3>Plain band</h3>'
        '<div class="wp-block-group is-style-csd-cards"><article class="wp-block-group is-style-csd-card"><figure class="wp-block-image is-style-csd-icon"><img src="' + PH + '" alt=""/></figure><h4>Card</h4><p>Card on a plain band.</p></article></div>'
        '<ul class="wp-block-list is-style-csd-list"><li>One</li><li>Two</li></ul>'
        '<blockquote class="wp-block-quote is-style-csd-quote"><p>Quote.</p><cite>Source</cite></blockquote></div></section></div>'
    )


JS = r"""
() => {
  const cv = document.createElement('canvas'); cv.width = cv.height = 1;
  const cx = cv.getContext('2d', {willReadFrequently: true});
  function parse(s) { cx.clearRect(0,0,1,1); cx.fillStyle = '#000'; cx.fillStyle = s; cx.fillRect(0,0,1,1); const d = cx.getImageData(0,0,1,1).data; return [d[0], d[1], d[2], d[3] / 255]; }
  function stopsOf(img) { const m = img.match(/rgba?\([^)]*\)|color\(srgb[^)]*\)/g) || []; return m.map(parse); }
  function over(top, bot) { const a = top[3]; return [top[0]*a + bot[0]*(1-a), top[1]*a + bot[1]*(1-a), top[2]*a + bot[2]*(1-a), 1]; }
  function lum(c) { const f = v => { v /= 255; return v <= 0.03928 ? v/12.92 : Math.pow((v+0.055)/1.055, 2.4); }; return 0.2126*f(c[0]) + 0.7152*f(c[1]) + 0.0722*f(c[2]); }
  function ratio(a, b) { const x = lum(a), y = lum(b); return (Math.max(x,y)+0.05)/(Math.min(x,y)+0.05); }
  function candidates(el) {
    const layers = [];
    for (let a = el; a; a = a.parentElement) {
      const cs = getComputedStyle(a);
      const pb = getComputedStyle(a, '::before');
      if (pb.content !== 'none' && pb.position === 'absolute') { const oc = parse(pb.backgroundColor); if (oc[3] > 0) layers.push({c: oc}); }
      const bi = cs.backgroundImage;
      if (bi && bi !== 'none' && /gradient/.test(bi)) { layers.push({stops: stopsOf(bi)}); break; }
      const bc = parse(cs.backgroundColor);
      if (bc[3] > 0) { layers.push({c: bc}); if (bc[3] >= 1) break; }
      if (a.tagName === 'HTML') { layers.push({c: [255,255,255,1]}); break; }
    }
    let sets = [[]];
    for (const L of layers) {
      if (L.stops) { const n = []; for (const s of sets) for (const st of L.stops) n.push(s.concat([{c: st}])); sets = n; }
      else sets = sets.map(s => s.concat([L]));
    }
    return sets.map(s => { let base = [255,255,255,1]; for (let i = s.length - 1; i >= 0; i--) base = over(s[i].c, base); return base; });
  }
  const res = {texts: 0, fails: [], invisible: [], overflow: []};
  const vw = document.documentElement.clientWidth;
  for (const el of document.body.querySelectorAll('*')) {
    const r = el.getBoundingClientRect();
    const own = Array.from(el.childNodes).some(n => n.nodeType === 3 && n.textContent.trim().length);
    if (r.right > vw + 1 && r.width > 0) {
      let clipped = false;
      for (let p = el.parentElement; p && p !== document.body; p = p.parentElement) { if (getComputedStyle(p).overflowX !== 'visible') { const pr = p.getBoundingClientRect(); if (pr.right <= vw + 1) { clipped = true; break; } } }
      if (!clipped) res.overflow.push(el.tagName.toLowerCase() + '.' + (el.className || '').toString().slice(0, 60) + ' right=' + Math.round(r.right));
    }
    if (!own) continue;
    const cs = getComputedStyle(el);
    if (cs.display === 'none' || cs.visibility === 'hidden') continue;
    res.texts++;
    const txt = Array.from(el.childNodes).filter(n => n.nodeType === 3).map(n => n.textContent.trim()).join(' ').slice(0, 40);
    if (r.width < 1 || r.height < 1 || parseFloat(cs.opacity) < 0.05) { res.invisible.push(el.tagName.toLowerCase() + ' "' + txt + '"'); continue; }
    const fg = parse(cs.color);
    const size = parseFloat(cs.fontSize), weight = parseInt(cs.fontWeight, 10);
    const need = (size >= 24 || (size >= 18.66 && weight >= 700)) ? 3 : 4.5;
    let worst = 99, worstBg = null;
    const cands = candidates(el);
    if (cands.length === 0) { res.fails.push(el.tagName.toLowerCase() + ' "' + txt + '" background could not be read'); continue; }
    for (const bg of cands) { const f = over(fg, bg); const rt = ratio(f, bg); if (rt < worst) { worst = rt; worstBg = bg; } }
    if (worst < need) res.fails.push(el.tagName.toLowerCase() + '.' + (el.className||'').toString().slice(0,40) + ' "' + txt + '" ratio ' + worst.toFixed(2) + ' need ' + need + ' size ' + size + ' fg ' + cs.color + ' bg ' + (worstBg ? worstBg.slice(0,3).map(Math.round).join(',') : '?'));
  }
  return {texts: res.texts, fails: res.fails, invisible: res.invisible, overflow: res.overflow,
          sw: document.documentElement.scrollWidth, cw: document.documentElement.clientWidth, h: document.documentElement.scrollHeight};
}
"""


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--css", default=str(ROOT / "examples" / "demo-site.css"))
    ap.add_argument("--block-css", default="", help="a copy of WordPress's block-library/style.min.css (optional)")
    ap.add_argument("--shots", default="", help="folder for full-page screenshots (optional)")
    a = ap.parse_args()

    css = Path(a.css).read_text(encoding="utf-8")
    lib = Path(a.block_css).read_text(encoding="utf-8") if a.block_css else ""
    lib += FALLBACK_LIBRARY if not a.block_css else "body{margin:0;font-family:Arial,sans-serif}"
    styles = registered_styles()
    n_styles = sum(len(v) for v in styles.values())
    n_patterns = len(list((PLUGIN / "patterns").glob("*.html")))
    lines = [f"CSD Site Styles render check: {a.css}", f"css bytes: {len(css.encode('utf-8'))}",
             f"registered block styles: {n_styles}, patterns: {n_patterns}",
             "Block library stylesheet: " + (a.block_css if a.block_css else "not given (fallback layout rules)"), ""]
    bad = 0
    if n_styles != 24 or n_patterns != 8:
        bad += 1
        lines.append("PROBLEM: expected 24 block styles and 8 patterns")
    missing = [n for v in styles.values() for n, _ in v if ".is-style-" + n not in css]
    if missing:
        bad += len(missing)
        lines.append("PROBLEM: the stylesheet has no rule for: " + ", ".join(missing))

    tmp = Path(tempfile.mkdtemp(prefix="csd-render-"))
    shots = Path(a.shots) if a.shots else None
    if shots:
        shots.mkdir(parents=True, exist_ok=True)
    pages = [("gallery", gallery_body(styles)), ("extras", extra_body())]
    with sync_playwright() as pw:
        br = pw.chromium.launch()
        for name, body in pages:
            doc = ('<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
                   f"<style>{lib}</style><style>{css}</style><style>{STOP_MOTION}</style></head><body>{body}</body></html>")
            f = tmp / f"{name}.html"
            f.write_text(doc, encoding="utf-8")
            for w in (1440, 390):
                pg = br.new_page(viewport={"width": w, "height": 900}, reduced_motion="reduce")
                pg.goto(f.as_uri())
                pg.wait_for_timeout(300)
                r = pg.evaluate(JS)
                if shots:
                    pg.screenshot(path=str(shots / f"{name}-{w}.png"), full_page=True)
                pg.close()
                ov = r["sw"] > r["cw"] or bool(r["overflow"])
                bad += int(ov) + len(r["fails"]) + len(r["invisible"])
                lines.append(f"[{name} @ {w}] height {r['h']}  overflow: {'YES' if ov else '0'}  text elements {r['texts']}  contrast fails {len(r['fails'])}  invisible {len(r['invisible'])}")
                lines += ["    overflow: " + x for x in r["overflow"][:10]]
                lines += ["    contrast: " + x for x in r["fails"]]
                lines += ["    invisible: " + x for x in r["invisible"]]
        br.close()
    lines += ["", "TOTAL problems: " + str(bad)]
    print("\n".join(lines))
    return 1 if bad else 0


if __name__ == "__main__":
    sys.exit(main())
