

    // WHY localStorage AND NOT sessionStorage?
    //
    // localStorage   - persists after browser is closed/refreshed.
    //                  Favourites stay saved permanently.
    //
    // sessionStorage - deleted when tab is closed. Favourites
    //                  would disappear every session. NOT suitable.
    //
    // CHOICE: localStorage - favourites must persist between sessions.
 // Retrieve the key stored during login
// favourites.js
// Reads the api_key from localStorage (stored there at login)
// Communicates with api.php using POST JSON — same as all other API calls
 const API_KEY = localStorage.getItem("api_key");
 
// ── Escape helper ─────────────────────────────────────────────
// FIX: prevents XSS when inserting DB strings into innerHTML
function escapeHtml(str) {
    return String(str)
        .replace(/&/g,  "&amp;")
        .replace(/</g,  "&lt;")
        .replace(/>/g,  "&gt;")
        .replace(/"/g,  "&quot;")
        .replace(/'/g,  "&#39;");
}
 
// ── Load and display favourites ───────────────────────────────
async function loadFavourites() {
    const grid     = document.getElementById("favouritesGrid");
    const emptyMsg = document.getElementById("emptyMsg");
    const favCount = document.getElementById("favCount");
 
    if (!API_KEY || API_KEY === "null") {
        emptyMsg.innerHTML     = "Please <a href='login.php'>log in</a> to see your favourites.";
        emptyMsg.style.display = "block";
        return;
    }
 
    grid.innerHTML = "<p style='color:#888;padding:20px;'>Loading your favourites...</p>";
 
    let raw, data;
    try {
        const res = await fetch("api.php", {
            method:  "POST",
            headers: { "Content-Type": "application/json" },
            body:    JSON.stringify({ type: "GetFavourites", apikey: API_KEY })
        });
        raw  = await res.text();
        data = JSON.parse(raw);
    } catch (e) {
        emptyMsg.innerHTML     = "Error loading favourites. Check console.";
        emptyMsg.style.display = "block";
        grid.innerHTML         = "";
        console.error("GetFavourites error:", raw || e);
        return;
    }
 
    if (data.status === "error") {
        emptyMsg.innerHTML     = "Could not load favourites: " + escapeHtml(data.data);
        emptyMsg.style.display = "block";
        grid.innerHTML         = "";
        return;
    }
 
    const planes = data.data || [];
 
    favCount.textContent = planes.length > 0
        ? "You have " + planes.length + " favourite plane(s)."
        : "";
 
    if (planes.length === 0) {
        emptyMsg.style.display = "block";
        grid.innerHTML         = "";
        return;
    }
 
    emptyMsg.style.display = "none";
 
    // FIX: use escapeHtml() for all DB text going into innerHTML
    //      and data-attributes on the button instead of inline string
    //      arguments — prevents XSS and apostrophe breakage (issues #6 & #7)
    grid.innerHTML = planes.map(function(plane) {
        const safeName = escapeHtml(plane.manufacturer + " " + plane.model);
        return `
            <div class="plane-card" id="fav-card-${escapeHtml(String(plane.id))}">
                <h3>${escapeHtml(plane.manufacturer)} ${escapeHtml(plane.model)}</h3>
                <p><b>Seats:</b> ${escapeHtml(String(plane.seating_capacity || "N/A"))}</p>
                <p><b>Classes:</b> ${escapeHtml(String(plane.cabin_classes || "N/A"))}</p>
                <button
                    class="my-button"
                    data-favid="${escapeHtml(String(plane.id))}"
                    data-name="${safeName}"
                    onclick="removeFavouriteFromBtn(this)">
                    &#9733; Remove from Favourites
                </button>
            </div>
        `;
    }).join("");
}
 
// ── Wrapper: read data-attributes then call removeFavourite ───
// FIX: avoids inline onclick with string arguments so apostrophes
//      in plane names (e.g. "Learjet 45'") don't break the JS
function removeFavouriteFromBtn(btn) {
    removeFavourite(btn.dataset.favid, btn.dataset.name);
}
 
// ── Remove a single favourite ─────────────────────────────────
async function removeFavourite(favId, planeName) {
    if (!confirm("Remove " + planeName + " from favourites?")) return;
 
    let raw, data;
    try {
        const res = await fetch("api.php", {
            method:  "POST",
            headers: { "Content-Type": "application/json" },
            body:    JSON.stringify({
                type:   "RemoveFavourite",
                apikey: API_KEY,
                fav_id: favId          // uses the favourites table row id
            })
        });
        raw  = await res.text();
        data = JSON.parse(raw);
    } catch (e) {
        alert("Error removing favourite. Check console.");
        console.error("RemoveFavourite error:", raw || e);
        return;
    }
 
    if (data.status === "error") {
        alert("Could not remove: " + data.data);
        return;
    }
 
    // Remove card from DOM without page reload
    const card = document.getElementById("fav-card-" + favId);
    if (card) card.remove();
 
    // Update count
    const grid      = document.getElementById("favouritesGrid");
    const remaining = grid.querySelectorAll(".plane-card").length;
    const favCount  = document.getElementById("favCount");
    if (remaining === 0) {
        document.getElementById("emptyMsg").style.display = "block";
        if (favCount) favCount.textContent = "";
    } else {
        if (favCount) favCount.textContent = "You have " + remaining + " favourite plane(s).";
    }
}
 
window.onload = loadFavourites;
 