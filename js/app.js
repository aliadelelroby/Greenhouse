// Global variables
let temperatureChart = null;
let selectedSensors = new Set();
let includeWeatherData = false;

// DOM elements - will be set when content is loaded
let menuSelect = null;
let startDateInput = null;
let endDateInput = null;
let sensorDropdownMenu = null;
let selectedPillsContainer = null;
let chartContainer = null;
let chartPlaceholder = null;
let includeWeatherCheckbox = null;

// API endpoints - using real API files
const API_ENDPOINTS = {
  SENSORS: "api/sensors.php",
  DATA: "api/data.php",
  EXPORT: "api/export.php",
};

// Function to initialize greenhouse tab when content is loaded
function initializeGreenhouseTab() {
  // Get DOM elements
  menuSelect = document.getElementById("menuSelect");
  startDateInput = document.getElementById("startDate");
  endDateInput = document.getElementById("endDate");
  sensorDropdownMenu = document.getElementById("sensorDropdownMenu");
  selectedPillsContainer = document.getElementById("selectedPills");
  chartContainer = document.getElementById("chartContainer");
  chartPlaceholder = document.getElementById("chartPlaceholder");
  includeWeatherCheckbox = document.getElementById("includeWeather");

  // Initialize chart display state properly
  initializeChartDisplay();

  // Initialize dropdown functionality
  initializeSensorDropdown();

  // Initialize export buttons
  initializeExportButtons();

  // Initialize datepickers
  initializeDatePickers();

  // Initialize functionality
  initializeEventListeners();
  setDefaultDates();
  if (menuSelect && menuSelect.value) {
    loadSensors(menuSelect.value);
  }
}

// Initialize chart display state to ensure consistent height
function initializeChartDisplay() {
  if (chartContainer && chartPlaceholder) {
    // Ensure chart container has proper height from CSS
    chartContainer.style.display = "none";
    chartPlaceholder.style.display = "flex";

    // Make sure the chart container maintains its CSS height
    chartContainer.style.height = ""; // Reset any inline height
    chartContainer.classList.add("chart-container"); // Ensure CSS class is applied
  }
}

// Initialize sensor dropdown toggle functionality
function initializeSensorDropdown() {
  const dropdownBtn = document.getElementById("sensorDropdownBtn");
  const dropdownMenu = document.getElementById("sensorDropdownMenu");

  if (dropdownBtn && dropdownMenu) {
    // Remove any existing event listeners
    dropdownBtn.removeEventListener("click", toggleSensorDropdown);

    // Add click event listener to toggle dropdown
    dropdownBtn.addEventListener("click", toggleSensorDropdown);

    // Close dropdown when clicking outside
    document.addEventListener("click", function (event) {
      if (
        !dropdownBtn.contains(event.target) &&
        !dropdownMenu.contains(event.target)
      ) {
        dropdownMenu.classList.add("hidden");
      }
    });
  }
}

// Toggle sensor dropdown visibility
function toggleSensorDropdown(event) {
  event.preventDefault();
  event.stopPropagation();

  const dropdownMenu = document.getElementById("sensorDropdownMenu");
  if (dropdownMenu) {
    dropdownMenu.classList.toggle("hidden");
  }
}

// Initialize when DOM is loaded (for backward compatibility)
document.addEventListener("DOMContentLoaded", function () {
  // Only initialize if elements exist (not lazy loaded)
  if (document.getElementById("menuSelect")) {
    initializeGreenhouseTab();
  }
});

function initializeEventListeners() {
  if (menuSelect) {
    menuSelect.addEventListener("change", () => {
      loadSensors(menuSelect.value);
    });
  }

  if (startDateInput) {
    startDateInput.addEventListener("change", updateChart);
  }

  if (endDateInput) {
    endDateInput.addEventListener("change", updateChart);
  }

  if (includeWeatherCheckbox) {
    includeWeatherCheckbox.addEventListener("change", (e) => {
      includeWeatherData = e.target.checked;
      updateChart();
    });
  }
}

