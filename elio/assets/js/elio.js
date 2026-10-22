/**
 * ELIO front-end behaviour (no dependencies).
 *
 * The interactive home sections are edited as plain lists of groups in the block editor
 * (.elio-why-item, .elio-day-stop, .elio-campus-item, .elio-edu-item). Here each list is read
 * and rebuilt into the designed UI (tabs, timeline, sliders), mirroring the original React
 * components. Class names are written out in full so Tailwind can find them.
 */
(function () {
	"use strict";

	var LANG = (window.ELIO && window.ELIO.lang) || (document.documentElement.lang.indexOf("en") === 0 ? "en" : "vi");
	var L = {
		vi: { prev: "Trước", next: "Tiếp", swipe: "Vuốt để xem thêm", dayNext: "Tiếp theo: " },
		en: { prev: "Previous", next: "Next", swipe: "Swipe to see more", dayNext: "Next: " },
	}[LANG];

	/* ---------------------------------------------------------------- helpers */

	function esc(s) {
		return String(s).replace(/[&<>"]/g, function (c) {
			return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c];
		});
	}
	function el(html) {
		var t = document.createElement("template");
		t.innerHTML = html.trim();
		return t.content.firstElementChild;
	}
	function swap(node, onCls, offCls, on) {
		var add = (on ? onCls : offCls).split(" ").filter(Boolean);
		var rem = (on ? offCls : onCls).split(" ").filter(Boolean);
		node.classList.remove.apply(node.classList, rem);
		node.classList.add.apply(node.classList, add);
	}
	function txt(root, sel) {
		var n = root.querySelector(sel);
		return n ? n.textContent.trim() : "";
	}
	function pad(n) {
		return String(n).padStart(2, "0");
	}
	function imgOf(root, sel) {
		var img = root.querySelector(sel + " img") || root.querySelector("img");
		if (!img) return { src: "", alt: "", pos: "50% 50%" };
		return { src: img.currentSrc || img.src, alt: img.alt || "", pos: getComputedStyle(img).objectPosition || "50% 50%" };
	}
	function items(root, cls) {
		return Array.prototype.filter.call(root.children, function (c) {
			return c.classList.contains(cls);
		});
	}
	var canHover = window.matchMedia("(hover: hover)");

	var ARROW = {
		outline: "border-[1.5px] border-forest/25 text-forest hover:border-forest hover:bg-cream",
		solid: "bg-forest text-ivory hover:bg-forest-hover",
		"outline-light": "border border-ivory/55 text-ivory hover:bg-ivory/15",
		"solid-light": "bg-ivory text-forest hover:bg-gold-light",
	};
	function arrow(dir, tone, size) {
		return (
			'<button type="button" data-dir="' + dir + '" aria-label="' + esc(dir === "prev" ? L.prev : L.next) + '" style="width:' + size + "px;height:" + size + 'px" class="flex shrink-0 cursor-pointer items-center justify-center rounded-full text-lg transition-colors ' + ARROW[tone] + '">' + (dir === "prev" ? "←" : "→") + "</button>"
		);
	}
	var HAND = '<svg aria-hidden="true" class="size-[18px] text-gold-label" fill="currentColor" viewBox="0 0 256 256"><path d="M216,140v36c0,25.59-8.49,42.16-9.31,43.73a8,8,0,0,1-14.14-7.48c.07-.13,7.45-14.65,7.45-36.25V140a12,12,0,0,0-24,0v4a8,8,0,0,1-16,0V124a12,12,0,0,0-24,0v12a8,8,0,0,1-16,0V68a12,12,0,0,0-24,0V176a8,8,0,0,1-14.69,4.38l-18.68-28.62c-.08-.12-.16-.24-.23-.37A12,12,0,0,0,41.3,164.27l29.36,49.73a8,8,0,0,1-13.78,8.14L27.53,172.4a28,28,0,0,1,48.4-28.13L80,150.5V68a28,28,0,0,1,56,0V99.33a28,28,0,0,1,38.08,16.86A28,28,0,0,1,216,140ZM240,48a8,8,0,0,0-2.34-5.66l-24-24a8,8,0,0,0-11.32,11.32L212.69,40H184a8,8,0,0,0,0,16h28.69L202.34,66.34a8,8,0,0,0,11.32,11.32l24-24A8,8,0,0,0,240,48Z"/></svg>';

	/** "01 / 08 · ✋ Swipe to see more" row above a swipe strip. */
	function swipeHint(extra) {
		return el(
			'<div class="flex items-center justify-between text-xs text-muted ' + (extra || "") + '"><span data-count class="font-semibold text-gold-label tabular-nums"></span><span class="flex items-center gap-1.5"><span data-hand class="motion-safe:animate-nudge">' + HAND + "</span>" + esc(L.swipe) + "</span></div>"
		);
	}

	/**
	 * A sideways-scrolling strip: keeps the active item centred (without moving the page),
	 * fades whichever edge still hides items, and stops the swipe hint once the user scrolls.
	 */
	function swipeStrip(strip, hint) {
		function edges() {
			var left = strip.scrollLeft > 4;
			var right = strip.scrollLeft + strip.clientWidth < strip.scrollWidth - 4;
			var m = "linear-gradient(to right, " + (left ? "transparent 0, #000 40px" : "#000 0") + ", " + (right ? "#000 calc(100% - 56px), transparent 100%" : "#000 100%") + ")";
			strip.style.maskImage = m;
			strip.style.webkitMaskImage = m;
		}
		strip.addEventListener("scroll", function () {
			edges();
			var h = hint && hint.querySelector("[data-hand]");
			if (h) h.classList.remove("motion-safe:animate-nudge");
		});
		window.addEventListener("resize", edges);
		edges();
		return {
			centre: function (item) {
				if (!item || strip.scrollWidth <= strip.clientWidth) return;
				strip.scrollTo({ left: item.offsetLeft - strip.clientWidth / 2 + item.offsetWidth / 2, behavior: "smooth" });
			},
		};
	}

	/* ----------------------------------------------------------------- header */

	/** The menu button + panel pair used by the header and the floating nav. Returns setMenu(open). */
	function menu(root) {
		var btn = root.querySelector("[data-elio-menu]");
		var panel = root.querySelector("[data-elio-menu-panel]");
		function setMenu(open) {
			if (!btn) return;
			panel.hidden = !open;
			btn.setAttribute("aria-expanded", String(open));
			btn.setAttribute("aria-label", btn.getAttribute(open ? "data-label-close" : "data-label-open"));
		}
		if (btn)
			btn.addEventListener("click", function () {
				setMenu(panel.hidden);
			});
		if (panel)
			panel.addEventListener("click", function (e) {
				if (e.target.closest("a")) setMenu(false);
			});
		document.addEventListener("keydown", function (e) {
			if (e.key === "Escape") setMenu(false);
		});
		return setMenu;
	}

	function header(root) {
		var lang = root.querySelector("[data-elio-lang]");
		var langBtn = lang && lang.querySelector("button");
		var langPop = lang && lang.querySelector("div[hidden], div:last-child");
		menu(root);

		function setLang(open) {
			if (!lang) return;
			langPop.hidden = !open;
			langBtn.setAttribute("aria-expanded", String(open));
		}
		if (langBtn)
			langBtn.addEventListener("click", function () {
				setLang(langPop.hidden);
			});
		document.addEventListener("pointerdown", function (e) {
			if (lang && !lang.contains(e.target)) setLang(false);
		});
		document.addEventListener("keydown", function (e) {
			if (e.key === "Escape") setLang(false);
		});
	}

	/* ------------------------------------------------------------ floating nav */

	/**
	 * A round button that appears once the header has scrolled out of view and leaves when the
	 * header is back. Its menu opens on click, and also on hover where there is a mouse.
	 * Links to sections of this page are marked aria-current while that section is on screen.
	 */
	function bubble(root) {
		var head = document.querySelector("[data-elio-header]");
		if (!head || !window.IntersectionObserver) return;
		var btn = root.querySelector("[data-elio-menu]");
		var panel = root.querySelector("[data-elio-menu-panel]");

		if (window.matchMedia("(hover: hover)").matches) {
			var hovering = false;
			var timer;
			// Registered before menu()'s toggle: a click on a menu that hovering already opened keeps it open.
			btn.addEventListener("click", function (e) {
				if (hovering && !panel.hidden) e.stopImmediatePropagation();
			});
			root.addEventListener("mouseenter", function () {
				hovering = true;
				clearTimeout(timer);
				setMenu(true);
			});
			// The delay lets the pointer cross the gap between the button and the menu.
			root.addEventListener("mouseleave", function () {
				hovering = false;
				timer = setTimeout(function () {
					setMenu(false);
				}, 250);
			});
		}
		var setMenu = menu(root);

		new IntersectionObserver(function (entries) {
			var e = entries[entries.length - 1];
			var show = !e.isIntersecting && e.boundingClientRect.top < 0;
			root.classList.toggle("is-shown", show);
			if (!show) setMenu(false);
		}).observe(head);

		document.addEventListener("pointerdown", function (e) {
			if (!root.contains(e.target)) setMenu(false);
		});

		var links = Array.prototype.filter.call(root.querySelectorAll("nav a"), function (a) {
			return a.hash && a.pathname === location.pathname && document.getElementById(a.hash.slice(1));
		});
		if (!links.length) return;
		var ticking = false;
		function spy() {
			ticking = false;
			var line = window.innerHeight * 0.35;
			// The section starting closest above the line wins, whatever order the menu is in.
			var current = "";
			var best = -Infinity;
			links.forEach(function (a) {
				var top = document.getElementById(a.hash.slice(1)).getBoundingClientRect().top;
				if (top <= line && top > best) {
					best = top;
					current = a.hash;
				}
			});
			links.forEach(function (a) {
				if (a.hash === current) a.setAttribute("aria-current", "location");
				else a.removeAttribute("aria-current");
			});
		}
		window.addEventListener(
			"scroll",
			function () {
				if (!ticking) {
					ticking = true;
					requestAnimationFrame(spy);
				}
			},
			{ passive: true }
		);
		spy();
	}

	/* -------------------------------------------------------------- why ELIO */

	function why(root) {
		var data = items(root, "elio-why-item").map(function (it) {
			return { icon: imgOf(it, ".elio-why-icon").src, n: txt(it, ".elio-why-n"), en: txt(it, ".elio-why-en"), title: txt(it, ".elio-why-title"), body: txt(it, ".elio-why-body"), key: txt(it, ".elio-why-key") };
		});
		if (!data.length) return;
		var active = 0;
		var collapsed = false;
		var picked = false;

		var ui = el('<div class="grid gap-6 lg:grid-cols-[400px_minmax(0,1fr)] lg:gap-10"><div class="flex flex-col gap-1.5" data-list></div></div>');
		var list = ui.querySelector("[data-list]");
		var rows = data.map(function (p, i) {
			var row = el(
				'<div class="scroll-mt-4 rounded-xl border transition-colors">' +
					'<button type="button" aria-controls="why-panel-' + i + '" class="grid w-full cursor-pointer grid-cols-[32px_minmax(0,1fr)_20px] items-center gap-3 bg-transparent px-4 py-3.5 text-left text-forest sm:grid-cols-[40px_minmax(0,1fr)_20px] sm:gap-3.5 sm:px-[18px] sm:py-4 lg:grid-cols-[40px_minmax(0,1fr)]">' +
					'<span class="text-[13px] font-semibold text-gold-label">' + esc(p.n) + "</span>" +
					'<span class="flex flex-col gap-0.5"><span class="text-[10px] tracking-[.12em] text-muted sm:text-[11px]">' + esc(p.en) + '</span><span data-title class="text-[15px]">' + esc(p.title) + "</span></span>" +
					'<span aria-hidden="true" data-chev class="text-gold-label transition-transform duration-300 lg:hidden">▾</span>' +
					"</button>" +
					'<div id="why-panel-' + i + '" data-acc class="grid transition-[grid-template-rows] duration-300 ease-out lg:hidden"><div class="overflow-hidden"><div class="flex flex-col gap-3 px-4 pb-5 sm:px-[18px] sm:pb-6">' +
					'<p class="m-0 text-[15px] leading-[1.7] text-muted">' + esc(p.body) + "</p>" +
					'<div class="border-t border-forest/14 pt-3 text-[15px] font-medium italic">' + esc(p.key) + "</div>" +
					"</div></div></div></div>"
			);
			row.querySelector("button").addEventListener("click", function () {
				picked = true;
				if (i === active) collapsed = !collapsed;
				else {
					active = i;
					collapsed = false;
				}
				render();
			});
			list.appendChild(row);
			return row;
		});
		var panel = el(
			'<div class="hidden flex-col gap-5 rounded-[20px] bg-cream bg-[radial-gradient(circle_at_100%_100%,transparent_0_150px,rgba(201,169,106,.45)_150px_151.5px,transparent_152.5px)] bg-no-repeat p-12 lg:flex">' +
				'<img data-icon alt="" width="64" height="64" class="size-16">' +
				'<div data-label class="text-xs font-semibold tracking-[.16em] text-gold-label"></div>' +
				'<div data-title class="text-[32px] leading-[1.2] font-medium tracking-[-.01em]"></div>' +
				'<p data-body class="m-0 max-w-[620px] text-[17px] leading-[1.7] text-muted"></p>' +
				'<div data-key class="mt-2 border-t border-forest/14 pt-5 text-lg font-medium italic"></div></div>'
		);
		ui.appendChild(panel);

		function render() {
			rows.forEach(function (row, i) {
				var on = i === active;
				var open = on && !collapsed;
				// Desktop highlight follows the selected tab; accordion highlight follows the open row.
				swap(row, "lg:border-forest/25 lg:bg-cream", "lg:border-transparent lg:hover:bg-cream/60", on);
				swap(row, "border-forest/25 bg-cream", "border-transparent hover:bg-cream/60", open);
				row.querySelector("button").setAttribute("aria-expanded", String(open));
				swap(row.querySelector("[data-title]"), "font-semibold", "font-medium", on);
				swap(row.querySelector("[data-chev]"), "rotate-180", "", open);
				swap(row.querySelector("[data-acc]"), "grid-rows-[1fr]", "grid-rows-[0fr]", open);
			});
			var p = data[active];
			panel.querySelector("[data-icon]").src = p.icon;
			panel.querySelector("[data-label]").textContent = p.n + " — " + p.en;
			panel.querySelector("[data-title]").textContent = p.title;
			panel.querySelector("[data-body]").textContent = p.body;
			panel.querySelector("[data-key]").textContent = p.key;
			// When a row above the tapped one collapses, the tapped row can slide above the screen.
			if (picked) {
				var row = rows[active];
				setTimeout(function () {
					if (window.matchMedia("(max-width: 1023px)").matches && row.getBoundingClientRect().top < 0) row.scrollIntoView({ behavior: "smooth", block: "start" });
				}, 320);
			}
		}
		render();
		root.appendChild(ui);
		root.classList.add("is-ready");
	}

	/* --------------------------------------------------------- a day at ELIO */

	function day(root) {
		var data = items(root, "elio-day-stop").map(function (it) {
			var part = it.querySelector(".elio-day-part");
			var tint = part ? (part.className.match(/\bbg-(butter|sage-tint|lilac|peach|wheat|cream)\b/) || ["bg-butter"])[0] : "bg-butter";
			var im = imgOf(it, ".elio-day-img");
			return { src: im.src, pos: im.pos, t: txt(it, ".elio-day-time"), part: txt(it, ".elio-day-part"), tint: tint, name: txt(it, ".elio-day-name"), body: txt(it, ".elio-day-body") };
		});
		var n = data.length;
		if (!n) return;
		var active = 0;

		var ui = el(
			'<div class="flex flex-col gap-8 md:gap-11"><div class="grid gap-5 lg:grid-cols-[minmax(0,1.45fr)_minmax(0,1fr)] lg:gap-7">' +
				'<div class="relative h-[300px] overflow-hidden rounded-[24px] bg-placeholder shadow-[0_18px_40px_rgba(15,58,46,.14)] sm:h-[420px] lg:h-[500px] lg:rounded-[36px]">' +
				'<img data-photo alt="" class="absolute inset-0 h-full w-full object-cover">' +
				'<div class="absolute top-4 left-4 flex items-center gap-3 rounded-full bg-ivory/95 py-2 pr-5 pl-2 shadow-[0_8px_22px_rgba(15,58,46,.16)] backdrop-blur-[6px] lg:top-6 lg:left-6">' +
				'<span class="flex size-9 items-center justify-center rounded-full bg-forest lg:size-10"><span class="size-2.5 rounded-full bg-gold"></span></span>' +
				'<span class="flex flex-col leading-[1.1]"><span data-time class="text-lg font-semibold tracking-[-.01em] text-forest tabular-nums lg:text-[22px]"></span><span data-part class="mt-[3px] text-[10px] font-semibold tracking-[.16em] text-gold-label"></span></span></div>' +
				'<div data-count class="absolute right-4 bottom-4 rounded-full bg-ivory/94 px-4 py-2 text-[13px] font-semibold text-forest tabular-nums lg:right-[22px] lg:bottom-[22px]"></div></div>' +
				'<div class="flex flex-col gap-4 rounded-[24px] bg-ivory p-6 sm:p-8 lg:gap-[18px] lg:rounded-[36px] lg:p-10"><div class="grid" data-cards></div>' +
				'<div class="mt-auto flex items-center gap-3 pt-2">' + arrow("prev", "outline", 52) + arrow("next", "solid", 52) + '<span class="ml-1.5 grid text-[13px] text-muted" data-nexts></span></div></div></div>' +
				'<div data-rail class="no-scrollbar hidden overflow-x-auto md:-mx-10 md:block md:px-10 lg:mx-0 lg:overflow-visible lg:px-0"><div class="relative grid gap-2 pt-1 lg:min-w-0" data-stops><div class="absolute top-[52px] right-[7%] left-[7%] border-t-2 border-dashed border-gold-label/45"></div></div></div></div>'
		);
		var cards = ui.querySelector("[data-cards]");
		var nexts = ui.querySelector("[data-nexts]");
		var stops = ui.querySelector("[data-stops]");
		var rail = ui.querySelector("[data-rail]");
		stops.style.gridTemplateColumns = "repeat(" + n + ", minmax(0, 1fr))";
		stops.style.minWidth = n * 100 + "px";

		// Every stop sits in the same grid cell and only the active one is visible, so the card
		// is always as tall as the longest stop and never jumps when switching.
		var cardEls = data.map(function (d, i) {
			var nx = data[(i + 1) % n];
			nexts.appendChild(el('<span class="col-start-1 row-start-1">' + esc(L.dayNext + nx.t + " · " + nx.name) + "</span>"));
			return cards.appendChild(
				el(
					'<div class="col-start-1 row-start-1 flex flex-col gap-4 lg:gap-[18px]">' +
						'<span class="self-start rounded-full px-3.5 py-[7px] text-[11px] font-semibold tracking-[.16em] text-forest ' + d.tint + '">' + esc(d.part) + "</span>" +
						'<div class="text-[26px] leading-[1.15] font-semibold tracking-[-.015em] text-pretty text-forest lg:text-[34px]">' + esc(d.name) + "</div>" +
						'<p class="m-0 text-[15px] leading-[1.7] text-pretty text-muted lg:text-[17px]">' + esc(d.body) + "</p></div>"
				)
			);
		});
		var stopEls = data.map(function (d, i) {
			var b = el(
				'<button type="button" class="relative flex cursor-pointer flex-col items-center gap-2.5 bg-transparent p-0 text-forest transition-transform hover:-translate-y-1">' +
					'<span class="flex size-24 items-center justify-center"><span data-ring class="relative block overflow-hidden rounded-full bg-placeholder transition-all"><img alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" src="' + esc(d.src) + '" style="object-position:' + esc(d.pos) + '"></span></span>' +
					'<span data-t class="text-[15px] font-bold tabular-nums">' + esc(d.t) + "</span>" +
					'<span data-n class="max-w-[140px] text-center text-[13px] leading-[1.35]">' + esc(d.name) + "</span></button>"
			);
			b.addEventListener("click", function () {
				go(i);
			});
			return stops.appendChild(b);
		});
		var photo = ui.querySelector("[data-photo]");
		ui.querySelector('[data-dir="prev"]').addEventListener("click", function () {
			go((active + n - 1) % n);
		});
		ui.querySelector('[data-dir="next"]').addEventListener("click", function () {
			go((active + 1) % n);
		});

		function go(i) {
			active = i;
			var d = data[i];
			photo.src = d.src;
			photo.alt = d.name;
			photo.style.objectPosition = d.pos;
			ui.querySelector("[data-time]").textContent = d.t;
			ui.querySelector("[data-part]").textContent = d.part;
			ui.querySelector("[data-count]").textContent = pad(i + 1) + " / " + pad(n);
			cardEls.forEach(function (c, j) {
				swap(c, "visible", "invisible", j === i);
				c.setAttribute("aria-hidden", String(j !== i));
				swap(nexts.children[j], "visible", "invisible", j === i);
				nexts.children[j].setAttribute("aria-hidden", String(j !== i));
			});
			stopEls.forEach(function (b, j) {
				var on = j === i;
				b.setAttribute("aria-pressed", String(on));
				swap(b.querySelector("[data-ring]"), "size-24 border-4 border-gold shadow-[0_10px_24px_rgba(15,58,46,.22)]", "size-[68px] border-3 border-ivory shadow-[0_4px_10px_rgba(15,58,46,.10)]", on);
				swap(b.querySelector("[data-t]"), "text-gold-label", "text-forest", on);
				swap(b.querySelector("[data-n]"), "font-semibold text-forest", "font-normal text-muted", on);
			});
			// On narrow screens the rail scrolls sideways; keep the active stop in view without moving the page.
			if (rail.scrollWidth > rail.clientWidth) rail.scrollTo({ left: stopEls[i].offsetLeft - rail.clientWidth / 2 + stopEls[i].offsetWidth / 2, behavior: "smooth" });
		}
		go(0);
		root.appendChild(ui);
		root.classList.add("is-ready");
	}

	/* ----------------------------------------------------------------- campus */

	function campus(root) {
		var data = items(root, "elio-campus-item").map(function (it) {
			var im = imgOf(it, ".elio-campus-img");
			return { src: im.src, n: txt(it, ".elio-campus-n"), en: txt(it, ".elio-campus-en"), title: txt(it, ".elio-campus-title"), body: txt(it, ".elio-campus-body") };
		});
		var n = data.length;
		if (!n) return;
		var active = 0;

		var ui = el(
			'<div class="grid gap-4 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)] lg:gap-10">' +
				'<div class="relative h-[400px] overflow-hidden rounded-[min(320px,calc(50vw_-_20px))_min(320px,calc(50vw_-_20px))_24px_24px] bg-placeholder shadow-[0_24px_50px_rgba(15,58,46,.14)] sm:h-[500px] lg:h-[640px] lg:rounded-[320px_320px_28px_28px]">' +
				'<img data-photo alt="" class="absolute inset-0 h-full w-full object-cover">' +
				'<div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(15,58,46,0)_45%,rgba(11,44,35,.8)_100%)] lg:bg-[linear-gradient(180deg,rgba(15,58,46,0)_55%,rgba(11,44,35,.75)_100%)]"></div>' +
				'<div class="absolute inset-x-5 bottom-5 flex flex-col items-start gap-4 text-ivory sm:flex-row sm:items-end sm:justify-between sm:gap-6 lg:inset-x-9 lg:bottom-8">' +
				'<div class="flex max-w-[440px] flex-col gap-2"><span data-label class="text-[11px] font-semibold tracking-[.2em] text-gold-light"></span><span data-title class="text-2xl leading-[1.15] font-medium tracking-[-.015em] lg:text-[30px]"></span><span data-body class="text-sm leading-[1.55] text-pretty text-ivory/88 lg:text-[15px]"></span></div>' +
				'<div class="flex shrink-0 gap-2.5">' + arrow("prev", "outline-light", 48) + arrow("next", "solid-light", 48) + "</div></div></div>" +
				'<div class="flex min-w-0 flex-col gap-2 lg:hidden" data-phone><div data-strip class="no-scrollbar -mx-5 flex snap-x scroll-px-5 gap-3 overflow-x-auto px-5 pt-2 pb-2 md:-mx-10 md:scroll-px-10 md:px-10"></div></div>' +
				'<div class="hidden flex-col justify-between gap-1 lg:flex" data-list></div></div>'
		);
		var phone = ui.querySelector("[data-phone]");
		var hint = swipeHint();
		phone.insertBefore(hint, phone.firstChild);
		var stripEl = ui.querySelector("[data-strip]");
		var strip = swipeStrip(stripEl, hint);
		var list = ui.querySelector("[data-list]");

		var thumbs = data.map(function (cp, i) {
			var b = el(
				'<button type="button" aria-label="' + esc(cp.title) + '" class="flex w-[118px] shrink-0 cursor-pointer snap-start flex-col gap-2 bg-transparent p-0 text-left text-forest sm:w-[136px]">' +
					'<span data-ph class="relative block h-[84px] w-full overflow-hidden rounded-[42px_42px_10px_10px] bg-placeholder transition-all sm:h-[96px] sm:rounded-[48px_48px_12px_12px]"><img alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" src="' + esc(cp.src) + '"></span>' +
					'<span class="flex items-baseline gap-1.5 px-0.5"><span data-n class="text-xs tabular-nums">' + esc(cp.n) + '</span><span data-t class="line-clamp-2 text-[13px] leading-snug">' + esc(cp.title) + "</span></span></button>"
			);
			b.addEventListener("click", function () {
				go(i);
			});
			return stripEl.appendChild(b);
		});
		stripEl.appendChild(el('<span aria-hidden="true" class="w-2 shrink-0 snap-end md:w-7"></span>'));

		var rows = data.map(function (cp, i) {
			var b = el(
				'<button type="button" class="grid cursor-pointer grid-cols-[36px_minmax(0,1fr)_56px] items-center gap-3 rounded-[20px] bg-transparent py-2.5 pr-3 pl-3.5 text-left text-forest transition-colors sm:grid-cols-[44px_minmax(0,1fr)_64px] sm:gap-4 sm:pl-[18px]">' +
					'<span data-n class="text-xl font-light tabular-nums sm:text-[22px]">' + esc(cp.n) + "</span>" +
					'<span class="flex min-w-0 flex-col gap-[3px]"><span data-t class="text-base sm:text-[17px]">' + esc(cp.title) + '</span><span class="text-[10px] font-semibold tracking-[.18em] text-gold-label">' + esc(cp.en) + "</span></span>" +
					'<span data-ph class="relative h-[46px] w-14 overflow-hidden rounded-[26px_26px_8px_8px] bg-placeholder transition-opacity sm:h-[52px] sm:w-16"><img alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" src="' + esc(cp.src) + '"></span></button>'
			);
			b.addEventListener("click", function () {
				go(i);
			});
			b.addEventListener("mouseenter", function () {
				if (canHover.matches) go(i);
			});
			return list.appendChild(b);
		});
		var photo = ui.querySelector("[data-photo]");
		ui.querySelector('[data-dir="prev"]').addEventListener("click", function () {
			go((active + n - 1) % n);
		});
		ui.querySelector('[data-dir="next"]').addEventListener("click", function () {
			go((active + 1) % n);
		});

		function go(i, first) {
			if (i === active && !first) return;
			active = i;
			var cp = data[i];
			photo.src = cp.src;
			photo.alt = cp.title;
			ui.querySelector("[data-label]").textContent = cp.n + " — " + cp.en;
			ui.querySelector("[data-title]").textContent = cp.title;
			ui.querySelector("[data-body]").textContent = cp.body;
			hint.querySelector("[data-count]").textContent = pad(i + 1) + " / " + pad(n);
			thumbs.forEach(function (b, j) {
				var on = j === i;
				b.setAttribute("aria-pressed", String(on));
				swap(b.querySelector("[data-ph]"), "opacity-100 ring-3 ring-gold ring-offset-2 ring-offset-ivory", "opacity-60", on);
				swap(b.querySelector("[data-n]"), "font-semibold text-gold-text", "text-forest/45", on);
				swap(b.querySelector("[data-t]"), "font-semibold", "font-normal text-muted", on);
			});
			rows.forEach(function (b, j) {
				var on = j === i;
				b.setAttribute("aria-pressed", String(on));
				swap(b, "bg-paper", "hover:bg-cream", on);
				swap(b.querySelector("[data-n]"), "text-gold-text", "text-forest/40", on);
				swap(b.querySelector("[data-t]"), "font-semibold", "font-normal", on);
				swap(b.querySelector("[data-ph]"), "opacity-100", "opacity-55", on);
			});
			if (!first) strip.centre(thumbs[i]);
		}
		go(0, true);
		root.appendChild(ui);
		root.classList.add("is-ready");
	}

	/* -------------------------------------------------------------- educators */

	function educators(root) {
		var data = items(root, "elio-edu-item").map(function (it) {
			var im = imgOf(it, ".elio-edu-img");
			var he = it.classList.contains("is-he");
			return {
				src: im.src, n: txt(it, ".elio-edu-n"), role: txt(it, ".elio-edu-role"), name: txt(it, ".elio-edu-name"),
				who: txt(it, ".elio-edu-who"), belief: txt(it, ".elio-edu-belief"),
				brings: Array.prototype.map.call(it.querySelectorAll(".elio-edu-chip"), function (c) { return c.textContent.trim(); }).filter(Boolean),
				keys: he ? ["WHO HE IS", "WHAT HE BELIEVES", "HE BRINGS"] : ["WHO SHE IS", "WHAT SHE BELIEVES", "SHE BRINGS"],
			};
		});
		var n = data.length;
		if (!n) return;
		var active = 0;

		function rowHtml(label, inner) {
			return '<div class="grid gap-2 border-t border-forest/14 pt-5 sm:grid-cols-[132px_minmax(0,1fr)] sm:gap-5 lg:pt-[22px]"><span class="pt-1 text-[11px] font-semibold tracking-[.16em] text-muted">' + esc(label) + "</span>" + inner + "</div>";
		}
		function profile(e) {
			return (
				'<div class="flex flex-col gap-6 lg:gap-7"><div class="flex flex-col gap-2.5"><span class="text-[11px] font-semibold tracking-[.18em] text-gold-label sm:text-xs">' + esc(e.n + " — " + e.role) + '</span><span class="text-[30px] leading-[1.1] font-normal tracking-[-.02em] text-forest lg:text-[40px]">' + esc(e.name) + "</span></div>" +
				rowHtml(e.keys[0], '<span class="text-[15px] leading-[1.7] text-pretty text-body lg:text-base">' + esc(e.who) + "</span>") +
				rowHtml(e.keys[1], '<span class="text-xl leading-[1.4] font-light text-pretty text-forest italic lg:text-2xl">' + esc(e.belief) + "</span>") +
				rowHtml(e.keys[2], '<div class="flex flex-wrap gap-2">' + e.brings.map(function (b) { return '<span class="rounded-full bg-sage-pale px-3.5 py-2 text-[13px] font-medium text-forest">' + esc(b) + "</span>"; }).join("") + "</div>") +
				"</div>"
			);
		}

		var ui = el('<div class="flex flex-col gap-6 lg:gap-7"></div>');

		// Phones: the profile cards themselves are a swipe carousel.
		var phone = el('<div class="flex min-w-0 flex-col gap-2 md:hidden"></div>');
		var phint = swipeHint();
		var track = el('<div class="no-scrollbar -mx-5 flex snap-x snap-mandatory scroll-px-5 gap-3 overflow-x-auto px-5 pb-2"></div>');
		phone.appendChild(phint);
		phone.appendChild(track);
		data.forEach(function (e, i) {
			var card = el(
				'<article aria-label="' + esc(e.name) + '" class="flex w-[calc(100%-28px)] shrink-0 snap-start flex-col overflow-hidden rounded-[24px] border border-forest/10 bg-paper">' +
					'<div class="relative h-[340px] shrink-0 bg-placeholder"><img alt="' + esc(e.name) + '" loading="lazy" class="absolute inset-0 h-full w-full object-cover object-[50%_20%]" src="' + esc(e.src) + '"></div>' +
					'<div class="flex flex-1 flex-col gap-6 p-6">' + profile(e) +
					'<div class="mt-auto flex items-center gap-2.5 pt-2">' + arrow("prev", "outline", 46) + arrow("next", "solid", 46) + '<span class="ml-1.5 text-[13px] text-muted tabular-nums">' + pad(i + 1) + " / " + pad(n) + "</span></div></div></article>"
			);
			card.querySelector('[data-dir="prev"]').addEventListener("click", function () {
				slide(i - 1);
			});
			card.querySelector('[data-dir="next"]').addEventListener("click", function () {
				slide(i + 1);
			});
			track.appendChild(card);
		});
		track.appendChild(el('<span aria-hidden="true" class="w-2 shrink-0 snap-end"></span>'));
		function step() {
			var a = track.children[0];
			var b = track.children[1];
			return a && b && b.tagName === "ARTICLE" ? b.offsetLeft - a.offsetLeft : track.clientWidth || 1;
		}
		function slide(i) {
			track.scrollTo({ left: ((i + n) % n) * step(), behavior: "smooth" });
		}
		track.addEventListener("scroll", function () {
			var h = phint.querySelector("[data-hand]");
			if (h) h.classList.remove("motion-safe:animate-nudge");
			var i = Math.round(track.scrollLeft / step());
			if (i >= 0 && i < n && i !== active) go(i, true);
		});

		// Tablet: tabs scroll sideways. Desktop: 4-up grid of tabs above the profile panel.
		var tabsWrap = el('<div class="hidden min-w-0 flex-col gap-2 md:flex"></div>');
		var thint = swipeHint("lg:hidden");
		var tabs = el('<div role="tablist" class="no-scrollbar -mx-10 flex snap-x scroll-px-10 gap-3 overflow-x-auto px-10 py-1 lg:mx-0 lg:grid lg:grid-cols-4 lg:overflow-visible lg:px-0 lg:py-0"></div>');
		tabsWrap.appendChild(thint);
		tabsWrap.appendChild(tabs);
		var strip = swipeStrip(tabs, thint);
		var tabEls = data.map(function (e, i) {
			var b = el(
				'<button type="button" role="tab" class="flex h-[76px] w-[250px] shrink-0 cursor-pointer snap-start items-center gap-3.5 rounded-2xl border py-2.5 pr-4 pl-2.5 text-left text-forest hover:border-forest lg:w-auto">' +
					'<span class="relative size-[52px] shrink-0 overflow-hidden rounded-full bg-placeholder"><img alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover object-[50%_18%]" src="' + esc(e.src) + '"></span>' +
					'<span class="flex min-w-0 flex-col gap-[3px]"><span class="truncate text-[10px] font-semibold tracking-[.14em] text-gold-label">' + esc(e.n + " — " + e.role) + '</span><span data-name class="text-[15px]">' + esc(e.name) + "</span></span></button>"
			);
			b.addEventListener("click", function () {
				go(i);
			});
			return tabs.appendChild(b);
		});
		tabs.appendChild(el('<span aria-hidden="true" class="w-2 shrink-0 snap-end md:w-7 lg:hidden"></span>'));

		var panel = el(
			'<div role="tabpanel" class="hidden overflow-hidden rounded-[24px] border border-forest/10 bg-paper md:grid lg:min-h-[640px] lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)] lg:rounded-[28px]">' +
				'<div class="relative h-[480px] bg-placeholder lg:h-full"><img data-photo alt="" class="absolute inset-0 h-full w-full object-cover object-[50%_20%]"></div>' +
				'<div class="flex min-h-0 flex-col gap-6 p-10 lg:gap-7 lg:px-14 lg:pt-14 lg:pb-12"><div class="grid" data-profiles></div>' +
				'<div class="mt-auto flex items-center gap-2.5 pt-2">' + arrow("prev", "outline", 46) + arrow("next", "solid", 46) + '<span data-count class="ml-1.5 text-[13px] text-muted tabular-nums"></span></div></div></div>'
		);
		// All profiles share one grid cell and only the active one is visible, so the card never jumps.
		var profiles = data.map(function (e) {
			return panel.querySelector("[data-profiles]").appendChild(el('<div class="col-start-1 row-start-1">' + profile(e) + "</div>"));
		});
		panel.querySelector('[data-dir="prev"]').addEventListener("click", function () {
			go((active + n - 1) % n);
		});
		panel.querySelector('[data-dir="next"]').addEventListener("click", function () {
			go((active + 1) % n);
		});

		function go(i, fromSwipe) {
			active = i;
			panel.querySelector("[data-photo]").src = data[i].src;
			panel.querySelector("[data-photo]").alt = data[i].name;
			panel.querySelector("[data-count]").textContent = pad(i + 1) + " / " + pad(n);
			phint.querySelector("[data-count]").textContent = pad(i + 1) + " / " + pad(n);
			thint.querySelector("[data-count]").textContent = pad(i + 1) + " / " + pad(n);
			tabEls.forEach(function (b, j) {
				var on = j === i;
				b.setAttribute("aria-selected", String(on));
				swap(b, "border-forest bg-paper", "border-forest/14 bg-transparent", on);
				swap(b.querySelector("[data-name]"), "font-semibold", "font-medium", on);
			});
			profiles.forEach(function (p, j) {
				swap(p, "visible", "invisible", j === i);
				p.setAttribute("aria-hidden", String(j !== i));
			});
			if (!fromSwipe) strip.centre(tabEls[i]);
		}
		go(0, true);

		ui.appendChild(phone);
		ui.appendChild(tabsWrap);
		ui.appendChild(panel);
		root.appendChild(ui);
		root.classList.add("is-ready");
	}

	/* -------------------------------------------------------------- tour form */

	function tour(card) {
		var form = card.querySelector("form");
		var done = card.querySelector("[data-elio-tour-done]");
		var failed = card.querySelector("[data-elio-tour-error]");
		var btn = form.querySelector('button[type="submit"]');
		var btnLabel = btn.textContent;
		var msgs = JSON.parse(card.getAttribute("data-msgs") || "{}");
		var ages = ["18-24m", "2-3y", "4-6y"];
		var f = {};
		["name", "phone", "age", "date", "message"].forEach(function (k) {
			f[k] = form.querySelector('[name="' + k + '"]');
		});

		function today() {
			var d = new Date();
			return d.getFullYear() + "-" + pad(d.getMonth() + 1) + "-" + pad(d.getDate());
		}
		function validate() {
			var e = {};
			var v = function (k) {
				return f[k].value.trim();
			};
			if (!v("name")) e.name = "required";
			if (!f.phone.value) e.phone = "required";
			else if (!/^\d{9,12}$/.test(f.phone.value)) e.phone = "phone";
			if (ages.indexOf(f.age.value) < 0) e.age = "required";
			if (!f.date.value) e.date = "required";
			else if (!/^\d{4}-\d{2}-\d{2}$/.test(f.date.value) || f.date.value < today()) e.date = "date";
			return e;
		}
		function showErrors(e) {
			["name", "phone", "age", "date"].forEach(function (k) {
				var span = form.querySelector("#tour-" + k + "-err");
				var on = !!e[k];
				f[k].setAttribute("aria-invalid", String(on));
				if (on) f[k].setAttribute("aria-describedby", "tour-" + k + "-err");
				else f[k].removeAttribute("aria-describedby");
				span.hidden = !on;
				span.textContent = on ? msgs[e[k]] || msgs.required : "";
			});
		}
		function setDone(isDone) {
			form.hidden = isDone;
			done.hidden = !isDone;
			var a = card.getAttribute("data-form-class");
			var b = card.getAttribute("data-done-class");
			swap(card, b, a, isDone);
		}

		// Phone accepts digits only: anything else typed or pasted (spaces, dots, "+") is dropped.
		f.phone.addEventListener("input", function () {
			var clean = f.phone.value.replace(/\D/g, "").slice(0, 12);
			if (clean !== f.phone.value) f.phone.value = clean;
		});
		// Set lazily, so a cached page never carries a stale minimum.
		f.date.addEventListener("focus", function () {
			f.date.min = today();
		});
		Object.keys(f).forEach(function (k) {
			f[k].addEventListener("input", function () {
				if (f[k].getAttribute("aria-invalid") === "true") {
					f[k].setAttribute("aria-invalid", "false");
					var span = form.querySelector("#tour-" + k + "-err");
					if (span) span.hidden = true;
				}
			});
		});

		form.addEventListener("submit", function (ev) {
			ev.preventDefault();
			var e = validate();
			showErrors(e);
			var first = Object.keys(e)[0];
			if (first) {
				f[first].focus();
				return;
			}
			failed.hidden = true;
			btn.disabled = true;
			btn.textContent = msgs.sending || btnLabel;
			fetch(window.ELIO.tourUrl, {
				method: "POST",
				headers: { "Content-Type": "application/json" },
				body: JSON.stringify({
					name: f.name.value, phone: f.phone.value, age: f.age.value, date: f.date.value, message: f.message.value,
					website: form.querySelector('[name="website"]').value, lang: card.getAttribute("data-lang"),
				}),
			})
				.then(function (r) {
					if (!r.ok) throw new Error(String(r.status));
					form.reset();
					setDone(true);
				})
				.catch(function () {
					failed.hidden = false;
				})
				.then(function () {
					btn.disabled = false;
					btn.textContent = btnLabel;
				});
		});
		card.querySelector("[data-elio-tour-again]").addEventListener("click", function () {
			setDone(false);
		});
	}

	/* ------------------------------------------------------------------- boot */

	// Scrollbar width, used by the CSS that lets the desktop hero run edge to edge.
	function scrollbar() {
		document.documentElement.style.setProperty("--elio-sb", window.innerWidth - document.documentElement.clientWidth + "px");
	}
	scrollbar();
	window.addEventListener("resize", scrollbar);

	function each(sel, fn) {
		Array.prototype.forEach.call(document.querySelectorAll(sel), function (n) {
			try {
				fn(n);
			} catch (err) {
				// One broken section must not stop the others; show its plain list instead.
				if (window.console) console.error("[elio]", sel, err);
				n.classList.add("is-failed");
			}
		});
	}
	each("[data-elio-header]", header);
	each("[data-elio-bubble]", bubble);
	each(".elio-why", why);
	each(".elio-day", day);
	each(".elio-campus", campus);
	each(".elio-edu", educators);
	each("[data-elio-tour]", tour);
})();
