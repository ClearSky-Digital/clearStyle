<?php
/**
 * Plugin Name:       CSD Site Styles
 * Plugin URI:        https://github.com/ClearSky-Digital/csd-site-styles
 * Description:       Block style names, block patterns and a settings page for pages built from WordPress's own blocks. The plugin carries no styling: the look comes from a CSS file you save on its settings page.
 * Version:           1.5.2
 * Requires at least: 6.9
 * Requires PHP:      7.4
 * Author:            ClearSky Digital
 * Author URI:        https://cscloud.digital
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       csd-site-styles
 *
 * CSD Site Styles is free software; you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * CSD Site Styles is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General
 * Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program; if not,
 * see <https://www.gnu.org/licenses/>.
 *
 * Copyright (C) 2026 ClearSky Digital LLC (https://cscloud.digital)
 *
 * @package CSD_Site_Styles
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CSD_SITE_STYLES_VERSION', '1.5.2' );
define( 'CSD_SITE_STYLES_OPTION', 'csd_site_styles_config' );

require_once plugin_dir_path( __FILE__ ) . 'includes/patterns.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/block-styles.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/blocks.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/css-sanitizer.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/gallery.php';

/**
 * Decide whether the saved CSS is needed on this request.
 *
 * In the admin it loads only on block editor screens (when WordPress can tell which screen it is on), so the editor shows the page as it will look and other admin screens are left alone.
 * On the site it loads only for a single page or post whose content uses the csd-page class.
 * A site owner can force it on elsewhere with the filter csd_site_styles_should_load.
 */
function csd_site_styles_should_load() {
	if ( is_admin() ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen instanceof WP_Screen ) {
			return (bool) $screen->is_block_editor();
		}
		return true;
	}

	$load = false;
	if ( is_singular() ) {
		$post = get_post();
		if ( $post instanceof WP_Post && false !== strpos( $post->post_content, 'csd-page' ) ) {
			$load = true;
		}
	}

	return (bool) apply_filters( 'csd_site_styles_should_load', $load );
}

/**
 * Print the saved CSS as inline CSS. The saved text is checked again before it is printed.
 * enqueue_block_assets runs on the site and in the block editor, including the editor's content frame.
 */
function csd_site_styles_enqueue() {
	if ( ! csd_site_styles_should_load() ) {
		return;
	}

	$css = csd_site_styles_get_css();
	if ( '' === $css ) {
		return;
	}

	wp_register_style( 'csd-site-styles-config', false, array(), CSD_SITE_STYLES_VERSION );
	wp_enqueue_style( 'csd-site-styles-config' );
	wp_add_inline_style( 'csd-site-styles-config', $css );
}
add_action( 'enqueue_block_assets', 'csd_site_styles_enqueue' );
