// TF-08 / TF-09 against the real Google Sheet: configures Settings → ELIO with the webhook URL and
// secret from ../elio-web/.env.local (never printed), submits one booking through the real form,
// then clears the settings again.
import puppeteer from "puppeteer-core";
import fs from "fs";

const BASE = "http://127.0.0.1:9400";
const env = Object.fromEntries(
	fs.readFileSync("D:/ELIO/elio-web/.env.local", "utf8").split(/\r?\n/).filter((l) => /^[A-Z_]+=/.test(l)).map((l) => [l.slice(0, l.indexOf("=")), l.slice(l.indexOf("=") + 1).trim()])
);
if (!env.GOOGLE_SHEET_WEBHOOK_URL || !env.GOOGLE_SHEET_SECRET) throw new Error("missing values in .env.local");

const browser = await puppeteer.launch({ executablePath: "C:/Program Files/Google/Chrome/Application/chrome.exe", headless: true, args: ["--no-proxy-server"] });

async function setSettings(fields) {
	const ctx = await browser.createBrowserContext();
	const a = await ctx.newPage();
	a.setDefaultTimeout(180000);
	await a.goto(BASE + "/wp-admin/options-general.php?page=elio", { waitUntil: "load" });
	for (const [k, v] of Object.entries(fields)) await a.$eval("#elio-" + k, (el, v) => (el.value = v), v);
	await Promise.all([a.waitForNavigation({ waitUntil: "load" }), a.click('input[type=submit][value="Lưu cài đặt"]')]);
	const status = await a.evaluate(() => [...document.querySelectorAll(".form-table td")].map((t) => t.textContent).find((t) => t.includes("cấu hình")));
	await ctx.close();
	return status;
}

console.log("settings:", await setSettings({ webhook_url: env.GOOGLE_SHEET_WEBHOOK_URL, secret: env.GOOGLE_SHEET_SECRET }));

const ctx = await browser.createBrowserContext();
const p = await ctx.newPage();
p.setDefaultTimeout(180000);
await p.setCookie({ name: "playground_auto_login_already_happened", value: "1", url: BASE });
await p.setViewport({ width: 1440, height: 900 });
let apiResponse = null;
p.on("response", async (r) => {
	if (r.url().includes("/elio/v1/tour")) apiResponse = { status: r.status(), body: await r.text().catch(() => "") };
});
await p.goto(BASE + "/", { waitUntil: "load" });
const stamp = new Date().toLocaleString("vi-VN", { timeZone: "Asia/Ho_Chi_Minh" });
const date = new Date(Date.now() + 5 * 864e5).toISOString().slice(0, 10);
await p.evaluate(
	(v) => {
		const f = document.querySelector("[data-elio-tour] form");
		for (const [k, val] of Object.entries(v)) {
			const el = f.querySelector(`[name="${k}"]`);
			el.value = val;
			el.dispatchEvent(new Event("input", { bubbles: true }));
		}
		f.querySelector("button[type=submit]").click();
	},
	{ name: "TEST – WordPress (TF-08)", phone: "0834123456", age: "2-3y", date, message: "Đơn thử từ bản WordPress, gửi lúc " + stamp + ". Xoá dòng này sau khi kiểm tra." }
);
await p.waitForFunction(() => !document.querySelector("[data-elio-tour-done]").hidden || !document.querySelector("[data-elio-tour-error]").hidden, { timeout: 120000 });
const done = await p.evaluate(() => !document.querySelector("[data-elio-tour-done]").hidden);
await p.evaluate(() => document.getElementById("tour").scrollIntoView());
await p.screenshot({ path: "D:/ELIO/elio-wp/tests/out/tf08-real.png" });
console.log("form:", done ? "màn Cảm ơn" : "BÁO LỖI");
console.log("api:", JSON.stringify(apiResponse));
console.log("sent at:", stamp, "| visit date:", date.split("-").reverse().join("/"));

console.log("settings cleared:", await setSettings({ webhook_url: "" }));
await browser.close();
