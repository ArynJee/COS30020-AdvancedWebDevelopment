<?php
session_start();
include 'services/gemini_service.php';
include 'services/pdf_utils.php'; 

if (!isset($_SESSION['user']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "RootFlower";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// gemini API key
define('GEMINI_API_KEY', 'AIzaSyBXyXdv-i8oeL4ZowmsgDCTHu3lu0YYUW0');

$alert = $_SESSION['alert'] ?? '';
$alertType = $_SESSION['alertType'] ?? '';
unset($_SESSION['alert'], $_SESSION['alertType']);

$result = null;
$error = null;
$imagePreview = null;
$source = null;
$uploadedImage = null;

// load previous results from session if they exist
if (isset($_SESSION['current_result'])) {
    $result = $_SESSION['current_result'];
    $source = $_SESSION['current_source'] ?? 'AI';
    $imagePreview = $_SESSION['current_image'] ?? null;
}

// handle PDF download
if (isset($_GET['download'])) {
    // check for result from multiple sources
    $download_result = null;
    $download_source = 'AI';
    
    if (isset($_SESSION['current_result'])) {
        // use session result
        $download_result = $_SESSION['current_result'];
        $download_source = $_SESSION['current_source'] ?? 'AI';
    } elseif (isset($result)) {
        // use current page result
        $download_result = $result;
        $download_source = $source ?? 'AI';
    }
    
    if ($download_result){
        if ($download_source === 'database' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $sql = "SELECT description FROM flower_table WHERE id = $id";
            $db_result = $conn->query($sql);
            
            if ($db_result && $row = $db_result->fetch_assoc() && !empty($row['description'])) {
                $pdf_path = $row['description'];
                
                if (file_exists($pdf_path) && strpos($pdf_path, '.pdf') !== false) {
                    // send original PDF file
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment; filename="flower_report_' . $id . '.pdf"');
                    header('Content-Length: ' . filesize($pdf_path));
                    readfile($pdf_path);
                    exit;
                }
            }
        }
        
        // For AI results or when original PDF not found, generate PDF
        $pdf_content = PDFUtils::createFlowerPDF($download_result, $download_source);
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="flower_report_' . time() . '.pdf"');
        header('Content-Length: ' . strlen($pdf_content));
        echo $pdf_content;
        exit;
    } else {
        // No result data - redirect back
        header('Location: identify.php');
        exit;
    }
}

// check if we need to clear previous upload
if (isset($_GET['clear'])) {
    unset($_SESSION['uploaded_image']);
    unset($_SESSION['current_result']);
    unset($_SESSION['current_source']);
    unset($_SESSION['current_image']);
    header('Location: identify.php');
    exit;
}

// handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['flower_image']) && !isset($_POST['identify'])) {
    $upload_dir = 'images/identify_uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $file = $_FILES['flower_image'];
    
    if ($file['error'] !== UPLOAD_ERR_OK){
        $error = "Upload failed. Error code: " . $file['error'];
    } elseif (!in_array($file['type'], $allowed_types)){
        $error = "Only JPG, PNG, and GIF images are allowed.";
    } elseif ($file['size'] > 5 * 1024 * 1024){ 
        $error = "File size must be less than 5MB.";
    } else{
        $filename = uniqid() . '_' . basename($file['name']);
        $filepath = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)){
            $_SESSION['uploaded_image'] = $filepath;
            $_SESSION['uploaded_filename'] = $filename;
            header('Location: identify.php');
            exit;
        }else{
            $error_msg = "Failed to save uploaded file.";
            $_SESSION['alert'] = $error_msg;
            $_SESSION['alertType'] = 'danger';
        }
    }
}

