<!DOCTYPE html>
<html lang="en">
<head>
    <title>Grace Airways — My Bookings</title>
    <link href="css/bookings.css" rel="stylesheet">
</head>
<body>

<?php include "header.php"; ?>

<div id="bookingsWrapper">

    <h2>✈ My Booked Flights</h2>
    <p id="bookingCount" style="color:#888; font-size:13px;"></p>

    <div id="loadingMsg" style="color:#888;">Loading your bookings...</div>
    <div id="emptyMsg"   style="display:none;">
        You have no bookings yet. 
        <a href="index.php">Book a flight now!</a>
    </div>

    <div id="bookingsTableWrap" style="display:none; overflow-x:auto;">
        <table id="bookingsTable">
            <thead>
                <tr>
                    <th>Plane</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Departure Date</th>
                    <th>Flight Time</th>
                    <th>Distance</th>
                    <th>Passengers</th>
                    <th>Cancel</th>
                </tr>
            </thead>
            <tbody id="bookingsBody"></tbody>
        </table>
    </div>

</div>

<script>
const API_KEY = localStorage.getItem("api_key");

// ── Convert minutes to "Xh Ym" format ────────────────────────
function formatTime(mins) {
    const h = Math.floor(mins / 60);
    const m = Math.round(mins % 60);
    return h + "h " + m + "m";
}

// ── Load bookings from API ────────────────────────────────────
async function loadBookings() {
    const body      = document.getElementById("bookingsBody");
    const emptyMsg  = document.getElementById("emptyMsg");
    const tableWrap = document.getElementById("bookingsTableWrap");
    const loadMsg   = document.getElementById("loadingMsg");
    const countEl   = document.getElementById("bookingCount");

    if (!API_KEY) {
        loadMsg.innerHTML = "Please <a href='login.php'>log in</a> to see your bookings.";
        return;
    }

    const response = await fetch("api.php", {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify({ type: "GetBookings", apikey: API_KEY })
    });

    const result = await response.json();
    loadMsg.style.display = "none";

    if (result.status === "error") {
        emptyMsg.innerHTML     = "Error loading bookings: " + result.data;
        emptyMsg.style.display = "block";
        return;
    }

    const bookings = result.data;

    if (bookings.length === 0) {
        emptyMsg.style.display = "block";
        return;
    }

    countEl.textContent = "You have " + bookings.length + " booking(s).";
    tableWrap.style.display = "block";

    body.innerHTML = bookings.map(function(b) {
        const depDate = new Date(b.departure_date).toLocaleString();
        return `
            <tr id="booking-row-${b.booking_id}">
                <td>${b.manufacturer} ${b.model}</td>
                <td>${b.departure_airport}</td>
                <td>${b.arrival_airport}</td>
                <td>${depDate}</td>
                <td>${formatTime(b.flight_time_mins)}</td>
                <td>${parseFloat(b.distance_km).toFixed(1)} km</td>
                <td>${b.passengers}</td>
                <td>
                    <button class="cancel-btn"
                        onclick="cancelBooking(${b.booking_id})">
                        Cancel
                    </button>
                </td>
            </tr>
        `;
    }).join("");
}

// ── Cancel a booking ──────────────────────────────────────────
async function cancelBooking(bookingId) {
    if (!confirm("Are you sure you want to cancel this booking?")) return;

    const response = await fetch("api.php", {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify({
            type:       "RemoveBooking",
            apikey:     API_KEY,
            booking_id: bookingId
        })
    });

    const result = await response.json();

    if (result.status === "success") {
        // Remove the row from the table without reloading
        const row = document.getElementById("booking-row-" + bookingId);
        if (row) row.remove();

        // Update count
        const remaining = document.querySelectorAll("#bookingsBody tr").length;
        document.getElementById("bookingCount").textContent =
            remaining > 0 ? "You have " + remaining + " booking(s)." : "";

        if (remaining === 0) {
            document.getElementById("bookingsTableWrap").style.display = "none";
            document.getElementById("emptyMsg").style.display = "block";
        }
    } else {
        alert("Could not cancel: " + result.data);
    }
}

window.onload = loadBookings;
</script>

</body>
</html>