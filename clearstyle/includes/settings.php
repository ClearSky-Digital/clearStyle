<?php
/**
 * The settings page for ClearStyle.
 *
 * One page under Settings. It takes a CSS file or pasted CSS, checks it (css-sanitizer.php), saves it in one option and shows it.
 * Only people who can manage options reach it. Saving and clearing each need a nonce.
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
 * The saved CSS, checked again before use. Returns an empty string when nothing is saved or the saved text no longer passes.
 *
 * @return string
 */
function csd_site_styles_get_css() {
	static $cache = array();
	$site         = get_current_blog_id();
	if ( isset( $cache[ $site ] ) ) {
		return $cache[ $site ];
	}

	$cache[ $site ] = '';
	$config         = get_option( CSD_SITE_STYLES_OPTION, array() );
	if ( ! is_array( $config ) || empty( $config['css'] ) || ! is_string( $config['css'] ) ) {
		return $cache[ $site ];
	}

	$result         = csd_site_styles_validate_css( $config['css'] );
	$cache[ $site ] = $result['ok'] ? $result['css'] : '';

	return $cache[ $site ];
}

/**
 * Whether the current user may view and change the saved CSS.
 * Needs manage_options. On a multisite network it also needs edit_css, the capability core's Additional CSS asks for,
 * which only super admins hold, so a site administrator cannot save CSS there. A single site is unchanged.
 */
function csd_site_styles_user_can_manage() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}
	return ! is_multisite() || current_user_can( 'edit_css' );
}

/**
 * Add the page under Settings.
 */
function csd_site_styles_add_menu() {
	// Only people who can use the page see the menu item: a role with edit_css alone on multisite would otherwise open it and get a 403.
	if ( ! csd_site_styles_user_can_manage() ) {
		return;
	}
	add_options_page(
		__( 'ClearStyle', 'clearstyle' ),
		__( 'ClearStyle', 'clearstyle' ),
		'manage_options',
		'clearstyle',
		'csd_site_styles_render_page'
	);
}
add_action( 'admin_menu', 'csd_site_styles_add_menu' );

/**
 * A notice on the Dashboard and Plugins screens while no CSS is saved, with a link to the settings page.
 * The plugin carries no styling, so until a CSS file is saved pages that use csd-page show as plain blocks.
 */
function csd_site_styles_no_css_notice() {
	if ( ! csd_site_styles_user_can_manage() || '' !== csd_site_styles_get_css() ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'plugins' ), true ) ) {
		return;
	}
	printf(
		'<div class="notice notice-warning is-dismissible csd-site-styles-no-css"><p>%s <a href="%s">%s</a></p></div>',
		esc_html__( 'ClearStyle is active but no CSS is saved. Pages that use the csd-page class show as plain blocks until you save a CSS file.', 'clearstyle' ),
		esc_url( admin_url( 'options-general.php?page=clearstyle' ) ),
		esc_html__( 'Open ClearStyle', 'clearstyle' )
	);
}
add_action( 'admin_notices', 'csd_site_styles_no_css_notice' );

/**
 * Remember a message for the next load of the page (the redirect after a save).
 *
 * @param string   $type   success, error or info.
 * @param string[] $lines  The lines to show.
 */
function csd_site_styles_set_notice( $type, $lines ) {
	set_transient(
		'csd_site_styles_notice_' . get_current_user_id(),
		array(
			'type'  => $type,
			'lines' => array_values( array_map( 'strval', (array) $lines ) ),
		),
		120
	);
}

/**
 * Send the person back to the settings page.
 */
function csd_site_styles_back() {
	wp_safe_redirect( admin_url( 'options-general.php?page=clearstyle' ) );
	exit;
}

// phpcs:disable WordPress.Security.NonceVerification.Missing -- the nonce is checked by check_admin_referer() in csd_site_styles_handle_save() before this function runs.
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- the CSS is raw text on purpose: it is validated, never repaired, by csd_site_styles_validate_css().
/**
 * Read the CSS the person gave: an uploaded .css file wins over pasted text.
 *
 * @param string[] $errors Filled with messages.
 * @return string|null The raw CSS, or null when nothing usable was given.
 */
