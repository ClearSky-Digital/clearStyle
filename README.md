# CSD Site Styles

[![CI](https://github.com/gcollins808/csd-site-styles/actions/workflows/ci.yml/badge.svg)](https://github.com/gcollins808/csd-site-styles/actions/workflows/ci.yml)

CSD Site Styles helps you build pages from WordPress's own blocks. It adds 24 block styles and eight ready-made patterns, and it gives you a settings page where you save your own CSS. The plugin leaves all styling to a CSS file you control, so the look of your pages is yours to set.

- Requires WordPress 6.9 or later and PHP 7.4 or later.
- License: GPL-2.0-or-later.

## Getting the plugin and updates

The project lives at https://github.com/gcollins808/csd-site-styles and is free to use.

- Download the plugin from the [Releases page](https://github.com/gcollins808/csd-site-styles/releases) of this repository. Pick the zip named `csd-site-styles-<version>.zip`. Do not pick "Source code (zip)"; that is the whole project, and WordPress cannot install it. Each release also lists a `.sha256` file, which a technical person can use to confirm the download is intact. You can ignore it.
- To hear about new versions, **watch** the repository: Watch, then Custom, then tick Releases.

The zip from the Releases page is ready to install.

For help with the plugin, open an issue on this repository. Support is best effort: we read issues when we can and do not promise response times.

## What it does

- Adds 24 block styles to the Styles panel. When you pick one, WordPress adds a class such as `is-style-csd-card` to the block.
- Adds eight block patterns (an opening band, a split section, a band with three cards, and so on) written as plain block markup.
- Adds a settings page under Settings, then CSD Site Styles. You upload a `.css` file or paste CSS there. The plugin checks it, saves it and prints it on your pages.
- Shows a gallery under the form. It draws every block style, pattern and core block with your saved CSS, so you can see the result before you open a real page.

The plugin ships no stylesheet, fonts, colors or artwork. Until you save CSS, pages show as plain WordPress blocks, and an administrator sees a notice on the Dashboard and Plugins screens.

## Install

**With the zip (the usual way)**

1. Download `csd-site-styles-<version>.zip` from the Releases page (not "Source code (zip)").
2. In WordPress, go to Plugins, Add New Plugin, Upload Plugin. Choose the zip and press Install Now.
3. Press Activate.

**By hand**

1. Unzip the download. You get one folder named `csd-site-styles`.
2. Copy that folder into `wp-content/plugins/` on your site.
3. Activate the plugin on the Plugins screen.

## Use it

1. Open Settings, CSD Site Styles.
2. Choose a CSS file, or paste CSS into the text box, and press **Save CSS**. A good first file is `examples/demo-site.css` from this repository (see below).
3. Look at the gallery under the form. The buttons above it switch between "Show without the saved CSS" and "Show with the saved CSS", and between "Phone view" and "Wide view".
4. On a page, either insert one of the patterns from the CSD Site Styles category (they already carry the class `csd-page`), or select the outer Group block, open Advanced in the block settings, and type `csd-page` in Additional CSS class(es). Then pick a style for each block in its Styles panel.

Your CSS prints only on a single page or post whose content contains `csd-page`, and in the block editor, so the editor shows the page as visitors will see it. Nothing prints on other pages. The class must be in the page's own content. A band placed in a theme template or a widget area does not switch the CSS on. A developer can switch it on elsewhere with the filter `csd_site_styles_should_load`, for example for a template that carries the class.

To remove your CSS, press **Clear saved CSS** on the settings page.

## Write your own CSS from the example

`examples/demo-site.css` is a neutral starting point. It styles all 24 block styles and all eight patterns, with a plain blue, gray and white palette and system fonts only.

1. Copy the file and rename it.
2. Change the values in the first section, `0. Settings`. They are named values (CSS custom properties such as `--csd-accent`) for colors, fonts, spacing and corner radius, each with a comment that says what it does. Most sites only need to change these.
3. Save the new file on the settings page and check the gallery.
4. Check text contrast again if you change a color. Text should be at least 4.5 to 1 against its background.

Keep every rule inside `.csd-page`, as the example does. That keeps your styles away from the rest of your site.

### The 24 block styles

The class is `is-style-` followed by the name.

| Block | Names |
|---|---|
| Group (16) | `csd-hero`, `csd-light`, `csd-dark`, `csd-feature`, `csd-column`, `csd-note`, `csd-split`, `csd-cards`, `csd-pentagon`, `csd-card`, `csd-panels`, `csd-panel`, `csd-placeholder`, `csd-glass`, `csd-facts`, `csd-listing` |
| Cover (2) | `csd-photo-band`, `csd-photo-tile` |
| Image (1) | `csd-icon` |
| Heading (2) | `csd-subtitle`, `csd-ruled` |
| Paragraph (1) | `csd-cta` |
| List (1) | `csd-list` |
| Quote (1) | `csd-quote` |

The editor labels are plain ("CSD Card", "CSD Plain band", "CSD Accent band"). Your CSS decides how each name looks.

### The eight patterns

- Opening band with page title
- Band with text and a side box
- Band with three cards
- Band with text
- Band with two panels
- Closing band with a button
- Photo band with heading and buttons
- Three photo tiles

The patterns are markup only. The two photo patterns have no image, so choose your photo in each Cover block.

## What the settings page checks

The settings page checks your CSS and **refuses** it if anything below is found. It never repairs or edits your text. A refused file changes nothing, and the page lists each reason.

The CSS is refused when it:

1. is larger than 256 KB (262,144 bytes);
2. contains a null character or is not valid UTF-8;
3. contains a less-than sign anywhere, comments and quoted text included, so the printed CSS always stays inside its style element;
4. has an unclosed comment or quoted text, or braces that do not balance;
5. contains `@import`;
6. contains `expression(`, `javascript:`, `vbscript:`, `behavior:`, `progid:` or `-moz-binding` (a comment placed inside one of these names does not hide it);
7. contains `image-set(`, `image(`, `src(`, `element(`, `cross-fade(` or `paint(` as a function;
8. contains a `url()` that is not a `data:image/...` address or a `#fragment`, or one the checks are unable to read (an unquoted address with a space, quote or parenthesis). Fonts as `data:font/` addresses are not accepted. An SVG data address that contains an apostrophe must be in quotes;
9. is something the checks are unable to read. A check that does not finish counts as a refusal.

A file is also refused when it is empty, when it does not end in `.css`, or when a quoted text runs onto a new line. Saving replaces the CSS that was saved before.

Good to know:

- The checks read CSS the way a browser does. Line endings are treated alike, comments and quoted text are set aside, and CSS escapes are decoded, so `@\69mport` is read as `@import`.
- A property called `src` inside `@font-face` is fine (with a `local()` source). Only the function `src(` is refused.
- Only a leading byte order mark is dropped and line endings are made into line feeds. Nothing else changes.
- The same checks run again on the saved text before it is printed, so a bad value written into the database by other means is not printed.
- The checks are a scanner for the rules above and are not a full CSS parser. A value they are unable to judge, such as a made-up color name, is saved.
- If saved CSS ever stops passing the checks (for example after a plugin update), the plugin stops printing it and the settings page says so. Save a new file or clear it.
- An upload larger than your server accepts shows a message that says so. Use a smaller file or paste the CSS instead.

Only administrators can save CSS. The checks cover the rules listed above and nothing more. A rule that is not scoped to `.csd-page` can change the look of your whole site, so start every rule with `.csd-page`.

## Fonts

The plugin loads no fonts, and the settings page refuses `@import` and font addresses in `url()`. Load fonts from your theme, a font plugin, or a link in your site's head, and name them in your CSS. The demo stylesheet uses system fonts, so it needs nothing.

## FAQ

**Why do my pages look plain after I activate it?**
The plugin carries no styling. Save a CSS file on Settings, CSD Site Styles. Until then pages that use `csd-page` show as plain blocks.

**Does it work on multisite?**
The CSS is stored in one option for each site, so each site saves its own. Deleting the plugin removes the option from every site. The settings page needs the `manage_options` capability. On a network, a site administrator who lacks the `unfiltered_html` capability can still save CSS here, so consider who has administrator access.

**What does it store?**
One option, `csd_site_styles_config`: the CSS, the time it was saved and the ID of the user who saved it. It is not autoloaded. A short message (kept for up to two minutes) is stored so the page can show the result of a save.

**What happens when I uninstall it?**
Deleting the plugin from the Plugins screen removes the saved CSS and any waiting message. Deactivating alone keeps the CSS.

**Does it collect data or contact other sites?**
No. It makes no outside requests, sets no cookies and does no tracking. The gallery frame is sandboxed. It can load only WordPress's own block library stylesheet from your site and inline `data:` images.

**Can I use my own photos in the photo styles?**
Yes. Choose the photo in the Cover block. Photos you add in the editor are ordinary page content, so the saved CSS has nothing to load.

## For developers

These notes are for developers; skip them to use the plugin.

```
csd-site-styles/   the plugin (this folder becomes the release zip)
examples/          demo-site.css, a neutral example stylesheet
tests/             the sanitizer test and the render check
bin/build-zip.py   builds the release zip
```

Run the checks locally:

```
php tests/sanitizer-test.php                   # needs PHP 7.4 or later, no WordPress
composer install && vendor/bin/phpcs           # WordPress coding standards
python3 bin/build-zip.py                       # writes dist/csd-site-styles-<version>.zip
python3 -m pip install playwright && python3 -m playwright install chromium
python3 tests/render_check.py                  # draws the gallery with examples/demo-site.css
```

The render check draws every block style, the patterns and the core blocks at 1440 and 390 pixels wide and fails on sideways overflow or text below AA contrast. It takes an optional `--css` flag to check another stylesheet.

GitHub Actions runs the same checks on every push and pull request. Pushing a tag such as `v1.5.1` builds the zip and attaches it to a release; the tag must match the version in the plugin header.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). To report a security problem, see [SECURITY.md](SECURITY.md).

## License

GPL-2.0-or-later. See [LICENSE](LICENSE). You may use, change and pass on copies under the same license. The license is what covers what you do with the code.

---

CSD Site Styles is made and maintained by ClearSky Digital.