function setDefaultDates() {
  if (startDateInput && endDateInput) {
    const today = new Date();
    const lastWeek = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);

    startDateInput.value = lastWeek.toISOString().split("T")[0];
    endDateInput.value = today.toISOString().split("T")[0];
  }
}

// Load sensors for selected greenhouse - updated to use new controller
async function loadSensors(greenhouseId) {
  if (!greenhouseId || !sensorDropdownMenu) {
    if (sensorDropdownMenu) {
      sensorDropdownMenu.innerHTML =
        '<div class="p-3 text-gray-500">Select a greenhouse first...</div>';
    }
    selectedSensors.clear();
    updatePills();
    updateChart();
    return;
  }

  try {
    const response = await fetch(`${API_ENDPOINTS.SENSORS}?id=${greenhouseId}`);
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const sensors = await response.json();
    console.log("Sensors received:", sensors);

    if (sensors.error) {
      console.error("Server error:", sensors.error);
      sensorDropdownMenu.innerHTML =
        '<div class="p-3 text-red-500">Error loading sensors</div>';
      return;
    }

    if (!Array.isArray(sensors) || sensors.length === 0) {
      sensorDropdownMenu.innerHTML =
        '<div class="p-3 text-gray-500">No sensors available</div>';
      return;
    }

    // Build sensor dropdown with improved styling
    sensorDropdownMenu.innerHTML = sensors
      .map(
        (sensor) => `
            <div class="px-3 py-2 hover:bg-gray-50">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" 
                           value="${sensor.id || sensor.id_sensor}" 
                           name="sensorCheckbox" 
                           class="mr-3 text-thermeleon-500 focus:ring-thermeleon-500 border-gray-300 rounded">
                    <span class="text-sm text-gray-900">${
                      sensor.name || sensor.name_sensor
                    }</span>
                </label>
            </div>
        `
      )
      .join("");

    // Add event listeners to checkboxes
    const checkboxes = sensorDropdownMenu.querySelectorAll(
      'input[type="checkbox"]'
    );
    checkboxes.forEach((checkbox) => {
      checkbox.addEventListener("change", handleSensorSelection);
    });

    // Reset selections
    selectedSensors.clear();
    updatePills();
    updateChart();
    updateExportButtons();
  } catch (error) {
    console.error("Error loading sensors:", error);
    if (sensorDropdownMenu) {
      sensorDropdownMenu.innerHTML =
        '<div class="p-3 text-red-500">Error loading sensors</div>';
    }
  }
}

function handleSensorSelection(event) {
  const sensorId = event.target.value;
  const sensorName = event.target.nextElementSibling.textContent;

  if (event.target.checked) {
    selectedSensors.add(sensorId);
  } else {
    selectedSensors.delete(sensorId);
  }

  updatePills();
  updateChart();
  updateExportButtons();
}

// Update sensor pills display
function updatePills() {
  if (!selectedPillsContainer) return;

  if (selectedSensors.size === 0) {
    selectedPillsContainer.innerHTML =
      '<span class="text-gray-500 text-sm">No sensors selected</span>';
    return;
  }

  const pills = Array.from(selectedSensors)
    .map((sensorId) => {
      const sensorName = getSensorName(sensorId);
      return `
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-thermeleon-100 text-thermeleon-800">
                ${sensorName}
                <button onclick="removeSensor('${sensorId}')" class="ml-2 text-thermeleon-600 hover:text-thermeleon-900">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </span>
        `;
    })
    .join("");

  selectedPillsContainer.innerHTML = pills;
}

function getSensorName(sensorId) {
  const checkbox = document.querySelector(`input[value="${sensorId}"]`);
  if (checkbox && checkbox.nextElementSibling) {
    return checkbox.nextElementSibling.textContent;
  }
  return `Sensor ${sensorId}`;
}

