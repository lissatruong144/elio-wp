// Automated run of the pre-deploy test cases against the local WordPress Playground
// (`npm run preview`). Usage: node tests/run.mjs  → tests/out/results.json + screenshots.
import puppeteer from "puppeteer-core";
import fs from "fs";

const BASE = "http://127.0.0.1:9400";
const CHROME = "C:/Program Files/Google/Chrome/Application/chrome.exe";
const OUT = "D:/ELIO/elio-wp/tests/out/";
const MOCK_URL = BASE + "/wp-content/themes/elio/_mock_sheet.php";
const MOCK_LOG = "D:/ELIO/elio-wp/elio/_mock_sheet.log";
const SECRET = "test-secret-123";
fs.mkdirSync(OUT, { recursive: true });
fs.rmSync(MOCK_LOG, { force: true });

const results = [];
const consoleErrors = [];
let saved = 0; // successful bookings in this run (the endpoint allows 5 per IP / 10 min)
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const assert = (c, msg) => {
	if (!c) throw new Error(msg);
};
const ONLY = process.env.ONLY ? process.env.ONLY.split(",") : null;
const want = (id) => !ONLY || ONLY.includes(id);
const anyOf = (...ids) => ids.some(want);
function rec(id, status, note = "") {
	results.push({ id, status, note });
	fs.writeFileSync(OUT + "results.json", JSON.stringify(results, null, 2));
	console.log(status.padEnd(8), id.padEnd(6), note);
}
async function T(id, fn) {
	if (!want(id)) return;
	try {
		const r = await fn();
		if (r === undefined || r === true) rec(id, "Pass");
		else if (typeof r === "string") rec(id, "Pass", r);
		else rec(id, r.status, r.note || "");
	} catch (e) {
		rec(id, "Fail", String(e.message || e).slice(0, 400));
	}
}
const blocked = (id, note) => want(id) && rec(id, "Blocked", note);

const browser = await puppeteer.launch({ executablePath: CHROME, headless: true, args: ["--no-proxy-server"], protocolTimeout: 240000 });

/** A logged-out visitor (Playground logs every new browser in as admin unless this cookie is set). */
async function visitor(w = 1440, h = 900) {
	const ctx = await browser.createBrowserContext();
	const p = await ctx.newPage();
	p.setDefaultTimeout(120000);
	await p.setViewport({ width: w, height: h, isMobile: w < 600, hasTouch: w < 600 });
	await p.setCookie({ name: "playground_auto_login_already_happened", value: "1", url: BASE });
	p.on("console", (m) => m.type() === "error" && consoleErrors.push(p.url() + " :: " + m.text()));
	p.on("pageerror", (e) => consoleErrors.push(p.url() + " :: " + e.message));
	return p;
}
async function go(p, path) {
	try {
		await p.goto(BASE + path, { waitUntil: "load", timeout: 180000 });
	} catch (e) {
		await p.goto(BASE + path, { waitUntil: "load", timeout: 180000 });
	}
	await p.waitForFunction(() => !document.querySelector(".elio-why") || document.querySelector(".elio-why").classList.contains("is-ready"), { timeout: 60000 }).catch(() => {});
	await p.addStyleTag({ content: "html{scroll-behavior:auto!important}" });
	await sleep(300);
}
async function resize(p, w, h = 900) {
	await p.setViewport({ width: w, height: h, isMobile: w < 600, hasTouch: w < 600 });
	await sleep(400);
}
const shot = (p, name) => p.screenshot({ path: OUT + name + ".png" });
const noHScroll = (p) => p.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1);
/** Top of an element relative to the viewport. */
const topOf = (p, sel) => p.evaluate((s) => document.querySelector(s)?.getBoundingClientRect().top ?? null, sel);
const inView = async (p, id) => {
	const t = await topOf(p, "#" + id);
	const atBottom = await p.evaluate(() => Math.ceil(window.scrollY + innerHeight) >= document.documentElement.scrollHeight - 2);
	return t !== null && ((t > -4 && t < 80) || (atBottom && t < innerHeightOf()));
};
const innerHeightOf = () => 900;
const visible = (p, sel) => p.evaluate((s) => { const n = document.querySelector(s); if (!n) return false; const r = n.getBoundingClientRect(); return !n.closest("[hidden]") && r.width > 0 && r.height > 0 && getComputedStyle(n).visibility !== "hidden"; }, sel);
const text = (p, sel) => p.evaluate((s) => document.querySelector(s)?.textContent.trim() ?? null, sel);

/** Admin session (auto-login) with a REST nonce for API calls from the page. */
async function adminPage() {
	const ctx = await browser.createBrowserContext();
	const p = await ctx.newPage();
	p.setDefaultTimeout(180000);
	await p.setViewport({ width: 1440, height: 1000 });
	await p.goto(BASE + "/wp-admin/", { waitUntil: "networkidle2", timeout: 180000 });
	const nonce = await p.evaluate(async () => (await fetch("/wp-admin/admin-ajax.php?action=rest-nonce")).text());
	p.api = (method, path, body) =>
		p.evaluate(
			async (method, path, body, nonce) => {
				const r = await fetch("/wp-json" + path, { method, headers: { "Content-Type": "application/json", "X-WP-Nonce": nonce }, body: body ? JSON.stringify(body) : undefined });
				const t = await r.text();
				let j;
				try { j = JSON.parse(t); } catch { j = t; }
				return { status: r.status, body: j };
			},
			method, path, body, nonce
		);
	return p;
}
async function setSettings(admin, fields) {
	await admin.goto(BASE + "/wp-admin/options-general.php?page=elio", { waitUntil: "networkidle2" });
	for (const [k, v] of Object.entries(fields)) {
		await admin.$eval("#elio-" + k, (el, v) => (el.value = v), v);
	}
	await Promise.all([admin.waitForNavigation({ waitUntil: "load", timeout: 180000 }), admin.click('input[type=submit][value="Lưu cài đặt"]')]);
}
const mockRows = () => (fs.existsSync(MOCK_LOG) ? fs.readFileSync(MOCK_LOG, "utf8").trim().split("\n").filter(Boolean).map((l) => JSON.parse(l)) : []);

/* ------------------------------------------------------------------ fixtures */

const admin = await adminPage();
const homeVi = (await admin.api("GET", "/wp/v2/pages?slug=trang-chu&context=edit&_fields=id,content")).body[0];
const originalHome = homeVi.content.raw;
const postsVi = (await admin.api("GET", "/wp/v2/posts?per_page=20&_fields=id,link,slug")).body;
const created = { posts: [], pages: [], users: [] };

/* ============================================================== HEADER & HERO */

{
	const p = await visitor(1440, 900);
	await go(p, "/");
	await shot(p, "home-1440");

	await T("NV-01", async () => {
		const links = await p.$$eval("header > nav a", (as) => as.map((a) => [a.textContent.trim(), a.getAttribute("href")]));
		assert(links.length === 6, "menu có " + links.length + " mục");
		const bad = [];
		for (const [label, href] of links) {
			const hash = href.split("#")[1];
			if (!hash) continue;
			await p.evaluate(() => scrollTo(0, 0));
			await p.mouse.move(2, 2);
			await p.$eval(`header > nav a[href="${href}"]`, (a) => a.click());
			await sleep(700);
			const t = await topOf(p, "#" + hash);
			if (t === null || t < -4 || t > 90) bad.push(`${label}→#${hash} top=${t}`);
		}
		const news = links.find((l) => !l[1].includes("#"));
		assert(new URL(news[1]).pathname === "/tin-tuc/", "link Tin tức = " + news[1]);
		await go(p, "/tin-tuc/");
		assert(await p.$(".elio-cards > li"), "trang Tin tức trống");
		const en = await visitor(1440, 900);
		await go(en, "/en/");
		const enLogo = await en.$eval("header > a", (a) => new URL(a.href).pathname);
		const enLinks = await en.$$eval("header > nav a", (as) => as.map((a) => a.textContent.trim() + "=" + a.getAttribute("href")));
		await en.close();
		assert(!bad.length, bad.join("; "));
		const enStatus = await fetch(BASE + enLogo, { redirect: "manual", headers: { Cookie: "playground_auto_login_already_happened=1" } }).then((r) => r.status);
		assert(["/en/", "/en/home/"].includes(enLogo) && enStatus === 200, "logo EN → " + enLogo + " (" + enStatus + ")");
		assert(enLinks.some((l) => l.startsWith("News=") && l.includes("/en/news/")), "EN: " + enLinks.join(", "));
		return "6 mục VI cuộn đúng; Tin tức → /tin-tuc/; EN: " + enLinks.map((l) => l.split("=")[0]).join(", ");
	});
	await p.close();
}

