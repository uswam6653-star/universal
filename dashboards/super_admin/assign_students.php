<?php 
require_once __DIR__ . '/../../includes/header.php'; 

// Handle Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_trainer'])) {
    $mid = $_POST['member_id'];
    $tid = $_POST['trainer_id'];
    
    // Use 'complaints' table as assignment bridge
    // subject = 'WORKOUT' represents the link
    $check = $pdo->prepare("SELECT id FROM complaints WHERE user_id = ? AND subject = 'WORKOUT'");
    $check->execute([$mid]);
    
    if ($check->fetch()) {
        $pdo->prepare("UPDATE complaints SET assigned_to = ?, status = 'In Progress' WHERE user_id = ? AND subject = 'WORKOUT'")->execute([$tid, $mid]);
    } else {
        $pdo->prepare("INSERT INTO complaints (user_id, assigned_to, subject, description, status) VALUES (?, ?, 'WORKOUT', 'Gym Member workout plan', 'In Progress')")->execute([$mid, $tid]);
    }
    $success = "Trainer assigned successfully!";
}

// Fetch Trainers
$trainers = $pdo->query("SELECT id, name FROM users WHERE role = 'trainer'")->fetchAll();

// Fetch Members (Students) and their assigned trainers
$members = $pdo->query("
    SELECT u.id, u.name, u.roll_no, t.name as trainer_name 
    FROM users u 
    LEFT JOIN complaints c ON u.id = c.user_id AND c.subject = 'WORKOUT'
    LEFT JOIN users t ON c.assigned_to = t.id
    WHERE u.role = 'student'
    ORDER BY u.name
")->fetchAll();
?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h5 class="fw-bold mb-4">Assign New Trainer</h5>
            <?php if(isset($success)) echo "<div class='alert alert-success small'>$success</div>"; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="small fw-bold">Select Member</label>
                    <select name="member_id" class="form-select rounded-3" required>
                        <option value="">-- Choose Member --</option>
                        <?php foreach($members as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= $m['name'] ?> (<?= $m['roll_no'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="small fw-bold">Select Trainer</label>
                    <select name="trainer_id" class="form-select rounded-3" required>
                        <option value="">-- Choose Trainer --</option>
                        <?php foreach($trainers as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= $t['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="assign_trainer" class="btn btn-primary w-100 rounded-3 py-2 fw-bold">Link Trainer</button>
            </form>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-transparent border-0 p-4"><h5 class="fw-bold mb-0">Trainer Assignments</h5></div>
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light small fw-bold"><tr><th class="ps-4">Member</th><th>Assigned Trainer</th></tr></thead>
                    <tbody>
                        <?php foreach($members as $m): ?>
                        <tr>
                            <td class="ps-4"><strong><?= $m['name'] ?></strong><br><small><?= $m['roll_no'] ?></small></td>
                            <td>
                                <?php if($m['trainer_name']): ?>
                                    <span class="badge bg-success shadow-sm"><?= $m['trainer_name'] ?></span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border">Not Assigned</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
