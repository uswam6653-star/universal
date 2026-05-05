<?php 
require_once '../../includes/header.php'; 

// 1. Summary Statistics
$total_collected = $pdo->query("SELECT SUM(amount) FROM payments")->fetchColumn() ?: 0;
$total_pending = $pdo->query("SELECT SUM(balance_due) FROM invoices")->fetchColumn() ?: 0;
$def_count = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM invoices WHERE balance_due > 0")->fetchColumn() ?: 0;

// 2. Program-wise Revenue Data for Chart
$program_data = $pdo->query("
    SELECT p.name, SUM(inv.payable_amount - inv.balance_due) as collected 
    FROM programs p 
    JOIN semesters s ON p.id = s.program_id 
    JOIN invoices inv ON s.id = inv.semester_id 
    GROUP BY p.id
")->fetchAll();

$prog_labels = []; $prog_values = [];
foreach($program_data as $pd) {
    $prog_labels[] = $pd['name'];
    $prog_values[] = (float)$pd['collected'];
}
?>

<div class="row">
    <div class="col-lg-4 col-6">
        <div class="small-box text-bg-success">
            <div class="inner">
                <h3>PKR <?= number_format($total_collected / 1000, 1) ?>k</h3>
                <p>Total Revenue Collected</p>
            </div>
            <div class="icon"><i class="bi bi-cash-coin"></i></div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box text-bg-danger">
            <div class="inner">
                <h3>PKR <?= number_format($total_pending / 1000, 1) ?>k</h3>
                <p>Outstanding Dues</p>
            </div>
            <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
        </div>
    </div>
    <div class="col-lg-4 col-12">
        <div class="small-box text-bg-warning">
            <div class="inner">
                <h3><?= $def_count ?></h3>
                <p>Active Defaulters</p>
            </div>
            <div class="icon"><i class="bi bi-person-x"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Revenue by Program</h3></div>
            <div class="card-body">
                <canvas id="programChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title">Top Defaulter List</h3></div>
            <div class="card-body p-0">
                <table class="table table-hover table-sm">
                    <thead><tr><th>Name</th><th>Dues</th></tr></thead>
                    <tbody>
                        <?php
                        $defaulters = $pdo->query("
                            SELECT u.name, i.balance_due 
                            FROM invoices i 
                            JOIN users u ON i.user_id = u.id 
                            WHERE i.balance_due > 0 
                            ORDER BY i.balance_due DESC 
                            LIMIT 8
                        ");
                        while($df = $defaulters->fetch()): ?>
                        <tr>
                            <td><?= htmlspecialchars($df['name']) ?></td>
                            <td class="text-danger fw-bold"><?= number_format($df['balance_due'], 0) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Program Chart
new Chart(document.getElementById('programChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($prog_labels) ?>,
        datasets: [{
            label: 'Collected Amount (PKR)',
            data: <?= json_encode($prog_values) ?>,
            backgroundColor: '#17a2b8'
        }]
    },
    options: { maintainAspectRatio: false }
});
</script>

<?php require_once '../../includes/footer.php'; ?>