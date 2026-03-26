<?php
session_start();
require_once '../config.php';

// ── GUARD: Admin only ──
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php?msg=required");
    exit();
}

$conn  = get_db_connection();
$uname = htmlspecialchars($_SESSION['username']);

// ── STATS ──
$total_components = $conn->query("SELECT COUNT(*) as c FROM components")->fetch_assoc()['c'];
$total_users      = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$total_admins     = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='admin'")->fetch_assoc()['c'];
$total_builds     = $conn->query("SELECT COUNT(*) as c FROM builds")->fetch_assoc()['c'];
$low_stock        = $conn->query("SELECT COUNT(*) as c FROM components WHERE stock_quantity <= 5")->fetch_assoc()['c'];
$out_of_stock     = $conn->query("SELECT COUNT(*) as c FROM components WHERE stock_quantity = 0")->fetch_assoc()['c'];

// ── COMPONENT BREAKDOWN ──
$cat_res  = $conn->query("SELECT Type, COUNT(*) as cnt, SUM(stock_quantity) as total_stock FROM components GROUP BY Type ORDER BY Type");
$cat_data = [];
while ($r = $cat_res->fetch_assoc()) $cat_data[] = $r;

// ── RECENT 5 BUILDS ──
$recent_builds = $conn->query("SELECT b.build_id, b.build_name, b.created_at, u.username FROM builds b JOIN users u ON b.user_id=u.user_id ORDER BY b.created_at DESC LIMIT 5");

