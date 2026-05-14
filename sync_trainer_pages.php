<?php
require_once 'core/db.php';

$pages = [
    ['id' => 34, 'parent_id' => 902, 'name' => 'My Members', 'url' => 'dashboards/trainer/manage_members.php', 'icon' => 'bi-people', 'sort' => 1],
    ['id' => 35, 'parent_id' => 902, 'name' => 'Workout Plans', 'url' => 'dashboards/hod/workout_plans.php', 'icon' => 'bi-clipboard-pulse', 'sort' => 2],
    ['id' => 36, 'parent_id' => 902, 'name' => 'Member Progress', 'url' => 'dashboards/hod/member_progress.php', 'icon' => 'bi-activity', 'sort' => 3],
    ['id' => 40, 'parent_id' => 902, 'name' => 'Attendance', 'url' => 'dashboards/trainer/attendance.php', 'icon' => 'bi-calendar-check', 'sort' => 4],
    ['id' => 41, 'parent_id' => 902, 'name' => 'Training Hub', 'url' => 'dashboards/trainer/training_hub.php', 'icon' => 'bi-play-btn', 'sort' => 5],
    ['id' => 42, 'parent_id' => 902, 'name' => 'Gym Staff', 'url' => 'dashboards/trainer/staff_list.php', 'icon' => 'bi-person-badge', 'sort' => 6],
    ['id' => 43, 'parent_id' => 902, 'name' => 'Assign Trainers', 'url' => 'dashboards/trainer/assign_trainers.php', 'icon' => 'bi-person-plus', 'sort' => 7],
];

foreach ($pages as $p) {
    $stmt = $pdo->prepare("REPLACE INTO sys_pages (id, parent_id, name, url, icon_class, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$p['id'], $p['parent_id'], $p['name'], $p['url'], $p['icon'], $p['sort']]);
    
    // Ensure trainer role has access
    $pdo->prepare("REPLACE INTO role_access (role_id, page_id) SELECT id, ? FROM sys_roles WHERE role_name IN ('trainer', 'hod', 'super_admin')")->execute([$p['id']]);
}

echo "Trainer Portal pages updated in sys_pages.\n";
?>
