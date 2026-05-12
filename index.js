const API_KEY = localStorage.getItem("api_key");
const API_URL = "api.php";
var BATCH     = 25;
 
// ── Guard: redirect to login if no API key found ─────────────
if (!API_KEY || API_KEY === "null" || API_KEY === "undefined") {
    window.location.href = "login.php";
}
 
// ── Safe showMessage fallback ─────────────────────────────────
if (typeof showMessage !== "function") {
    window.showMessage = function(msg, color) {
        var el = document.getElementById("apiMessage");
        if (el) {
            el.textContent   = msg;
            el.style.color   = color || "red";
            el.style.display = "block";
        }
        console.warn("showMessage:", msg);
    };
}
 
// ── Failsafe: force-hide loader after 12 seconds ─────────────
setTimeout(function () {
    var loader = document.getElementById("pageLoader");
    if (loader && loader.style.display !== "none") {
        loader.style.display = "none";
        console.warn("Loader force-hidden after timeout.");
    }
}, 12000);
 
// ── Loader state ─────────────────────────────────────────────
var loaderDone = { airports: false, planes: false };
 
function setLoaderProgress(percent, text) {
    var bar = document.getElementById("loaderBar");
    var txt = document.getElementById("loaderText");
    if (bar) bar.style.width = percent + "%";
    if (txt) txt.textContent = text;
}
 
function checkAllLoaded() {
    if (loaderDone.airports && loaderDone.planes) {
        setLoaderProgress(100, "Ready!");
        setTimeout(function () {
            var loader = document.getElementById("pageLoader");
            if (loader) {
                loader.classList.add("fade-out");
                setTimeout(function () { loader.style.display = "none"; }, 600);
            }
        }, 400);
    }
}
 
// ── AIRPORTS ─────────────────────────────────────────────────
var allAirports   = [];
var airportLoaded = false;
 
function loadAirports() {
    if (airportLoaded) return;
    setLoaderProgress(20, "Loading airports...");
 
    fetch(API_URL, {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify({ type: "GetAllAirports", apikey: API_KEY, limit: 500, page: 1 })
    })
    .then(function (res) { return res.text(); })
    .then(function (raw) {
        console.log("GetAllAirports:", raw);
        var data;
        try { data = JSON.parse(raw); }
        catch (e) {
            showMessage("Airport load failed — server error. Check console.", "red");
            console.error("Non-JSON from GetAllAirports:", raw);
            loaderDone.airports = true; checkAllLoaded(); return;
        }
        if (data.status === "error") {
            if (data.data && data.data.toLowerCase().includes("api key")) {
                localStorage.removeItem("api_key"); window.location.href = "login.php"; return;
            }
            showMessage("Airport error: " + data.data, "red");
            loaderDone.airports = true; checkAllLoaded(); return;
        }
        allAirports = data.data || [];
        airportLoaded = true;
        loaderDone.airports = true;
        setLoaderProgress(50, "Airports loaded ✔ (" + allAirports.length + ")");
        checkAllLoaded();
        searchAirport("from");
        searchAirport("to");
    })
    .catch(function (err) {
        showMessage("Failed to load airports: " + err.message, "red");
        loaderDone.airports = true; checkAllLoaded();
    });
}
 
// ── PLANES ───────────────────────────────────────────────────
var allPlanes    = [];
var planesLoaded = false;
 
