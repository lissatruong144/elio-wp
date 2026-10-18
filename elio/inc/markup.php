<?php
/**
 * Helpers that print serialized core-block markup (the same HTML the block editor saves),
 * so the generated home page opens in the editor without "unexpected content" warnings.
 * Styling is done with Tailwind classes in each block's "Additional CSS class(es)".
 *
 * @package elio
 */

defined( 'ABSPATH' ) || exit;

/** Shared horizontal gutter: 20px phone → 40px tablet → 56px desktop. */
const ELIO_GUTTER = 'px-5 md:px-10 lg:px-14';

/** An image block that fills its (relative, overflow-hidden) parent. */
const ELIO_FILL = 'absolute inset-0 [&_img]:h-full [&_img]:w-full [&_img]:object-cover';

/** Block comment attributes, escaped the way the editor's serializer does. */
function elio_attrs( array $a ) {
	if ( ! $a ) {
		return '';
	}
	$json = wp_json_encode( $a, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP );
	return ' ' . str_replace( '--', '\\u002d\\u002d', $json );
}

/** core/group. $tag may be div, section, article, aside, header, footer, main. */
function elio_group( $cls, $inner, $tag = 'div', $id = '' ) {
	$a = [];
	if ( 'div' !== $tag ) {
		$a['tagName'] = $tag;
	}
	if ( $id ) {
		$a['anchor'] = $id;
	}
	if ( $cls ) {
		$a['className'] = $cls;
	}
	$id_attr = $id ? ' id="' . esc_attr( $id ) . '"' : '';
	return '<!-- wp:group' . elio_attrs( $a ) . " -->\n"
		. '<' . $tag . $id_attr . ' class="' . esc_attr( trim( 'wp-block-group ' . $cls ) ) . '">'
		. $inner
		. '</' . $tag . ">\n<!-- /wp:group -->\n\n";
}

/** core/paragraph. $html is trusted inline markup (strong, em, br, a, mark). */
function elio_p( $cls, $html ) {
	$a = $cls ? [ 'className' => $cls ] : [];
	$c = $cls ? ' class="' . esc_attr( $cls ) . '"' : '';
	return '<!-- wp:paragraph' . elio_attrs( $a ) . " -->\n<p" . $c . '>' . $html . "</p>\n<!-- /wp:paragraph -->\n\n";
}

/** core/heading. */
function elio_h( $cls, $html, $level = 2 ) {
	$a = [];
	if ( 2 !== $level ) {
		$a['level'] = $level;
	}
	if ( $cls ) {
		$a['className'] = $cls;
	}
	return '<!-- wp:heading' . elio_attrs( $a ) . " -->\n<h" . $level . ' class="' . esc_attr( trim( 'wp-block-heading ' . $cls ) ) . '">' . $html . '</h' . $level . ">\n<!-- /wp:heading -->\n\n";
}

/**
 * core/image. No sizeSlug: its "size-full" class would collide with Tailwind's size-full.
 */
function elio_image( $cls, $src, $alt = '' ) {
	$a = [ 'linkDestination' => 'none' ];
	if ( $cls ) {
		$a['className'] = $cls;
	}
	return '<!-- wp:image' . elio_attrs( $a ) . " -->\n"
		. '<figure class="' . esc_attr( trim( 'wp-block-image ' . $cls ) ) . '"><img src="' . esc_url( $src ) . '" alt="' . esc_attr( $alt ) . '"/></figure>'
		. "\n<!-- /wp:image -->\n\n";
}

/** core/html, for purely decorative bits (lines, dots, icons). */
function elio_html( $raw ) {
	return "<!-- wp:html -->\n" . $raw . "\n<!-- /wp:html -->\n\n";
}

/** A pill link (paragraph holding one link). $variant: dark | ivory | ghost-light. */
function elio_pill( $variant, $href, $label, $cls = '' ) {
	return elio_p( trim( 'elio-pill is-' . $variant . ' ' . $cls ), '<a href="' . esc_url( $href ) . '">' . esc_html( $label ) . '</a>' );
}

/** Inline gold (or other palette) colour, as the editor's "Highlight" tool saves it. */
function elio_color( $slug, $html ) {
	return '<mark style="background-color:rgba(0, 0, 0, 0)" class="has-inline-color has-' . esc_attr( $slug ) . '-color">' . $html . '</mark>';
}

/** Inline SVG icon from inc/icons.php. */
function elio_icon( $name, $cls = '' ) {
	static $icons = null;
	if ( null === $icons ) {
		$icons = require __DIR__ . '/icons.php';
	}
	$svg = $icons[ $name ] ?? '';
	return $cls ? str_replace( '<svg ', '<svg class="' . esc_attr( $cls ) . '" ', $svg ) : $svg;
}