// handle flower identification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['identify']) && isset($_SESSION['uploaded_image'])){
    $filepath = $_SESSION['uploaded_image'];
    $imagePreview = $filepath;

    try {
        // step 1: call gemini API
        $gemini = new GeminiService(GEMINI_API_KEY);
        $ai_result = $gemini->identifyFlower($filepath);
        
        if (!$ai_result || !isset($ai_result['scientific_name'])) {
            $error_msg = "Failed to identify. Flower data not available.";
            $_SESSION['alert'] = $error_msg;
            $_SESSION['alertType'] = 'danger';
            header('Location: identify.php');
        } else {
            // step 2: cross-reference with database
            $scientific_name = $conn->real_escape_string($ai_result['scientific_name']);
            $common_name = $conn->real_escape_string($ai_result['common_name']);
            
            $sql = "SELECT * FROM flower_table WHERE Scientific_Name LIKE '%$scientific_name%' OR Common_Name LIKE '%$common_name%' OR Scientific_Name LIKE '%" . $conn->real_escape_string($ai_result['common_name']) . "%' OR Common_Name LIKE '%" . $conn->real_escape_string($ai_result['scientific_name']) . "%' LIMIT 1";
            
            $db_result = $conn->query($sql);
            
            if ($db_result && $db_result->num_rows > 0) {
                // found in database
                $result = $db_result->fetch_assoc();
                $source = 'database';

                $result['description'] = PDFUtils::getFlowerDescription($result);
                $_SESSION['alert'] = "Flower identified from database!";
                $_SESSION['alertType'] = 'success';
                header('Location: identify.php');
            } else {
                // if not, use AI result
                $result = [
                    'Scientific_Name' => $ai_result['scientific_name'],
                    'Common_Name' => $ai_result['common_name'],
                    'description' => $ai_result['description'],
                    'plants_image' => $imagePreview
                ];
                $source = 'AI';
                $_SESSION['alert'] = "Flower identified using AI analysis!";
                $_SESSION['alertType'] = 'success';
                header('Location: identify.php');
            }
            $_SESSION['current_result'] = $result;
            $_SESSION['current_source'] = $source;
            $_SESSION['current_image'] = $imagePreview;
        }
    } catch (Exception $e) {
        $error = "Identification failed: " . $e->getMessage();
    }
}