function loadPlanes() {
    if (planesLoaded) return;
    setLoaderProgress(60, "Loading aircraft...");
 
    var planeList = document.getElementById("planeList");
    if (planeList) {
        planeList.innerHTML     = "<div style='padding:8px;color:#888;'>Loading aircraft...</div>";
        planeList.style.display = "block";
    }
 
    fetch(API_URL, {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify({ type: "GetAllPlanes", apikey: API_KEY, limit: 500, page: 1 })
    })
    .then(function (res) { return res.text(); })
    .then(function (raw) {
        console.log("GetAllPlanes:", raw);
        var data;
        try { data = JSON.parse(raw); }
        catch (e) {
            showMessage("Plane load failed — server error. Check console.", "red");
            console.error("Non-JSON from GetAllPlanes:", raw);
            loaderDone.planes = true; checkAllLoaded(); return;
        }
        if (data.status === "error") {
            if (data.data && data.data.toLowerCase().includes("api key")) {
                localStorage.removeItem("api_key"); window.location.href = "login.php"; return;
            }
            if (planeList) planeList.innerHTML = "<div style='padding:8px;color:red;'>⚠ " + data.data + "</div>";
            loaderDone.planes = true; checkAllLoaded(); return;
        }
        allPlanes    = data.data || [];
        planesLoaded = true;
        loaderDone.planes = true;
        setLoaderProgress(90, "Aircraft loaded ✔ (" + allPlanes.length + ")");
        checkAllLoaded();
        populateManufacturerDropdown();
        searchPlane();
    })
    .catch(function (err) {
        showMessage("Failed to load planes: " + err.message, "red");
        loaderDone.planes = true; checkAllLoaded();
    });
}
 
// ── Populate manufacturer dropdown ────────────────────────────
function populateManufacturerDropdown() {
    var select = document.getElementById("calcManufacturer");
    if (!select) return;
    var seen = {};
    allPlanes.forEach(function (p) { if (p.manufacturer) seen[p.manufacturer] = true; });
    select.innerHTML = '<option value="all">All manufacturers</option>';
    Object.keys(seen).sort().forEach(function (name) {
        var opt = document.createElement("option");
        opt.value = name; opt.textContent = name;
        select.appendChild(opt);
    });
}
 
// ── Search planes ─────────────────────────────────────────────
function searchPlane() {
    var query  = (document.getElementById("planeSearch") || {}).value || "";
    var listEl = document.getElementById("planeList");
    if (!listEl) return;
    query = query.toLowerCase().trim();
 
    var filtered = allPlanes.filter(function (p) {
        return (p.manufacturer + " " + p.model).toLowerCase().includes(query);
    });
 
    if (filtered.length === 0) {
        listEl.innerHTML = "<div style='padding:8px;color:#aaa;'>No aircraft found.</div>";
        listEl.style.display = "block"; return;
    }
 
    listEl.innerHTML = "";
    filtered.slice(0, 30).forEach(function (p) {
        var div = document.createElement("div");
        div.className   = "dropdown-item";
        div.textContent = p.manufacturer + " " + p.model + " (" + p.seats + " seats)";
        div.style.cssText = "padding:8px 12px;cursor:pointer;";
        div.onmouseenter = function () { div.style.background = "#f0f0f0"; };
        div.onmouseleave = function () { div.style.background = ""; };
        div.onclick = function () {
            document.getElementById("planeSearch").value = p.manufacturer + " " + p.model;
            document.getElementById("planeValue").value  = p.id;
            document.getElementById("planeSelected").textContent =
                "✔ " + p.manufacturer + " " + p.model + " — " + p.seats + " seats";
            listEl.style.display = "none";
            updateCabinClasses(p);
        };
        listEl.appendChild(div);
    });
    listEl.style.display = "block";
}
 
// ── Update cabin classes ──────────────────────────────────────
function updateCabinClasses(plane) {
    var select = document.getElementById("cabinClass");
    var hint   = document.getElementById("cabinHint");
    if (!select) return;
 
    var classes = (plane.cabin_classes || "Economy,Business,First").split(",");
    select.innerHTML = "";
    classes.forEach(function (cls) {
        cls = cls.trim();
        if (cls) {
            var opt = document.createElement("option");
            opt.value = cls;
            opt.textContent = cls;
            select.appendChild(opt);
        }
    });
    select.disabled = false;
    if (hint) hint.textContent = "Available classes for this aircraft.";
}
 
