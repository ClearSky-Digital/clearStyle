# Contributing

Thank you for helping. This repository is public, so issues and pull requests are visible to everyone. Issues are welcome. Support and review are best effort, with no promised response times.

## Before you start

- Open an [issue](https://github.com/gcollins808/csd-site-styles/issues) first for anything bigger than a small fix, so we can agree on the direction.
- Keep the plugin free of styling. It registers names and patterns and prints the CSS a site saves. Looks belong in `examples/` or in your own CSS file.
- Keep client or site specific names, colors, fonts, images and addresses out of every file, including examples and tests.

## Make a change

1. Fork the repository and start your branch from `main`.
2. Make the change. Follow the WordPress coding standards (tabs, Yoda conditions, escaped output). Use US spelling and plain wording in text people read.
3. Run the checks:
   - `php tests/sanitizer-test.php`
   - `composer install` then `vendor/bin/phpcs` (errors fail the build)
   - `python3 tests/render_check.py` when you change `examples/demo-site.css` (needs Playwright; the README shows the install lines)
   - `python3 bin/build-zip.py` to confirm the zip builds
4. If you change what the sanitizer accepts or refuses, add a case to `tests/sanitizer-test.php` and update the README and `csd-site-styles/readme.txt`.
5. Add a line to `CHANGELOG.md` under an Unreleased heading.
6. Open a [pull request](https://github.com/gcollins808/csd-site-styles/pulls) from your fork and say what changed and why.

## Releases

A maintainer raises the version in `csd-site-styles/csd-site-styles.php` (header and `CSD_SITE_STYLES_VERSION`) and in `csd-site-styles/readme.txt` (Stable tag, the Changelog and the Upgrade Notice), moves the changelog entry under the new version, then pushes a tag such as `v1.5.2`. The release workflow refuses a tag that does not match the plugin version.

## License

By contributing you agree that your work is licensed under GPL-2.0-or-later, the same as the project.