{
	const p = await visitor(390, 844);
	await go(p, "/");
	await T("NV-02", async () => {
		await p.click("[data-elio-menu]");
		await sleep(200);
		const st = await p.evaluate(() => { const b = document.querySelector("[data-elio-menu]"); const panel = document.querySelector("[data-elio-menu-panel]"); const [list, x] = b.querySelectorAll("svg"); return { open: !panel.hidden, exp: b.getAttribute("aria-expanded"), label: b.getAttribute("aria-label"), listShown: getComputedStyle(list).display !== "none", xShown: getComputedStyle(x).display !== "none", items: panel.querySelectorAll("nav a").length }; });
		await shot(p, "menu-open-390");
		assert(st.open && st.exp === "true", "menu không mở");
		assert(st.label === "Đóng menu", "nhãn = " + st.label);
		assert(st.xShown && !st.listShown, "icon chưa đổi ☰→✕");
		assert(st.items === 6, "menu mobile có " + st.items + " mục");
		await p.click("[data-elio-menu-panel] nav a");
		await sleep(500);
		assert(await p.evaluate(() => document.querySelector("[data-elio-menu-panel]").hidden), "bấm mục menu không đóng menu");
	});
	await T("NV-03", async () => {
		await p.evaluate(() => scrollTo(0, 0));
		await p.click("[data-elio-menu]");
		await p.keyboard.press("Escape");
		const a = await p.evaluate(() => document.querySelector("[data-elio-menu-panel]").hidden);
		await p.click("[data-elio-menu]");
		await p.click("[data-elio-menu]");
		const b = await p.evaluate(() => document.querySelector("[data-elio-menu-panel]").hidden);
		assert(a && b, `Esc=${a}, bấm ✕=${b}`);
	});
	await T("NV-04", async () => {
		await p.evaluate(() => scrollTo(0, 0));
		await p.click("[data-elio-menu]");
		const r = await p.evaluate(() => { const panel = document.querySelector("[data-elio-menu-panel]"); const a = panel.querySelector(".elio-pill a"); const ra = a.getBoundingClientRect(); return { w: ra.width, pw: panel.getBoundingClientRect().width, href: a.getAttribute("href"), shown: ra.height > 0 }; });
		assert(r.shown && r.w > r.pw - 50, `nút rộng ${r.w}/${r.pw}`);
		await p.click("[data-elio-menu-panel] .elio-pill a");
		await sleep(700);
		assert(await inView(p, "tour"), "không cuộn tới #tour");
		return `nút ${Math.round(r.w)}px / panel ${Math.round(r.pw)}px, cuộn tới #tour`;
	});
	await p.close();
}

{
	const p = await visitor(1440, 900);
	await go(p, "/");
	await T("NV-05", async () => {
		await p.click("[data-elio-lang] button");
		await sleep(200);
		const open = await visible(p, "[data-elio-lang] a[hreflang=en]");
		const check = await p.evaluate(() => !!document.querySelector("[data-elio-lang] a[hreflang=vi] svg"));
		await shot(p, "lang-popup");
		assert(open && check, `popup mở=${open}, ✓ ở VI=${check}`);
		const enUrl = await p.$eval("[data-elio-lang] a[hreflang=en]", (a) => a.href);
		await go(p, new URL(enUrl).pathname);
		const u1 = p.url();
		const label = await text(p, "[data-elio-lang] button span");
		assert(/\/en\/(home\/)?$/.test(u1) && label === "EN", `${u1} nút=${label}`);
		const viUrl = await p.$eval("[data-elio-lang] a[hreflang=vi]", (a) => a.href);
		assert(viUrl === BASE + "/", "link về VI: " + viUrl);
		await go(p, "/");
		// On a post: the switcher goes to that post's translation.
		const post = postsVi.find((x) => !x.link.includes("/en/"));
		await go(p, new URL(post.link).pathname);
		const enHref = await p.$eval("[data-elio-lang] a[hreflang=en]", (a) => a.getAttribute("href"));
		assert(/\/en\/[a-z0-9-]+\/$/.test(new URL(enHref).pathname), "bài VI → link EN = " + enHref);
		return `/ ↔ /en/; bài “${post.slug}” → ${new URL(enHref).pathname}`;
	});
	await T("NV-06", async () => {
		await p.click("[data-elio-lang] button");
		await p.mouse.click(700, 600);
		await sleep(100);
		const a = !(await visible(p, "[data-elio-lang] a[hreflang=en]"));
		await p.click("[data-elio-lang] button");
		await p.keyboard.press("Escape");
		const b = !(await visible(p, "[data-elio-lang] a[hreflang=en]"));
		assert(a && b, `bấm ngoài=${a}, Esc=${b}`);
	});
	await T("NV-07", async () => {
		await go(p, new URL(postsVi.find((x) => !x.link.includes("/en/")).link).pathname);
		assert((await p.$eval("header > a", (a) => a.href)) === BASE + "/", "logo VI sai");
		const post = postsVi.find((x) => !x.link.includes("/en/"));
		await go(p, new URL(post.link).pathname);
		const campusUrl = await p.$eval('header > nav a[href$="#campus"]', (a) => a.href);
		assert(campusUrl === BASE + "/#campus", "link từ bài viết: " + campusUrl);
		await p.goto(campusUrl, { waitUntil: "load", timeout: 180000 });
		await sleep(1500);
		assert(await inView(p, "campus"), "không cuộn tới #campus");
	});
	await T("NV-09", async () => {
		const bad = [];
		for (const w of [1440, 1280, 1024, 768, 640, 440, 390, 320]) {
			await resize(p, w);
			await go(p, "/");
			const r = await p.evaluate(() => ({ h: document.querySelector("header").offsetHeight, scroll: document.documentElement.scrollWidth - innerWidth }));
			if (r.scroll > 1 || r.h > 110) bad.push(`${w}px: header ${r.h}px, tràn ${r.scroll}px`);
		}
		assert(!bad.length, bad.join("; "));
		return "1440→320px: header 1 hàng, không tràn";
	});
	await p.close();
}

await T("NV-08", async () => {
	const menus = (await admin.api("GET", "/wp/v2/menus?_fields=id,name")).body;
	const vi = menus.find((m) => m.name.includes("(VI)"));
	const items = (await admin.api("GET", `/wp/v2/menu-items?menus=${vi.id}&_fields=id,title,menu_order&per_page=50`)).body.sort((a, b) => a.menu_order - b.menu_order);
	const first = items[0];
	const old = first.title.rendered;
	await admin.api("POST", "/wp/v2/menu-items/" + first.id, { title: "Giới thiệu TEST" });
	const p = await visitor();
	await go(p, "/");
	const viNav = await p.$$eval("header > nav a", (as) => as.map((a) => a.textContent.trim()));
	await go(p, "/en/");
	const enNav = await p.$$eval("header > nav a", (as) => as.map((a) => a.textContent.trim()));
	await p.close();
	await admin.api("POST", "/wp/v2/menu-items/" + first.id, { title: old });
	assert(viNav[0] === "Giới thiệu TEST", "VI: " + viNav[0]);
	assert(enNav[0] === "About ELIO", "EN: " + enNav[0]);
	return "VI đổi thành “Giới thiệu TEST”, EN giữ “About ELIO” (đã trả lại tên cũ)";
});

for (const w of [390, 440]) {
	const p = await visitor(w, w === 440 ? 956 : 844);
	await go(p, "/");
	await shot(p, "hero-" + w);
	await T(w === 390 ? "HE-01" : "HE-01b", async () => {
		const r = await p.evaluate(() => {
			const s = document.getElementById("top");
			const rs = s.getBoundingClientRect();
			const fig = s.querySelector("figure");
			const rf = fig.getBoundingClientRect();
			const copy = s.children[1].getBoundingClientRect();
			const btns = [...s.querySelectorAll(".elio-pill a")].map((a) => a.getBoundingClientRect().width);
			return { left: rs.left, right: innerWidth - rs.right, radius: parseFloat(getComputedStyle(s).borderTopLeftRadius), ratio: rf.width / rf.height, overlap: copy.top < rf.bottom - 1, btns, copyW: copy.width };
		});
		assert(Math.abs(r.left - 20) < 1 && Math.abs(r.right - 20) < 1, `lề ${r.left}/${r.right}`);
		assert(r.radius >= 20, "bo góc " + r.radius);
		assert(Math.abs(r.ratio - 4 / 3) < 0.02, "tỉ lệ ảnh " + r.ratio.toFixed(2));
		assert(!r.overlap, "chữ đè lên ảnh");
		assert(Math.abs(r.btns[0] - r.btns[1]) < 1 && r.btns[0] > r.copyW - 60, "nút " + r.btns.join("/"));
		return `${w}px: lề 20px, bo ${r.radius}px, ảnh 4:3 không bị chữ đè, 2 nút ${Math.round(r.btns[0])}px`;
	});
	await p.close();
}

