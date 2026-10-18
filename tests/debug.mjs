// Ad-hoc investigation of NV-07 (logo on a post) and FO-03 (footer quick links).
import puppeteer from "puppeteer-core";
const BASE = "http://127.0.0.1:9400";
const browser = await puppeteer.launch({ executablePath: "C:/Program Files/Google/Chrome/Application/chrome.exe", headless: true, args: ["--no-proxy-server"] });
const ctx = await browser.createBrowserContext();
const p = await ctx.newPage();
p.setDefaultTimeout(120000);
await p.setCookie({ name: "playground_auto_login_already_happened", value: "1", url: BASE });
await p.setViewport({ width: 1440, height: 900 });

const posts = await (await fetch(BASE + "/wp-json/wp/v2/posts?per_page=20&_fields=link", { headers: { Cookie: "playground_auto_login_already_happened=1" } })).json();
console.log("posts:", posts.map((x) => x.link.replace(BASE, "")).join(" "));
const vi = posts.find((x) => !x.link.includes("/en/"));
await p.goto(vi.link, { waitUntil: "load", timeout: 180000 });
console.log("post", vi.link.replace(BASE, ""), "logo:", await p.$eval("header > a", (a) => a.getAttribute("href")), "lang:", await p.$eval("html", (h) => h.lang));

await p.goto(BASE + "/", { waitUntil: "load", timeout: 180000 });
await p.addStyleTag({ content: "html{scroll-behavior:auto!important}" });
const links = await p.$$eval("footer a", (as) => as.filter((a) => a.textContent.trim().startsWith("→")).map((a) => a.getAttribute("href")));
for (const href of links) {
	await p.evaluate(() => scrollTo(0, 0));
	const before = await p.evaluate(() => [location.hash, scrollY]);
	await p.$eval(`footer a[href="${href}"]`, (a) => a.click());
	await new Promise((r) => setTimeout(r, 700));
	const id = href.split("#")[1];
	const after = await p.evaluate((id) => [location.hash, Math.round(scrollY), Math.round(document.getElementById(id).getBoundingClientRect().top)], id);
	console.log(href.replace(BASE, ""), "before", before, "after", after);
}
await browser.close();
