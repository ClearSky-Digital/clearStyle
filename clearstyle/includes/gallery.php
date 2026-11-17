<?php
/**
 * The gallery on the settings page.
 *
 * It shows every block style, the eight patterns, the core blocks by type and the custom blocks the plugin registers, inside a frame.
 * The frame holds the saved CSS, so the gallery is a preview of what pages will look like. With no saved CSS, or with the plain link, it shows the blocks as WordPress prints them.
 * The frame is sandboxed: nothing inside it can run a script.
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
 * A one-pixel placeholder image as a data address. The gallery frame allows only data: images, so no photo is fetched from anywhere.
 *
 * @return string
 */
function csd_site_styles_gallery_placeholder() {
	return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADElEQVR4nGNoaGgAAAMEAYFL09IQAAAAAElFTkSuQmCC';
}

/**
 * Give each Cover block in the markup a placeholder image, so the photo styles show in the gallery. The pattern files stay markup only; this runs on the gallery copy.
 *
 * @param string $markup Block markup.
 * @return string
 */
function csd_site_styles_gallery_add_photos( $markup ) {
	$img = '<img class="wp-block-cover__image-background" alt="" src="' . esc_attr( csd_site_styles_gallery_placeholder() ) . '" data-object-fit="cover"/>';
	return str_replace( '</span><div class="wp-block-cover__inner-container">', '</span>' . $img . '<div class="wp-block-cover__inner-container">', $markup );
}

/**
 * The sample markup for one registered block style.
 *
 * @param string $block The block name.
 * @param string $name  The style name, such as csd-card.
 * @param string $label The style's label.
 * @return string Block markup.
 */
function csd_site_styles_gallery_sample( $block, $name, $label ) {
	$class = 'is-style-' . $name;
	$text  = esc_html( $label );

	switch ( $block ) {
		case 'core/cover':
			return csd_site_styles_gallery_add_photos( '<!-- wp:cover {"dimRatio":0,"className":"' . esc_attr( $class ) . '"} --><div class="wp-block-cover ' . esc_attr( $class ) . '"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">' . $text . '</h3><!-- /wp:heading --><!-- wp:paragraph --><p>' . esc_html__( 'Sample text over the photo.', 'clearstyle' ) . '</p><!-- /wp:paragraph --></div></div><!-- /wp:cover -->' );
		case 'core/image':
			return '<!-- wp:image {"className":"' . esc_attr( $class ) . '"} --><figure class="wp-block-image ' . esc_attr( $class ) . '"><img src="' . esc_attr( csd_site_styles_gallery_placeholder() ) . '" alt=""/></figure><!-- /wp:image -->';
		case 'core/heading':
			return '<!-- wp:heading {"className":"' . esc_attr( $class ) . '"} --><h2 class="wp-block-heading ' . esc_attr( $class ) . '">' . $text . '</h2><!-- /wp:heading -->';
		case 'core/paragraph':
			return '<!-- wp:paragraph {"className":"' . esc_attr( $class ) . '"} --><p class="' . esc_attr( $class ) . '"><a href="#">' . $text . '</a></p><!-- /wp:paragraph -->';
		case 'core/list':
			return '<!-- wp:list {"className":"' . esc_attr( $class ) . '"} --><ul class="wp-block-list ' . esc_attr( $class ) . '"><li>' . $text . '</li><li>' . esc_html__( 'A second item', 'clearstyle' ) . '</li></ul><!-- /wp:list -->';
		case 'core/quote':
			return '<!-- wp:quote {"className":"' . esc_attr( $class ) . '"} --><blockquote class="wp-block-quote ' . esc_attr( $class ) . '"><p>' . $text . '</p><cite>' . esc_html__( 'Source', 'clearstyle' ) . '</cite></blockquote><!-- /wp:quote -->';
		default:
			return '<!-- wp:group {"className":"' . esc_attr( $class ) . '"} --><div class="wp-block-group ' . esc_attr( $class ) . '"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">' . $text . '</h3><!-- /wp:heading --><!-- wp:paragraph --><p>' . esc_html__( 'Sample text inside the block.', 'clearstyle' ) . '</p><!-- /wp:paragraph --></div><!-- /wp:group -->';
	}
}

/**
 * The registered block styles whose names start with csd-, as array( block => array( name => label ) ).
 *
 * @return array
 */
