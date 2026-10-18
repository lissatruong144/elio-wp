<?php
/**
 * Home page sections as block markup, one function per section (mirrors the Next.js
 * components). Sections marked "interactive" hold a plain list of items (one group per
 * item) that assets/js/elio.js turns into tabs / sliders / timelines on the front end;
 * in the editor the list stays a list, so adding an item is just duplicating a group.
 *
 * @package elio
 */

defined( 'ABSPATH' ) || exit;

const ELIO_LABEL = 'text-[11px] font-semibold tracking-[.18em] text-gold-label sm:text-xs';

function elio_section_hero( $c ) {
	$t = $c['t'];

	$badge = elio_group(
		'absolute top-3 left-3 flex items-center gap-2.5 rounded-[12px] bg-ivory/94 px-3 py-2 shadow-[0_10px_30px_rgba(15,58,46,.18)] md:top-5 md:left-auto md:right-5 md:gap-3 md:rounded-[14px] md:px-3.5 md:py-2.5 lg:top-10 lg:right-14 lg:px-[18px] lg:py-3.5',
		elio_image( 'size-6 shrink-0 md:size-7 lg:size-8 [&_img]:h-full [&_img]:w-full', elio_img( 'icon-sun.png' ) )
		. elio_group(
			'flex flex-col',
			elio_p( 'text-[9px] font-semibold tracking-[.14em] text-gold-label md:text-[10px] lg:text-[11px]', 'A BRIGHT BEGINNING.' )
			. elio_p( 'text-xs font-medium text-forest md:text-[13px] lg:text-sm', esc_html( $t['badge'] ) )
		)
	);

	$photo = elio_group(
		'relative aspect-[4/3] md:aspect-[16/10] lg:absolute lg:inset-0 lg:aspect-auto',
		elio_image( ELIO_FILL . ' [&_img]:object-[50%_40%] lg:[&_img]:object-[50%_92%]', elio_img( 'hero-nature-class.jpg' ), $t['heroAlt'] )
		. elio_html( '<div class="pointer-events-none absolute inset-0 bg-[linear-gradient(180deg,rgba(15,58,46,0)_0,rgba(15,58,46,0)_62%,rgba(15,58,46,.55)_84%,rgba(15,58,46,1)_100%)] lg:bg-[linear-gradient(180deg,rgba(15,58,46,0)_0,rgba(15,58,46,0)_50%,rgba(15,58,46,.6)_68%,rgba(11,44,35,.94)_100%)]"></div>' )
		. $badge
	);

	$h1 = esc_html( $t['h1a'] ) . '<strong>' . esc_html( $t['h1b'] ) . '</strong>' . esc_html( $t['h1c'] ) . '<strong>' . elio_color( 'gold-light', esc_html( $t['h1d'] ) ) . '</strong>';

	$copy = elio_group(
		'relative grid gap-5 px-5 pt-5 pb-6 text-ivory md:gap-6 md:px-8 md:pt-7 md:pb-9 lg:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)] lg:items-end lg:gap-16 lg:px-14 lg:pt-28 lg:pb-14',
		elio_group(
			'flex flex-col gap-3 lg:gap-[18px]',
			elio_p( 'elio-kicker is-light gap-2.5 text-[10px] font-medium tracking-[.16em] before:w-5 sm:text-xs lg:gap-3 lg:tracking-[.18em] lg:before:w-7', esc_html( $t['heroKicker'] ) )
			. elio_h( 'text-[27px] leading-[1.15] font-light tracking-[-.02em] text-pretty text-ivory sm:text-[36px] lg:text-[50px] lg:leading-[1.12] [&_strong]:font-semibold', $h1, 1 )
		)
		. elio_group(
			'flex flex-col gap-5 lg:gap-[22px] lg:pb-1.5',
			elio_p( 'text-sm leading-[1.6] text-pretty text-ivory/85 md:text-[15px] lg:text-base lg:leading-[1.65]', esc_html( $t['heroP'] ) )
			. elio_group(
				'flex flex-col gap-2.5 sm:flex-row sm:flex-wrap sm:items-center lg:gap-3',
				elio_pill( 'ivory', '#tour', $t['heroCta'], '[&_a]:w-full [&_a]:px-5 [&_a]:py-3.5 [&_a]:text-xs sm:[&_a]:w-auto lg:[&_a]:px-6 lg:[&_a]:py-4 lg:[&_a]:text-[13px]' )
				. elio_pill( 'ghost-light', '#', $t['video'], '[&_a]:w-full [&_a]:px-[18px] [&_a]:py-[13px] [&_a]:text-xs sm:[&_a]:w-auto lg:[&_a]:px-[22px] lg:[&_a]:py-[15px] lg:[&_a]:text-[13px]' )
			)
		)
	);

	$hero = elio_group(
		'relative mx-5 mt-3 mb-4 flex flex-col overflow-hidden rounded-[26px] bg-forest shadow-[0_18px_40px_-20px_rgba(15,58,46,.45)] md:mx-10 md:rounded-[32px] lg:mx-0 lg:mt-0 lg:mb-0 lg:h-[720px] lg:justify-end lg:rounded-none lg:shadow-none',
		$photo . $copy,
		'section',
		'top'
	);

	$quick = '';
	foreach ( $c['quick'] as $q ) {
		$quick .= elio_group(
			'flex flex-col gap-2 px-3 py-5 sm:px-4 lg:py-[22px]',
			elio_image( 'size-[26px] lg:size-[30px] [&_img]:h-full [&_img]:w-full', $q['icon'] )
			. elio_p( 'text-[10px] tracking-[.1em] text-muted sm:text-[11px] xl:whitespace-nowrap', esc_html( $q['k'] ) )
			. elio_p( 'text-[13px] leading-[1.3] font-semibold sm:text-sm', esc_html( $q['v'] ) )
		);
	}

	return $hero . elio_group( 'mx-5 grid grid-cols-2 border-y border-forest/12 sm:grid-cols-3 md:mx-10 lg:mx-14 lg:grid-cols-6', $quick );
}

