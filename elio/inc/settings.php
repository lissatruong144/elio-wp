<?php
/**
 * Settings → ELIO: Google Sheet webhook, contact details, social links, and a button that
 * creates the starter content (home page in both languages, news page, menus, sample posts).
 *
 * @package elio
 */

defined( 'ABSPATH' ) || exit;

const ELIO_SETTINGS_DEFAULTS = [
	'webhook_url' => '',
	'secret'      => '',
	'phone'       => '0834 1234 56',
	'email'       => 'elio@gmail.com',
	'address'     => '',
	'address_en'  => '',
	'maps_url'    => '',
	'facebook'    => '',
	'instagram'   => '',
	'tiktok'      => '',
	'youtube'     => '',
];

/** Saved settings; ELIO_SHEET_WEBHOOK_URL / ELIO_SHEET_SECRET in wp-config.php take priority. */
function elio_settings() {
	$s = wp_parse_args( (array) get_option( 'elio_settings', [] ), ELIO_SETTINGS_DEFAULTS );
	if ( defined( 'ELIO_SHEET_WEBHOOK_URL' ) ) {
		$s['webhook_url'] = ELIO_SHEET_WEBHOOK_URL;
	}
	if ( defined( 'ELIO_SHEET_SECRET' ) ) {
		$s['secret'] = ELIO_SHEET_SECRET;
	}
	return $s;
}

add_action(
	'admin_menu',
	function () {
		add_options_page( 'ELIO', 'ELIO', 'manage_options', 'elio', 'elio_settings_page' );
	}
);

add_action(
	'admin_post_elio_save',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Forbidden', 403 );
		}
		check_admin_referer( 'elio_save' );
		$in  = wp_unslash( $_POST['elio'] ?? [] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$old = (array) get_option( 'elio_settings', [] );
		$new = [];
		foreach ( array_keys( ELIO_SETTINGS_DEFAULTS ) as $k ) {
			$v = trim( (string) ( $in[ $k ] ?? '' ) );
			if ( in_array( $k, [ 'webhook_url', 'maps_url', 'facebook', 'instagram', 'tiktok', 'youtube' ], true ) ) {
				$v = esc_url_raw( $v );
			} elseif ( 'email' === $k ) {
				$v = sanitize_email( $v );
			} else {
				$v = sanitize_text_field( $v );
			}
			$new[ $k ] = $v;
		}
		// An empty secret field means "keep the current one" (it is never printed back).
		if ( '' === $new['secret'] ) {
			$new['secret'] = $old['secret'] ?? '';
		}
		update_option( 'elio_settings', $new, false );
		wp_safe_redirect( admin_url( 'options-general.php?page=elio&saved=1' ) );
		exit;
	}
);

add_action(
	'admin_post_elio_demo',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Forbidden', 403 );
		}
		check_admin_referer( 'elio_demo' );
		$log = elio_setup_demo();
		set_transient( 'elio_demo_log', $log, 300 );
		wp_safe_redirect( admin_url( 'options-general.php?page=elio&demo=1' ) );
		exit;
	}
);

/** Polylang state: [active, has vi, has en]. */
function elio_pll_state() {
	if ( ! function_exists( 'pll_languages_list' ) ) {
		return [ false, false, false ];
	}
	$langs = pll_languages_list( [ 'fields' => 'slug' ] );
	return [ true, in_array( 'vi', $langs, true ), in_array( 'en', $langs, true ) ];
}

