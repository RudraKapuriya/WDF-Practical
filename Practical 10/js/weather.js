(function () {
  const pageHeader = document.querySelector(".header-inner");
  const registrationBrand = document.querySelector(".auth-brand");
  const container = pageHeader || registrationBrand;

  if (!container) return;

  const widget = document.createElement("div");
  widget.className = "weather-widget";
  if (container === registrationBrand) widget.classList.add("weather-widget-auth");
  widget.setAttribute("aria-label", "Current local weather");
  widget.innerHTML =
    '<i class="weather-icon fa-solid fa-cloud-sun" data-weather-icon aria-hidden="true"></i>' +
    '<span class="weather-copy"><strong data-weather-condition>Loading weather</strong>' +
    '<small data-weather-details>Checking your location</small></span>' +
    '<button class="weather-refresh" type="button" data-weather-refresh aria-label="Refresh weather" title="Refresh weather">' +
    '<i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i></button>';

  container.append(widget);

  const conditionElement = widget.querySelector("[data-weather-condition]");
  const detailsElement = widget.querySelector("[data-weather-details]");
  const iconElement = widget.querySelector("[data-weather-icon]");
  const refreshButton = widget.querySelector("[data-weather-refresh]");

  const conditions = {
    0: ["Clear sky", "fa-sun"],
    1: ["Mainly clear", "fa-cloud-sun"],
    2: ["Partly cloudy", "fa-cloud-sun"],
    3: ["Overcast", "fa-cloud"],
    45: ["Fog", "fa-smog"],
    48: ["Depositing fog", "fa-smog"],
    51: ["Light drizzle", "fa-cloud-rain"],
    53: ["Drizzle", "fa-cloud-rain"],
    55: ["Heavy drizzle", "fa-cloud-rain"],
    56: ["Freezing drizzle", "fa-cloud-rain"],
    57: ["Heavy freezing drizzle", "fa-cloud-rain"],
    61: ["Light rain", "fa-cloud-rain"],
    63: ["Rain", "fa-cloud-showers-heavy"],
    65: ["Heavy rain", "fa-cloud-showers-heavy"],
    66: ["Freezing rain", "fa-cloud-showers-heavy"],
    67: ["Heavy freezing rain", "fa-cloud-showers-heavy"],
    71: ["Light snow", "fa-snowflake"],
    73: ["Snow", "fa-snowflake"],
    75: ["Heavy snow", "fa-snowflake"],
    77: ["Snow grains", "fa-snowflake"],
    80: ["Light showers", "fa-cloud-rain"],
    81: ["Showers", "fa-cloud-showers-heavy"],
    82: ["Heavy showers", "fa-cloud-showers-heavy"],
    85: ["Snow showers", "fa-snowflake"],
    86: ["Heavy snow showers", "fa-snowflake"],
    95: ["Thunderstorm", "fa-cloud-bolt"],
    96: ["Thunderstorm with hail", "fa-cloud-bolt"],
    99: ["Thunderstorm with heavy hail", "fa-cloud-bolt"],
  };

  function setUnavailable(message) {
    conditionElement.textContent = "Weather unavailable";
    detailsElement.textContent = message;
    iconElement.className = "weather-icon fa-solid fa-location-dot";
  }

  function loadWeather() {
    if (!navigator.geolocation) {
      setUnavailable("Location is not supported by this browser");
      return;
    }

    conditionElement.textContent = "Loading weather";
    detailsElement.textContent = "Checking your location";

    navigator.geolocation.getCurrentPosition(
      async function (position) {
        const { latitude, longitude } = position.coords;
        const url = new URL("https://api.open-meteo.com/v1/forecast");
        url.search = new URLSearchParams({
          latitude,
          longitude,
          current: "temperature_2m,apparent_temperature,weather_code",
          temperature_unit: "celsius",
        });

        try {
          const response = await fetch(url);
          if (!response.ok) throw new Error("Weather request failed");

          const data = await response.json();
          const current = data.current;
          const [description, icon] = conditions[current.weather_code] || [
            "Current conditions",
            "fa-cloud",
          ];

          conditionElement.textContent = description;
          detailsElement.textContent = `${Math.round(current.temperature_2m)}°C · feels like ${Math.round(current.apparent_temperature)}°C`;
          iconElement.className = `weather-icon fa-solid ${icon}`;
        } catch {
          setUnavailable("Could not reach the weather service");
        }
      },
      function () {
        setUnavailable("Allow location access to see local weather");
      },
      { enableHighAccuracy: false, maximumAge: 900000, timeout: 10000 },
    );
  }

  refreshButton.addEventListener("click", loadWeather);
  loadWeather();
  window.setInterval(loadWeather, 900000);
})();