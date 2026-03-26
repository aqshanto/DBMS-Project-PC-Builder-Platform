<?php
session_start();
require_once '../config.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { header("Location: ../login.php"); exit(); }

$conn = get_db_connection();
$msg  = ""; $msg_type = ""; $errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_component'])) {
    // ── COMMON FIELDS ──
    $name   = trim($_POST['name']   ?? '');
    $brand  = trim($_POST['brand']  ?? '');
    $type   = trim($_POST['type']   ?? '');
    $price  = (float)($_POST['price'] ?? 0);
    $stock  = (int)($_POST['stock']   ?? 0);
    $ntype  = strtolower(str_replace(' ', '', $type));

    if (empty($name))  $errors[] = "Name is required.";
    if (empty($brand)) $errors[] = "Brand is required.";
    if (empty($type))  $errors[] = "Type is required.";
    if ($price <= 0)   $errors[] = "Price must be greater than 0.";

    if (empty($errors)) {
        // Insert into components table
        $stmt = $conn->prepare("INSERT INTO components (Name, Brand, Type, Price, stock_quantity) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdi", $name, $brand, $type, $price, $stock);
        $stmt->execute();
        $new_id = $conn->insert_id;
        $stmt->close();

        // ── INSERT INTO SPEC TABLE ──
        switch ($ntype) {
            case 'cpu':
                $socket = trim($_POST['socket'] ?? '');
                $cores  = (int)($_POST['cores'] ?? 0);
                $clock  = (float)($_POST['clock'] ?? 0);
                $tdp    = (float)($_POST['tdp'] ?? 0);
                $pm     = (int)($_POST['passmark'] ?? 0);
                $s = $conn->prepare("INSERT INTO cpus (component_id,Socket,Cores,Clock_Speed,tdp_watt,passmark_score) VALUES (?,?,?,?,?,?)");
                $s->bind_param("isiidi",$new_id,$socket,$cores,$clock,$tdp,$pm);
                $s->execute(); $s->close();
                break;
            case 'motherboard':
                $socket  = trim($_POST['socket'] ?? '');
                $ff      = $_POST['form_factor'] ?? 'ATX';
                $ram_t   = $_POST['ram_type'] ?? 'DDR4';
                $m2      = (int)($_POST['m2_slots'] ?? 0);
                $ram_sl  = (int)($_POST['ram_slots'] ?? 4);
                $s = $conn->prepare("INSERT INTO motherboards (component_id,Socket,Form_Factor,Max_Ram_Capacity,Max_Ram_Slots,supported_ram_type,m2_slots) VALUES (?,?,?,?,?,?,?)");
                $s->bind_param("issiisi",$new_id,$socket,$ff,128,$ram_sl,$ram_t,$m2);
                $s->execute(); $s->close();
                break;
            case 'ram':
                $cap  = (int)($_POST['capacity'] ?? 0);
                $ddr  = $_POST['ddr_version'] ?? 'DDR4';
                $spd  = (int)($_POST['speed_mhz'] ?? 0);
                $s = $conn->prepare("INSERT INTO rams (component_id,Capacity_GB,DDR_Version,Speed_MHz) VALUES (?,?,?,?)");
                $s->bind_param("iisi",$new_id,$cap,$ddr,$spd);
                $s->execute(); $s->close();
                break;
            case 'gpu':
                $vram  = (int)($_POST['vram'] ?? 0);
                $tdp   = (int)($_POST['tdp'] ?? 0);
                $len   = (int)($_POST['gpu_length'] ?? 0);
                $mtype = trim($_POST['mem_type'] ?? 'GDDR6');
                $perf  = (int)($_POST['perf_score'] ?? 0);
                $s = $conn->prepare("INSERT INTO gpus (component_id,VRAM_GB,TDP_Watt,GPU_Length_mm,Memory_Type,perf_score) VALUES (?,?,?,?,?,?)");
                $s->bind_param("iiisii",$new_id,$vram,$tdp,$len,$mtype,$perf);
                $s->execute(); $s->close();
                break;
            case 'powersupply':
                $watt  = (int)($_POST['wattage'] ?? 0);
                $eff   = $_POST['efficiency'] ?? 'Bronze';
                $mod   = $_POST['modularity'] ?? 'Non';
                $s = $conn->prepare("INSERT INTO powersupplies (component_id,Wattage,Efficiency_Rating,Modularity) VALUES (?,?,?,?)");
                $s->bind_param("iiss",$new_id,$watt,$eff,$mod);
                $s->execute(); $s->close();
                break;
            case 'storage':
                $cap   = (int)($_POST['capacity'] ?? 0);
                $stype = trim($_POST['storage_type'] ?? 'NVMe SSD');
                $iface = trim($_POST['interface'] ?? 'PCIe 4.0 x4');
                $read  = (int)($_POST['read_speed'] ?? 0);
                $write = (int)($_POST['write_speed'] ?? 0);
                $s = $conn->prepare("INSERT INTO storages (component_id,Capacity_GB,Storage_Type,Interface,Read_Speed_MBps,Write_Speed_MBps) VALUES (?,?,?,?,?,?)");
                $s->bind_param("iissii",$new_id,$cap,$stype,$iface,$read,$write);
                $s->execute(); $s->close();
                break;
            case 'case':
                $ff    = $_POST['case_ff'] ?? 'ATX';
                $color = trim($_POST['color'] ?? 'Black');
                $gpu_l = (int)($_POST['max_gpu'] ?? 360);
                $s = $conn->prepare("INSERT INTO cases (component_id,Form_Factor,Color,Max_GPU_Length) VALUES (?,?,?,?)");
                $s->bind_param("issi",$new_id,$ff,$color,$gpu_l);
                $s->execute(); $s->close();
                break;
        }

        header("Location: admin_components.php?msg=added");
        exit();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Component — Admin</title>
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

        /* FORM */
        .form-card{background:var(--card);border-radius:14px;border:1px solid var(--border);padding:2rem;max-width:700px;}
        .form-section{margin-bottom:1.5rem;padding-bottom:1.5rem;border-bottom:1px solid var(--border);}
        .form-section:last-child{border-bottom:none;margin-bottom:0;padding-bottom:0;}
        .section-heading{font-size:.8rem;font-weight:bold;text-transform:uppercase;letter-spacing:1px;color:var(--accent);margin-bottom:1rem;}
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;}
        .form-group{display:flex;flex-direction:column;gap:.4rem;}
        .form-group.full{grid-column:1/-1;}
        label{font-size:.82rem;color:var(--muted);font-weight:bold;}
        input,select{padding:9px 12px;background:var(--sidebar);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:.9rem;outline:none;width:100%;}
        input:focus,select:focus{border-color:var(--accent);}
        .btn-submit{width:100%;padding:12px;background:var(--accent);color:white;border:none;border-radius:9px;font-size:1rem;font-weight:bold;cursor:pointer;margin-top:1.5rem;transition:background .2s;}
        .btn-submit:hover{background:#2563eb;}
        .errors{background:rgba(239,68,68,.1);border:1px solid var(--danger);border-radius:8px;padding:1rem;margin-bottom:1.2rem;}
        .errors p{color:#fca5a5;font-size:.88rem;margin:.2rem 0;}
        .errors p::before{content:"• ";}

        /* SPEC PANELS */
        .spec-panel{display:none;}
        .spec-panel.active{display:block;}

        @media(max-width:900px){.sidebar{display:none;}.form-grid{grid-template-columns:1fr;}}
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sb-logo">⚡ PC Builder <span>Admin Panel</span></div>
    <a href="admin_dashboard.php" class="sb-link">🏠 Dashboard</a>
    <a href="admin_components.php" class="sb-link">🖥️ Components</a>
    <a href="admin_add_component.php" class="sb-link active">➕ Add Component</a>
    <a href="admin_users.php" class="sb-link">👥 Users</a>
    <div class="sb-divider"></div>
    <div class="sb-bottom">
        <a href="../index.php" class="sb-link">🌐 View Site</a>
        <a href="../logout.php" class="sb-link" style="color:var(--danger);">🚪 Sign Out</a>
    </div>
</aside>

<main class="main">
    <div class="page-title">➕ Add New Component</div>
    <div class="page-sub">Fill in the details below. Spec fields change based on component type.</div>

    <?php if (!empty($errors)): ?>
    <div class="errors"><?php foreach($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?></div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST">

            <!-- COMMON FIELDS -->
            <div class="form-section">
                <div class="section-heading">📦 Basic Information</div>
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Component Name *</label>
                        <input type="text" name="name" placeholder="e.g. Core i5-14600K" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Brand *</label>
                        <input type="text" name="brand" placeholder="e.g. Intel, AMD, ASUS" required value="<?= htmlspecialchars($_POST['brand'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Type *</label>
                        <select name="type" id="typeSelect" onchange="showSpecs(this.value)" required>
                            <option value="">— Select Type —</option>
                            <option value="CPU">CPU</option>
                            <option value="Motherboard">Motherboard</option>
                            <option value="RAM">RAM</option>
                            <option value="GPU">GPU</option>
                            <option value="Power Supply">Power Supply</option>
                            <option value="Storage">Storage</option>
                            <option value="Case">Case</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Price (৳) *</label>
                        <input type="number" name="price" placeholder="e.g. 35000" step="0.01" min="0" required value="<?= $_POST['price'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Stock Quantity</label>
                        <input type="number" name="stock" placeholder="e.g. 20" min="0" value="<?= $_POST['stock'] ?? '0' ?>">
                    </div>
                </div>
            </div>

            <!-- CPU SPECS -->
            <div class="form-section spec-panel" id="spec-cpu">
                <div class="section-heading">🔲 CPU Specifications</div>
                <div class="form-grid">
                    <div class="form-group"><label>Socket</label><input type="text" name="socket" placeholder="e.g. LGA1700, AM5"></div>
                    <div class="form-group"><label>Cores</label><input type="number" name="cores" placeholder="e.g. 14" min="1"></div>
                    <div class="form-group"><label>Clock Speed (GHz)</label><input type="number" name="clock" placeholder="e.g. 3.5" step="0.1" min="0"></div>
                    <div class="form-group"><label>TDP (Watts)</label><input type="number" name="tdp" placeholder="e.g. 125" min="0"></div>
                    <div class="form-group"><label>PassMark Score</label><input type="number" name="passmark" placeholder="e.g. 35000" min="0"></div>
                </div>
            </div>

            <!-- MOTHERBOARD SPECS -->
            <div class="form-section spec-panel" id="spec-motherboard">
                <div class="section-heading">📋 Motherboard Specifications</div>
                <div class="form-grid">
                    <div class="form-group"><label>Socket</label><input type="text" name="socket" placeholder="e.g. LGA1700, AM5"></div>
                    <div class="form-group">
                        <label>Form Factor</label>
                        <select name="form_factor"><option value="ATX">ATX</option><option value="Micro-ATX">Micro-ATX</option><option value="Mini-ITX">Mini-ITX</option></select>
                    </div>
                    <div class="form-group">
                        <label>Supported RAM Type</label>
                        <select name="ram_type"><option value="DDR4">DDR4</option><option value="DDR5">DDR5</option></select>
                    </div>
                    <div class="form-group"><label>RAM Slots</label><input type="number" name="ram_slots" placeholder="e.g. 4" min="1" value="4"></div>
                    <div class="form-group"><label>M.2 Slots</label><input type="number" name="m2_slots" placeholder="e.g. 2" min="0" value="2"></div>
                </div>
            </div>

            <!-- RAM SPECS -->
            <div class="form-section spec-panel" id="spec-ram">
                <div class="section-heading">💾 RAM Specifications</div>
                <div class="form-grid">
                    <div class="form-group"><label>Capacity (GB)</label><input type="number" name="capacity" placeholder="e.g. 16, 32" min="1"></div>
                    <div class="form-group">
                        <label>DDR Version</label>
                        <select name="ddr_version"><option value="DDR4">DDR4</option><option value="DDR5">DDR5</option><option value="DDR3">DDR3</option></select>
                    </div>
                    <div class="form-group"><label>Speed (MHz)</label><input type="number" name="speed_mhz" placeholder="e.g. 3200, 5600" min="0"></div>
                </div>
            </div>

            <!-- GPU SPECS -->
            <div class="form-section spec-panel" id="spec-gpu">
                <div class="section-heading">🎮 GPU Specifications</div>
                <div class="form-grid">
                    <div class="form-group"><label>VRAM (GB)</label><input type="number" name="vram" placeholder="e.g. 8, 16, 24" min="1"></div>
                    <div class="form-group"><label>TDP (Watts)</label><input type="number" name="tdp" placeholder="e.g. 250" min="0"></div>
                    <div class="form-group"><label>GPU Length (mm)</label><input type="number" name="gpu_length" placeholder="e.g. 285" min="0"></div>
                    <div class="form-group"><label>Memory Type</label><input type="text" name="mem_type" placeholder="e.g. GDDR6X" value="GDDR6"></div>
                    <div class="form-group"><label>Performance Score</label><input type="number" name="perf_score" placeholder="e.g. 25000" min="0"></div>
                </div>
            </div>

            <!-- PSU SPECS -->
            <div class="form-section spec-panel" id="spec-powersupply">
                <div class="section-heading">⚡ Power Supply Specifications</div>
                <div class="form-grid">
                    <div class="form-group"><label>Wattage</label><input type="number" name="wattage" placeholder="e.g. 750" min="0"></div>
                    <div class="form-group">
                        <label>Efficiency Rating</label>
                        <select name="efficiency"><option value="Bronze">80+ Bronze</option><option value="Gold">80+ Gold</option><option value="Platinum">80+ Platinum</option></select>
                    </div>
                    <div class="form-group">
                        <label>Modularity</label>
                        <select name="modularity"><option value="Non">Non-Modular</option><option value="Semi">Semi-Modular</option><option value="Full">Full Modular</option></select>
                    </div>
                </div>
            </div>

            <!-- STORAGE SPECS -->
            <div class="form-section spec-panel" id="spec-storage">
                <div class="section-heading">💿 Storage Specifications</div>
                <div class="form-grid">
                    <div class="form-group"><label>Capacity (GB)</label><input type="number" name="capacity" placeholder="e.g. 1000 for 1TB" min="1"></div>
                    <div class="form-group">
                        <label>Storage Type</label>
                        <select name="storage_type"><option value="NVMe SSD">NVMe SSD</option><option value="SATA SSD">SATA SSD</option><option value="HDD">HDD</option></select>
                    </div>
                    <div class="form-group"><label>Interface</label><input type="text" name="interface" placeholder="e.g. PCIe 4.0 x4, SATA III" value="PCIe 4.0 x4"></div>
                    <div class="form-group"><label>Read Speed (MB/s)</label><input type="number" name="read_speed" placeholder="e.g. 7000" min="0"></div>
                    <div class="form-group"><label>Write Speed (MB/s)</label><input type="number" name="write_speed" placeholder="e.g. 6500" min="0"></div>
                </div>
            </div>

            <!-- CASE SPECS -->
            <div class="form-section spec-panel" id="spec-case">
                <div class="section-heading">📦 Case Specifications</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Form Factor</label>
                        <select name="case_ff"><option value="ATX">ATX</option><option value="Micro-ATX">Micro-ATX</option><option value="Mini-ITX">Mini-ITX</option></select>
                    </div>
                    <div class="form-group"><label>Color</label><input type="text" name="color" placeholder="e.g. Black, White" value="Black"></div>
                    <div class="form-group"><label>Max GPU Length (mm)</label><input type="number" name="max_gpu" placeholder="e.g. 360" min="0" value="360"></div>
                </div>
            </div>

            <button type="submit" name="add_component" class="btn-submit">➕ Add Component</button>
        </form>
    </div>
</main>

<script>
function showSpecs(val) {
    document.querySelectorAll('.spec-panel').forEach(p => p.classList.remove('active'));
    const map = {
        'CPU':          'spec-cpu',
        'Motherboard':  'spec-motherboard',
        'RAM':          'spec-ram',
        'GPU':          'spec-gpu',
        'Power Supply': 'spec-powersupply',
        'Storage':      'spec-storage',
        'Case':         'spec-case',
    };
    if (map[val]) document.getElementById(map[val]).classList.add('active');
}
// On page load if type was pre-selected
document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('typeSelect');
    if (sel.value) showSpecs(sel.value);
});
</script>
</body>
</html>
