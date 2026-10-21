"use strict";

document.querySelectorAll("[data-json-browser]").forEach(function (browser) {
	var results = browser.querySelector("[data-results]");
	var status = browser.querySelector("[data-status]");
	var search = browser.querySelector("[data-search]");
	var category = browser.querySelector("[data-category]");
	var sort = browser.querySelector("[data-sort]");
	var pageSize = browser.querySelector("[data-page-size]");
	var pagination = browser.querySelector("[data-pagination]");
	var records = [];
	var currentPage = 1;
	var titleKey = browser.dataset.titleKey;
	var descriptionKey = browser.dataset.descriptionKey;
	var dateKey = browser.dataset.dateKey;
	var categoryKey = browser.dataset.categoryKey;

	function setStatus(message, state) {
		status.textContent = message;
		status.dataset.state = state || "";
	}

	function makeButton(text, disabled, action) {
		var button = document.createElement("button");
		button.type = "button";
		button.className = "js-control";
		button.textContent = text;
		button.disabled = disabled;
		button.addEventListener("click", action);
		return button;
	}

	function render() {
		var query = search.value.trim().toLocaleLowerCase();
		var selectedCategory = category.value;
		var matching = records.filter(function (record) {
			var searchable = [record[titleKey], record[descriptionKey], record[categoryKey]]
				.filter(Boolean).join(" ").toLocaleLowerCase();
			return searchable.includes(query) && (!selectedCategory || record[categoryKey] === selectedCategory);
		});
		var sortParts = sort.value.split("-");
		var sortField = sortParts[0] === "title" ? titleKey : dateKey;
		var direction = sortParts[1] === "desc" ? -1 : 1;

		matching.sort(function (left, right) {
			var comparison = sortField === dateKey
				? new Date(left[sortField]) - new Date(right[sortField])
				: String(left[sortField] || "").localeCompare(String(right[sortField] || ""));
			return comparison * direction;
		});

		var perPage = Number(pageSize.value);
		var pageCount = Math.max(1, Math.ceil(matching.length / perPage));
		currentPage = Math.min(currentPage, pageCount);
		var visible = matching.slice((currentPage - 1) * perPage, currentPage * perPage);
		results.replaceChildren();

		visible.forEach(function (record) {
			var card = document.createElement("article");
			var heading = document.createElement("h3");
			var description = document.createElement("p");
			card.className = "card json-card";
			heading.textContent = record[titleKey];
			description.textContent = record[descriptionKey];
			card.append(heading, description);

			var metadata = [record[categoryKey]];
			if (dateKey && record[dateKey]) {
				var date = new Date(record[dateKey]);
				metadata.push((browser.dataset.dateLabel || "Date") + ": " + date.toLocaleDateString());
			}
			metadata = metadata.filter(Boolean);
			if (metadata.length) {
				var detail = document.createElement("small");
				detail.className = "json-meta";
				detail.textContent = metadata.join(" · ");
				card.appendChild(detail);
			}
			results.appendChild(card);
		});

		if (!matching.length) {
			setStatus(records.length ? "No matching items found." : "No items are available yet.", "empty");
		} else {
			setStatus("Showing " + ((currentPage - 1) * perPage + 1) + "-" + Math.min(currentPage * perPage, matching.length) + " of " + matching.length + " items.", "ready");
		}

		pagination.replaceChildren();
		if (pageCount > 1) {
			pagination.appendChild(makeButton("Previous", currentPage === 1, function () {
				currentPage -= 1;
				render();
			}));
			var pageLabel = document.createElement("span");
			pageLabel.textContent = "Page " + currentPage + " of " + pageCount;
			pagination.appendChild(pageLabel);
			pagination.appendChild(makeButton("Next", currentPage === pageCount, function () {
				currentPage += 1;
				render();
			}));
		}
	}

	function populateCategories() {
		var categories = Array.from(new Set(records.map(function (record) {
			return record[categoryKey];
		}).filter(Boolean))).sort();
		categories.forEach(function (name) {
			var option = document.createElement("option");
			option.value = name;
			option.textContent = name;
			category.appendChild(option);
		});
	}

	async function loadRecords() {
		setStatus("Loading data...", "loading");
		results.replaceChildren();
		pagination.replaceChildren();
		try {
			var response = await fetch(browser.dataset.source);
			if (!response.ok) throw new Error("The data request failed (" + response.status + ").");
			var payload = await response.json();
			if (!Array.isArray(payload)) throw new Error("The JSON feed must contain an array.");
			records = payload;
			populateCategories();
			render();
		} catch (error) {
			setStatus("Could not load data. " + error.message + " Use a local web server, then retry.", "error");
			var retry = makeButton("Retry", false, loadRecords);
			status.appendChild(document.createTextNode(" "));
			status.appendChild(retry);
		}
	}

	search.addEventListener("input", function () { currentPage = 1; render(); });
	category.addEventListener("change", function () { currentPage = 1; render(); });
	sort.addEventListener("change", function () { currentPage = 1; render(); });
	pageSize.addEventListener("change", function () { currentPage = 1; render(); });
	loadRecords();
});