// ── Search airports ───────────────────────────────────────────
function searchAirport(direction) {
    var inputId = direction === "from" ? "fromSearch"   : "toSearch";
    var listId  = direction === "from" ? "fromList"     : "toList";
    var valueId = direction === "from" ? "fromValue"    : "toValue";
    var labelId = direction === "from" ? "fromSelected" : "toSelected";
 
    var query  = (document.getElementById(inputId) || {}).value || "";
    var listEl = document.getElementById(listId);
    if (!listEl) return;
    query = query.toLowerCase().trim();
 
    var filtered = allAirports.filter(function (a) {
        return (a.name + " " + a.city + " " + a.country + " " + a.code)
            .toLowerCase().includes(query);
    });
 
    if (filtered.length === 0) {
        listEl.innerHTML = "<div style='padding:8px;color:#aaa;'>No airports found.</div>";
        listEl.style.display = "block"; return;
    }
 
    listEl.innerHTML = "";
    filtered.slice(0, 30).forEach(function (a) {
        var div = document.createElement("div");
        div.className   = "dropdown-item";
        div.textContent = a.name + " (" + a.code + ") — " + a.city + ", " + a.country;
        div.style.cssText = "padding:8px 12px;cursor:pointer;";
        div.onmouseenter = function () { div.style.background = "#f0f0f0"; };
        div.onmouseleave = function () { div.style.background = ""; };
        div.onclick = function () {
            document.getElementById(inputId).value       = a.name + " (" + a.code + ")";
            document.getElementById(valueId).value       = a.code;
            document.getElementById(labelId).textContent = "✔ " + a.name + " — " + a.city + ", " + a.country;
            listEl.style.display = "none";
        };
        listEl.appendChild(div);
    });
    listEl.style.display = "block";
}
 
// ── Seat calculator ───────────────────────────────────────────
function calculateSeats() {
    var min          = parseInt(document.getElementById("minSeats").value) || 0;
    var max          = parseInt(document.getElementById("maxSeats").value) || 9999;
    var manufacturer = document.getElementById("calcManufacturer").value;
    var resultEl     = document.getElementById("calcResult");
    var loadingEl    = document.getElementById("calcLoading");
    if (loadingEl) loadingEl.style.display = "none";
 
    var filtered = allPlanes.filter(function (p) {
        var seats = parseInt(p.seats) || 0;
        var mfOk  = manufacturer === "all" || p.manufacturer === manufacturer;
        return seats >= min && seats <= max && mfOk;
    });
 
    if (!resultEl) return;
    if (filtered.length === 0) {
        resultEl.innerHTML = "<p style='color:#888;'>No aircraft match those criteria.</p>";
    } else {
        resultEl.innerHTML =
            "<p><strong>" + filtered.length + "</strong> aircraft found with " +
            min + "–" + max + " seats" +
            (manufacturer !== "all" ? " from <strong>" + manufacturer + "</strong>" : "") + ".</p>" +
            "<ul style='margin-top:8px;'>" +
            filtered.map(function (p) {
                return "<li>" + p.manufacturer + " " + p.model + " — " + p.seats + " seats</li>";
            }).join("") + "</ul>";
    }
}
 