{
	const p = await visitor(1440, 900);
	await go(p, "/");
	await T("HE-02", async () => {
		const r = await p.evaluate(() => { const s = document.getElementById("top"); const f = s.querySelector("figure").getBoundingClientRect(); const rs = s.getBoundingClientRect(); return { h: rs.height, radius: getComputedStyle(s).borderTopLeftRadius, cover: Math.abs(f.height - rs.height) < 2 && Math.abs(f.width - rs.width) < 2 }; });
		assert(Math.round(r.h) === 720 && r.radius === "0px" && r.cover, JSON.stringify(r));
		return "cao 720px, ảnh phủ toàn khung, không bo góc";
	});
	await T("HE-03", async () => {
		const out = [];
		for (const [w, size] of [[390, 24], [768, 28], [1440, 32]]) {
			await resize(p, w);
			const r = await p.evaluate(() => { const b = document.querySelector("#top .absolute.top-3"); const f = b.querySelector("figure").getBoundingClientRect(); return { fw: f.width, h: b.getBoundingClientRect().height }; });
			assert(Math.round(r.fw) === size && r.h < 80, `${w}px: icon ${r.fw}px, huy hiệu cao ${r.h}px`);
			out.push(`${w}px icon ${size}px`);
		}
		return out.join(", ");
	});
	await resize(p, 1440);
	await T("HE-04", async () => {
		await p.evaluate(() => scrollTo(0, 0));
		await p.mouse.move(2, 2);
		await p.$eval("#top .elio-pill.is-ivory a", (a) => a.click());
		await sleep(700);
		assert(await inView(p, "tour"), "không tới #tour");
	});
	await T("HE-05", async () => {
		const href = await p.$eval("#top .elio-pill.is-ghost-light a", (a) => a.getAttribute("href"));
		return { status: "Pass", note: "link = " + href + " (đã thống nhất giữ #)" };
	});
	await T("HE-06", async () => {
		const out = [];
		for (const [w, cols, icon] of [[390, 2, 26], [768, 3, 26], [1280, 6, 30]]) {
			await resize(p, w);
			const r = await p.evaluate(() => { const g = document.getElementById("top").nextElementSibling; return { cols: getComputedStyle(g).gridTemplateColumns.split(" ").length, icon: g.querySelector("figure").getBoundingClientRect().width }; });
			assert(r.cols === cols && Math.round(r.icon) === icon, `${w}px: ${r.cols} cột, icon ${r.icon}px`);
			out.push(`${w}px ${cols} cột`);
		}
		return out.join(", ");
	});
	await p.close();
}

await T("HE-07", async () => {
	const p = await visitor(390, 844);
	await go(p, "/en/");
	const r = await p.evaluate(() => ({ scroll: document.documentElement.scrollWidth - innerWidth, clipped: [...document.querySelectorAll("#top .elio-pill a, #top h1")].filter((e) => e.scrollWidth > e.clientWidth + 1).length }));
	await shot(p, "hero-en-390");
	await p.close();
	assert(r.scroll <= 1 && !r.clipped, JSON.stringify(r));
});

/* ============================================================= HOME SECTIONS */

{
	const p = await visitor(1440, 900);
	await go(p, "/");
	await T("SC-01", async () => {
		const r = await p.evaluate(() => ({
			ready: [".elio-why", ".elio-day", ".elio-campus", ".elio-edu"].map((s) => document.querySelector(s).classList.contains("is-ready")),
			source: [...document.querySelectorAll(".elio-why-item,.elio-day-stop,.elio-campus-item,.elio-edu-item")].filter((n) => getComputedStyle(n).display !== "none").length,
			jsClassFirst: document.documentElement.classList.contains("js"),
		}));
		const errs = consoleErrors.filter((e) => e.includes("[elio]"));
		assert(r.ready.every(Boolean), "chưa dựng xong: " + r.ready);
		assert(r.source === 0, r.source + " thẻ nguồn còn hiện");
		assert(!errs.length, errs.join(" | "));
		return "4 section dựng xong, danh sách nguồn bị ẩn từ trước khi JS chạy (class js trên <html>), không lỗi [elio]";
	});
	await T("WH-01", async () => {
		await p.evaluate(() => document.getElementById("why").scrollIntoView());
		const seen = [];
		const rows = await p.$$(".elio-why [aria-controls^=why-panel]");
		for (let i = 0; i < rows.length; i++) {
			await rows[i].click();
			const [rowTitle, panelTitle] = await p.evaluate((i) => [document.querySelectorAll(".elio-why [aria-controls^=why-panel] [data-title]")[i].textContent, document.querySelector(".elio-why .lg\\:flex [data-title]").textContent], i);
			assert(rowTitle === panelTitle, `mục ${i + 1}: panel “${panelTitle}”`);
			seen.push(panelTitle.slice(0, 18));
		}
		await shot(p, "why-1440");
		return rows.length + " mục, panel đổi đúng";
	});
	await T("DA-01", async () => {
		await p.evaluate(() => document.getElementById("day").scrollIntoView());
		const read = () => p.evaluate(() => ({ c: document.querySelector(".elio-day [data-count]").textContent, src: document.querySelector(".elio-day [data-photo]").src.split("/").pop(), t: document.querySelector(".elio-day [data-time]").textContent, pressed: [...document.querySelectorAll(".elio-day [data-stops] > button")].findIndex((b) => b.getAttribute("aria-pressed") === "true"), next: document.querySelector(".elio-day [data-nexts] .visible").textContent }));
		const seq = [await read()];
		for (let i = 0; i < 7; i++) {
			await p.click('.elio-day [data-dir="next"]');
			seq.push(await read());
		}
		assert(seq[7].c === "01 / 07" && seq[7].src === seq[0].src, "sau 7 lần → " + seq[7].c);
		assert(new Set(seq.slice(0, 7).map((s) => s.src)).size === 7, "ảnh không đổi đủ 7");
		assert(seq.every((s, i) => s.pressed === i % 7), "timeline không theo");
		assert(seq[0].next.includes("08:30"), "Tiếp theo = " + seq[0].next);
		await p.click('.elio-day [data-dir="prev"]');
		const back = await read();
		assert(back.c === "07 / 07" && back.t === "16:00", "← ở 01 → " + back.c);
		await shot(p, "day-1440");
		return "→ ×7 quay về 01/07; ← từ 01 → 07/07 (16:00); ảnh, giờ, timeline, “Tiếp theo” đồng bộ";
	});
	await T("CA-01", async () => {
		await p.evaluate(() => document.getElementById("campus").scrollIntoView());
		await p.mouse.move(2, 2);
		const lbl = () => p.$eval(".elio-campus [data-label]", (e) => e.textContent);
		const first = await lbl();
		for (let i = 0; i < 8; i++) await p.click('.elio-campus [data-dir="next"]');
		const after = await lbl();
		await p.click('.elio-campus [data-dir="prev"]');
		const prev = await lbl();
		const rows = await p.$$(".elio-campus [data-list] > button");
		await rows[2].hover();
		const hov = await lbl();
		await shot(p, "campus-1440");
		assert(after === first, "sau 8 lần → " + after);
		assert(prev.startsWith("08"), "← từ 01 → " + prev);
		assert(hov.startsWith("03"), "rê chuột dòng 3 → " + hov);
		return "vòng 08↔01 đúng, rê chuột đổi ảnh";
	});
	await T("GV-01", async () => {
		await p.evaluate(() => document.getElementById("educators").scrollIntoView());
		const tabs = await p.$$(".elio-edu [role=tab]");
		await tabs[2].click();
		const a = await p.$eval(".elio-edu [data-profiles] > .visible", (e) => e.textContent);
		await p.click('.elio-edu [role=tabpanel] [data-dir="next"]');
		const b = await p.$eval(".elio-edu [data-profiles] > .visible", (e) => e.textContent);
		const sel = await p.$$eval(".elio-edu [role=tab]", (ts) => ts.findIndex((t) => t.getAttribute("aria-selected") === "true"));
		await shot(p, "edu-1440");
		assert(a.includes("Mai Pham") && b.includes("Alice Becca") && sel === 3, `tab3=${a.slice(0, 40)} next=${b.slice(0, 40)} sel=${sel}`);
		return "tab 3 → Ms. Mai Pham, → Ms. Alice Becca, tab đúng";
	});
	await T("NU-01", async () => {
		const out = [];
		for (const w of [1440, 390]) {
			await resize(p, w);
			const bad = await p.evaluate(() => [...document.querySelectorAll("#nutrition figure.rounded-full, #approach figure.rounded-full, #values figure.rounded-full, #values span.rounded-full.border")].map((f) => f.getBoundingClientRect()).filter((r) => Math.abs(r.width - r.height) > 1).length);
			const n = await p.evaluate(() => document.querySelectorAll("#nutrition figure.rounded-full, #approach figure.rounded-full, #values figure.rounded-full, #values span.rounded-full.border").length);
			assert(!bad, `${w}px: ${bad}/${n} vòng tròn bị méo`);
			out.push(`${w}px ${n} vòng tròn đều`);
		}
		return out.join(", ");
	});
	await resize(p, 1440);
	await T("GR-01", async () => {
		const r = await p.evaluate(() => { const g = document.querySelector("#grow .xl\\:grid-cols-5"); return { cols: getComputedStyle(g).gridTemplateColumns.split(" ").length, hs: [...g.children].map((c) => c.lastElementChild.getBoundingClientRect().height) }; });
		assert(r.cols === 5 && r.hs.every((h, i) => i === 0 || h > r.hs[i - 1]), JSON.stringify(r));
		await resize(p, 320);
		const ov = await p.evaluate(() => { const h = document.querySelector("#grow h2"); return h.scrollWidth - h.clientWidth; });
		await resize(p, 1440);
		assert(ov <= 1, "320px tiêu đề tràn " + ov + "px");
		return `5 cột bậc thang (${r.hs.map(Math.round).join("→")}px), 320px không tràn`;
	});
	await T("NW-01", async () => {
		const read = (q) => q.evaluate(() => ({ n: document.querySelectorAll("#news .elio-cards > li").length, imgs: document.querySelectorAll("#news .elio-cards > li img").length, dates: [...document.querySelectorAll("#news .wp-block-post-date")].map((d) => d.textContent.trim()), cats: [...document.querySelectorAll("#news .wp-block-post-terms")].map((d) => d.textContent.trim()), more: document.querySelector("#news p a").getAttribute("href") }));
		const vi = await read(p);
		const q = await visitor();
		await go(q, "/en/");
		const en = await read(q);
		await go(q, new URL(vi.more).pathname);
		const st1 = await q.evaluate(() => document.querySelectorAll(".elio-cards > li").length);
		await q.close();
		assert(vi.n === 3 && vi.imgs === 3 && en.n === 3, `VI ${vi.n} thẻ / ${vi.imgs} ảnh, EN ${en.n}`);
		assert(vi.dates.every((d) => /^\d{2}\/\d{2}\/\d{4}$/.test(d)), "ngày: " + vi.dates);
		assert(vi.cats.every((c) => /Sự kiện|Hoạt động|Thông báo/.test(c)) && en.cats.every((c) => /Events|Activities|Announcements/.test(c)), "chuyên mục: " + vi.cats + " | " + en.cats);
		assert(vi.more.endsWith("/tin-tuc/") && en.more.endsWith("/en/news/") && st1 >= 3, "Xem tất cả: " + vi.more + " / " + en.more);
		return `VI ${vi.dates.join(", ")}; EN ${en.cats.join(", ")}`;
	});
	await T("FO-01", async () => {
		const r = await p.evaluate(() => ({ tel: document.querySelector('footer a[href^="tel:"]').getAttribute("href"), telText: document.querySelector('footer a[href^="tel:"]').textContent.trim(), mail: document.querySelector('footer a[href^="mailto:"]').getAttribute("href"), addr: document.querySelector("footer > div:last-child span:last-child").textContent }));
		const q = await visitor();
		await go(q, "/en/");
		const enAddr = await q.$eval("footer > div:last-child span:last-child", (e) => e.textContent);
		await q.close();
		assert(r.tel === "tel:+84834123456" && r.telText === "0834 1234 56" && r.mail === "mailto:elio@gmail.com", JSON.stringify(r));
		assert(r.addr === "Thảo Điền, TP.HCM" && enAddr === "Thao Dien, Ho Chi Minh City", r.addr + " / " + enAddr);
		return "SĐT → tel:+84834123456; email; địa chỉ VI/EN đúng (số liệu mẫu, cần thay số thật)";
	});
	await T("FO-03", async () => {
		const p = await visitor(1440, 900);
		await go(p, "/");
		const links = await p.$$eval("footer a", (as) => as.filter((a) => a.textContent.trim().startsWith("→")).map((a) => a.getAttribute("href")));
		const bad = [];
		for (const href of links) {
			await p.evaluate(() => scrollTo(0, 0));
			await p.mouse.move(2, 2);
			await p.$eval(`footer a[href="${href}"]`, (a) => a.click());
			await sleep(700);
			const id = href.split("#")[1];
			if (!(await inView(p, id))) bad.push(id);
		}
		await p.close();
		assert(links.length === 5 && !bad.length, links.length + " link, lỗi: " + bad);
		return "5 link cuộn đúng section";
	});
	await p.close();
}