/** Interactive: tab list + panel on desktop, accordion below lg. */
function elio_section_why( $c ) {
	$t     = $c['t'];
	$items = '';
	foreach ( $c['pillars'] as $p ) {
		$items .= elio_group(
			'elio-why-item',
			elio_image( 'elio-why-icon [&_img]:size-16', $p['icon'] )
			. elio_p( 'elio-why-n', esc_html( $p['n'] ) )
			. elio_p( 'elio-why-en', esc_html( $p['en'] ) )
			. elio_h( 'elio-why-title', esc_html( $p['title'] ), 3 )
			. elio_p( 'elio-why-body', esc_html( $p['body'] ) )
			. elio_p( 'elio-why-key', esc_html( $p['key'] ) )
		);
	}

	return elio_group(
		'flex scroll-mt-4 flex-col gap-8 pt-16 pb-14 md:gap-12 md:pt-20 lg:pt-[104px] lg:pb-[88px] ' . ELIO_GUTTER,
		elio_group(
			'grid gap-5 lg:grid-cols-2 lg:items-end lg:gap-14',
			elio_group(
				'flex flex-col gap-3.5',
				elio_p( ELIO_LABEL, 'WHY ELIO' )
				. elio_h( 'text-[28px] leading-[1.2] font-medium tracking-[-.015em] text-pretty md:text-[34px] lg:text-[38px]', esc_html( $t['whyH'] ) )
			)
			. elio_p( 'text-[15px] leading-[1.7] text-muted lg:text-base', esc_html( $t['whyP'] ) )
		)
		. elio_group( 'elio-why', $items ),
		'section',
		'why'
	);
}

/** Interactive: photo + detail card + timeline rail. */
function elio_section_day( $c ) {
	$t     = $c['t'];
	$stops = '';
	foreach ( $c['day'] as $d ) {
		$stops .= elio_group(
			'elio-day-stop',
			elio_image( 'elio-day-img ' . $d['pos'], $d['src'], $d['name'] )
			. elio_p( 'elio-day-time', esc_html( $d['t'] ) )
			. elio_p( 'elio-day-part ' . $d['tint'], esc_html( $d['part'] ) )
			. elio_h( 'elio-day-name', esc_html( $d['name'] ), 3 )
			. elio_p( 'elio-day-body', esc_html( $d['body'] ) )
		);
	}

	$head = elio_group(
		'grid gap-8 lg:grid-cols-[minmax(0,1fr)_420px] lg:items-end lg:gap-14',
		elio_group(
			'flex flex-col gap-3.5',
			elio_p( ELIO_LABEL, 'THE ELIO EXPERIENCE' )
			. elio_h( 'text-[30px] font-medium tracking-[-.015em] md:text-[36px] lg:text-[40px]', esc_html( $t['dayH'] ) )
			. elio_p( 'text-[15px] leading-[1.6] text-muted lg:text-[17px]', esc_html( $t['dayP'] ) )
		)
		. elio_group(
			'relative flex items-start gap-3.5 rounded-[28px_28px_28px_6px] bg-ivory px-5 py-[18px] shadow-[0_10px_26px_rgba(15,58,46,.08)] sm:px-[26px] sm:py-[22px] lg:-rotate-[1.5deg]',
			elio_image( 'size-[30px] shrink-0 sm:size-[34px] [&_img]:h-full [&_img]:w-full', elio_img( 'icon-sun.png' ) )
			. elio_group(
				'flex flex-col gap-1.5',
				elio_p( 'text-base leading-[1.45] text-forest italic sm:text-[17px]', esc_html( $t['quote'] ) )
				. elio_p( 'text-[13px] text-muted', esc_html( $t['quoteBy'] ) )
			)
		)
	);

	return elio_group(
		'flex flex-col gap-8 bg-cream bg-no-repeat pt-16 pb-14 md:gap-11 md:pt-20 lg:pt-24 lg:pb-[88px] bg-[radial-gradient(circle_at_94%_0%,rgba(201,169,106,.24)_0_150px,transparent_151px),radial-gradient(circle_at_94%_0%,transparent_0_196px,rgba(201,169,106,.45)_196px_197.5px,transparent_198.5px),radial-gradient(circle_at_0%_100%,rgba(15,58,46,.06)_0_200px,transparent_201px)] ' . ELIO_GUTTER,
		$head . elio_group( 'elio-day', $stops ),
		'section',
		'day'
	);
}

