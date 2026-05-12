

<?php
ob_start(); // buffer any stray PHP warnings so they never corrupt JSON output
require_once("config.php");
ob_clean(); // discard any output config.php may have produced
header("Content-Type: application/json");
 
function respond($code, $status, $message) {
    http_response_code($code);
    echo json_encode([
        "status"    => $status,
        "timestamp" => time(),
        "data"      => $message
    ]);
    exit;
}
 
$data = json_decode(file_get_contents("php://input"), true);
 
if (!$data || count($data) == 0) {
    $data = $_POST;
}
 
if (!$data || count($data) == 0) {
    respond(400, "error", "Post parameters are missing");
}
 
if (!isset($data["type"])) {
    respond(400, "error", "Missing type");
}
 

// REGISTER — no api key needed
// =====
if ($data["type"] == "Register" || $data["type"] == "signup") {
    if (
        empty($data["name"])      || empty($data["surname"]) ||
        empty($data["email"])     || empty($data["password"]) ||
        empty($data["user_type"])
    ) {
        respond(400, "error", "Missing fields");
    }
 
    if (!preg_match("/^[^@\s]+@[^@\s]+\.[^@\s]+$/", $data["email"])) {
        respond(400, "error", "Invalid email");
    }
 
    if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z0-9]).{9,}$/", $data["password"])) {
        respond(400, "error", "Weak password");
    }
 
    $stmt = $connection->prepare("SELECT email FROM user WHERE email = ?");
    $stmt->bind_param("s", $data["email"]);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        respond(400, "error", "Email already exists");
    }
 
    $salt = bin2hex(random_bytes(16));
    $hashedPassword = hash("sha256", $salt . $data["password"]);
    $apiKey   = bin2hex(random_bytes(10));
 
    $stmt = $connection->prepare("
        INSERT INTO user (name, surname, email, password, type, api_key, salt)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssssss",
        $data["name"], $data["surname"], $data["email"],
        $hashedPassword, $data["user_type"], $apiKey, $salt
    );
 
    if (!$stmt->execute()) {
        respond(500, "error", "Database insert failed");
    }
 
    respond(200, "success", [["apikey" => $apiKey]]);
}
 

// LOGIN — no api key needed, this is how you GET the key
// ============================================================
if ($data["type"] == "Login") {
 
    if (empty($data["email"]) || empty($data["password"])) {
        respond(400, "error", "Missing email or password");
    }
 
    $stmt = $connection->prepare(
        "SELECT user_id, password, salt, api_key FROM user WHERE email = ?"
    );
    $stmt->bind_param("s", $data["email"]);
    $stmt->execute();
    $result = $stmt->get_result();
 
    if ($result->num_rows === 0) {
        respond(401, "error", "Invalid email or password");
    }
 
    $row           = $result->fetch_assoc();
    $hashedAttempt = hash("sha256", $row["salt"] . $data["password"]);
 
    if ($hashedAttempt !== $row["password"]) {
        respond(401, "error", "Invalid email or password");
    }
 
    respond(200, "success", [
        ["apikey" => $row["api_key"]]
    ]);
}
 

// ALL ROUTES BELOW REQUIRE A VALID API KEY
// ============================================================
if (!isset($data["apikey"])) {
    respond(400, "error", "Missing API key");
}
 
$stmt = $connection->prepare("SELECT * FROM user WHERE api_key = ?");
$stmt->bind_param("s", $data["apikey"]);
$stmt->execute();
$userResult = $stmt->get_result();
 
if ($userResult->num_rows != 1) {
    respond(403, "error", "Invalid API key");
}
 
// Fetch user row once here so all routes can use $currentUser / $currentUserId
$currentUser   = $userResult->fetch_assoc();
$currentUserId = (int)$currentUser["user_id"];
 

