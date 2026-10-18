<?php
/**
 * Interface strings (header, footer, form, blog) in both languages. Page content itself is
 * edited in the block editor; these are the fixed labels around it.
 *
 * @package elio
 */

defined( 'ABSPATH' ) || exit;

/** Current front-end language: 'vi' or 'en'. */
function elio_lang() {
	if ( function_exists( 'pll_current_language' ) ) {
		$l = pll_current_language( 'slug' );
		if ( $l ) {
			return 'en' === $l ? 'en' : 'vi';
		}
	}
	return 0 === strpos( determine_locale(), 'en' ) ? 'en' : 'vi';
}

/**
 * Home URL of the current language, with an optional #anchor. With a static front page,
 * Polylang's canonical URL for a translated home page is that page's own URL (/en/home/);
 * /en/ redirects there, so linking to it directly saves a redirect.
 */
function elio_home( $hash = '' ) {
	$url = function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' );
	return $url . ( $hash ? '#' . $hash : '' );
}

function elio_t( $key ) {
	static $s = null;
	if ( null === $s ) {
		$s = [
			'vi' => [
				'navCta'      => 'ĐẶT LỊCH THAM QUAN →',
				'langLabel'   => 'Chọn ngôn ngữ',
				'menuOpen'    => 'Mở menu',
				'menuClose'   => 'Đóng menu',
				'nav'         => [ [ 'Về ELIO', 'why' ], [ 'Chương trình', 'approach' ], [ 'Không gian', 'campus' ], [ 'Tuyển sinh', 'tuition' ], [ 'Đời sống phụ huynh', 'parents' ], [ 'Tin tức', 'news' ] ],
				'ftContact'   => 'LIÊN HỆ',
				'ftLinks'     => 'LIÊN KẾT NHANH',
				'ftFollow'    => 'THEO DÕI ELIO',
				'addr'        => 'Thảo Điền, TP.HCM',
				'quick'       => [ [ 'Về ELIO', 'why' ], [ 'Chương trình', 'approach' ], [ 'Không gian', 'campus' ], [ 'Tuyển sinh', 'tuition' ], [ 'Liên hệ', 'tour' ] ],
				'fTitle'      => 'ĐẶT LỊCH THAM QUAN',
				'fSub'        => 'Đội ngũ Tuyển sinh ELIO sẽ liên hệ với ba mẹ trong vòng 24 giờ.',
				'fName'       => 'Họ và tên phụ huynh *',
				'fNamePh'     => 'Nhập họ và tên',
				'fPhone'      => 'Số điện thoại *',
				'fPhonePh'    => 'Nhập số điện thoại',
				'fAge'        => 'Độ tuổi của con *',
				'ages'        => [ 'Chọn độ tuổi của con', '18–24 tháng', '2–3 tuổi', '4–6 tuổi' ],
				'fDate'       => 'Ngày tham quan mong muốn *',
				'fMsg'        => 'Điều ba mẹ muốn tìm hiểu',
				'fMsgPh'      => 'Chia sẻ câu hỏi hoặc thông tin ba mẹ quan tâm...',
				'fBtn'        => 'ĐẶT LỊCH THAM QUAN →',
				'fSending'    => 'ĐANG GỬI...',
				'errRequired' => 'Vui lòng điền thông tin này.',
				'errPhone'    => 'Số điện thoại chưa hợp lệ.',
				'errDate'     => 'Vui lòng chọn một ngày từ hôm nay trở đi.',
				'errSubmit'   => 'Chưa gửi được yêu cầu. Ba mẹ vui lòng thử lại.',
				'thanks'      => 'Cảm ơn ba mẹ!',
				'thanksP'     => 'Đội ngũ Tuyển sinh ELIO sẽ liên hệ trong vòng 24 giờ. Một chuyến tham quan, một bước đầu tiên cho hành trình của con.',
				'steps'       => [ [ 'Chúng tôi gọi lại', 'Trong vòng 24 giờ' ], [ 'Xác nhận lịch hẹn', 'Thời gian phù hợp với ba mẹ' ], [ 'Gặp nhau tại ELIO', 'Tham quan cùng tư vấn viên' ] ],
				'again'       => 'Gửi yêu cầu khác',
				'news'        => 'Tin tức',
				'backToNews'  => '← Tất cả bài viết',
				'noPosts'     => 'Chưa có bài viết nào.',
				'searchFor'   => 'Kết quả tìm kiếm cho',
				'notFoundK'   => 'LỖI 404',
				'notFoundH'   => 'Không tìm thấy trang',
				'notFoundP'   => 'Trang ba mẹ tìm có thể đã được chuyển hoặc không còn tồn tại.',
				'backHome'    => 'VỀ TRANG CHỦ →',
			],
			'en' => [
				'navCta'      => 'BOOK A TOUR →',
				'langLabel'   => 'Choose language',
				'menuOpen'    => 'Open menu',
				'menuClose'   => 'Close menu',
				'nav'         => [ [ 'About ELIO', 'why' ], [ 'Programs', 'approach' ], [ 'Campus', 'campus' ], [ 'Admissions', 'tuition' ], [ 'Parent life', 'parents' ], [ 'News', 'news' ] ],
				'ftContact'   => 'CONTACT',
				'ftLinks'     => 'QUICK LINKS',
				'ftFollow'    => 'FOLLOW ELIO',
				'addr'        => 'Thao Dien, Ho Chi Minh City',
				'quick'       => [ [ 'About', 'why' ], [ 'Learning', 'approach' ], [ 'Campus', 'campus' ], [ 'Admissions', 'tuition' ], [ 'Contact', 'tour' ] ],
				'fTitle'      => 'BOOK A SCHOOL TOUR',
				'fSub'        => 'Our admissions team will get back to you within 24 hours.',
				'fName'       => 'Parent’s full name *',
				'fNamePh'     => 'Enter your full name',
				'fPhone'      => 'Phone number *',
				'fPhonePh'    => 'Enter your phone number',
				'fAge'        => 'Child’s age *',
				'ages'        => [ 'Select your child’s age', '18–24 months', '2–3 years', '4–6 years' ],
				'fDate'       => 'Preferred visit date *',
				'fMsg'        => 'What would you like to know?',
				'fMsgPh'      => 'Share any questions or topics you care about...',
				'fBtn'        => 'BOOK A SCHOOL TOUR →',
				'fSending'    => 'SENDING...',
				'errRequired' => 'Please fill in this field.',
				'errPhone'    => 'Please enter a valid phone number.',
				'errDate'     => 'Please choose today or a later date.',
				'errSubmit'   => 'We couldn’t send your request. Please try again.',
				'thanks'      => 'Thank you!',
				'thanksP'     => 'Our admissions team will be in touch within 24 hours. One visit — the first step of your child’s journey.',
				'steps'       => [ [ 'We call you', 'Within 24 hours' ], [ 'Confirm your visit', 'A time that suits you' ], [ 'See you at ELIO', 'A 1-on-1 campus tour' ] ],
				'again'       => 'Send another request',
				'news'        => 'News',
				'backToNews'  => '← All posts',
				'noPosts'     => 'No posts yet.',
				'searchFor'   => 'Search results for',
				'notFoundK'   => 'ERROR 404',
				'notFoundH'   => 'Page not found',
				'notFoundP'   => 'The page you are looking for may have moved or no longer exists.',
				'backHome'    => 'BACK TO HOME →',
			],
		];
	}
	return $s[ elio_lang() ][ $key ] ?? $s['vi'][ $key ] ?? $key;
}
