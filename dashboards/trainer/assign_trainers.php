<?php 
require_once __DIR__ . '/../../includes/header.php'; 

// Handle Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_trainer'])) {
    $mid = $_POST['member_id'];
    $tid = $_POST['trainer_id'];
    
    // Update complaints table (Workout assignment)
    $stmt = $pdo->prepare("UPDATE complaints SET assigned_to = ? WHERE user_id = ? AND subject = 'WORKOUT'");
    $stmt->execute([$tid, $mid]);
    
    // Update identity_no in users table
    $trainer = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $trainer->execute([$tid]);
    $tname = $trainer->fetchColumn();
    
    $user = $pdo->prepare("SELECT identity_no FROM users WHERE id = ?");
    $user->execute([$mid]);
    $meta = explode('|', $user->fetchColumn() ?? '');
    $meta = array_pad($meta, 11, '');
    $meta[6] = $tname; // Index 6 is Trainer Name
    $meta[10] = "trainer:$tid"; // Index 10 is Trainer ID
    $new_meta = implode('|', $meta);
    
    $pdo->prepare("UPDATE users SET identity_no = ? WHERE id = ?")->execute([$new_meta, $mid]);
    $success = "Member successfully reassigned to $tname.";
}

// Fetch All Members and Trainers
$members = $pdo->query("SELECT id, name, registration_no FROM users WHERE role = 'student' ORDER BY name ASC")->fetchAll();
$trainers = $pdo->query("SELECT id, name FROM users WHERE role IN ('trainer', 'hod') ORDER BY name ASC")->fetchAll();
?>

<div class="row g-4">
    <div class="col-md-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
            <h5 class="fw-bold mb-4">Assign / Change Trainer</h5>
            <?php if(isset($success)) echo "<div class='alert alert-success rounded-3 small shadow-sm'>$success</div>"; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Select Member</label>
                    <select name="member_id" class="form-select border-0 bg-light rounded-3 p-3" required>
                        <option value="">-- Choose Member --</option>
                        <?php foreach($members as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= $m['name'] ?> (<?= $m['registration_no'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Assign to Coach</label>
                    <select name="trainer_id" class="form-select border-0 bg-light rounded-3 p-3" required>
                        <option value="">-- Choose Trainer --</option>
                        <?php foreach($trainers as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= $t['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="assign_trainer" class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow">Confirm Assignment</button>
            </form>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 bg-white">
            <div class="card-header bg-dark text-white p-4">
                <h5 class="fw-bold mb-0">Current Assignments</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small fw-bold">
                            <tr>
                                <th class="ps-4">Member</th>
                                <th>Assigned Trainer</th>
                                <th class="text-end pe-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $stmt = $pdo->query("
                                SELECT u.name as member_name, t.name as trainer_name
                                FROM users u
                                JOIN complaints c ON u.id = c.user_id AND c.subject = 'WORKOUT'
                                JOIN users t ON c.assigned_to = t.id
                                ORDER BY u.name ASC
                            ");
                            while($row = $stmt->fetch()): ?>
                            <tr>
                                <td class="ps-4 fw-bold small text-dark"><?= htmlspecialchars($row['member_name']) ?></td>
                                <td class="small text-muted"><i class="bi bi-person-check me-1"></i> <?= htmlspecialchars($row['trainer_name']) ?></td>
                                <td class="text-end pe-4">
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill x-small px-3">Active Link</span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .x-small { font-size: 0.7rem; }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
