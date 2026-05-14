<aside class="app-sidebar bg-body-secondary shadow">
    <div class="sidebar-brand">
        <a href="<?= BASE_URL ?>index.php" class="brand-link">
            <img src="<?= $settings['system_logo'] ?>" alt="Logo" class="brand-image opacity-75 shadow">
            <span class="brand-text fw-light"><?= $settings['system_name'] ?></span>
        </a>
    </div>
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">

                <?php
                function buildMenu($pdo, $userRole, $currentUrl, $parentId = 0, $userPerms = [])
                {
                    // Fetch all pages for this level
                    $stmt = $pdo->prepare("SELECT * FROM sys_pages WHERE parent_id = ? ORDER BY sort_order ASC");
                    $stmt->execute([$parentId]);
                    $all_pages = $stmt->fetchAll();

                    foreach ($all_pages as $item) {
                        // Check if this item is accessible
                        $accStmt = $pdo->prepare("SELECT 1 FROM role_access WHERE role_key = ? AND page_id = ?");
                        $accStmt->execute([$userRole, $item['id']]);
                        $hasRoleAccess = $accStmt->fetchColumn();
                        $hasIndvAccess = in_array((string)$item['id'], $userPerms);
                        $isSuperAdmin = ($userRole === 'super_admin');

                        // Check children recursively (we need to see if ANY child is accessible)
                        $childStmt = $pdo->prepare("SELECT * FROM sys_pages WHERE parent_id = ?");
                        $childStmt->execute([$item['id']]);
                        $children = $childStmt->fetchAll();
                        
                        $hasAccessibleChild = false;
                        if (!empty($children)) {
                            foreach ($children as $child) {
                                // Check if child has access
                                $cAccStmt = $pdo->prepare("SELECT 1 FROM role_access WHERE role_key = ? AND page_id = ?");
                                $cAccStmt->execute([$userRole, $child['id']]);
                                if ($isSuperAdmin || $cAccStmt->fetchColumn() || in_array((string)$child['id'], $userPerms)) {
                                    $hasAccessibleChild = true;
                                    break;
                                }
                            }
                        }

                        // Skip if no access to this item AND no accessible children
                        if (!$isSuperAdmin && !$hasRoleAccess && !$hasIndvAccess && !$hasAccessibleChild) continue;

                        $hasChildren = count($children) > 0;
                        $isActive = (strpos($currentUrl, $item['page_url']) !== false && $item['page_url'] !== '#');
                        $menuOpen = $isActive ? 'menu-open' : '';
                        $activeClass = $isActive ? 'active' : '';

                        echo '<li class="nav-item ' . $menuOpen . '">';
                        echo '<a href="' . ($hasChildren ? '#' : BASE_URL . $item['page_url']) . '" class="nav-link ' . $activeClass . '">';
                        echo '<i class="nav-icon ' . $item['icon_class'] . '"></i>';
                        echo '<p>' . htmlspecialchars($item['page_name']);
                        if ($hasChildren) {
                            echo '<i class="nav-arrow bi bi-chevron-right"></i>';
                        }
                        echo '</p></a>';

                        if ($hasChildren) {
                            echo '<ul class="nav nav-treeview">';
                            buildMenu($pdo, $userRole, $currentUrl, $item['id'], $userPerms);
                            echo '</ul>';
                        }
                        echo '</li>';
                    }
                }

                // Get current relative URL for highlighting
                $cur = ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
                $cur = str_replace(['universal/', 'project/'], '', $cur);
                
                $uPerms = $_SESSION['granular_perms'] ?? [];
                buildMenu($pdo, $_SESSION['role'], $cur, 0, $uPerms);
                ?>

            </ul>
        </nav>
    </div>
</aside>