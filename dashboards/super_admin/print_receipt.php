<?php
require_once '../../core/db.php';
require_once '../../core/session.php';

// 1. Get Payment ID from URL
$id = $_GET['id'] ?? '';

if (empty($id)) {
    die("Error: No Invoice ID provided.");
}

// 2. Fetch All Payments from Settings
$stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'gym_payments'");
$stmt->execute();
$res = $stmt->fetch();
$payments = $res ? json_decode($res['setting_value'], true) : [];

// 3. Find the specific payment
$pay = null;
foreach ($payments as $p) {
    if ($p['id'] == $id) {
        $pay = $p;
        break;
    }
}

if (!$pay) {
    die("Error: Payment record not found.");
}

// 4. Fetch System Settings (Company Info)
$settings = [];
$stmt = $pdo->query("SELECT * FROM system_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt - <?= htmlspecialchars($id) ?></title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .receipt-card {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
        }
        .receipt-header { border-bottom: 2px solid #f1f1f1; padding-bottom: 20px; margin-bottom: 20px; }
        .receipt-footer { border-top: 2px solid #f1f1f1; padding-top: 20px; margin-top: 30px; font-size: 0.9rem; }
        .paid-stamp {
            position: absolute;
            top: 40px;
            right: 40px;
            border: 3px solid #198754;
            color: #198754;
            padding: 5px 15px;
            font-weight: bold;
            transform: rotate(15deg);
            border-radius: 10px;
            opacity: 0.8;
            text-transform: uppercase;
        }
        @media print {
            body { background: white; margin: 0; padding: 0; }
            .receipt-card { box-shadow: none; margin: 0 auto; border: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="receipt-card">
        <?php if (($pay['status'] ?? '') === 'Paid'): ?>
            <div class="paid-stamp">Paid</div>
        <?php endif; ?>

        <div class="receipt-header text-center">
            <h2 class="fw-bold mb-1"><?= htmlspecialchars($settings['system_name'] ?? 'GYM ERP') ?></h2>
            <p class="text-muted mb-0">Official Fee Receipt</p>
        </div>

        <div class="row mb-4">
            <div class="col-6">
                <small class="text-muted d-block uppercase">Billed To:</small>
                <h5 class="fw-bold mb-0"><?= htmlspecialchars($pay['member_name'] ?? 'Guest') ?></h5>
                <small>Reg No: <?= htmlspecialchars($pay['id'] ?? 'N/A') ?></small>
            </div>
            <div class="col-6 text-end">
                <small class="text-muted d-block uppercase">Invoice Details:</small>
                <p class="mb-0 fw-bold">ID: <?= htmlspecialchars($pay['id'] ?? 'N/A') ?></p>
                <small>Date: <?= date('d M Y', strtotime($pay['payment_date'])) ?></small>
            </div>
        </div>

        <table class="table table-borderless">
            <thead class="bg-light">
                <tr>
                    <th>Description</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr style="height: 100px; vertical-align: top;">
                    <td>
                        <h6 class="fw-bold mb-0"><?= htmlspecialchars($pay['plan_name'] ?? 'Gym Membership') ?></h6>
                        <small class="text-muted">Payment Method: <?= htmlspecialchars($pay['method'] ?? 'Cash') ?></small>
                    </td>
                    <td class="text-end fw-bold">Rs. <?= number_format($pay['amount']) ?></td>
                </tr>
                <tr class="border-top">
                    <td class="text-end fw-bold pt-3 h4">Total Paid</td>
                    <td class="text-end fw-bold pt-3 h4 text-success">Rs. <?= number_format($pay['amount']) ?></td>
                </tr>
            </tbody>
        </table>

        <div class="receipt-footer text-center">
            <p class="mb-1">Thank you for being a part of <strong><?= htmlspecialchars($settings['system_name'] ?? 'us') ?></strong>!</p>
            <p class="text-muted small">This is a system generated receipt and does not require a signature.</p>
        </div>

        <div class="text-center mt-4 no-print">
            <button onclick="window.print()" class="btn btn-primary px-4 rounded-pill me-2">
                <i class="bi bi-printer me-2"></i>Print Receipt
            </button>
            <button onclick="window.close()" class="btn btn-outline-secondary px-4 rounded-pill">
                Close
            </button>
        </div>
    </div>
</div>

<script>
    // Automatically trigger print on load
    window.onload = function() {
        // window.print();
    };
</script>

</body>
</html>