// GET ALL PLANES  (FIX: default limit raised to 200 so all planes show)
// =========================
if ($data["type"] == "GetAllPlanes") {
    $query  = "SELECT * FROM planes WHERE 1 = 1";
    $params = [];
    $types  = "";
 
    if (isset($data["search"]) && is_array($data["search"])) {
        $allowed = ["id", "manufacturer", "model", "seats"];
        $fuzzy   = isset($data["fuzzy"]) && $data["fuzzy"] === true;
 
        foreach ($data["search"] as $key => $value) {
            if (in_array($key, $allowed)) {
                if ($key == "id" || $key == "seats") {
                    $query   .= " AND $key = ?";
                    $params[] = (int)$value;
                    $types   .= "i";
                } else {
                    if ($fuzzy) {
                        $query   .= " AND $key LIKE ?";
                        $params[] = "%" . $value . "%";
                    } else {
                        $query   .= " AND $key = ?";
                        $params[] = $value;
                    }
                    $types .= "s";
                }
            }
        }
    }
 
    $allowedSort  = ["id", "manufacturer", "model", "seats"];
    $allowedOrder = ["ASC", "DESC"];
 
    if (
        isset($data["sort"], $data["order"]) &&
        in_array($data["sort"],  $allowedSort) &&
        in_array($data["order"], $allowedOrder)
    ) {
        $query .= " ORDER BY " . $data["sort"] . " " . $data["order"];
    }
 
    // FIX: raised default limit from 25 → 200 so all planes are returned
    $limit = 200;
    if (isset($data["limit"])) {
        $limit = (int)$data["limit"];
        if ($limit < 1 || $limit > 800) {
            respond(400, "error", "Invalid limit");
        }
    }
 
    $page   = isset($data["page"]) ? (int)$data["page"] : 1;
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $limit;
    $query .= " LIMIT $limit OFFSET $offset";
 
    $stmt = $connection->prepare($query);
    if (!$stmt) respond(500, "error", "SQL prepare failed");
 
    if (!empty($params)) {
        $bind   = [];
        $bind[] = $types;
        for ($i = 0; $i < count($params); $i++) {
            $bind[] = &$params[$i];
        }
        call_user_func_array([$stmt, "bind_param"], $bind);
    }
 
    $stmt->execute();
    $result = $stmt->get_result();
 
    $planes = [];
    while ($row = $result->fetch_assoc()) {
        $planes[] = $row;
    }
 
    respond(200, "success", $planes);
}
 

// GET ALL AIRPORTS
// ============================================================
if ($data["type"] == "GetAllAirports") {
    $query  = "SELECT * FROM airports WHERE 1=1";
    $params = [];
    $types  = "";
 
    if (isset($data["search"]) && is_array($data["search"])) {
        $allowed = ["id", "name", "code", "country", "city"];
        foreach ($data["search"] as $key => $value) {
            if (in_array($key, $allowed)) {
                $query   .= " AND $key LIKE ?";
                $params[] = "%" . $value . "%";
                $types   .= "s";
            }
        }
    }
 
    $limit = 500;
    if (isset($data["limit"])) {
        $limit = (int)$data["limit"];
        if ($limit < 1 || $limit > 1000) {
            respond(400, "error", "Invalid limit");
        }
    }
 
    $page   = isset($data["page"]) ? (int)$data["page"] : 1;
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $limit;
    $query .= " LIMIT $limit OFFSET $offset";
 
    $stmt = $connection->prepare($query);
    if (!$stmt) respond(500, "error", "SQL prepare failed");
 
    if (!empty($params)) {
        $bind   = [];
        $bind[] = $types;
        for ($i = 0; $i < count($params); $i++) {
            $bind[] = &$params[$i];
        }
        call_user_func_array([$stmt, "bind_param"], $bind);
    }
 
    $stmt->execute();
    $result = $stmt->get_result();
 
    $airports = [];
    while ($row = $result->fetch_assoc()) {
        $airports[] = $row;
    }
 
    respond(200, "success", $airports);
}
 

