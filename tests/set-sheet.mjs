// Points the local preview's Settings → ELIO at the real Google Sheet (values from
// ../elio-web/.env.local, never printed). `node tests/set-sheet.mjs off` clears the URL again.
import puppeteer from "puppeteer-core";
import fs from "fs";

const BASE = "http://127.0.0.1:9400";
const off = process.argv[2] === "off";
const env = Object.fromEntries(
	fs.readFileSync("D:/ELIO/elio-web/.env.local", "utf8").split(/\r?\n/).filter((l) => /^[A-Z_]+=/.test(l)).map((l) => [l.slice(0, l.indexOf("=")), l.slice(l.indexOf("=") + 1).trim()])
);
const fields = off ? { webhook_url: "" } : { webhook_url: env.GOOGLE_SHEET_WEBHOOK_URL, secret: env.GOOGLE_SHEET_SECRET };

const browser = await puppeteer.launch({ executablePath: "C:/Program Files/Google/Chrome/Application/chrome.exe", headless: true, args: ["--no-proxy-server"] });
const a = await browser.newPage();
a.setDefaultTimeout(180000);
await a.goto(BASE + "/wp-admin/options-general.php?page=elio", { waitUntil: "load" });
for (const [k, v] of Object.entries(fields)) await a.$eval("#elio-" + k, (el, v) => (el.value = v), v);
await Promise.all([a.waitForNavigation({ waitUntil: "load" }), a.click('input[type=submit][value="Lưu cài đặt"]')]);
console.log(await a.evaluate(() => [...document.querySelectorAll(".form-table td")].map((t) => t.textContent).find((t) => t.includes("cấu hình")).trim()));
await browser.close();
