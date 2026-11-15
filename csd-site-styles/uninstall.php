<?php
/**
 * Runs when the plugin is deleted from the Plugins screen: removes the saved CSS and any waiting message.
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
	foreach ( get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	) as $csd_site_styles_site_id ) {
		switch_to_blog( $csd_site_styles_site_id );
		$csd_site_styles_clean();
		restore_current_blog();
	}
} else {
	$csd_site_styles_clean();
}
