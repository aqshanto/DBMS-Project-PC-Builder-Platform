<?php
session_start();
require_once 'config.php';
$conn = get_db_connection();

// ============================================================
//  URL PARAMS
// ============================================================
$target_slot = isset($_GET['slot'])    ? trim($_GET['slot'])               : '';
$filter      = isset($_GET['filter'])  ? strtolower(trim($_GET['filter'])) : ($target_slot ?: 'all');
$search      = isset($_GET['search'])  ? trim($_GET['search'])             : '';
$sort        = isset($_GET['sort'])    ? $_GET['sort']                     : 'default';
$price_min   = isset($_GET['pmin'])    ? (int)$_GET['pmin']               : 0;
$price_max   = isset($_GET['pmax'])    ? (int)$_GET['pmax']               : 999999;
$brands_sel  = isset($_GET['brands'])  && is_array($_GET['brands']) ? $_GET['brands'] : [];
$in_stock    = isset($_GET['instock']) ? 1 : 0;
$compat_only = isset($_GET['compat'])  ? 1 : 0;
$view_mode   = isset($_GET['view'])    && $_GET['view'] === 'list' ? 'list' : 'grid';

// ============================================================
//  BUILD COMPATIBILITY CONTEXT
// ============================================================
$req_socket = $req_ram_type = $req_form_factor = "";
$total_tdp  = 0;
$max_gpu_len = 9999;

if (!empty($_SESSION['build']['cpu'])) {
    $id = (int)$_SESSION['build']['cpu'];
    $r  = $conn->query("SELECT Socket, tdp_watt FROM cpus WHERE component_id=$id")->fetch_assoc();
    if ($r) { $req_socket = $r['Socket']; $total_tdp += (float)$r['tdp_watt']; }
}
if (!empty($_SESSION['build']['motherboard'])) {
    $id = (int)$_SESSION['build']['motherboard'];
    $r  = $conn->query("SELECT Socket, supported_ram_type, Form_Factor FROM motherboards WHERE component_id=$id")->fetch_assoc();
    if ($r) { if (!$req_socket) $req_socket = $r['Socket']; $req_ram_type = $r['supported_ram_type']; $req_form_factor = $r['Form_Factor']; }
}
if (!empty($_SESSION['build']['ram'])) {
    $id = (int)$_SESSION['build']['ram'];
    $r  = $conn->query("SELECT DDR_Version FROM rams WHERE component_id=$id")->fetch_assoc();
    if ($r && !$req_ram_type) $req_ram_type = $r['DDR_Version'];
}
if (!empty($_SESSION['build']['case'])) {
    $id = (int)$_SESSION['build']['case'];
    $r  = $conn->query("SELECT Form_Factor, Max_GPU_Length FROM cases WHERE component_id=$id")->fetch_assoc();
    if ($r) { if (!$req_form_factor) $req_form_factor = $r['Form_Factor']; $max_gpu_len = (int)$r['Max_GPU_Length']; }
}
if (!empty($_SESSION['build']['gpu'])) {
    $id = (int)$_SESSION['build']['gpu'];
    $r  = $conn->query("SELECT TDP_Watt FROM gpus WHERE component_id=$id")->fetch_assoc();
    if ($r) $total_tdp += (float)$r['TDP_Watt'];
}
$req_min_psu = $total_tdp > 0 ? (int)($total_tdp * 1.3 + 50) : 0;
$build_count = isset($_SESSION['build']) ? count($_SESSION['build']) : 0;

function ff_compatible($case_ff, $mb_ff) {
    $rank = ['ATX' => 3, 'Micro-ATX' => 2, 'Mini-ITX' => 1];
    return ($rank[$case_ff] ?? 0) >= ($rank[$mb_ff] ?? 0);
}

// ============================================================
//  BRANDS FOR CURRENT CATEGORY
// ============================================================
$brand_sql = "SELECT Brand, COUNT(*) as cnt FROM components";
$sf_filter = $conn->real_escape_string($filter);
if ($filter !== 'all') $brand_sql .= " WHERE LOWER(REPLACE(Type,' ',''))='$sf_filter'";
$brand_sql .= " GROUP BY Brand ORDER BY Brand";
$all_brands = [];
$br_res = $conn->query($brand_sql);
while ($br = $br_res->fetch_assoc()) $all_brands[] = $br;

// Price range for slider
$pr_sql = "SELECT MIN(Price) as mn, MAX(Price) as mx FROM components";
if ($filter !== 'all') $pr_sql .= " WHERE LOWER(REPLACE(Type,' ',''))='$sf_filter'";
$pr = $conn->query($pr_sql)->fetch_assoc();
$cat_pmin = (int)($pr['mn'] ?? 0);
$cat_pmax = (int)($pr['mx'] ?? 999999);
if ($price_max === 999999) $price_max = $cat_pmax;

// ============================================================
//  CATEGORY COUNTS
// ============================================================
$cat_counts = ['all' => 0];
$cr_res = $conn->query("SELECT LOWER(REPLACE(Type,' ','')) as nt, COUNT(*) as cnt FROM components GROUP BY Type");
while ($cr = $cr_res->fetch_assoc()) { $cat_counts[$cr['nt']] = (int)$cr['cnt']; $cat_counts['all'] += (int)$cr['cnt']; }

// ============================================================
//  MAIN QUERY
// ============================================================
$where = ["1=1"];
$ss = $conn->real_escape_string($search);
if (!empty($search))   $where[] = "(c.Name LIKE '%$ss%' OR c.Brand LIKE '%$ss%' OR c.Type LIKE '%$ss%')";
if ($filter !== 'all') $where[] = "LOWER(REPLACE(c.Type,' ',''))='$sf_filter'";
if ($price_min > 0)    $where[] = "c.Price >= $price_min";
if ($price_max < 999999) $where[] = "c.Price <= $price_max";
if ($in_stock)         $where[] = "c.stock_quantity > 0";
if (!empty($brands_sel)) {
    $be = implode(',', array_map(fn($b) => "'" . $conn->real_escape_string($b) . "'", $brands_sel));
    $where[] = "c.Brand IN ($be)";
}
$order_map = ['price_asc'=>'c.Price ASC','price_desc'=>'c.Price DESC','name_asc'=>'c.Name ASC','name_desc'=>'c.Name DESC','stock'=>'c.stock_quantity DESC'];
$order  = $order_map[$sort] ?? 'c.Type ASC, c.Name ASC';
$result = $conn->query("SELECT c.* FROM components c WHERE " . implode(' AND ', $where) . " ORDER BY $order");
$total_before_compat = $result ? $result->num_rows : 0;

// ============================================================
//  URL BUILDER HELPER
// ============================================================
function build_url($ov = []) {
    global $filter, $search, $sort, $price_min, $price_max, $brands_sel, $in_stock, $compat_only, $view_mode, $target_slot, $cat_pmax;
    $p = [];
    if (!isset($ov['filter'])) { if ($filter !== 'all') $p['filter'] = $filter; } elseif ($ov['filter'] !== null) $p['filter'] = $ov['filter'];
    if (!isset($ov['search'])) { if ($search) $p['search'] = $search; } elseif ($ov['search'] !== null) $p['search'] = $ov['search'];
    if (!isset($ov['sort']))   { if ($sort !== 'default') $p['sort'] = $sort; } elseif ($ov['sort'] !== null) $p['sort'] = $ov['sort'];
    $pm = isset($ov['pmin']) ? $ov['pmin'] : $price_min;
    $px = isset($ov['pmax']) ? $ov['pmax'] : $price_max;
    if ($pm > 0)   $p['pmin'] = $pm;
    if ($px < $cat_pmax && $px !== null) $p['pmax'] = $px;
    $brands = isset($ov['brands']) ? $ov['brands'] : $brands_sel;
    if (!empty($brands)) $p['brands'] = $brands;
    if (!isset($ov['instock'])) { if ($in_stock)    $p['instock'] = 1; } elseif ($ov['instock'] !== null) $p['instock'] = 1;
    if (!isset($ov['compat']))  { if ($compat_only) $p['compat']  = 1; } elseif ($ov['compat']  !== null) $p['compat']  = 1;
    if (!isset($ov['view']))    { if ($view_mode === 'list') $p['view'] = 'list'; } elseif ($ov['view'] !== null) $p['view'] = $ov['view'];
    if ($target_slot) $p['slot'] = $target_slot;
    return 'index.php' . (!empty($p) ? '?' . http_build_query($p) : '');
}