// ── Submit booking ────────────────────────────────────────────
// FIX: Now sends first_name, last_name, email, and cabin_class to API
function submitBooking() {
    var fname      = (document.getElementById("fname")        || {}).value || "";
    var lname      = (document.getElementById("lname")        || {}).value || "";
    var email      = (document.getElementById("email")        || {}).value || "";
    var travelDate = (document.getElementById("travelDate")   || {}).value || "";
    var noppl      = (document.getElementById("noppl")        || {}).value || "1";
    var fromCode   = (document.getElementById("fromValue")    || {}).value || "";
    var toCode     = (document.getElementById("toValue")      || {}).value || "";
    var planeId    = (document.getElementById("planeValue")   || {}).value || "";
    var cabinClass = (document.getElementById("cabinClass")   || {}).value || "";
    var isReturn   = !!(document.getElementById("returnFlight") || {}).checked;
    var returnDate = (document.getElementById("returnDate")   || {}).value || "";
 
    // ── Validation ────────────────────────────────────────────
    if (!fname || !lname || !email) {
        showMessage("Please fill in your first name, last name and email.", "red"); return;
    }
    if (!travelDate) {
        showMessage("Please pick a travel date.", "red"); return;
    }
    if (!fromCode) {
        showMessage("Please select a departure airport from the dropdown.", "red"); return;
    }
    if (!toCode) {
        showMessage("Please select a destination airport from the dropdown.", "red"); return;
    }
    if (fromCode === toCode) {
        showMessage("Departure and destination cannot be the same airport.", "red"); return;
    }
    if (!planeId) {
        showMessage("Please select an aircraft from the dropdown.", "red"); return;
    }
    if (!cabinClass) {
        showMessage("Please select a cabin class.", "red"); return;
    }
    if (isReturn && !returnDate) {
        showMessage("Please pick a return date.", "red"); return;
    }
 
    // ── Build request body ────────────────────────────────────
    // FIX: Include all user details that API expects
    var body = {
        type:              "BookFlight",
        apikey:            API_KEY,
        plane_id:          parseInt(planeId),
        departure_airport: fromCode,
        arrival_airport:   toCode,
        departure_date:    travelDate,
        passengers:        parseInt(noppl),
        cabin_class:       cabinClass,
        first_name:        fname,      // FIX: Added
        last_name:         lname,      // FIX: Added
        email:             email       // FIX: Added
    };
    if (isReturn) body.return_date = returnDate;
 
    console.log("Sending BookFlight:", body);
    showMessage("Submitting booking...", "orange");
 
    fetch(API_URL, {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify(body)
    })
    .then(function (res) { return res.text(); })
    .then(function (raw) {
        console.log("BookFlight raw response:", raw);
        var data;
        try { data = JSON.parse(raw); }
        catch (e) {
            showMessage("Server error — could not parse response. Check console.", "red");
            console.error("Non-JSON from BookFlight:", raw);
            return;
        }
        if (data.status === "success") {
            showMessage(
                "✔ Booking confirmed! Booking ID: " + data.data.outbound.booking_id +
                (data.data.return ? "  |  Return ID: " + data.data.return.booking_id : ""),
                "green"
            );
        } else {
            showMessage("Booking failed: " + data.data, "red");
        }
    })
    .catch(function (err) {
        showMessage("Network error: " + err.message, "red");
        console.error("BookFlight fetch error:", err);
    });
}
 
// ── FAQ toggle ────────────────────────────────────────────────
function toggleFaq(btn) {
    var answer = btn.nextElementSibling;
    var arrow  = btn.querySelector(".faq-arrow");
    if (!answer) return;
    var isOpen = answer.style.display === "block";
    answer.style.display = isOpen ? "none" : "block";
    if (arrow) arrow.style.transform = isOpen ? "rotate(0deg)" : "rotate(180deg)";
}
 
// ── Return date toggle ────────────────────────────────────────
function toggleReturnDate() {
    var cb  = document.getElementById("returnFlight");
    var div = document.getElementById("returnDateDiv");
    if (cb && div) div.style.display = cb.checked ? "block" : "none";
}
 
// ── Close dropdowns when clicking outside ────────────────────
document.addEventListener("click", function (e) {
    var dropdowns = ["fromList", "toList", "planeList"];
    dropdowns.forEach(function (id) {
        var el     = document.getElementById(id);
        var parent = el && el.closest(".search-box");
        if (el && parent && !parent.contains(e.target)) {
            el.style.display = "none";
        }
    });
});
 
// ── Kick off loading on page ready ───────────────────────────
document.addEventListener("DOMContentLoaded", function () {
    loadAirports();
    loadPlanes();
});
 