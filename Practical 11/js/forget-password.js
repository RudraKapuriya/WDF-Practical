"use strict";

var themeKey = "vidya-setu-theme";

function makeButton(text, className) {
	var button = document.createElement("button");
	button.type = "button";
	button.className = "js-control " + className;
	button.textContent = text;
	return button;
}

function addThemeButton() {
	var brand = document.querySelector(".auth-brand");
	var oldTheme = localStorage.getItem(themeKey);
	if (!brand) return;
	if (oldTheme === "dark") document.body.classList.add("theme-dark");
	var button = makeButton(oldTheme === "dark" ? "Light mode" : "Dark mode", "theme-toggle");
	button.setAttribute("aria-pressed", oldTheme === "dark");
	button.addEventListener("click", function () {
		var darkMode = document.body.classList.toggle("theme-dark");
		localStorage.setItem(themeKey, darkMode ? "dark" : "light");
		button.textContent = darkMode ? "Light mode" : "Dark mode";
		button.setAttribute("aria-pressed", darkMode);
	});
	brand.appendChild(button);
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

document.querySelectorAll("form").forEach(function (form) {
	function confirmForm(event) {
		event.preventDefault();
		showModal("Thank you", "Your request has been received by the Vidya Setu Student Hub.");
	}
	form.addEventListener("submit", confirmForm);
	form.querySelectorAll("button").forEach(function (button) {
		if (!button.classList.contains("password-toggle")) button.addEventListener("click", confirmForm);
	});
});

addThemeButton();
