<?php


session_start();
include "db.php";
// Ask Google Gemini which category a photo belongs to.
// Returns the category name, or null if the AI is unavailable or unsure.
function detect_category_with_ai($path) {
    if (!function_exists('curl_init')) {
        $GLOBALS['ai_error'] = "PHP curl is not enabled on this server, so the AI cannot be reached.";
        return null;
    }
    if (!file_exists(__DIR__ . '/ai_config.php')) {
        $GLOBALS['ai_error'] = "The AI is not set up yet. Please choose the category yourself.";
        return null;
    }
    require_once __DIR__ . '/ai_config.php';

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($path);
    $raw = file_get_contents($path);
    $data = base64_encode($raw);

    // Shrink big photos before sending (faster and more reliable)
    if (function_exists('imagecreatefromstring')) {
        $img = @imagecreatefromstring($raw);
        if ($img) {
            $w = imagesx($img);
            $h = imagesy($img);
            $max = 800;
            if ($w > $max || $h > $max) {
                $scale = $max / max($w, $h);
                $nw = (int)($w * $scale);
                $nh = (int)($h * $scale);
                $small = imagecreatetruecolor($nw, $nh);
                imagecopyresampled($small, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
                ob_start();
                imagejpeg($small, null, 80);
                $jpg = ob_get_clean();
                imagedestroy($small);
                $data = base64_encode($jpg);
                $mime = 'image/jpeg';
            }
            imagedestroy($img);
        }
    }

    $prompt = 'This photo was sent to a city complaint app. Reply with exactly ONE word '
            . 'from this list and nothing else: road_damage, garbage, streetlight, water_leak, unsure. '
            . 'Use unsure if the photo does not clearly show one of these problems.';

    $body = json_encode(['contents' => [['parts' => [
        ['text' => $prompt],
        ['inline_data' => ['mime_type' => $mime, 'data' => $data]],
    ]]]]);

    // Models to try, in order
    $models = [GEMINI_MODEL];
    if (defined('GEMINI_MODEL_FALLBACK') && GEMINI_MODEL_FALLBACK !== GEMINI_MODEL) {
        $models[] = GEMINI_MODEL_FALLBACK;
    }

    $res = false;
    $http = 0;
    $success = false;

    foreach ($models as $model) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent';

        for ($try = 1; $try <= 2; $try++) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-goog-api-key: ' . GEMINI_API_KEY],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
            ]);
            $res = curl_exec($ch);
            $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http === 200) { $success = true; break 2; }

            // Busy or temporary: try once more on the same model
            if ($res === false || in_array($http, [500, 503, 504], true)) {
                if ($try < 2) { sleep(1); }
                continue;
            }
            // Other errors (limit, wrong model): go to the next model
            break;
        }
    }

    if (!$success) {
        if ($http === 429) {
            $GLOBALS['ai_error'] = "The AI is busy right now. Please try again in a minute or choose the category yourself.";
        } elseif ($res === false || in_array($http, [500, 503, 504], true)) {
            $GLOBALS['ai_error'] = "Google's AI is very busy right now. Please try again in a minute, or choose the category yourself.";
        } else {
            $GLOBALS['ai_error'] = "The AI is unavailable (code " . $http . "). Please choose the category yourself.";
        }
        return null;
    }

    $json = json_decode($res, true);
    $text = strtolower(trim($json['candidates'][0]['content']['parts'][0]['text'] ?? ''));

    $map = [
        'road_damage' => 'Road Damage',
        'garbage'     => 'Garbage',
        'streetlight' => 'Streetlights',
        'water_leak'  => 'Water Leakage',
    ];
    if (!isset($map[$text])) {
        $GLOBALS['ai_error'] = "The AI is not sure what this photo shows. Please choose the category yourself.";
        return null;
    }
    return $map[$text];
}
// Check user login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Create CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";
$message_type = "";

$category = "";
$description = "";
$location = "";

// Success message
if (isset($_GET['success'])) {
    $message = "Your complaint has been submitted successfully!";
    $detected_ok = ["Road Damage", "Garbage", "Streetlights", "Water Leakage"];
    if (isset($_GET['detected']) && in_array($_GET['detected'], $detected_ok, true)) {
        $message .= " AI detected the category: " . $_GET['detected'] . ".";
    }
    $message_type = "success";
}

