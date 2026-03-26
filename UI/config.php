<!-- define('DB_HOST', 'sql103.infinityfree.com');
define('DB_USER', 'if0_41395736');
define('DB_PASS', 'DBMSSpring2026');
define('DB_NAME', 'if0_41395736_pc_builder'); -->

<?php
// ============================================================
//  PC BUILDER — DATABASE CONFIGURATION
//  ✅ Change ONLY this file when moving to a new server
// ============================================================

// --- LOCAL (XAMPP) SETTINGS ---
// Use these while developing on your computer
// define('DB_HOST', 'localhost');
// define('DB_USER', 'root');
// define('DB_PASS', '');
// define('DB_NAME', 'pc_builder');

define('DB_HOST', 'sql103.infinityfree.com');
define('DB_USER', 'if0_41395736');
define('DB_PASS', 'DBMSLabSpring2026');
define('DB_NAME', 'if0_41395736_pc_builder');

// --- INFINITYFREE SETTINGS (fill in after signup) ---
// When you go live, COMMENT OUT the 4 lines above
// and UNCOMMENT the 4 lines below, then paste your values:

// define('DB_HOST', 'sql200.infinityfree.com');   // ← from InfinityFree dashboard
// define('DB_USER', 'if0_xxxxxxxx');               // ← your DB username
// define('DB_PASS', 'your_password_here');          // ← your DB password
// define('DB_NAME', 'if0_xxxxxxxx_pc_builder');     // ← your DB name

// ============================================================
//  DO NOT EDIT BELOW THIS LINE
// ============================================================
function get_db_connection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("<div style='font-family:sans-serif;background:#7f1d1d;color:#fca5a5;padding:1.5rem;border-radius:8px;margin:2rem;'>
                <strong>❌ Database Connection Failed</strong><br><br>" 
                . htmlspecialchars($conn->connect_error) . 
            "</div>");
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}
?>