// ── LOW STOCK ITEMS ──
$low_items = $conn->query("SELECT Name, Brand, Type, stock_quantity FROM components WHERE stock_quantity <= 5 ORDER BY stock_quantity ASC LIMIT 8");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — NextGen PC Builder</title>
    <style>
        :root { --bg:#0f172a;--card:#1e293b;--accent:#3b82f6;--success:#10b981;--danger:#ef4444;--warn:#f59e0b;--muted:#94a3b8;--text:#f8fafc;--border:#334155;--sidebar:#0b1120; }
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Segoe UI',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh;}

        /* SIDEBAR */
        .sidebar{width:220px;background:var(--sidebar);border-right:1px solid var(--border);padding:1.5rem 1rem;display:flex;flex-direction:column;gap:.3rem;flex-shrink:0;}
        .sb-logo{font-size:1.1rem;font-weight:bold;color:var(--accent);margin-bottom:1.2rem;padding-bottom:1rem;border-bottom:1px solid var(--border);}
        .sb-logo span{color:var(--muted);font-size:.8rem;display:block;font-weight:normal;margin-top:.2rem;}
        .sb-link{display:flex;align-items:center;gap:.6rem;padding:.65rem .9rem;border-radius:8px;text-decoration:none;color:var(--muted);font-size:.88rem;transition:all .2s;}
        .sb-link:hover{background:var(--card);color:var(--text);}
        .sb-link.active{background:var(--accent);color:white;}
        .sb-divider{height:1px;background:var(--border);margin:.5rem 0;}
        .sb-bottom{margin-top:auto;}

        /* MAIN */
        .main{flex:1;padding:2rem;overflow-y:auto;}
        .page-title{font-size:1.6rem;font-weight:bold;margin-bottom:.3rem;}
        .page-sub{color:var(--muted);font-size:.9rem;margin-bottom:2rem;}

        /* STAT CARDS */
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:1rem;margin-bottom:2rem;}
        .stat-card{background:var(--card);border-radius:12px;padding:1.2rem;border:1px solid var(--border);transition:border-color .2s;}
        .stat-card:hover{border-color:var(--accent);}
        .stat-icon{font-size:1.6rem;margin-bottom:.6rem;}
        .stat-val{font-size:2rem;font-weight:bold;margin-bottom:.2rem;}
        .stat-lbl{font-size:.8rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;}
        .stat-card.warn .stat-val{color:var(--warn);}
        .stat-card.danger .stat-val{color:var(--danger);}
        .stat-card.success .stat-val{color:var(--success);}
        .stat-card.accent .stat-val{color:var(--accent);}

        /* TABLES */
        .section-title{font-size:1rem;font-weight:bold;margin-bottom:1rem;display:flex;align-items:center;justify-content:space-between;}
        .table-card{background:var(--card);border-radius:12px;border:1px solid var(--border);overflow:hidden;margin-bottom:2rem;}
        table{width:100%;border-collapse:collapse;}
        th{background:var(--sidebar);text-align:left;padding:.7rem 1rem;font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);}
        td{padding:.8rem 1rem;border-bottom:1px solid var(--border);font-size:.88rem;}
        tr:last-child td{border-bottom:none;}
        tr:hover td{background:rgba(255,255,255,.03);}

        /* BADGES */
        .badge{display:inline-block;padding:2px 10px;border-radius:20px;font-size:.72rem;font-weight:bold;}
        .badge-ok{background:rgba(16,185,129,.15);color:#6ee7b7;border:1px solid var(--success);}
        .badge-warn{background:rgba(245,158,11,.15);color:#fcd34d;border:1px solid var(--warn);}
        .badge-danger{background:rgba(239,68,68,.15);color:#fca5a5;border:1px solid var(--danger);}

        /* CAT GRID */
        .cat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.8rem;margin-bottom:2rem;}
        .cat-card{background:var(--card);border-radius:10px;border:1px solid var(--border);padding:1rem;}
        .cat-name{font-size:.82rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:.3rem;}
        .cat-count{font-size:1.4rem;font-weight:bold;color:var(--accent);}
        .cat-stock{font-size:.78rem;color:var(--muted);margin-top:.2rem;}

        /* QUICK ACTIONS */
        .quick-actions{display:flex;gap:.8rem;flex-wrap:wrap;margin-bottom:2rem;}
        .qa-btn{display:flex;align-items:center;gap:.5rem;padding:.8rem 1.2rem;background:var(--card);border:1px solid var(--border);border-radius:10px;text-decoration:none;color:var(--text);font-size:.88rem;font-weight:bold;transition:all .2s;}
        .qa-btn:hover{border-color:var(--accent);color:var(--accent);}

        /* TWO COL */
        .two-col{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;}
        @media(max-width:900px){.two-col{grid-template-columns:1fr;}.sidebar{display:none;}}
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sb-logo">⚡ PC Builder <span>Admin Panel</span></div>
    <a href="admin_dashboard.php" class="sb-link active">🏠 Dashboard</a>
    <a href="admin_components.php" class="sb-link">🖥️ Components</a>
    <a href="admin_add_component.php" class="sb-link">➕ Add Component</a>
    <a href="admin_users.php" class="sb-link">👥 Users</a>
    <a href="admin_orders.php" class="sb-link">📦 Orders</a>
    <div class="sb-divider"></div>
    <div class="sb-bottom">
        <a href="../index.php" class="sb-link">🌐 View Site</a>
        <a href="../logout.php" class="sb-link" style="color:var(--danger);">🚪 Sign Out</a>
    </div>
</aside>

<!-- MAIN -->
<main class="main">
    <div class="page-title">👋 Welcome back, <?= $uname ?>!</div>
    <div class="page-sub">Here's what's happening with your PC Builder store today.</div>

    <!-- STAT CARDS -->
    <div class="stats-grid">
        <div class="stat-card accent"><div class="stat-icon">🖥️</div><div class="stat-val"><?= $total_components ?></div><div class="stat-lbl">Total Components</div></div>
        <div class="stat-card accent"><div class="stat-icon">👥</div><div class="stat-val"><?= $total_users ?></div><div class="stat-lbl">Registered Users</div></div>
        <div class="stat-card success"><div class="stat-icon">💾</div><div class="stat-val"><?= $total_builds ?></div><div class="stat-lbl">Saved Builds</div></div>
        <div class="stat-card accent"><div class="stat-icon">🔑</div><div class="stat-val"><?= $total_admins ?></div><div class="stat-lbl">Admins</div></div>
        <div class="stat-card warn"><div class="stat-icon">⚠️</div><div class="stat-val"><?= $low_stock ?></div><div class="stat-lbl">Low Stock Items</div></div>
        <div class="stat-card danger"><div class="stat-icon">❌</div><div class="stat-val"><?= $out_of_stock ?></div><div class="stat-lbl">Out of Stock</div></div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="section-title">⚡ Quick Actions</div>
    <div class="quick-actions">
        <a href="admin_add_component.php" class="qa-btn">➕ Add New Component</a>
        <a href="admin_components.php" class="qa-btn">🖥️ Manage Components</a>
        <a href="admin_users.php" class="qa-btn">👥 Manage Users</a>
        <a href="../index.php" class="qa-btn">🌐 View Live Site</a>
    </div>

    <!-- COMPONENT BREAKDOWN -->
    <div class="section-title">📊 Component Inventory by Category</div>
    <div class="cat-grid">
        <?php foreach ($cat_data as $cat): ?>
        <div class="cat-card">
            <div class="cat-name"><?= htmlspecialchars($cat['Type']) ?></div>
            <div class="cat-count"><?= $cat['cnt'] ?> items</div>
            <div class="cat-stock">Total stock: <?= number_format($cat['total_stock']) ?> units</div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="two-col">
        <!-- RECENT BUILDS -->
        <div>
            <div class="section-title">
                💾 Recent Saved Builds
                <a href="admin_users.php" style="font-size:.8rem;color:var(--accent);text-decoration:none;">View all →</a>
            </div>
            <div class="table-card">
                <table>
                    <thead><tr><th>Build Name</th><th>User</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php if ($recent_builds && $recent_builds->num_rows > 0): ?>
                            <?php while ($rb = $recent_builds->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($rb['build_name']) ?></td>
                                <td style="color:var(--accent);">@<?= htmlspecialchars($rb['username']) ?></td>
                                <td style="color:var(--muted);font-size:.8rem;"><?= date('M d, Y', strtotime($rb['created_at'])) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="color:var(--muted);text-align:center;">No builds yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- LOW STOCK ALERT -->
        <div>
            <div class="section-title" style="color:var(--warn);">
                ⚠️ Low Stock Alert
                <a href="admin_components.php?filter=low" style="font-size:.8rem;color:var(--accent);text-decoration:none;">View all →</a>
            </div>
            <div class="table-card">
                <table>
                    <thead><tr><th>Component</th><th>Type</th><th>Stock</th></tr></thead>
                    <tbody>
                        <?php if ($low_items && $low_items->num_rows > 0): ?>
                            <?php while ($li = $low_items->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($li['Name']) ?></td>
                                <td style="color:var(--muted);font-size:.8rem;"><?= htmlspecialchars($li['Type']) ?></td>
                                <td>
                                    <?php if ($li['stock_quantity'] == 0): ?>
                                        <span class="badge badge-danger">Out of Stock</span>
                                    <?php else: ?>
                                        <span class="badge badge-warn"><?= $li['stock_quantity'] ?> left</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="color:var(--success);text-align:center;">✅ All items well stocked!</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

</body>
</html>