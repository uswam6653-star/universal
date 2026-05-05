<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'includes/header.php'; 

$uid = $_SESSION['user_id'] ?? 0;
$uname = $_SESSION['name'] ?? 'Trainer';

// Fetch Trainer Stats - Wrap in Try-Catch
try {
    $totalAssigned = $pdo->prepare("SELECT count(*) FROM complaints WHERE assigned_to = ? AND subject = 'WORKOUT'");
    $totalAssigned->execute([$uid]);
    $memberCount = $totalAssigned->fetchColumn() ?: 0;
} catch (Exception $e) {
    echo "Error fetching stats: " . $e->getMessage();
    $memberCount = 0;
}

?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white p-5 rounded-4 shadow-sm border-0">
                <h1 class="fw-bold">Welcome, <?= htmlspecialchars($uname) ?>!</h1>
                <p class="lead">You are currently managing <?= $memberCount ?> active members.</p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <a href="manage_members.php" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center">
                    <i class="bi bi-people-fill display-4 text-primary mb-3"></i>
                    <h5 class="fw-bold">My Squad</h5>
                    <p class="text-muted small">View all members assigned to you.</p>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="../hod/workout_plans.php" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center">
                    <i class="bi bi-clipboard2-check-fill display-4 text-success mb-3"></i>
                    <h5 class="fw-bold">Workouts</h5>
                    <p class="text-muted small">Update member exercise routines.</p>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="../hod/member_progress.php" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center">
                    <i class="bi bi-graph-up-arrow display-4 text-info mb-3"></i>
                    <h5 class="fw-bold">Progress Logs</h5>
                    <p class="text-muted small">Track body stats and performance.</p>
                </div>
            </a>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