function csd_site_styles_gallery_styles() {
	$out      = array();
	$registry = WP_Block_Styles_Registry::get_instance();
	foreach ( array( 'core/group', 'core/cover', 'core/image', 'core/heading', 'core/paragraph', 'core/list', 'core/quote' ) as $block ) {
		$styles = $registry->get_registered_styles_for_block( $block );
		foreach ( $styles as $name => $style ) {
			if ( 0 === strpos( $name, 'csd-' ) ) {
				$out[ $block ][ $name ] = isset( $style['label'] ) ? (string) $style['label'] : $name;
			}
		}
	}
	return $out;
}

/**
 * The block names the plugin registers from its build folder, if any.
 *
 * @return string[]
 */
function csd_site_styles_gallery_custom_blocks() {
	$manifest = plugin_dir_path( __DIR__ ) . 'build/blocks-manifest.php';
	if ( ! is_readable( $manifest ) ) {
		return array();
	}
	$list  = include $manifest;
	$names = array();
	if ( is_array( $list ) ) {
		foreach ( $list as $folder => $data ) {
			if ( is_array( $data ) && ! empty( $data['name'] ) && is_string( $data['name'] ) ) {
				$names[] = $data['name'];
			}
		}
	}
	return $names;
}

/**
 * The markup for the core blocks shown by type.
 *
 * @return string Block markup.
 */
function csd_site_styles_gallery_core_blocks() {
	return '<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Heading 1</h1><!-- /wp:heading -->'
		. '<!-- wp:heading --><h2 class="wp-block-heading">Heading 2</h2><!-- /wp:heading -->'
		. '<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Heading 3</h3><!-- /wp:heading -->'
		. '<!-- wp:heading {"level":4} --><h4 class="wp-block-heading">Heading 4</h4><!-- /wp:heading -->'
		. '<!-- wp:paragraph --><p>A paragraph with a <a href="#">link</a>, <strong>bold text</strong> and <em>emphasis</em>.</p><!-- /wp:paragraph -->'
		. '<!-- wp:list --><ul class="wp-block-list"><li>First item</li><li>Second item</li></ul><!-- /wp:list -->'
		. '<!-- wp:list {"ordered":true} --><ol class="wp-block-list"><li>First step</li><li>Second step</li></ol><!-- /wp:list -->'
		. '<!-- wp:quote --><blockquote class="wp-block-quote"><p>A quotation.</p><cite>Source</cite></blockquote><!-- /wp:quote -->'
		. '<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">Button</a></div><!-- /wp:button --></div><!-- /wp:buttons -->'
		. '<!-- wp:separator --><hr class="wp-block-separator has-alpha-channel-opacity"/><!-- /wp:separator -->'
		. '<!-- wp:table --><figure class="wp-block-table"><table><thead><tr><th>Heading</th><th>Heading</th></tr></thead><tbody><tr><td>Cell</td><td>Cell</td></tr></tbody></table></figure><!-- /wp:table -->'
		. '<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:paragraph --><p>First column</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:paragraph --><p>Second column</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns -->';
}

/**
 * Build the whole gallery as one HTML document for the frame.
 *
 * @param string $css The saved CSS, or an empty string for the plain gallery.
 * @return string
 */
