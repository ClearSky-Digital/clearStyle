=== ClearStyle ===
Contributors: clearskydigital
Tags: block styles, block patterns, custom css, gutenberg
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.6.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Block style names, block patterns and a settings page for pages built from WordPress blocks. The look comes from CSS you save on the settings page.

== Description ==

ClearStyle registers 24 block style names (for example "CSD Card" and "CSD Hero band") and eight block patterns written as plain block markup. It ships no CSS, fonts or artwork.

Open Settings, ClearStyle. Upload a .css file or paste CSS, and save it. The plugin prints that CSS on pages that use the `csd-page` class and in the block editor. The same page shows a gallery of every block style, pattern and block, with and without your CSS, so you can see the result before you open a real page.

The CSS is checked before it is saved. Text that contains a less-than sign, `@import`, a `url()` other than a `data:image/` address or a `#fragment`, `expression()`, `javascript:`, `behavior:`, `progid:`, `-moz-binding`, a null character, invalid UTF-8 or unbalanced braces is refused, and the saved CSS stays as it was. The limit is 256 KB. Nothing is repaired: the text that passes is the text that prints.

The source repository includes a neutral example stylesheet (`examples/demo-site.css`) that styles every block style and pattern. Copy it and change the values at the top.

Your theme loads the fonts; this plugin loads none.

== Installation ==

1. Upload the zip under Plugins, Add New Plugin, Upload Plugin, and activate it. Or unzip it and copy the `clearstyle` folder into `wp-content/plugins/`.
2. Open Settings, ClearStyle, choose your CSS file (or paste CSS) and press Save CSS.
3. In the page editor, give the outer group the class `csd-page`, or insert a pattern from the ClearStyle category, which already carries it.

== Frequently Asked Questions ==

= Why do my pages look plain after I activate the plugin? =

The plugin carries no styling. Until you save a CSS file on Settings, ClearStyle, pages that use the `csd-page` class show as plain WordPress blocks. A notice on the Dashboard and Plugins screens tells an administrator when no CSS is saved.

= Where does my CSS load? =

On the site it loads only on a single page or post whose content contains `csd-page`, and in the block editor. The class must be in the page's own content. A band placed in a theme template or a widget area does not switch the CSS on. Start every rule with `.csd-page` so nothing reaches the rest of your site. A site owner can switch it on elsewhere with the filter `csd_site_styles_should_load`.

= Can I use web fonts? =

Not through the saved CSS. `@import` and `url()` to a font host are refused, and so are `data:font/` addresses. Load fonts from your theme or a font plugin and name them in your CSS.

= Why was my CSS refused? =

The settings page lists what failed. The most common causes are a less-than sign (even inside a comment), `@import`, or a `url()` that points to a web address instead of a `data:image/` address.

= Does it work on multisite? =

The CSS is stored in one option for each site, so each site saves its own. Uninstalling removes the option from every site. The settings page needs the `manage_options` capability. On a multisite network it also needs `edit_css`, the capability core's Additional CSS asks for, so only super admins can save CSS there.

= What does it store? =

One option named `csd_site_styles_config` (the CSS, the time it was saved and the ID of the user who saved it), not autoloaded. A short-lived message (up to two minutes) is kept for the page you are redirected to after saving.

= What happens when I delete the plugin? =

Deleting it from the Plugins screen removes the saved CSS and any waiting message.

= Does it send data anywhere? =

No. The plugin makes no outside requests, sets no cookies and does no tracking. The gallery frame is sandboxed and cannot load anything except the WordPress block library stylesheet and inline `data:` images.

= I use CSD Site Styles. How do I switch to ClearStyle? =

ClearStyle is the new name of CSD Site Styles, and WordPress installs it as a separate plugin. Install ClearStyle, deactivate CSD Site Styles, then activate ClearStyle. It reads the same saved option, so your CSS carries over and your pages look the same. Keep the two from running together: activate ClearStyle only after CSD Site Styles is deactivated. Check one page first, and delete CSD Site Styles afterwards. Deleting it removes the shared saved CSS, and you would then save your CSS file again on the ClearStyle settings page.

= Where do I get new versions and help? =

Download `clearstyle-<version>.zip` from the Releases page of https://github.com/ClearSky-Digital/clearstyle (not "Source code (zip)"), and watch the repository for new releases. Support is best effort, with no promised response times. Open an issue there with questions.

== Changelog ==

= 1.6.0 =
* Renamed from CSD Site Styles; saved settings carry over.
* The plugin folder, main file, slug and text domain are now clearstyle. Option names, hooks and function names are unchanged.

= 1.5.2 =
* On a multisite network, saving or clearing CSS now also needs the edit_css capability (as core's Additional CSS does), so only super admins can do it. A single site is unchanged.
* On multisite the Settings menu item shows only to people who can use the page.
* A CSS file field posted as an array is refused with the usual message instead of a PHP warning.
* Uninstalling on a network visits the sites in pages of 100.

= 1.5.1 =
* New block styles: CSD Photo band and CSD Photo tile (Cover block) and CSD Icon badge (Image block), and two patterns: photo band and three photo tiles. They show in the gallery with a placeholder image.
* The editor labels of the Light and Dark band styles are now Plain band and Accent band. Class names are unchanged.

= 1.5.0 =
* The plugin carries no styling. Styling comes from CSS saved on the settings page.
* New settings page with a gallery, and a Dashboard and Plugins notice while no CSS is saved.
* A saved CSS file may use only `data:image/` addresses and `#fragment` addresses in `url()`; an address with an apostrophe or a space must be in quotes.
* Removed the stylesheets, the tokens file and the font loader.

== Upgrade Notice ==

= 1.6.0 =
ClearStyle is the new name of CSD Site Styles and installs as its own plugin. Deactivate CSD Site Styles, then activate ClearStyle, and your saved CSS carries over.

= 1.5.2 =
On a multisite network only super admins can save CSS from now on. A single site is unchanged.

= 1.5.1 =
Adds photo band, photo tile and icon badge styles and two patterns. They carry no styling; add rules for them to your saved CSS. The editor labels of the Light and Dark band styles now read Plain band and Accent band; the class names are unchanged, so existing pages are not affected.

= 1.5.0 =
This version carries no styling. After you update, open Settings, ClearStyle and save your CSS file, or pages that use csd-page lose their look.
