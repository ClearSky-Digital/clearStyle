<?php
/**
 * Runs the plugin's CSS checks against fixed inputs and every stylesheet in examples/.
 * Needs PHP 7.4 or later. WordPress is not needed: the few functions the checks call are stubbed here.
 * Usage: php tests/sanitizer-test.php
 *
 * This file is part of CSD Site Styles.
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
 */

define( 'ABSPATH', '/' );
function __( $s ) { return $s; }
function number_format_i18n( $n ) { return (string) $n; }
require dirname( __DIR__ ) . '/csd-site-styles/includes/css-sanitizer.php';

$cases = array(
	// name => array( css, expected ok )
	'plain rule'                       => array( '.a{color:red}', true ),
	'comment with less-than'           => array( '/* <x> */.a{b:c}', false ),
	'closing style tag'                => array( '.a{b:c}</style><script>', false ),
	'closing style tag escaped'        => array( '.a{content:"\3c /style>"}', true ), // escape inside a string is data, not markup
	'import'                           => array( '@import url(#x);.a{b:c}', false ),
	'import upper case'                => array( '@IMPORT "x.css";', false ),
	'import escaped'                   => array( '@\69mport "x.css";', false ),
	'import in comment'                => array( '/* @import */.a{b:c}', true ),
	'url http'                         => array( '.a{background:url(http://e.test/x.png)}', false ),
	'url https quoted'                 => array( '.a{background:url("https://e.test/x.png")}', false ),
	'url relative'                     => array( '.a{background:url(x.png)}', false ),
	'url data image'                   => array( '.a{background:url("data:image/svg+xml,%3Csvg%3E%3C/svg%3E")}', true ),
	'url data font is refused'         => array( '@font-face{font-family:x;src:url(data:font/woff2;base64,AAAA)}', false ),
	'src property is not src()'        => array( '@font-face{font-family:x;src:local(x)}', true ),
	'url data text'                    => array( '.a{background:url(data:text/html,hi)}', false ),
	'url fragment'                     => array( '.a{fill:url(#grad)}', true ),
	'url escaped scheme'               => array( '.a{background:url(\68ttp://e.test/x)}', false ),
	'url spaced data'                  => array( '.a{background:url( "data:image/png;base64,AAAA" )}', true ),
	'url unquoted with quote inside'   => array( '.a{background:url(x"y)}', false ),
	'expression'                       => array( '.a{width:expression(alert(1))}', false ),
	'javascript'                       => array( '.a{background:url(javascript:alert(1))}', false ),
	'vbscript'                         => array( '.a{x:vbscript:y}', false ),
	'behavior'                         => array( '.a{behavior:url(x.htc)}', false ),
	'moz binding'                      => array( '.a{-moz-binding:url(x)}', false ),
	'image-set'                        => array( '.a{background:image-set("x.png" 1x)}', false ),
	'webkit image-set'                 => array( '.a{background:-webkit-image-set(url(#a) 1x)}', false ),
	'image function'                   => array( '.a{background:image("x.png")}', false ),
	'element function'                 => array( '.a{background:element(#x)}', false ),
	'paint function'                   => array( '.a{background:paint(x)}', false ),
	'null byte'                        => array( ".a{b:c}\0", false ),
	'invalid utf8'                     => array( ".a{b:\xFF}", false ),
	'unbalanced open'                  => array( '.a{b:c', false ),
	'unbalanced close'                 => array( '.a{b:c}}', false ),
	'unclosed comment'                 => array( '.a{b:c}/* x', false ),
	'unclosed string'                  => array( ".a{content:\"x}", false ),
	'string with brace'                => array( '.a:before{content:"}"}', true ),
	'too big'                          => array( str_repeat( '.a{b:c}', 40000 ), false ),
	'bom allowed'                      => array( "\xEF\xBB\xBF.a{b:c}", true ),
	'not a string'                     => array( array( 'x' ), false ),
	'media query'                      => array( '@media (max-width:600px){.a{b:c}}', true ),
	'keyframes'                        => array( '@keyframes k{from{a:b}to{a:c}}', true ),
	// cases added after the first review round
	'hex escape then CRLF hides url('  => array( '.a{background:\75' . "\r\n" . 'rl(http://evil.test/x)}', false ),
	'hex escape then CRLF hides import' => array( '@\69' . "\r\n" . "mport 'x';.a{b:c}", false ),
	'lone CR ends a string'            => array( ".a{content:\"x\r}body{background:url(http://evil.test/p.png)}c{content:\"y}", false ),
	'form feed ends a string'          => array( ".a{content:\"x\f}body{background:url(http://evil.test/p.png)}c{content:\"y}", false ),
	'comment mark inside raw url'      => array( '.a{background:url(data:image/png;a/*), url(//evil.example/*/)}', false ),
	'comment mark inside data url'     => array( '.a{background:url(data:image/png;base64,AA/*AA)}', true ),
	'escaped function name url'        => array( '.a{background:u\72l(http://e.test/x)}', false ),
	'upper case URL'                   => array( '.a{background:URL(HTTP://e.test/x)}', false ),
	'url with space inside'            => array( '.a{background:url(a b)}', false ),
	'url not closed'                   => array( '.a{background:url(data:image/png;base64,AAAA}', false ),
	'unquoted data url'                => array( '.a{background:url(data:image/png;base64,AAAA)}', true ),
	'unquoted svg data with apostrophe' => array( ".a{background:url(data:image/svg+xml,%3Csvg a='1'%3E)}", false ),
	'escape then url( offsets the count' => array( '.a{x:\41 url(data:image/png;a)} .b{background:\75 rl(//evil.example/p.png)}', false ),
	'hidden token plus decoy' => array( '.a{background:\75rl(http://evil.test/p.png)}.b{x:\75 url(data:image/png,a)}', false ),
	'comment glues hex escape to image-set' => array( '.a{--x:\6c/**/image-set("https://evil.test/x.png" 1x)}', false ),
	'comment glues hex escape to -webkit-cross-fade'      => array( '.a{--x:\6c/**/-webkit-cross-fade(url(#a), url(#b), 50%)}', false ),
	'escaped url( alone'               => array( '.b{background:\75 rl(data:image/png;a)}', false ),
	'moz image-set'                    => array( '.a{background:-moz-image-set(url(#a) 1x)}', false ),
	'o image-set'                      => array( '.a{background:-o-image-set(url(#a) 1x)}', false ),
	'moz element'                      => array( '.a{background:-moz-element(#x)}', false ),
	'entity text in a comment'         => array( '/* &lt;/style&gt;&lt;img src=//evil.test/b.gif&gt; */.a{b:c}', true ),
	'empty'                            => array( '', false ),
	'blank'                            => array( " \n\t ", false ),
	'form feed between rules'          => array( ".a{b:c}\f.d{e:f}", true ),
	'CRLF between rules'               => array( ".a{b:c}\r\n.d{e:f}", true ),
	'image-set word in selector name'  => array( '.my-image-set{b:c}', true ),
	'src word in class name'           => array( '.src{b:c}', true ),
);