function elio_section_nutrition( $c ) {
	$t     = $c['t'];
	$cards = '';
	foreach ( $c['nutri'] as $i => $n ) {
		$cards .= elio_group(
			'grid grid-cols-[52px_minmax(0,1fr)] items-center gap-x-4 gap-y-1 sm:flex sm:flex-col sm:items-start sm:gap-2.5 sm:px-[18px]' . ( $i > 0 ? ' sm:border-l sm:border-forest/14' : '' ),
			elio_image( 'row-span-2 flex size-[52px] items-center justify-center rounded-full ' . $n['tint'] . ' [&_img]:size-7', $n['icon'] )
			. elio_p( 'text-[15px] leading-[1.3] font-semibold text-forest', esc_html( $n['title'] ) )
			. elio_p( 'text-[13px] leading-[1.5] text-muted', esc_html( $n['body'] ) )
		);
	}

	$pols = '';
	foreach ( $c['polaroids'] as $p ) {
		$pols .= elio_group(
			'flex flex-col gap-2 rounded-md bg-white p-1.5 pb-2.5 shadow-[0_14px_30px_rgba(15,58,46,.18)] sm:gap-2.5 sm:p-2.5 sm:pb-3 lg:absolute lg:w-[204px] ' . $p['place'],
			elio_image( 'relative h-20 overflow-hidden bg-placeholder sm:h-[104px] [&_img]:h-full [&_img]:w-full [&_img]:object-cover', $p['src'], $p['cap'] )
			. elio_p( 'text-center text-[11px] leading-snug text-gold-label italic sm:text-[13px] lg:whitespace-nowrap', esc_html( $p['cap'] ) )
		);
	}

	$text = elio_group(
		'relative z-[1] flex flex-col gap-5 lg:gap-[22px]',
		elio_p( 'elio-kicker', 'NUTRITION' )
		. elio_h( 'text-[32px] leading-[1.1] font-light tracking-[-.02em] text-forest md:text-[38px] lg:text-[44px] [&_strong]:font-medium sm:[&_strong]:whitespace-nowrap', esc_html( $t['nuH1'] ) . '<br><strong>' . elio_color( 'gold-text', esc_html( $t['nuH2'] ) ) . '</strong>' )
		. elio_p( 'max-w-[440px] text-[15px] leading-[1.65] text-pretty text-muted lg:text-[17px]', esc_html( $t['nuP'] ) )
		. elio_group( 'mt-1.5 grid gap-5 sm:grid-cols-3 sm:gap-0', $cards )
		. elio_pill( 'dark', '#', $t['nuCta'], 'mt-2 self-start [&_a]:px-[30px] [&_a]:py-[17px] [&_a]:tracking-[.08em]' )
	);

	$photos = elio_group(
		'relative lg:h-[540px]',
		elio_group(
			'relative h-[340px] overflow-hidden rounded-[min(280px,calc(50vw_-_44px))_min(280px,calc(50vw_-_44px))_24px_24px] bg-placeholder sm:h-[440px] lg:absolute lg:inset-y-0 lg:right-[150px] lg:left-0 lg:h-auto lg:rounded-[280px_280px_32px_32px]',
			elio_image( ELIO_FILL . ' [&_img]:object-[62%_50%]', elio_img( 'day/04-lunch.jpg' ), $t['nuAlt'] )
			. elio_group(
				'absolute bottom-5 left-4 flex items-center gap-2.5 rounded-full bg-ivory/95 py-2.5 pr-[18px] pl-2.5 shadow-[0_10px_24px_rgba(15,58,46,.16)] sm:bottom-7 sm:left-6',
				elio_image( 'flex size-9 shrink-0 items-center justify-center rounded-full bg-sage-tint [&_img]:size-5', elio_img( 'icon-leaf.png' ) )
				. elio_p( 'text-[13px] font-semibold text-forest', esc_html( $t['nuBadge'] ) )
			)
		)
		. elio_group( 'mt-6 grid grid-cols-3 gap-3 sm:gap-5 lg:contents', $pols )
	);

	return elio_group(
		'bg-ivory py-14 md:py-20 lg:py-24 ' . ELIO_GUTTER,
		elio_group(
			'relative grid gap-10 overflow-hidden rounded-[28px] border border-forest/8 bg-paper bg-[radial-gradient(circle_at_0%_100%,rgba(143,169,128,.22)_0_160px,transparent_161px),radial-gradient(circle_at_0%_100%,transparent_0_200px,rgba(201,169,106,.4)_200px_201.5px,transparent_202.5px)] bg-no-repeat p-6 sm:p-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)] lg:items-center lg:gap-12 lg:rounded-[40px] lg:py-16 lg:pr-0 lg:pl-16',
			$text . $photos
		),
		'section',
		'nutrition'
	);
}

