"use strict";

(function () {
	var manager = document.querySelector("[data-event-manager]");
	if (!manager) return;

	var apiUrl = manager.dataset.api;
	var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
	var form = manager.querySelector("[data-event-form]");
	var status = manager.querySelector("[data-status]");
	var listStatus = manager.querySelector("[data-list-status]");
	var list = manager.querySelector("[data-event-list]");
	var count = manager.querySelector("[data-count]");
	var formTitle = manager.querySelector("[data-form-title]");
	var saveButton = manager.querySelector("[data-save-button]");
	var cancelButton = manager.querySelector("[data-cancel-edit]");
	var posterInput = form.elements.poster;
	var posterPreview = manager.querySelector("[data-poster-preview]");
	var events = [];

	function showStatus(message, isError) {
		status.textContent = message;
		status.classList.toggle("error", Boolean(isError));
	}

	async function request(url, options) {
		var response = await fetch(url, options);
		var payload;
		try {
			payload = await response.json();
		} catch (error) {
			throw new Error("The server returned an invalid response. Please try again.");
		}
		if (!response.ok || !payload.success) {
			if (response.status === 401) {
				window.location.href = "../login.php";
			}
			throw new Error(payload.message || "The request failed. Please try again.");
		}
		return payload;
	}

	function makeCell(row, value) {
		var cell = document.createElement("td");
		cell.textContent = value;
		row.appendChild(cell);
		return cell;
	}

	function renderEvents() {
		list.replaceChildren();
		count.textContent = "(" + events.length + ")";
		if (!events.length) {
			listStatus.textContent = "No events have been added yet.";
			return;
		}

		listStatus.textContent = "";
		var table = document.createElement("table");
		var head = document.createElement("thead");
		var headerRow = document.createElement("tr");
		["Poster", "Event", "Date / venue", "Capacity", "Actions"].forEach(function (label) {
			var heading = document.createElement("th");
			heading.scope = "col";
			heading.textContent = label;
			headerRow.appendChild(heading);
		});
		head.appendChild(headerRow);
		table.appendChild(head);

		var body = document.createElement("tbody");
		events.forEach(function (event) {
			var row = document.createElement("tr");
			var posterCell = document.createElement("td");
			if (event.poster_url) {
				var image = document.createElement("img");
				image.className = "poster-thumb";
				image.src = event.poster_url;
				image.alt = "Poster for " + event.title;
				posterCell.appendChild(image);
			} else {
				posterCell.textContent = "No poster";
			}
			row.appendChild(posterCell);

			var detailCell = document.createElement("td");
			var title = document.createElement("strong");
			title.textContent = event.title;
			var description = document.createElement("p");
			description.textContent = event.description;
			detailCell.appendChild(title);
			detailCell.appendChild(description);
			row.appendChild(detailCell);

			var date = new Date(event.starts_at.replace(" ", "T"));
			var dateText = Number.isNaN(date.getTime()) ? event.starts_at : date.toLocaleString();
			var placeCell = makeCell(row, dateText + "\n" + event.venue);
			placeCell.style.whiteSpace = "pre-line";
			makeCell(row, event.capacity === null ? "Unlimited" : String(event.capacity));

			var actionsCell = document.createElement("td");
			actionsCell.className = "actions";
			var editButton = document.createElement("button");
			editButton.type = "button";
			editButton.className = "button-secondary";
			editButton.textContent = "Edit";
			editButton.addEventListener("click", function () { beginEdit(event); });
			actionsCell.appendChild(editButton);
			var deleteButton = document.createElement("button");
			deleteButton.type = "button";
			deleteButton.className = "button-danger";
			deleteButton.textContent = "Delete";
			deleteButton.addEventListener("click", function () { deleteEvent(event); });
			actionsCell.appendChild(deleteButton);
			row.appendChild(actionsCell);
			body.appendChild(row);
		});
		table.appendChild(body);
		list.appendChild(table);
	}

	async function loadEvents() {
		listStatus.textContent = "Loading events...";
		try {
			var payload = await request(apiUrl, { headers: { Accept: "application/json" } });
			events = payload.data.events;
			renderEvents();
		} catch (error) {
			listStatus.textContent = "";
			showStatus(error.message, true);
		}
	}

	function beginEdit(event) {
		form.elements.id.value = event.id;
		form.elements.title.value = event.title;
		form.elements.description.value = event.description;
		form.elements.starts_at.value = event.starts_at.replace(" ", "T").slice(0, 16);
		form.elements.venue.value = event.venue;
		form.elements.capacity.value = event.capacity === null ? "" : event.capacity;
		posterInput.required = false;
		formTitle.textContent = "Edit event";
		saveButton.textContent = "Save changes";
		cancelButton.hidden = false;
		if (event.poster_url) {
			posterPreview.src = event.poster_url;
			posterPreview.alt = "Current poster for " + event.title;
			posterPreview.hidden = false;
		} else {
			posterPreview.removeAttribute("src");
			posterPreview.hidden = true;
		}
		showStatus("Editing: " + event.title, false);
		form.scrollIntoView({ behavior: "smooth", block: "start" });
		form.elements.title.focus();
	}

	function resetForm() {
		form.reset();
		form.elements.id.value = "";
		posterInput.required = true;
		posterPreview.removeAttribute("src");
		posterPreview.hidden = true;
		formTitle.textContent = "Add event";
		saveButton.textContent = "Add event";
		cancelButton.hidden = true;
	}

	async function deleteEvent(event) {
		if (!window.confirm('Delete "' + event.title + '" and its registrations?')) return;
		var data = new FormData();
		data.set("action", "delete");
		data.set("id", event.id);
		try {
			var payload = await request(apiUrl, {
				method: "POST",
				headers: { "X-CSRF-Token": csrfToken, Accept: "application/json" },
				body: data,
			});
			showStatus(payload.message, false);
			if (form.elements.id.value === String(event.id)) resetForm();
			await loadEvents();
		} catch (error) {
			showStatus(error.message, true);
		}
	}

	posterInput.addEventListener("change", function () {
		var file = posterInput.files[0];
		if (!file) {
			if (!form.elements.id.value) {
				posterPreview.hidden = true;
				posterPreview.removeAttribute("src");
			}
			return;
		}
		if (file.size > 5 * 1024 * 1024) {
			posterInput.setCustomValidity("Poster must be no larger than 5 MB.");
			posterInput.reportValidity();
			posterInput.value = "";
			return;
		}
		if (!["image/jpeg", "image/png", "image/webp"].includes(file.type)) {
			posterInput.setCustomValidity("Choose a JPEG, PNG, or WebP image.");
			posterInput.reportValidity();
			posterInput.value = "";
			return;
		}
		posterInput.setCustomValidity("");
		posterPreview.src = URL.createObjectURL(file);
		posterPreview.alt = "Selected event poster preview";
		posterPreview.hidden = false;
	});

	form.addEventListener("submit", async function (event) {
		event.preventDefault();
		if (!form.reportValidity()) return;

		var isEditing = Boolean(form.elements.id.value);
		var data = new FormData(form);
		data.set("action", isEditing ? "update" : "create");
		saveButton.disabled = true;
		showStatus(isEditing ? "Saving event changes..." : "Adding event...", false);
		try {
			var payload = await request(apiUrl, {
				method: "POST",
				headers: { "X-CSRF-Token": csrfToken, Accept: "application/json" },
				body: data,
			});
			showStatus(payload.message, false);
			resetForm();
			await loadEvents();
		} catch (error) {
			showStatus(error.message, true);
		} finally {
			saveButton.disabled = false;
		}
	});

	cancelButton.addEventListener("click", resetForm);
	loadEvents();
}());
