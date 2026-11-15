<?php
/**
 * The checks every piece of CSS passes before the plugin saves or prints it.
 *
 * The plugin ships no styling. A site owner saves one block of CSS on the settings page and the plugin prints it as inline CSS.
 * This file decides whether a piece of CSS may be saved. It rejects (and never repairs) CSS that could end the style element,
 * carry markup, load something from another address or run a script. The same checks run again before the saved CSS is printed.
 *
 * The scanner reads the CSS the way a browser does: line endings are made into one kind first, comments and quoted strings are set
 * aside, and an unquoted url() is read as one raw token (a comment mark inside it is part of the address, not a comment).
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
 * The largest CSS the settings page accepts, in bytes.
 */
define( 'CSD_SITE_STYLES_CSS_MAX', 262144 );

/**
 * Lower-case ASCII letters only, whatever the server's locale.
 *
 * @param string $text Text.
 * @return string
 */
function csd_site_styles_ascii_lower( $text ) {
	return strtr( $text, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz' );
}

/**
 * Turn CSS escapes (a backslash and a code, or a backslash and a character) into the characters they stand for.
 * A hex escape takes one white space after it; a carriage return and line feed pair counts as one white space.
 *
 * @param string $text CSS text.
 * @return string|null Null when the regular expression fails.
 */
function csd_site_styles_css_unescape( $text ) {
	$result = preg_replace_callback(
		'/\\\\(?:([0-9a-fA-F]{1,6})(?:\r\n|[ \t\n\f\r])?|([^\n\r\f0-9a-fA-F]))/',
		function ( $m ) {
			if ( isset( $m[1] ) && '' !== $m[1] ) {
				$code = hexdec( $m[1] );
				if ( 0 === $code || $code > 0x10FFFF || ( $code >= 0xD800 && $code <= 0xDFFF ) ) {
					return "\xEF\xBF\xBD";
				}
				return html_entity_decode( '&#' . $code . ';', ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			}
			return isset( $m[2] ) ? $m[2] : '';
		},
		$text
	);

	return $result;
}

/**
 * Read a quoted string that starts at $i (the quote). Returns the text inside and the index after the closing quote.
 *
 * @param string   $css    The CSS (line endings already made into line feeds).
 * @param int      $i      Index of the opening quote.
 * @param string[] $errors Filled with messages.
 * @return array|false     array( text, next index ) or false.
 */
function csd_site_styles_css_read_string( $css, $i, &$errors ) {
	$len   = strlen( $css );
	$quote = $css[ $i ];
	$j     = $i + 1;
	$buf   = '';
	while ( true ) {
		if ( $j >= $len ) {
			$errors[] = __( 'A quoted text is not closed.', 'csd-site-styles' );
			return false;
		}
		$d = $css[ $j ];
		if ( '\\' === $d ) {
			$buf .= substr( $css, $j, 2 );
			$j   += 2;
			continue;
		}
		if ( "\n" === $d ) {
			$errors[] = __( 'A quoted text runs onto a new line without being closed.', 'csd-site-styles' );
			return false;
		}
		if ( $d === $quote ) {
			return array( $buf, $j + 1 );
		}
		$buf .= $d;
		++$j;
	}
}

/**
 * Read the CSS once: take out comments, strings and url() addresses, count braces, and report broken pieces.
 *
 * @param string   $css    The CSS (line endings already made into line feeds).
 * @param string[] $errors Filled with messages.
 * @return array|false     array( 'plain' => code with strings and urls set aside, 'urls' => the url() addresses ) or false.
 */
function csd_site_styles_css_scan( $css, &$errors ) {
	$len   = strlen( $css );
	$i     = 0;
	$depth = 0;
	$plain = '';
	$urls  = array();

	while ( $i < $len ) {
		$c = $css[ $i ];

		if ( '/' === $c && $i + 1 < $len && '*' === $css[ $i + 1 ] ) {
			$end = strpos( $css, '*/', $i + 2 );
			if ( false === $end ) {
				$errors[] = __( 'A comment is not closed.', 'csd-site-styles' );
				return false;
			}
			// A comment is a separator, but not white space: a hex escape cannot absorb it and glue two names together.
			$plain .= "\x01";
			$i      = $end + 2;
			continue;
		}

		if ( '"' === $c || "'" === $c ) {
			$read = csd_site_styles_css_read_string( $css, $i, $errors );
			if ( false === $read ) {
				return false;
			}
			$plain .= '""';
			$i      = $read[1];
			continue;
		}

		if ( '\\' === $c ) {
			$plain .= substr( $css, $i, 2 );
			$i     += 2;
			continue;
		}

		if ( ( 'u' === $c || 'U' === $c ) && 'url(' === csd_site_styles_ascii_lower( substr( $css, $i, 4 ) )
			&& ( 0 === $i || 0 === preg_match( '/[A-Za-z0-9_\-\x80-\xff]/', $css[ $i - 1 ] ) ) ) {
			$j = $i + 4;
			while ( $j < $len && in_array( $css[ $j ], array( ' ', "\t", "\n" ), true ) ) {
				++$j;
			}
			if ( $j < $len && ( '"' === $css[ $j ] || "'" === $css[ $j ] ) ) {
				$read = csd_site_styles_css_read_string( $css, $j, $errors );
				if ( false === $read ) {
					return false;
				}
				$arg = csd_site_styles_css_unescape( $read[0] );
				if ( null === $arg ) {
					$errors[] = __( 'The CSS could not be checked.', 'csd-site-styles' );
					return false;
				}
				$j = $read[1];
				while ( $j < $len && in_array( $css[ $j ], array( ' ', "\t", "\n" ), true ) ) {
					++$j;
				}
				if ( $j >= $len || ')' !== $css[ $j ] ) {
					$errors[] = __( 'A url() is written in a way the plugin cannot read.', 'csd-site-styles' );
					return false;
				}
			} else {
				$raw = '';
				while ( true ) {
					if ( $j >= $len ) {
						$errors[] = __( 'A url() is not closed.', 'csd-site-styles' );
						return false;
					}
					$d = $css[ $j ];
					if ( ')' === $d ) {
						break;
					}
					if ( '\\' === $d ) {
						$raw .= substr( $css, $j, 2 );
						$j   += 2;
						continue;
					}
					if ( in_array( $d, array( ' ', "\t", "\n" ), true ) ) {
						while ( $j < $len && in_array( $css[ $j ], array( ' ', "\t", "\n" ), true ) ) {
							++$j;
						}
						if ( $j >= $len || ')' !== $css[ $j ] ) {
							$errors[] = __( 'A url() is written in a way the plugin cannot read.', 'csd-site-styles' );
							return false;
						}
						break;
					}
					if ( '"' === $d || "'" === $d || '(' === $d || ord( $d ) < 32 || 127 === ord( $d ) ) {
						$errors[] = __( 'A url() is written in a way the plugin cannot read. Put the address in quotes.', 'csd-site-styles' );
						return false;
					}
					$raw .= $d;
					++$j;
				}
				$arg = csd_site_styles_css_unescape( $raw );
				if ( null === $arg ) {
					$errors[] = __( 'The CSS could not be checked.', 'csd-site-styles' );
					return false;
				}
			}
			$urls[] = $arg;
			$plain .= 'url()';
			$i      = $j + 1;
			continue;
		}

		if ( '{' === $c ) {
			++$depth;
		} elseif ( '}' === $c ) {
			--$depth;
			if ( $depth < 0 ) {
				$errors[] = __( 'There is a closing brace without an opening brace.', 'csd-site-styles' );
				return false;
			}
		}

		$plain .= $c;
		++$i;
	}

	if ( 0 !== $depth ) {
		$errors[] = __( 'An opening brace is not closed.', 'csd-site-styles' );
		return false;
	}

	return array(
		'plain' => $plain,
		'urls'  => $urls,
	);
}

/**
 * Check a piece of CSS. Nothing is repaired: the text that passes is the text that was given, with a leading byte order mark
 * dropped and every line ending made a line feed (the way a browser reads it).
 *
 * @param mixed $css The CSS.
 * @return array array( 'ok' => bool, 'css' => string, 'errors' => string[] ).
 */
function csd_site_styles_validate_css( $css ) {
	$errors = array();
	$fail   = function ( $errors ) {
		return array(
			'ok'     => false,
			'css'    => '',
			'errors' => $errors,
		);
	};

	if ( ! is_string( $css ) ) {
		return $fail( array( __( 'No CSS was given.', 'csd-site-styles' ) ) );
	}

	if ( 0 === strncmp( $css, "\xEF\xBB\xBF", 3 ) ) {
		$css = substr( $css, 3 );
	}

	if ( strlen( $css ) > CSD_SITE_STYLES_CSS_MAX ) {
		return $fail(
			array(
				sprintf(
					/* translators: %s: the size limit in kilobytes */
					__( 'The CSS is larger than %s KB.', 'csd-site-styles' ),
					number_format_i18n( CSD_SITE_STYLES_CSS_MAX / 1024 )
				),
			)
		);
	}

	if ( '' === trim( $css ) ) {
		return $fail( array( __( 'The CSS is empty.', 'csd-site-styles' ) ) );
	}

	if ( false !== strpos( $css, "\0" ) ) {
		$errors[] = __( 'The CSS contains a null character.', 'csd-site-styles' );
	}
	if ( 1 !== preg_match( '//u', $css ) ) {
		$errors[] = __( 'The CSS is not valid UTF-8 text.', 'csd-site-styles' );
	}
	if ( false !== strpos( $css, '<' ) ) {
		$errors[] = __( 'The CSS contains a less-than sign. Markup and the end of a style element are not accepted.', 'csd-site-styles' );
	}
	if ( $errors ) {
		return $fail( $errors );
	}

	$css = preg_replace( '/\r\n?|\f/', "\n", $css );
	if ( null === $css ) {
		return $fail( array( __( 'The CSS could not be checked.', 'csd-site-styles' ) ) );
	}

	$scan = csd_site_styles_css_scan( $css, $errors );
	if ( false === $scan ) {
		return $fail( $errors );
	}

	$plain = csd_site_styles_css_unescape( $scan['plain'] );
	if ( null === $plain ) {
		return $fail( array( __( 'The CSS could not be checked.', 'csd-site-styles' ) ) );
	}
	$plain = csd_site_styles_ascii_lower( $plain );
	// A comment inside a name, such as expression/**/(, reads as the marker byte. For the name checks below the marker counts as white space.
	$spaced = str_replace( "\x01", ' ', $plain );

	if ( false !== strpos( $plain, '@import' ) ) {
		$errors[] = __( '@import is not accepted. Put the rules in this file, or load fonts from the theme.', 'csd-site-styles' );
	}

	$patterns = array(
		'/expression\s*\(/'                           => __( 'expression() is not accepted.', 'csd-site-styles' ),
		'/(?:javascript|vbscript)\s*:/'               => __( 'Script addresses are not accepted.', 'csd-site-styles' ),
		'/(?<![a-z0-9.#-])behavior\s*:|-moz-binding/' => __( 'behavior and -moz-binding are not accepted.', 'csd-site-styles' ),
		'/progid\s*:/'                                => __( 'progid filters are not accepted.', 'csd-site-styles' ),
		'/(?:image-set|image|src|element|cross-fade|paint)\s*\(/' => __( 'image-set(), image(), src(), element(), cross-fade() and paint() are not accepted.', 'csd-site-styles' ),
	);
	foreach ( $patterns as $pattern => $message ) {
		$hit = preg_match( $pattern, $spaced );
		if ( false === $hit ) {
			$errors[] = __( 'The CSS could not be checked.', 'csd-site-styles' );
		} elseif ( 1 === $hit ) {
			$errors[] = $message;
		}
	}

	// Every url( in the text must be one the scanner read as a url() token; a url( that only appears once escapes are decoded is refused.
	// The count takes every occurrence, whatever comes before it, so an escape that runs into a real url( cannot hide another one.
	if ( substr_count( $plain, 'url(' ) !== count( $scan['urls'] ) ) {
		$errors[] = __( 'A url() is written in a way the plugin cannot read.', 'csd-site-styles' );
	}

	foreach ( $scan['urls'] as $arg ) {
		$arg  = trim( $arg );
		$head = csd_site_styles_ascii_lower( preg_replace( '/[\x00-\x20]+/', '', substr( $arg, 0, 80 ) ) );
		$data = 1 === preg_match( '#^data:image/(?:svg\+xml|png|jpeg|jpg|gif|webp|avif)[;,]#', $head );
		$frag = 1 === preg_match( '/^#[a-z0-9_-]+$/i', $arg );
		if ( ! $data && ! $frag ) {
			$errors[] = __( 'Only url() addresses that start with data:image/, or that point to a fragment such as #name, are accepted.', 'csd-site-styles' );
			break;
		}
	}

	$errors = array_values( array_unique( $errors ) );

	return array(
		'ok'     => empty( $errors ),
		'css'    => empty( $errors ) ? $css : '',
		'errors' => $errors,
	);
}