function elio_section_approach( $c ) {
	$t     = $c['t'];
	$cards = '';
	foreach ( $c['approach'] as $a ) {
		$chips = '';
		foreach ( $a['chips'] as $chip ) {
			$chips .= elio_p( 'rounded-full bg-ivory/85 px-[11px] py-1.5 text-xs font-semibold text-forest', esc_html( $chip ) );
		}
		$cards .= elio_group(
			'relative flex flex-col gap-4 rounded-[28px] px-3.5 pt-3.5 pb-[26px] shadow-[0_6px_16px_rgba(15,58,46,.06)] lg:rounded-[32px] ' . $a['tint'],
			elio_image( 'mt-3 ml-3 flex size-[72px] items-center justify-center rounded-full bg-ivory shadow-[0_8px_18px_rgba(15,58,46,.10)] [&_img]:size-9', $a['icon'] )
			. elio_group(
				'flex flex-col gap-2.5 px-3 pt-2',
				elio_group(
					'flex items-baseline gap-2.5',
					elio_p( 'text-[34px] leading-none font-bold ' . $a['ink'], esc_html( $a['n'] ) )
					. elio_p( 'text-[10px] leading-[1.4] font-semibold tracking-[.14em] text-muted', esc_html( $a['en'] ) )
				)
				. elio_h( 'text-xl leading-[1.25] font-semibold text-pretty text-forest', esc_html( $a['title'] ), 3 )
				. elio_p( 'text-sm leading-[1.6] text-pretty text-body', esc_html( $a['body'] ) )
				. elio_group( 'mt-1.5 flex flex-wrap gap-1.5', $chips )
			)
		);
	}

	return elio_group(
		'relative flex scroll-mt-4 flex-col gap-10 overflow-hidden bg-no-repeat pt-16 pb-16 md:pt-20 lg:gap-14 lg:pt-[104px] lg:pb-[120px] bg-[radial-gradient(circle_at_100%_18%,rgba(201,169,106,.2)_0_120px,transparent_121px),radial-gradient(circle_at_100%_18%,transparent_0_160px,rgba(201,169,106,.45)_160px_161.5px,transparent_162.5px),radial-gradient(circle_at_3%_92%,rgba(143,169,128,.18)_0_90px,transparent_91px)] ' . ELIO_GUTTER,
		elio_image( 'absolute top-5 right-5 size-9 -rotate-12 opacity-90 lg:top-7 lg:right-16 lg:size-[46px] [&_img]:h-full [&_img]:w-full', elio_img( 'icon-sun.png' ) )
		. elio_group(
			'relative grid gap-8 lg:grid-cols-[minmax(0,1fr)_380px] lg:items-end lg:gap-12',
			elio_group(
				'flex flex-col gap-3.5',
				elio_p( 'elio-kicker', 'OUR EDUCATIONAL APPROACH' )
				. elio_h( 'text-[32px] leading-[1.1] font-light tracking-[-.02em] text-forest md:text-[38px] lg:text-[44px]', esc_html( $t['apH'] ) )
			)
			. elio_p(
				'rounded-[28px_28px_6px_28px] bg-forest px-5 py-[18px] text-base leading-normal font-light text-ivory italic shadow-[0_14px_30px_rgba(15,58,46,.18)] sm:px-[26px] sm:py-[22px] sm:text-[17px] lg:rotate-[1.5deg] [&_strong]:font-medium',
				'“Children learn best when they feel <strong>' . elio_color( 'gold-light', 'safe, curious' ) . '</strong> and <strong>' . elio_color( 'gold-light', 'connected.' ) . '</strong>”'
			)
		)
		. elio_group(
			'relative grid gap-5 sm:grid-cols-2 lg:grid-cols-4 lg:gap-6',
			elio_html( '<div class="absolute top-[62px] right-[4%] left-[4%] hidden border-t-2 border-dashed border-gold-label/35 lg:block"></div>' ) . $cards
		),
		'section',
		'approach'
	);
}

function elio_section_grow( $c ) {
	$t     = $c['t'];
	$cols  = '';
	$last  = count( $c['grow'] ) - 1;
	$tones = [ 'bg-[rgba(255,253,247,.55)]', 'bg-[rgba(255,253,247,.65)]', 'bg-[rgba(255,253,247,.75)]', 'bg-[rgba(255,253,247,.85)]', 'bg-wheat' ];
	$steps = [ 'xl:min-h-[420px]', 'xl:min-h-[460px]', 'xl:min-h-[500px]', 'xl:min-h-[540px]', 'xl:min-h-[580px]' ];
	foreach ( $c['grow'] as $i => $g ) {
		$lit   = $i === $last;
		$items = '';
		foreach ( $g['items'] as $it ) {
			$items .= elio_p( 'elio-check' . ( $lit ? ' is-lit' : '' ) . ' flex items-center gap-2.5 text-sm font-medium', esc_html( $it ) );
		}
		$cols .= elio_group(
			'group flex flex-col sm:last:col-span-2 lg:last:col-span-1',
			elio_html( '<div class="flex items-center gap-2.5 pb-4 pl-1"><span class="size-3.5 rounded-full shadow-[0_0_0_6px_rgba(201,169,106,.18)] transition-transform duration-300 group-hover:scale-125 ' . ( $lit ? 'bg-gold-text' : 'bg-gold' ) . '"></span><span class="h-px flex-1 bg-forest/16"></span></div>' )
			. elio_group(
				'flex flex-1 flex-col gap-3.5 rounded-[28px_28px_8px_8px] px-6 py-7 text-forest transition-[translate,box-shadow] duration-300 ease-out group-hover:-translate-y-2 group-hover:shadow-[0_22px_40px_rgba(15,58,46,.14)] motion-reduce:transition-none motion-reduce:group-hover:translate-y-0 ' . $tones[ min( $i, 4 ) ] . ' ' . $steps[ min( $i, 4 ) ],
				elio_p( 'text-[44px] leading-none font-extralight tracking-[-.02em] text-gold-text', esc_html( $g['n'] ) )
				. elio_p( 'text-[10px] leading-normal font-semibold tracking-[.18em] text-gold-label', esc_html( $g['en'] ) )
				. elio_h( 'text-xl leading-[1.25] font-medium tracking-[-.01em] text-pretty', esc_html( $g['title'] ), 3 )
				. elio_p( 'text-sm leading-[1.6] text-pretty text-muted', esc_html( $g['body'] ) )
				. elio_group(
					'mt-auto flex flex-col gap-2.5 border-t border-forest/12 pt-4',
					elio_p( 'text-[11px] font-semibold tracking-[.14em] text-gold-label', esc_html( $t['grDev'] ) ) . $items
				)
			)
		);
	}

	return elio_group(
		'relative flex flex-col gap-10 overflow-hidden bg-sage-pale bg-no-repeat pt-16 pb-16 text-forest md:pt-20 lg:gap-16 lg:pt-28 lg:pb-[104px] bg-[radial-gradient(circle_at_100%_0%,rgba(201,169,106,.14)_0_220px,transparent_221px),radial-gradient(circle_at_100%_0%,transparent_0_280px,rgba(201,169,106,.35)_280px_281.5px,transparent_282.5px),radial-gradient(circle_at_0%_100%,rgba(143,169,128,.14)_0_260px,transparent_261px)] ' . ELIO_GUTTER,
		elio_group(
			'grid gap-5 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] lg:items-end lg:gap-16',
			elio_group(
				'flex flex-col gap-[18px]',
				elio_p( 'elio-kicker', 'WHAT YOUR CHILD WILL GROW' )
				. elio_h( 'text-[30px] leading-[1.05] font-light tracking-[-.03em] text-forest min-[360px]:text-[36px] md:text-[46px] lg:text-[48px] xl:text-[56px] [&_strong]:font-medium [&_strong]:whitespace-nowrap', esc_html( $t['grH1'] ) . '<br><strong>' . elio_color( 'gold-text', esc_html( $t['grH2'] ) ) . '</strong>' )
			)
			. elio_p( 'max-w-[520px] text-[15px] leading-[1.7] text-pretty text-muted lg:text-[17px]', esc_html( $t['grP'] ) )
		)
		. elio_group( 'relative grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 xl:items-end', $cols ),
		'section',
		'grow'
	);
}