{
	const p = await visitor(390, 844);
	await go(p, "/");
	await T("WH-02", async () => {
		await p.evaluate(() => document.getElementById("why").scrollIntoView());
		const st = () => p.$$eval(".elio-why [data-acc]", (as) => as.map((a) => a.classList.contains("grid-rows-[1fr]")));
		const s0 = await st();
		const rows = await p.$$(".elio-why [aria-controls^=why-panel]");
		await rows[1].click();
		await sleep(400);
		const s1 = await st();
		await rows[1].click();
		await sleep(400);
		const s2 = await st();
		const chev = await p.$$eval(".elio-why [data-chev]", (c) => c.map((x) => x.classList.contains("rotate-180")));
		await shot(p, "why-390");
		assert(s0[0] && s0.filter(Boolean).length === 1, "ban đầu: " + s0);
		assert(s1[1] && s1.filter(Boolean).length === 1, "bấm dòng 2: " + s1);
		assert(!s2.some(Boolean) && !chev.some(Boolean), "bấm lại: " + s2);
	});
	await T("CA-02", async () => {
		await p.evaluate(() => document.getElementById("campus").scrollIntoView());
		const thumbs = await p.$$(".elio-campus [data-strip] > button");
		await thumbs[3].click();
		await sleep(600);
		const r = await p.evaluate(() => ({ c: document.querySelector(".elio-campus [data-count]").textContent, ring: document.querySelectorAll(".elio-campus [data-strip] > button")[3].querySelector("[data-ph]").classList.contains("ring-gold"), nudge: document.querySelector(".elio-campus [data-hand]").classList.contains("motion-safe:animate-nudge"), scrolled: document.querySelector(".elio-campus [data-strip]").scrollLeft }));
		await shot(p, "campus-390");
		assert(r.c === "04 / 08" && r.ring, JSON.stringify(r));
		assert(!r.nudge, "icon bàn tay vẫn nhún sau khi vuốt");
		return `04 / 08, viền vàng, dải cuộn ${Math.round(r.scrolled)}px, bàn tay ngừng nhún`;
	});
	await T("GV-02", async () => {
		await p.evaluate(() => document.getElementById("educators").scrollIntoView());
		await p.click('.elio-edu article [data-dir="next"]');
		await sleep(900);
		const r = await p.evaluate(() => { const t = document.querySelector(".elio-edu article").parentElement; const peek = document.querySelectorAll(".elio-edu article")[1].getBoundingClientRect().left < innerWidth; return { left: t.scrollLeft, c: document.querySelector(".elio-edu .md\\:hidden [data-count]").textContent, peek }; });
		await shot(p, "edu-390");
		assert(r.left > 100 && r.c === "02 / 04", JSON.stringify(r));
		return "→ trượt sang thẻ 2, bộ đếm 02 / 04";
	});
	await p.close();
}

await T("DA-02", async () => {
	const p = await visitor(768, 1024);
	await go(p, "/");
	await p.evaluate(() => document.getElementById("day").scrollIntoView());
	const y0 = await p.evaluate(() => scrollY);
	await p.$$eval(".elio-day [data-stops] > button", (b) => b[6].click());
	await sleep(800);
	const r = await p.evaluate(() => ({ left: document.querySelector(".elio-day [data-rail]").scrollLeft, y: scrollY, c: document.querySelector(".elio-day [data-count]").textContent }));
	await p.close();
	assert(r.left > 0 && Math.abs(r.y - y0) < 2 && r.c === "07 / 07", JSON.stringify(r) + " y0=" + y0);
	return `timeline cuộn ngang ${Math.round(r.left)}px, trang không cuộn dọc`;
});
await T("DA-03", async () => {
	const p = await visitor(440, 956);
	await go(p, "/");
	await p.evaluate(() => { const k = [...document.querySelectorAll("#day p")].find((n) => n.textContent.trim() === "THE ELIO EXPERIENCE"); scrollTo(0, k.getBoundingClientRect().top + scrollY - 40); });
	await sleep(300);
	const b = await p.$eval('.elio-day [data-dir="next"]', (e) => e.getBoundingClientRect().bottom);
	await shot(p, "day-440");
	await p.close();
	return b <= 956 ? "nút → ở y=" + Math.round(b) + "px trong khung 956px" : { status: "Fail", note: "nút ← → nằm ở y=" + Math.round(b) + "px, ngoài khung 956px (cần cuộn thêm)" };
});
blocked("CO-01", "Nội dung: trường cần xác nhận độ tuổi (18 hay 12 tháng)");
await T("FO-02", async () => {
	await setSettings(admin, { facebook: "https://facebook.com/elio.test" });
	const p = await visitor();
	await go(p, "/");
	const r = await p.$$eval('footer a[aria-label]', (as) => as.map((a) => [a.getAttribute("aria-label"), a.getAttribute("href"), a.getAttribute("target")]));
	await p.close();
	await setSettings(admin, { facebook: "" });
	const fb = r.find((x) => x[0] === "Facebook");
	const others = r.filter((x) => x[0] !== "Facebook");
	assert(fb[1] === "https://facebook.com/elio.test" && fb[2] === "_blank" && others.every((o) => o[1] === "#"), JSON.stringify(r));
	return "Facebook có URL → mở tab mới; ô trống → #";
});

/* ===================================================================== FORM */

