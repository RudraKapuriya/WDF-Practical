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

function addThemeButton() {
	var oldTheme = localStorage.getItem(themeKey);
	if (!header) return;
	if (oldTheme === "dark") body.classList.add("theme-dark");
	var button = makeButton(oldTheme === "dark" ? "Light mode" : "Dark mode", "theme-toggle");
	button.setAttribute("aria-pressed", oldTheme === "dark");
	button.addEventListener("click", function () {
		var darkMode = body.classList.toggle("theme-dark");
		localStorage.setItem(themeKey, darkMode ? "dark" : "light");
		button.textContent = darkMode ? "Light mode" : "Dark mode";
		button.setAttribute("aria-pressed", darkMode);
	});
	header.appendChild(button);
}

function addMenuButton() {
	if (!nav || !header) return;
	var button = makeButton("Menu", "menu-toggle");
	button.setAttribute("aria-controls", "main-navigation");
	button.setAttribute("aria-expanded", "false");
	nav.id = "main-navigation";
	nav.classList.add("js-collapsed");
	button.addEventListener("click", function () {
		var menuOpen = nav.classList.toggle("js-open");
		nav.classList.toggle("js-collapsed", !menuOpen);
		button.setAttribute("aria-expanded", menuOpen);
	});
	header.insertBefore(button, nav);
}

function addNotification() {
	var pageHeader = document.querySelector(".header");
	if (!pageHeader) return;
	var banner = document.createElement("div");
	var close = makeButton("×", "js-close");
	banner.className = "js-banner";
	banner.setAttribute("role", "status");
	banner.innerHTML = "<strong>Portal update:</strong> Mid-semester examinations begin on 25 August.";
	close.setAttribute("aria-label", "Dismiss notification");
	close.addEventListener("click", function () { banner.remove(); });
	banner.appendChild(close);
	pageHeader.after(banner);
}

addThemeButton();
addMenuButton();
addNotification();
