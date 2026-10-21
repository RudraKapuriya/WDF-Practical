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
addPasswordButtons();