function removeSensor(sensorId) {
  selectedSensors.delete(sensorId);

  // Uncheck the corresponding checkbox
  const checkbox = document.querySelector(`input[value="${sensorId}"]`);
  if (checkbox) {
    checkbox.checked = false;
  }

  updatePills();
  updateChart();
  updateExportButtons();
}

// Fetch data from server - updated to use new controller
async function fetchData(
  greenhouseId,
  sensors,
  startDate,
  endDate,
  includeWeather = false
) {
  if (!greenhouseId || (sensors.size === 0 && !includeWeather)) {
    return null;
  }

  try {
    let sensorData = {};
    let weatherData = null;

    // Fetch sensor data if sensors are selected
    if (sensors.size > 0) {
      const sensorString = Array.from(sensors).join(",");
      const sensorParams = new URLSearchParams();
      sensorParams.append("sensors", sensorString);
      if (startDate) sensorParams.append("start_date", startDate);
      if (endDate) sensorParams.append("end_date", endDate);

      const sensorUrl = `${API_ENDPOINTS.DATA}?${sensorParams.toString()}`;
      console.log("Fetching sensor data from:", sensorUrl);

      const sensorResponse = await fetch(sensorUrl);
      if (sensorResponse.ok) {
        const responseText = await sensorResponse.text();
        try {
          sensorData = JSON.parse(responseText);
        } catch (parseError) {
          console.error("JSON parse error for sensor data:", parseError);
        }
      }
    }

    // Fetch weather data if checkbox is checked
    if (includeWeather) {
      const weatherParams = new URLSearchParams();
      weatherParams.append("weather", "1");
      if (startDate) weatherParams.append("start_date", startDate);
      if (endDate) weatherParams.append("end_date", endDate);

      const weatherUrl = `${API_ENDPOINTS.DATA}?${weatherParams.toString()}`;
      console.log("Fetching weather data from:", weatherUrl);

      const weatherResponse = await fetch(weatherUrl);
      if (weatherResponse.ok) {
        const weatherResponseText = await weatherResponse.text();
        try {
          const weatherResult = JSON.parse(weatherResponseText);
          weatherData = weatherResult.weather || null;
        } catch (parseError) {
          console.error("JSON parse error for weather data:", parseError);
        }
      }
    }

    return {
      sensors: sensorData,
      weather: weatherData,
    };
  } catch (error) {
    console.error("Error fetching data:", error);
    return null;
  }
}

// Update chart display
async function updateChart() {
  if (!menuSelect) return;

  const greenhouseId = menuSelect.value;
  const sensors = selectedSensors;
  const startDate = startDateInput ? startDateInput.value : null;
  const endDate = endDateInput ? endDateInput.value : null;

  // Show/hide chart container and placeholder
  if (!greenhouseId || (sensors.size === 0 && !includeWeatherData)) {
    hideChart();
    return;
  }

  const data = await fetchData(
    greenhouseId,
    sensors,
    startDate,
    endDate,
    includeWeatherData
  );
  if (
    !data ||
    (Object.keys(data.sensors || {}).length === 0 && !data.weather)
  ) {
    hideChart();
    return;
  }

  showChart();
  renderChart(data);
}

function hideChart() {
  if (chartContainer) {
    chartContainer.style.display = "none";
    // Reset height to ensure CSS takes precedence
    chartContainer.style.height = "";
  }
  if (chartPlaceholder) chartPlaceholder.style.display = "flex";

  if (temperatureChart) {
    temperatureChart.destroy();
    temperatureChart = null;
  }
}

function showChart() {
  if (chartContainer) {
    chartContainer.style.display = "block";
    // Reset height to ensure CSS takes precedence
    chartContainer.style.height = "";
    // Ensure CSS class is maintained
    chartContainer.classList.add("chart-container");
  }
  if (chartPlaceholder) chartPlaceholder.style.display = "none";
}

