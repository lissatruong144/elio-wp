/**
 * Editor side of the theme's server-rendered blocks: each one shows a live preview of what
 * PHP renders on the site and has nothing to edit in place (its text comes from
 * Settings → ELIO and the menus).
 */
(function (wp) {
	var el = wp.element.createElement;
	var SSR = wp.serverSideRender;
	var blocks = {
		"elio/header": ["ELIO – Header", "menu"],
		"elio/footer": ["ELIO – Footer", "editor-insertmore"],
		"elio/tour-form": ["ELIO – Form đặt lịch tham quan", "feedback"],
		"elio/archive-header": ["ELIO – Tiêu đề trang tin tức", "heading"],
		"elio/back-to-news": ["ELIO – Link về trang tin tức", "undo"],
		"elio/not-found": ["ELIO – Trang 404", "warning"],
		"elio/no-posts": ["ELIO – Chưa có bài viết", "info"],
	};
	Object.keys(blocks).forEach(function (name) {
		wp.blocks.registerBlockType(name, {
			apiVersion: 3,
			title: blocks[name][0],
			icon: blocks[name][1],
			category: "theme",
			supports: { html: false, reusable: false },
			edit: function () {
				var props = wp.blockEditor ? wp.blockEditor.useBlockProps() : {};
				return el("div", props, el(SSR, { block: name }));
			},
			save: function () {
				return null;
			},
		});
	});
})(window.wp);
