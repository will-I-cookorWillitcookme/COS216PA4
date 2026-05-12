<!DOCTYPE html>
<html>
<head>
    <title>Grace Airways — Sign Up</title>
    <link href="css/Stylesignup.css" rel="stylesheet">
  
</head>

<body>

<div id="signupBox">

    <div class="signup-icon">✈</div>
    <h2>Create Account</h2>
    <p class="brand">Grace Airways</p>

    <hr class="divider">

    <form action="api.php" method="POST" id="signup">
        <input type="hidden" name="type" value="Register">

        <label for="name">First Name</label>
        <input type="text" id="name" name="name" placeholder="e.g. John">

        <label for="surname">Last Name</label>
        <input type="text" id="surname" name="surname" placeholder="e.g. Smith">

        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" placeholder="your@email.com">
        <small id="emailError"></small>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Min 9 chars, upper, lower, digit, symbol">
        <small id="passwordError"></small>

        <label for="type">Account Type</label>
        <input type="text" id="type" name="user_type" placeholder="e.g. passenger">
        <span class="field-hint">Enter: passenger, admin, or pilot</span>

        <hr class="divider">

        <button type="submit">Sign Up!</button>

    </form>

    <p>Already have an account? <a href="login.php">Log in here</a></p>
<script>

const submitform = document.getElementById("signup");

submitform.addEventListener("submit", function(event){

    let isValid = true;

    const email = document.getElementById("email").value;
    const password = document.getElementById("password").value;

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    // 8+ chars, uppercase, lowercase, digit, symbol
    const passwordRegex =
    /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;

    // Email validation
    if (!emailRegex.test(email)) {

        document.getElementById("emailError").innerText =
        "Enter a valid email address";

        isValid = false;

    } else {

        document.getElementById("emailError").innerText = "";
    }

    // Password validation
    if (!passwordRegex.test(password)) {

        document.getElementById("passwordError").innerText =
        "Password must contain uppercase, lowercase, number and symbol";

        isValid = false;

    } else {

        document.getElementById("passwordError").innerText = "";
    }

    // Stop form if invalid
    if (!isValid) {
        event.preventDefault();
    } else {
        alert("Registration successful");
    }

});

</script>

</body>
</html>