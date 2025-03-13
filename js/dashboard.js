document.addEventListener("DOMContentLoaded", function () {
    fetchEnergyStats();
});

function fetchEnergyStats() {
    fetch("../api/get_stats.php")
        .then(response => response.json())
        .then(data => {
            document.getElementById("totalEnergy").textContent = data.totalEnergy || "0";
            document.getElementById("carbonFootprint").textContent = data.carbonFootprint || "0";
            updateEnergyTable(data.entries);
        })
        .catch(error => console.error("Error fetching stats:", error));
}

function updateEnergyTable(entries) {
    const tableBody = document.getElementById("energyTable");
    tableBody.innerHTML = "";  // Clear existing rows

    if (entries.length === 0) {
        tableBody.innerHTML = "<tr><td colspan='3'>No data available</td></tr>";
        return;
    }

    entries.forEach(entry => {
        let row = `<tr>
            <td>${entry.date}</td>
            <td>${entry.appliance}</td>
            <td>${entry.energyUsed} kWh</td>
        </tr>`;
        tableBody.innerHTML += row;
    });
}