const fill = async (p, v) => {
	await p.evaluate((v) => {
		const f = document.querySelector("[data-elio-tour] form");
		for (const [k, val] of Object.entries(v)) {
			const el = f.querySelector(`[name="${k}"]`);
			el.value = val;
			el.dispatchEvent(new Event("input", { bubbles: true }));
		}
	}, v);
};
const future = () => { const d = new Date(Date.now() + 4 * 864e5); return d.toISOString().slice(0, 10); };
const errs = (p) => p.$$eval("[data-elio-tour] [id$=-err]", (s) => Object.fromEntries(s.filter((x) => !x.hidden).map((x) => [x.id.replace("tour-", "").replace("-err", ""), x.textContent])));
const submit = (p) => p.evaluate(() => { const b = document.querySelector("[data-elio-tour] button[type=submit]"); b.click(); return [b.disabled, b.textContent.trim()]; });
const waitDone = (p) => p.waitForFunction(() => !document.querySelector("[data-elio-tour-done]").hidden || !document.querySelector("[data-elio-tour-error]").hidden, { timeout: 60000 });
const isDone = (p) => p.evaluate(() => !document.querySelector("[data-elio-tour-done]").hidden);

await T("TF-16", async () => {
	await setSettings(admin, { webhook_url: MOCK_URL, secret: SECRET });
	await setSettings(admin, { phone: "0834 1234 56" }); // save again with the secret field left empty
	const r = await admin.evaluate(() => ({ status: [...document.querySelectorAll(".form-table td")].map((t) => t.textContent).find((t) => t.includes("cấu hình")), secretValue: document.querySelector("#elio-secret").value, ph: document.querySelector("#elio-secret").placeholder, html: document.documentElement.innerHTML.includes("test-secret-123") }));
	assert(r.status.includes("Đã cấu hình") && r.secretValue === "" && !r.html, JSON.stringify(r));
	return "lưu với ô SECRET trống → vẫn “Đã cấu hình”, SECRET không có trong HTML trang";
});

{
	const p = await visitor(1440, 900);
	const requests = [];
	p.on("request", (r) => r.url().includes("/elio/v1/tour") && requests.push(r.method()));
	await go(p, "/");
	await p.evaluate(() => document.getElementById("tour").scrollIntoView());
	await T("TF-01", async () => {
		await submit(p);
		await sleep(300);
		const e = await errs(p);
		const focus = await p.evaluate(() => document.activeElement.id);
		await shot(p, "form-errors");
		assert(Object.keys(e).length === 4 && focus === "tour-name" && !requests.length, `lỗi ${Object.keys(e)}, focus ${focus}, request ${requests.length}`);
		return "4 ô báo lỗi, focus Họ tên, không gửi request";
	});
	await T("TF-06", async () => {
		await p.type("#tour-name", "A");
		const e = await errs(p);
		assert(!e.name && e.phone, JSON.stringify(e));
	});
	await T("TF-02", async () => {
		await p.$eval("#tour-phone", (e) => (e.value = ""));
		await p.type("#tour-phone", "+84 834.123-456");
		const a = await p.$eval("#tour-phone", (e) => e.value);
		await p.$eval("#tour-phone", (e) => (e.value = ""));
		await p.type("#tour-phone", "0123456789012345");
		const b = await p.$eval("#tour-phone", (e) => e.value);
		// Paste: the input event fires with the whole pasted string.
		await p.$eval("#tour-phone", (e) => { e.value = "08 34-12.34 56"; e.dispatchEvent(new Event("input", { bubbles: true })); });
		const c = await p.$eval("#tour-phone", (e) => e.value);
		assert(a === "84834123456" && b === "012345678901" && c === "0834123456", `${a} | ${b} | ${c}`);
		return `“+84 834.123-456” → ${a}; 16 số → ${b}; dán “08 34-12.34 56” → ${c}`;
	});
	await T("TF-03", async () => {
		const out = [];
		for (const ph of ["12345678", "123456789", "0834123456", "012345678901"]) {
			await fill(p, { name: "TEST – x", phone: ph, age: "", date: "" });
			await submit(p);
			const e = await errs(p);
			out.push(ph.length + " số: " + (e.phone ? "lỗi" : "ok"));
		}
		assert(out.join() === "8 số: lỗi,9 số: ok,10 số: ok,12 số: ok", out.join(", "));
		return out.join(", ");
	});
	await T("TF-04", async () => {
		await p.focus("#tour-date");
		const min = await p.$eval("#tour-date", (e) => e.min);
		const today = await p.evaluate(() => { const d = new Date(); return d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0") + "-" + String(d.getDate()).padStart(2, "0"); });
		await fill(p, { name: "TEST – x", phone: "0834123456", age: "2-3y", date: "2020-01-01" });
		await submit(p);
		const e = await errs(p);
		assert(min === today && e.date === "Vui lòng chọn một ngày từ hôm nay trở đi.", `min=${min} lỗi=${e.date}`);
		return "min = hôm nay (" + min + "), ngày đã qua bị chặn";
	});
	blocked("TF-05", "Cần iPhone thật (Safari iOS)");
	await T("TF-07", async () => {
		await fill(p, { name: "TEST – Nguyễn Văn Á", phone: "0834123456", age: "2-3y", date: future(), message: "Lời nhắn <b>thử</b> & “ngoặc”" });
		const [disabled, label] = await submit(p);
		await waitDone(p);
		const done = await isDone(p);
		await shot(p, "form-done");
		assert(disabled && label === "ĐANG GỬI...", `khi gửi: disabled=${disabled} “${label}”`);
		assert(done, "không hiện màn cảm ơn");
		saved++;
		const steps = await p.$$eval("[data-elio-tour-done] .grid-cols-\\[36px_minmax\\(0\\,1fr\\)\\]", (s) => s.length);
		assert(steps === 3, steps + " bước");
		return "nút “ĐANG GỬI...” bị khoá → màn “Cảm ơn ba mẹ!” với 3 bước";
	});
	await T("TF-08", async () => {
		const row = mockRows().pop();
		assert(row && row.phone === "0834123456" && row.age === "2–3 tuổi" && /^\d{2}\/\d{2}\/\d{4}$/.test(row.date) && row.name === "TEST – Nguyễn Văn Á" && row.lang === "Tiếng Việt", JSON.stringify(row));
		return { status: "Pass", note: `Apps Script giả lập nhận: SĐT “${row.phone}”, “${row.age}”, ${row.date}, tên có dấu đúng, “${row.lang}”. Cần xem lại trên Sheet thật (số 0 đầu do Code.gs giữ)` };
	});
	blocked("TF-09", "Cần Google Sheet + email thật trên hosting");
	await T("TF-11", async () => {
		await p.click("[data-elio-tour-again]");
		const r = await p.evaluate(() => { const c = document.querySelector("[data-elio-tour]"); return { form: !c.querySelector("form").hidden, name: c.querySelector("#tour-name").value, arch: c.className.includes("lg:rounded-[260px_260px_28px_28px]") }; });
		assert(r.form && r.name === "" && r.arch, JSON.stringify(r));
	});
	await T("TF-12", async () => {
		await setSettings(admin, { secret: "wrong-secret" });
		const before = mockRows().length;
		await fill(p, { name: "TEST – sai secret", phone: "0834123456", age: "4-6y", date: future(), message: "" });
		await submit(p);
		await waitDone(p);
		const r = await p.evaluate(() => ({ alert: !document.querySelector("[data-elio-tour-error]").hidden, name: document.querySelector("#tour-name").value }));
		await setSettings(admin, { secret: SECRET });
		assert(r.alert && r.name === "TEST – sai secret" && mockRows().length === before, JSON.stringify(r));
		return "báo lỗi đỏ, dữ liệu còn nguyên, không có dòng mới (đã trả lại SECRET đúng)";
	});
	await T("TF-13", async () => {
		await setSettings(admin, { webhook_url: "" });
		const status = await p.evaluate(async (d) => (await fetch("/wp-json/elio/v1/tour", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ name: "TEST", phone: "0834123456", age: "2-3y", date: d }) })).status, future());
		await submit(p);
		await waitDone(p);
		const alert = await p.evaluate(() => !document.querySelector("[data-elio-tour-error]").hidden && document.querySelector("[data-elio-tour-done]").hidden);
		await setSettings(admin, { webhook_url: MOCK_URL });
		assert(status === 503 && alert, `API ${status}, form báo lỗi=${alert}`);
		return "API 503, form báo lỗi (đã nhập lại URL)";
	});
	await T("TF-14", async () => {
		await p.setOfflineMode(true);
		await submit(p);
		await waitDone(p);
		const r = await p.evaluate(() => ({ alert: !document.querySelector("[data-elio-tour-error]").hidden, name: document.querySelector("#tour-name").value }));
		await p.setOfflineMode(false);
		assert(r.alert && r.name === "TEST – sai secret", JSON.stringify(r));
	});
	await p.close();
}

await T("TF-10", async () => {
	const p = await visitor(1440, 900);
	await go(p, "/en/");
	await p.evaluate(() => document.getElementById("tour").scrollIntoView());
	const labels = await p.$$eval("[data-elio-tour] label", (l) => l.map((x) => x.childNodes[0].textContent.trim()));
	await submit(p);
	const e = await errs(p);
	await fill(p, { name: "TEST – EN", phone: "0834123456", age: "2-3y", date: future(), message: "" });
	await submit(p);
	await waitDone(p);
	const thanks = await p.$eval("[data-elio-tour-done]", (d) => d.textContent);
	await shot(p, "form-done-en");
	await p.close();
	saved++;
	const row = mockRows().pop();
	assert(labels[0] === "Parent’s full name *" && e.name === "Please fill in this field." && thanks.includes("Thank you!"), labels[0] + " | " + e.name);
	assert(row.lang === "English" && row.age === "2–3 tuổi", JSON.stringify(row));
	return "nhãn, lỗi, màn cảm ơn tiếng Anh; Sheet nhận “English”, độ tuổi tiếng Việt";
});

