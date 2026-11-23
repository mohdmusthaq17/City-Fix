<?php
session_start();
include "db.php";

// Ask the local AI service (ai_service/app.py) which category a photo belongs to.
// Returns the category name, or null if the service is off or unsure.
function detect_category_with_ai($path) {
    if (!function_exists('curl_init')) {
        $GLOBALS['ai_error'] = "PHP curl is not enabled in XAMPP, so the AI service cannot be reached.";
        return null;
    }
    $ch = curl_init("http://127.0.0.1:5001/predict");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['image' => new CURLFile($path)],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 25,
    ]);
    $res = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($res === false) {
        $GLOBALS['ai_error'] = "The AI service is not running (or took too long). Start it with 'python app.py' in the ai_service folder, then try again.";
        return null;
    }
    if ($http === 422) {
        $GLOBALS['ai_error'] = "The AI is not sure what this photo shows. Please choose the category yourself.";
        return null;
    }
    $data = json_decode($res, true);
    $allowed = ["Road Damage", "Garbage", "Streetlights", "Water Leakage"];
    if (isset($data['category']) && in_array($data['category'], $allowed, true)) {
        return $data['category'];
    }
    return null;
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
                    (user_id, category, description, location, image)
                    VALUES (?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($conn, $sql);

            if ($stmt) {

                mysqli_stmt_bind_param(
                    $stmt,
                    "issss",
                    $user_id,
                    $category,
                    $description,
                    $location,
                    $image_name
                );

                if (mysqli_stmt_execute($stmt)) {

                    // Clear form data after success
                    if ($auto_category === 1) {
                        $new_id = (int)mysqli_insert_id($conn);
                        mysqli_query($conn, "UPDATE complaints SET auto_category = 1 WHERE id = " . $new_id);
                    }
                    unset($_SESSION['csrf_token']);

                    if ($auto_category === 1) {
                        header("Location: report.php?success=1&detected=" . urlencode($category));
                    } else {
                        header("Location: report.php?success=1");
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
</script>

</body>
</html>