<?php
/**
 * Server-rendered blocks: site header, site footer, tour-booking form, and the heading on
 * blog list pages. Their text comes from inc/i18n.php and the ELIO settings page, so they
 * follow the visitor's language without being duplicated per language.
 *
 * @package elio
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		wp_register_script(
			'elio-blocks-editor',
			get_theme_file_uri( 'blocks/editor.js' ),
			[ 'wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-i18n' ],
			wp_get_theme()->get( 'Version' ),
			true
		);
		$common = [
			'api_version'          => 3,
			'category'             => 'theme',
			'editor_script_handles' => [ 'elio-blocks-editor' ],
			'supports'             => [ 'html' => false, 'reusable' => false ],
		];
		register_block_type( 'elio/header', $common + [ 'title' => 'ELIO – Header', 'render_callback' => 'elio_render_header' ] );
		register_block_type( 'elio/footer', $common + [ 'title' => 'ELIO – Footer', 'render_callback' => 'elio_render_footer' ] );
		register_block_type( 'elio/tour-form', $common + [ 'title' => 'ELIO – Form đặt lịch tham quan', 'render_callback' => 'elio_render_tour_form' ] );
		register_block_type( 'elio/archive-header', $common + [ 'title' => 'ELIO – Tiêu đề trang tin tức', 'render_callback' => 'elio_render_archive_header' ] );
		register_block_type( 'elio/back-to-news', $common + [ 'title' => 'ELIO – Link về trang tin tức', 'render_callback' => 'elio_render_back_to_news' ] );
		register_block_type( 'elio/not-found', $common + [ 'title' => 'ELIO – Trang 404', 'render_callback' => 'elio_render_not_found' ] );
		register_block_type(
			'elio/no-posts',
			$common + [
				'title'           => 'ELIO – Chưa có bài viết',
				'render_callback' => fn() => '<p class="text-[15px] text-muted">' . esc_html( elio_t( 'noPosts' ) ) . '</p>',
			]
		);
	}
);

/** Menu items for a theme location (Polylang picks the menu of the current language). */
function elio_menu_items( $location ) {
	$locations = get_nav_menu_locations();
	if ( empty( $locations[ $location ] ) ) {
		return null;
	}
	$items = wp_get_nav_menu_items( $locations[ $location ] );
	if ( ! $items ) {
		return null;
	}
	$out = [];
	foreach ( $items as $it ) {
		if ( ! $it->menu_item_parent ) {
			$out[] = [ $it->title, $it->url ];
		}
	}
	return $out;
}

/** Fallback links: [label, anchor] pairs from i18n → absolute links to the home page. */
function elio_anchor_links( $key ) {
	return array_map( fn( $l ) => [ $l[0], elio_home( $l[1] ) ], elio_t( $key ) );
}

