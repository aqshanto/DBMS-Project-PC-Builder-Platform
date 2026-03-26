<?php
session_start();
require_once 'config.php';
$conn = get_db_connection();

$id    = isset($_GET['id'])   ? (int)$_GET['id']    : 0;
$type  = isset($_GET['type']) ? trim($_GET['type'])  : '';
$ntype = str_replace(' ', '', strtolower($type));  // 'Power Supply' -> 'powersupply'

$component = null;
$error_msg = "";

// --- ADD TO BUILD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_build'])) {
    $_SESSION['build'][$ntype] = $id;
    header("Location: build.php");
    exit();
}

// --- BUILD SQL BASED ON TYPE (using prepared statements) ---
$join_table  = "";
$join_clause = "";

switch ($ntype) {
    case 'cpu':
        $join_table = "cpus"; break;
    case 'motherboard':
        $join_table = "motherboards"; break;
    case 'ram':
        $join_table = "rams"; break;
    case 'powersupply':
        $join_table = "powersupplies"; break;
    case 'storage':
        $join_table = "storages"; break;
    case 'gpu':
        $join_table = "gpus"; break;
    case 'case':
        $join_table = "cases"; break;
    default:
        $error_msg = "Unrecognized component type: '" . htmlspecialchars($type) . "'";
}

if ($join_table) {
    $sql = "SELECT * FROM components 
            JOIN `{$join_table}` ON components.component_id = `{$join_table}`.component_id 
            WHERE components.component_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $component = $result->fetch_assoc();
        } else {
            $error_msg = "No specs found for this component (ID #{$id}, type: {$ntype}).";
        }
        $stmt->close();
    } else {
        $error_msg = "SQL prepare error: " . $conn->error;
    }
}

// --- ALREADY IN BUILD? ---
$in_build = isset($_SESSION['build'][$ntype]) && $_SESSION['build'][$ntype] == $id;

// --- ICON MAP ---
$icons = [
    'cpu' => '🔲', 'motherboard' => '📋', 'ram' => '💾',
    'gpu' => '🎮', 'powersupply' => '⚡', 'storage' => '💿', 'case' => '📦',
];
$icon = $icons[$ntype] ?? '🖥️';