function csd_site_styles_gallery_document( $css ) {
	$body = '';

	$body .= '<h2>' . esc_html__( 'Block styles', 'clearstyle' ) . '</h2>';
	foreach ( csd_site_styles_gallery_styles() as $block => $list ) {
		$body .= '<h3>' . esc_html( $block ) . '</h3>';
		foreach ( $list as $name => $label ) {
			$body .= '<p>' . esc_html( $name ) . '</p>' . do_blocks( csd_site_styles_gallery_sample( $block, $name, $label ) );
		}
	}

	$body .= '<h2>' . esc_html__( 'Patterns', 'clearstyle' ) . '</h2>';
	$reg   = WP_Block_Patterns_Registry::get_instance();
	foreach ( $reg->get_all_registered() as $pattern ) {
		if ( 0 === strpos( $pattern['name'], 'clearstyle/' ) ) {
			$body .= '<h3>' . esc_html( $pattern['title'] ) . '</h3>' . do_blocks( csd_site_styles_gallery_add_photos( $pattern['content'] ) );
		}
	}

	$body .= '<h2>' . esc_html__( 'Core blocks by type', 'clearstyle' ) . '</h2>' . do_blocks( csd_site_styles_gallery_core_blocks() );

	$body  .= '<h2>' . esc_html__( 'Custom blocks', 'clearstyle' ) . '</h2>';
	$custom = csd_site_styles_gallery_custom_blocks();
	if ( empty( $custom ) ) {
		$body .= '<p>' . esc_html__( 'This plugin registers no custom blocks yet.', 'clearstyle' ) . '</p>';
	} else {
		foreach ( $custom as $name ) {
			$body .= '<h3>' . esc_html( $name ) . '</h3>' . do_blocks( '<!-- wp:' . $name . ' /-->' );
		}
	}

	$library = includes_url( 'css/dist/block-library/style.min.css' );

	$parts  = wp_parse_url( set_url_scheme( $library, is_ssl() ? 'https' : 'http' ) );
	$origin = $parts['scheme'] . '://' . $parts['host'] . ( ! empty( $parts['port'] ) ? ':' . $parts['port'] : '' );
	$doc    = '<!doctype html><html><head><meta charset="utf-8">';
	$doc   .= '<meta http-equiv="Content-Security-Policy" content="' . esc_attr( "default-src 'none'; style-src 'unsafe-inline' " . $origin . '; img-src data:' ) . '">';
	$doc   .= '<meta name="viewport" content="width=device-width, initial-scale=1">';
	// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- this link is inside the sandboxed gallery frame, which has no enqueue system.
	$doc .= '<link rel="stylesheet" href="' . esc_url( $library ) . '">';
	if ( '' !== $css ) {
		$doc .= '<style>' . $css . '</style>';
	}
	$doc .= '</head><body><div class="csd-page">' . $body . '</div></body></html>';

	return $doc;
}

/**
 * Show the gallery section of the settings page.
 *
 * @param bool $has_css Whether any CSS is saved.
 */
function csd_site_styles_render_gallery_section( $has_css ) {
	$page  = 'options-general.php?page=clearstyle';
	$plain = isset( $_GET['csd_plain'] ) && '1' === $_GET['csd_plain']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display switch, compared with a fixed value.
	$phone = isset( $_GET['csd_view'] ) && 'phone' === $_GET['csd_view']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display switch, compared with a fixed value.

	$css      = ( $plain || ! $has_css ) ? '' : csd_site_styles_get_css();
	$document = csd_site_styles_gallery_document( $css );

	$link = function ( $args ) use ( $page ) {
		return esc_url( admin_url( add_query_arg( $args, $page ) ) );
	};
	?>
	<h2><?php echo esc_html__( 'Gallery', 'clearstyle' ); ?></h2>
	<p>
		<?php
		if ( $plain || ! $has_css ) {
			echo esc_html__( 'Showing the blocks without any saved CSS.', 'clearstyle' );
		} else {
			echo esc_html__( 'Showing the blocks with the saved CSS.', 'clearstyle' );
		}
		?>
	</p>
	<p>
		<?php if ( $has_css ) : ?>
			<?php if ( $plain ) : ?>
				<a class="button" href="<?php echo $link( $phone ? array( 'csd_view' => 'phone' ) : array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in $link. ?>"><?php echo esc_html__( 'Show with the saved CSS', 'clearstyle' ); ?></a>
			<?php else : ?>
				<a class="button" href="<?php echo $link( array_merge( array( 'csd_plain' => '1' ), $phone ? array( 'csd_view' => 'phone' ) : array() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in $link. ?>"><?php echo esc_html__( 'Show without the saved CSS', 'clearstyle' ); ?></a>
			<?php endif; ?>
		<?php endif; ?>
		<?php
		$keep = $plain ? array( 'csd_plain' => '1' ) : array();
		if ( $phone ) :
			?>
			<a class="button" href="<?php echo $link( $keep ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in $link. ?>"><?php echo esc_html__( 'Wide view', 'clearstyle' ); ?></a>
		<?php else : ?>
			<a class="button" href="<?php echo $link( array_merge( $keep, array( 'csd_view' => 'phone' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in $link. ?>"><?php echo esc_html__( 'Phone view', 'clearstyle' ); ?></a>
		<?php endif; ?>
	</p>
	<iframe
		title="<?php echo esc_attr__( 'Gallery of the plugin blocks', 'clearstyle' ); ?>"
		sandbox=""
		width="<?php echo $phone ? '390' : '100%'; ?>"
		height="900"
		srcdoc="<?php echo htmlspecialchars( $document, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- encoded here, double encoding on so entities in the saved CSS stay text. ?>"></iframe>
	<?php
}
