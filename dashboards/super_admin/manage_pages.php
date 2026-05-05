<?php 
require_once '../../core/db.php'; 
require_once '../../core/session.php';

// --- Handle CRUD Operations ---

// 1. Create / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_menu'])) {
    $name = trim($_POST['menu_name']);
    $url = trim($_POST['menu_url']);
    $icon = trim($_POST['icon_class']);
    $order = (int)$_POST['sort_order'];
    $parent = (int)$_POST['parent_id'];

    if (!empty($_POST['edit_id'])) {
        // Update
        $stmt = $pdo->prepare("UPDATE sys_pages SET page_name=?, page_url=?, icon_class=?, parent_id=?, sort_order=? WHERE id=?");
        $stmt->execute([$name, $url, $icon, $parent, $order, $_POST['edit_id']]);
        $success_msg = "Page updated successfully! ✨";
    } else {
        // Create
        $stmt = $pdo->prepare("INSERT INTO sys_pages (page_name, page_url, icon_class, parent_id, sort_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $url, $icon, $parent, $order]);
        $new_id = $pdo->lastInsertId();
        
        // Auto-grant access to super_admin
        $pdo->prepare("INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('super_admin', ?)")->execute([$new_id]);
        
        $success_msg = "New page added! Admin access granted automatically. ✅";
    }
}

// 2. Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Delete roles access first
    $pdo->prepare("DELETE FROM role_access WHERE page_id = ?")->execute([$id]);
    // Delete the page
    $pdo->prepare("DELETE FROM sys_pages WHERE id = ?")->execute([$id]);
    header("Location: manage_pages.php?msg=Menu item removed");
    exit;
}

require_once __DIR__ . '/../../includes/header.php'; 

// Fetch All Pages
$all_pages = $pdo->query("SELECT * FROM sys_pages ORDER BY parent_id ASC, sort_order ASC")->fetchAll();

// Group for Table Display
$parent_pages = array_filter($all_pages, fn($p) => $p['parent_id'] == 0);

?>