// --- COLUMNS TO HIDE FROM SPECS GRID ---
$hide = ['component_id', 'Name', 'Brand', 'Type', 'Price', 'stock_quantity', 'image_url'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $component ? htmlspecialchars($component['Name']) : 'Component Details' ?></title>
    <style>
        :root {
            --bg: #0f172a; --card: #1e293b; --text: #f8fafc;
            --muted: #94a3b8; --accent: #3b82f6; --success: #10b981; --danger: #ef4444;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); color: var(--text); padding: 2rem 1rem; }

        .container { max-width: 820px; margin: 0 auto; background: var(--card); border-radius: 14px; padding: 2rem 2.5rem; box-shadow: 0 10px 20px rgba(0,0,0,.5); border: 1px solid #334155; }
        .back-link { display: inline-block; color: var(--accent); text-decoration: none; margin-bottom: 1.5rem; font-weight: bold; }
        .back-link:hover { text-decoration: underline; }

        /* Header */
        /* Product image */
        .detail-layout { display: grid; grid-template-columns: 280px 1fr; gap: 2rem; margin-bottom: 1.5rem; align-items: start; }
        .detail-img-box {
            background: #042a50; border-radius: 12px; border: 1px solid #1a3a5c;
            display: flex; align-items: center; justify-content: center;
            padding: 1.5rem; min-height: 240px;
        }
        .detail-img-box img { max-width: 100%; max-height: 220px; object-fit: contain; }
        .detail-img-placeholder { font-size: 5rem; opacity: .35; }
        .detail-right { display: flex; flex-direction: column; }

        .comp-header { margin-bottom: .8rem; }
        .comp-icon { font-size: 3rem; line-height: 1; }
        .comp-meta { flex: 1; }
        .comp-breadcrumb { font-size: .8rem; color: var(--muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: .4rem; }
        .comp-name { font-size: 1.9rem; font-weight: bold; color: var(--text); line-height: 1.2; }
        .comp-price { font-size: 2rem; color: var(--success); font-weight: bold; margin: 1rem 0; }

        /* Stock */
        .stock-row { color: var(--muted); font-size: .9rem; margin-bottom: 1.5rem; }
        .in-stock  { color: var(--success); font-weight: bold; }
        .low-stock { color: var(--danger); font-weight: bold; }

        /* Add button */
        .btn-add {
            display: block; width: 100%; padding: 13px; text-align: center;
            background: var(--accent); color: white; border: none; border-radius: 9px;
            font-size: 1.1rem; font-weight: bold; cursor: pointer; transition: background .2s; margin-bottom: 1.5rem;
        }
        .btn-add:hover { background: #2563eb; }
        .btn-add.in-build { background: var(--success); cursor: default; }
        .btn-add.in-build:hover { background: var(--success); }

        /* Specs grid */
        .specs-title { font-size: 1.1rem; font-weight: bold; margin: 1.5rem 0 1rem; padding-top: 1.5rem; border-top: 1px solid #334155; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; }
        .specs-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .9rem; }
        .spec-item { background: #0b1120; padding: 1rem; border-radius: 9px; border: 1px solid #1e3a5f; }
        .spec-label { color: var(--muted); font-size: .78rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: .3rem; }
        .spec-value { font-size: 1.1rem; font-weight: 600; color: var(--text); }

        /* Error */
        .error-box { background: rgba(127,29,29,.4); color: #fca5a5; padding: 1.5rem; border-radius: 9px; border: 1px solid var(--danger); }

        @media (max-width: 640px) {
            .container { padding: 1.5rem 1rem; }
            .detail-layout { grid-template-columns: 1fr; }
            .specs-grid { grid-template-columns: 1fr; }
            .comp-name { font-size: 1.4rem; }
        }
    </style>
</head>
<body>
<div class="container">
    <a href="index.php" class="back-link">← Back to Catalog</a>

    <?php if ($component): ?>

        <!-- TWO COLUMN LAYOUT: image left, info right -->
        <div class="detail-layout">

            <!-- LEFT: Product Image -->
            <div class="detail-img-box">
                <?php
                $img_src = "component_img.php?type=" . urlencode($ntype)
                         . "&brand=" . urlencode($component['Brand'])
                         . "&name="  . urlencode($component['Name']);
                ?>
                <img src="<?= $img_src ?>"
                     alt="<?= htmlspecialchars($component['Name']) ?>"
                     style="max-width:100%;max-height:220px;object-fit:contain;">
            </div>

            <!-- RIGHT: Info -->
            <div class="detail-right">
                <div class="comp-header">
                    <div class="comp-breadcrumb"><?= htmlspecialchars($component['Brand']) ?> · <?= htmlspecialchars($component['Type']) ?></div>
                    <div class="comp-name"><?= htmlspecialchars($component['Name']) ?></div>
                </div>

                <div class="comp-price">৳ <?= number_format($component['Price'], 2) ?></div>

                <div class="stock-row">
                    Availability:
                    <?php if ($component['stock_quantity'] > 5): ?>
                        <span class="in-stock">✅ In Stock (<?= (int)$component['stock_quantity'] ?> units)</span>
                    <?php elseif ($component['stock_quantity'] > 0): ?>
                        <span class="low-stock">⚠️ Low Stock (only <?= (int)$component['stock_quantity'] ?> left!)</span>
                    <?php else: ?>
                        <span class="low-stock">❌ Out of Stock</span>
                    <?php endif; ?>
                </div>

                <?php if ($in_build): ?>
                    <button class="btn-add in-build" disabled>✔ Already in Your Build</button>
                <?php elseif ($component['stock_quantity'] > 0): ?>
                    <form method="POST">
                        <button type="submit" name="add_to_build" class="btn-add">+ Add to My Build</button>
                    </form>
                <?php else: ?>
                    <button class="btn-add" disabled style="background:#334155;color:#64748b;cursor:not-allowed;">❌ Out of Stock</button>
                <?php endif; ?>

                <?php if ($component['stock_quantity'] > 0): ?>
                <a href="checkout.php?mode=single&id=<?= $id ?>"
                   style="display:block;width:100%;padding:12px;text-align:center;background:var(--success);color:#000;border:none;border-radius:9px;font-size:1rem;font-weight:bold;text-decoration:none;margin-top:.6rem;transition:background .2s;"
                   onmouseover="this.style.background='#34d399'" onmouseout="this.style.background='var(--success)'">
                    🛍️ Buy Now
                </a>
                <?php else: ?>
                <div style="width:100%;padding:12px;text-align:center;background:#1e293b;color:#64748b;border-radius:9px;font-size:.9rem;margin-top:.6rem;border:1px solid #334155;">
                    ⏳ Currently unavailable — check back soon
                </div>
                <?php endif; ?>
            </div>
        </div><!-- end detail-layout -->

        <div class="specs-title">Technical Specifications</div>
        <div class="specs-grid">
            <?php foreach ($component as $key => $value): ?>
                <?php if (!in_array($key, $hide) && $value !== null && $value !== ''): ?>
                <div class="spec-item">
                    <div class="spec-label"><?= htmlspecialchars(str_replace('_', ' ', $key)) ?></div>
                    <div class="spec-value"><?= htmlspecialchars($value) ?></div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <h2 style="margin-bottom:1rem">Oops! Something went wrong.</h2>
        <div class="error-box">
            <strong>Debug Info:</strong><br><br>
            <?= $error_msg ?: "Unknown error. Please go back and try again." ?>
        </div>
        <p style="margin-top:1.5rem;color:var(--muted);font-size:.9rem;">
            Copy the error above and share it so we can fix it.
        </p>
    <?php endif; ?>

</div>
<?php $conn->close(); ?>
</body>
</html>