/* ============================================================ EDITOR & NEWS */

const editorOpen = async (p, id) => {
	await p.goto(`${BASE}/wp-admin/post.php?post=${id}&action=edit`, { waitUntil: "networkidle2", timeout: 180000 });
	await p.waitForFunction(() => window.wp?.data?.select("core/block-editor")?.getBlocks().length > 0, { timeout: 180000 });
	await sleep(1500);
};
const editorSave = async (p) => {
	await p.evaluate(() => wp.data.dispatch("core/editor").savePost());
	await sleep(500);
	await p.waitForFunction(() => !wp.data.select("core/editor").isSavingPost(), { timeout: 120000 });
	await sleep(500);
};
/** clientId of blocks whose className contains `cls` (document order). */
const findBlocks = (p, cls) => p.evaluate((cls) => { const out = []; (function walk(bs) { bs.forEach((b) => { if ((b.attributes.className || "").split(" ").includes(cls)) out.push(b.clientId); walk(b.innerBlocks); }); })(wp.data.select("core/block-editor").getBlocks()); return out; }, cls);
const invalidCount = (p) => p.evaluate(() => { let bad = 0, all = 0; (function walk(bs) { bs.forEach((b) => { all++; if (!b.isValid) bad++; walk(b.innerBlocks); }); })(wp.data.select("core/block-editor").getBlocks()); return [bad, all]; });
const restoreHome = () => admin.api("POST", "/wp/v2/pages/" + homeVi.id, { content: originalHome });

if (anyOf("ED-01", "ED-02", "ED-03", "ED-04", "ED-05", "ED-06")) {
	// Editor role (the school's account).
	const pass = "Elio-Test-" + Date.now();
	const u = await admin.api("POST", "/wp/v2/users", { username: "elio-editor", email: "editor@example.com", password: pass, roles: ["editor"] });
	if (u.body.id) created.users.push(u.body.id);
	const ctx = await browser.createBrowserContext();
	const ed = await ctx.newPage();
	ed.setDefaultTimeout(180000);
	await ed.setViewport({ width: 1440, height: 1000 });
	await ed.setCookie({ name: "playground_auto_login_already_happened", value: "1", url: BASE });
	await ed.goto(BASE + "/wp-login.php", { waitUntil: "networkidle2" });
	await ed.type("#user_login", "elio-editor");
	await ed.type("#user_pass", pass);
	await Promise.all([ed.waitForNavigation({ waitUntil: "load", timeout: 180000 }), ed.click("#wp-submit")]);

	await T("ED-01", async () => {
		await editorOpen(ed, homeVi.id);
		const [bad, all] = await invalidCount(ed);
		await ed.evaluate(() => { try { wp.data.dispatch("core/preferences").set("core/edit-post", "welcomeGuide", false); } catch (e) {} });
		await shot(ed, "editor-home");
		const settings = await ed.goto(BASE + "/wp-admin/options-general.php?page=elio", { waitUntil: "networkidle2" });
		const denied = settings.status() === 403 || (await ed.content()).includes("not allowed");
		assert(bad === 0 && all > 500, `${bad}/${all} block lỗi`);
		return `tài khoản Editor: ${all} block, 0 lỗi; Editor không vào được Cài đặt → ELIO (${denied ? "bị chặn" : "VÀO ĐƯỢC"})`;
	});
	await editorOpen(ed, homeVi.id);
	await T("ED-02", async () => {
		const newImg = BASE + "/wp-content/themes/elio/assets/img/campus/01-exterior.jpg";
		await ed.evaluate((newImg) => {
			const all = [];
			(function walk(bs) { bs.forEach((b) => { all.push(b); walk(b.innerBlocks); }); })(wp.data.select("core/block-editor").getBlocks());
			const h1 = all.find((b) => b.name === "core/heading" && b.attributes.level === 1);
			const img = all.find((b) => b.name === "core/image" && (b.attributes.className || "").includes("object-[50%_40%]"));
			wp.data.dispatch("core/block-editor").updateBlockAttributes(h1.clientId, { content: "Tiêu đề TEST đã sửa" });
			wp.data.dispatch("core/block-editor").updateBlockAttributes(img.clientId, { url: newImg });
		}, newImg);
		await editorSave(ed);
		const p = await visitor();
		await go(p, "/");
		const r = await p.evaluate(() => ({ h1: document.querySelector("#top h1").textContent, img: document.querySelector("#top figure img").src, h: document.getElementById("top").offsetHeight }));
		await p.close();
		assert(r.h1 === "Tiêu đề TEST đã sửa" && r.img.endsWith("01-exterior.jpg") && r.h === 720, JSON.stringify(r));
		return "đổi tiêu đề + ảnh hero → trang thật đổi theo, hero vẫn cao 720px";
	});
	await T("ED-03", async () => {
		const stops = await findBlocks(ed, "elio-day-stop");
		await ed.evaluate((id) => wp.data.dispatch("core/block-editor").duplicateBlocks([id]), stops[stops.length - 1]);
		await sleep(500);
		const stops2 = await findBlocks(ed, "elio-day-stop");
		await ed.evaluate((id) => { const b = wp.data.select("core/block-editor").getBlock(id); const t = b.innerBlocks.find((x) => (x.attributes.className || "").includes("elio-day-time")); wp.data.dispatch("core/block-editor").updateBlockAttributes(t.clientId, { content: "17:30" }); }, stops2[stops2.length - 1]);
		await editorSave(ed);
		const p = await visitor();
		await go(p, "/");
		const r = await p.evaluate(() => ({ c: document.querySelector(".elio-day [data-count]").textContent, rail: document.querySelectorAll(".elio-day [data-stops] > button").length }));
		await p.click('.elio-day [data-dir="prev"]');
		const last = await p.$eval(".elio-day [data-time]", (e) => e.textContent);
		await p.close();
		assert(stops2.length === 8 && r.c === "01 / 08" && r.rail === 8 && last === "17:30", JSON.stringify({ ...r, last }));
		return "nhân bản thẻ 16:00 → 17:30: timeline 8 mốc, 01 / 08";
	});
	await T("ED-04", async () => {
		const items = await findBlocks(ed, "elio-edu-item");
		await ed.evaluate((ids) => { const d = wp.data.dispatch("core/block-editor"); d.removeBlocks([ids[0]]); const b = wp.data.select("core/block-editor").getBlock(ids[1]); d.updateBlockAttributes(ids[1], { className: b.attributes.className + " is-he" }); }, items);
		await editorSave(ed);
		const p = await visitor(390, 844);
		await go(p, "/");
		const r = await p.evaluate(() => ({ c: document.querySelector(".elio-edu .md\\:hidden [data-count]").textContent, he: document.querySelector(".elio-edu article").textContent.includes("WHO HE IS") }));
		await p.close();
		assert(r.c === "01 / 03" && r.he, JSON.stringify(r));
		return "xoá 1 thẻ → 01 / 03; class is-he → “WHO HE IS”";
	});
	await T("ED-05", async () => {
		const camp = await findBlocks(ed, "elio-campus-item");
		const why = await findBlocks(ed, "elio-why-item");
		await ed.evaluate((a, b) => { const d = wp.data.dispatch("core/block-editor"); d.duplicateBlocks([a]); d.duplicateBlocks([b]); }, camp[0], why[0]);
		await editorSave(ed);
		const p = await visitor();
		await go(p, "/");
		const r = await p.evaluate(() => ({ campus: document.querySelectorAll(".elio-campus [data-list] > button").length, why: document.querySelectorAll(".elio-why [aria-controls^=why-panel]").length, spaces: [...document.querySelectorAll("#campus p")].find((x) => x.textContent === "SPACES").nextElementSibling.textContent }));
		await p.close();
		assert(r.campus === 9 && r.why === 6, JSON.stringify(r));
		return { status: "Pass", note: `Campus 9 khu vực, Why 6 trụ cột; số “SPACES” vẫn ${r.spaces} (phải sửa tay — ghi vào hướng dẫn cho khách)` };
	});
	await T("ED-06", async () => {
		const idx = await ed.evaluate(() => { const bs = wp.data.select("core/block-editor").getBlocks(); const i = bs.findIndex((b) => b.attributes.anchor === "nutrition"); wp.data.dispatch("core/block-editor").removeBlocks([bs[i].clientId]); return i; });
		const ok = await ed.evaluate(async (i) => {
			const pats = wp.data.select("core").getBlockPatterns() || [];
			let pat = pats.find((x) => x.name === "elio/nutrition-vi");
			if (!pat) {
				await new Promise((r) => setTimeout(r, 3000));
				pat = (wp.data.select("core").getBlockPatterns() || []).find((x) => x.name === "elio/nutrition-vi");
			}
			if (!pat) return "không thấy pattern";
			wp.data.dispatch("core/block-editor").insertBlocks(wp.blocks.parse(pat.content), i);
			return true;
		}, idx);
		assert(ok === true, ok);
		const [bad] = await invalidCount(ed);
		await editorSave(ed);
		const p = await visitor();
		await go(p, "/");
		const r = await p.evaluate(() => ({ n: !!document.getElementById("nutrition"), pol: document.querySelectorAll("#nutrition .lg\\:absolute.lg\\:w-\\[204px\\]").length, order: [...document.querySelectorAll(".elio-home > section")].map((s) => s.id).join(",") }));
		await p.close();
		assert(r.n && r.pol === 3 && bad === 0 && r.order.includes("day,nutrition,approach"), JSON.stringify(r));
		return "xoá rồi chèn lại pattern “Dinh dưỡng” đúng vị trí, 0 block lỗi";
	});
	await restoreHome();
	await ctx.close();
}

