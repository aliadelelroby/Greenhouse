// Global variables
let temperatureChart = null;
let selectedSensors = new Set();

// DOM elements
const menuSelect = document.getElementById("menuSelect");
const startDateInput = document.getElementById("startDate");
const endDateInput = document.getElementById("endDate");
const sensorDropdownMenu = document.getElementById("sensorDropdownMenu");
const selectedPillsContainer = document.getElementById("selectedPills");
const chartContainer = document.getElementById("chartContainer");
const chartPlaceholder = document.getElementById("chartPlaceholder");

// API endpoints - using real API files
const API_ENDPOINTS = {
  SENSORS: "api/sensors.php",
  DATA: "api/data.php",
  EXPORT: "api/export.php",
};

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
  initializeEventListeners();
  setDefaultDates();
  loadSensors(menuSelect.value);
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
async function fetchData(greenhouseId, sensors, startDate, endDate) {
  if (!greenhouseId || sensors.size === 0) {
    return null;
  }

  const sensorString = Array.from(sensors).join(",");
  const params = new URLSearchParams();
  params.append("sensors", sensorString);
  if (startDate) params.append("start_date", startDate);
  if (endDate) params.append("end_date", endDate);

  const url = `${API_ENDPOINTS.DATA}?${params.toString()}`;

  try {
    console.log("Fetching data from:", url);
    const response = await fetch(url);

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const responseText = await response.text();
    console.log("Raw response:", responseText.substring(0, 200));

    let data;
    try {
      data = JSON.parse(responseText);
    } catch (parseError) {
      console.error("JSON parse error:", parseError);
      console.error("Response text:", responseText);
      throw new Error("Invalid JSON response from server");
    }

    if (data.error) {
      console.error("Server error:", data.error);
      return null;
    }

    console.log("Parsed data:", data);
    return data;
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
  if (!greenhouseId || sensors.size === 0) {
    hideChart();
    return;
  }

  const data = await fetchData(greenhouseId, sensors, startDate, endDate);
  if (!data || Object.keys(data).length === 0) {
    hideChart();
    return;
  }

  showChart();
  renderChart(data);
}

function hideChart() {
  if (chartContainer) chartContainer.style.display = "none";
  if (chartPlaceholder) chartPlaceholder.style.display = "flex";

  if (temperatureChart) {
    temperatureChart.destroy();
    temperatureChart = null;
  }
}

function showChart() {
  if (chartContainer) chartContainer.style.display = "block";
  if (chartPlaceholder) chartPlaceholder.style.display = "none";
}

function renderChart(data) {
  const ctx = document.getElementById("temperatureChart");
  if (!ctx) return;

  // Color palette for different sensors
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

  const datasets = [];
  let colorIndex = 0;

  // Process data for each selected sensor
  for (const sensorId of selectedSensors) {
    if (!data[sensorId] || !Array.isArray(data[sensorId])) continue;

    const sensorName = getSensorName(sensorId);
    const points = data[sensorId].map((d) => ({
      x: new Date(d.timestamp),
      y: parseFloat(d.value) || 0,
    }));

    // Filter out invalid data points
    const validPoints = points.filter(
      (point) =>
        point.x instanceof Date && !isNaN(point.x.getTime()) && !isNaN(point.y)
    );

    if (validPoints.length === 0) continue;

    datasets.push({
      label: sensorName,
      data: validPoints,
      borderColor: colors[colorIndex % colors.length],
      backgroundColor: colors[colorIndex % colors.length].replace("0.8", "0.1"),
      fill: false,
      tension: 0.4,
      pointRadius: 3,
      pointHoverRadius: 6,
      borderWidth: 2,
      pointBackgroundColor: colors[colorIndex % colors.length],
      pointBorderColor: "#fff",
      pointBorderWidth: 1,
    });
    colorIndex++;
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

// Global functions for HTML onclick events
window.removeSensor = removeSensor;
window.updateChart = updateChart;