function csd_site_styles_read_input( &$errors ) {
	$has_file = isset( $_FILES['csd_css_file'] ) && is_array( $_FILES['csd_css_file'] )
		&& isset( $_FILES['csd_css_file']['error'] ) && UPLOAD_ERR_NO_FILE !== (int) $_FILES['csd_css_file']['error'];

	if ( $has_file ) {
		$error = (int) $_FILES['csd_css_file']['error'];
		// A field posted as an array (csd_css_file[name][]) is not a real upload: treat its parts as empty so nothing is cast from an array.
		$name  = ( isset( $_FILES['csd_css_file']['name'] ) && is_string( $_FILES['csd_css_file']['name'] ) ) ? sanitize_file_name( wp_unslash( $_FILES['csd_css_file']['name'] ) ) : '';
		$tmp   = ( isset( $_FILES['csd_css_file']['tmp_name'] ) && is_string( $_FILES['csd_css_file']['tmp_name'] ) ) ? $_FILES['csd_css_file']['tmp_name'] : '';
		$size  = isset( $_FILES['csd_css_file']['size'] ) ? (int) $_FILES['csd_css_file']['size'] : 0;

		if ( UPLOAD_ERR_OK !== $error ) {
			$errors[] = __( 'The file did not upload.', 'clearstyle' );
			return null;
		}
		if ( 'css' !== strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) ) {
			$errors[] = __( 'The file must end in .css.', 'clearstyle' );
			return null;
		}
		if ( $size > CSD_SITE_STYLES_CSS_MAX ) {
			$errors[] = __( 'The file is larger than the size limit.', 'clearstyle' );
			return null;
		}
		if ( '' === $tmp || ! is_uploaded_file( $tmp ) ) {
			$errors[] = __( 'The file did not upload.', 'clearstyle' );
			return null;
		}
		$text = file_get_contents( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reads the uploaded local file.
		if ( false === $text ) {
			$errors[] = __( 'The file could not be read.', 'clearstyle' );
			return null;
		}
		if ( '' === trim( $text ) ) {
			$errors[] = __( 'The file is empty. To remove the saved CSS, use Clear saved CSS.', 'clearstyle' );
			return null;
		}
		return $text;
	}

	if ( isset( $_POST['csd_css_text'] ) && is_string( $_POST['csd_css_text'] ) ) {
		$text = wp_unslash( $_POST['csd_css_text'] );
		if ( '' !== trim( $text ) ) {
			return $text;
		}
	}

	$errors[] = __( 'Choose a CSS file or paste CSS before saving. To remove the saved CSS, use Clear saved CSS.', 'clearstyle' );
	return null;
}

// phpcs:enable WordPress.Security.NonceVerification.Missing
// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

/**
 * Save the CSS.
 */
function csd_site_styles_handle_save() {
	if ( ! csd_site_styles_user_can_manage() ) {
		wp_die( esc_html__( 'You do not have permission to change these settings.', 'clearstyle' ), 403 );
	}
	// A file larger than the server's post_max_size empties $_POST before WordPress sees it, so the nonce check would show a confusing "link expired" message.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- only decides which message to show; nothing is saved on this path.
	if ( empty( $_POST ) && isset( $_SERVER['CONTENT_LENGTH'] ) && (int) $_SERVER['CONTENT_LENGTH'] > 0 ) {
		csd_site_styles_set_notice(
			'error',
			array(
				__( 'The CSS was not saved. The saved CSS is unchanged.', 'clearstyle' ),
				__( 'The upload is larger than this server accepts. Use a smaller file or paste the CSS instead.', 'clearstyle' ),
			)
		);
		csd_site_styles_back();
	}
	check_admin_referer( 'csd_site_styles_save' );

	$errors = array();
	$raw    = csd_site_styles_read_input( $errors );
	if ( null !== $raw ) {
		$result = csd_site_styles_validate_css( $raw );
		if ( $result['ok'] ) {
			update_option(
				CSD_SITE_STYLES_OPTION,
				array(
					'css'   => $result['css'],
					'saved' => time(),
					'user'  => get_current_user_id(),
				),
				false
			);
			csd_site_styles_set_notice( 'success', array( __( 'The CSS was saved.', 'clearstyle' ) ) );
			csd_site_styles_back();
		}
		$errors = $result['errors'];
	}

	array_unshift( $errors, __( 'The CSS was not saved. The saved CSS is unchanged.', 'clearstyle' ) );
	csd_site_styles_set_notice( 'error', $errors );
	csd_site_styles_back();
}
add_action( 'admin_post_csd_site_styles_save', 'csd_site_styles_handle_save' );

/**
 * Clear the saved CSS.
 */
function csd_site_styles_handle_clear() {
	if ( ! csd_site_styles_user_can_manage() ) {
		wp_die( esc_html__( 'You do not have permission to change these settings.', 'clearstyle' ), 403 );
	}
	check_admin_referer( 'csd_site_styles_clear' );

	delete_option( CSD_SITE_STYLES_OPTION );
	csd_site_styles_set_notice( 'success', array( __( 'The saved CSS was cleared.', 'clearstyle' ) ) );
	csd_site_styles_back();
}
add_action( 'admin_post_csd_site_styles_clear', 'csd_site_styles_handle_clear' );

/**
 * Show the page.
 */