function renderChart(data) {
  const ctx = document.getElementById("temperatureChart");
  if (!ctx) return;

  // Color palette for different sensors and weather
  const colors = [
    "rgba(34, 197, 94, 0.8)", // green
    "rgba(59, 130, 246, 0.8)", // blue
    "rgba(234, 179, 8, 0.8)", // yellow
    "rgba(239, 68, 68, 0.8)", // red
    "rgba(168, 85, 247, 0.8)", // purple
    "rgba(34, 211, 238, 0.8)", // cyan
    "rgba(251, 146, 60, 0.8)", // orange
    "rgba(156, 163, 175, 0.8)", // gray
  ];

  const weatherColor = "rgba(0, 123, 255, 0.8)"; // blue for weather

  const datasets = [];
  let colorIndex = 0;

  // Process data for each selected sensor
  if (data.sensors) {
    for (const sensorId of selectedSensors) {
      if (!data.sensors[sensorId] || !Array.isArray(data.sensors[sensorId]))
        continue;

      const sensorName = getSensorName(sensorId);
      const points = data.sensors[sensorId].map((d) => ({
        x: new Date(d.timestamp),
        y: parseFloat(d.value) || 0,
      }));

      // Filter out invalid data points
      const validPoints = points.filter(
        (point) =>
          point.x instanceof Date &&
          !isNaN(point.x.getTime()) &&
          !isNaN(point.y)
      );

      if (validPoints.length === 0) continue;

      datasets.push({
        label: sensorName,
        data: validPoints,
        borderColor: colors[colorIndex % colors.length],
        backgroundColor: colors[colorIndex % colors.length].replace(
          "0.8",
          "0.1"
        ),
        fill: false,
        tension: 0.4,
        pointRadius: 3,
        pointHoverRadius: 6,
        borderWidth: 2,
        pointBackgroundColor: colors[colorIndex % colors.length],
        pointBorderColor: "#fff",
        pointBorderWidth: 1,
        yAxisID: "y",
      });
      colorIndex++;
    }
  }

  // Add weather data if available
  if (data.weather && Array.isArray(data.weather) && data.weather.length > 0) {
    const weatherPoints = data.weather.map((d) => ({
      x: new Date(d.timestamp),
      y: parseFloat(d.temperature) || 0,
    }));

    const validWeatherPoints = weatherPoints.filter(
      (point) =>
        point.x instanceof Date && !isNaN(point.x.getTime()) && !isNaN(point.y)
    );

    if (validWeatherPoints.length > 0) {
      datasets.push({
        label: "Outside Temperature",
        data: validWeatherPoints,
        borderColor: weatherColor,
        backgroundColor: weatherColor.replace("0.8", "0.1"),
        fill: false,
        tension: 0.4,
        pointRadius: 4,
        pointHoverRadius: 7,
        borderWidth: 3,
        pointBackgroundColor: weatherColor,
        pointBorderColor: "#fff",
        pointBorderWidth: 2,
        yAxisID: "y",
        borderDash: [5, 5], // Dashed line for weather
      });
    }
  }

  if (datasets.length === 0) {
    hideChart();
    return;
  }

  const config = {
    type: "line",
    data: { datasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        mode: "index",
        intersect: false,
      },
      plugins: {
        legend: {
          display: true,
          position: "top",
          labels: {
            usePointStyle: true,
            padding: 20,
            font: {
              size: 12,
            },
          },
        },
        tooltip: {
          enabled: true,
          mode: "index",
          intersect: false,
          backgroundColor: "rgba(0, 0, 0, 0.8)",
          titleColor: "#fff",
          bodyColor: "#fff",
          borderColor: "rgba(255, 255, 255, 0.1)",
          borderWidth: 1,
          callbacks: {
            title: function (context) {
              const date = new Date(context[0].parsed.x);
              return date.toLocaleString();
            },
            label: function (context) {
              const value = context.parsed.y;
              return `${context.dataset.label}: ${value.toFixed(2)}°C`;
            },
          },
        },
      },
      scales: {
        x: {
          type: "time",
          time: {
            unit: "hour",
            tooltipFormat: "dd LLL yyyy HH:mm",
            displayFormats: {
              hour: "HH:mm",
              day: "dd MMM",
            },
          },
          title: {
            display: true,
            text: "Time",
            font: {
              size: 14,
              weight: "bold",
            },
          },
          grid: {
            color: "rgba(0, 0, 0, 0.1)",
          },
        },
        y: {
          title: {
            display: true,
            text: "Temperature (°C)",
            font: {
              size: 14,
              weight: "bold",
            },
          },
          grid: {
            color: "rgba(0, 0, 0, 0.1)",
          },
          beginAtZero: false,
        },
      },
      animation: {
        duration: 1000,
        easing: "easeInOutQuart",
      },
    },
  };

  // Destroy existing chart
  if (temperatureChart) {
    temperatureChart.destroy();
  }

  // Create new chart
  temperatureChart = new Chart(ctx.getContext("2d"), config);
}

