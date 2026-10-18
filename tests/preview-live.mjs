// Previews local CSS/JS changes on the live site without deploying: loads https://totalab.io.vn,
// swaps in the freshly built elio.css, applies the scrollbar variable, takes screenshots.
import puppeteer from "puppeteer-core";
import fs from "fs";

const OUT = "D:/ELIO/elio-wp/tests/out/";
fs.mkdirSync(OUT, { recursive: true });
const css = fs.readFileSync("D:/ELIO/elio-wp/elio/assets/css/elio.css", "utf8");
// Templates without overflow-x-clip on the page frame (as in the new front-page.html).
const browser = await puppeteer.launch({ executablePath: "C:/Program Files/Google/Chrome/Application/chrome.exe", headless: true, args: ["--no-proxy-server"] });
const p = await browser.newPage();
for (const [w, h] of [[1920, 1080], [1440, 900], [1280, 800], [390, 844]]) {
	await p.setViewport({ width: w, height: h, isMobile: w < 600, hasTouch: w < 600 });
	await p.goto("https://totalab.io.vn/?nocache=" + Date.now(), { waitUntil: "load", timeout: 120000 });
	await p.evaluate((css) => {
		document.querySelectorAll('link[id="elio-css"]').forEach((l) => l.remove());
		const s = document.createElement("style");
		s.textContent = css;
		document.head.appendChild(s);
		document.querySelector(".elio-site").classList.remove("overflow-x-clip");
		document.documentElement.style.setProperty("--elio-sb", innerWidth - document.documentElement.clientWidth + "px");
	}, css);
	await new Promise((r) => setTimeout(r, 600));
	const m = await p.evaluate(() => {
		const r = (s) => document.querySelector(s)?.getBoundingClientRect();
		const top = r("#top"), logo = r("header > a"), h1 = r("#top h1"), badge = r("#top .lg\\:right-14"), cta = r("header .elio-pill");
		return { vw: document.documentElement.clientWidth, heroL: Math.round(top.left), heroR: Math.round(top.right), heroH: Math.round(top.height), logoL: Math.round(logo.left), h1L: Math.round(h1.left), badgeR: badge ? Math.round(badge.right) : null, ctaR: cta ? Math.round(cta.right) : null, hscroll: document.documentElement.scrollWidth - document.documentElement.clientWidth };
	});
	console.log(w + "px", JSON.stringify(m));
	await p.screenshot({ path: OUT + "hero-bleed-" + w + ".png" });
}
await browser.close();