// GET FAVOURITES
// ===============
if ($data["type"] == "GetFavourites") {
    $userId = $currentUserId;
 
    $stmt = $connection->prepare("
        SELECT f.id, f.plane_id, p.manufacturer, p.model, p.seats, p.cabin_classes
        FROM favourites f
        INNER JOIN planes p ON f.plane_id = p.id
        WHERE f.user_id = ?
        ORDER BY f.created_at DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
 
    $fav = [];
    while ($row = $result->fetch_assoc()) {
        $fav[] = $row;
    }
 
    respond(200, "success", $fav);
}
 
// ADD FAVOURITE
// ============================================================
if ($data["type"] == "AddFavourite") {
    foreach (["plane_id"] as $f) {
        if (empty($data[$f])) respond(400, "error", "Missing field: $f");
    }
 
    $planeId = (int)$data["plane_id"];
    $userId  = $currentUserId;
 
    $checkStmt = $connection->prepare("
        SELECT id FROM favourites WHERE user_id = ? AND plane_id = ?
    ");
    $checkStmt->bind_param("ii", $userId, $planeId);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        respond(200, "success", "Already in favourites");
    }
 
    $insertStmt = $connection->prepare("
        INSERT INTO favourites (user_id, plane_id) VALUES (?, ?)
    ");
    $insertStmt->bind_param("ii", $userId, $planeId);
 
    if (!$insertStmt->execute()) {
        respond(500, "error", "Failed to add favourite");
    }
 
    respond(200, "success", "Favourite added");
}
 
// REMOVE FAVOURITE
// ============================================================
if ($data["type"] == "RemoveFavourite") {
    if (empty($data["fav_id"])) respond(400, "error", "Missing fav_id");
 
    $favId = (int)$data["fav_id"];
    $userId = $currentUserId;
 
    $checkStmt = $connection->prepare("
        SELECT id FROM favourites WHERE id = ? AND user_id = ?
    ");
    $checkStmt->bind_param("ii", $favId, $userId);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows === 0) {
        respond(404, "error", "Favourite not found");
    }
 
    $delStmt = $connection->prepare("DELETE FROM favourites WHERE id = ?");
    $delStmt->bind_param("i", $favId);
 
    if (!$delStmt->execute()) {
        respond(500, "error", "Failed to remove favourite");
    }
 
    respond(200, "success", "Favourite removed");
}
 
// ============================================================
// HELPER FUNCTIONS
// ============================================================
function haversineDistance($lat1, $lon1, $lat2, $lon2) {
    $R = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $R * $c;
}
 
function calcFlightTime($distance, $speed, $range, $cargo, $seats) {
    if ($speed <= 0) return 0;
    $baseTime = $distance / $speed;
    $speedFactor = max(0.8, 1.2 - ($speed / 1000));
    $rangeFactor = min(1.3, 1 + ($distance / $range) * 0.15);
    return (int)round($baseTime * 60 * $speedFactor * $rangeFactor);
}
 
function getOrCreateFlight($connection, $planeId, $fromCode, $toCode,
                            $depDate, $flightTime, $distance) {
    $checkStmt = $connection->prepare("
        SELECT id FROM flights
        WHERE plane_id = ? AND departure_airport = ?
          AND arrival_airport = ? AND DATE(departure_date) = DATE(?)
        LIMIT 1
    ");
    $checkStmt->bind_param("isss", $planeId, $fromCode, $toCode, $depDate);
    $checkStmt->execute();
    $existing = $checkStmt->get_result();
    if ($existing->num_rows > 0) {
        return $existing->fetch_assoc()["id"];
    }
    
    $insStmt = $connection->prepare("
        INSERT INTO flights
            (plane_id, departure_airport, arrival_airport,
             departure_date, flight_time_mins, distance_km)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $insStmt->bind_param("isssdd", $planeId, $fromCode, $toCode,
                                   $depDate, $flightTime, $distance);
    
    if (!$insStmt->execute()) {
        throw new Exception("Flight insert failed: " . $insStmt->error);
    }
    return $connection->insert_id;
}
 
function checkSeats($connection, $flightId, $requestedPassengers, $planeSeats) {
    $bookedStmt = $connection->prepare(
        "SELECT COALESCE(SUM(passengers), 0) AS total FROM bookings WHERE flight_id = ?"
    );
    $bookedStmt->bind_param("i", $flightId);
    $bookedStmt->execute();
    $booked    = (int)$bookedStmt->get_result()->fetch_assoc()["total"];
    $available = $planeSeats - $booked;
    if ($requestedPassengers > $available) {
        return ["ok" => false, "available" => $available];
    }
    return ["ok" => true, "available" => $available];
}
 
// ============================================================
// BOOK FLIGHT — FIX: NOW STORES ALL BOOKING DETAILS
// FIX: wrapped in a transaction so a failed return booking
//      doesn't leave an orphaned outbound booking in the DB
// ============================================================
if ($data["type"] == "BookFlight") {
    // FIX: Validate all required fields including user details
    foreach (["plane_id","departure_airport","arrival_airport","departure_date",
              "passengers","first_name","last_name","email","cabin_class"] as $f) {
        if (empty($data[$f])) respond(400, "error", "Missing field: $f");
    }
 
    $planeId    = (int)$data["plane_id"];
    $fromCode   = trim($data["departure_airport"]);
    $toCode     = trim($data["arrival_airport"]);
    $depDate    = $data["departure_date"];
    $passengers = (int)$data["passengers"];
    $isReturn   = !empty($data["return_date"]);
    $retDate    = $isReturn ? $data["return_date"] : null;
    $userId     = $currentUserId;
    
    // FIX: Extract user details from request
    $firstName  = trim($data["first_name"]);
    $lastName   = trim($data["last_name"]);
    $email      = trim($data["email"]);
    $cabinClass = trim($data["cabin_class"]);
 
    $planeStmt = $connection->prepare(
        "SELECT id, seats, max_speed_kmh, max_range_km, max_cargo_kg FROM planes WHERE id = ?"
    );
    $planeStmt->bind_param("i", $planeId);
    $planeStmt->execute();
    $planeResult = $planeStmt->get_result();
    if ($planeResult->num_rows === 0) respond(404, "error", "Plane not found");
    $plane = $planeResult->fetch_assoc();
 
    $airportStmt = $connection->prepare(
        "SELECT code, latitude, longitude FROM airports WHERE code = ? OR code = ?"
    );
    $airportStmt->bind_param("ss", $fromCode, $toCode);
    $airportStmt->execute();
    $airportResult = $airportStmt->get_result();
    $airports = [];
    while ($row = $airportResult->fetch_assoc()) {
        $airports[$row["code"]] = $row;
    }
    if (!isset($airports[$fromCode]) || !isset($airports[$toCode])) {
        respond(404, "error", "One or both airports not found");
    }
 
    $distance   = haversineDistance(
        $airports[$fromCode]["latitude"], $airports[$fromCode]["longitude"],
        $airports[$toCode]["latitude"],   $airports[$toCode]["longitude"]
    );
    $flightTime = calcFlightTime($distance, $plane["max_speed_kmh"],
                                 $plane["max_range_km"], $plane["max_cargo_kg"], $plane["seats"]);
 
    if ($distance > $plane["max_range_km"]) {
        respond(400, "error", "Plane range (" . $plane["max_range_km"] .
            " km) is less than distance (" . round($distance, 1) . " km).");
    }
 
    // FIX: begin transaction so both bookings succeed or both roll back
    $connection->begin_transaction();
 
    try {
        $outFlightId  = getOrCreateFlight($connection, $planeId, $fromCode,
                                          $toCode, $depDate, $flightTime, $distance);
        $outSeatCheck = checkSeats($connection, $outFlightId, $passengers, $plane["seats"]);
        if (!$outSeatCheck["ok"]) {
            $connection->rollback();
            respond(400, "error", "Outbound flight full. Only " .
                $outSeatCheck["available"] . " seat(s) left.");
        }
 
        // FIX: INSERT with all user details and cabin class
        $bookStmt = $connection->prepare(
            "INSERT INTO bookings 
             (flight_id, user_id, passengers, cabin_class, first_name, last_name, email, booking_date) 
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $bookStmt->bind_param("iiissss", 
            $outFlightId, $userId, $passengers, 
            $cabinClass, $firstName, $lastName, $email
        );
        
        if (!$bookStmt->execute()) {
            $connection->rollback();
            respond(500, "error", "Failed to insert outbound booking: " . $bookStmt->error);
        }
        $outBookingId = $connection->insert_id;
 
        $returnBookingId = null;
        $retFlightId     = null;
 
        if ($isReturn) {
            $retFlightId  = getOrCreateFlight($connection, $planeId, $toCode,
                                              $fromCode, $retDate, $flightTime, $distance);
            $retSeatCheck = checkSeats($connection, $retFlightId, $passengers, $plane["seats"]);
            if (!$retSeatCheck["ok"]) {
                // FIX: roll back the outbound booking too — no orphaned records
                $connection->rollback();
                respond(400, "error", "Return flight full. Only " .
                    $retSeatCheck["available"] . " seat(s) left.");
            }
            
            // FIX: INSERT return booking with same details
            $bookStmt->bind_param("iiissss", 
                $retFlightId, $userId, $passengers, 
                $cabinClass, $firstName, $lastName, $email
            );
            
            if (!$bookStmt->execute()) {
                $connection->rollback();
                respond(500, "error", "Failed to insert return booking: " . $bookStmt->error);
            }
            $returnBookingId = $connection->insert_id;
        }
 
        $connection->commit();
 
    } catch (Exception $e) {
        $connection->rollback();
        respond(500, "error", "Booking failed: " . $e->getMessage());
    }
 
    respond(200, "success", [
        "outbound" => [
            "booking_id"  => $outBookingId,
            "flight_id"   => $outFlightId,
            "from"        => $fromCode,
            "to"          => $toCode,
            "departure"   => $depDate,
            "distance_km" => round($distance, 1),
            "flight_mins" => $flightTime,
            "passengers"  => $passengers
        ],
        "return" => $isReturn ? [
            "booking_id"  => $returnBookingId,
            "flight_id"   => $retFlightId,
            "from"        => $toCode,
            "to"          => $fromCode,
            "departure"   => $retDate,
            "distance_km" => round($distance, 1),
            "flight_mins" => $flightTime,
            "passengers"  => $passengers
        ] : null
    ]);
}
 
// ============================================================
// GET BOOKINGS
// ============================================================
if ($data["type"] == "GetBookings") {
    $userId  = $currentUserId;
 
    $stmt = $connection->prepare("
        SELECT b.id AS booking_id, b.passengers, b.cabin_class, 
               b.first_name, b.last_name, b.email, b.booking_date,
               f.id AS flight_id, f.departure_airport, f.arrival_airport,
               f.departure_date, f.flight_time_mins, f.distance_km,
               p.manufacturer, p.model, p.seats
        FROM bookings b
        INNER JOIN flights f ON b.flight_id = f.id
        INNER JOIN planes  p ON f.plane_id  = p.id
        WHERE b.user_id = ?
        ORDER BY f.departure_date ASC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result   = $stmt->get_result();
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
 
    respond(200, "success", $bookings);
}
 
// ============================================================
// REMOVE BOOKING
// ============================================================
if ($data["type"] == "RemoveBooking") {
    if (empty($data["booking_id"])) respond(400, "error", "Missing booking_id");
 
    $bookingId = (int)$data["booking_id"];
    $userId    = $currentUserId;
 
    $check = $connection->prepare(
        "SELECT id FROM bookings WHERE id = ? AND user_id = ?"
    );
    $check->bind_param("ii", $bookingId, $userId);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        respond(404, "error", "Booking not found or does not belong to you");
    }
 
    $del = $connection->prepare("DELETE FROM bookings WHERE id = ?");
    $del->bind_param("i", $bookingId);
    $del->execute();
 
    respond(200, "success", "Booking cancelled successfully");
}
 
respond(400, "error", "Invalid request type");
?>
 
 