function elio_settings_page() {
	$s = elio_settings();
	list( $pll, $has_vi, $has_en ) = elio_pll_state();
	$row = function ( $key, $label, $help = '', $type = 'text' ) use ( $s ) {
		$val = 'secret' === $key ? '' : $s[ $key ];
		$ph  = 'secret' === $key && $s['secret'] ? '•••••••• (đã lưu – để trống nếu không đổi)' : '';
		echo '<tr><th scope="row"><label for="elio-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>'
			. '<input type="' . esc_attr( $type ) . '" class="regular-text" id="elio-' . esc_attr( $key ) . '" name="elio[' . esc_attr( $key ) . ']" value="' . esc_attr( $val ) . '" placeholder="' . esc_attr( $ph ) . '" autocomplete="off">'
			. ( $help ? '<p class="description">' . wp_kses_post( $help ) . '</p>' : '' )
			. '</td></tr>';
	};
	?>
	<div class="wrap">
		<h1>ELIO – Cài đặt website</h1>
		<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success"><p>Đã lưu cài đặt.</p></div>
		<?php endif; ?>
		<?php
		$log = get_transient( 'elio_demo_log' );
		if ( isset( $_GET['demo'] ) && $log ) : // phpcs:ignore WordPress.Security.NonceVerification
			delete_transient( 'elio_demo_log' );
			?>
			<div class="notice notice-success"><p><strong>Đã tạo nội dung mẫu:</strong></p><ul style="list-style:disc;padding-left:20px"><?php foreach ( $log as $line ) { echo '<li>' . esc_html( $line ) . '</li>'; } ?></ul></div>
		<?php endif; ?>

		<h2>1. Nội dung mẫu</h2>
		<?php if ( ! $pll ) : ?>
			<p><strong>Chưa cài Polylang.</strong> Để có song ngữ, hãy cài plugin <em>Polylang</em> (miễn phí) tại Plugins → Add New, rồi vào Languages thêm <strong>Tiếng Việt</strong> (mặc định) và <strong>English</strong>. Bạn vẫn có thể tạo nội dung mẫu ngay, khi đó chỉ có bản tiếng Việt.</p>
		<?php elseif ( ! $has_vi || ! $has_en ) : ?>
			<p><strong>Polylang chưa có đủ ngôn ngữ.</strong> Vào <a href="<?php echo esc_url( admin_url( 'admin.php?page=mlang' ) ); ?>">Languages</a> và thêm <strong>Tiếng Việt</strong> (đặt làm mặc định) và <strong>English</strong>, rồi quay lại đây.</p>
		<?php else : ?>
			<p>Polylang đã sẵn sàng với Tiếng Việt và English.</p>
		<?php endif; ?>
		<p>Nút dưới đây tạo: trang chủ (VI và EN), trang Tin tức/News, menu, 3 chuyên mục và 3 bài viết mẫu cho mỗi ngôn ngữ, rồi đặt trang chủ làm trang đầu. Những trang đã tạo trước đó sẽ không bị ghi đè.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="elio_demo">
			<?php wp_nonce_field( 'elio_demo' ); ?>
			<?php submit_button( 'Tạo nội dung mẫu', 'secondary', 'submit', false ); ?>
		</form>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="elio_save">
			<?php wp_nonce_field( 'elio_save' ); ?>

			<h2 style="margin-top:2em">2. Form đặt lịch tham quan → Google Sheet</h2>
			<?php if ( defined( 'ELIO_SHEET_WEBHOOK_URL' ) || defined( 'ELIO_SHEET_SECRET' ) ) : ?>
				<p><em>Đang dùng giá trị khai báo trong wp-config.php (ELIO_SHEET_WEBHOOK_URL / ELIO_SHEET_SECRET).</em></p>
			<?php endif; ?>
			<table class="form-table" role="presentation">
				<?php
				$row( 'webhook_url', 'Web app URL (Apps Script)', 'Link kết thúc bằng <code>/exec</code> lấy từ Apps Script → Deploy → Web app.', 'url' );
				$row( 'secret', 'SECRET', 'Phải trùng với <code>SECRET</code> trong Code.gs.', 'password' );
				?>
				<tr><th scope="row">Trạng thái</th><td><?php echo $s['webhook_url'] && $s['secret'] ? '✅ Đã cấu hình – đơn sẽ được ghi vào Google Sheet.' : '⚠️ Chưa cấu hình – form sẽ báo lỗi khi phụ huynh gửi.'; ?></td></tr>
			</table>

			<h2>3. Thông tin liên hệ (footer)</h2>
			<table class="form-table" role="presentation">
				<?php
				$row( 'phone', 'Số điện thoại' );
				$row( 'email', 'Email', '', 'email' );
				$row( 'address', 'Địa chỉ (tiếng Việt)', 'Để trống sẽ hiện “Thảo Điền, TP.HCM”.' );
				$row( 'address_en', 'Địa chỉ (English)' );
				$row( 'maps_url', 'Link Google Maps', '', 'url' );
				?>
			</table>

			<h2>4. Mạng xã hội</h2>
			<table class="form-table" role="presentation">
				<?php
				$row( 'facebook', 'Facebook', '', 'url' );
				$row( 'instagram', 'Instagram', '', 'url' );
				$row( 'tiktok', 'TikTok', '', 'url' );
				$row( 'youtube', 'YouTube', '', 'url' );
				?>
			</table>
			<?php submit_button( 'Lưu cài đặt' ); ?>
		</form>
	</div>
	<?php
}

add_action(
	'admin_notices',
	function () {
		if ( ! current_user_can( 'manage_options' ) || get_option( 'elio_demo_ids' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( $screen && 'settings_page_elio' === $screen->id ) {
			return;
		}
		echo '<div class="notice notice-info"><p><strong>Theme ELIO:</strong> vào <a href="' . esc_url( admin_url( 'options-general.php?page=elio' ) ) . '">Cài đặt → ELIO</a> để tạo trang chủ và nội dung mẫu.</p></div>';
	}
);
