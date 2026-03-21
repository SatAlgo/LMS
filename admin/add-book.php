<?php
session_start();
require_once "../includes/config.php";
require_once "../includes/header.php";

// Admin Access Control
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    header("Location: ../index.php");
    exit();
}

$success_msg = "";
$error_msg = "";

// Fetch Accession Numbers for suggestions
$last_acc_query = mysqli_query($connection, "SELECT accession_number FROM books ORDER BY CAST(accession_number AS UNSIGNED) DESC LIMIT 1");
$last_acc_row = mysqli_fetch_assoc($last_acc_query);
$last_acc = $last_acc_row ? $last_acc_row['accession_number'] : "None";
$suggested_acc = $last_acc_row ? (int)$last_acc_row['accession_number'] + 1 : 1;

if (isset($_POST['add_book'])) {
    $acc_no = mysqli_real_escape_string($connection, $_POST['accession_number']);
    $title = mysqli_real_escape_string($connection, $_POST['title']);
    $author = mysqli_real_escape_string($connection, $_POST['author']);
    $publisher = mysqli_real_escape_string($connection, $_POST['publisher']);
    $price = mysqli_real_escape_string($connection, $_POST['price']);
    $status = 1; // Available

    $check_acc = mysqli_query($connection, "SELECT accession_number FROM books WHERE accession_number='$acc_no'");
    if (mysqli_num_rows($check_acc) > 0) {
        $error_msg = "Error: Accession Number $acc_no already exists.";
    } else {
        // Master Table Logic
        $master_check = mysqli_query($connection, "SELECT book_id FROM book_master WHERE title='$title' AND author='$author'");
        if (mysqli_num_rows($master_check) > 0) {
            $book_id = mysqli_fetch_assoc($master_check)['book_id'];
        } else {
            mysqli_query($connection, "INSERT INTO book_master (title, author, publisher) VALUES ('$title', '$author', '$publisher')");
            $book_id = mysqli_insert_id($connection);
        }

        // Insert into books
        $query = "INSERT INTO books (accession_number, book_id, title, author, publisher, price, status) 
                  VALUES ('$acc_no', '$book_id', '$title', '$author', '$publisher', '$price', '$status')";
        
        if (mysqli_query($connection, $query)) {
            $success_msg = "Book added successfully!";
            // Update suggestions for the next entry
            $last_acc = $acc_no;
            $suggested_acc = (int)$acc_no + 1;
        } else {
            $error_msg = "Database Error: " . mysqli_error($connection);
        }
    }
}
?>

<style>
    /* Prevent page-level scrolling */
    html, body { height: 100vh; overflow: hidden; background-color: #f4f7f6; }
    .main-wrapper { display: flex; height: 100vh; width: 100vw; }
    
    .content-area { 
        flex-grow: 1; 
        display: flex; 
        flex-direction: column; 
        overflow: hidden; 
        margin-left: 10px; /* Aligns with the fixed sidebar */
    }

    .sticky-header { 
        background: white; 
        border-bottom: 2px solid #eee; 
        padding: 15px 30px; /* Reduced padding for more space */
        margin-left: 230px;
        flex-shrink: 0; 
    }

    .main-content { 
        padding: 20px 30px; /* Reduced padding */
        flex-grow: 1; 
        overflow-y: auto; /* Internal scrolling only */
        display: flex; 
        justify-content: center; 
        align-items: flex-start; 
    }

    .card-add { border: none; border-radius: 12px; width: 100%; max-width: 700px; }
    .form-group { margin-bottom: 1rem; } /* Tightened spacing */
</style>

<div class="main-wrapper">
    <?php include "../sidebar.php"; ?>

    <div class="content-area">
        <div class="sticky-header">
            <h3 class="m-0 font-weight-bold">
                <i class="fas fa-book-medical text-success mr-2"></i> Add New Book Entry
            </h3>
        </div>

        <div class="main-content">
            <div class="card card-add shadow-sm">
                <div class="card-body p-4">
                    <?php if ($success_msg): ?>
                        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                            <i class="fas fa-check-circle mr-2"></i> <?php echo $success_msg; ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_msg): ?>
                        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                            <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $error_msg; ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold small text-uppercase">Accession Number</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" name="accession_number" class="form-control" 
                                           value="<?php echo $suggested_acc; ?>" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text bg-light">Last: <b><?php echo $last_acc; ?></b></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold small text-uppercase">Book Title</label>
                            <input type="text" name="title" class="form-control form-control-sm" placeholder="Title" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold small text-uppercase">Author(s)</label>
                                <input type="text" name="author" class="form-control form-control-sm" placeholder="Author" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold small text-uppercase">Publisher</label>
                                <input type="text" name="publisher" class="form-control form-control-sm" placeholder="Publisher" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 form-group">
                                <label class="font-weight-bold small text-uppercase">Price (₹)</label>
                                <input type="number" name="price" class="form-control form-control-sm" placeholder="0.00" step="0.01">
                            </div>
                        </div>

                        <hr class="my-3">
                        <div class="d-flex">
                            <button type="submit" name="add_book" class="btn btn-success btn-sm px-4 font-weight-bold mr-2">
                                <i class="fas fa-save mr-2"></i> SAVE BOOK
                            </button>
                            <a href="manage-books.php" class="btn btn-outline-secondary btn-sm px-4">CANCEL</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>