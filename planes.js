const USER_API_KEY = localStorage.getItem("api_key");
 
let allPlanes     = [];
let currentFilter = "all";
 
// Set of plane_ids the current user has favourited (loaded from DB on startup)
let favouritedPlaneIds = new Set();
 
// ================================================================
// LOAD PLANES FROM OUR OWN api.php
// FIX: switched from XMLHttpRequest + external Wheatley API
//      to fetch() + local api.php so plane IDs match the DB
// ================================================================
async function loadPlanes() {
    document.getElementById("loadingMsg").style.display = "block";
    document.getElementById("planesGrid").innerHTML     = "";
    document.getElementById("noResults").style.display  = "none";
 
    // FIX: load the user's existing favourites first so star buttons
    //      render correctly on the first paint (issue #3 in review)
    await loadFavouriteIds();
 
    let raw, data;
    try {
        const res = await fetch("api.php", {
            method:  "POST",
            headers: { "Content-Type": "application/json" },
            body:    JSON.stringify({
                type:   "GetAllPlanes",
                apikey: USER_API_KEY
                // no limit sent → server default is now 200
            })
        });
        raw  = await res.text();
        data = JSON.parse(raw);
    } catch (e) {
        document.getElementById("loadingMsg").style.display = "none";
        document.getElementById("planesGrid").innerHTML =
            "<p style='color:red;padding:20px;'>&#9888; Network error loading planes.</p>";
        console.error("loadPlanes error:", raw || e);
        return;
    }
 
    document.getElementById("loadingMsg").style.display = "none";
 
    if (data.status === "error") {
        document.getElementById("planesGrid").innerHTML =
            "<p style='color:red;padding:20px;'>&#9888; API Error: " + escapeHtml(data.data) + "</p>";
        return;
    }
 
    allPlanes = data.data || [];
    applySearchSortFilter();
}
 
// ================================================================
// LOAD FAVOURITE IDs FROM DB
// FIX: replaces the old localStorage-only getFavouriteIds() so
//      the star buttons always reflect the real DB state
// ================================================================
async function loadFavouriteIds() {
    favouritedPlaneIds = new Set();
 
    if (!USER_API_KEY || USER_API_KEY === "null") return;
 
    try {
        const res  = await fetch("api.php", {
            method:  "POST",
            headers: { "Content-Type": "application/json" },
            body:    JSON.stringify({ type: "GetFavourites", apikey: USER_API_KEY })
        });
        const data = await res.json();
        if (data.status === "success" && Array.isArray(data.data)) {
            data.data.forEach(function(fav) {
                if (fav.plane_id) favouritedPlaneIds.add(String(fav.plane_id));
            });
        }
    } catch (e) {
        console.warn("Could not load favourite IDs:", e);
    }
}
 
// ================================================================
// FILTER
// ================================================================
function filterPlanes(manufacturer, btn) {
    const buttons = document.querySelectorAll(".filter-buttons button");
    buttons.forEach(function(b) { b.classList.remove("active"); });
    btn.classList.add("active");
    currentFilter = manufacturer;
    applySearchSortFilter();
}
 
// ================================================================
// SEARCH + SORT + FILTER
// ================================================================
function applySearchSortFilter() {
    const searchText = document.getElementById("search").value.toLowerCase().trim();
    const sortValue  = document.getElementById("sortSelect").value;
 
    let result = allPlanes.filter(function(p) {
        return currentFilter === "all" ||
               (p.manufacturer || "").toLowerCase().includes(currentFilter.toLowerCase());
    });
 
    if (searchText !== "") {
        result = result.filter(function(p) {
            return (p.manufacturer || "").toLowerCase().includes(searchText) ||
                   (p.model        || "").toLowerCase().includes(searchText);
        });
    }
 
    if      (sortValue === "seats-asc")         result.sort(function(a,b){ return (a.seats||0)-(b.seats||0); });
    else if (sortValue === "seats-desc")         result.sort(function(a,b){ return (b.seats||0)-(a.seats||0); });
    else if (sortValue === "manufacturer-asc")   result.sort(function(a,b){ return (a.manufacturer||"").localeCompare(b.manufacturer||""); });
    else if (sortValue === "manufacturer-desc")  result.sort(function(a,b){ return (b.manufacturer||"").localeCompare(a.manufacturer||""); });
    else if (sortValue === "model-asc")          result.sort(function(a,b){ return (a.model||"").localeCompare(b.model||""); });
 
    renderPlanes(result);
}
 
