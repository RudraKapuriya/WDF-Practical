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

function addRegistrationValidation() {
	var form = document.querySelector("#student-registration");
	if (!form) return;

	var genderOptions = form.querySelectorAll('input[name="gender"]');
	var fields = {
		name: form.elements.name,
		username: form.elements.username,
		email: form.elements.email,
		mobile: form.elements.mobile,
		password: form.elements.password,
		confirmPassword: form.elements.confirmPassword,
		course: form.elements.course,
		year: form.elements.year,
		gender: genderOptions[0],
		terms: form.elements.terms,
	};
	var errors = {
		name: document.querySelector("#name-error"),
		username: document.querySelector("#username-error"),
		email: document.querySelector("#email-error"),
		mobile: document.querySelector("#mobile-error"),
		password: document.querySelector("#password-error"),
		confirmPassword: document.querySelector("#confirm-password-error"),
		course: document.querySelector("#course-error"),
		year: document.querySelector("#year-error"),
		gender: document.querySelector("#gender-error"),
		terms: document.querySelector("#terms-error"),
	};
	var strength = document.querySelector("#password-strength");
	var checks = [
		{ label: "8 characters", test: /.{8,}/ },
		{ label: "an uppercase letter", test: /[A-Z]/ },
		{ label: "a lowercase letter", test: /[a-z]/ },
		{ label: "a number", test: /[0-9]/ },
		{ label: "a symbol", test: /[^A-Za-z0-9]/ },
	];

	function passwordRequirements(value) {
		return checks.filter(function (check) { return !check.test.test(value); });
	}

	function validate() {
		var messages = {};
		var missingPasswordRules = passwordRequirements(fields.password.value);
		if (!/^[A-Za-z][A-Za-z .'-]{1,79}$/.test(fields.name.value.trim())) {
			messages.name = "Enter a name using 2 to 80 letters, spaces, apostrophes, or hyphens.";
		}
		if (!/^[A-Za-z0-9_]{3,30}$/.test(fields.username.value.trim())) {
			messages.username = "Enter a username using 3 to 30 letters, numbers, or underscores.";
		}
		if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(fields.email.value.trim())) {
			messages.email = "Enter a valid email address.";
		}
		if (!/^[6-9][0-9]{9}$/.test(fields.mobile.value.trim())) {
			messages.mobile = "Enter a valid 10-digit mobile number starting with 6 to 9.";
		}
		if (missingPasswordRules.length) {
			messages.password = "Add " + missingPasswordRules.map(function (rule) {
				return rule.label;
			}).join(", ") + ".";
		}
		if (fields.confirmPassword.value !== fields.password.value || !fields.confirmPassword.value) {
			messages.confirmPassword = "Enter the same password again.";
		}
		if (!fields.course.value) messages.course = "Select a course.";
		if (!fields.year.value) messages.year = "Select your year of study.";
		if (!form.querySelector('input[name="gender"]:checked')) {
			messages.gender = "Select a gender option.";
		}
		if (!fields.terms.checked) messages.terms = "Accept the terms and conditions to continue.";

		Object.keys(errors).forEach(function (key) {
			var invalid = Boolean(messages[key]);
			errors[key].textContent = messages[key] || "";
			if (fields[key]) fields[key].setAttribute("aria-invalid", invalid ? "true" : "false");
			if (key === "gender") {
				genderOptions.forEach(function (option) {
					option.setAttribute("aria-invalid", invalid ? "true" : "false");
				});
			}
		});

		var firstInvalid = Object.keys(fields).find(function (key) { return Boolean(messages[key]); });
		if (firstInvalid) {
			fields[firstInvalid].focus();
			return false;
		}
		return true;
	}

	fields.password.addEventListener("input", function () {
		var remaining = passwordRequirements(fields.password.value);
		strength.textContent = remaining.length
			? "Password needs: " + remaining.map(function (rule) { return rule.label; }).join(", ") + "."
			: "Strong password.";
		strength.classList.toggle("is-strong", remaining.length === 0);
	});

	form.addEventListener("submit", function (event) {
		if (!validate()) event.preventDefault();
	});
}

addThemeButton();
addPasswordButtons();
addRegistrationValidation();