const catVi = (await admin.api("GET", "/wp/v2/categories?slug=su-kien&_fields=id")).body[0]?.id;
const media = (await admin.api("GET", "/wp/v2/media?per_page=1&_fields=id")).body[0]?.id;
const richContent =
	'<!-- wp:paragraph -->\n<p>Đoạn mở đầu của bài viết thử, có <a href="https://example.com">một liên kết</a>.</p>\n<!-- /wp:paragraph -->\n\n' +
	'<!-- wp:heading -->\n<h2 class="wp-block-heading">Tiêu đề phụ H2</h2>\n<!-- /wp:heading -->\n\n' +
	'<!-- wp:list -->\n<ul class="wp-block-list"><!-- wp:list-item -->\n<li>Mục một</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>Mục hai</li>\n<!-- /wp:list-item --></ul>\n<!-- /wp:list -->\n\n' +
	'<!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->\n<figure class="wp-block-image size-large"><img src="' + BASE + '/wp-content/themes/elio/assets/img/day/06-art.jpg" alt=""/><figcaption class="wp-element-caption">Chú thích ảnh</figcaption></figure>\n<!-- /wp:image -->\n\n' +
	'<!-- wp:quote -->\n<blockquote class="wp-block-quote"><!-- wp:paragraph -->\n<p>Một câu trích dẫn.</p>\n<!-- /wp:paragraph --></blockquote>\n<!-- /wp:quote -->';

await T("ED-07", async () => {
	const r = await admin.api("POST", "/wp/v2/posts", { title: "Bài TEST tự động", status: "publish", excerpt: "Tóm tắt bài TEST.", content: richContent, categories: [catVi], featured_media: media });
	assert(r.status === 201, "tạo bài: " + r.status + " " + JSON.stringify(r.body).slice(0, 200));
	created.posts.push(r.body.id);
	const p = await visitor();
	await go(p, "/tin-tuc/");
	const first = await p.$eval(".elio-cards > li .elio-card-title", (e) => e.textContent.trim());
	await go(p, "/");
	const home = await p.$eval("#news .elio-cards > li .elio-card-title", (e) => e.textContent.trim());
	await go(p, new URL(r.body.link).pathname);
	const st = await p.evaluate(() => { const c = document.querySelector(".elio-prose"); return { h2: getComputedStyle(c.querySelector("h2")).fontSize, ul: getComputedStyle(c.querySelector("ul")).listStyleType, quote: getComputedStyle(c.querySelector("blockquote")).borderLeftWidth, sizeFullImg: c.querySelector("figure img").getBoundingClientRect().height < 2000 }; });
	await shot(p, "post-1440");
	await resize(p, 390, 844);
	const ok390 = await noHScroll(p);
	await shot(p, "post-390");
	await p.close();
	assert(first === "Bài TEST tự động" && home === "Bài TEST tự động", `Tin tức: ${first} | trang chủ: ${home}`);
	assert(st.h2 === "28px" && st.ul === "disc" && st.quote === "3px" && ok390, JSON.stringify(st) + " 390=" + ok390);
	return "bài mới đứng đầu Tin tức và khối tin trang chủ; H2 28px, danh sách •, trích dẫn có vạch; 390px không tràn";
});
await T("ED-08", async () => {
	const keys = Object.keys((await admin.api("GET", "/wp/v2/posts/" + created.posts[0] + "?context=edit")).body);
	if (!keys.includes("lang")) return { status: "Blocked", note: "Polylang bản miễn phí không cho tạo bản dịch qua REST; cần bấm + trong trình soạn thảo bằng tay (đã chạy được với bài mẫu do nút “Tạo nội dung mẫu” tạo)" };
	return { status: "Blocked", note: "cần thao tác tay trong hộp Languages" };
});
await T("ED-09", async () => {
	const r = await admin.api("POST", "/wp/v2/posts", { title: "Bài TEST không ảnh", status: "publish", content: "<!-- wp:paragraph -->\n<p>Nội dung ngắn để làm tóm tắt tự động cho thẻ bài viết.</p>\n<!-- /wp:paragraph -->" });
	created.posts.push(r.body.id);
	const p = await visitor();
	await go(p, "/tin-tuc/");
	const c = await p.evaluate(() => { const li = [...document.querySelectorAll(".elio-cards > li")].find((l) => l.textContent.includes("Bài TEST không ảnh")); return { fig: !!li.querySelector("figure"), ex: li.querySelector(".elio-card-excerpt")?.textContent.trim() }; });
	await shot(p, "news-1440");
	await p.close();
	assert(!c.fig && c.ex && c.ex.startsWith("Nội dung ngắn"), JSON.stringify(c));
	return "thẻ không có ô ảnh trống, tóm tắt tự lấy từ nội dung";
});
await T("ED-10", async () => {
	await admin.api("POST", "/wp/v2/posts/" + created.posts[1], { sticky: true });
	const p = await visitor();
	await go(p, "/tin-tuc/");
	const pos = await p.evaluate(() => { const li = document.querySelector(".elio-cards > li.sticky"); return li ? getComputedStyle(li).position : "không có li.sticky"; });
	await p.close();
	await admin.api("POST", "/wp/v2/posts/" + created.posts[1], { sticky: false });
	assert(pos === "static", pos);
	return "bài sticky: position static (không dính)";
});
await T("ED-11", async () => {
	for (let i = 1; i <= 8; i++) {
		const r = await admin.api("POST", "/wp/v2/posts", { title: "Bài TEST phân trang " + i, status: "publish", content: "<!-- wp:paragraph -->\n<p>elio phân trang " + i + "</p>\n<!-- /wp:paragraph -->", categories: [catVi] });
		created.posts.push(r.body.id);
	}
	const p = await visitor();
	await go(p, "/tin-tuc/");
	const r1 = await p.evaluate(() => ({ n: document.querySelectorAll(".elio-cards > li").length, next: document.querySelector(".wp-block-query-pagination-next")?.getAttribute("href"), nums: document.querySelectorAll(".wp-block-query-pagination .page-numbers").length }));
	await shot(p, "news-pagination");
	await go(p, new URL(r1.next).pathname + new URL(r1.next).search);
	const n2 = await p.evaluate(() => document.querySelectorAll(".elio-cards > li").length);
	await go(p, "/category/su-kien/");
	const catH = await p.$eval("main h1", (e) => e.textContent);
	await go(p, "/?s=elio");
	const sH = await p.$eval("main h1", (e) => e.textContent);
	await p.close();
	const sticky = (await admin.api("GET", "/wp/v2/posts?sticky=true&_fields=id")).body.length;
	const perPage = (await admin.api("GET", "/wp/v2/settings")).body.posts_per_page;
	assert(perPage === 9, "Settings → Reading: " + perPage + " bài/trang (setup đặt 9 cho lưới 3 cột)");
	assert(r1.n === 9 + sticky && r1.next && n2 > 0, JSON.stringify(r1) + " trang2=" + n2);
	assert(catH === "Sự kiện" && sH.startsWith("Kết quả tìm kiếm cho"), catH + " | " + sH);
	return `${r1.n} bài trang 1 (9 + ${sticky} bài ghim), trang 2 có ${n2} bài; chuyên mục “${catH}”; “${sH}”`;
});
await T("ED-12", async () => {
	const pats = (await admin.api("GET", "/wp/v2/block-patterns/patterns")).body;
	const content = ["elio/hero-vi", "elio/grow-vi"].map((n) => pats.find((x) => x.name === n).content).join("\n\n");
	const r = await admin.api("POST", "/wp/v2/pages", { title: "Trang rộng TEST", status: "publish", template: "page-full", content });
	created.pages.push(r.body.id);
	const p = await visitor();
	await go(p, new URL(r.body.link).pathname);
	const s = await p.evaluate(() => ({ top: document.getElementById("top")?.getBoundingClientRect().width, narrow: !!document.querySelector("main .max-w-\\[760px\\]"), grow: !!document.getElementById("grow") }));
	await p.close();
	assert(s.top >= 1279 && !s.narrow && s.grow, JSON.stringify(s));
	return "hero rộng " + s.top + "px, không bị khung chữ 760px";
});
await T("ED-13", async () => {
	const p = await visitor();
	await go(p, "/khong-ton-tai/");
	const vi = await p.evaluate(() => [document.querySelector("main h1").textContent, document.querySelector("main .elio-pill a").getAttribute("href")]);
	await go(p, "/en/khong-ton-tai/");
	const en = await p.evaluate(() => [document.querySelector("main h1").textContent, document.querySelector("main .elio-pill a").getAttribute("href")]);
	await p.close();
	assert(vi[0] === "Không tìm thấy trang" && en[0] === "Page not found" && /^\/en\/(home\/)?$/.test(new URL(en[1]).pathname), vi + " | " + en);
	return `VI “${vi[0]}”, EN “${en[0]}” → ${new URL(en[1]).pathname}`;
});