/** Interactive: big arched photo + list (desktop) / thumbnail strip (phones). */
function elio_section_campus( $c ) {
	$t     = $c['t'];
	$items = '';
	foreach ( $c['campus'] as $cp ) {
		$items .= elio_group(
			'elio-campus-item',
			elio_image( 'elio-campus-img', $cp['src'], $cp['title'] )
			. elio_p( 'elio-campus-n', esc_html( $cp['n'] ) )
			. elio_p( 'elio-campus-en', esc_html( $cp['en'] ) )
			. elio_h( 'elio-campus-title', esc_html( $cp['title'] ), 3 )
			. elio_p( 'elio-campus-body', esc_html( $cp['body'] ) )
		);
	}

	$spec = fn( $k, $v ) => elio_group(
		'flex flex-col gap-1.5',
		elio_p( 'text-[10px] font-semibold tracking-[.2em] text-gold-label', $k )
		. elio_p( 'text-2xl font-light tracking-[-.01em] text-forest lg:text-[28px]', $v )
	);

	$glance = '';
	foreach ( $c['glance'] as $g ) {
		$glance .= elio_group(
			'flex items-start gap-3.5 bg-paper p-5 xl:flex-col xl:gap-4 xl:px-6 xl:py-7',
			elio_html( '<span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-sage-tint text-forest">' . elio_icon( $g['icon'], 'size-5' ) . '</span>' )
			. elio_group(
				'flex min-w-0 flex-col gap-1.5',
				elio_p( 'text-[11px] font-semibold tracking-[.14em] text-gold-label', esc_html( $g['k'] ) )
				. elio_p( 'text-sm leading-[1.45] font-medium text-forest lg:text-[15px]', esc_html( $g['v'] ) )
			)
		);
	}

	return elio_group(
		'flex scroll-mt-4 flex-col gap-8 border-t border-forest/12 pt-16 pb-16 md:pt-20 lg:gap-12 lg:pt-28 lg:pb-[104px] ' . ELIO_GUTTER,
		elio_group(
			'grid gap-6 border-b border-forest/20 pb-7 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end lg:gap-12',
			elio_group(
				'flex flex-col gap-4',
				elio_p( 'elio-kicker', 'OUR CAMPUS' )
				. elio_h( 'text-[36px] leading-[1.05] font-light tracking-[-.03em] text-forest md:text-[46px] lg:text-[56px]', esc_html( $t['campusH'] ) )
			)
			. elio_group(
				'flex flex-wrap items-end gap-x-6 gap-y-4 sm:gap-x-10',
				$spec( 'AREA', '2.000 m²' )
				. elio_html( '<span class="block h-12 w-px bg-forest/20"></span>' )
				. $spec( 'SPACES', '08' )
				. elio_html( '<span class="hidden h-12 w-px bg-forest/20 sm:block"></span>' )
				. elio_p( 'max-w-[180px] text-[15px] leading-[1.45] text-muted italic', 'Designed for little explorers.' )
			)
		)
		. elio_group( 'elio-campus', $items )
		. elio_group( 'grid grid-cols-1 gap-px overflow-hidden rounded-[24px] border border-forest/10 bg-forest/10 min-[420px]:grid-cols-2 md:grid-cols-3 lg:rounded-[28px] xl:grid-cols-6', $glance ),
		'section',
		'campus'
	);
}