$cases = array_merge( $cases, require __DIR__ . '/sanitizer-adversarial.php' );

$fail = 0;
$t0 = microtime( true );
$r  = csd_site_styles_validate_css( str_repeat( 'url(', 60000 ) );
$dt = microtime( true ) - $t0;
echo ( ! $r['ok'] && $dt < 2 ? 'PASS ' : 'FAIL ' ) . 'worst case input of repeated url( is refused in under 2 seconds (' . round( $dt, 3 ) . " s)
";
if ( $r['ok'] || $dt >= 2 ) {
	++$fail;
}
$t0 = microtime( true );
$r  = csd_site_styles_validate_css( file_get_contents( dirname( __DIR__ ) . '/examples/demo-site.css' ) . str_repeat( '.a{b:c}', 20000 ) );
$dt = microtime( true ) - $t0;
echo ( $dt < 2 ? 'PASS ' : 'FAIL ' ) . 'a near-limit file is checked in under 2 seconds (' . round( $dt, 3 ) . ' s, ' . ( $r['ok'] ? 'accepted' : 'refused: ' . implode( ' | ', $r['errors'] ) ) . ")
";
if ( $dt >= 2 ) {
	++$fail;
}

foreach ( $cases as $name => $case ) {
	$r  = csd_site_styles_validate_css( $case[0] );
	$ok = ( $r['ok'] === $case[1] );
	if ( ! $ok ) {
		++$fail;
	}
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . $name . ' (expected ' . ( $case[1] ? 'accept' : 'reject' ) . ', got ' . ( $r['ok'] ? 'accept' : 'reject' ) . ')' . ( $r['ok'] ? '' : ' [' . implode( ' | ', $r['errors'] ) . ']' ) . "\n";
}

if ( ! is_readable( dirname( __DIR__ ) . '/examples/demo-site.css' ) ) {
	echo "FAIL examples/demo-site.css is missing
";
	++$fail;
}

foreach ( glob( dirname( __DIR__ ) . '/examples/*.css' ) as $file ) {
	$text = file_get_contents( $file );
	$r    = csd_site_styles_validate_css( $text );
	$ok   = $r['ok'] && $r['css'] === $text;
	if ( ! $ok ) {
		++$fail;
	}
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . 'example file ' . basename( $file ) . ' is accepted unchanged (' . strlen( $text ) . ' bytes)' . ( $r['ok'] ? '' : ' [' . implode( ' | ', $r['errors'] ) . ']' ) . "\n";
}

echo $fail ? "SUMMARY: $fail FAILURES\n" : "SUMMARY: ALL PASS\n";
exit( $fail ? 1 : 0 );
