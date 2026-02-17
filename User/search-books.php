<?php
session_start();
require_once "../includes/config.php";
require_once "../includes/header.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}
?>

<style>
/* ===== GLOBAL ===== */
html, body {
    height: 100vh;
    overflow: hidden;
    background-color: #f4f7f6;
}

.main-wrapper {
    display: flex;
    flex-direction: column;
    height: 100vh;
}

/* ===== TOP HEADER ===== */
.sticky-header {
    background: #fff;
    border-bottom: 2px solid #eee;
    padding: 14px 16px;
    flex-shrink: 0;
}

/* ===== SEARCH BAR ===== */
.search-header {
    background: #fff;
    padding: 12px 16px;
    border-bottom: 1px solid #ddd;
    flex-shrink: 0;
}

/* ===== MAIN CONTENT ===== */
.main-content {
    flex-grow: 1;
    padding: 1px 16px;
    overflow-y: auto;
    background: #fff;
}

/* ===== STICKY LIST HEADER ===== */
.list-header {
    position: sticky;
    top: 0;
    z-index: 5;
    display: flex;
    padding: 12px 16px;
    background: #f8f9fa;
    font-weight: bold;
    font-size: 0.85rem;
    text-transform: uppercase;
    color: #6c757d;
    border-bottom: 2px solid #dee2e6;
}

/* ===== BOOK ROW ===== */
.book-list-item {
    display: flex;
    align-items: center;
    padding: 14px 16px;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.2s;
}

.book-list-item:hover {
    background-color: #fcfdfe;
}

/* ===== COLUMNS ===== */
.col-title  { width: 45%; font-weight: 600; color: #333; }
.col-author { width: 35%; color: #666; }
.col-copies { width: 20%; text-align: right; }

/* ===== BADGES ===== */
.copy-badge {
    font-size: 0.8rem;
    padding: 5px 14px;
    border-radius: 6px;
    font-weight: bold;
}

.badge-available {
    background: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #c8e6c9;
}

.badge-unavailable {
    background: #ffebee;
    color: #c62828;
    border: 1px solid #ffcdd2;
}
</style>

<div class="main-wrapper">

    <!-- ===== HEADER ===== -->
    <div class="sticky-header d-flex justify-content-between align-items-center">
        <h4 class="m-0 font-weight-bold">
            <i class="fas fa-book-reader mr-2"></i> Library Catalog
        </h4>
        <a href="dashboard.php" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
        </a>
    </div>

    <!-- ===== SEARCH ===== -->
    <div class="search-header">
        <div class="d-flex align-items-center">
            <div class="flex-grow-1 mr-3">
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text"
                           id="catalogSearch"
                           class="form-control"
                           placeholder="Search by Title or Author">
                </div>
            </div>

            <div class="custom-control custom-switch border rounded px-3 py-2 bg-light shadow-sm">
                <input type="checkbox" class="custom-control-input" id="availableOnly">
                <label class="custom-control-label font-weight-bold" for="availableOnly">
                    Available Only
                </label>
            </div>
        </div>
    </div>

    <!-- ===== CONTENT ===== -->
    <div class="main-content">

        <!-- FIXED COLUMN ROW -->
        <div class="list-header">
            <div class="col-title">Book Title</div>
            <div class="col-author">Author</div>
            <div class="col-copies">Available Copies</div>
        </div>

        <div id="bookGrid">
            <?php
            $sql = "
                SELECT title, author,
                       SUM(status = 1) AS available_count,
                       COUNT(*) AS total_count
                FROM books
                GROUP BY title, author
                ORDER BY title ASC
            ";
            $res = mysqli_query($connection, $sql);

            if ($res && mysqli_num_rows($res) > 0):
                while ($book = mysqli_fetch_assoc($res)):
                    $avail = (int)$book['available_count'];
            ?>
                <div class="book-list-item book-entry" data-available="<?= $avail ?>">
                    <div class="col-title title-search"><?= htmlspecialchars($book['title']) ?></div>
                    <div class="col-author author-search"><?= htmlspecialchars($book['author']) ?></div>
                    <div class="col-copies">
                        <?php if ($avail > 0): ?>
                            <span class="copy-badge badge-available">
                                <?= $avail ?> / <?= $book['total_count'] ?> Available
                            </span>
                        <?php else: ?>
                            <span class="copy-badge badge-unavailable">
                                Out of Stock
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php
                endwhile;
            else:
            ?>
                <div class="text-center p-5 text-muted">No books found.</div>
            <?php endif; ?>
        </div>

        <div id="noResults" class="text-center p-5 d-none">
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <p class="text-muted h5">No matching books found</p>
        </div>

    </div>
</div>

<?php include "../includes/footer.php"; ?>

<script>
$(document).ready(function () {

    function filterBooks() {
        let query = $('#catalogSearch').val().toLowerCase().trim();
        let onlyAvail = $('#availableOnly').is(':checked');
        let visible = 0;

        $('.book-entry').each(function () {
            let title = $(this).find('.title-search').text().toLowerCase();
            let author = $(this).find('.author-search').text().toLowerCase();
            let avail = parseInt($(this).data('available'));

            let matchesSearch = title.includes(query) || author.includes(query);
            let matchesAvail = !onlyAvail || avail > 0;

            if (matchesSearch && matchesAvail) {
                $(this).css('display', 'flex');
                visible++;
            } else {
                $(this).css('display', 'none');
            }
        });

        $('#noResults').toggleClass('d-none', visible !== 0);
    }

    $('#catalogSearch').on('input', filterBooks);
    $('#availableOnly').on('change', filterBooks);
});
</script>