/** Interactive: tabs + profile panel (tablet/desktop), swipe carousel (phones). */
function elio_section_educators( $c ) {
	$t     = $c['t'];
	$items = '';
	foreach ( $c['educators'] as $e ) {
		$chips = '';
		foreach ( $e['brings'] as $b ) {
			$chips .= elio_p( 'elio-edu-chip', esc_html( $b ) );
		}
		$items .= elio_group(
			'elio-edu-item',
			elio_image( 'elio-edu-img', $e['src'], $e['name'] )
			. elio_p( 'elio-edu-n', esc_html( $e['n'] ) )
			. elio_p( 'elio-edu-role', esc_html( $e['role'] ) )
			. elio_h( 'elio-edu-name', esc_html( $e['name'] ), 3 )
			. elio_p( 'elio-edu-who', esc_html( $e['who'] ) )
			. elio_p( 'elio-edu-belief', esc_html( $e['belief'] ) )
			. elio_group( 'elio-edu-brings', $chips )
		);
	}

	$chips = '';
	foreach ( $c['teamChips'] as $chip ) {
		$chips .= elio_p( 'rounded-full border border-ivory/40 bg-ivory/16 px-3 py-1.5 text-xs font-medium whitespace-nowrap backdrop-blur-[6px] sm:px-3.5 sm:py-2 sm:text-[13px]', esc_html( $chip ) );
	}

	return elio_group(
		'flex flex-col gap-10 border-t border-forest/12 pt-16 pb-16 md:pt-20 lg:gap-14 lg:pt-28 lg:pb-[104px] ' . ELIO_GUTTER,
		elio_group(
			'grid gap-5 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] lg:items-end lg:gap-16',
			elio_group(
				'flex flex-col gap-4',
				elio_p( 'elio-kicker', 'MEET OUR EDUCATORS' )
				. elio_h( 'text-[34px] leading-[1.08] font-light tracking-[-.025em] text-pretty text-forest md:text-[42px] lg:text-[52px]', esc_html( $t['eduH'] ) )
				. elio_p( 'text-lg font-normal text-gold-text italic lg:text-xl', 'Teachers who make every day brighter.' )
			)
			. elio_p( 'text-[15px] leading-[1.75] text-pretty text-muted lg:pb-1.5 lg:text-base', esc_html( $t['eduP'] ) )
		)
		. elio_group(
			'relative h-[320px] overflow-hidden rounded-[24px] bg-placeholder sm:h-[440px] lg:h-[560px] lg:rounded-[28px]',
			elio_image( ELIO_FILL . ' [&_img]:object-[50%_40%]', elio_img( 'edu/team.jpg' ), $t['teamAlt'] )
			. elio_html( '<div class="pointer-events-none absolute inset-0 bg-[linear-gradient(180deg,rgba(11,44,35,0)_40%,rgba(11,44,35,.75)_100%)] lg:bg-[linear-gradient(180deg,rgba(11,44,35,0)_60%,rgba(11,44,35,.7)_100%)]"></div>' )
			. elio_group(
				'absolute inset-x-5 bottom-5 flex flex-col items-start gap-3 text-ivory md:flex-row md:items-end md:justify-between md:gap-6 lg:inset-x-9 lg:bottom-[30px]',
				elio_group(
					'flex flex-col gap-1.5',
					elio_p( 'text-[11px] font-semibold tracking-[.2em] text-gold-light', esc_html( $t['teamK'] ) )
					. elio_p( 'text-lg font-normal tracking-[-.01em] sm:text-xl lg:text-2xl', esc_html( $t['teamCap'] ) )
				)
				. elio_group( 'flex shrink-0 flex-wrap gap-2 md:justify-end lg:gap-2.5', $chips )
			)
		)
		. elio_group(
			'flex flex-col gap-6 lg:gap-7',
			elio_group(
				'flex items-center gap-4',
				elio_p( 'text-[11px] font-semibold tracking-[.18em] text-gold-label sm:text-xs sm:whitespace-nowrap', esc_html( $t['teamH'] ) )
				. elio_html( '<span class="block h-px flex-1 bg-forest/16"></span>' )
			)
			. elio_group( 'elio-edu', $items )
		),
		'section',
		'educators'
	);
}

