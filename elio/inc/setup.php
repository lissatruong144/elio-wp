<?php
/**
 * Creates the starter content once (Settings → ELIO → "Tạo nội dung mẫu"). Safe to run
 * again: anything it created before is found by ID and left alone.
 *
 * @package elio
 */

defined( 'ABSPATH' ) || exit;

function elio_setup_demo() {
	$log = [];
	$ids = (array) get_option( 'elio_demo_ids', [] );
	list( $pll, $has_vi, $has_en ) = elio_pll_state();
	$bilingual = $pll && $has_vi && $has_en;
	$langs     = $bilingual ? [ 'vi', 'en' ] : [ 'vi' ];

	if ( ! get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
		$log[] = 'Bật đường dẫn đẹp (/%postname%/).';
	}

	// Content created before Polylang had its languages (including WordPress' own sample post)
	// gets the default language, as Polylang's "set language in mass" notice would do.
	if ( $bilingual && function_exists( 'PLL' ) && method_exists( PLL()->model, 'set_language_in_mass' ) ) {
		PLL()->model->set_language_in_mass();
	}

	$make_page = function ( $key, $lang, $title, $slug, $content = '' ) use ( &$ids, &$log, $bilingual ) {
		if ( ! empty( $ids[ $key ] ) && get_post( $ids[ $key ] ) ) {
			if ( $bilingual && pll_get_post_language( $ids[ $key ] ) !== $lang ) {
				pll_set_post_language( $ids[ $key ], $lang );
			}
			return (int) $ids[ $key ];
		}
		$id = wp_insert_post(
			[
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $content,
			]
		);
		if ( function_exists( 'pll_set_post_language' ) && pll_is_translated_post_type( 'page' ) ) {
			pll_set_post_language( $id, $lang );
		}
		$ids[ $key ] = $id;
		$log[]       = 'Tạo trang “' . $title . '”.';
		return $id;
	};

	// News pages first: the home page links to them.
	$news = [];
	foreach ( $langs as $l ) {
		$news[ $l ] = $make_page( 'news_' . $l, $l, 'en' === $l ? 'News' : 'Tin tức', 'en' === $l ? 'news' : 'tin-tuc' );
	}
	$home = [];
	foreach ( $langs as $l ) {
		$home[ $l ] = $make_page( 'home_' . $l, $l, 'en' === $l ? 'Home' : 'Trang chủ', 'en' === $l ? 'home' : 'trang-chu', elio_home_markup( $l ) );
	}
	if ( $bilingual ) {
		pll_save_post_translations( $news );
		pll_save_post_translations( $home );
	}
	// WordPress' own sample post and page, if still untouched.
	foreach ( [ [ 'hello-world', 'post' ], [ 'sample-page', 'page' ] ] as $sample ) {
		$p = get_page_by_path( $sample[0], OBJECT, $sample[1] );
		if ( $p && 'publish' === $p->post_status && $p->post_modified === $p->post_date ) {
			wp_trash_post( $p->ID );
			$log[] = 'Chuyển “' . $p->post_title . '” (nội dung mặc định của WordPress) vào thùng rác.';
		}
	}
	update_option( 'date_format', 'd/m/Y' );
	// The news grid has 3 columns: 9 posts per page fills it evenly (WordPress' default is 10).
	update_option( 'posts_per_page', 9 );
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home['vi'] );
	update_option( 'page_for_posts', $news['vi'] );

	// Menus.
	$menu_ids = [];
	foreach ( $langs as $l ) {
		$key = 'menu_' . $l;
		if ( empty( $ids[ $key ] ) || ! wp_get_nav_menu_object( $ids[ $key ] ) ) {
			$name    = 'en' === $l ? 'Main menu (EN)' : 'Menu chính (VI)';
			$existing = wp_get_nav_menu_object( $name );
			$menu_id = $existing ? $existing->term_id : wp_create_nav_menu( $name );
			if ( ! $existing ) {
				// Anchor links point at that language's home page (Polylang: / and /en/home/).
				$base  = 'vi' === $l ? home_url( '/' ) : get_permalink( $home[ $l ] );
				$items = 'en' === $l
					? [ [ 'About ELIO', 'why' ], [ 'Programs', 'approach' ], [ 'Campus', 'campus' ], [ 'Admissions', 'tuition' ], [ 'Parent life', 'parents' ] ]
					: [ [ 'Về ELIO', 'why' ], [ 'Chương trình', 'approach' ], [ 'Không gian', 'campus' ], [ 'Tuyển sinh', 'tuition' ], [ 'Đời sống phụ huynh', 'parents' ] ];
				foreach ( $items as $i => $it ) {
					wp_update_nav_menu_item(
						$menu_id,
						0,
						[
							'menu-item-title'    => $it[0],
							'menu-item-url'      => $base . '#' . $it[1],
							'menu-item-type'     => 'custom',
							'menu-item-status'   => 'publish',
							'menu-item-position' => $i + 1,
						]
					);
				}
				wp_update_nav_menu_item(
					$menu_id,
					0,
					[
						'menu-item-title'     => 'en' === $l ? 'News' : 'Tin tức',
						'menu-item-object'    => 'page',
						'menu-item-object-id' => $news[ $l ],
						'menu-item-type'      => 'post_type',
						'menu-item-status'    => 'publish',
						'menu-item-position'  => count( $items ) + 1,
					]
				);
				$log[] = 'Tạo menu “' . $name . '”.';
			}
			$ids[ $key ] = $menu_id;
		}
		$menu_ids[ $l ] = (int) $ids[ $key ];
	}
	$locations            = (array) get_theme_mod( 'nav_menu_locations', [] );
	$locations['primary'] = $menu_ids['vi'];
	set_theme_mod( 'nav_menu_locations', $locations );
	if ( $bilingual ) {
		// Polylang keeps one menu per language for each theme location.
		$opt = (array) get_option( 'polylang', [] );
		$opt['nav_menus'][ get_stylesheet() ]['primary'] = $menu_ids;
		update_option( 'polylang', $opt );
	}

	// Categories.
	$cats = [
		[ 'Sự kiện', 'su-kien', 'Events', 'events' ],
		[ 'Hoạt động', 'hoat-dong', 'Activities', 'activities' ],
		[ 'Thông báo', 'thong-bao', 'Announcements', 'announcements' ],
	];
	$cat_ids = [];
	foreach ( $cats as $i => $cat ) {
		$pair = [];
		foreach ( $langs as $l ) {
			$name = 'en' === $l ? $cat[2] : $cat[0];
			$slug = 'en' === $l ? $cat[3] : $cat[1];
			$term = get_term_by( 'slug', $slug, 'category' );
			$tid  = $term ? $term->term_id : wp_insert_term( $name, 'category', [ 'slug' => $slug ] )['term_id'];
			if ( $bilingual ) {
				pll_set_term_language( $tid, $l );
			}
			$pair[ $l ]          = $tid;
			$cat_ids[ $l ][ $i ] = $tid;
		}
		if ( $bilingual ) {
			pll_save_term_translations( $pair );
		}
	}

	// Sample posts with featured images.
	$posts = elio_demo_posts();
	foreach ( $posts as $i => $p ) {
		$pair = [];
		foreach ( $langs as $l ) {
			$key = 'post_' . $i . '_' . $l;
			if ( ! empty( $ids[ $key ] ) && get_post( $ids[ $key ] ) ) {
				$pair[ $l ] = (int) $ids[ $key ];
				if ( $bilingual && pll_get_post_language( $pair[ $l ] ) !== $l ) {
					pll_set_post_language( $pair[ $l ], $l );
				}
				continue;
			}
			$id = wp_insert_post(
				[
					'post_type'     => 'post',
					'post_status'   => 'publish',
					'post_title'    => $p[ $l ][0],
					'post_excerpt'  => $p[ $l ][1],
					'post_content'  => $p[ $l ][2],
					'post_date'     => gmdate( 'Y-m-d H:i:s', time() - ( $i * 9 + 2 ) * DAY_IN_SECONDS ),
					'post_category' => [ $cat_ids[ $l ][ $p['cat'] ] ],
				]
			);
			if ( $bilingual ) {
				pll_set_post_language( $id, $l );
			}
			$img = elio_demo_attachment( $p['img'], $ids );
			if ( $img ) {
				set_post_thumbnail( $id, $img );
			}
			$ids[ $key ] = $id;
			$pair[ $l ]  = $id;
			$log[]       = 'Tạo bài viết mẫu “' . $p[ $l ][0] . '”.';
		}
		if ( $bilingual ) {
			pll_save_post_translations( $pair );
		}
	}

	update_option( 'elio_demo_ids', $ids, false );
	flush_rewrite_rules();
	if ( ! $bilingual ) {
		$log[] = 'Chỉ tạo bản tiếng Việt vì Polylang chưa có đủ Tiếng Việt và English.';
	}
	if ( count( $log ) === 0 ) {
		$log[] = 'Mọi thứ đã có sẵn, không tạo thêm gì.';
	}
	return $log;
}