$active_filters = count($brands_sel) + ($price_min > $cat_pmin || $price_max < $cat_pmax ? 1 : 0) + $in_stock + $compat_only;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NextGen PC Builder</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Exo+2:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ═══════════════════════════════════════════════
           CYBERPUNK GLASSMORPHISM — DESIGN SYSTEM
        ═══════════════════════════════════════════════ */
        :root {
            --bg-deep:     #020810;
            --bg-mid:      #060f1e;
            --glass:       rgba(10, 25, 55, 0.55);
            --glass-hover: rgba(15, 35, 75, 0.75);
            --glass-solid: rgba(8, 20, 45, 0.85);
            --border-glow: rgba(0, 200, 255, 0.18);
            --border-dim:  rgba(0, 150, 200, 0.10);
            --cyan:        #00d4ff;
            --cyan-dim:    rgba(0, 212, 255, 0.6);
            --cyan-glow:   rgba(0, 212, 255, 0.15);
            --blue:        #0080ff;
            --purple:      #7b2fff;
            --success:     #00ff9d;
            --success-dim: rgba(0, 255, 157, 0.15);
            --danger:      #ff3366;
            --warn:        #ffaa00;
            --text-bright: #e8f4ff;
            --text-mid:    #8bb8d4;
            --text-dim:    #4a7a9b;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        /* ANIMATED BACKGROUND */
        body {
            font-family: 'Exo 2', sans-serif;
            background: var(--bg-deep);
            color: var(--text-bright);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 50% at 20% -10%, rgba(0,80,255,0.12) 0%, transparent 60%),
                radial-gradient(ellipse 60% 40% at 80% 10%, rgba(123,47,255,0.1) 0%, transparent 55%),
                radial-gradient(ellipse 50% 60% at 50% 100%, rgba(0,180,255,0.08) 0%, transparent 60%);
            pointer-events: none;
            z-index: 0;
        }

        /* Animated grid lines */
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(0,180,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,180,255,0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
            z-index: 0;
        }

        /* GLASS MIXIN */
        .glass {
            background: var(--glass);
            backdrop-filter: blur(20px) saturate(1.4);
            -webkit-backdrop-filter: blur(20px) saturate(1.4);
            border: 1px solid var(--border-glow);
            position: relative;
            z-index: 1;
        }

        /* ── HEADER ── */
        header {
            background: rgba(2, 8, 20, 0.8);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border-bottom: 1px solid var(--border-glow);
            padding: 1.4rem 2rem 1.2rem;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 40px rgba(0, 212, 255, 0.07);
        }

        header::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--cyan), var(--blue), var(--purple), transparent);
        }

        header h1 {
            font-family: 'Rajdhani', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 4px;
            text-transform: uppercase;
            background: linear-gradient(135deg, var(--cyan), var(--blue), var(--purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
        }

        header p {
            color: var(--text-dim);
            font-size: .82rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: .3rem;
        }

        .h-actions {
            display: flex;
            justify-content: center;
            gap: .6rem;
            margin-top: .9rem;
            flex-wrap: wrap;
            align-items: center;
        }

        /* NEON BUTTONS */
        .btn-cart {
            background: linear-gradient(135deg, rgba(0,130,255,0.2), rgba(0,212,255,0.15));
            color: var(--cyan);
            padding: 8px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            font-size: .82rem;
            border: 1px solid rgba(0,212,255,0.35);
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: all .2s;
            position: relative;
            overflow: hidden;
            font-family: 'Rajdhani', sans-serif;
        }
        .btn-cart:hover {
            background: rgba(0,212,255,0.2);
            border-color: var(--cyan);
            box-shadow: 0 0 20px rgba(0,212,255,0.25), inset 0 0 20px rgba(0,212,255,0.05);
            color: white;
        }
        .cbadge {
            background: var(--warn);
            color: #000;
            border-radius: 3px;
            padding: 1px 7px;
            font-size: .72rem;
            font-weight: 700;
            margin-left: 5px;
        }
        .btn-a {
            padding: 7px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            font-size: .78rem;
            transition: all .2s;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-family: 'Rajdhani', sans-serif;
        }
        .btn-login  { border: 1px solid rgba(0,212,255,0.3); color: var(--cyan-dim); }
        .btn-login:hover  { border-color: var(--cyan); color: var(--cyan); box-shadow: 0 0 12px rgba(0,212,255,0.2); }
        .btn-reg    { background: linear-gradient(135deg,rgba(0,255,157,0.15),rgba(0,200,100,0.1)); color: var(--success); border: 1px solid rgba(0,255,157,0.3); }
        .btn-reg:hover    { border-color: var(--success); box-shadow: 0 0 12px rgba(0,255,157,0.2); }
        .btn-out    { border: 1px solid rgba(255,51,102,0.3); color: rgba(255,51,102,.7); }
        .btn-out:hover    { border-color: var(--danger); color: var(--danger); box-shadow: 0 0 12px rgba(255,51,102,0.2); }
        .welmsg     { color: var(--success); font-size: .8rem; font-weight: 600; letter-spacing: 1px; }

        /* PROGRESS BAR */
        .prog-wrap { max-width: 300px; margin: .7rem auto 0; }
        .prog-lbl  { font-size: .72rem; color: var(--text-dim); margin-bottom: 4px; letter-spacing: 1px; text-transform: uppercase; }
        .prog-bar  { background: rgba(255,255,255,.05); border-radius: 2px; height: 3px; overflow: hidden; }
        .prog-fill { height: 100%; border-radius: 2px; background: linear-gradient(90deg, var(--cyan), var(--blue)); transition: width .5s; box-shadow: 0 0 8px var(--cyan); }

        /* ── CATEGORY TABS ── */
        .tabs-wrap {
            max-width: 1400px;
            margin: 1rem auto .4rem;
            padding: 0 1.5rem;
            overflow-x: auto;
            position: relative;
            z-index: 1;
        }
        .tabs { display: flex; gap: .4rem; white-space: nowrap; }
        .tab {
            padding: 7px 16px;
            border-radius: 3px;
            text-decoration: none;
            font-size: .78rem;
            font-weight: 600;
            border: 1px solid var(--border-dim);
            color: var(--text-dim);
            transition: all .2s;
            flex-shrink: 0;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-family: 'Rajdhani', sans-serif;
            background: var(--glass);
        }
        .tab:hover { border-color: var(--border-glow); color: var(--text-mid); }
        .tab.active {
            background: linear-gradient(135deg, rgba(0,130,255,0.25), rgba(0,212,255,0.15));
            border-color: rgba(0,212,255,0.5);
            color: var(--cyan);
            box-shadow: 0 0 15px rgba(0,212,255,0.15);
        }
        .tab .ct {
            background: rgba(0,212,255,0.15);
            color: var(--cyan-dim);
            padding: 1px 6px;
            border-radius: 2px;
            font-size: .68rem;
            margin-left: 4px;
        }

        /* ── SLOT BANNER ── */
        .slot-banner {
            max-width: 1400px;
            margin: .5rem auto;
            padding: 0 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
            z-index: 1;
        }
        .slot-badge {
            background: linear-gradient(135deg, rgba(0,130,255,0.2), rgba(0,212,255,0.1));
            color: var(--cyan);
            border: 1px solid rgba(0,212,255,0.3);
            padding: 7px 16px;
            border-radius: 3px;
            font-weight: 600;
            font-size: .8rem;
            letter-spacing: 1px;
            font-family: 'Rajdhani', sans-serif;
        }

        /* ── MAIN LAYOUT ── */
        .main-wrap {
            max-width: 1400px;
            margin: 0 auto;
            padding: .5rem 1.5rem 3rem;
            display: grid;
            grid-template-columns: 230px 1fr;
            gap: 1.2rem;
            align-items: start;
            position: relative;
            z-index: 1;
        }

        /* ── FILTER SIDEBAR ── */
        .sidebar {
            background: var(--glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 8px;
            border: 1px solid var(--border-glow);
            padding: 1.2rem;
            position: sticky;
            top: 80px;
        }

        .sb-title {
            font-family: 'Rajdhani', sans-serif;
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--cyan-dim);
            margin-bottom: .9rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .3rem;
        }
        .sb-clear { color: var(--danger); font-size: .72rem; text-decoration: none; letter-spacing: 0; text-transform: none; font-family: 'Exo 2', sans-serif; }
        .sb-clear:hover { text-decoration: underline; }

        .sb-sec { border-bottom: 1px solid var(--border-dim); padding-bottom: .9rem; margin-bottom: .9rem; }
        .sb-sec:last-of-type { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }

        .sb-sec-title {
            font-family: 'Rajdhani', sans-serif;
            font-size: .8rem;
            font-weight: 700;
            color: var(--text-mid);
            margin-bottom: .65rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            user-select: none;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .sb-sec-title::after { content: '▲'; font-size: .5rem; color: var(--text-dim); transition: transform .2s; }
        .sb-sec-title.coll::after { transform: rotate(180deg); }
        .sb-body { display: flex; flex-direction: column; gap: .45rem; }
        .sb-body.hide { display: none; }

        .pi-row { display: flex; gap: .4rem; margin-bottom: .5rem; }
        .pi-row input {
            width: 100%;
            padding: 6px 8px;
            background: rgba(0,0,0,.4);
            border: 1px solid var(--border-dim);
            border-radius: 4px;
            color: var(--text-bright);
            font-size: .78rem;
            outline: none;
            font-family: 'Exo 2', sans-serif;
        }
        .pi-row input:focus { border-color: var(--border-glow); }

        input[type=range] {
            width: 100%;
            accent-color: var(--cyan);
            cursor: pointer;
            margin: .2rem 0;
        }
        .pr-labels { display: flex; justify-content: space-between; font-size: .68rem; color: var(--text-dim); }

        .fcheck {
            display: flex;
            align-items: center;
            gap: .45rem;
            font-size: .8rem;
            cursor: pointer;
            color: var(--text-mid);
            transition: color .15s;
        }
        .fcheck:hover { color: var(--text-bright); }
        .fcheck input[type=checkbox] { accent-color: var(--cyan); width: 13px; height: 13px; cursor: pointer; flex-shrink: 0; }
        .fcnt {
            margin-left: auto;
            background: rgba(0,212,255,.08);
            color: var(--text-dim);
            padding: 1px 6px;
            border-radius: 2px;
            font-size: .68rem;
            flex-shrink: 0;
        }

        /* TOGGLE */
        .tog-row { display: flex; align-items: center; justify-content: space-between; }
        .tog-lbl { font-size: .8rem; color: var(--text-mid); }
        .tog { position: relative; width: 36px; height: 19px; flex-shrink: 0; }
        .tog input { opacity: 0; width: 0; height: 0; }
        .tog-sl { position: absolute; inset: 0; background: rgba(255,255,255,.05); border: 1px solid var(--border-dim); border-radius: 2px; transition: .3s; cursor: pointer; }
        .tog-sl::before { content: ''; position: absolute; width: 13px; height: 13px; left: 2px; top: 2px; background: var(--text-dim); border-radius: 1px; transition: .3s; }
        .tog input:checked + .tog-sl { background: rgba(0,212,255,.15); border-color: rgba(0,212,255,.5); }
        .tog input:checked + .tog-sl::before { transform: translateX(17px); background: var(--cyan); box-shadow: 0 0 8px var(--cyan); }

        /* ── CONTENT AREA ── */
        .content { min-width: 0; }

        /* TOP BAR */
        .top-bar {
            background: var(--glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 8px;
            border: 1px solid var(--border-glow);
            padding: .85rem 1rem;
            margin-bottom: .8rem;
            display: flex;
            gap: .6rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .s-inp {
            flex: 1;
            min-width: 160px;
            padding: 9px 13px;
            background: rgba(0,0,0,.4);
            border: 1px solid var(--border-dim);
            border-radius: 4px;
            color: var(--text-bright);
            font-size: .88rem;
            outline: none;
            font-family: 'Exo 2', sans-serif;
            transition: border .2s;
        }
        .s-inp:focus { border-color: rgba(0,212,255,.4); box-shadow: 0 0 12px rgba(0,212,255,.08); }
        .s-inp::placeholder { color: var(--text-dim); }
        .s-btn {
            padding: 8px 18px;
            background: linear-gradient(135deg, rgba(0,130,255,.25), rgba(0,212,255,.15));
            color: var(--cyan);
            border: 1px solid rgba(0,212,255,.35);
            border-radius: 4px;
            font-weight: 600;
            font-size: .82rem;
            cursor: pointer;
            white-space: nowrap;
            letter-spacing: 1px;
            font-family: 'Rajdhani', sans-serif;
            text-transform: uppercase;
            transition: all .2s;
        }
        .s-btn:hover { box-shadow: 0 0 15px rgba(0,212,255,.2); color: white; }
        .sort-sel {
            padding: 8px 12px;
            background: rgba(0,0,0,.4);
            border: 1px solid var(--border-dim);
            border-radius: 4px;
            color: var(--text-mid);
            font-size: .82rem;
            outline: none;
            cursor: pointer;
            font-family: 'Exo 2', sans-serif;
        }
        .sort-sel:focus { border-color: var(--border-glow); }
        .view-tog { display: flex; gap: .3rem; flex-shrink: 0; }
        .vbtn {
            padding: 8px 12px;
            background: rgba(0,0,0,.3);
            border: 1px solid var(--border-dim);
            border-radius: 4px;
            color: var(--text-dim);
            cursor: pointer;
            font-size: .85rem;
            text-decoration: none;
            transition: all .2s;
        }
        .vbtn.active, .vbtn:hover {
            background: rgba(0,212,255,.15);
            border-color: rgba(0,212,255,.4);
            color: var(--cyan);
        }

        /* CHIPS */
        .chips { display: flex; gap: .45rem; flex-wrap: wrap; margin-bottom: .7rem; align-items: center; }
        .chip-lbl { font-size: .72rem; color: var(--text-dim); letter-spacing: 1px; text-transform: uppercase; font-family: 'Rajdhani', sans-serif; }
        .chip {
            font-size: .75rem;
            background: rgba(0,130,255,.15);
            color: var(--text-mid);
            border: 1px solid rgba(0,212,255,.2);
            padding: 3px 9px;
            border-radius: 3px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .chip a { color: var(--danger); text-decoration: none; font-weight: bold; font-size: .82rem; }
        .chip a:hover { color: white; }

        /* RESULT BAR */
        .res-bar { display: flex; align-items: center; justify-content: space-between; margin-bottom: .8rem; flex-wrap: wrap; gap: .5rem; }
        .res-count { font-size: .78rem; color: var(--text-dim); letter-spacing: .5px; }
        .res-count strong { color: var(--text-bright); }
        .cp-pills { display: flex; gap: .35rem; flex-wrap: wrap; }
        .cp-pill {
            background: rgba(0,130,255,.12);
            border: 1px solid rgba(0,212,255,.2);
            color: var(--text-dim);
            padding: 2px 9px;
            border-radius: 3px;
            font-size: .7rem;
            letter-spacing: .5px;
        }
        .cp-pill span { color: var(--cyan-dim); font-weight: 600; }

        /* ══════════════════════════════════
           PRODUCT GRID — GLASS CARDS
        ══════════════════════════════════ */
        .pgrid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(185px, 1fr));
            gap: 1rem;
        }

        a.card {
            background: var(--glass);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 8px;
            padding: 1.1rem;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
            border: 1px solid var(--border-dim);
            transition: all .3s cubic-bezier(.2, 0, 0, 1);
            position: relative;
            overflow: hidden;
        }

        a.card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(0,212,255,0.4), transparent);
            transform: translateX(-100%);
            transition: transform .5s;
        }

        a.card:hover {
            border-color: rgba(0,212,255,.35);
            background: var(--glass-hover);
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0,100,200,.2), 0 0 0 1px rgba(0,212,255,.1);
        }
        a.card:hover::before { transform: translateX(100%); }
        a.card.incompat { opacity: .3; pointer-events: none; filter: grayscale(60%); }

        .cimg {
            width: 100%;
            height: 115px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,.35);
            border-radius: 5px;
            margin-bottom: .8rem;
            overflow: hidden;
            border: 1px solid var(--border-dim);
        }
        .cimg img {
            max-width: 100%;
            max-height: 105px;
            object-fit: contain;
            transition: transform .4s;
            padding: 5px;
        }
        a.card:hover .cimg img { transform: scale(1.08); }

        .tbadge {
            display: inline-block;
            background: rgba(0,212,255,.1);
            color: var(--cyan-dim);
            border: 1px solid rgba(0,212,255,.2);
            padding: .18rem .65rem;
            border-radius: 2px;
            font-size: .62rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: .55rem;
            align-self: flex-start;
            font-family: 'Rajdhani', sans-serif;
        }
        .card h3 { font-size: .92rem; margin-bottom: .28rem; line-height: 1.3; color: var(--text-bright); font-weight: 500; }
        .card .brand { color: var(--text-dim); font-size: .72rem; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: .6rem; font-family: 'Rajdhani', sans-serif; font-weight: 600; }
        .card .price {
            font-family: 'Rajdhani', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--cyan);
            margin-top: auto;
            padding-top: .7rem;
            border-top: 1px solid var(--border-dim);
            letter-spacing: .5px;
        }
        .card .stock { font-size: .7rem; color: var(--text-dim); margin-top: .28rem; }

        .wlbl {
            color: var(--danger);
            font-size: .7rem;
            background: rgba(255,51,102,.1);
            padding: 4px 7px;
            border-radius: 3px;
            margin-top: .6rem;
            text-align: center;
            font-weight: 600;
            border: 1px solid rgba(255,51,102,.25);
        }
        .clbl {
            color: var(--success);
            font-size: .68rem;
            margin-top: .55rem;
            text-align: center;
            background: rgba(0,255,157,.08);
            padding: 4px 7px;
            border-radius: 3px;
            border: 1px solid rgba(0,255,157,.2);
            font-family: 'Rajdhani', sans-serif;
            letter-spacing: .5px;
            font-weight: 700;
        }

        /* ══════════════════════════════════
           LIST VIEW
        ══════════════════════════════════ */
        .plist { display: flex; flex-direction: column; gap: .6rem; }
        a.lcard {
            background: var(--glass);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 7px;
            padding: .85rem 1.1rem;
            display: flex;
            align-items: center;
            gap: .9rem;
            text-decoration: none;
            color: inherit;
            border: 1px solid var(--border-dim);
            transition: all .2s;
        }
        a.lcard:hover { border-color: rgba(0,212,255,.3); background: var(--glass-hover); }
        a.lcard.incompat { opacity: .3; pointer-events: none; filter: grayscale(60%); }
        .limg { width: 58px; height: 58px; flex-shrink: 0; background: rgba(0,0,0,.35); border-radius: 5px; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px solid var(--border-dim); }
        .limg img { max-width: 100%; max-height: 52px; object-fit: contain; padding: 3px; }
        .linfo { flex: 1; min-width: 0; }
        .ltype { font-size: .65rem; text-transform: uppercase; letter-spacing: 1.5px; color: var(--cyan-dim); font-weight: 700; margin-bottom: .18rem; font-family: 'Rajdhani', sans-serif; }
        .lname { font-size: .9rem; font-weight: 500; margin-bottom: .18rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text-bright); }
        .lbrand { font-size: .72rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; font-family: 'Rajdhani', sans-serif; }
        .lright { display: flex; flex-direction: column; align-items: flex-end; gap: .28rem; flex-shrink: 0; }
        .lprice { font-size: 1.1rem; font-weight: 700; color: var(--cyan); font-family: 'Rajdhani', sans-serif; letter-spacing: .5px; }
        .lstock { font-size: .7rem; color: var(--text-dim); }
        .lcbadge { font-size: .68rem; padding: 2px 8px; border-radius: 3px; font-weight: 700; font-family: 'Rajdhani', sans-serif; letter-spacing: .5px; }
        .lcbadge.ok { background: rgba(0,255,157,.08); color: var(--success); border: 1px solid rgba(0,255,157,.2); }
        .lcbadge.no { background: rgba(255,51,102,.08); color: var(--danger); border: 1px solid rgba(255,51,102,.2); }

        /* EMPTY */
        .empty { text-align: center; padding: 4rem 1rem; color: var(--text-dim); }
        .empty h2 { font-size: 1.4rem; margin-bottom: .5rem; font-family: 'Rajdhani', sans-serif; letter-spacing: 2px; color: var(--text-mid); }
        .empty a { color: var(--cyan); text-decoration: none; font-size: .85rem; }

        /* MOBILE */
        .mob-filter-btn {
            display: none;
            width: 100%;
            padding: 10px;
            background: var(--glass);
            border: 1px solid var(--border-glow);
            border-radius: 6px;
            color: var(--text-bright);
            font-weight: 600;
            font-size: .85rem;
            cursor: pointer;
            margin-bottom: .8rem;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            font-family: 'Rajdhani', sans-serif;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* SCROLLBAR */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: rgba(0,0,0,.2); }
        ::-webkit-scrollbar-thumb { background: rgba(0,212,255,.2); border-radius: 2px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(0,212,255,.4); }

        @media(max-width:820px){
            .main-wrap{grid-template-columns:1fr;}
            .sidebar{display:none;}
            .sidebar.open{display:block;}
            .mob-filter-btn{display:flex;}
            .pgrid{grid-template-columns:repeat(auto-fill,minmax(155px,1fr));}
            header h1{font-size:1.5rem;}
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header>
    <h1>⚡ NEXTGEN PC BUILDER</h1>
    <p>Configure · Optimize · Build</p>
    <div class="h-actions">
        <a href="build.php" class="btn-cart">
            🛒 My Build<?php if ($build_count > 0): ?><span class="cbadge"><?= $build_count ?>/7</span><?php endif; ?>
        </a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="my_builds.php"  class="btn-a btn-login">📂 Builds</a>
            <a href="my_orders.php"  class="btn-a btn-login">📦 Orders</a>
            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                <a href="admin/admin_dashboard.php" class="btn-a" style="border:1px solid rgba(255,170,0,.3);color:rgba(255,170,0,.8);">🔑 Admin</a>
            <?php endif; ?>
            <span class="welmsg">◈ <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="logout.php" class="btn-a btn-out">Exit</a>
        <?php else: ?>
            <a href="login.php"    class="btn-a btn-login">Sign In</a>
            <a href="register.php" class="btn-a btn-reg">Register</a>
        <?php endif; ?>
    </div>
    <?php if (isset($_GET['welcome'])): ?>
    <div style="margin-top:.6rem;">
        <span style="background:rgba(0,255,157,.08);border:1px solid rgba(0,255,157,.3);color:var(--success);padding:5px 16px;border-radius:3px;font-size:.78rem;font-weight:600;letter-spacing:1px;font-family:'Rajdhani',sans-serif;">
            ◈ WELCOME ABOARD, <?= strtoupper(htmlspecialchars($_SESSION['username'])) ?>!
        </span>
    </div>
    <?php endif; ?>
    <?php if ($build_count > 0): ?>
    <div class="prog-wrap">
        <div class="prog-lbl"><?= $build_count ?>/7 components selected</div>
        <div class="prog-bar"><div class="prog-fill" style="width:<?= round($build_count/7*100) ?>%"></div></div>
    </div>
    <?php endif; ?>
</header>

<!-- CATEGORY TABS -->
<div class="tabs-wrap"><div class="tabs">
<?php
$tab_defs = ['all'=>'⬡ All','cpu'=>'CPU','motherboard'=>'Motherboard','ram'=>'RAM','gpu'=>'GPU','powersupply'=>'PSU','storage'=>'Storage','case'=>'Case'];
foreach ($tab_defs as $key => $label):
    $cnt    = $cat_counts[$key] ?? 0;
    $active = $filter === $key ? 'active' : '';
    $href   = build_url(['filter'=>$key==='all'?null:$key,'pmin'=>null,'pmax'=>null,'brands'=>null,'instock'=>null]);
?>
<a href="<?= $href ?>" class="tab <?= $active ?>"><?= $label ?><?php if ($cnt>0):?><span class="ct"><?= $cnt ?></span><?php endif;?></a>
<?php endforeach; ?>
</div></div>

<!-- SLOT BANNER -->
<?php if ($target_slot): ?>
<div class="slot-banner"><span class="slot-badge">◈ Selecting: <?= strtoupper($target_slot) ?></span><a href="index.php" style="color:var(--text-dim);font-size:.8rem;">← Back</a></div>
<?php endif; ?>

<!-- MAIN LAYOUT -->
<div class="main-wrap">

<!-- MOBILE FILTER BTN -->
<button class="mob-filter-btn" onclick="document.getElementById('sb').classList.toggle('open')">
    ⚙ Filters <?php if ($active_filters>0):?><span class="cbadge"><?= $active_filters ?></span><?php endif;?>
</button>

<!-- FILTER SIDEBAR -->
<form method="GET" action="index.php">
<?php if ($filter!=='all'):?><input type="hidden" name="filter" value="<?=htmlspecialchars($filter)?>"><?php endif;?>
<?php if ($target_slot):?><input type="hidden" name="slot" value="<?=htmlspecialchars($target_slot)?>"><?php endif;?>
<?php if ($search):?><input type="hidden" name="search" value="<?=htmlspecialchars($search)?>"><?php endif;?>
<?php if ($sort!=='default'):?><input type="hidden" name="sort" value="<?=htmlspecialchars($sort)?>"><?php endif;?>
<?php if ($view_mode==='list'):?><input type="hidden" name="view" value="list"><?php endif;?>

<aside class="sidebar" id="sb">
    <div class="sb-title">
        ⚙ Filters
        <?php if ($active_filters>0):?>
            <span style="background:rgba(0,212,255,.12);color:var(--cyan-dim);padding:1px 8px;border-radius:2px;font-size:.68rem;"><?=$active_filters?> active</span>
            <a href="<?=build_url(['pmin'=>null,'pmax'=>null,'brands'=>null,'instock'=>null,'compat'=>null])?>" class="sb-clear">Clear</a>
        <?php endif;?>
    </div>

    <!-- PRICE -->
    <div class="sb-sec">
        <div class="sb-sec-title" onclick="toggleSec(this)">Price Range (৳)</div>
        <div class="sb-body">
            <div class="pi-row">
                <input type="number" name="pmin" id="pmin" value="<?=$price_min?>" placeholder="Min" min="0" oninput="debounceSubmit()">
                <input type="number" name="pmax" id="pmax" value="<?=$price_max?>" placeholder="Max" min="0" oninput="debounceSubmit()">
            </div>
            <input type="range" id="pslider" min="<?=$cat_pmin?>" max="<?=$cat_pmax?>" value="<?=$price_max?>" oninput="document.getElementById('pmax').value=this.value" onchange="this.form.submit()">
            <div class="pr-labels"><span>৳<?=number_format($cat_pmin)?></span><span>৳<?=number_format($cat_pmax)?></span></div>
        </div>
    </div>

    <!-- BRAND -->
    <?php if (!empty($all_brands)):?>
    <div class="sb-sec">
        <div class="sb-sec-title" onclick="toggleSec(this)">Brand</div>
        <div class="sb-body">
            <?php foreach ($all_brands as $b):?>
            <label class="fcheck">
                <input type="checkbox" name="brands[]" value="<?=htmlspecialchars($b['Brand'])?>" <?=in_array($b['Brand'],$brands_sel)?'checked':''?> onchange="this.form.submit()">
                <?=htmlspecialchars($b['Brand'])?>
                <span class="fcnt"><?=$b['cnt']?></span>
            </label>
            <?php endforeach;?>
        </div>
    </div>
    <?php endif;?>

    <!-- IN STOCK -->
    <div class="sb-sec">
        <div class="sb-sec-title" onclick="toggleSec(this)">Availability</div>
        <div class="sb-body">
            <div class="tog-row">
                <span class="tog-lbl">In Stock only</span>
                <label class="tog"><input type="checkbox" name="instock" value="1" <?=$in_stock?'checked':''?> onchange="this.form.submit()"><span class="tog-sl"></span></label>
            </div>
        </div>
    </div>

    <!-- COMPAT -->
    <?php if ($req_socket||$req_ram_type||$req_form_factor||$req_min_psu>0):?>
    <div class="sb-sec">
        <div class="sb-sec-title" onclick="toggleSec(this)">Compatibility</div>
        <div class="sb-body">
            <div class="tog-row">
                <span class="tog-lbl">Compatible only</span>
                <label class="tog"><input type="checkbox" name="compat" value="1" <?=$compat_only?'checked':''?> onchange="this.form.submit()"><span class="tog-sl"></span></label>
            </div>
        </div>
    </div>
    <?php endif;?>
</aside>
</form>

<!-- CONTENT -->
<div class="content">

    <!-- TOP BAR -->
    <form method="GET" action="index.php" class="top-bar">
        <?php if ($filter!=='all'):?><input type="hidden" name="filter" value="<?=htmlspecialchars($filter)?>"><?php endif;?>
        <?php if ($target_slot):?><input type="hidden" name="slot" value="<?=htmlspecialchars($target_slot)?>"><?php endif;?>
        <?php if ($price_min>0):?><input type="hidden" name="pmin" value="<?=$price_min?>"><?php endif;?>
        <?php if ($price_max<$cat_pmax):?><input type="hidden" name="pmax" value="<?=$price_max?>"><?php endif;?>
        <?php foreach ($brands_sel as $bs):?><input type="hidden" name="brands[]" value="<?=htmlspecialchars($bs)?>"><?php endforeach;?>
        <?php if ($in_stock):?><input type="hidden" name="instock" value="1"><?php endif;?>
        <?php if ($compat_only):?><input type="hidden" name="compat" value="1"><?php endif;?>
        <?php if ($view_mode==='list'):?><input type="hidden" name="view" value="list"><?php endif;?>

        <input type="text" name="search" class="s-inp" placeholder="Search components..." value="<?=htmlspecialchars($search)?>">
        <button type="submit" class="s-btn">Search</button>
        <select name="sort" class="sort-sel" onchange="this.form.submit()">
            <option value="default"    <?=$sort==='default'   ?'selected':''?>>Default</option>
            <option value="price_asc"  <?=$sort==='price_asc' ?'selected':''?>>Price ↑</option>
            <option value="price_desc" <?=$sort==='price_desc'?'selected':''?>>Price ↓</option>
            <option value="name_asc"   <?=$sort==='name_asc'  ?'selected':''?>>Name A→Z</option>
            <option value="name_desc"  <?=$sort==='name_desc' ?'selected':''?>>Name Z→A</option>
            <option value="stock"      <?=$sort==='stock'     ?'selected':''?>>Stock ↓</option>
        </select>
        <div class="view-tog">
            <a href="<?=build_url(['view'=>null])?>" class="vbtn <?=$view_mode==='grid'?'active':''?>" title="Grid">▦</a>
            <a href="<?=build_url(['view'=>'list'])?>" class="vbtn <?=$view_mode==='list'?'active':''?>" title="List">☰</a>
        </div>
    </form>

    <!-- CHIPS -->
    <?php $has_chips = !empty($brands_sel)||$price_min>$cat_pmin||$price_max<$cat_pmax||$in_stock||$compat_only||!empty($search); ?>
    <?php if ($has_chips):?>
    <div class="chips">
        <span class="chip-lbl">Active:</span>
        <?php if (!empty($search)):?><span class="chip">⊕ "<?=htmlspecialchars($search)?>" <a href="<?=build_url(['search'=>null])?>">×</a></span><?php endif;?>
        <?php if ($price_min>$cat_pmin||$price_max<$cat_pmax):?><span class="chip">৳<?=number_format($price_min)?>–<?=number_format($price_max)?> <a href="<?=build_url(['pmin'=>null,'pmax'=>null])?>">×</a></span><?php endif;?>
        <?php foreach ($brands_sel as $bs):?><span class="chip"><?=htmlspecialchars($bs)?> <a href="<?=build_url(['brands'=>array_values(array_diff($brands_sel,[$bs]))])?>">×</a></span><?php endforeach;?>
        <?php if ($in_stock):?><span class="chip">In Stock <a href="<?=build_url(['instock'=>null])?>">×</a></span><?php endif;?>
        <?php if ($compat_only):?><span class="chip">Compatible <a href="<?=build_url(['compat'=>null])?>">×</a></span><?php endif;?>
    </div>
    <?php endif;?>

    <!-- RESULT BAR -->
    <div class="res-bar">
        <div class="res-count">
            <strong id="shownCnt">—</strong> / <strong><?=$total_before_compat?></strong>
            <?=$filter!=='all'?' '.strtoupper($filter):' components'?>
        </div>
        <?php if ($req_socket||$req_ram_type||$req_form_factor||$req_min_psu>0):?>
        <div class="cp-pills">
            <?php if ($req_socket):?><span class="cp-pill">Socket: <span><?=$req_socket?></span></span><?php endif;?>
            <?php if ($req_ram_type):?><span class="cp-pill">RAM: <span><?=$req_ram_type?></span></span><?php endif;?>
            <?php if ($req_form_factor):?><span class="cp-pill">Form: <span><?=$req_form_factor?></span></span><?php endif;?>
            <?php if ($req_min_psu>0):?><span class="cp-pill">PSU: <span>≥<?=$req_min_psu?>W</span></span><?php endif;?>
        </div>
        <?php endif;?>
    </div>

    <!-- PRODUCTS -->
    <div class="<?=$view_mode==='list'?'plist':'pgrid'?>" id="prodCont">
    <?php
    $shown = 0;
    if ($result && $result->num_rows > 0):
        while ($row = $result->fetch_assoc()):
            $cid   = $row['component_id'];
            $type  = $row['Type'];
            $ntype = str_replace(' ', '', strtolower($type));

            $is_compat = true; $wmsg = "";
            $has_ctx   = ($req_socket||$req_ram_type||$req_form_factor||$req_min_psu>0||$max_gpu_len<9999);

            if ($ntype==='cpu' && $req_socket) {
                $s=$conn->query("SELECT Socket FROM cpus WHERE component_id=$cid")->fetch_assoc();
                if ($s&&$s['Socket']!==$req_socket){$is_compat=false;$wmsg="🚫 Socket mismatch – needs {$req_socket}";}
            }
            if ($ntype==='motherboard') {
                $s=$conn->query("SELECT Socket,supported_ram_type,Form_Factor FROM motherboards WHERE component_id=$cid")->fetch_assoc();
                if ($s){
                    if ($req_socket&&$s['Socket']!==$req_socket){$is_compat=false;$wmsg="🚫 Socket mismatch – needs {$req_socket}";}
                    elseif ($req_ram_type&&$s['supported_ram_type']!==$req_ram_type){$is_compat=false;$wmsg="🚫 RAM mismatch – needs {$req_ram_type}";}
                    elseif ($req_form_factor&&!ff_compatible($req_form_factor,$s['Form_Factor'])){$is_compat=false;$wmsg="🚫 Won't fit in {$req_form_factor} case";}
                }
            }
            if ($ntype==='ram'&&$req_ram_type){
                $s=$conn->query("SELECT DDR_Version FROM rams WHERE component_id=$cid")->fetch_assoc();
                if ($s&&$s['DDR_Version']!==$req_ram_type){$is_compat=false;$wmsg="🚫 Needs {$req_ram_type} motherboard";}
            }
            if ($ntype==='case'){
                $s=$conn->query("SELECT Form_Factor FROM cases WHERE component_id=$cid")->fetch_assoc();
                if ($s&&$req_form_factor&&!ff_compatible($s['Form_Factor'],$req_form_factor)){$is_compat=false;$wmsg="🚫 Motherboard won't fit ({$req_form_factor})";}
            }
            if ($ntype==='gpu'&&$max_gpu_len<9999){
                $s=$conn->query("SELECT GPU_Length_mm FROM gpus WHERE component_id=$cid")->fetch_assoc();
                if ($s&&$s['GPU_Length_mm']>$max_gpu_len){$is_compat=false;$wmsg="🚫 Too long for case (max {$max_gpu_len}mm)";}
            }
            if ($ntype==='powersupply'&&$req_min_psu>0){
                $s=$conn->query("SELECT Wattage FROM powersupplies WHERE component_id=$cid")->fetch_assoc();
                if ($s&&$s['Wattage']<$req_min_psu){$is_compat=false;$wmsg="🚫 Too weak – need at least {$req_min_psu}W";}
            }

            if ($compat_only && !$is_compat) continue;
            $shown++;

            $href    = "details.php?id={$cid}&type=" . urlencode($type);
            $img_src = "component_img.php?type=" . urlencode($ntype) . "&brand=" . urlencode($row['Brand']) . "&name=" . urlencode($row['Name']);
            $incompat_class = $is_compat ? '' : 'incompat';

            if ($view_mode === 'list'):
    ?>
        <a href="<?=$is_compat?$href:'#'?>" class="lcard <?=$incompat_class?>">
            <div class="limg"><img src="<?=$img_src?>" alt="<?=htmlspecialchars($row['Name'])?>" loading="lazy"></div>
            <div class="linfo">
                <div class="ltype"><?=htmlspecialchars($type)?></div>
                <div class="lname"><?=htmlspecialchars($row['Name'])?></div>
                <div class="lbrand"><?=htmlspecialchars($row['Brand'])?></div>
            </div>
            <div class="lright">
                <div class="lprice">৳ <?=number_format($row['Price'],2)?></div>
                <div class="lstock">📦 <?=(int)$row['stock_quantity']?> units</div>
                <?php if ($has_ctx):?>
                <div class="lcbadge <?=$is_compat?'ok':'no'?>"><?=$is_compat?'◈ COMPATIBLE':'✕ '.htmlspecialchars(substr($wmsg,3))?></div>
                <?php endif;?>
            </div>
        </a>
    <?php else:?>
        <a href="<?=$is_compat?$href:'#'?>" class="card <?=$incompat_class?>">
            <div class="cimg"><img src="<?=$img_src?>" alt="<?=htmlspecialchars($row['Name'])?>" loading="lazy"></div>
            <span class="tbadge"><?=htmlspecialchars($type)?></span>
            <h3><?=htmlspecialchars($row['Name'])?></h3>
            <div class="brand"><?=htmlspecialchars($row['Brand'])?></div>
            <div class="price">৳ <?=number_format($row['Price'],2)?></div>
            <div class="stock">📦 <?=(int)$row['stock_quantity']?> units</div>
            <?php if (!$is_compat):?><div class="wlbl"><?=htmlspecialchars($wmsg)?></div>
            <?php elseif ($has_ctx):?><div class="clbl">◈ COMPATIBLE</div>
            <?php endif;?>
        </a>
    <?php
            endif;
        endwhile;
    endif;
    ?>
    </div>

    <?php if ($shown === 0):?>
    <div class="empty">
        <h2>// NO COMPONENTS FOUND</h2>
        <p style="margin-top:.5rem;font-size:.85rem;">Adjust your filters or search term.</p>
        <a href="<?=build_url(['search'=>null,'pmin'=>null,'pmax'=>null,'brands'=>null,'instock'=>null,'compat'=>null])?>">↺ Reset filters</a>
    </div>
    <?php endif;?>

</div><!-- content -->
</div><!-- main-wrap -->

<script>
document.getElementById('shownCnt').textContent = <?=$shown?>;
function toggleSec(el){el.classList.toggle('coll');el.nextElementSibling.classList.toggle('hide');}
let debTimer;
function debounceSubmit(){
    clearTimeout(debTimer);
    const v = document.getElementById('pmax').value;
    if (v) document.getElementById('pslider').value = v;
    debTimer = setTimeout(()=>{ document.getElementById('pmin').closest('form').submit(); }, 700);
}
</script>
<?php $conn->close();?>
</body>
</html>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Segoe UI',sans-serif;background:var(--bg);color:var(--white);}

        /* HEADER */
        header{background:linear-gradient(135deg,#042a50,#0b609c);padding:1.5rem 1.5rem 1.2rem;text-align:center;border-bottom:3px solid var(--accent);}
        header h1{font-size:2.2rem;letter-spacing:1px;}
        header p{color:var(--text);margin-top:.3rem;font-size:.95rem;}
        .h-actions{display:flex;justify-content:center;gap:.7rem;margin-top:1rem;flex-wrap:wrap;align-items:center;}
        .btn-cart{background:var(--accent);color:white;padding:9px 22px;border-radius:50px;text-decoration:none;font-weight:bold;font-size:.88rem;transition:background .2s;}
        .btn-cart:hover{background:#0d73ba;}
        .cbadge{background:var(--warn);color:#000;border-radius:50px;padding:2px 8px;font-size:.75rem;font-weight:bold;margin-left:5px;}
        .btn-a{padding:8px 18px;border-radius:50px;text-decoration:none;font-weight:bold;font-size:.85rem;transition:all .2s;}
        .btn-login{border:2px solid var(--badge);color:var(--badge);}
        .btn-login:hover{background:var(--badge);color:var(--bg);}
        .btn-reg{background:var(--success);color:#000;}
        .btn-reg:hover{background:#34d399;}
        .btn-out{border:2px solid var(--danger);color:var(--danger);}
        .btn-out:hover{background:var(--danger);color:white;}
        .welmsg{color:#6ee7b7;font-size:.88rem;font-weight:bold;}
        .prog-wrap{max-width:360px;margin:.8rem auto 0;}
        .prog-lbl{font-size:.8rem;color:var(--text);margin-bottom:5px;}
        .prog-bar{background:rgba(255,255,255,.15);border-radius:20px;height:7px;overflow:hidden;}
        .prog-fill{height:100%;border-radius:20px;background:linear-gradient(90deg,var(--success),#34d399);transition:width .5s;}

        /* TABS */
        .tabs-wrap{max-width:1400px;margin:1rem auto .5rem;padding:0 1rem;overflow-x:auto;}
        .tabs{display:flex;gap:.4rem;white-space:nowrap;}
        .tab{padding:7px 15px;border-radius:50px;text-decoration:none;font-size:.82rem;font-weight:600;border:1px solid var(--accent);color:var(--text);transition:all .2s;flex-shrink:0;}
        .tab:hover,.tab.active{background:var(--accent);color:white;}
        .tab .ct{background:rgba(255,255,255,.2);padding:1px 6px;border-radius:20px;font-size:.7rem;margin-left:3px;}

        /* SLOT BANNER */
        .slot-banner{max-width:1400px;margin:.5rem auto;padding:0 1rem;display:flex;align-items:center;gap:1rem;}
        .slot-badge{background:var(--accent);color:white;padding:7px 16px;border-radius:50px;font-weight:bold;font-size:.83rem;}

        /* LAYOUT */
        .main-wrap{max-width:1400px;margin:0 auto;padding:.5rem 1rem 3rem;display:grid;grid-template-columns:235px 1fr;gap:1.2rem;align-items:start;}

        /* SIDEBAR */
        .sidebar{background:var(--sidebar);border-radius:14px;border:1px solid var(--border);padding:1.2rem;position:sticky;top:1rem;}
        .sb-title{font-size:.72rem;font-weight:bold;text-transform:uppercase;letter-spacing:1.5px;color:var(--badge);margin-bottom:.9rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.3rem;}
        .sb-clear{color:var(--danger);font-size:.75rem;text-decoration:none;}
        .sb-clear:hover{text-decoration:underline;}
        .sb-sec{border-bottom:1px solid var(--border);padding-bottom:.9rem;margin-bottom:.9rem;}
        .sb-sec:last-of-type{border-bottom:none;margin-bottom:0;padding-bottom:0;}
        .sb-sec-title{font-size:.82rem;font-weight:bold;color:var(--text);margin-bottom:.65rem;cursor:pointer;display:flex;align-items:center;justify-content:space-between;user-select:none;}
        .sb-sec-title::after{content:'▲';font-size:.55rem;color:var(--badge);transition:transform .2s;}
        .sb-sec-title.coll::after{transform:rotate(180deg);}
        .sb-body{display:flex;flex-direction:column;gap:.45rem;}
        .sb-body.hide{display:none;}
        .pi-row{display:flex;gap:.4rem;margin-bottom:.5rem;}
        .pi-row input{width:100%;padding:6px 8px;background:#031c34;border:1px solid var(--border);border-radius:6px;color:var(--white);font-size:.8rem;outline:none;}
        .pi-row input:focus{border-color:var(--accent);}
        input[type=range]{width:100%;accent-color:var(--accent);cursor:pointer;margin:.2rem 0;}
        .pr-labels{display:flex;justify-content:space-between;font-size:.7rem;color:var(--badge);}
        .fcheck{display:flex;align-items:center;gap:.45rem;font-size:.82rem;cursor:pointer;color:var(--text);}
        .fcheck:hover{color:var(--white);}
        .fcheck input[type=checkbox]{accent-color:var(--accent);width:13px;height:13px;cursor:pointer;flex-shrink:0;}
        .fcnt{margin-left:auto;background:rgba(114,160,184,.18);color:var(--badge);padding:1px 7px;border-radius:20px;font-size:.7rem;flex-shrink:0;}
        .tog-row{display:flex;align-items:center;justify-content:space-between;}
        .tog-lbl{font-size:.82rem;color:var(--text);}
        .tog{position:relative;width:36px;height:19px;flex-shrink:0;}
        .tog input{opacity:0;width:0;height:0;}
        .tog-sl{position:absolute;inset:0;background:#1e3a5f;border-radius:20px;transition:.3s;cursor:pointer;}
        .tog-sl::before{content:'';position:absolute;width:13px;height:13px;left:3px;top:3px;background:var(--badge);border-radius:50%;transition:.3s;}
        .tog input:checked+.tog-sl{background:var(--accent);}
        .tog input:checked+.tog-sl::before{transform:translateX(17px);background:white;}
        /* apply btn removed - auto submit */

        /* CONTENT */
        .content{min-width:0;}
        .top-bar{background:var(--sidebar);border-radius:12px;border:1px solid var(--border);padding:.85rem 1rem;margin-bottom:.8rem;display:flex;gap:.6rem;align-items:center;flex-wrap:wrap;}
        .s-inp{flex:1;min-width:160px;padding:8px 13px;background:#031c34;border:1px solid var(--border);border-radius:8px;color:var(--white);font-size:.88rem;outline:none;}
        .s-inp:focus{border-color:var(--accent);}
        .s-btn{padding:8px 16px;background:var(--accent);color:white;border:none;border-radius:8px;font-weight:bold;font-size:.85rem;cursor:pointer;white-space:nowrap;}
        .s-btn:hover{background:#0d73ba;}
        .sort-sel{padding:7px 11px;background:#031c34;border:1px solid var(--border);border-radius:8px;color:var(--white);font-size:.83rem;outline:none;cursor:pointer;}
        .view-tog{display:flex;gap:.3rem;flex-shrink:0;}
        .vbtn{padding:7px 11px;background:transparent;border:1px solid var(--border);border-radius:8px;color:var(--badge);cursor:pointer;font-size:.85rem;text-decoration:none;transition:all .2s;}
        .vbtn.active,.vbtn:hover{background:var(--accent);color:white;border-color:var(--accent);}

        /* CHIPS */
        .chips{display:flex;gap:.45rem;flex-wrap:wrap;margin-bottom:.7rem;align-items:center;}
        .chip-lbl{font-size:.75rem;color:var(--badge);}
        .chip{font-size:.75rem;background:rgba(11,96,156,.35);color:var(--text);border:1px solid var(--accent);padding:3px 9px;border-radius:20px;display:flex;align-items:center;gap:4px;}
        .chip a{color:var(--danger);text-decoration:none;font-weight:bold;font-size:.82rem;}
        .chip a:hover{color:white;}

        /* RESULT BAR */
        .res-bar{display:flex;align-items:center;justify-content:space-between;margin-bottom:.8rem;flex-wrap:wrap;gap:.5rem;}
        .res-count{font-size:.83rem;color:var(--badge);}
        .res-count strong{color:var(--white);}
        .cp-pills{display:flex;gap:.35rem;flex-wrap:wrap;}
        .cp-pill{background:rgba(11,96,156,.28);border:1px solid var(--accent);color:var(--text);padding:3px 9px;border-radius:20px;font-size:.72rem;}
        .cp-pill span{color:var(--white);font-weight:bold;}

        /* GRID */
        .pgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(185px,1fr));gap:.9rem;}
        a.card{background:var(--card);border-radius:13px;padding:1.1rem;display:flex;flex-direction:column;text-decoration:none;color:inherit;border:1px solid transparent;transition:all .25s;}
        a.card:hover{transform:translateY(-5px);box-shadow:0 12px 22px rgba(0,0,0,.32);border-color:var(--badge);}
        a.card.incompat{opacity:.38;pointer-events:none;filter:grayscale(40%);}
        .cimg{width:100%;height:120px;display:flex;align-items:center;justify-content:center;background:#042a50;border-radius:9px;margin-bottom:.8rem;overflow:hidden;border:1px solid #1a3a5c;}
        .cimg img{max-width:100%;max-height:110px;object-fit:contain;transition:transform .3s;padding:5px;}
        a.card:hover .cimg img{transform:scale(1.06);}
        .tbadge{display:inline-block;background:var(--badge);color:var(--bg);padding:.2rem .65rem;border-radius:50px;font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:1px;margin-bottom:.55rem;align-self:flex-start;}
        .card h3{font-size:.95rem;margin-bottom:.28rem;line-height:1.3;}
        .card .brand{color:var(--text);font-size:.75rem;text-transform:uppercase;letter-spacing:1px;font-weight:bold;opacity:.8;margin-bottom:.6rem;}
        .card .price{font-size:1.25rem;font-weight:800;color:var(--text);margin-top:auto;padding-top:.7rem;border-top:1px solid rgba(182,205,220,.18);}
        .card .stock{font-size:.72rem;color:var(--badge);margin-top:.28rem;}
        .wlbl{color:#fca5a5;font-size:.72rem;background:rgba(127,29,29,.55);padding:4px 7px;border-radius:6px;margin-top:.6rem;text-align:center;font-weight:bold;border:1px solid var(--danger);}
        .clbl{color:#6ee7b7;font-size:.7rem;margin-top:.55rem;text-align:center;background:rgba(16,185,129,.1);padding:4px 7px;border-radius:6px;border:1px solid var(--success);}

        /* LIST */
        .plist{display:flex;flex-direction:column;gap:.6rem;}
        a.lcard{background:var(--card);border-radius:11px;padding:.9rem 1.1rem;display:flex;align-items:center;gap:.9rem;text-decoration:none;color:inherit;border:1px solid transparent;transition:all .2s;}
        a.lcard:hover{border-color:var(--badge);background:#0a3a60;}
        a.lcard.incompat{opacity:.38;pointer-events:none;filter:grayscale(40%);}
        .limg{width:62px;height:62px;flex-shrink:0;background:#042a50;border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden;border:1px solid #1a3a5c;}
        .limg img{max-width:100%;max-height:56px;object-fit:contain;padding:3px;}
        .linfo{flex:1;min-width:0;}
        .ltype{font-size:.68rem;text-transform:uppercase;letter-spacing:1px;color:var(--badge);font-weight:bold;margin-bottom:.18rem;}
        .lname{font-size:.93rem;font-weight:bold;margin-bottom:.18rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .lbrand{font-size:.75rem;color:var(--text);opacity:.8;}
        .lright{display:flex;flex-direction:column;align-items:flex-end;gap:.28rem;flex-shrink:0;}
        .lprice{font-size:1.12rem;font-weight:800;color:var(--text);}
        .lstock{font-size:.72rem;color:var(--badge);}
        .lcbadge{font-size:.7rem;padding:3px 8px;border-radius:6px;font-weight:bold;}
        .lcbadge.ok{background:rgba(16,185,129,.14);color:#6ee7b7;border:1px solid var(--success);}
        .lcbadge.no{background:rgba(239,68,68,.14);color:#fca5a5;border:1px solid var(--danger);}

        /* EMPTY */
        .empty{text-align:center;padding:3rem 1rem;color:var(--text);}
        .empty h2{font-size:1.4rem;margin-bottom:.5rem;}

        /* MOBILE */
        .mob-filter-btn{display:none;width:100%;padding:10px;background:var(--sidebar);border:1px solid var(--border);border-radius:10px;color:var(--white);font-weight:bold;font-size:.88rem;cursor:pointer;margin-bottom:.8rem;align-items:center;justify-content:center;gap:.4rem;}
        @media(max-width:820px){
            .main-wrap{grid-template-columns:1fr;}
            .sidebar{display:none;}
            .sidebar.open{display:block;}
            .mob-filter-btn{display:flex;}
            .pgrid{grid-template-columns:repeat(auto-fill,minmax(155px,1fr));}
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header>
    <h1>⚡ NextGen PC Builder</h1>
    <p>Select components and build your perfect machine.</p>
    <div class="h-actions">
        <a href="build.php" class="btn-cart">🛒 View My Build<?php if ($build_count > 0): ?><span class="cbadge"><?= $build_count ?>/7</span><?php endif; ?></a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="my_builds.php" class="btn-a btn-login" style="font-size:.8rem;">📂 My Builds</a>
            <a href="my_orders.php" class="btn-a btn-login" style="font-size:.8rem;">📦 My Orders</a>
            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                <a href="admin/admin_dashboard.php" class="btn-a btn-login" style="font-size:.8rem;border-color:#f59e0b;color:#f59e0b;">🔑 Admin</a>
            <?php endif; ?>
            <span class="welmsg">👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="logout.php" class="btn-a btn-out">Sign Out</a>
        <?php else: ?>
            <a href="login.php"    class="btn-a btn-login">Sign In</a>
            <a href="register.php" class="btn-a btn-reg">Register</a>
        <?php endif; ?>
    </div>
    <?php if (isset($_GET['welcome'])): ?>
    <div style="margin-top:.6rem;"><span style="background:rgba(16,185,129,.15);border:1px solid var(--success);color:#6ee7b7;padding:6px 18px;border-radius:50px;font-size:.83rem;font-weight:bold;">🎉 Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</span></div>
    <?php endif; ?>
    <?php if ($build_count > 0): ?>
    <div class="prog-wrap">
        <div class="prog-lbl"><?= $build_count ?>/7 components selected</div>
        <div class="prog-bar"><div class="prog-fill" style="width:<?= round($build_count/7*100) ?>%"></div></div>
    </div>
    <?php endif; ?>
</header>

<!-- CATEGORY TABS -->
<div class="tabs-wrap"><div class="tabs">
<?php
$tab_defs = ['all'=>'🖥️ All','cpu'=>'🔲 CPU','motherboard'=>'📋 Motherboard','ram'=>'💾 RAM','gpu'=>'🎮 GPU','powersupply'=>'⚡ PSU','storage'=>'💿 Storage','case'=>'📦 Case'];
foreach ($tab_defs as $key => $label):
    $cnt    = $cat_counts[$key] ?? 0;
    $active = $filter === $key ? 'active' : '';
    $href   = build_url(['filter'=>$key==='all'?null:$key,'pmin'=>null,'pmax'=>null,'brands'=>null,'instock'=>null]);
?>
<a href="<?= $href ?>" class="tab <?= $active ?>"><?= $label ?><?php if ($cnt>0):?><span class="ct"><?= $cnt ?></span><?php endif;?></a>
<?php endforeach; ?>
</div></div>

<!-- SLOT BANNER -->
<?php if ($target_slot): ?>
<div class="slot-banner"><span class="slot-badge">🔍 Choosing: <?= strtoupper($target_slot) ?></span><a href="index.php" style="color:var(--text);font-size:.83rem;">← Back to all</a></div>
<?php endif; ?>

<!-- MAIN LAYOUT -->
<div class="main-wrap">

<!-- MOBILE FILTER BUTTON -->
<button class="mob-filter-btn" onclick="document.getElementById('sb').classList.toggle('open')">
    ⚙️ Filters <?php if ($active_filters>0):?><span class="cbadge"><?= $active_filters ?></span><?php endif;?>
</button>

<!-- FILTER SIDEBAR FORM -->
<form method="GET" action="index.php">
<?php if ($filter!=='all'):?><input type="hidden" name="filter" value="<?=htmlspecialchars($filter)?>"><?php endif;?>
<?php if ($target_slot):?><input type="hidden" name="slot" value="<?=htmlspecialchars($target_slot)?>"><?php endif;?>
<?php if ($search):?><input type="hidden" name="search" value="<?=htmlspecialchars($search)?>"><?php endif;?>
<?php if ($sort!=='default'):?><input type="hidden" name="sort" value="<?=htmlspecialchars($sort)?>"><?php endif;?>
<?php if ($view_mode==='list'):?><input type="hidden" name="view" value="list"><?php endif;?>

<aside class="sidebar" id="sb">
    <div class="sb-title">
        Filters
        <?php if ($active_filters>0):?>
            <span style="background:var(--accent);color:white;padding:1px 8px;border-radius:20px;font-size:.7rem;"><?=$active_filters?> active</span>
            <a href="<?=build_url(['pmin'=>null,'pmax'=>null,'brands'=>null,'instock'=>null,'compat'=>null])?>" class="sb-clear">Clear all</a>
        <?php endif;?>
    </div>

    <!-- PRICE -->
    <div class="sb-sec">
        <div class="sb-sec-title" onclick="toggleSec(this)">Price Range (৳)</div>
        <div class="sb-body">
            <div class="pi-row">
                <input type="number" name="pmin" id="pmin" value="<?=$price_min?>" placeholder="Min" min="0" oninput="debounceSubmit()">
                <input type="number" name="pmax" id="pmax" value="<?=$price_max?>" placeholder="Max" min="0" oninput="debounceSubmit()">
            </div>
            <input type="range" id="pslider" min="<?=$cat_pmin?>" max="<?=$cat_pmax?>" value="<?=$price_max?>" oninput="document.getElementById('pmax').value=this.value" onchange="this.form.submit()">
            <div class="pr-labels"><span>৳<?=number_format($cat_pmin)?></span><span>৳<?=number_format($cat_pmax)?></span></div>
        </div>
    </div>

    <!-- BRAND -->
    <?php if (!empty($all_brands)):?>
    <div class="sb-sec">
        <div class="sb-sec-title" onclick="toggleSec(this)">Brand</div>
        <div class="sb-body">
            <?php foreach ($all_brands as $b):?>
            <label class="fcheck">
                <input type="checkbox" name="brands[]" value="<?=htmlspecialchars($b['Brand'])?>" <?=in_array($b['Brand'],$brands_sel)?'checked':''?> onchange="this.form.submit()">
                <?=htmlspecialchars($b['Brand'])?>
                <span class="fcnt"><?=$b['cnt']?></span>
            </label>
            <?php endforeach;?>
        </div>
    </div>
    <?php endif;?>

    <!-- IN STOCK -->
    <div class="sb-sec">
        <div class="sb-sec-title" onclick="toggleSec(this)">Availability</div>
        <div class="sb-body">
            <div class="tog-row">
                <span class="tog-lbl">In Stock only</span>
                <label class="tog"><input type="checkbox" name="instock" value="1" <?=$in_stock?'checked':''?> onchange="this.form.submit()"><span class="tog-sl"></span></label>
            </div>
        </div>
    </div>

    <!-- COMPAT ONLY -->
    <?php if ($req_socket||$req_ram_type||$req_form_factor||$req_min_psu>0):?>
    <div class="sb-sec">
        <div class="sb-sec-title" onclick="toggleSec(this)">Compatibility</div>
        <div class="sb-body">
            <div class="tog-row">
                <span class="tog-lbl">Compatible only</span>
                <label class="tog"><input type="checkbox" name="compat" value="1" <?=$compat_only?'checked':''?> onchange="this.form.submit()"><span class="tog-sl"></span></label>
            </div>
        </div>
    </div>
    <?php endif;?>

    <!-- no apply button needed — auto submits -->
</aside>
</form>

<!-- RIGHT CONTENT -->
<div class="content">

    <!-- TOP BAR -->
    <form method="GET" action="index.php" class="top-bar">
        <?php if ($filter!=='all'):?><input type="hidden" name="filter" value="<?=htmlspecialchars($filter)?>"><?php endif;?>
        <?php if ($target_slot):?><input type="hidden" name="slot" value="<?=htmlspecialchars($target_slot)?>"><?php endif;?>
        <?php if ($price_min>0):?><input type="hidden" name="pmin" value="<?=$price_min?>"><?php endif;?>
        <?php if ($price_max<$cat_pmax):?><input type="hidden" name="pmax" value="<?=$price_max?>"><?php endif;?>
        <?php foreach ($brands_sel as $bs):?><input type="hidden" name="brands[]" value="<?=htmlspecialchars($bs)?>"><?php endforeach;?>
        <?php if ($in_stock):?><input type="hidden" name="instock" value="1"><?php endif;?>
        <?php if ($compat_only):?><input type="hidden" name="compat" value="1"><?php endif;?>
        <?php if ($view_mode==='list'):?><input type="hidden" name="view" value="list"><?php endif;?>

        <input type="text" name="search" class="s-inp" placeholder="🔍 Search by name or brand..." value="<?=htmlspecialchars($search)?>">
        <button type="submit" class="s-btn">Search</button>
        <select name="sort" class="sort-sel" onchange="this.form.submit()">
            <option value="default"   <?=$sort==='default'   ?'selected':''?>>Sort: Default</option>
            <option value="price_asc" <?=$sort==='price_asc' ?'selected':''?>>Price: Low → High</option>
            <option value="price_desc"<?=$sort==='price_desc'?'selected':''?>>Price: High → Low</option>
            <option value="name_asc"  <?=$sort==='name_asc'  ?'selected':''?>>Name: A → Z</option>
            <option value="name_desc" <?=$sort==='name_desc' ?'selected':''?>>Name: Z → A</option>
            <option value="stock"     <?=$sort==='stock'     ?'selected':''?>>Stock: Most first</option>
        </select>
        <div class="view-tog">
            <a href="<?=build_url(['view'=>null])?>" class="vbtn <?=$view_mode==='grid'?'active':''?>" title="Grid">▦</a>
            <a href="<?=build_url(['view'=>'list'])?>" class="vbtn <?=$view_mode==='list'?'active':''?>" title="List">☰</a>
        </div>
    </form>

    <!-- ACTIVE FILTER CHIPS -->
    <?php $has_chips = !empty($brands_sel)||$price_min>$cat_pmin||$price_max<$cat_pmax||$in_stock||$compat_only||!empty($search); ?>
    <?php if ($has_chips):?>
    <div class="chips">
        <span class="chip-lbl">Active:</span>
        <?php if (!empty($search)):?><span class="chip">🔍 "<?=htmlspecialchars($search)?>" <a href="<?=build_url(['search'=>null])?>">×</a></span><?php endif;?>
        <?php if ($price_min>$cat_pmin||$price_max<$cat_pmax):?><span class="chip">৳<?=number_format($price_min)?>–৳<?=number_format($price_max)?> <a href="<?=build_url(['pmin'=>null,'pmax'=>null])?>">×</a></span><?php endif;?>
        <?php foreach ($brands_sel as $bs):?><span class="chip"><?=htmlspecialchars($bs)?> <a href="<?=build_url(['brands'=>array_values(array_diff($brands_sel,[$bs]))])?>">×</a></span><?php endforeach;?>
        <?php if ($in_stock):?><span class="chip">In Stock <a href="<?=build_url(['instock'=>null])?>">×</a></span><?php endif;?>
        <?php if ($compat_only):?><span class="chip">Compatible only <a href="<?=build_url(['compat'=>null])?>">×</a></span><?php endif;?>
    </div>
    <?php endif;?>

    <!-- RESULT COUNT + COMPAT PILLS -->
    <div class="res-bar">
        <div class="res-count">Showing <strong id="shownCnt">—</strong> of <strong><?=$total_before_compat?></strong> <?=$filter!=='all'?strtoupper($filter):''?> components</div>
        <?php if ($req_socket||$req_ram_type||$req_form_factor||$req_min_psu>0):?>
        <div class="cp-pills">
            <?php if ($req_socket):?><span class="cp-pill">Socket: <span><?=$req_socket?></span></span><?php endif;?>
            <?php if ($req_ram_type):?><span class="cp-pill">RAM: <span><?=$req_ram_type?></span></span><?php endif;?>
            <?php if ($req_form_factor):?><span class="cp-pill">Form: <span><?=$req_form_factor?></span></span><?php endif;?>
            <?php if ($req_min_psu>0):?><span class="cp-pill">PSU: <span>≥<?=$req_min_psu?>W</span></span><?php endif;?>
        </div>
        <?php endif;?>
    </div>

    <!-- PRODUCTS -->
    <div class="<?=$view_mode==='list'?'plist':'pgrid'?>" id="prodCont">
    <?php
    $shown = 0;
    if ($result && $result->num_rows > 0):
        while ($row = $result->fetch_assoc()):
            $cid   = $row['component_id'];
            $type  = $row['Type'];
            $ntype = str_replace(' ', '', strtolower($type));

            $is_compat = true; $wmsg = "";
            $has_ctx   = ($req_socket||$req_ram_type||$req_form_factor||$req_min_psu>0||$max_gpu_len<9999);

            if ($ntype==='cpu' && $req_socket) {
                $s=$conn->query("SELECT Socket FROM cpus WHERE component_id=$cid")->fetch_assoc();
                if ($s&&$s['Socket']!==$req_socket){$is_compat=false;$wmsg="🚫 Socket mismatch – needs {$req_socket}";}
            }
            if ($ntype==='motherboard') {
                $s=$conn->query("SELECT Socket,supported_ram_type,Form_Factor FROM motherboards WHERE component_id=$cid")->fetch_assoc();
                if ($s){
                    if ($req_socket&&$s['Socket']!==$req_socket){$is_compat=false;$wmsg="🚫 Socket mismatch – needs {$req_socket}";}
                    elseif ($req_ram_type&&$s['supported_ram_type']!==$req_ram_type){$is_compat=false;$wmsg="🚫 RAM mismatch – needs {$req_ram_type}";}
                    elseif ($req_form_factor&&!ff_compatible($req_form_factor,$s['Form_Factor'])){$is_compat=false;$wmsg="🚫 Won't fit in {$req_form_factor} case";}
                }
            }
            if ($ntype==='ram'&&$req_ram_type){
                $s=$conn->query("SELECT DDR_Version FROM rams WHERE component_id=$cid")->fetch_assoc();
                if ($s&&$s['DDR_Version']!==$req_ram_type){$is_compat=false;$wmsg="🚫 Needs {$req_ram_type} motherboard";}
            }
            if ($ntype==='case'){
                $s=$conn->query("SELECT Form_Factor FROM cases WHERE component_id=$cid")->fetch_assoc();
                if ($s&&$req_form_factor&&!ff_compatible($s['Form_Factor'],$req_form_factor)){$is_compat=false;$wmsg="🚫 Motherboard won't fit ({$req_form_factor})";}
            }
            if ($ntype==='gpu'&&$max_gpu_len<9999){
                $s=$conn->query("SELECT GPU_Length_mm FROM gpus WHERE component_id=$cid")->fetch_assoc();
                if ($s&&$s['GPU_Length_mm']>$max_gpu_len){$is_compat=false;$wmsg="🚫 Too long for case (max {$max_gpu_len}mm)";}
            }
            if ($ntype==='powersupply'&&$req_min_psu>0){
                $s=$conn->query("SELECT Wattage FROM powersupplies WHERE component_id=$cid")->fetch_assoc();
                if ($s&&$s['Wattage']<$req_min_psu){$is_compat=false;$wmsg="🚫 Too weak – need at least {$req_min_psu}W";}
            }

            if ($compat_only && !$is_compat) continue;
            $shown++;

            $href    = "details.php?id={$cid}&type=" . urlencode($type);
            $img_src = "component_img.php?type=" . urlencode($ntype) . "&brand=" . urlencode($row['Brand']) . "&name=" . urlencode($row['Name']);
            $incompat_class = $is_compat ? '' : 'incompat';

            if ($view_mode === 'list'):
    ?>
        <a href="<?=$is_compat?$href:'#'?>" class="lcard <?=$incompat_class?>">
            <div class="limg"><img src="<?=$img_src?>" alt="<?=htmlspecialchars($row['Name'])?>" loading="lazy"></div>
            <div class="linfo">
                <div class="ltype"><?=htmlspecialchars($type)?></div>
                <div class="lname"><?=htmlspecialchars($row['Name'])?></div>
                <div class="lbrand"><?=htmlspecialchars($row['Brand'])?></div>
            </div>
            <div class="lright">
                <div class="lprice">৳ <?=number_format($row['Price'],2)?></div>
                <div class="lstock">📦 <?=(int)$row['stock_quantity']?> units</div>
                <?php if ($has_ctx):?>
                <div class="lcbadge <?=$is_compat?'ok':'no'?>"><?=$is_compat?'✅ Compatible':'🚫 '.htmlspecialchars(substr($wmsg,3))?></div>
                <?php endif;?>
            </div>
        </a>
    <?php else:?>
        <a href="<?=$is_compat?$href:'#'?>" class="card <?=$incompat_class?>">
            <div class="cimg"><img src="<?=$img_src?>" alt="<?=htmlspecialchars($row['Name'])?>" loading="lazy"></div>
            <span class="tbadge"><?=htmlspecialchars($type)?></span>
            <h3><?=htmlspecialchars($row['Name'])?></h3>
            <div class="brand"><?=htmlspecialchars($row['Brand'])?></div>
            <div class="price">৳ <?=number_format($row['Price'],2)?></div>
            <div class="stock">📦 <?=(int)$row['stock_quantity']?> units</div>
            <?php if (!$is_compat):?><div class="wlbl"><?=htmlspecialchars($wmsg)?></div>
            <?php elseif ($has_ctx):?><div class="clbl">✅ Compatible with your build</div>
            <?php endif;?>
        </a>
    <?php
            endif;
        endwhile;
    endif;
    ?>
    </div>

    <?php if ($shown === 0):?>
    <div class="empty">
        <h2>😕 No components found</h2>
        <p style="margin-top:.5rem;">Try adjusting your filters or search term.</p>
        <a href="<?=build_url(['search'=>null,'pmin'=>null,'pmax'=>null,'brands'=>null,'instock'=>null,'compat'=>null])?>" style="display:inline-block;margin-top:1rem;color:var(--accent);">↺ Reset all filters</a>
    </div>
    <?php endif;?>

</div><!-- content -->
</div><!-- main-wrap -->

<script>
document.getElementById('shownCnt').textContent = <?=$shown?>;

// Collapse/expand filter sections
function toggleSec(el) {
    el.classList.toggle('coll');
    el.nextElementSibling.classList.toggle('hide');
}

// Debounce for price text inputs — waits 700ms after user stops typing then submits
let debTimer;
function debounceSubmit() {
    clearTimeout(debTimer);
    // Update slider to match pmax input live
    const pmaxVal = document.getElementById('pmax').value;
    if (pmaxVal) document.getElementById('pslider').value = pmaxVal;
    debTimer = setTimeout(() => {
        document.getElementById('pmin').closest('form').submit();
    }, 700);
}
</script>
<?php $conn->close();?>
</body>
</html>