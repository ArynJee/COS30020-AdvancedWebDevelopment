<?php
session_start();
include 'include/function.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// clear session if not from form submissions
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['form_error'])) {
    unset($_SESSION['']);
}

$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "RootFlower";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// pre fill data
$edit_work = null;
$edit_id = null;

if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
} elseif (isset($_GET['form_error']) && $_GET['form_error'] === 'edit' && isset($_SESSION['edit_id'])) {
    $edit_id = $_SESSION['edit_id'];
    unset($_SESSION['edit_id']);
}

// form validation
if ($edit_id) {
    $edit_sql = "SELECT * FROM studentwork_table WHERE id = '$edit_id'";
    $edit_result = $conn->query($edit_sql);
    if ($edit_result->num_rows > 0) {
        $edit_work = $edit_result->fetch_assoc();
    }
}

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $delete_work = ['id' => $delete_id];
}

// handle form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // edit student work
    if (isset($_POST['edit_work'])){
        $work_id = $_POST['work_id'];
        $description = trim($_POST['description']);

        $errors = [];
        
        // validation
        if (empty($description)){
            $errors["description"] = "Description is required";
        }

        if(empty($errors)){
            $current_sql = "SELECT email, workshop_title FROM studentwork_table WHERE id = '$work_id'";
            $current_result = $conn->query($current_sql);
            
            if ($current_result && $current_result->num_rows > 0) {
                $current_data = $current_result->fetch_assoc();
                
                $update_sql = "UPDATE studentwork_table SET description = '$description' WHERE id = '$work_id'";
                
                if ($conn->query($update_sql)){
                    // include_once 'include/function.php';
                    createNotification(
                        $current_data['email'],
                        "Student Work Updated", 
                        "Your student work description for \"{$current_data['workshop_title']}\" has been updated",
                        'student_work',
                        'updated'
                    );

                    $_SESSION['alert'] = "Student work updated successfully!";
                    $_SESSION['alertType'] = "success";
                } else {
                    $_SESSION['alert'] = "Error updating student work: " . $conn->error;
                    $_SESSION['alertType'] = "danger";
                }
            } else {
                $_SESSION['alert'] = "Error: Student work data not found.";
                $_SESSION['alertType'] = "danger";
            }
        }
    }

    // delete student work
    if(isset($_POST['delete_work'])){
        $work_id = $_POST['work_id'];
        $user_sql = "SELECT email, workshop_title FROM studentwork_table WHERE id = '$work_id'";
        $user_result = $conn->query($user_sql);
    
    if ($user_result && $user_result->num_rows > 0) {
        $user_data = $user_result->fetch_assoc();
        $delete_sql = "DELETE FROM studentwork_table WHERE id = '$work_id'";

        if ($conn->query($delete_sql)){
            createNotification(
                $user_data['email'],
                "Student Work Deleted",
                "Your student work for \"{$user_data['workshop_title']}\" has been deleted by the admin.",
                'student_work',
                'deleted'
            );

            $_SESSION['alert'] = "Student work deleted successfully!";
            $_SESSION['alertType'] = "success";
        } else{
            $_SESSION['alert'] = "Error deleting student work: " . $conn->error;
            $_SESSION['alertType'] = "danger";
        }
    }else{
        $_SESSION['alert'] = "Error: Student work data not found.";
        $_SESSION['alertType'] = "danger"; 
    }
        header("Location: manage_studentwork.php");
        exit();
    }

    // update status 
    if(isset($_POST['update_status_btn'])){
        $id = $_POST['id'] ?? null;
        $status = $_POST['status'] ?? null;

        $user_sql = "SELECT email, workshop_title FROM studentwork_table WHERE id = $id";
        $user_result = $conn->query($user_sql);
        
        if ($user_result && $user_result->num_rows > 0) {
            $user_data = $user_result->fetch_assoc();
            
            $sql = "UPDATE studentwork_table SET status = '$status' WHERE id = $id";

            if ($conn->query($sql)){
                // include_once 'include/function.php';
                createNotification(
                    $user_data['email'],
                    "Student Work " . ucfirst($status),
                    "Your student work for \"{$user_data['workshop_title']}\" has been $status",
                    'student_work',
                    $status
                );

                $_SESSION['alert'] = "Status updated successfully!";
                $_SESSION['alertType'] = "success";
            } else{
                $_SESSION['alert'] = "Error updating status.";
                $_SESSION['alertType'] = "danger";
            }
        } else {
            $_SESSION['alert'] = "Error: Student work data not found.";
            $_SESSION['alertType'] = "danger";
        }
        
        header("Location: manage_studentwork.php");
        exit();
    }
}

