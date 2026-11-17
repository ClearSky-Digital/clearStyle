<?php
/**
 * Block patterns for ClearStyle.
 *
 * Each pattern is a plain block markup file in the patterns folder. The files are read from this plugin's own folder.
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
 * Register the pattern category and the eight patterns.
 */
function csd_site_styles_register_patterns() {
	register_block_pattern_category(
		'csd',
		array( 'label' => __( 'ClearStyle', 'clearstyle' ) )
	);

	$dir = plugin_dir_path( __DIR__ ) . 'patterns/';

	$patterns = array(
		'csd-hero'          => array(
			'title'       => __( 'Opening band with page title', 'clearstyle' ),
			'description' => __( 'An opening band with the page title, a where-we-work note, a subtitle, text and a link.', 'clearstyle' ),
			'keywords'    => array( 'hero', 'title', 'opening' ),
		),
		'csd-split-section' => array(
			'title'       => __( 'Band with text and a side box', 'clearstyle' ),
			'description' => __( 'A band with a label, a subtitle, text on the left and a placeholder box on the right.', 'clearstyle' ),
			'keywords'    => array( 'split', 'two columns', 'section' ),
		),
		'csd-cards-band'    => array(
			'title'       => __( 'Band with three cards', 'clearstyle' ),
			'description' => __( 'A band with a label, a subtitle and three cards.', 'clearstyle' ),
			'keywords'    => array( 'cards', 'services', 'list' ),
		),
		'csd-text-band'     => array(
			'title'       => __( 'Band with text', 'clearstyle' ),
			'description' => __( 'A band with a label, a subtitle and two paragraphs.', 'clearstyle' ),
			'keywords'    => array( 'text', 'section' ),
		),
		'csd-panels-band'   => array(
			'title'       => __( 'Band with two panels', 'clearstyle' ),
			'description' => __( 'A band with two side-by-side panels, each with a label, a subtitle and text.', 'clearstyle' ),
			'keywords'    => array( 'panels', 'two columns' ),
		),
		'csd-closing-band'  => array(
			'title'       => __( 'Closing band with a button', 'clearstyle' ),
			'description' => __( 'A closing band with a panel, a short invitation and a button.', 'clearstyle' ),
			'keywords'    => array( 'closing', 'button', 'contact' ),
		),
		'csd-photo-band'    => array(
			'title'       => __( 'Photo band with heading and buttons', 'clearstyle' ),
			'description' => __( 'A band over a photo with a heading, a line of text and two buttons. Choose the photo in the Cover block.', 'clearstyle' ),
			'keywords'    => array( 'photo', 'cover', 'band', 'hero' ),
		),
		'csd-photo-tiles'   => array(
			'title'       => __( 'Three photo tiles', 'clearstyle' ),
			'description' => __( 'Three photo tiles, each with a title and a button. Choose each photo in its Cover block.', 'clearstyle' ),
			'keywords'    => array( 'photo', 'tiles', 'neighborhoods', 'cards' ),
		),
	);

	foreach ( $patterns as $file => $info ) {
		$path = $dir . $file . '.html';
		if ( ! is_readable( $path ) ) {
			continue;
		}

		$content = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reads a pattern file from this plugin's own folder.
		if ( false === $content ) {
			continue;
		}

		register_block_pattern(
			'clearstyle/' . $file,
			array(
				'title'         => $info['title'],
				'description'   => $info['description'],
				'categories'    => array( 'csd' ),
				'keywords'      => $info['keywords'],
				'viewportWidth' => 1280,
				'content'       => $content,
			)
		);
	}
}
add_action( 'init', 'csd_site_styles_register_patterns' );
