<?php

require_once '../../includes/header.php';

$cats = $pdo->query("SELECT * FROM complaint_categories")->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO complaints (user_id, category_id, subject, description, priority) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $_POST['category_id'], $_POST['subject'], $_POST['description'], $_POST['priority']]);
    $success = "Complaint submitted successfully!";
}
?>
<div class="row justify-content-center"><div class="col-md-8"><div class="card border-0 shadow-sm rounded-4 p-4">
<h3 class="fw-bold mb-4">Submit New Complaint</h3>
<?php if (isset($success))
    echo "<div class='alert alert-success rounded-3'>$success</div>"; ?>
<form method="POST">
<div class="mb-3"><label class="fw-bold small">Subject</label><input type="text" name="subject" class="form-control rounded-3" required></div>
<div class="row"><div class="col-md-6 mb-3"><label class="fw-bold small">Category</label><select name="category_id" class="form-select rounded-3"><?php foreach ($cats as $c)
    echo "<option value='{$c['id']}'>{$c['name']}</option>"; ?></select></div>
<div class="col-md-6 mb-3"><label class="fw-bold small">Priority</label><select name="priority" class="form-select rounded-3"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option></select></div></div>
<div class="mb-4"><label class="fw-bold small">Description</label><textarea name="description" class="form-control rounded-3" rows="5" required></textarea></div>
<button type="submit" class="btn btn-primary w-100 fw-bold py-2 rounded-3 shadow-sm">Submit Complaint</button>
</form></div></div></div>
<?php require_once '../../includes/footer.php'; ?>
