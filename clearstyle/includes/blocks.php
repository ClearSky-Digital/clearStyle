<?php
/**
 * Custom blocks for ClearStyle.
 *
 * No block is shipped yet. Blocks are built outside the plugin and the build writes
 * its output to this plugin's build folder, with a blocks-manifest.php file that lists every block.
 * When the build folder is absent, nothing is registered.
 *
 * ClearStyle is free software; you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * ClearStyle is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General
 * Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program; if not,
 * see <https://www.gnu.org/licenses/>.
 *
 * Copyright (C) 2026 ClearSky Digital LLC (https://cscloud.digital)
 *
 * @package ClearStyle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register every block listed in build/blocks-manifest.php.
 * The block names, scripts, styles and render files come from each block's own block.json.
 */
function csd_site_styles_register_blocks() {
	if ( ! function_exists( 'wp_register_block_types_from_metadata_collection' ) ) {
		return;
	}

	$dir      = plugin_dir_path( __DIR__ ) . 'build/';
	$manifest = $dir . 'blocks-manifest.php';
	if ( ! is_readable( $manifest ) ) {
		return;
	}

	wp_register_block_types_from_metadata_collection( $dir, $manifest );
}
add_action( 'init', 'csd_site_styles_register_blocks' );
