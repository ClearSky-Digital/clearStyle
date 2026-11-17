<?php
/**
 * Runs when the plugin is deleted from the Plugins screen: removes the saved CSS and any waiting message.
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

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$csd_site_styles_clean = function () {
	delete_option( 'csd_site_styles_config' );
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one-time cleanup of this plugin's own short-lived messages.
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_csd_site_styles_notice_' ) . '%', $wpdb->esc_like( '_transient_timeout_csd_site_styles_notice_' ) . '%' ) );
};

if ( is_multisite() ) {
	// Walk the network in pages of 100 sites, so a very large network is not loaded in one query.
	$csd_site_styles_offset = 0;
	$csd_site_styles_batch  = 100;
	$csd_site_styles_found  = 0;
	do {
		$csd_site_styles_ids = get_sites(
			array(
				'fields' => 'ids',
				'number' => $csd_site_styles_batch,
				'offset' => $csd_site_styles_offset,
			)
		);
		foreach ( $csd_site_styles_ids as $csd_site_styles_site_id ) {
			switch_to_blog( $csd_site_styles_site_id );
			$csd_site_styles_clean();
			restore_current_blog();
		}
		$csd_site_styles_found   = count( $csd_site_styles_ids );
		$csd_site_styles_offset += $csd_site_styles_batch;
	} while ( $csd_site_styles_found === $csd_site_styles_batch );
} else {
	$csd_site_styles_clean();
}