// check if we have a previously uploaded image in session
if (isset($_SESSION['uploaded_image']) && file_exists($_SESSION['uploaded_image'])) {
    $uploadedImage = $_SESSION['uploaded_image'];
    $imagePreview = $uploadedImage;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="author" content="Aryn Mei Wei JEE"/>
    <meta name="description" content="RootFlower FLower Identification Page"/>
    <meta name="keywords" content="Root Flower, floral class, Kuching-based"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/styles.css"/>

    <!--bootstrap framework CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">

    <title>RootFlower Flower Identification Page</title>
</head>

<body>
<header>
    <?php include 'include/header.php'; ?>
</header>

<?php if (!empty($alert)): ?>
    <div class="alert alert-<?= htmlspecialchars($alertType) ?> fade show alert-dismissable translate-middle start-50 position-fixed mt-5 w-75 text-center" role="alert">
        <?= htmlspecialchars($alert) ?>
    </div>
<?php endif; ?>

<article>
    <div class="product-hero position-relative mb-5">
        <div class="product-hero-img position-relative object-fit-cover">
            <img src="images/carousel-2.jpg" alt="Product Image" class="w-100">
            <div class="gradient-overlay"></div>
        </div>

        <div class="product-caption position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center text-center">
            <h1 class="display-3 fw-bolder text-capitalize mb-4">Flower Identifier</h1>
            <p class="fs-5 mb-5">Upload an image of a flower to identify its species. We'll cross-reference it with our database or use AI to provide detailed flower information.</p>
        </div>
    </div>
    <div class="container py-5 mb-5">

        <div class="row g-5">
        <!-- upload section -->
        <div class="col-lg-6">
            <div class="card shadow h-100">
                <div class="card-body identify-card p-4 px-5 rounded">
                    <h2 class="h4 fw-bold mb-4 text-center">
                        <i class="bi bi-cloud-arrow-up me-2"></i>
                        Upload Image
                    </h2>
                    
                    <!-- upload image for preview -->
                    <form method="POST" enctype="multipart/form-data" id="uploadForm" class="text-center">
                        <div class="upload-container mb-4">
                            <div class="upload-area mx-auto my-0 d-flex flex-column justify-content-center align-items-center text-center" id="uploadZone">
                                <?php if ($imagePreview): ?>
                                    <img src="<?php echo htmlspecialchars($imagePreview); ?>" 
                                        class="upload-preview rounded mb-3"
                                        alt="Uploaded flower">
                                    <p class="text-success mb-1 fw-semibold">
                                        <i class="bi bi-check-circle me-1"></i>
                                        Image uploaded successfully!
                                    </p>
                                <?php else: ?>
                                    <div class="d-flex flex-column justify-content-center align-items-center h-100">
                                        <i class="bi bi-cloud-arrow-up fs-1"></i>
                                        <p class="mb-1 fw-medium">Click to upload flower image</p>
                                        <p class="text-muted small">JPG, PNG (Max 5MB)</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <input type="file" id="fileInput" name="flower_image" class="form-control visually-hidden" accept="image/*">
                        </div>
                    </form>
                    
                    <!-- identify flower form -->
                    <?php if ($imagePreview && !$result): ?>
                        <form method="POST" class="text-center">
                            <input type="hidden" name="identify" value="1">
                            <button type="submit" 
                                    class="btn identify-button w-100 py-2 mb-2">
                                <i class="bi bi-search me-2"></i>
                                Identify Flower
                            </button>
                            <a href="identify.php?clear=1" class="btn identify-button w-100 py-2">
                                <i class="bi bi-arrow-clockwise me-2"></i>
                                Reset
                            </a>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($result): ?>
                        <div class="text-center">
                            <a href="identify.php?clear=1" class="btn identify-button w-100 py-2">
                                <i class="bi bi-arrow-clockwise me-2"></i>
                                Identify Another Flower
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

            <!-- Results Section -->
            <div class="col-lg-6">
                <?php if ($result): ?>
                    <div class="card border-secondary shadow h-100 overflow-hidden">
                        <div class="card-header result-card-header p-3 d-flex justify-content-between align-items-center">
                            <span class="fw-bold d-flex align-items-center">
                                <i class="bi bi-check-circle me-2"></i>
                                Identification Successful
                            </span>
                            <span class="badge rounded-pill bg-<?php echo $source === 'database' ? 'success' : 'info'; ?>">
                                <?php echo $source === 'database' ? 'From Database' : 'AI Identified'; ?>
                            </span>
                        </div>
                        
                        <div class="card-body result-card-body p-4">
                            <div class="mb-4">
                                <small class="text-uppercase text-muted fw-bold">Common Name</small>
                                <h3 class="display-6 fw-bold">
                                    <?php echo htmlspecialchars($result['Common_Name']); ?>
                                </h3>
                            </div>

                            <div class="mb-4">
                                <small class="text-uppercase text-muted fw-bold">Scientific Name</small>
                                <h4 class="fst-italic text-primary-emphasis">
                                    <?php echo htmlspecialchars($result['Scientific_Name']); ?>
                                </h4>
                            </div>

                            <div class="mb-4">
                                <small class="text-uppercase text-muted fw-bold d-block mb-2">Description</small>
                                <div class="p-3 bg-light rounded">
                                    <p class="mb-0">
                                        <?php echo nl2br(htmlspecialchars($result['description'] ?? 'No description available')); ?>
                                    </p>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <?php if ($source === 'database' && isset($result['id'])): ?>
                                    <a href="identify.php?download=1&id=<?php echo $result['id']; ?>" 
                                        class="btn btn-outline-dark py-2 fw-bold">
                                        <i class="bi bi-download me-2"></i>
                                        Download PDF Report
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-outline-dark py-2 fw-bold" onclick="generatePDF()">
                                        <i class="bi bi-download me-2"></i>
                                        Generate PDF Report
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card no-result-card shadow h-100">
                        <div class="card-body rounded identify-card p-4 d-flex flex-column justify-content-center align-items-center text-center">
                            <i class="bi bi-flower1 fs-1 mb-2"></i>
                            <h4 class="mb-2">No Results Yet</h4>
                            <p>Upload a flower image and click "Identify Flower" to see results here.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</article>


<!-- Footer -->
<?php
include 'include/footer.php'; 
$conn->close();
?>

<!-- bootstrap framework Javascript-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script> 
<script>
    function generatePDF() {
        // For AI results, submit a form to generate PDF
        if (<?php echo ($source ?? '') === 'AI' ? 'true' : 'false'; ?>) {
            // Create a hidden form to submit
            const form = document.createElement('form');
            form.method = 'GET';
            form.action = 'identify.php';
            
            const downloadInput = document.createElement('input');
            downloadInput.type = 'hidden';
            downloadInput.name = 'download';
            downloadInput.value = '1';
            
            form.appendChild(downloadInput);
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const uploadZone = document.getElementById('uploadZone');
        const fileInput = document.getElementById('fileInput');
        const uploadForm = document.getElementById('uploadForm');
        
        if (uploadZone) {
            uploadZone.addEventListener('click', function(e) {
                e.preventDefault();
                fileInput.click();
            });
        }
        
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    uploadForm.submit();
                }
            });
        }
    });
</script>
<script src="js/main.js"></script>
</body>
</html>