if (isset($_POST['submit_report'])) {

    // CSRF verification
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $message = "Invalid request. Please try again.";
        $message_type = "error";
    } else {

        $category = trim($_POST['category'] ?? "");
        $description = trim($_POST['description'] ?? "");
        $location = trim($_POST['location'] ?? "");
        $wants_auto = ($category === "auto");
                $lat = (isset($_POST['latitude']) && $_POST['latitude'] !== '') ? (float)$_POST['latitude'] : null;
        $lng = (isset($_POST['longitude']) && $_POST['longitude'] !== '') ? (float)$_POST['longitude'] : null;
        if ($lat === null || $lng === null || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            $lat = null;
            $lng = null;
        }
        $auto_category = 0;

        $allowed_categories = [
            "Road Damage",
            "Garbage",
            "Streetlights",
            "Water Leakage"
        ];

        $image_name = null;
        $upload_success = true;

        // Validate fields
        if (
            (!$wants_auto && !in_array($category, $allowed_categories, true)) ||
            empty($description) ||
            empty($location)
        ) {
            $message = "Please fill in all required fields correctly.";
            $message_type = "error";
            $upload_success = false;
        }

        // AI mode needs a photo
        if (
            $upload_success && $wants_auto &&
            (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE)
        ) {
            $message = "Please upload a photo so the AI can detect the category.";
            $message_type = "error";
            $upload_success = false;
        }
        // Image upload
        if (
            $upload_success &&
            isset($_FILES['image']) &&
            $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $message = "Image upload failed.";
                $message_type = "error";
                $upload_success = false;
            } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                $message = "Image size must be less than 2 MB.";
                $message_type = "error";
                $upload_success = false;
            } else {

                $tmp_name = $_FILES['image']['tmp_name'];

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime_type = $finfo->file($tmp_name);

                $allowed_types = [
                    "image/jpeg" => "jpg",
                    "image/png" => "png",
                    "image/webp" => "webp"
                ];

                if (
                    !isset($allowed_types[$mime_type]) ||
                    getimagesize($tmp_name) === false
                ) {
                    $message = "Only JPG, PNG and WEBP images are allowed.";
                    $message_type = "error";
                    $upload_success = false;
                } else {

                    $upload_dir = "uploads/";

                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $image_name = bin2hex(random_bytes(16)) .
                                  "." . $allowed_types[$mime_type];

                    if (!move_uploaded_file(
                        $tmp_name,
                        $upload_dir . $image_name
                    )) {
                        $message = "Unable to upload image.";
                        $message_type = "error";
                        $upload_success = false;
                    }
                }
            }
        }

        // AI category detection
        if ($upload_success && $wants_auto) {
            $ai_category = detect_category_with_ai("uploads/" . $image_name);
            if ($ai_category === null) {
                if ($image_name && file_exists("uploads/" . $image_name)) {
                    unlink("uploads/" . $image_name);
                }
                $message = $GLOBALS['ai_error'] ?? "The AI could not decide the category. Please choose it yourself.";
                $message_type = "error";
                $category = "";
                $upload_success = false;
            } else {
                $category = $ai_category;
                $auto_category = 1;
            }
        }
        // Save complaint
        if ($upload_success) {

            $user_id = $_SESSION['user_id'];

            $sql = "INSERT INTO complaints
                    (user_id, category, description, location, image, latitude, longitude)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($conn, $sql);

            if ($stmt) {

                mysqli_stmt_bind_param(
                    $stmt,
                    "issssdd",
                    $user_id,
                    $category,
                    $description,
                    $location,
                    $image_name,
                    $lat,
                    $lng
                );

                if (mysqli_stmt_execute($stmt)) {

                    // Clear form data after success
                    if ($auto_category === 1) {
                        $new_id = (int)mysqli_insert_id($conn);
                        mysqli_query($conn, "UPDATE complaints SET auto_category = 1 WHERE id = " . $new_id);
                    }
                    unset($_SESSION['csrf_token']);

                                        $go = "my_complaints.php?success=1";
                                       $go = "my_complaints.php?success=1";
                    if ($auto_category === 1) {
                        $go .= "&detected=" . urlencode($category);
                    }
                    if (!headers_sent()) {
                        header("Location: " . $go);
                    } else {
                        echo '<script>window.location.href=' . json_encode($go) . ';</script>';
                        echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($go) . '"></noscript>';
                    }
                    exit();

                } else {

                    // Remove uploaded image if database insert fails
                    if ($image_name && file_exists("uploads/" . $image_name)) {
                        unlink("uploads/" . $image_name);
                    }

                    $message = "Unable to submit complaint. Please try again.";
                    $message_type = "error";
                }

                mysqli_stmt_close($stmt);

            } else {
                $message = "Database error. Please try again.";
                $message_type = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Report Complaint - CityFix</title>

    <link rel="stylesheet" href="css/style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">

    <style>
        .report-container {
            max-width: 650px;
            margin: 40px auto;
            padding: 25px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        }

        .report-container h2 {
            text-align: center;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 15px;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .submit-btn {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 6px;
            background: #1769aa;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        .submit-btn:hover {
            background: #0d4f85;
        }

        .message {
            padding: 12px;
            margin-bottom: 18px;
            border-radius: 6px;
        }

        .success {
            background: #d4edda;
            color: #155724;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }

        #imagePreview {
            max-width: 100%;
            max-height: 200px;
            margin-top: 10px;
            display: none;
            border-radius: 8px;
        }
                #map { height: 300px; border-radius: 8px; margin-top: 10px; border: 1px solid #ccc; }
        .map-btn { background:#0f766e; color:white; border:none; padding:10px 16px; border-radius:6px; cursor:pointer; font-size:14px; }
        #mapStatus { display:block; margin-top:6px; color:#475569; }
    </style>
</head>

<body>

<div class="report-container">

    <h2>Report a Complaint</h2>

    <?php if (!empty($message)): ?>
        <div class="message <?php echo htmlspecialchars($message_type); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
        >

        <div class="form-group">
            <label for="category">Complaint Category</label>

            <select name="category" id="category" required>
                <option value="">Select Category</option>

                <option value="auto"
                    <?php if ($category == "auto") echo "selected"; ?>>
                    Not sure? Let AI detect it from my photo
                </option>

                <option value="Road Damage"
                    <?php if ($category == "Road Damage") echo "selected"; ?>>
                    Road Damage
                </option>

                <option value="Garbage"
                    <?php if ($category == "Garbage") echo "selected"; ?>>
                    Garbage
                </option>

                <option value="Streetlights"
                    <?php if ($category == "Streetlights") echo "selected"; ?>>
                    Streetlights
                </option>

                <option value="Water Leakage"
                    <?php if ($category == "Water Leakage") echo "selected"; ?>>
                    Water Leakage
                </option>
            </select>
        </div>

        <div class="form-group">
            <label for="description">Complaint Description</label>

            <textarea
                name="description"
                id="description"
                placeholder="Describe your problem..."
                required
            ><?php echo htmlspecialchars($description); ?></textarea>
        </div>

        <div class="form-group">
            <label for="location">Location</label>

            <input
                type="text"
                name="location"
                id="location"
                placeholder="Enter your area, street or landmark"
                value="<?php echo htmlspecialchars($location); ?>"
                required
            >
        </div>
        
                <div class="form-group">
            <label>Pin the exact spot (optional)</label>
            <button type="button" class="map-btn" id="gpsBtn">&#128205; Use my current location</button>
            <small id="mapStatus">Or tap the map / drag the pin to the problem spot.</small>
            <div id="map"></div>
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">
            <small>Your pinned location is saved with this complaint and shown to the city admin. Your device asks permission before sharing it.</small>
        </div>

        <div class="form-group">
            <label for="image">Upload Image (Optional)</label>

            <input
                type="file"
                name="image"
                id="image"
                accept=".jpg,.jpeg,.png,.webp"
            >

            <small>Allowed: JPG, PNG, WEBP. Maximum size: 2 MB.</small>

            <img id="imagePreview" alt="Image preview">
        </div>

        <button type="submit" name="submit_report" class="submit-btn">
            Submit Complaint
        </button>

    </form>

</div>

<script>
document.getElementById("image").addEventListener("change", function () {
    const preview = document.getElementById("imagePreview");
    const file = this.files[0];

    if (file) {
        preview.src = URL.createObjectURL(file);
        preview.style.display = "block";
    } else {
        preview.style.display = "none";
    }
});
    document.querySelector("form").addEventListener("submit", function () {
    var btn = this.querySelector(".submit-btn");
    setTimeout(function () {
        btn.disabled = true;
        btn.textContent = "Submitting... please wait";
    }, 0);
});
</script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
(function () {
    var map = L.map('map').setView([20.5937, 78.9629], 5);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    var marker = null;
    var latInput = document.getElementById('latitude');
    var lngInput = document.getElementById('longitude');
    var locInput = document.getElementById('location');
    var status = document.getElementById('mapStatus');
    var autoFilled = false;

    locInput.addEventListener('input', function () { autoFilled = false; });

    function fillAddress(lat, lng) {
        var coords = lat.toFixed(5) + ', ' + lng.toFixed(5);
        if (locInput.value.trim() === '' || autoFilled) {
            locInput.value = coords;
            autoFilled = true;
        }
        fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + lat + '&lon=' + lng)
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d && d.display_name && autoFilled) {
                    locInput.value = d.display_name.substring(0, 200);
                }
            })
            .catch(function () {});
    }

    function setPoint(lat, lng, zoom) {
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.on('dragend', function () {
                var p = marker.getLatLng();
                setPoint(p.lat, p.lng, null);
            });
        }
        latInput.value = lat.toFixed(7);
        lngInput.value = lng.toFixed(7);
        if (zoom) { map.setView([lat, lng], zoom); }
        status.textContent = 'Pinned: ' + lat.toFixed(5) + ', ' + lng.toFixed(5);
        fillAddress(lat, lng);
    }

    map.on('click', function (e) { setPoint(e.latlng.lat, e.latlng.lng, null); });

    document.getElementById('gpsBtn').addEventListener('click', function () {
        if (!navigator.geolocation) {
            status.textContent = 'Your browser cannot detect location. Please tap the map instead.';
            return;
        }
        status.textContent = 'Finding your location...';
        navigator.geolocation.getCurrentPosition(function (pos) {
            setPoint(pos.coords.latitude, pos.coords.longitude, 17);
        }, function () {
            status.textContent = 'Could not get your location. Allow location access, or tap the map instead.';
        }, { enableHighAccuracy: true, timeout: 15000 });
    });
})();
</script>
</body>
</html>