/** Shared values · Partnership with parents · Tuition. */
function elio_section_community( $c ) {
	$t = $c['t'];

	$values = '';
	foreach ( $c['values'] as $i => $v ) {
		$icon    = 'heart' === $v['icon']
			? elio_html( '<span class="flex size-[46px] items-center justify-center rounded-full border border-gold-light/55 text-gold-light">' . elio_icon( 'heart', 'size-5' ) . '</span>' )
			: elio_image( 'flex size-[46px] items-center justify-center rounded-full border border-gold-light/55 [&_img]:size-[22px] [&_img]:[filter:brightness(0)_saturate(100%)_invert(86%)_sepia(22%)_saturate(560%)_hue-rotate(354deg)]', elio_img( 'icon-' . $v['icon'] . '.png' ) );
		$values .= elio_group(
			'flex flex-col gap-3.5 lg:px-[26px] lg:py-0.5 ' . ( 0 === $i ? 'lg:pl-0' : 'lg:border-l lg:border-ivory/16' ),
			$icon
			. elio_h( 'text-[17px] leading-[1.3] font-medium text-pretty text-ivory', esc_html( $v['title'] ), 3 )
			. elio_p( 'text-[13px] leading-[1.6] text-pretty text-ivory/80', esc_html( $v['body'] ) )
		);
	}
	$values_block = elio_group(
		'pb-16 lg:pb-[104px] ' . ELIO_GUTTER,
		elio_group(
			'flex flex-col gap-8 rounded-[24px] bg-forest bg-[radial-gradient(circle_at_100%_100%,rgba(201,169,106,.14)_0_150px,transparent_181px),radial-gradient(circle_at_100%_100%,transparent_0_190px,rgba(201,169,106,.32)_190px_191.5px,transparent_192.5px)] bg-no-repeat px-6 py-9 text-ivory sm:p-10 lg:gap-9 lg:rounded-[28px] lg:px-[52px] lg:py-12',
			elio_p( 'elio-kicker is-light tracking-[.2em]', esc_html( $t['valH'] ) )
			. elio_group( 'grid gap-8 sm:grid-cols-2 lg:grid-cols-4 lg:gap-0', $values )
		),
		'section',
		'values'
	);

	$cards = '';
	foreach ( $c['parents'] as $p ) {
		$cards .= elio_group(
			'flex flex-col gap-2.5 rounded-2xl bg-ivory p-5 lg:p-6',
			elio_p( 'text-[11px] font-semibold tracking-[.12em] text-gold-label', esc_html( $p['n'] . ' — ' . $p['en'] ) )
			. elio_h( 'text-lg font-medium', esc_html( $p['title'] ), 3 )
			. elio_p( 'text-[13px] leading-normal text-muted', esc_html( $p['tags'] ) )
		);
	}
	$parents = elio_group(
		'mx-5 grid scroll-mt-4 gap-8 rounded-[24px] bg-cream p-6 sm:p-10 md:mx-10 lg:mx-14 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)] lg:items-center lg:gap-14 lg:p-16',
		elio_group(
			'flex flex-col gap-3.5',
			elio_p( ELIO_LABEL, 'A PARTNERSHIP WITH PARENTS' )
			. elio_h( 'text-[28px] leading-[1.2] font-medium tracking-[-.015em] text-balance lg:text-[34px]', esc_html( $t['parH'] ) )
			. elio_p( 'text-[15px] leading-[1.7] text-muted lg:text-base', esc_html( $t['parP'] ) )
		)
		. elio_group( 'grid gap-3 sm:grid-cols-3 sm:gap-4', $cards )
		. elio_p( 'elio-dots border-t border-forest/12 pt-6 text-[10px] font-semibold tracking-[.14em] text-gold-label sm:text-[11px] lg:col-span-full', 'PARENT–TEACHER CONFERENCE · LEARNING UPDATES · COMMUNITY ACTIVITIES' ),
		'section',
		'parents'
	);

	$rows = '';
	foreach ( $c['fees'] as $f ) {
		$rows .= elio_group(
			'grid grid-cols-[minmax(0,1fr)_auto] items-baseline gap-x-4 gap-y-1.5 border-b border-forest/14 py-5 sm:grid-cols-[minmax(0,1fr)_140px_220px] sm:gap-4 lg:py-[22px]',
			elio_p( 'text-[17px] font-medium lg:text-lg', esc_html( $f['p'] ) )
			. elio_p( 'text-sm text-muted', esc_html( $f['age'] ) )
			. elio_p( 'col-span-2 text-[15px] font-semibold sm:col-span-1 sm:text-right', esc_html( $f['fee'] ) )
		);
	}
	$tuition = elio_group(
		'grid scroll-mt-4 gap-8 py-16 md:py-20 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)] lg:gap-14 lg:py-24 ' . ELIO_GUTTER,
		elio_group(
			'flex flex-col gap-3.5',
			elio_p( ELIO_LABEL, esc_html( $t['feeK'] ) )
			. elio_h( 'text-[28px] leading-[1.2] font-medium tracking-[-.015em] lg:text-[34px]', esc_html( $t['feeH'] ) )
			. elio_p( 'text-[15px] leading-[1.7] text-muted', esc_html( $t['feeP'] ) )
			. elio_p( 'mt-2 self-start text-[13px] font-semibold tracking-[.06em] text-forest [&_a:hover]:text-gold-label', '<a href="#">' . esc_html( $t['feeA'] ) . '</a>' )
		)
		. elio_group( 'flex flex-col', $rows ),
		'section',
		'tuition'
	);

	return $values_block . $parents . $tuition;
}

/** Latest three posts. Polylang limits the query to the page's language. */
function elio_section_news( $c, $lang ) {
	$t    = $c['t'];
	$page = get_page_by_path( 'en' === $lang ? 'news' : 'tin-tuc' );
	$more = $page ? get_permalink( $page ) : home_url( 'en' === $lang ? '/en/news/' : '/tin-tuc/' );

	$query = '<!-- wp:query {"queryId":7,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"className":"elio-news"} -->' . "\n"
		. '<div class="wp-block-query elio-news">'
		. elio_post_cards_template()
		. "\n" . '<!-- wp:query-no-results -->' . "\n"
		. elio_p( 'text-[15px] text-muted', 'en' === $lang ? 'No posts yet.' : 'Chưa có bài viết.' )
		. '<!-- /wp:query-no-results -->'
		. "</div>\n<!-- /wp:query -->\n\n";

	return elio_group(
		'flex flex-col gap-8 border-t border-forest/12 pt-16 pb-16 md:pt-20 lg:gap-12 lg:pt-24 lg:pb-24 ' . ELIO_GUTTER,
		elio_group(
			'flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between',
			elio_group(
				'flex flex-col gap-3.5',
				elio_p( 'elio-kicker', esc_html( $t['newsK'] ) )
				. elio_h( 'text-[30px] leading-[1.1] font-light tracking-[-.02em] text-forest md:text-[38px] lg:text-[44px]', esc_html( $t['newsH'] ) )
			)
			. elio_p( 'shrink-0 text-[13px] font-semibold tracking-[.06em] text-forest [&_a:hover]:text-gold-label', '<a href="' . esc_url( $more ) . '">' . esc_html( $t['newsA'] ) . '</a>' )
		)
		. $query,
		'section',
		'news'
	);
}

