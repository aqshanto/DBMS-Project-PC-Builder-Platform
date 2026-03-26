<?php
session_start();
require_once '../config.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { header("Location: ../login.php"); exit(); }

$conn    = get_db_connection();
$my_id   = (int)$_SESSION['user_id'];
$msg     = ""; $msg_type = "";

// ── CHANGE ROLE ──
if (isset($_GET['promote'])) {
    $uid = (int)$_GET['promote'];
    if ($uid !== $my_id) {
        $conn->query("UPDATE users SET role='admin' WHERE user_id=$uid");
        header("Location: admin_users.php?msg=promoted"); exit();
    }
}
if (isset($_GET['demote'])) {
    $uid = (int)$_GET['demote'];
    if ($uid !== $my_id) {
        $conn->query("UPDATE users SET role='user' WHERE user_id=$uid");
        header("Location: admin_users.php?msg=demoted"); exit();
    }
}

// ── DELETE USER ──
if (isset($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    if ($uid !== $my_id) {
        // Delete their builds first
        $bids = $conn->query("SELECT build_id FROM builds WHERE user_id=$uid");
        if ($bids) while ($b = $bids->fetch_assoc()) {
            $conn->query("DELETE FROM build_components WHERE build_id=" . $b['build_id']);
        }
        $conn->query("DELETE FROM builds WHERE user_id=$uid");
        $conn->query("DELETE FROM users WHERE user_id=$uid");
        header("Location: admin_users.php?msg=deleted"); exit();
    }
}

if (isset($_GET['msg'])) {
    $msgs = ['promoted'=>['✅ User promoted to Admin.','success'],'demoted'=>['⬇️ User role changed to User.','warn'],'deleted'=>['🗑️ User deleted.','warn']];
    if (isset($msgs[$_GET['msg']])) { $msg = $msgs[$_GET['msg']][0]; $msg_type = $msgs[$_GET['msg']][1]; }
}

// ── FETCH USERS WITH BUILD COUNT ──
$users = $conn->query("
    SELECT u.user_id, u.username, u.user_mail, u.role,
           COUNT(b.build_id) as build_count
    FROM users u
    LEFT JOIN builds b ON u.user_id = b.user_id
    GROUP BY u.user_id
    ORDER BY u.role DESC, u.user_id ASC
");
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users — Admin</title>
    <style>
        :root{--bg:#0f172a;--card:#1e293b;--accent:#3b82f6;--success:#10b981;--danger:#ef4444;--warn:#f59e0b;--muted:#94a3b8;--text:#f8fafc;--border:#334155;--sidebar:#0b1120;}
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Segoe UI',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh;}
        .sidebar{width:220px;background:var(--sidebar);border-right:1px solid var(--border);padding:1.5rem 1rem;display:flex;flex-direction:column;gap:.3rem;flex-shrink:0;}
        .sb-logo{font-size:1.1rem;font-weight:bold;color:var(--accent);margin-bottom:1.2rem;padding-bottom:1rem;border-bottom:1px solid var(--border);}
        .sb-logo span{color:var(--muted);font-size:.8rem;display:block;font-weight:normal;}
        .sb-link{display:flex;align-items:center;gap:.6rem;padding:.65rem .9rem;border-radius:8px;text-decoration:none;color:var(--muted);font-size:.88rem;transition:all .2s;}
        .sb-link:hover{background:var(--card);color:var(--text);}
        .sb-link.active{background:var(--accent);color:white;}
        .sb-divider{height:1px;background:var(--border);margin:.5rem 0;}
        .sb-bottom{margin-top:auto;}
        .main{flex:1;padding:2rem;overflow-y:auto;}
        .page-title{font-size:1.6rem;font-weight:bold;margin-bottom:.3rem;}
        .page-sub{color:var(--muted);font-size:.9rem;margin-bottom:1.5rem;}
        .alert{padding:.9rem 1.1rem;border-radius:8px;margin-bottom:1.2rem;font-size:.9rem;}
        .alert.success{background:rgba(16,185,129,.12);border:1px solid var(--success);color:#6ee7b7;}
        .alert.warn{background:rgba(245,158,11,.1);border:1px solid var(--warn);color:#fcd34d;}
        .table-wrap{background:var(--card);border-radius:12px;border:1px solid var(--border);overflow:hidden;}
        table{width:100%;border-collapse:collapse;}
        th{background:var(--sidebar);text-align:left;padding:.65rem 1rem;font-size:.73rem;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);}
        td{padding:.9rem 1rem;border-bottom:1px solid var(--border);font-size:.88rem;vertical-align:middle;}
        tr:last-child td{border-bottom:none;}
        tr:hover td{background:rgba(255,255,255,.02);}
        .badge{display:inline-block;padding:3px 12px;border-radius:20px;font-size:.75rem;font-weight:bold;}
        .badge-admin{background:rgba(59,130,246,.2);color:#93c5fd;border:1px solid var(--accent);}
        .badge-user{background:rgba(148,163,184,.15);color:var(--muted);border:1px solid var(--border);}
        .badge-you{background:rgba(16,185,129,.15);color:#6ee7b7;border:1px solid var(--success);margin-left:.4rem;}
        .btn{padding:5px 12px;border-radius:6px;border:none;font-size:.78rem;font-weight:bold;cursor:pointer;text-decoration:none;transition:all .2s;display:inline-block;}
        .btn-promote{background:rgba(59,130,246,.15);color:#93c5fd;border:1px solid var(--accent);}
        .btn-promote:hover{background:var(--accent);color:white;}
        .btn-demote{background:rgba(245,158,11,.15);color:#fcd34d;border:1px solid var(--warn);}
        .btn-demote:hover{background:var(--warn);color:#000;}
        .btn-delete{background:rgba(239,68,68,.12);color:#fca5a5;border:1px solid var(--danger);}
        .btn-delete:hover{background:var(--danger);color:white;}
        .avatar{width:34px;height:34px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:.9rem;color:white;flex-shrink:0;}
        .user-cell{display:flex;align-items:center;gap:.8rem;}
        .user-info{min-width:0;}
        .user-name{font-weight:bold;font-size:.9rem;}
        .user-email{font-size:.78rem;color:var(--muted);}
        @media(max-width:900px){.sidebar{display:none;}}
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sb-logo">⚡ PC Builder <span>Admin Panel</span></div>
    <a href="admin_dashboard.php" class="sb-link">🏠 Dashboard</a>
    <a href="admin_components.php" class="sb-link">🖥️ Components</a>
    <a href="admin_add_component.php" class="sb-link">➕ Add Component</a>
    <a href="admin_users.php" class="sb-link active">👥 Users</a>
    <div class="sb-divider"></div>
    <div class="sb-bottom">
        <a href="../index.php" class="sb-link">🌐 View Site</a>
        <a href="../logout.php" class="sb-link" style="color:var(--danger);">🚪 Sign Out</a>
    </div>
</aside>

<main class="main">
    <div class="page-title">👥 Manage Users</div>
    <div class="page-sub">View all registered users, manage roles and permissions.</div>

    <?php if ($msg): ?><div class="alert <?= $msg_type ?>"><?= $msg ?></div><?php endif; ?>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Saved Builds</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($users && $users->num_rows > 0): ?>
                <?php while ($u = $users->fetch_assoc()): ?>
                <?php $is_me = $u['user_id'] == $my_id; ?>
                <tr>
                    <td>
                        <div class="user-cell">
                            <div class="avatar"><?= strtoupper(substr($u['username'], 0, 2)) ?></div>
                            <div class="user-info">
                                <div class="user-name">
                                    <?= htmlspecialchars($u['username']) ?>
                                    <?php if ($is_me): ?><span class="badge badge-you">You</span><?php endif; ?>
                                </div>
                                <div class="user-email"><?= htmlspecialchars($u['user_mail']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if ($u['role'] === 'admin'): ?>
                            <span class="badge badge-admin">🔑 Admin</span>
                        <?php else: ?>
                            <span class="badge badge-user">👤 User</span>
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--accent);font-weight:bold;"><?= $u['build_count'] ?> build<?= $u['build_count'] != 1 ? 's' : '' ?></td>
                    <td>
                        <?php if (!$is_me): ?>
                        <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
                            <?php if ($u['role'] !== 'admin'): ?>
                                <a href="admin_users.php?promote=<?= $u['user_id'] ?>"
                                   onclick="return confirm('Promote <?= htmlspecialchars($u['username']) ?> to Admin?')"
                                   class="btn btn-promote">⬆️ Make Admin</a>
                            <?php else: ?>
                                <a href="admin_users.php?demote=<?= $u['user_id'] ?>"
                                   onclick="return confirm('Demote <?= htmlspecialchars($u['username']) ?> to User?')"
                                   class="btn btn-demote">⬇️ Make User</a>
                            <?php endif; ?>
                            <a href="admin_users.php?delete=<?= $u['user_id'] ?>"
                               onclick="return confirm('Delete user <?= htmlspecialchars($u['username']) ?>? All their builds will also be deleted!')"
                               class="btn btn-delete">🗑️ Delete</a>
                        </div>
                        <?php else: ?>
                            <span style="color:var(--muted);font-size:.8rem;">— That's you</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align:center;color:var(--muted);padding:2rem;">No users found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>
