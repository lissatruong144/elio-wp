<?php
/**
 * ELIO International Preschool theme.
 *
 * @package elio
 */

defined( 'ABSPATH' ) || exit;

require __DIR__ . '/inc/i18n.php';
require __DIR__ . '/inc/markup.php';
require __DIR__ . '/inc/content.php';
require __DIR__ . '/inc/sections.php';
require __DIR__ . '/inc/settings.php';
require __DIR__ . '/inc/blocks.php';
require __DIR__ . '/inc/tour.php';
require __DIR__ . '/inc/setup.php';

add_action(
	'after_setup_theme',
	function () {
		// Spacing comes from the Tailwind classes on each block, not from WordPress' layout rules.
		add_theme_support( 'disable-layout-styles' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'editor-styles' );
		add_editor_style( [ 'assets/css/elio.css', 'assets/css/editor.css' ] );
		remove_theme_support( 'core-block-patterns' );
		register_nav_menus(
			[
				'primary' => 'Menu chính (header)',
				'footer'  => 'Liên kết nhanh (footer)',
			]
		);
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		$v = fn( $f ) => (string) filemtime( get_theme_file_path( $f ) );
		wp_enqueue_style( 'elio', get_theme_file_uri( 'assets/css/elio.css' ), [], $v( 'assets/css/elio.css' ) );
		wp_enqueue_script( 'elio', get_theme_file_uri( 'assets/js/elio.js' ), [], $v( 'assets/js/elio.js' ), [ 'strategy' => 'defer', 'in_footer' => true ] );
		wp_localize_script(
			'elio',
			'ELIO',
			[
				'tourUrl' => esc_url_raw( rest_url( 'elio/v1/tour' ) ),
				'lang'    => elio_lang(),
			]
		);
	}
);

// Block patterns: the full home page and each section, in both languages.
add_action(
	'init',
	function () {
		// Only the editor needs patterns; skip building them on ordinary page views.
		$uri = $_SERVER['REQUEST_URI'] ?? ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! is_admin() && false === strpos( $uri, '/wp-json/' ) && false === strpos( $uri, 'rest_route=' ) ) {
			return;
		}
		register_block_pattern_category( 'elio', [ 'label' => 'ELIO – Trang chủ' ] );
		register_block_pattern_category( 'elio-en', [ 'label' => 'ELIO – Home (English)' ] );
		$sections = [
			'hero'      => [ 'Hero + dải thông tin', 'Hero + quick facts' ],
			'why'       => [ 'Why ELIO (5 trụ cột)', 'Why ELIO (5 pillars)' ],
			'day'       => [ 'Một ngày tại ELIO', 'A day at ELIO' ],
			'nutrition' => [ 'Dinh dưỡng', 'Nutrition' ],
			'approach'  => [ 'Phương pháp giáo dục', 'Educational approach' ],
			'grow'      => [ 'Con sẽ phát triển', 'What your child will grow' ],
			'campus'    => [ 'Không gian trường', 'Campus' ],
			'educators' => [ 'Đội ngũ giáo viên', 'Educators' ],
			'community' => [ 'Giá trị · Phụ huynh · Học phí', 'Values · Parents · Tuition' ],
			'news'      => [ 'Tin tức mới', 'Latest news' ],
			'tour'      => [ 'Đặt lịch tham quan', 'Book a tour' ],
		];
		foreach ( [ 'vi', 'en' ] as $lang ) {
			$c   = elio_content( $lang );
			$cat = 'en' === $lang ? 'elio-en' : 'elio';
			register_block_pattern(
				'elio/home-' . $lang,
				[
					'title'      => 'en' === $lang ? 'Home page – full (English)' : 'Trang chủ – đầy đủ (Tiếng Việt)',
					'categories' => [ $cat ],
					'content'    => elio_home_markup( $lang ),
				]
			);
			foreach ( $sections as $key => $titles ) {
				$fn = 'elio_section_' . $key;
				register_block_pattern(
					'elio/' . $key . '-' . $lang,
					[
						'title'      => 'en' === $lang ? $titles[1] : $titles[0],
						'categories' => [ $cat ],
						'content'    => 'news' === $key ? $fn( $c, $lang ) : $fn( $c ),
					]
				);
			}
		}
	}
);

// Lets the CSS hide the editable lists of interactive sections before the script rebuilds them.
add_action(
	'wp_head',
	function () {
		wp_print_inline_script_tag( "document.documentElement.classList.add('js')" );
	},
	0
);

// Favicon (unless one is set in Settings → General → Site Icon) and link-preview image.
add_action(
	'wp_head',
	function () {
		if ( ! has_site_icon() ) {
			echo '<link rel="icon" href="' . esc_url( elio_img( 'favicon.ico' ) ) . '" sizes="any">' . "\n";
			echo '<link rel="icon" type="image/png" href="' . esc_url( elio_img( 'site-icon.png' ) ) . '">' . "\n";
			echo '<link rel="apple-touch-icon" href="' . esc_url( elio_img( 'apple-icon.png' ) ) . '">' . "\n";
		}
		// Leave Open Graph to an SEO plugin if one is active.
		if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || class_exists( 'All_in_One_SEO_Pack' ) ) {
			return;
		}
		$title = is_front_page() ? get_bloginfo( 'name' ) : wp_get_document_title();
		$desc  = is_singular() && has_excerpt() ? get_the_excerpt() : get_bloginfo( 'description' );
		$image = is_singular() && has_post_thumbnail() ? get_the_post_thumbnail_url( null, 'large' ) : elio_img( 'og.png' );
		printf( '<meta property="og:type" content="%s">' . "\n", is_singular( 'post' ) ? 'article' : 'website' );
		printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
		if ( $desc ) {
			printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( wp_strip_all_tags( $desc ) ) );
			printf( '<meta name="description" content="%s">' . "\n", esc_attr( wp_strip_all_tags( $desc ) ) );
		}
		printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
		printf( '<meta property="og:url" content="%s">' . "\n", esc_url( is_singular() ? get_permalink() : home_url( add_query_arg( [] ) ) ) );
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	},
	5
);
