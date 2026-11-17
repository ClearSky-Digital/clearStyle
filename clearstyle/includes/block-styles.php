<?php
/**
 * Block styles for ClearStyle.
 *
 * The plugin ships no styling. It registers class names as core block styles: an editor chooses one in the block's Styles panel, and WordPress adds the class is-style-<name> to the block.
 * What each class looks like comes from the CSS saved on the plugin's settings page; this file only lists the names and labels.
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
 * Register the block styles for the core group, cover, image, heading, paragraph, list and quote blocks.
 */
function csd_site_styles_register_block_styles() {
	$styles = array(
		'core/group'     => array(
			'csd-hero'        => __( 'CSD Hero band', 'clearstyle' ),
			'csd-light'       => __( 'CSD Plain band', 'clearstyle' ),
			'csd-dark'        => __( 'CSD Accent band', 'clearstyle' ),
			'csd-feature'     => __( 'CSD Feature band', 'clearstyle' ),
			'csd-column'      => __( 'CSD Content column', 'clearstyle' ),
			'csd-note'        => __( 'CSD Note', 'clearstyle' ),
			'csd-split'       => __( 'CSD Split layout', 'clearstyle' ),
			'csd-cards'       => __( 'CSD Cards layout', 'clearstyle' ),
			'csd-pentagon'    => __( 'CSD Pentagon layout', 'clearstyle' ),
			'csd-card'        => __( 'CSD Card', 'clearstyle' ),
			'csd-panels'      => __( 'CSD Panels layout', 'clearstyle' ),
			'csd-panel'       => __( 'CSD Panel', 'clearstyle' ),
			'csd-placeholder' => __( 'CSD Placeholder box', 'clearstyle' ),
			'csd-glass'       => __( 'CSD Glass panel', 'clearstyle' ),
			'csd-facts'       => __( 'CSD Facts panel', 'clearstyle' ),
			'csd-listing'     => __( 'CSD Listing frame', 'clearstyle' ),
		),
		'core/cover'     => array(
			'csd-photo-band' => __( 'CSD Photo band', 'clearstyle' ),
			'csd-photo-tile' => __( 'CSD Photo tile', 'clearstyle' ),
		),
		'core/image'     => array(
			'csd-icon' => __( 'CSD Icon badge', 'clearstyle' ),
		),
		'core/heading'   => array(
			'csd-subtitle' => __( 'CSD Subtitle', 'clearstyle' ),
			'csd-ruled'    => __( 'CSD Ruled heading', 'clearstyle' ),
		),
		'core/paragraph' => array(
			'csd-cta' => __( 'CSD Call to action', 'clearstyle' ),
		),
		'core/list'      => array(
			'csd-list' => __( 'CSD List', 'clearstyle' ),
		),
		'core/quote'     => array(
			'csd-quote' => __( 'CSD Quote', 'clearstyle' ),
		),
	);

	foreach ( $styles as $block => $list ) {
		foreach ( $list as $name => $label ) {
			register_block_style(
				$block,
				array(
					'name'  => $name,
					'label' => $label,
				)
			);
		}
	}
}
add_action( 'init', 'csd_site_styles_register_block_styles' );
