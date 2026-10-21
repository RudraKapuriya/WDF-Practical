"use strict";

var body = document.body;
var nav = document.querySelector(".nav");
var header = document.querySelector(".header-inner");
var themeKey = "vidya-setu-theme";

function makeButton(text, className) {
	var button = document.createElement("button");
	button.type = "button";
	button.className = "js-control " + className;
	button.textContent = text;
	return button;
}

function setupHeader() {
	var oldTheme = localStorage.getItem(themeKey);
	if (header) {
		if (oldTheme === "dark") body.classList.add("theme-dark");
		var themeButton = makeButton(oldTheme === "dark" ? "Light mode" : "Dark mode", "theme-toggle");
		themeButton.setAttribute("aria-pressed", oldTheme === "dark");
		themeButton.addEventListener("click", function () {
			var darkMode = body.classList.toggle("theme-dark");
			localStorage.setItem(themeKey, darkMode ? "dark" : "light");
			themeButton.textContent = darkMode ? "Light mode" : "Dark mode";
			themeButton.setAttribute("aria-pressed", darkMode);
		});
		header.appendChild(themeButton);
	}
	if (nav && header) {
		var menuButton = makeButton("Menu", "menu-toggle");
		menuButton.setAttribute("aria-controls", "main-navigation");
		menuButton.setAttribute("aria-expanded", "false");
		nav.id = "main-navigation";
		nav.classList.add("js-collapsed");
		menuButton.addEventListener("click", function () {
			var menuOpen = nav.classList.toggle("js-open");
			nav.classList.toggle("js-collapsed", !menuOpen);
			menuButton.setAttribute("aria-expanded", menuOpen);
		});
		header.insertBefore(menuButton, nav);
	}
	var pageHeader = document.querySelector(".header");
	if (pageHeader) {
		var banner = document.createElement("div");
		var close = makeButton("×", "js-close");
		banner.className = "js-banner";
		banner.setAttribute("role", "status");
		banner.innerHTML = "<strong>Portal update:</strong> Browse the Notices page for the latest college updates.";
		close.setAttribute("aria-label", "Dismiss notification");
		close.addEventListener("click", function () { banner.remove(); });
		banner.appendChild(close);
		pageHeader.after(banner);
	}
}

function showModal(title, message) {
	var background = document.createElement("div");
	var box = document.createElement("section");
	var close = makeButton("×", "js-modal-close");
	var heading = document.createElement("h2");
	var text = document.createElement("p");
	background.className = "js-modal-backdrop";
	box.className = "js-modal";
	close.setAttribute("aria-label", "Close dialog");
	box.setAttribute("role", "dialog");
	box.setAttribute("aria-modal", "true");
	heading.textContent = title;
	text.textContent = message;
	close.addEventListener("click", function () { background.remove(); });
	background.addEventListener("click", function (event) {
		if (event.target === background) background.remove();
	});
	box.appendChild(close);
	box.appendChild(heading);
	box.appendChild(text);
	background.appendChild(box);
	document.body.appendChild(background);
	close.focus();
}

function addNoticeEvents() {
	document.querySelectorAll(".notice").forEach(function (notice) {
		notice.tabIndex = 0;
		notice.setAttribute("role", "button");
		function openNotice() { showModal("Announcement details", notice.textContent.trim()); }
		notice.addEventListener("click", openNotice);
		notice.addEventListener("keydown", function (event) {
			if (event.key === "Enter" || event.key === " ") {
				event.preventDefault();
				openNotice();
			}
		});
	});
}

setupHeader();
addNoticeEvents();
