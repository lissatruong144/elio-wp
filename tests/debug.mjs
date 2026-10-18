// Which rule sets #top's margin-left on the live site (with the new CSS injected)?
import puppeteer from "puppeteer-core";
import fs from "fs";
const css = fs.readFileSync("D:/ELIO/elio-wp/elio/assets/css/elio.css", "utf8");
const browser = await puppeteer.launch({ executablePath: "C:/Program Files/Google/Chrome/Application/chrome.exe", headless: true, args: ["--no-proxy-server"] });
const p = await browser.newPage();
await p.setViewport({ width: 1920, height: 1080 });
await p.goto("https://totalab.io.vn/?nocache=" + Date.now(), { waitUntil: "load", timeout: 120000 });
const out = await p.evaluate((css) => {
	document.querySelectorAll('link[id="elio-css"]').forEach((l) => l.remove());
	const s = document.createElement("style");
	s.textContent = css;
	document.head.appendChild(s);
	const top = document.getElementById("top");
	const cs = getComputedStyle(top);
	const parent = top.parentElement;
	// Every stylesheet rule that matches #top and touches margin.
	const hits = [];
	for (const sh of document.styleSheets) {
		let rules;
		try { rules = sh.cssRules; } catch (e) { continue; }
		const walk = (list) => {
			for (const r of list) {
				if (r.cssRules && !r.selectorText) walk(r.cssRules);
				else if (r.selectorText && /margin/.test(r.cssText)) {
					try { if (top.matches(r.selectorText)) hits.push(r.cssText.slice(0, 220)); } catch (e) {}
				}
			}
		};
		walk(rules);
	}
	return { marginLeft: cs.marginLeft, bleed: cs.getPropertyValue("--bleed"), parent: parent.className, parentDisplay: getComputedStyle(parent).display, hits };
}, css);
console.log(JSON.stringify(out, null, 1));
await browser.close();
