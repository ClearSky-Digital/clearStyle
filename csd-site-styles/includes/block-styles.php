<?php
/**
 * Block styles for CSD Site Styles.
 *
 * The plugin ships no styling. It registers class names as core block styles: an editor chooses one in the block's Styles panel, and WordPress adds the class is-style-<name> to the block.
 * What each class looks like comes from the CSS saved on the plugin's settings page; this file only lists the names and labels.
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
 * Copyright (C) 2026 ClearSky Digital
 *
 * @package CSD_Site_Styles
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
			'csd-hero'        => __( 'CSD Hero band', 'csd-site-styles' ),
			'csd-light'       => __( 'CSD Plain band', 'csd-site-styles' ),
			'csd-dark'        => __( 'CSD Accent band', 'csd-site-styles' ),
			'csd-feature'     => __( 'CSD Feature band', 'csd-site-styles' ),
			'csd-column'      => __( 'CSD Content column', 'csd-site-styles' ),
			'csd-note'        => __( 'CSD Note', 'csd-site-styles' ),
			'csd-split'       => __( 'CSD Split layout', 'csd-site-styles' ),
			'csd-cards'       => __( 'CSD Cards layout', 'csd-site-styles' ),
			'csd-pentagon'    => __( 'CSD Pentagon layout', 'csd-site-styles' ),
			'csd-card'        => __( 'CSD Card', 'csd-site-styles' ),
			'csd-panels'      => __( 'CSD Panels layout', 'csd-site-styles' ),
			'csd-panel'       => __( 'CSD Panel', 'csd-site-styles' ),
			'csd-placeholder' => __( 'CSD Placeholder box', 'csd-site-styles' ),
			'csd-glass'       => __( 'CSD Glass panel', 'csd-site-styles' ),
			'csd-facts'       => __( 'CSD Facts panel', 'csd-site-styles' ),
			'csd-listing'     => __( 'CSD Listing frame', 'csd-site-styles' ),
		),
		'core/cover'     => array(
			'csd-photo-band' => __( 'CSD Photo band', 'csd-site-styles' ),
			'csd-photo-tile' => __( 'CSD Photo tile', 'csd-site-styles' ),
		),
		'core/image'     => array(
			'csd-icon' => __( 'CSD Icon badge', 'csd-site-styles' ),
		),
		'core/heading'   => array(
			'csd-subtitle' => __( 'CSD Subtitle', 'csd-site-styles' ),
			'csd-ruled'    => __( 'CSD Ruled heading', 'csd-site-styles' ),
		),
		'core/paragraph' => array(
			'csd-cta' => __( 'CSD Call to action', 'csd-site-styles' ),
		),
		'core/list'      => array(
			'csd-list' => __( 'CSD List', 'csd-site-styles' ),
		),
		'core/quote'     => array(
			'csd-quote' => __( 'CSD Quote', 'csd-site-styles' ),
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