/** Post cards used by the home "news" block and the blog templates. */
function elio_post_cards_template() {
	return '<!-- wp:post-template {"className":"elio-cards"} -->' . "\n"
		. '<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/10","className":"elio-card-img"} /-->' . "\n"
		. '<!-- wp:group {"className":"elio-card-body"} -->' . "\n"
		. '<div class="wp-block-group elio-card-body">'
		. '<!-- wp:group {"className":"elio-card-meta"} -->' . "\n"
		. '<div class="wp-block-group elio-card-meta"><!-- wp:post-terms {"term":"category"} /-->' . "\n"
		. '<!-- wp:post-date /--></div>' . "\n"
		. '<!-- /wp:group -->' . "\n"
		. '<!-- wp:post-title {"level":3,"isLink":true,"className":"elio-card-title"} /-->' . "\n"
		. '<!-- wp:post-excerpt {"excerptLength":24,"className":"elio-card-excerpt"} /--></div>' . "\n"
		. '<!-- /wp:group -->' . "\n"
		. '<!-- /wp:post-template -->';
}

function elio_section_tour( $c ) {
	$t     = $c['t'];
	$chips = '';
	foreach ( $c['visit'] as $v ) {
		$chips .= elio_group(
			'flex min-w-0 items-center gap-3 rounded-3xl border border-ivory/22 bg-forest-deep/42 py-2.5 pr-4 pl-2.5 backdrop-blur-[10px] lg:rounded-full',
			elio_html( '<span class="flex size-[38px] shrink-0 items-center justify-center rounded-full bg-gold text-forest">' . elio_icon( $v['icon'], 'size-[18px]' ) . '</span>' )
			. elio_group(
				'flex min-w-0 flex-col gap-[3px]',
				elio_p( 'text-[11px] font-semibold tracking-[.12em] text-gold-light', esc_html( $v['k'] ) )
				. elio_p( 'text-[13px] leading-[1.4] text-balance text-ivory', esc_html( $v['v'] ) )
			)
		);
	}

	return elio_group(
		'relative grid scroll-mt-4 items-center gap-x-16 gap-y-10 overflow-hidden bg-forest pt-16 pb-10 text-ivory md:pt-24 lg:min-h-[860px] lg:grid-cols-[minmax(0,1fr)_520px] lg:gap-y-14 lg:pt-[120px] lg:pb-12 ' . ELIO_GUTTER,
		elio_image( ELIO_FILL . ' [&_img]:object-[28%_50%]', elio_img( 'tour-entrance.jpg' ), $t['tourAlt'] )
		. elio_html( '<div class="pointer-events-none absolute inset-0 bg-[linear-gradient(180deg,rgba(11,44,35,.88)_0,rgba(11,44,35,.6)_45%,rgba(11,44,35,.8)_100%)] lg:bg-[linear-gradient(90deg,rgba(11,44,35,.82)_0,rgba(11,44,35,.5)_32%,rgba(11,44,35,0)_52%,rgba(11,44,35,0)_100%)]"></div>' )
		. elio_image( 'absolute top-6 right-5 size-10 opacity-95 md:right-10 lg:top-11 lg:right-14 lg:size-[52px] [&_img]:h-full [&_img]:w-full', elio_img( 'icon-sun.png' ) )
		. elio_group(
			'relative flex flex-col gap-5 self-start lg:gap-[22px]',
			elio_p( 'elio-kicker is-light', 'BOOK A SCHOOL TOUR' )
			. elio_h( 'text-[34px] leading-[1.08] font-light tracking-[-.025em] text-pretty md:text-[42px] lg:text-[52px]', esc_html( $t['tourH'] ) )
			. elio_p( 'max-w-[460px] text-[15px] leading-[1.7] text-ivory/86 lg:text-[17px]', esc_html( $t['tourP'] ) )
		)
		. "<!-- wp:elio/tour-form /-->\n\n"
		. elio_group( 'relative grid gap-3 lg:col-span-full lg:mt-2 lg:grid-cols-3 lg:gap-3.5', $chips ),
		'section',
		'tour'
	);
}

/** The whole home page for one language. */
function elio_home_markup( $lang ) {
	$c = elio_content( $lang );
	return elio_section_hero( $c )
		. elio_section_why( $c )
		. elio_section_day( $c )
		. elio_section_nutrition( $c )
		. elio_section_approach( $c )
		. elio_section_grow( $c )
		. elio_section_campus( $c )
		. elio_section_educators( $c )
		. elio_section_community( $c )
		. elio_section_news( $c, $lang )
		. elio_section_tour( $c );
}