/** Copies a theme image into the media library once and returns its attachment ID. */
function elio_demo_attachment( $rel, array &$ids ) {
	$key = 'img_' . md5( $rel );
	if ( ! empty( $ids[ $key ] ) && get_post( $ids[ $key ] ) ) {
		return (int) $ids[ $key ];
	}
	$file = get_theme_file_path( 'assets/img/' . $rel );
	if ( ! is_readable( $file ) ) {
		return 0;
	}
	$up = wp_upload_bits( 'elio-' . basename( $rel ), null, file_get_contents( $file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! empty( $up['error'] ) ) {
		return 0;
	}
	$type = wp_check_filetype( $up['file'] );
	$id   = wp_insert_attachment(
		[
			'post_mime_type' => $type['type'],
			'post_title'     => sanitize_file_name( basename( $rel ) ),
			'post_status'    => 'inherit',
		],
		$up['file']
	);
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $up['file'] ) );
	$ids[ $key ] = $id;
	return $id;
}

/** [image, category index, vi => [title, excerpt, content], en => [...]] */
function elio_demo_posts() {
	$para = fn( $t ) => "<!-- wp:paragraph -->\n<p>" . esc_html( $t ) . "</p>\n<!-- /wp:paragraph -->\n\n";
	$h2   = fn( $t ) => "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . esc_html( $t ) . "</h2>\n<!-- /wp:heading -->\n\n";
	$note = [ 'vi' => '(Bài viết mẫu – hãy sửa hoặc xoá và thay bằng nội dung thật của trường.)', 'en' => '(Sample post – edit or delete it and replace it with the school’s real news.)' ];

	return [
		[
			'img' => 'campus/04-outdoor.jpg',
			'cat' => 0,
			'vi'  => [
				'Ngày hội khám phá thiên nhiên tại ELIO',
				'Các bé cùng cô giáo trồng cây, quan sát côn trùng và làm “nhà khoa học nhí” ngay trong sân trường.',
				$para( 'Sáng thứ Bảy vừa qua, sân chơi ngoài trời của ELIO trở thành một phòng thí nghiệm xanh. Các bé được tự tay gieo hạt, quan sát lá cây qua kính lúp và kể lại những điều mình phát hiện.' )
				. $h2( 'Học qua trải nghiệm' )
				. $para( 'Mỗi hoạt động đều bắt đầu từ một câu hỏi của trẻ: “Vì sao lá có màu xanh?”, “Con kiến đi đâu?”. Giáo viên không trả lời ngay mà cùng con tìm hiểu.' )
				. $para( $note['vi'] ),
			],
			'en'  => [
				'Nature discovery day at ELIO',
				'Children planted seeds, watched insects and became “little scientists” in our own playground.',
				$para( 'Last Saturday, the ELIO outdoor playground turned into a green laboratory. Children sowed seeds, looked at leaves through magnifying glasses and shared what they had discovered.' )
				. $h2( 'Learning by doing' )
				. $para( 'Every activity started with a child’s question: “Why are leaves green?”, “Where is the ant going?”. Teachers didn’t answer straight away — they found out together.' )
				. $para( $note['en'] ),
			],
		],
		[
			'img' => 'day/06-art.jpg',
			'cat' => 1,
			'vi'  => [
				'Góc nghệ thuật: khi con tự tay tạo nên tác phẩm',
				'Màu nước, đất nặn và giấy tái chế – cách ELIO nuôi dưỡng sự sáng tạo mỗi ngày.',
				$para( 'Ở góc nghệ thuật, không có bài mẫu nào để chép lại. Mỗi bé chọn chất liệu, tự quyết định mình sẽ làm gì và kể câu chuyện phía sau tác phẩm của mình.' )
				. $para( $note['vi'] ),
			],
			'en'  => [
				'The art corner: when children make their own work',
				'Watercolours, clay and recycled paper — how ELIO nurtures creativity every day.',
				$para( 'In the art corner there is no model to copy. Each child chooses the materials, decides what to make and tells the story behind their piece.' )
				. $para( $note['en'] ),
			],
		],
		[
			'img' => 'campus/01-exterior.jpg',
			'cat' => 2,
			'vi'  => [
				'Thông báo tuyển sinh năm học 2027 – 2028',
				'ELIO bắt đầu nhận hồ sơ cho các lớp Nhà trẻ, Mẫu giáo nhỏ và Mẫu giáo lớn.',
				$para( 'Ba mẹ có thể đặt lịch tham quan trường ngay trên website. Đội ngũ Tuyển sinh sẽ liên hệ trong vòng 24 giờ để sắp xếp thời gian phù hợp.' )
				. $para( $note['vi'] ),
			],
			'en'  => [
				'Admissions open for the 2027 – 2028 school year',
				'ELIO is now accepting applications for Nursery, Pre-K and Kindergarten.',
				$para( 'Parents can book a school tour right on our website. Our admissions team will be in touch within 24 hours to find a time that suits you.' )
				. $para( $note['en'] ),
			],
		],
	];
}
