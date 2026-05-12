<!DOCTYPE html>
<html>
<head>
    <title>Grace Airways — Login</title>
    <link href="css/login.css" rel="stylesheet">
</head>
<body>
 
<!-- NO form tag — we use fetch() instead so the page never reloads -->
<div id="loginBox">
 
    <div class="login-icon">✈</div>
    <h2>Welcome Back</h2>
    <p class="brand">Grace Airways</p>
 
    <hr class="divider">
 
    <label for="loginEmail">Email Address</label>
    <input type="email" id="loginEmail" placeholder="your@email.com">
 
    <label for="loginPassword">Password</label>
    <input type="password" id="loginPassword" placeholder="Enter your password">
 
    <p id="loginError"></p>
 
    <button onclick="doLogin()">LOG IN</button>
 
    <hr class="divider">
 
    <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
 
</div>
<script>
async function doLogin() {
 
    console.log("doLogin() fired");
 
    const email    = document.getElementById("loginEmail").value.trim();
    const password = document.getElementById("loginPassword").value;
    const errorEl  = document.getElementById("loginError");
 
    // Clear any previous error
    errorEl.textContent = "";
    errorEl.classList.remove("visible");
 
    if (!email || !password) {
        errorEl.textContent = "Fill in both fields.";
        errorEl.classList.add("visible");
        return;
    }
 
    console.log("Sending fetch to api.php...");
 
    try {
        const response = await fetch("api.php", {
            method:  "POST",
            headers: { "Content-Type": "application/json" },
            body:    JSON.stringify({
                type:     "Login",
                email:    email,
                password: password
            })
        });
 
        const raw = await response.text();
        console.log("Raw server response:", raw);
 
        let result;
        try {
            result = JSON.parse(raw);
        } catch (parseErr) {
            // PHP error or non-JSON output — show the raw output so you can debug
            errorEl.textContent = "Server error — check console for details.";
            errorEl.classList.add("visible");
            console.error("Non-JSON response from server:", raw);
            return;
        }
 
        console.log("Parsed result:", result);
 
        if (result.status === "success") {
 
            // ── FIX: guard against null/missing api key ──────────────
            var apiKey = result.data && result.data[0] && result.data[0].apikey
                ? result.data[0].apikey
                : null;
 
            if (!apiKey) {
                errorEl.textContent = "Login failed: no API key returned. Check server logs.";
                errorEl.classList.add("visible");
                console.error("API returned success but apikey is missing:", result);
                return;
            }
 
            // Store the key and redirect
            localStorage.setItem("api_key", apiKey);
            console.log("API key stored, redirecting to index.php...");
            window.location.href = "index.php";
 
        } else {
            errorEl.textContent = result.data || "Login failed.";
            errorEl.classList.add("visible");
        }
 
    } catch (err) {
        console.error("Error in doLogin:", err);
        errorEl.textContent = "Network error: " + err.message;
        errorEl.classList.add("visible");
    }
}
</script>
 
</body>
</html>