$records_current_page = 5;
$page = isset($_GET['page']) ? (int)$_GET['page']: 1;
$offset = ($page - 1) * $records_current_page;

// total records in database
$count_sql = "SELECT COUNT(*) as total FROM studentwork_table";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_current_page);

$sql = "SELECT * FROM studentwork_table ORDER BY upload_date DESC LIMIT $offset, $records_current_page";
$result = $conn->query($sql);

$alert = $_SESSION['alert'] ?? '';
$alertType = $_SESSION['alertType'] ?? '';
unset($_SESSION['alert'], $_SESSION['alertType']);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="author" content="Aryn Mei Wei JEE"/>
    <meta name="description" content="Manage Student Works Page"/>
    <meta name="keywords" content="Root Flower, floral class, Kuching-based"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/styles.css"/>

    <!--bootstrap framework CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">

    <title>RootFlower Manage Student Works Page</title>
</head>

<body class="ps-4">
<header>
    <?php include 'include/admin_sidebar.php'?>
</header>

<article class="admin-side">
    <div class="container-fluid">
    <?php if (!empty($alert)): ?>
        <div class="alert alert-<?= htmlspecialchars($alertType) ?> fade show alert-dismissable translate-middle start-50 position-fixed mt-1 ms-5 text-center w-60" role="alert">
            <?= htmlspecialchars($alert) ?>
        </div>
    <?php endif; ?>

        <div class="table-wrapper">
            <div class="table-title">
                <div class="row">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="ml-lg-2 mb-3 fw-semibold">Manage Student Works</h2>
                    </div>
                </div>
            </div>
            
            <div class="table-container overflow-x-scroll w-100">
                <table class="table table-bordered table-striped">
                    <thead class="text-center">
                        <tr>
                            <th>No.</th>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>Workshop Title</th>
                            <th>Uploaded Work(s)</th>
                            <th>Description</th>
                            <th>Upload Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    
                    <tbody class="text-center table-group-divider">
                        <?php if ($result->num_rows > 0): ?>
                            <?php $counter = $offset + 1; ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $counter ?></td>
                                    <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['workshop_title']) ?></td>
                                    <td>
                                        <button type="button" class="btn btn-update" onclick="openWorkModal(<?= htmlspecialchars(json_encode($row)) ?>)">
                                            <i class="bi bi-eye"></i> View Work
                                        </button>
                                    </td>
                                    <td class="text-start">
                                        <div class="description-preview overflow-y-auto">
                                            <?= htmlspecialchars(substr($row['description'], 0, 150))?>
                                        </div>
                                    </td>
                                    <td><?= date('Y-m-d', strtotime($row['upload_date'])) ?></td>
                                    <td>
                                        <?php 
                                            $currentStatus = $row['status'];
                                            $isApproved = $currentStatus === 'approved';
                                            $isRejected = $currentStatus === 'rejected';
                                        ?>
                                        
                                        <!-- approve -->
                                        <form method="POST" class="d-block">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" name="update_status_btn" class="btn btn-sm mb-1 <?= $isApproved ? 'btn-outline-secondary' : 'btn-outline-success' ?>" <?= $isApproved ? 'disabled' : '' ?>>
                                                Approve
                                            </button>
                                        </form>
                                        
                                        <!-- reject -->
                                        <form method="POST" class="d-block">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="status" value="rejected">
                                            <button type="submit" name="update_status_btn" class="btn btn-sm px-3 <?= $isRejected ? 'btn-outline-secondary' : 'btn-outline-danger' ?>" <?= $isRejected ? 'disabled' : '' ?>>
                                                Reject
                                            </button>
                                        </form>
                                    </td>
                                    <!-- edit and delete button -->
                                    <td class="text-nowrap">
                                        <form method="GET" action="manage_studentwork.php" class="d-inline me-2">
                                            <input type="hidden" name="edit" value="<?= $row['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-warning"><i class="bi bi-pen"></i></button>
                                        </form>
                                        
                                        <form method="GET" action="manage_studentwork.php" class="d-inline">
                                            <input type="hidden" name="delete" value="<?= $row['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php $counter++; ?>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No student works found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- pagination -->
            <div class="d-flex justify-content-start align-items-center mt-3 mb-5 gap-3">
                <div class="hint-text">Showing <?= min($records_current_page, $result->num_rows) ?> out of <?= $total_records ?> entries</div>
                <ul class="pagination mb-0">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </div>
        </div>

        <!-- edit student work modal -->
        <div class="modal fade" id="editWorkModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content themed-modal">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Student Work</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="work_id" value="<?= $edit_work ? htmlspecialchars($edit_work['id']) : '' ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control <?= !empty($errors['description']) ? 'is-invalid' : '' ?>" name="description" rows="5"><?= !empty($errors['description']) && isset($old['description']) ? htmlspecialchars($old['description']) : ($edit_work ? htmlspecialchars($edit_work['description']) : '') ?></textarea>
                                <?php if (!empty($errors['description'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['description']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="edit_work" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- delete student work modal -->
        <div class="modal fade" id="deleteWorkModal" tabindex="-1" aria-labelledby="deleteWorkModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content themed-modal">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteWorkModalLabel">Delete Student Work</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="work_id" value="<?= $delete_work ? htmlspecialchars($delete_work['id']) : '' ?>">
                            <p>Are you sure you want to delete this student work?</p>
                            <p><strong>Work ID: <?= $delete_work ? htmlspecialchars($delete_work['id']) : 'Unknown' ?></strong></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="delete_work" class="btn btn-danger">Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- view work modal -->
        <div class="modal fade" id="viewWorkModal" tabindex="-1" aria-labelledby="viewWorkModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content themed-modal">
                    <div class="modal-header">
                        <h5 class="modal-title" id="viewWorkModalLabel">Student Work</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="workGrid" class="media-grid2 mb-2 overflow-y-auto">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</article>

<?php 
include 'include/admin_footer.php' ?>

<!-- bootstrap framework Javascript-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script> 

<!-- keep modal open with validation error with URL parameter -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const formError = urlParams.get('form_error');
    const hasEditErrors = <?= !empty($errors) && isset($_POST['edit_work']) ? 'true' : 'false' ?>;
    
    if (formError === 'edit' || hasEditErrors) {
        const editModal = new bootstrap.Modal(document.getElementById('editWorkModal'));
        editModal.show();
    }

    <?php if (isset($_GET['edit']) && $edit_work): ?>
    const editUrlModal = new bootstrap.Modal(document.getElementById('editWorkModal'));
    editUrlModal.show();
    <?php endif; ?>
    
    <?php if (isset($_GET['delete']) && $delete_work): ?>
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteWorkModal'));
    deleteModal.show();
    <?php endif; ?>
});

// open view work modal
function openWorkModal(workData) {
    const workGrid = document.getElementById('workGrid');
    const mediaFiles = JSON.parse(workData.media_files);
    
    // clear previous grid items
    workGrid.innerHTML = '';

    mediaFiles.forEach((media, index) => {
        const isVideo = media.toLowerCase().endsWith('.mp4');
        const mediaContainer = document.createElement('div');
        mediaContainer.className = 'media-container position-relative overflow-hidden rounded';
        
        if (isVideo) {
            mediaContainer.innerHTML = `
                <video class="media-item2 w-100 object-fit-cover shadow rounded" controls autoplay muted>
                    <source src="${media}" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            `;
        } else {
            mediaContainer.innerHTML = `
                <img src="${media}" class="media-item2 w-100 object-fit-cover shadow rounded" alt="Work image ${index + 1}">
            `;
        }
        
        workGrid.appendChild(mediaContainer);
    });
    
    // show modal
    const viewModal = new bootstrap.Modal(document.getElementById('viewWorkModal'));
    viewModal.show();
}
</script>

<!-- clear URL -->
<?php if ((isset($_GET['edit']) || isset($_GET['delete']) || isset($_GET['form_error'])) && empty($errors)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.history.replaceState) {
                const clearURL = window.location.pathname;
                window.history.replaceState(null, null, clearURL);
            }
        });
    </script>
<?php endif; ?>

<!-- javascript -->
<script src="js/main.js"></script>
</body>
</html>