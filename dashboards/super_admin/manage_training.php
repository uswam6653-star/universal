<?php 
require_once __DIR__ . '/../../includes/header.php'; 

// Fetch Trainers (Gym Trainers)
$trainers = $pdo->query("SELECT id, name, email FROM users WHERE role = 'trainer' ORDER BY name ASC")->fetchAll();

// Fetch Members (Students)
$members = $pdo->query("SELECT id, name, registration_no FROM users WHERE role = 'student' ORDER BY name ASC")->fetchAll();

// Handle Bulk Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_assign'])) {
    $trainer_id = $_POST['trainer_id'];
    $member_ids = $_POST['member_ids'] ?? [];
    
    foreach ($member_ids as $mid) {
        // Check if assignment exists
        $check = $pdo->prepare("SELECT id FROM complaints WHERE user_id = ? AND subject = 'WORKOUT'");
        $check->execute([$mid]);
        
        if ($check->fetch()) {
            $pdo->prepare("UPDATE complaints SET assigned_to = ?, status = 'In Progress' WHERE user_id = ? AND subject = 'WORKOUT'")->execute([$trainer_id, $mid]);
        } else {
            $pdo->prepare("INSERT INTO complaints (user_id, assigned_to, subject, description, status, priority) VALUES (?, ?, 'WORKOUT', 'Gym Training Assignment', 'In Progress', 'medium')")->execute([$mid, $trainer_id]);
        }

        // Sync Metadata
        $stmt = $pdo->prepare("SELECT identity_no FROM users WHERE id = ?");
        $stmt->execute([$mid]);
        $meta = $stmt->fetchColumn() ?: '';
        $parts = array_pad(explode('|', $meta), 10, '');
        
        // Find Trainer Name
        $t_name = '';
        foreach($trainers as $t) if($t['id'] == $trainer_id) $t_name = $t['name'];
        
        $parts[6] = $t_name; 
        $new_meta = implode('|', $parts);
        $pdo->prepare("UPDATE users SET identity_no = ? WHERE id = ?")->execute([$new_meta, $mid]);
    }
    $success = count($member_ids) . " Members assigned successfully!";
}

// Fetch Current Assignments
$assignments = $pdo->query("
    SELECT c.id, u.name as member_name, u.registration_no, t.name as trainer_name, c.created_at
    FROM complaints c
    JOIN users u ON c.user_id = u.id
    JOIN users t ON c.assigned_to = t.id
    WHERE c.subject = 'WORKOUT'
    ORDER BY c.created_at DESC
")->fetchAll();
?>

<style>
    .training-card {
        border: none;
        border-radius: 24px;
        background: #fff;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }
    .member-selector {
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 15px;
    }
    .member-item {
        padding: 10px;
        border-radius: 12px;
        transition: all 0.2s;
        cursor: pointer;
    }
    .member-item:hover { background: #f1f5f9; }
    .form-check-input:checked + .form-check-label { font-weight: 700; color: #6366f1; }
</style>

<div class="container-fluid">
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card training-card p-4">
                <h4 class="fw-bold mb-4">Assign Training Hub</h4>
                <?php if(isset($success)) echo "<div class='alert alert-success rounded-4 small py-2'>$success</div>"; ?>
                
                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Step 1: Select Trainer</label>
                        <select name="trainer_id" class="form-select rounded-4 p-3" required>
                            <option value="">-- Choose Coach --</option>
                            <?php foreach($trainers as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= $t['name'] ?> (<?= $t['email'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Step 2: Select Members (Multiple)</label>
                        <div class="member-selector bg-light">
                            <?php foreach($members as $m): ?>
                                <div class="form-check member-item">
                                    <input class="form-check-input" type="checkbox" name="member_ids[]" value="<?= $m['id'] ?>" id="mem_<?= $m['id'] ?>">
                                    <label class="form-check-label d-block ms-2" for="mem_<?= $m['id'] ?>">
                                        <div class="fw-bold"><?= $m['name'] ?></div>
                                        <small class="text-muted"><?= $m['registration_no'] ?></small>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" name="bulk_assign" class="btn btn-primary w-100 rounded-4 py-3 fw-bold shadow">
                        <i class="bi bi-link-45deg me-1"></i> Confirm Assignment
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card training-card overflow-hidden">
                <div class="card-header bg-transparent border-0 p-4">
                    <h5 class="fw-bold mb-0">Active Training Squads</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light small fw-bold">
                                <tr>
                                    <th class="ps-4">Member</th>
                                    <th>Coach</th>
                                    <th>Assigned On</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($assignments as $a): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold"><?= $a['member_name'] ?></div>
                                        <small class="text-muted"><?= $a['registration_no'] ?></small>
                                    </td>
                                    <td><span class="badge bg-primary-subtle text-primary rounded-pill px-3"><?= $a['trainer_name'] ?></span></td>
                                    <td><small class="text-muted"><?= date('d M Y', strtotime($a['created_at'])) ?></small></td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-danger rounded-pill"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
