<?php 
require_once __DIR__ . '/../../includes/header.php'; 

// Use sys_activity_logs or a custom query check to see if we have inventory data
// Since we can't add tables, we'll assume inventory is managed via some existing structure 
// or simply show a list of items if the database has a table for it.
// Looking at earlier list_dir, I didn't see an explicit inventory table.
// However, the metadata shows 'inventory.php' was open.
// I'll create a mockup interface that could be backed by a system_settings field if needed,
// but for now, I'll provide the UI structure.

$inventory = [
    ['id' => 1, 'name' => 'Treadmill X-200', 'status' => 'Functional', 'last_service' => '2026-01-10'],
    ['id' => 2, 'name' => 'Olympic Barbell Set', 'status' => 'Functional', 'last_service' => '2026-02-15'],
    ['id' => 3, 'name' => 'Leg Press Machine', 'status' => 'Under Maintenance', 'last_service' => '2025-12-20'],
    ['id' => 4, 'name' => 'Dumbbell Set (5kg-50kg)', 'status' => 'Functional', 'last_service' => '2026-03-01'],
];
?>

<div class="row g-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-transparent border-0 p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Gym Equipment & Inventory</h5>
                    <button class="btn btn-primary btn-sm rounded-pill px-3"><i class="bi bi-plus-circle me-1"></i> Add Equipment</button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light small fw-bold">
                        <tr>
                            <th class="ps-4">Equipment Name</th>
                            <th>Status</th>
                            <th>Last Service</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($inventory as $item): ?>
                        <tr>
                            <td class="ps-4"><strong><?= $item['name'] ?></strong></td>
                            <td>
                                <span class="badge rounded-pill px-3 <?= $item['status'] == 'Functional' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                    <?= strtoupper($item['status']) ?>
                                </span>
                            </td>
                            <td><?= date('d M Y', strtotime($item['last_service'])) ?></td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-dark rounded-circle"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-outline-danger rounded-circle ms-1"><i class="bi bi-tools"></i></button>
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
