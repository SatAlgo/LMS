<?php
session_start();
require_once "../includes/config.php";
require_once "../includes/header.php";

// Admin Access Control
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$acc_no = mysqli_real_escape_string($connection, $_GET['id']);
$success_msg = "";
$error_msg = "";

// Handle Update Request
if (isset($_POST['update_book'])) {
    $title = mysqli_real_escape_string($connection, $_POST['title']);
    $author = mysqli_real_escape_string($connection, $_POST['author']);
    $isbn = mysqli_real_escape_string($connection, $_POST['isbn']);
    $price = mysqli_real_escape_string($connection, $_POST['price']);

    // Only update if the accession number matches AND the status is 1 (Available)
    $update_query = "UPDATE books SET title='$title', author='$author', price='$price' 
                 WHERE accession_number='$acc_no' AND status = 1";
    
    if (mysqli_query($connection, $update_query)) {
        $success_msg = "Book details updated successfully!";
    } else {
        $error_msg = "Error updating record: " . mysqli_error($connection);
    }
}

// Fetch current details to populate the form
$res = mysqli_query($connection, "SELECT * FROM books WHERE accession_number='$acc_no'");
$row = mysqli_fetch_assoc($res);

// Redirect if book doesn't exist
if ((int)$row['status'] === 0) {
    header("Location: manage-books.php?msg=issued");
    exit();
}
?>

<style>
    /* Unified Fixed Layout */
    html, body { height: 100vh; overflow: hidden; background-color: #f4f7f6; }
    .main-wrapper { display: flex; height: 100vh; width: 100vw; }
    
    /* Content Area with 240px offset for the fixed sidebar */
    .content-area { 
        flex-grow: 1; 
        display: flex; 
        flex-direction: column; 
        overflow: hidden; 
        margin-left: 10px; 
    }

    .sticky-header { 
        background: white; 
        border-bottom: 2px solid #eee; 
        padding: 25px 30px; 
        margin-left: 230px; 
        flex-shrink: 0; 
    }

    .main-content { 
        padding: 40px; 
        flex-grow: 1; 
        overflow-y: auto; 
        display: flex; 
        justify-content: center; 
        align-items: flex-start; 
    }

    .card-edit { border: none; border-radius: 12px; width: 100%; max-width: 750px; }
</style>

<div class="main-wrapper">
    <?php include "../sidebar.php"; ?>

    <div class="content-area">
        <div class="sticky-header">
            <h3 class="m-0 font-weight-bold">
                <i class="fas fa-edit text-info mr-2"></i> Edit Book Copy
            </h3>
        </div>

        <div class="main-content">
            <div class="card card-edit shadow-sm">
                <div class="card-body p-4">
                    <?php if($success_msg): ?>
                        <div class="alert alert-success shadow-sm">
                            <i class="fas fa-check-circle mr-2"></i> <?php echo $success_msg; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($error_msg): ?>
                        <div class="alert alert-danger shadow-sm"><?php echo $error_msg; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold text-muted small">ACCESSION NUMBER (READ-ONLY)</label>
                                <input type="text" class="form-control bg-light font-weight-bold" 
                                       value="<?php echo $row['accession_number']; ?>" readonly>
                                <small class="text-muted">Unique ID cannot be changed.</small>
                            </div>
                            <!-- <div class="col-md-6 form-group">
                                <label class="font-weight-bold">ISBN</label>
                                <input type="text" name="isbn" class="form-control" 
                                       value="<?php echo htmlspecialchars($row['isbn'] ?? ''); ?>" placeholder="ISBN-13">
                            </div> -->
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Book Title</label>
                            <input type="text" name="title" class="form-control" 
                                   value="<?php echo htmlspecialchars($row['title']); ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-8 form-group">
                                <label class="font-weight-bold">Author(s)</label>
                                <input type="text" name="author" class="form-control" 
                                       value="<?php echo htmlspecialchars($row['author']); ?>" required>
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="font-weight-bold">Price (₹)</label>
                                <input type="number" name="price" class="form-control" step="0.01" 
                                       value="<?php echo $row['price']; ?>">
                            </div>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex">
                            <button type="submit" name="update_book" class="btn btn-info px-5 font-weight-bold mr-2 text-white shadow-sm">
                                UPDATE BOOK
                            </button>
                            <a href="manage-books.php" class="btn btn-outline-secondary px-4">CANCEL</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>