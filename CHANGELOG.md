# Changelog

All notable changes to ClearStyle (formerly CSD Site Styles) are listed here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and versions follow [Semantic Versioning](https://semver.org/).

## [1.6.0] - 2026-11-17

### Changed
- Renamed from CSD Site Styles; saved settings carry over.
- The plugin folder, main file, slug and text domain are now `clearstyle`, the settings page is Settings, ClearStyle, and the release zip is `clearstyle-<version>.zip`.
- Every option name, hook name and function prefix (`csd_site_styles_*`) is unchanged, so a site that ran CSD Site Styles keeps its saved CSS after it switches. To switch, deactivate CSD Site Styles and activate ClearStyle.

## [1.5.2] - 2026-11-17

### Changed
- On a multisite network, saving, clearing and viewing the settings page also need the `edit_css` capability (as core's Additional CSS does), on top of `manage_options`. Only super admins hold it there. A single site is unchanged.
- On multisite the Settings menu item shows only to people who can use the page (`manage_options` and `edit_css`), so a role with `edit_css` alone no longer sees a menu item that ends in a 403.
- A `csd_css_file` field posted as an array is refused with the usual "did not upload" or extension message and raises no PHP warning.
- Uninstall on a network visits the sites in pages of 100 instead of loading them all at once.

### Noted, not changed
- `csd_site_styles_should_load()` returns true in the admin when the current screen is not known. The function is hooked to `enqueue_block_assets` only, which runs after the screen is set on the admin screens we checked, and the only effect is printing the same CSS the public site prints, so there is no exposure. Returning false instead would change the block editor behaviour and was not tested, so it is left as it is.

## [1.5.1] - 2026-11-15

### Added
- Block styles `csd-photo-band` and `csd-photo-tile` on the Cover block, and `csd-icon` on the Image block. They carry no styling; the saved CSS decides how they look.
- Two block patterns written as plain markup: "Photo band with heading and buttons" and "Three photo tiles". They contain no images and no style attributes.
- The gallery adds a one-pixel data image to the Cover blocks in its own copy so the photo styles show. It fetches nothing.
- `examples/demo-site.css`, a neutral example stylesheet that covers all 24 block styles and all eight patterns.
- GitHub Actions: PHP lint on PHP 7.4 to 8.4, PHPCS with the WordPress standards, the sanitizer test, a render check of the demo stylesheet, and a release workflow that builds the zip.

### Changed
- The settings page also refuses `progid:`, and a comment placed inside `expression(`, `javascript:`, `behavior:` or `vbscript:` no longer hides it.
- An upload larger than the server accepts now shows a message that says so.
- In the admin, the saved CSS loads on block editor screens only.
- The editor labels of `csd-light` and `csd-dark` are now "CSD Plain band" and "CSD Accent band". The class names `is-style-csd-light` and `is-style-csd-dark` are unchanged, so existing pages and saved CSS keep working.
- The plugin is licensed GPL-2.0-or-later, with a license header in every PHP file.

## [1.5.0] - 2026-11-15

### Added
- A settings page, Settings, CSD Site Styles, that takes a CSS file or pasted CSS, checks it, saves it in one option (`csd_site_styles_config`, not autoloaded) and prints it as inline CSS.
- A gallery under the form that draws every block style, pattern and core block with the saved CSS.
- A notice on the Dashboard and Plugins screens while no CSS is saved.
- Checks that refuse CSS larger than 256 KB, markup, `@import`, `javascript:`, `behavior:` and `-moz-binding`, outside `url()` addresses, and CSS that cannot be read. Nothing is repaired.
- An uninstall step that deletes the saved CSS and any waiting message.

### Changed
- The plugin no longer carries any styling. Pages that use `csd-page` show as plain blocks until CSS is saved on the settings page.
- A saved CSS file may use only `data:image/` addresses and `#fragment` addresses in `url()`. An address with an apostrophe or a space must be in quotes.
- The block style names are the same class names as before, with neutral editor labels.

### Removed
- The stylesheets, the tokens file and the font loader. Fonts are loaded by the theme.

## Earlier versions

Versions before 1.5.0 carried their stylesheets inside the plugin. They are not part of this repository.