function csd_site_styles_render_page() {
	if ( ! csd_site_styles_user_can_manage() ) {
		wp_die( esc_html__( 'You do not have permission to open this page.', 'clearstyle' ), 403 );
	}

	$config = get_option( CSD_SITE_STYLES_OPTION, array() );
	$config = is_array( $config ) ? $config : array();
	$css    = csd_site_styles_get_css();
	$notice = get_transient( 'csd_site_styles_notice_' . get_current_user_id() );
	if ( false !== $notice ) {
		delete_transient( 'csd_site_styles_notice_' . get_current_user_id() );
	}

	$stored_but_rejected = ! empty( $config['css'] ) && '' === $css;
	$action_url          = admin_url( 'admin-post.php' );
	$max_kb              = number_format_i18n( CSD_SITE_STYLES_CSS_MAX / 1024 );
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'ClearStyle', 'clearstyle' ); ?></h1>
		<p><?php echo esc_html__( 'This plugin carries no styling. It registers block style names and block patterns, and it prints the CSS you save here on pages that use the csd-page class and in the block editor.', 'clearstyle' ); ?></p>

		<?php if ( is_array( $notice ) && ! empty( $notice['lines'] ) ) : ?>
			<div class="notice notice-<?php echo esc_attr( 'success' === $notice['type'] ? 'success' : 'error' ); ?>">
				<?php foreach ( $notice['lines'] as $line ) : ?>
					<p><?php echo esc_html( $line ); ?></p>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( $stored_but_rejected ) : ?>
			<div class="notice notice-warning">
				<p><?php echo esc_html__( 'The saved CSS no longer passes the checks, so it is not being printed. Save a new file or clear it.', 'clearstyle' ); ?></p>
			</div>
		<?php endif; ?>

		<h2><?php echo esc_html__( 'Saved CSS', 'clearstyle' ); ?></h2>
		<?php if ( '' === $css ) : ?>
			<p><?php echo esc_html__( 'No CSS is saved. Pages built from the patterns show as plain blocks.', 'clearstyle' ); ?></p>
		<?php else : ?>
			<p>
				<?php
				$when = ! empty( $config['saved'] ) ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $config['saved'] ) : '';
				echo esc_html(
					sprintf(
						/* translators: 1: size in kilobytes, 2: date and time */
						__( '%1$s KB saved %2$s.', 'clearstyle' ),
						number_format_i18n( strlen( $css ) / 1024, 1 ),
						$when
					)
				);
				?>
			</p>
			<label class="screen-reader-text" for="csd-saved-css"><?php echo esc_html__( 'The saved CSS', 'clearstyle' ); ?></label>
			<textarea id="csd-saved-css" class="large-text code" rows="12" readonly="readonly"><?php echo esc_textarea( $css ); ?></textarea>
			<form method="post" action="<?php echo esc_url( $action_url ); ?>">
				<input type="hidden" name="action" value="csd_site_styles_clear" />
				<?php wp_nonce_field( 'csd_site_styles_clear' ); ?>
				<?php submit_button( __( 'Clear saved CSS', 'clearstyle' ), 'delete', 'csd_clear', false ); ?>
			</form>
		<?php endif; ?>

		<h2><?php echo esc_html__( 'Save CSS', 'clearstyle' ); ?></h2>
		<p>
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: size limit in kilobytes */
					__( 'Upload a .css file or paste CSS. Up to %s KB. Saving replaces the CSS now saved. The text is checked and, if it fails, nothing changes and the page says why.', 'clearstyle' ),
					$max_kb
				)
			);
			?>
		</p>
		<p><?php echo esc_html__( 'Not accepted: a less-than sign, @import, url() other than data:image/ or a #fragment, expression(), javascript:, behavior:, progid:, -moz-binding, a null character, text that is not valid UTF-8, and unbalanced braces. Fonts are loaded by the theme.', 'clearstyle' ); ?></p>
		<form method="post" action="<?php echo esc_url( $action_url ); ?>" enctype="multipart/form-data">
			<input type="hidden" name="action" value="csd_site_styles_save" />
			<?php wp_nonce_field( 'csd_site_styles_save' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="csd_css_file"><?php echo esc_html__( 'CSS file', 'clearstyle' ); ?></label></th>
					<td><input type="file" id="csd_css_file" name="csd_css_file" accept=".css" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="csd_css_text"><?php echo esc_html__( 'Or paste CSS', 'clearstyle' ); ?></label></th>
					<td><textarea id="csd_css_text" name="csd_css_text" class="large-text code" rows="12"></textarea></td>
				</tr>
			</table>
			<?php submit_button( __( 'Save CSS', 'clearstyle' ) ); ?>
		</form>

		<?php csd_site_styles_render_gallery_section( '' !== $css ); ?>
	</div>
	<?php
}
