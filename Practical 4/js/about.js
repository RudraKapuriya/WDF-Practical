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

function addFaq() {
	var title = document.querySelector(".page-head h1");
	var cards = document.querySelector(".about-grid");
	if (!title || title.textContent.trim() !== "About Vidya Setu" || !cards) return;

	var faq = document.createElement("section");
	var questions = [
		["What can I find in the portal?", "You can review attendance, results, fees, announcements, and support information."],
		["How do I contact student support?", "Open the Contact page to find the support office address, phone number, email, and message form."],
		["Will my theme preference be remembered?", "Yes. The selected light or dark theme is saved in your browser using localStorage."],
	];
	faq.className = "js-faq";
	faq.innerHTML = "<h2>Frequently Asked Questions</h2>";
	questions.forEach(function (item) {
		var details = document.createElement("details");
		var summary = document.createElement("summary");
		var answer = document.createElement("p");
		summary.textContent = item[0];
		answer.textContent = item[1];
		details.appendChild(summary);
		details.appendChild(answer);
		faq.appendChild(details);
	});
	cards.after(faq);
}

addThemeButton();
addMenuButton();
addNotification();
addFaq();