// Update export buttons state
function updateExportButtons() {
  const exportQuick = document.getElementById("exportQuick");
  const exportDetailed = document.getElementById("exportDetailed");

  if (!exportQuick || !exportDetailed) return;

  const hasData = selectedSensors.size > 0 && menuSelect && menuSelect.value;

  exportQuick.disabled = !hasData;
  exportDetailed.disabled = !hasData;

  if (hasData) {
    exportQuick.classList.remove("opacity-50", "cursor-not-allowed");
    exportDetailed.classList.remove("opacity-50", "cursor-not-allowed");
  } else {
    exportQuick.classList.add("opacity-50", "cursor-not-allowed");
    exportDetailed.classList.add("opacity-50", "cursor-not-allowed");
  }
}

// Export functionality
function exportData(type) {
  const greenhouseId = menuSelect ? menuSelect.value : "";
  const sensors = Array.from(selectedSensors);
  const startDate = startDateInput ? startDateInput.value : "";
  const endDate = endDateInput ? endDateInput.value : "";

  if (!greenhouseId || sensors.length === 0) {
    // Use the global notification system instead of the modal
    if (typeof showErrorNotification === "function") {
      showErrorNotification(
        "Please select a greenhouse and at least one sensor."
      );
    } else {
      alert("Please select a greenhouse and at least one sensor.");
    }
    return;
  }

  const params = new URLSearchParams();
  params.append("greenhouse_id", greenhouseId);
  params.append("sensors", sensors.join(","));
  params.append("type", type);
  if (startDate) params.append("start_date", startDate);
  if (endDate) params.append("end_date", endDate);

  // Use real API endpoint
  const url = `api/export.php?${params.toString()}`;
  window.open(url, "_blank");
}

// Initialize export button event listeners
function initializeExportButtons() {
  const exportQuick = document.getElementById("exportQuick");
  const exportDetailed = document.getElementById("exportDetailed");
  const refreshChart = document.getElementById("refreshChart");

  if (exportQuick) {
    exportQuick.removeEventListener("click", handleExportQuick); // Remove any existing listeners
    exportQuick.addEventListener("click", handleExportQuick);
  }

  if (exportDetailed) {
    exportDetailed.removeEventListener("click", handleExportDetailed);
    exportDetailed.addEventListener("click", handleExportDetailed);
  }

  if (refreshChart) {
    refreshChart.removeEventListener("click", updateChart);
    refreshChart.addEventListener("click", updateChart);
  }
}

// Event handlers for export buttons
function handleExportQuick(event) {
  if (!event.target.disabled) {
    exportData("quick");
  }
}

function handleExportDetailed(event) {
  if (!event.target.disabled) {
    exportData("detailed");
  }
}

