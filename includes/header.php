<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/all.min.css">
    

    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar-custom { background-color: #141D49; }
        .navbar-custom .navbar-brand, .navbar-custom .nav-link { color: #ffffff; }
        .navbar-custom .nav-link:hover { color: #d1d1d1; }
        .sidebar { height: 100vh; background: #343a40; color: white; position: fixed; width: 240px; }
        .main-content { margin-left: 240px; padding: 30px; }
        @media (max-width: 768px) { .sidebar { width: 100%; height: auto; position: relative; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>

<?php if(isset($_SESSION['role'])): ?>
<nav class="navbar navbar-expand-lg navbar-custom shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand font-weight-bold d-flex align-items-center" href="#">
            <img src="../assets/images/logo.jpg" alt="MIT AOE Logo" 
                style="height: 35px; width: auto; margin-right: 12px; filter: brightness(-1) invert(1);">
            <span>Central Library</span>
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <span class="nav-link">Welcome, <?php echo $_SESSION['username'] ?? 'User'; ?></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger" href="../logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>