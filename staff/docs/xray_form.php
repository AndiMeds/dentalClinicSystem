<?php
include '../../php/upload_xray.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <link rel="stylesheet" href="/css/xray.css">
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Upload X-ray - Dental Clinic Staff Portal</title>
</head>
<body>
    <div class="container">
        <h1>Upload X-ray for <?php echo htmlspecialchars($patient['name'] ?? 'Unknown Patient'); ?></h1>

        <?php echo $upload_message; ?>

        <form id="uploadForm" action="" method="post"
            enctype="multipart/form-data">
            <div class="form-group">
            <input type="hidden" name="patient_id" value="<?php echo htmlspecialchars($patient_id); ?>">
                <label for="xray_name">X-ray Name:</label>
                <input type="text" id="xray_name" name="xray_name" required>
            </div>

            <div class="form-group">
                <label for="xray_date">X-ray Date:</label>
                <input type="date" id="xray_date" name="xray_date" required>
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" required></textarea>
            </div>

            <div class="form-group">
                <label for="xray_file">X-ray Image (JPG, PNG, or GIF, max 5MB):</label>
                <input type="file" id="xray_file" name="xray_file" accept="image/*" required
                    onchange="previewImage(event)">
            </div>
            <div class="form-group">
                <img id="image_preview" src="" alt="X-ray Image Preview"
                    style="display:none; max-width: 100%; height: auto;" />
            </div>

            <div class="form-group">
                <button type="submit" id="submitButton" class="btn btn-primary">Upload X-ray</button>
                <span id="loadingIndicator" class="loading">Uploading...</span>
            </div>
        </form>
        <a href="/staff/patient_details.php?patient_id=<?php echo $patient_id; ?>" class="btn btn-back">Back to Patient Records</a>
    </div>

    <script src="/js/xray.js"></script>
</body>
</html>

<?php
if (isset($conn)) {
    $conn->close();
}
?>