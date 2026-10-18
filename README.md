# ELIO – theme WordPress

Theme WordPress cho website ELIO International Preschool, chuyển từ bản Next.js (`../elio-web`) và giữ nguyên thiết kế. Khách tự sửa nội dung trong trình soạn thảo Gutenberg. Website có hai ngôn ngữ Việt/Anh qua Polylang, có trang Tin tức, và form đặt lịch tham quan ghi vào Google Sheet (dùng chung `Code.gs` với bản cũ).

```
elio-wp/
├── elio/                 ← theme (thư mục được tải lên WordPress)
│   ├── functions.php, theme.json, style.css
│   ├── inc/              ← PHP: nội dung mẫu, các section, header/footer/form, cài đặt
│   ├── templates/        ← trang chủ, trang, bài viết, danh sách tin, 404
│   ├── assets/css/elio.css   ← CSS đã build (không sửa tay)
│   ├── assets/js/elio.js     ← tương tác: menu, tab, slider, timeline, form
│   └── assets/img, assets/fonts
├── build/                ← công cụ build (không tải lên)
│   ├── tailwind.css      ← nguồn của elio.css
│   ├── blueprint.json    ← dựng WordPress thử trên máy
│   └── zip.ps1
└── dist/elio-theme.zip   ← file để tải lên WordPress
```

## Đưa lên hosting (iNET hoặc bất kỳ hosting WordPress nào)

Yêu cầu: WordPress 6.5 trở lên, PHP 7.4 trở lên. Không cần Node.js trên hosting.

1. **Cài WordPress** trên hosting (iNET có trình cài sẵn trong DirectAdmin/cPanel).
2. **Cài Polylang**: Plugins → Add New → tìm “Polylang” → Install → Activate. Trong trình hướng dẫn của Polylang, thêm **Tiếng Việt** trước (làm ngôn ngữ mặc định), rồi thêm **English**.
3. **Cài theme**: Appearance → Themes → Add New → Upload Theme → chọn `dist/elio-theme.zip` → Install → Activate.
   - Nếu báo file quá lớn (zip khoảng 8 MB), nhờ iNET tăng `upload_max_filesize`, hoặc giải nén rồi tải thư mục `elio` lên `wp-content/themes/` qua File Manager/FTP.
4. **Tạo nội dung**: Settings → **ELIO** → bấm **Tạo nội dung mẫu**. Nút này tạo trang chủ VI/EN, trang Tin tức/News, menu, chuyên mục và 3 bài mẫu cho mỗi ngôn ngữ. Nó cũng đặt trang chủ làm trang đầu và bật đường dẫn đẹp. Chạy lại nhiều lần cũng không tạo trùng.
5. **Nối form với Google Sheet**: vẫn ở Settings → ELIO, mục 2:
   - *Web app URL*: link `/exec` của Apps Script (chính là `GOOGLE_SHEET_WEBHOOK_URL` trong `elio-web/.env.local`).
   - *SECRET*: giá trị `SECRET` trong `elio-web/google-apps-script/Code.paste-me.gs` (trùng `GOOGLE_SHEET_SECRET` trong `.env.local`).
   - Có thể khai báo trong `wp-config.php` thay vì nhập vào trang cài đặt: `define( 'ELIO_SHEET_WEBHOOK_URL', '…' );` và `define( 'ELIO_SHEET_SECRET', '…' );`.
6. **Thông tin liên hệ và mạng xã hội** (footer): Settings → ELIO, mục 3–4.
7. **Khẩu hiệu tiếng Anh**: Languages → Translations → dịch “Tagline” (dòng mô tả site).
8. **Thử form**: gửi một đơn tên “TEST – …”, kiểm tra Google Sheet và email thông báo, rồi xoá dòng thử.

## Hướng dẫn cho khách sửa nội dung

- **Trang chủ**: Pages → “Trang chủ” (bản tiếng Anh là “Home”) → Edit. Bấm vào chữ hoặc ảnh để sửa trực tiếp. Muốn đổi ảnh thì chọn ảnh → Replace.
- **Mốc giờ, trụ cột Why ELIO, khu vực Campus, giáo viên**: trong trình soạn thảo, mỗi mục là một thẻ nằm trong khung viền vàng nét đứt. Muốn thêm thì chọn thẻ → nhân bản (Ctrl+Shift+D) → sửa. Muốn bỏ thì xoá thẻ. Trên website, các thẻ này tự ghép thành tab, slider và timeline.
  - Giáo viên nam: thêm class `is-he` cho thẻ (Block → Advanced → Additional CSS class) để nhãn đổi thành “WHO HE IS…”.
- **Thêm lại section đã xoá**: nút **+** → Patterns → “ELIO – Trang chủ” (hoặc “ELIO – Home (English)”).
- **Viết bài**: Posts → Add New. Chọn ảnh đại diện (Featured image), chuyên mục, và viết đoạn tóm tắt (Excerpt) để hiện trên thẻ bài. Muốn có bản tiếng Anh, bấm dấu **+** ở cột ngôn ngữ (hộp Languages) để tạo bản dịch.
- **Menu**: Appearance → Menus. Mỗi ngôn ngữ có một menu riêng: “Menu chính (VI)” và “Main menu (EN)”. Link đến từng phần của trang chủ có dạng `https://ten-mien/#why`, `#approach`, `#campus`, `#tuition`, `#parents`, `#tour`, `#news`.
- **Trang mới có giao diện rộng** (dùng các section như trang chủ): khi tạo trang, chọn Template → “Trang rộng”.

## Sửa code và tự deploy qua GitHub

Push lên nhánh `main` → GitHub Actions (`.github/workflows/deploy.yml`) build CSS rồi upload thư mục `elio/` lên hosting qua FTP. Chỉ file thay đổi được gửi lên. Nội dung trang/bài viết trên WordPress không bị ảnh hưởng.

Cài đặt một lần:
1. DirectAdmin → **FTP Management** → tạo tài khoản FTP, thư mục gốc `domains/totalab.io.vn/public_html/wp-content/themes/elio`.
2. GitHub → repo → **Settings → Secrets and variables → Actions** → thêm `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`. Nếu hosting không hỗ trợ FTPS, thêm biến (tab Variables) `FTP_PROTOCOL` = `ftp`.

Hằng ngày: sửa trong VS Code → (thử bằng `npm run preview`) → commit + push → xem tab **Actions** trên GitHub → wp-admin **LiteSpeed Cache → Purge All**.

## Phát triển

```bash
npm install
npm run build     # build lại assets/css/elio.css (bắt buộc sau khi thêm/sửa class Tailwind)
npm run preview   # WordPress thử tại http://127.0.0.1:9400 (tự cài Polylang + nội dung mẫu)
npm run zip       # build CSS rồi đóng gói dist/elio-theme.zip
```

Ghi chú kỹ thuật:
- Class Tailwind được biên dịch với `!important`. CSS của WordPress không nằm trong cascade layer nên sẽ đè Tailwind nếu không làm vậy.
- Block ảnh không dùng `sizeSlug`, vì class `size-full` của WordPress trùng tên với utility của Tailwind.
- Nội dung trang chủ khi tạo lần đầu lấy từ `inc/content.php` và `inc/sections.php`. Sau khi trang đã được tạo, nội dung nằm trong cơ sở dữ liệu, nên sửa các file này không làm thay đổi trang có sẵn.