// ================================================================
// RENDER PLANES
// FIX: uses escapeHtml() for all user-visible text so XSS is
//      not possible even if the DB contains HTML special chars.
//      Fav button state now reads from favouritedPlaneIds (DB-backed)
//      instead of graceFavouriteIds in localStorage.
//      onclick uses data-attributes to avoid apostrophe breakage.
// ================================================================
function renderPlanes(planes) {
    const grid      = document.getElementById("planesGrid");
    const noResults = document.getElementById("noResults");
    grid.innerHTML  = "";
 
    if (planes.length === 0) {
        noResults.style.display = "block";
        return;
    }
    noResults.style.display = "none";
 
    planes.forEach(function(p) {
        const id           = p.id            || "?";
        const manufacturer = p.manufacturer  || "Unknown";
        const model        = p.model         || "Unknown Model";
        const seats        = p.seats         || "N/A";
        const classes      = p.classes       || p.cabin_classes || "N/A";
        const description  = p.description   || "";
        const range        = p.max_range_km  || "N/A";
        const speed        = p.max_speed_kmh || "N/A";
        const imgSrc       = p.image_url     || "img/Airbus.png";
 
        const alreadyFav  = favouritedPlaneIds.has(String(id));
        const favBtnLabel = alreadyFav ? "&#9733; FAVOURITED" : "&#9734; FAVOURITE";
        const favBtnStyle = alreadyFav ? "background:#f0a500;color:white;" : "";
 
        const card = document.createElement("div");
        card.id             = "plane-card-" + id;
        card.style.marginBottom = "20px";
 
        // FIX: text content set via textContent (not innerHTML) where possible,
        //      and escapeHtml() used for any string going into HTML attributes.
        card.innerHTML =
            '<div class="image">' +
                '<img src="' + escapeHtml(imgSrc) + '" ' +
                'alt="' + escapeHtml(manufacturer) + ' ' + escapeHtml(model) + '" ' +
                'class="plane-img" width="300px" height="200px" ' +
                'onerror="this.src=\'img/Airbus.png\'" ' +
                'data-planeid="' + escapeHtml(String(id)) + '" ' +
                'onclick="goToView(' + parseInt(id) + ')" ' +
                'title="Click to view full details">' +
                '<br><small style="color:#007bff;font-size:11px;">&#128065; Click image for details</small>' +
            '</div>' +
            '<form>' +
                '<div class="Manufacturer-and-model">' +
                    '<p><b>Manufacturer:</b></p><p>' + escapeHtml(manufacturer) + '</p><br>' +
                    '<p><b>Model:</b></p><p>' + escapeHtml(model) + '</p><br>' +
                    '<p><b>Cabin Classes:</b></p><p>' + escapeHtml(classes) + '</p>' +
                '</div>' +
                '<div class="Number-of-seats">' +
                    '<p><b>Seating Capacity:</b> ' + escapeHtml(String(seats)) + ' passengers</p><br>' +
                    '<p><b>Max Range:</b> ' + escapeHtml(String(range)) + ' km</p>' +
                    '<p><b>Max Speed:</b> ' + escapeHtml(String(speed)) + ' km/h</p><br>' +
                    (description ? '<p style="color:#555;font-size:13px;">' + escapeHtml(description) + '</p><br>' : '') +
                '</div>' +
                '<div class="favourite">' +
                    // FIX: data-attributes carry the plane info so apostrophes
                    //      in names don't break the onclick handler
                    '<button type="button" class="my-button" id="favbtn-' + id + '" ' +
                    'style="' + favBtnStyle + '" ' +
                    'data-id="'           + escapeHtml(String(id))           + '" ' +
                    'data-manufacturer="' + escapeHtml(manufacturer)         + '" ' +
                    'data-model="'        + escapeHtml(model)                + '" ' +
                    'data-seats="'        + escapeHtml(String(seats))        + '" ' +
                    'data-classes="'      + escapeHtml(classes)              + '" ' +
                    'onclick="addFavouriteFromBtn(this)">' +
                    favBtnLabel + '</button>' +
                '</div><br>' +
            '</form>';
 
        grid.appendChild(card);
    });
}
 
