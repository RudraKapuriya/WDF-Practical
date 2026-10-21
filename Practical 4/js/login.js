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

function addFormConfirmation() {
	document.querySelectorAll("form").forEach(function (form) {
		function confirmForm(event) {
			event.preventDefault();
			showModal("Thank you", "Your request has been received by the Vidya Setu Student Hub.");
		}
		form.addEventListener("submit", confirmForm);
		form.querySelectorAll("button").forEach(function (button) {
			if (!button.classList.contains("password-toggle")) {
				button.addEventListener("click", confirmForm);
			}
		});
	});
}

function addPasswordButtons() {
	document.querySelectorAll('input[type="password"]').forEach(function (field, number) {
		var wrapper = document.createElement("span");
		var button = document.createElement("button");
		var fieldId = field.id || "password-field-" + (number + 1);
		field.id = fieldId;
		wrapper.className = "password-control";
		button.type = "button";
		button.className = "password-toggle";
		button.setAttribute("aria-label", "Show password");
		button.setAttribute("aria-controls", fieldId);
		button.title = "Show password";
		button.innerHTML = '<i class="fa-solid fa-eye" aria-hidden="true"></i>';
		button.addEventListener("click", function () {
			var showing = field.type === "text";
			field.type = showing ? "password" : "text";
			button.setAttribute("aria-label", showing ? "Show password" : "Hide password");
			button.title = showing ? "Show password" : "Hide password";
			button.innerHTML = showing
				? '<i class="fa-solid fa-eye" aria-hidden="true"></i>'
				: '<i class="fa-solid fa-eye-slash" aria-hidden="true"></i>';
		});
		field.parentNode.insertBefore(wrapper, field);
		wrapper.appendChild(field);
		wrapper.appendChild(button);
	});
}

addThemeButton();
addFormConfirmation();
addPasswordButtons();
