<?php
session_start();
require_once "../includes/config.php";
require_once "../includes/header.php";

if (!isset($_SESSION['role'])) {
    header("Location: ../index.php");
    exit();
}
?>

<style>
    html, body { height: 100vh; overflow: hidden; background-color: #f4f7f6; }
    .main-wrapper { display: flex; height: 100vh; width: 100vw; }
    .content-area { flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; }
    .sticky-header { background: white; border-bottom: 2px solid #eee; padding: 15px 30px; margin-left: 230px; flex-shrink: 0; }
    .main-content { padding: 30px; flex-grow: 1; overflow-y: auto; }
    
    .info-card { border: none; border-radius: 15px; background: white; margin-bottom: 25px; height: 100%; }
    .dev-badge { background: #e3f2fd; color: #0d47a1; padding: 4px 12px; border-radius: 20px; font-weight: bold; font-size: 0.75rem; }
    .mentor-section { border-left: 3px solid #007bff; padding-left: 15px; }
</style>

<div class="main-wrapper">
    <?php include "../sidebar.php"; ?>

    <div class="content-area">
        <div class="sticky-header">
            <h3 class="m-0 font-weight-bold"><i class="fas fa-info-circle text-primary mr-2"></i> System Information</h3>
        </div>

        <div class="main-content">
            <div class="row">
                <div class="col-md-7">
                    <div class="card info-card shadow-sm p-4">
                        <h5 class="font-weight-bold text-dark mb-3">Project Overview</h5>
                        <p class="text-muted small">This Library Management System is a specialized digital transformation initiative for the <b>MIT AOE Central Library</b>. It automates critical workflows including student record management, real-time book tracking, and dynamic fine calculation.</p>
                        
                        <div class="mentor-section mt-4">
                            <h6 class="font-weight-bold mb-2">Faculty Guidance</h6>
                            <p class="mb-1 small"><b>Project Guide:</b> Dr. Minakshi N. Vharkate</p>
                            <p class="small"><b>Head of Department:</b> Dr. Pramod Ganjewar</p>
                        </div>

                        <h6 class="font-weight-bold mt-4 mb-2">Technical Features</h6>
                        <ul class="list-unstyled small text-muted">
                            <li class="mb-1"><i class="fas fa-check text-success mr-2"></i> Role-Based Access Control (Admin/Staff/Student)</li>
                            <li class="mb-1"><i class="fas fa-check text-success mr-2"></i> Real-time Live Fine Calculation Logic</li>
                            <li class="mb-1"><i class="fas fa-check text-success mr-2"></i> Sticky-UI for seamless large-scale data management</li>
                            <li class="mb-1"><i class="fas fa-check text-success mr-2"></i> Industry-Standard Encryption: Implemented robust password hashing</li>
                            <li class="mb-1"><i class="fas fa-check text-success mr-2"></i> Multi-Factor Password Recovery</li>
                            <li class="mb-1"><i class="fas fa-check text-success mr-2"></i> Automated Notification Engine</li>
                            <li class="mb-1"><i class="fas fa-check text-success mr-2"></i> High-Performance Indexing: Employs fast search features optimized for large datasets</li>
                        </ul>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="card info-card shadow-sm p-4 text-center">
                        <div class="mb-3">
                            <i class="fas fa-users fa-2x text-primary"></i>
                        </div>
                        <h5 class="font-weight-bold">Development Team</h5>
                        
                        <div class="bg-light p-3 rounded mb-3 border">
                            <h6 class="font-weight-bold mb-0">Satyam Gaikwad</h6>
                            <p class="small text-primary mb-1">Lead Developer & System Architect</p>
                            <span class="dev-badge">B.Tech Comp. Engg (2027)</span>
                        </div>

                        <div class="text-left mt-3">
                            <h6 class="small font-weight-bold text-uppercase text-muted">Team Members</h6>
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span class="small">Sahas Nagar</span>
                                <span class="small text-muted">Contributor</span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="small">Sanket Banate</span>
                                <span class="small text-muted">Contributor</span>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4 pt-3 border-top">
                            <p class="small text-muted mb-0"><b>Contact:</b> satyamgaikwad.mitaoe@gmail.com</p>
                            <p class="small text-muted"><b>Version:</b> 1.0.5 (Stable Build)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>