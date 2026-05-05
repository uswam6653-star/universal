<?php 
require_once '../../includes/header.php'; 

// 1. Handle Add Scholarship
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_scholarship'])) {
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $amount = $_POST['amount'];
    
    $stmt = $pdo->prepare("INSERT INTO scholarships (name, type, amount) VALUES (?, ?, ?)");
    $stmt->execute([$name, $type, $amount]);
    echo "<script>alert('Scholarship defined!'); window.location.href='manage_scholarships.php';</script>";
}

// 2. Handle Awarding Scholarship to Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['award_scholarship'])) {
    $user_id = $_POST['user_id'];
    $scholar_id = $_POST['scholarship_id'];
    
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT IGNORE INTO user_scholarships (user_id, scholarship_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $scholar_id]);
    
    // Update student's scholarship percent if it's percentage based
    $sch = $pdo->prepare("SELECT * FROM scholarships WHERE id = ?");
    $sch->execute([$scholar_id]);
    $s_data = $sch->fetch();
    
    if ($s_data['type'] === 'percentage') {
        $upd = $pdo->prepare("UPDATE users SET scholarship_percent = ? WHERE id = ?");
        $upd->execute([$s_data['amount'], $user_id]);
    }
    
    $pdo->commit();
    echo "<script>alert('Scholarship awarded successfully!'); window.location.href='manage_scholarships.php';</script>";
}

$scholarships = $pdo->query("SELECT * FROM scholarships")->fetchAll();
$students = $pdo->query("SELECT id, name, roll_no FROM users WHERE role = 'student'")->fetchAll();
?>

<div class="row">
    <div class="col-md-5">
        <div class="card card-info card-outline">
            <div class="card-header"><h3 class="card-title">Define Scholarships</h3></div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label>Scholarship Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Merit 25%" required>
                    </div>
                    <div class="mb-3">
                        <label>Type</label>
                        <select name="type" class="form-select">
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed Amount</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Value</label>
                        <input type="number" name="amount" class="form-control" required>
                    </div>
                    <button type="submit" name="add_scholarship" class="btn btn-info w-100">Save Definition</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title">Award Scholarship to Student</h3></div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label>Select Student</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">-- Choose Student --</option>
                            <?php foreach($students as $st): ?>
                                <option value="<?= $st['id'] ?>"><?= $st['roll_no'] ?> - <?= htmlspecialchars($st['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Select Scholarship</label>
                        <select name="scholarship_id" class="form-select" required>
                            <option value="">-- Choose Scholarship --</option>
                            <?php foreach($scholarships as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= $s['amount'] ?><?= $s['type']=='percentage'?'%':' PKR' ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="award_scholarship" class="btn btn-primary w-100">Award Scholarship</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>