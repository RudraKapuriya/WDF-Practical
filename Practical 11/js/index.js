"use strict";

var body = document.body;
var header = document.querySelector(".header-inner");
var nav = document.querySelector(".nav");
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
	banner.innerHTML = "<strong>Portal update:</strong> Browse the Notices page for the latest college updates.";
	close.setAttribute("aria-label", "Dismiss notification");
	close.addEventListener("click", function () { banner.remove(); });
	banner.appendChild(close);
	pageHeader.after(banner);
}

function addSlider() {
	var card = document.querySelector(".hero-card");
	if (!card) return;
	var slider = document.createElement("div");
	var controls = document.createElement("div");
	var previous = makeButton("Previous", "js-slider-prev");
	var next = makeButton("Next", "js-slider-next");
	var status = document.createElement("span");
	var current = 0;
	var slides = [
		["Quick access", "Use the navigation to reach attendance, results, fees, and announcements."],
		["Stay informed", "Read the latest college notices and examination updates in one place."],
		["Track progress", "Review your academic information regularly to stay on schedule."],
	];
	slider.className = "js-slider";
	slider.setAttribute("aria-label", "Student hub highlights");
	controls.className = "js-slider-controls";
	slides.forEach(function (item, index) {
		var slide = document.createElement("div");
		slide.className = "js-slide";
		slide.hidden = index !== 0;
		slide.innerHTML = "<strong>" + item[0] + "</strong><br>" + item[1];
		slider.appendChild(slide);
	});
	function showSlide() {
		var allSlides = slider.querySelectorAll(".js-slide");
		allSlides.forEach(function (slide, index) { slide.hidden = index !== current; });
		status.textContent = current + 1 + " of " + allSlides.length;
	}
	previous.addEventListener("click", function () {
		current = (current - 1 + slides.length) % slides.length;
		showSlide();
	});
	next.addEventListener("click", function () {
		current = (current + 1) % slides.length;
		showSlide();
	});
	controls.appendChild(previous);
	controls.appendChild(status);
	controls.appendChild(next);
	slider.appendChild(controls);
	card.appendChild(slider);
	showSlide();
}

addThemeButton();
addMenuButton();
addNotification();
addSlider();