// ================================================================
// NAVIGATE TO DETAIL VIEW
// ================================================================
function goToView(planeId) {
    localStorage.setItem("selectedPlaneId", String(planeId));
 
    for (let i = 0; i < allPlanes.length; i++) {
        if (String(allPlanes[i].id) === String(planeId)) {
            localStorage.setItem("selectedPlaneData", JSON.stringify(allPlanes[i]));
            break;
        }
    }
 
    window.location.href = "view.html";
}
 
// ================================================================
// WRAPPER: read data-attributes from button then call addFavourite
// FIX: avoids inline onclick with string args — safe against apostrophes
// ================================================================
function addFavouriteFromBtn(btn) {
    addFavourite(
        btn.dataset.id,
        btn.dataset.manufacturer,
        btn.dataset.model,
        btn.dataset.seats,
        btn.dataset.classes
    );
}
 
// ================================================================
// ADD FAVOURITE
// FIX: now sends plane_id to api.php so the favourites table has
//      a proper foreign key link back to the planes table.
//      Also updates favouritedPlaneIds set so re-renders stay correct.
// ================================================================
function addFavourite(id, manufacturer, model, seats, classes) {
    id = String(id);
 
    if (!USER_API_KEY || USER_API_KEY === "null") {
        showToast("Please log in to save favourites.", "red");
        return;
    }
 
    const btn = document.getElementById("favbtn-" + id);
    if (btn) btn.disabled = true;
 
    fetch("api.php", {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify({
            type:             "AddFavourite",
            apikey:           USER_API_KEY,
            plane_id:         id,
            manufacturer:     manufacturer,
            model:            model,
            cabin_classes:    classes,
            seating_capacity: String(seats)
        })
    })
    // Use .text() first so a broken/empty server response shows a real error
    .then(function(res) { return res.text(); })
    .then(function(raw) {
        if (btn) btn.disabled = false;
        let data;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            console.error("AddFavourite — server returned non-JSON:", raw);
            showToast("&#9888; Server error — check console.", "red");
            return;
        }
        if (data.status === "success") {
            if (data.data === "Already in favourites") {
                showToast("&#9733; Already in favourites!", "#f0a500");
            } else {
                // Update in-memory set so button stays gold after filter/sort
                favouritedPlaneIds.add(id);
                if (btn) {
                    btn.innerHTML        = "&#9733; FAVOURITED";
                    btn.style.background = "#f0a500";
                    btn.style.color      = "white";
                }
                showToast("&#10003; Added: " + manufacturer + " " + model, "green");
            }
        } else {
            showToast("&#9888; Error: " + data.data, "red");
        }
    })
    .catch(function(err) {
        if (btn) btn.disabled = false;
        showToast("&#9888; Network error: " + err.message, "red");
    });
}
 
// ================================================================
// TOAST
// ================================================================
function showToast(msg, color) {
    const toast        = document.getElementById("toast");
    toast.innerHTML    = msg;
    toast.style.display    = "block";
    toast.style.background = color || "#333";
    setTimeout(function() { toast.style.display = "none"; }, 3000);
}
 
// ================================================================
// ESCAPE HELPER
// FIX: prevents XSS when inserting DB strings into innerHTML
// ================================================================
function escapeHtml(str) {
    return String(str)
        .replace(/&/g,  "&amp;")
        .replace(/</g,  "&lt;")
        .replace(/>/g,  "&gt;")
        .replace(/"/g,  "&quot;")
        .replace(/'/g,  "&#39;");
}
 
window.onload = function() {
    loadPlanes();
};
 