/* ======================================================= LANGUAGE, LAYOUT, SEO */

await T("LG-01", async () => {
	const p = await visitor();
	await go(p, "/en/");
	const vi = await p.evaluate(() => {
		const re = /[ăâđêôơưạảấầẩẫậắằẳẵặẹẻẽếềểễệỉịọỏốồổỗộớờởỡợụủứừửữựỳỵỷỹ]/i;
		const out = new Set();
		const w = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
		while (w.nextNode()) {
			const t = w.currentNode.textContent.trim();
			const el = w.currentNode.parentElement;
			if (t && re.test(t) && el.closest("body") && !el.closest("[data-elio-lang], #wpadminbar, script, style") && el.getClientRects().length && getComputedStyle(el).visibility !== "hidden") out.add(t.slice(0, 60));
		}
		return [...out];
	});
	await p.close();
	assert(!vi.length, "còn chữ tiếng Việt: " + vi.join(" | "));
	return "không còn câu tiếng Việt (ngoài “Tiếng Việt” trong hộp chọn ngôn ngữ)";
});
await T("LG-02", async () => {
	const p = await visitor();
	await go(p, "/en/");
	const t = await p.title();
	await p.close();
	return t.includes("Trường mầm non") ? { status: "Fail", note: "tiêu đề tab EN: “" + t + "”. Bước cấu hình: Languages → Translations → dịch Tagline" } : "“" + t + "”";
});
await T("LG-03", async () => {
	const p = await visitor();
	await go(p, "/en/news/");
	const r = await p.evaluate(() => ({ d: [...document.querySelectorAll(".wp-block-post-date")].map((x) => x.textContent.trim()), c: [...document.querySelectorAll(".wp-block-post-terms")].map((x) => x.textContent.trim()) }));
	await p.close();
	assert(r.d.every((d) => /^\d{2}\/\d{2}\/\d{4}$/.test(d)) && r.c.every((c) => /Events|Activities|Announcements/.test(c)), JSON.stringify(r));
	return "EN: ngày dd/mm/yyyy, chuyên mục tiếng Anh (VI đã kiểm ở NW-01)";
});
await T("RS-01", async () => {
	const post = postsVi.find((x) => !x.link.includes("/en/"));
	const bad = [];
	const p = await visitor();
	for (const path of ["/", "/en/", "/tin-tuc/", new URL(post.link).pathname]) {
		for (const w of [320, 390, 440, 768, 1024, 1280, 1440]) {
			await resize(p, w);
			await go(p, path);
			const over = await p.evaluate(() => document.documentElement.scrollWidth - innerWidth);
			if (over > 1) bad.push(`${path} @${w}: tràn ${over}px`);
		}
	}
	await p.close();
	assert(!bad.length, bad.join("; "));
	return "4 trang × 7 độ rộng: không có thanh cuộn ngang";
});
await T("RS-02", async () => {
	const p = await visitor(1920, 1080);
	await go(p, "/");
	const r = await p.$eval(".elio-site", (e) => { const b = e.getBoundingClientRect(); return [b.width, b.left]; });
	await shot(p, "home-1920");
	await p.close();
	assert(r[0] === 1280 && r[1] === 320, JSON.stringify(r));
	return "1280px ở giữa (lề 320px mỗi bên)";
});
blocked("PF-01", "Lighthouse phải chạy trên domain thật (bản thử chạy PHP trong trình duyệt, chậm hơn hosting nhiều)");
blocked("PF-03", "Cần plugin cache trên hosting");
await T("SE-01", async () => {
	const p = await visitor();
	await go(p, "/");
	const home = await p.$eval('meta[property="og:image"]', (m) => m.content);
	const post = postsVi.find((x) => !x.link.includes("/en/"));
	await go(p, new URL(post.link).pathname);
	const pi = await p.$eval('meta[property="og:image"]', (m) => m.content);
	const ok = await p.evaluate(async (u) => (await fetch(u)).status, home);
	await p.close();
	assert(home.endsWith("/assets/img/og.png") && ok === 200 && /\/uploads\//.test(pi), home + " | " + pi);
	return { status: "Pass", note: "thẻ og:image đúng (trang chủ: og.png, bài: ảnh đại diện). Xem thật trên Zalo/Facebook sau khi có domain" };
});
await T("SE-02", async () => {
	const p = await visitor();
	await go(p, "/");
	const r = await p.evaluate(async () => { const links = [...document.querySelectorAll('link[rel="icon"], link[rel="apple-touch-icon"]')].map((l) => l.href); const st = await Promise.all(links.map(async (u) => (await fetch(u)).status)); return links.map((l, i) => l.split("/").pop() + "=" + st[i]); });
	await p.close();
	assert(r.length === 3 && r.every((x) => x.endsWith("=200")), r.join(", "));
	return { status: "Pass", note: r.join(", ") + ". Phần “Thêm vào màn hình chính” cần iPhone thật" };
});
await T("SE-03", async () => {
	const p = await visitor();
	await go(p, "/");
	const n = await p.$$eval("h1", (h) => h.length);
	await p.close();
	assert(n === 1, n + " thẻ h1");
});
await T("AC-01", async () => {
	const p = await visitor();
	await go(p, "/");
	const seen = [];
	for (let i = 0; i < 40; i++) {
		await p.keyboard.press("Tab");
		seen.push(await p.evaluate(() => { const e = document.activeElement; const cs = getComputedStyle(e); return { tag: e.tagName + (e.className && typeof e.className === "string" ? "." + e.className.split(" ")[0] : ""), outline: cs.outlineStyle !== "none" && parseFloat(cs.outlineWidth) > 0, color: cs.outlineColor }; }));
	}
	await p.close();
	const bad = seen.filter((s) => !s.outline);
	assert(!bad.length, bad.length + "/40 phần tử không có viền focus: " + [...new Set(bad.map((b) => b.tag))].join(", "));
	return "40 lần Tab: phần tử nào cũng có viền focus (" + seen[0].color + ")";
});
await T("AC-02", async () => {
	const p = await visitor(390, 844);
	await p.emulateMediaFeatures([{ name: "prefers-reduced-motion", value: "reduce" }]);
	await go(p, "/");
	const anim = await p.$eval(".elio-campus [data-hand]", (e) => getComputedStyle(e).animationName);
	await resize(p, 1440);
	await p.evaluate(() => document.getElementById("grow").scrollIntoView());
	const card = await p.$("#grow .group > div:last-child");
	await card.hover();
	await sleep(400);
	const tr = await p.evaluate(() => getComputedStyle(document.querySelector("#grow .group > div:last-child")).translate);
	await p.close();
	assert(anim === "none" && (tr === "none" || tr === "0px"), `animation=${anim}, translate=${tr}`);
	return "bàn tay không nhún, thẻ không nảy khi rê chuột";
});
await T("PF-02", async () => {
	const errs = consoleErrors.filter((e) => !/favicon|wp-admin|autosave|heartbeat/i.test(e) && !/status of (404|429|502|503)|ERR_INTERNET_DISCONNECTED/.test(e));
	assert(!errs.length, errs.slice(0, 5).join(" | "));
	return "không có lỗi Console trên các trang khách xem trong suốt lượt test";
});

/* ===================================================== RATE LIMIT (last: it locks the form) */

await T("TF-15", async () => {
	const p = await visitor();
	await go(p, "/");
	const codes = [];
	for (let i = 0; i < 6 - saved; i++) {
		codes.push(await p.evaluate(async (d) => (await fetch("/wp-json/elio/v1/tour", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ name: "TEST – giới hạn", phone: "0834123456", age: "2-3y", date: d }) })).status, future()));
	}
	await p.close();
	const ok = codes.filter((c) => c === 200).length;
	assert(ok + saved === 5 && codes[codes.length - 1] === 429, `trước đó ${saved} đơn, rồi: ${codes.join(",")}`);
	return `${saved} đơn ở các case trước + ${ok} đơn nữa = 5 lưu được, đơn thứ 6 bị chặn (429)`;
});
blocked("TF-17", "Error log của hosting thật");

/* ------------------------------------------------------------------ cleanup */

for (const id of created.posts) await admin.api("DELETE", "/wp/v2/posts/" + id + "?force=true");
for (const id of created.pages) await admin.api("DELETE", "/wp/v2/pages/" + id + "?force=true");
for (const id of created.users) await admin.api("DELETE", "/wp/v2/users/" + id + "?force=true&reassign=1");
await setSettings(admin, { webhook_url: "", secret: "" });
await browser.close();
fs.writeFileSync(OUT + "results.json", JSON.stringify(results, null, 2));
const sum = results.reduce((a, r) => ((a[r.status] = (a[r.status] || 0) + 1), a), {});
console.log("\nSUMMARY", JSON.stringify(sum));
