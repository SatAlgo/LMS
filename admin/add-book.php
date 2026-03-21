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

// 1. Fetch Branches from Settings Table (Comma Separated)
$branch_data = mysqli_query($connection, "SELECT config_value FROM settings WHERE config_key = 'branches' OR config_key = 'branch' LIMIT 1");
$branch_row = mysqli_fetch_assoc($branch_data);
$branches_list = ($branch_row && !empty($branch_row['config_value'])) ? explode(',', $branch_row['config_value']) : [];

// 2. Fetch Accession Numbers for suggestions
$last_acc_query = mysqli_query($connection, "SELECT accession_number FROM books ORDER BY CAST(accession_number AS UNSIGNED) DESC LIMIT 1");
$last_acc_row = mysqli_fetch_assoc($last_acc_query);
$last_acc = $last_acc_row ? $last_acc_row['accession_number'] : "None";
$suggested_acc = $last_acc_row ? (int)$last_acc_row['accession_number'] + 1 : 1;

// 3. Process Form Submission
if (isset($_POST['add_book'])) {
    $acc_no = mysqli_real_escape_string($connection, $_POST['accession_number']);
    $title = mysqli_real_escape_string($connection, $_POST['title']);
    $author = mysqli_real_escape_string($connection, $_POST['author']);
    $publisher = mysqli_real_escape_string($connection, $_POST['publisher']);
    $isbn = mysqli_real_escape_string($connection, $_POST['isbn'] ?? '');
    $edition = mysqli_real_escape_string($connection, $_POST['edition'] ?? '');
    $branch = mysqli_real_escape_string($connection, $_POST['branch'] ?? '');
    $price = mysqli_real_escape_string($connection, $_POST['price'] ?? 0);
    $location   = mysqli_real_escape_string($connection, $_POST['rack_location'] ?? '');
    $condition  = mysqli_real_escape_string($connection, $_POST['book_condition'] ?? 'Good');
    $status = 1; // Available

    // Check if Accession Number exists
    $check_acc = mysqli_query($connection, "SELECT accession_number FROM books WHERE accession_number='$acc_no'");
    if (mysqli_num_rows($check_acc) > 0) {
        $error_msg = "Error: Accession Number $acc_no already exists.";
    } else {
        // MASTER TABLE LOGIC
        // Check if book already exists in master by title and author
        $master_check = mysqli_query($connection, "SELECT book_id FROM book_master WHERE title='$title' AND author='$author'");
        
        if (mysqli_num_rows($master_check) > 0) {
            $book_id = mysqli_fetch_assoc($master_check)['book_id'];
        } else {
            // Add to Master Table if it doesn't exist
            $master_sql = "INSERT INTO book_master (title, author, publisher, isbn, edition) 
                           VALUES ('$title', '$author', '$publisher', '$isbn', '$edition')";
            if(mysqli_query($connection, $master_sql)) {
                $book_id = mysqli_insert_id($connection);
            } else {
                $error_msg = "Master Table Error: " . mysqli_error($connection);
            }
        }

        // PHYSICAL BOOKS TABLE LOGIC
        if (isset($book_id)) {
            $query = "INSERT INTO books (accession_number, book_id, title, author, publisher, edition, isbn, branch, rack_location, book_condition, price, status) 
                    VALUES ('$acc_no', '$book_id', '$title', '$author', '$publisher', '$edition', '$isbn', '$branch', '$location', '$condition', '$price', '$status')";
            
            if (mysqli_query($connection, $query)) {
                $success_msg = "Book added successfully!";
                $last_acc = $acc_no;
                $suggested_acc = (int)$acc_no + 1;
            } else {
                $error_msg = "Books Table Error: " . mysqli_error($connection);
            }
        }
    }
}
?>

<style>
    html, body { height: 100vh; overflow: hidden; background-color: #f4f7f6; }
    .main-wrapper { display: flex; height: 100vh; width: 100vw; }
    .content-area { flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; margin-left: 10px; }
    .sticky-header { background: white; border-bottom: 2px solid #eee; padding: 15px 30px; margin-left: 230px; flex-shrink: 0; }
    .main-content { padding: 20px 30px; flex-grow: 1; overflow-y: auto; display: flex; justify-content: center; align-items: flex-start; }
    .card-add { border: none; border-radius: 12px; width: 100%; max-width: 750px; }
    .form-group { margin-bottom: 1rem; }
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
                    <?php if ($success_msg) echo "<div class='alert alert-success'>$success_msg</div>"; ?>
                    <?php if ($error_msg) echo "<div class='alert alert-danger'>$error_msg</div>"; ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold small text-uppercase">Accession Number</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" name="accession_number" class="form-control" value="<?php echo $suggested_acc; ?>" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text bg-light">Last: <b><?php echo $last_acc; ?></b></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold small text-uppercase">ISBN (Optional)</label>
                                <input type="text" name="isbn" class="form-control form-control-sm" placeholder="ISBN">
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
                                <label class="font-weight-bold small text-uppercase">Edition (Optional)</label>
                                <input type="text" name="edition" class="form-control form-control-sm" placeholder="e.g. 5th Ed">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold small text-uppercase">Publisher</label>
                                <input type="text" name="publisher" class="form-control form-control-sm" placeholder="Publisher" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold small text-uppercase">Branch/Category</label>
                                <select name="branch" class="form-control form-control-sm">
                                    <option value="">-- Select Branch --</option>
                                    <?php 
                                    foreach ($branches_list as $b) {
                                        $name = trim($b);
                                        if(!empty($name)) echo "<option value='$name'>$name</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold small text-uppercase">Rack Location</label>
                                <input type="text" name="rack_location" class="form-control form-control-sm" placeholder="e.g. Shelf A-101">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold small text-uppercase">Book Condition</label>
                                <select name="book_condition" class="form-control form-control-sm">
                                    <option value="Good">Good</option>
                                    <option value="New">New</option>
                                    <option value="Fair">Fair (Used)</option>
                                    <option value="Damaged">Damaged</option>
                                </select>
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