<div class="row g-4">
    <div class="col-12">
        <?php if(isset($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 rounded-4 shadow-sm">
                <i class="bi bi-check-circle-fill me-2"></i><?= $success_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-info alert-dismissible fade show border-0 rounded-4 shadow-sm">
                <i class="bi bi-info-circle-fill me-2"></i><?= htmlspecialchars($_GET['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Add / Edit Page Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-transparent border-0 p-4 pb-0">
                <h5 class="fw-bold mb-0" id="formTitle">Add New Sidebar Link</h5>
                <p class="text-muted small mb-0">Add a new page or section to your gym sidebar.</p>
            </div>
            <div class="card-body p-4">
                <form method="POST" id="menuForm" class="row g-3">
                    <input type="hidden" name="edit_id" id="edit_id">
                    
                    <div class="col-md-3">
                        <label class="small fw-bold mb-1">Display Name</label>
                        <input type="text" name="menu_name" id="f_name" class="form-control rounded-3 border-primary-subtle" placeholder="e.g. My Reports" required title="This name will appear in the sidebar">
                    </div>

                    <div class="col-md-3">
                        <label class="small fw-bold mb-1">URL Path (Filename)</label>
                        <input type="text" name="menu_url" id="f_url" class="form-control rounded-3 border-primary-subtle" placeholder="dashboards/super_admin/..." required title="MUST match the actual PHP filename on your system">
                        <div class="form-text x-small text-danger" style="font-size: 0.70rem;">Use <code>#</code> for a category dropdown.</div>
                    </div>

                    <div class="col-md-2">
                        <label class="small fw-bold mb-1">Icon <a href="https://icons.getbootstrap.com/" target="_blank" class="small text-decoration-none ms-1"><i class="bi bi-box-arrow-up-right"></i></a></label>
                        <select name="icon_class" id="f_icon" class="form-select rounded-3 border-primary-subtle">
                            <option value="bi bi-circle">Default Circle</option>
                            <option value="bi bi-speedometer2">Dashboard</option>
                            <option value="bi bi-people-fill">Gym Members</option>
                            <option value="bi bi-cash-coin">Payments</option>
                            <option value="bi bi-calendar-check">Attendance</option>
                            <option value="bi bi-person-badge">Staff Records</option>
                            <option value="bi bi-graph-up">Analytics</option>
                            <option value="bi bi-gear-fill">System Settings</option>
                            <option value="bi bi-alarm-fill">Alerts</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="small fw-bold mb-1">Parent Category</label>
                        <select name="parent_id" id="f_parent" class="form-select rounded-3 border-primary-subtle">
                            <option value="0">Main Menu</option>
                            <?php foreach($parent_pages as $pp): ?>
                                <option value="<?= $pp['id'] ?>"><?= htmlspecialchars($pp['page_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-1">
                        <label class="small fw-bold mb-1">Rank</label>
                        <input type="number" name="sort_order" id="f_order" class="form-control rounded-3 border-primary-subtle" value="0" title="Order in sidebar (1, 2, 3...)">
                    </div>

                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" name="save_menu" class="btn btn-primary w-100 rounded-3 fw-bold">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                    
                    <div class="col-12" id="resetBtn" style="display:none;">
                        <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="resetForm()">
                            <i class="bi bi-x-circle me-1"></i> Cancel Editing
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Menu List Card -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 p-4">
                <h5 class="fw-bold mb-0">Sidebar Navigation List</h5>
                <p class="text-muted small mb-0">Manage all links appearing in the left-side navigation menu.</p>
            </div>
            <div class="card-body p-4 pt-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light small fw-bold text-uppercase">
                            <tr>
                                <th class="ps-4">Order & Icon</th>
                                <th>Menu Name</th>
                                <th>URL Path</th>
                                <th>Category</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($all_pages as $p): 
                                $isParent = ($p['parent_id'] == 0);
                            ?>
                            <tr class="<?= $isParent ? 'fw-bold table-light' : '' ?>">
                                <td class="ps-4">
                                    <span class="badge bg-secondary me-2"><?= $p['sort_order'] ?></span>
                                    <i class="<?= $p['icon_class'] ?> fs-5"></i>
                                </td>
                                <td><?= htmlspecialchars($p['page_name']) ?></td>
                                <td><code><?= htmlspecialchars($p['page_url']) ?></code></td>
                                <td>
                                    <?php 
                                    if($isParent) echo '<span class="badge bg-primary">Main Category</span>';
                                    else {
                                        foreach($parent_pages as $pp) {
                                            if($pp['id'] == $p['parent_id']) {
                                                echo '<small class="text-muted">Under: </small>' . htmlspecialchars($pp['page_name']);
                                                break;
                                            }
                                        }
                                    }
                                    ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group shadow-sm rounded-3 overflow-hidden">
                                        <button class="btn btn-sm btn-white text-primary border-end" onclick="editMenu(<?= htmlspecialchars(json_encode($p)) ?>)" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-white text-danger" onclick="return confirm('Dhyan se! Kya aap waqai is page link ko mitana chahte hain?')" title="Delete">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    </div>
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

<script>
function editMenu(data) {
    document.getElementById('formTitle').innerText = "Edit Menu Item: " + data.page_name;
    document.getElementById('edit_id').value = data.id;
    document.getElementById('f_name').value = data.page_name;
    document.getElementById('f_url').value = data.page_url;
    document.getElementById('f_icon').value = data.icon_class;
    document.getElementById('f_parent').value = data.parent_id;
    document.getElementById('f_order').value = data.sort_order;
    document.getElementById('resetBtn').style.display = "block";
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('menuForm').reset();
    document.getElementById('edit_id').value = "";
    document.getElementById('formTitle').innerText = "Add New Sidebar Menu";
    document.getElementById('resetBtn').style.display = "none";
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>