// Initialize Air Datepickers with custom styling and options
function initializeDatePickers() {
  // Check if the input elements exist
  const startDateElement = document.getElementById("startDate");
  const endDateElement = document.getElementById("endDate");

  if (!startDateElement || !endDateElement) {
    console.warn(
      "Date input elements not found, skipping datepicker initialization"
    );
    return;
  }

  // English locale object - using default English locale
  const enLocale = {
    days: [
      "Sunday",
      "Monday",
      "Tuesday",
      "Wednesday",
      "Thursday",
      "Friday",
      "Saturday",
    ],
    daysShort: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
    daysMin: ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"],
    months: [
      "January",
      "February",
      "March",
      "April",
      "May",
      "June",
      "July",
      "August",
      "September",
      "October",
      "November",
      "December",
    ],
    monthsShort: [
      "Jan",
      "Feb",
      "Mar",
      "Apr",
      "May",
      "Jun",
      "Jul",
      "Aug",
      "Sep",
      "Oct",
      "Nov",
      "Dec",
    ],
    today: "Today",
    clear: "Clear",
    dateFormat: "yyyy-MM-dd",
    timeFormat: "HH:mm",
    firstDay: 0,
  };

  // Common options for both date pickers
  const datePickerOptions = {
    locale: enLocale,
    dateFormat: "yyyy-MM-dd",
    autoClose: true,
    position: "bottom left",
    classes: "greenhouse-datepicker",
    buttons: ["today", "clear"],
    prevHtml:
      '<svg class="w-4 h-4"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    nextHtml:
      '<svg class="w-4 h-4"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    navTitles: {
      days: "MMMM <i>yyyy</i>",
      months: "yyyy",
      years: "yyyy1 - yyyy2",
    },
    onSelect: function ({ date, formattedDate, datepicker }) {
      // Update export buttons when date is selected
      setTimeout(updateExportButtons, 100);
    },
  };

  // Variables to store datepicker instances
  let startDatePicker, endDatePicker;

  // Initialize start date picker
  startDatePicker = new AirDatepicker("#startDate", {
    ...datePickerOptions,
    onSelect: function ({ date, formattedDate, datepicker }) {
      // Update export buttons when date is selected
      setTimeout(updateExportButtons, 100);
    },
  });

  // Initialize end date picker
  endDatePicker = new AirDatepicker("#endDate", {
    ...datePickerOptions,
    onSelect: function ({ date, formattedDate, datepicker }) {
      // Update export buttons when date is selected
      setTimeout(updateExportButtons, 100);
    },
  });

  // Set default date range (last 30 days)
  const today = new Date();
  const thirtyDaysAgo = new Date(today);
  thirtyDaysAgo.setDate(today.getDate() - 30);

  // Select dates after both pickers are initialized
  setTimeout(() => {
    startDatePicker.selectDate(thirtyDaysAgo);
    endDatePicker.selectDate(today);
  }, 100);
}

// Platform tab initialization
function initializePlatformTab() {
  // Wait for the platform content to be fully loaded
  const checkAndLoad = (attempts = 0) => {
    const tbody = document.getElementById("entityTableBody");

    if (!tbody && attempts < 10) {
      // If elements aren't ready yet, wait a bit more
      setTimeout(() => checkAndLoad(attempts + 1), 100);
      return;
    }

    // Try to call the loadSystemData function
    if (typeof loadSystemData === "function") {
      loadSystemData();
    } else if (typeof renderRealData === "function") {
      renderRealData();
    } else {
      // If neither function exists, try to manually trigger a data refresh
      setTimeout(() => {
        if (typeof refreshData === "function") {
          refreshData();
        } else {
          // As a last resort, simulate a refresh button click
          const refreshBtn = document.querySelector(
            'button[onclick="refreshData()"]'
          );
          if (refreshBtn) {
            refreshBtn.click();
          }
        }
      }, 200);
    }
  };

  checkAndLoad();
}

// Manager tab initialization (placeholder for future use)
function initializeManagerTab() {
  // Add manager-specific initialization here if needed
  console.log("Manager tab initialized");
}

// Global functions for HTML onclick events
window.removeSensor = removeSensor;
window.updateChart = updateChart;
window.exportData = exportData;