function elio_render_header() {
	$nav  = elio_menu_items( 'primary' ) ?? elio_anchor_links( 'nav' );
	$logo = elio_img( 'elio-logo-transparent.png' );
	$tour = elio_home( 'tour' );

	$langs = [];
	if ( function_exists( 'pll_the_languages' ) ) {
		$raw = pll_the_languages( [ 'raw' => 1, 'hide_if_empty' => 0, 'hide_if_no_translation' => 0 ] );
		if ( is_array( $raw ) && count( $raw ) > 1 ) {
			$langs = $raw;
		}
	}
	$lang_names = [ 'vi' => 'Tiếng Việt', 'en' => 'English' ];

	ob_start();
	?>
	<header class="relative z-30 flex items-center gap-3 border-b border-forest/12 py-3.5 sm:gap-5 lg:py-5 xl:gap-8 px-5 md:px-10 lg:px-14" data-elio-header>
		<a href="<?php echo esc_url( elio_home() ); ?>" class="mr-auto block shrink-0">
			<img src="<?php echo esc_url( $logo ); ?>" alt="Elio International Preschool" width="1236" height="542" class="h-9 w-auto lg:h-11">
		</a>

		<nav class="hidden gap-[26px] text-sm font-medium xl:flex" aria-label="<?php echo esc_attr( 'en' === elio_lang() ? 'Main' : 'Chính' ); ?>">
			<?php foreach ( $nav as $n ) : ?>
				<a href="<?php echo esc_url( $n[1] ); ?>" class="whitespace-nowrap text-forest hover:text-gold-label"><?php echo esc_html( $n[0] ); ?></a>
			<?php endforeach; ?>
		</nav>

		<?php if ( $langs ) : ?>
			<div class="relative shrink-0" data-elio-lang>
				<button type="button" aria-label="<?php echo esc_attr( elio_t( 'langLabel' ) ); ?>" aria-expanded="false" class="flex cursor-pointer items-center gap-1.5 rounded-full border border-forest/20 bg-transparent px-3 py-2.5 text-[13px] font-semibold tracking-[.06em] text-forest hover:border-forest">
					<?php echo elio_icon( 'globe', 'size-[18px]' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span><?php echo esc_html( strtoupper( elio_lang() ) ); ?></span>
				</button>
				<div hidden class="absolute top-[calc(100%+8px)] right-0 z-20 flex min-w-[168px] flex-col gap-0.5 rounded-xl border border-forest/12 bg-ivory p-1.5 shadow-[0_14px_30px_rgba(15,58,46,.14)]">
					<?php foreach ( $langs as $l ) : ?>
						<a href="<?php echo esc_url( $l['url'] ); ?>" hreflang="<?php echo esc_attr( $l['slug'] ); ?>" class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-left text-sm text-forest hover:bg-cream <?php echo $l['current_lang'] ? 'bg-cream font-semibold' : 'font-normal'; ?>">
							<span class="w-[22px] text-[11px] tracking-[.1em] text-gold-label"><?php echo esc_html( strtoupper( $l['slug'] ) ); ?></span>
							<span class="flex-1"><?php echo esc_html( $lang_names[ $l['slug'] ] ?? $l['name'] ); ?></span>
							<?php
							if ( $l['current_lang'] ) {
								echo elio_icon( 'check', 'size-3.5 text-gold-label' ); // phpcs:ignore WordPress.Security.EscapeOutput
							}
							?>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<span class="elio-pill is-dark hidden shrink-0 sm:block [&_a]:px-5 [&_a]:py-3"><a href="<?php echo esc_url( $tour ); ?>"><?php echo esc_html( elio_t( 'navCta' ) ); ?></a></span>

		<button type="button" data-elio-menu aria-expanded="false" aria-label="<?php echo esc_attr( elio_t( 'menuOpen' ) ); ?>" data-label-open="<?php echo esc_attr( elio_t( 'menuOpen' ) ); ?>" data-label-close="<?php echo esc_attr( elio_t( 'menuClose' ) ); ?>" class="flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full border border-forest/20 bg-transparent text-forest hover:border-forest xl:hidden">
			<?php echo elio_icon( 'list', 'size-5 [[aria-expanded=true]>&]:hidden' ) . elio_icon( 'x', 'hidden size-5 [[aria-expanded=true]>&]:block' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</button>

		<div hidden data-elio-menu-panel class="absolute inset-x-0 top-full z-10 border-b border-forest/12 bg-ivory px-5 pb-6 shadow-[0_18px_30px_rgba(15,58,46,.10)] md:px-10 lg:px-14 xl:hidden">
			<nav class="flex flex-col">
				<?php foreach ( $nav as $n ) : ?>
					<a href="<?php echo esc_url( $n[1] ); ?>" class="border-b border-forest/10 py-4 text-base font-medium text-forest hover:text-gold-label"><?php echo esc_html( $n[0] ); ?></a>
				<?php endforeach; ?>
			</nav>
			<span class="elio-pill is-dark mt-5 block sm:hidden [&_a]:w-full [&_a]:px-5 [&_a]:py-4"><a href="<?php echo esc_url( $tour ); ?>"><?php echo esc_html( elio_t( 'navCta' ) ); ?></a></span>
		</div>
	</header>
	<?php
	return ob_get_clean();
}

function elio_render_footer() {
	$s     = elio_settings();
	$links = elio_menu_items( 'footer' ) ?? elio_anchor_links( 'quick' );
	$phone = $s['phone'];
	$tel   = 'tel:' . preg_replace( '/[^\d+]/', '', preg_replace( '/^0/', '+84', $phone ) );
	$addr  = 'en' === elio_lang() && $s['address_en'] ? $s['address_en'] : ( $s['address'] ? $s['address'] : elio_t( 'addr' ) );

	$contact = [
		[ 'map-pin', $addr, $s['maps_url'] ? $s['maps_url'] : '#' ],
		[ 'phone', $phone, $tel ],
		[ 'envelope', $s['email'], 'mailto:' . $s['email'] ],
		[ 'map-trifold', 'Google Maps', $s['maps_url'] ? $s['maps_url'] : '#' ],
	];
	$socials = [
		[ 'Facebook', 'facebook', $s['facebook'] ],
		[ 'Instagram', 'instagram', $s['instagram'] ],
		[ 'TikTok', 'tiktok', $s['tiktok'] ],
		[ 'YouTube', 'youtube', $s['youtube'] ],
	];
	$heading = fn( $text ) => '<span class="mb-0.5 flex items-center gap-2 text-sm font-bold tracking-[.08em] text-forest"><span class="size-2 rounded-full bg-gold"></span>' . esc_html( $text ) . '</span>';

	ob_start();
	?>
	<footer class="relative flex flex-col gap-6 overflow-hidden border-t border-forest/10 bg-cream bg-no-repeat pt-8 pb-5 text-forest lg:gap-[22px] lg:pt-9 lg:pb-[18px] bg-[radial-gradient(circle_at_100%_0%,rgba(201,169,106,.2)_0_110px,transparent_111px),radial-gradient(circle_at_100%_0%,transparent_0_146px,rgba(201,169,106,.45)_146px_147.5px,transparent_148.5px),radial-gradient(circle_at_0%_100%,rgba(143,169,128,.18)_0_70px,transparent_71px)] px-5 md:px-10 lg:px-14">
		<div class="grid grid-cols-2 gap-x-5 gap-y-6 sm:grid-cols-3 sm:gap-x-8 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,.8fr)] lg:items-start lg:gap-10">
			<div class="col-span-2 flex flex-wrap items-center gap-x-3 gap-y-1.5 sm:col-span-3 lg:col-span-1 lg:flex-col lg:flex-nowrap lg:items-start lg:gap-2">
				<img src="<?php echo esc_url( elio_img( 'elio-logo-transparent.png' ) ); ?>" alt="ELIO International Preschool" width="1236" height="542" loading="lazy" class="h-7 w-auto lg:h-[30px]">
				<span class="text-lg leading-[1.2] font-medium tracking-[-.015em] text-forest lg:text-[22px]">ELIO International <span class="text-gold-text">Preschool</span></span>
				<span class="flex basis-full items-center gap-2 text-[11px] font-semibold tracking-[.18em] text-gold-label sm:basis-auto"><img src="<?php echo esc_url( elio_img( 'icon-sun.png' ) ); ?>" alt="" width="20" height="20" loading="lazy" class="size-5">A BRIGHT BEGINNING.</span>
			</div>

			<div class="flex min-w-0 flex-col gap-1.5 lg:gap-2">
				<?php echo $heading( elio_t( 'ftContact' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php foreach ( $contact as $row ) : ?>
					<a href="<?php echo esc_url( $row[2], [ 'http', 'https', 'tel', 'mailto' ] ); ?>" class="flex items-center gap-2 text-[13px] text-body hover:text-gold-text lg:gap-2.5">
						<?php echo elio_icon( $row[0], 'size-4 shrink-0 text-gold-text' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span class="min-w-0 wrap-anywhere"><?php echo str_replace( '@', '@<wbr>', esc_html( $row[1] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					</a>
				<?php endforeach; ?>
			</div>

			<div class="flex min-w-0 flex-col gap-1.5 lg:gap-2">
				<?php echo $heading( elio_t( 'ftLinks' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php foreach ( $links as $l ) : ?>
					<a href="<?php echo esc_url( $l[1] ); ?>" class="self-start text-[13px] text-body hover:text-gold-text">→ <?php echo esc_html( $l[0] ); ?></a>
				<?php endforeach; ?>
			</div>

			<div class="col-span-2 flex flex-wrap items-center justify-between gap-2 sm:col-span-1 sm:flex-col sm:items-start sm:justify-start lg:gap-2">
				<?php echo $heading( elio_t( 'ftFollow' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<div class="flex gap-2 sm:gap-2.5">
					<?php foreach ( $socials as $so ) : ?>
						<a href="<?php echo esc_url( $so[2] ? $so[2] : '#' ); ?>" aria-label="<?php echo esc_attr( $so[0] ); ?>" <?php echo $so[2] ? 'target="_blank" rel="noopener"' : ''; ?> class="flex size-9 items-center justify-center rounded-full bg-ivory text-forest shadow-[0_4px_10px_rgba(15,58,46,.08)] transition-colors hover:bg-gold hover:text-forest sm:size-10 lg:size-9">
							<?php echo elio_icon( $so[1], 'size-[17px]' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<div class="flex flex-col gap-1.5 border-t border-forest/12 pt-3.5 text-xs text-muted sm:flex-row sm:justify-between sm:gap-6">
			<span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> ELIO International Preschool</span>
			<span><?php echo esc_html( $addr ); ?></span>
		</div>
	</footer>
	<?php
	return ob_get_clean();
}

function elio_render_tour_form() {
	$field  = 'w-full rounded-[10px] border border-forest/20 bg-white px-3.5 py-3 text-[15px] font-normal text-forest aria-invalid:border-[#B4462E]';
	$label  = 'flex flex-col gap-1.5 text-xs font-semibold';
	$err    = 'text-xs font-medium text-[#B4462E]';
	$sun    = esc_url( elio_img( 'icon-sun.png' ) );
	$ages   = elio_t( 'ages' );
	$values = [ '18-24m', '2-3y', '4-6y' ];

	$form_cls = 'rounded-[min(260px,calc(50vw_-_20px))_min(260px,calc(50vw_-_20px))_28px_28px] px-5 pt-20 pb-8 sm:px-11 sm:pt-24 sm:pb-10 lg:rounded-[260px_260px_28px_28px]';
	$done_cls = 'rounded-[28px] p-6 sm:p-11';

	$msgs = [
		'required' => elio_t( 'errRequired' ),
		'phone'    => elio_t( 'errPhone' ),
		'date'     => elio_t( 'errDate' ),
		'sending'  => elio_t( 'fSending' ),
	];

	ob_start();
	?>
	<div class="elio-tour-card relative mx-auto w-full max-w-[520px] bg-ivory text-forest shadow-[0_30px_60px_rgba(0,0,0,.28)] lg:mx-0 lg:justify-self-end <?php echo esc_attr( $form_cls ); ?>" data-elio-tour data-form-class="<?php echo esc_attr( $form_cls ); ?>" data-done-class="<?php echo esc_attr( $done_cls ); ?>" data-msgs="<?php echo esc_attr( wp_json_encode( $msgs ) ); ?>" data-lang="<?php echo esc_attr( elio_lang() ); ?>">
		<form novalidate class="flex flex-col gap-4">
			<img src="<?php echo $sun; // phpcs:ignore WordPress.Security.EscapeOutput ?>" alt="" width="40" height="40" class="-mt-12 size-10 self-center sm:-mt-14">
			<div class="text-center text-lg font-semibold sm:text-xl"><?php echo esc_html( elio_t( 'fTitle' ) ); ?></div>
			<div class="text-center text-sm text-pretty text-muted"><?php echo esc_html( elio_t( 'fSub' ) ); ?></div>

			<div class="grid gap-3.5 sm:grid-cols-2">
				<label class="<?php echo esc_attr( $label ); ?>"><?php echo esc_html( elio_t( 'fName' ) ); ?>
					<input name="name" id="tour-name" autocomplete="name" maxlength="200" placeholder="<?php echo esc_attr( elio_t( 'fNamePh' ) ); ?>" class="<?php echo esc_attr( $field ); ?>">
					<span class="<?php echo esc_attr( $err ); ?>" id="tour-name-err" hidden></span>
				</label>
				<label class="<?php echo esc_attr( $label ); ?>"><?php echo esc_html( elio_t( 'fPhone' ) ); ?>
					<input name="phone" id="tour-phone" type="tel" inputmode="numeric" pattern="[0-9]*" autocomplete="tel" placeholder="<?php echo esc_attr( elio_t( 'fPhonePh' ) ); ?>" class="<?php echo esc_attr( $field ); ?>">
					<span class="<?php echo esc_attr( $err ); ?>" id="tour-phone-err" hidden></span>
				</label>
				<label class="<?php echo esc_attr( $label ); ?>"><?php echo esc_html( elio_t( 'fAge' ) ); ?>
					<select name="age" id="tour-age" class="<?php echo esc_attr( $field ); ?>">
						<option value=""><?php echo esc_html( $ages[0] ); ?></option>
						<?php foreach ( $values as $i => $v ) : ?>
							<option value="<?php echo esc_attr( $v ); ?>"><?php echo esc_html( $ages[ $i + 1 ] ); ?></option>
						<?php endforeach; ?>
					</select>
					<span class="<?php echo esc_attr( $err ); ?>" id="tour-age-err" hidden></span>
				</label>
				<label class="<?php echo esc_attr( $label ); ?>"><?php echo esc_html( elio_t( 'fDate' ) ); ?>
					<input name="date" id="tour-date" type="date" class="<?php echo esc_attr( $field ); ?> py-[11px]">
					<span class="<?php echo esc_attr( $err ); ?>" id="tour-date-err" hidden></span>
				</label>
			</div>
			<label class="<?php echo esc_attr( $label ); ?>"><?php echo esc_html( elio_t( 'fMsg' ) ); ?>
				<textarea name="message" id="tour-message" rows="3" maxlength="2000" placeholder="<?php echo esc_attr( elio_t( 'fMsgPh' ) ); ?>" class="<?php echo esc_attr( $field ); ?> resize-none"></textarea>
			</label>
			<?php // Honeypot: real visitors never see or fill this. ?>
			<input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="absolute -left-[9999px] size-px opacity-0">

			<p role="alert" hidden data-elio-tour-error class="m-0 text-center text-[13px] font-medium text-[#B4462E]"><?php echo esc_html( elio_t( 'errSubmit' ) ); ?></p>
			<button type="submit" class="cursor-pointer rounded-full border-0 bg-forest p-4 text-[13px] font-semibold tracking-[.06em] text-ivory transition-colors hover:bg-forest-hover disabled:cursor-wait disabled:opacity-70"><?php echo esc_html( elio_t( 'fBtn' ) ); ?></button>
		</form>

		<div class="relative flex flex-col gap-6" role="status" hidden data-elio-tour-done>
			<span class="absolute -top-2 right-2 size-3.5 rounded-full bg-gold"></span>
			<span class="absolute top-9 right-16 size-2 rounded-full bg-sage"></span>
			<span class="absolute top-1 right-[110px] size-2.5 rotate-[25deg] rounded-[3px] bg-gold-light"></span>
			<div class="flex items-center gap-4 sm:gap-[18px]">
				<span class="relative flex size-16 shrink-0 items-center justify-center rounded-full bg-butter sm:size-[76px]">
					<img src="<?php echo $sun; // phpcs:ignore WordPress.Security.EscapeOutput ?>" alt="" width="44" height="44" class="size-9 sm:size-11">
					<span class="absolute -right-0.5 -bottom-0.5 flex size-7 items-center justify-center rounded-full border-3 border-ivory bg-forest text-ivory"><?php echo elio_icon( 'check', 'size-3.5' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				</span>
				<div class="flex flex-col gap-1">
					<span class="text-[11px] font-semibold tracking-[.18em] text-gold-label">A BRIGHT BEGINNING.</span>
					<span class="text-[26px] leading-[1.1] font-medium tracking-[-.015em] sm:text-[32px]"><?php echo esc_html( elio_t( 'thanks' ) ); ?></span>
				</div>
			</div>
			<div class="text-[15px] leading-[1.65] text-pretty text-muted"><?php echo esc_html( elio_t( 'thanksP' ) ); ?></div>
			<div class="flex flex-col rounded-[20px] border border-forest/10 bg-paper px-5 py-5 sm:px-[22px]">
				<?php
				$steps = elio_t( 'steps' );
				foreach ( $steps as $i => $st ) :
					?>
					<div class="grid grid-cols-[36px_minmax(0,1fr)] gap-3.5">
						<div class="flex flex-col items-center">
							<span class="flex size-9 items-center justify-center rounded-full bg-forest text-xs font-bold text-gold-light">0<?php echo (int) $i + 1; ?></span>
							<?php if ( $i < count( $steps ) - 1 ) : ?>
								<span class="min-h-3.5 w-0 flex-1 border-l-2 border-dashed border-gold-label/40"></span>
							<?php endif; ?>
						</div>
						<div class="flex flex-col gap-0.5 pt-1.5 pb-4">
							<span class="text-[15px] font-semibold"><?php echo esc_html( $st[0] ); ?></span>
							<span class="text-[13px] text-muted"><?php echo esc_html( $st[1] ); ?></span>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<button type="button" data-elio-tour-again class="cursor-pointer self-start rounded-full border border-forest/25 bg-transparent px-5 py-3 text-[13px] font-semibold tracking-[.04em] text-forest hover:border-forest">← <?php echo esc_html( elio_t( 'again' ) ); ?></button>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/** "← All posts" link to the news page of the current language. */
function elio_render_back_to_news() {
	$page = (int) get_option( 'page_for_posts' );
	$url  = $page ? get_permalink( $page ) : elio_home();
	return '<a href="' . esc_url( $url ) . '" class="self-start text-[13px] font-semibold tracking-[.06em] text-forest hover:text-gold-label">' . esc_html( elio_t( 'backToNews' ) ) . '</a>';
}

function elio_render_not_found() {
	return '<div class="flex flex-col items-start gap-5"><p class="elio-kicker">' . esc_html( elio_t( 'notFoundK' ) ) . '</p>'
		. '<h1 class="text-[34px] leading-[1.08] font-light tracking-[-.025em] text-forest md:text-[42px] lg:text-[52px]">' . esc_html( elio_t( 'notFoundH' ) ) . '</h1>'
		. '<p class="max-w-[520px] text-[15px] leading-[1.7] text-muted lg:text-[17px]">' . esc_html( elio_t( 'notFoundP' ) ) . '</p>'
		. '<span class="elio-pill is-dark mt-2"><a href="' . esc_url( elio_home() ) . '">' . esc_html( elio_t( 'backHome' ) ) . '</a></span></div>';
}

/** Title block for the blog list, category/tag archives and search results. */
function elio_render_archive_header() {
	$kicker = elio_t( 'news' );
	$desc   = '';
	if ( is_search() ) {
		$title = elio_t( 'searchFor' ) . ' “' . get_search_query() . '”';
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$title = single_term_title( '', false );
		$desc  = term_description();
	} elseif ( is_home() && get_option( 'page_for_posts' ) ) {
		$title = get_the_title( get_option( 'page_for_posts' ) );
	} elseif ( is_archive() ) {
		$title = wp_strip_all_tags( get_the_archive_title() );
	} else {
		$title = $kicker;
	}
	return '<div class="flex flex-col gap-3.5"><p class="elio-kicker">' . esc_html( mb_strtoupper( $kicker ) ) . '</p>'
		. '<h1 class="text-[34px] leading-[1.08] font-light tracking-[-.025em] text-forest md:text-[42px] lg:text-[52px]">' . esc_html( $title ) . '</h1>'
		. ( $desc ? '<div class="max-w-[640px] text-[15px] leading-[1.7] text-muted lg:text-base">' . wp_kses_post( $desc ) . '</div>' : '' )
		. '</div>';
}
