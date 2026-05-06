<?php
require "db_config.php";
// ============================================================
// SmartFarm - Smart Farming Management Application
// Backend PHP Logic
// ============================================================

session_start();

// ---- Database simulation (In production, use MySQL/PDO) ----
// Sample data arrays acting as DB for demo

// Handle POST actions FIRST so the data is available for the fetch below
$action_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add_sale') {
            $stmt = $pdo->prepare("INSERT INTO sales (product, qty, unit, price_unit, date, buyer, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['product'], $_POST['qty'], $_POST['unit'], $_POST['price_unit'], $_POST['date'], $_POST['buyer'], $_POST['status']]);
            $action_msg = "✓ Sale recorded successfully!";
        } 
        elseif ($action === 'add_inventory') {
            $stmt = $pdo->prepare("INSERT INTO inventory (item, category, qty, unit, min_qty, cost, supplier) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['item'], $_POST['category'], $_POST['qty'], $_POST['unit'], $_POST['min_qty'], $_POST['cost'], $_POST['supplier']]);
            $action_msg = "✓ Inventory item added!";
        } 
        elseif ($action === 'add_employee') {
            $stmt = $pdo->prepare("INSERT INTO employees (name, role, salary, phone, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['name'], $_POST['role'], $_POST['salary'], $_POST['phone'], $_POST['status']]);
            $action_msg = "✓ Employee added to system!";
        } 
        elseif ($action === 'add_task') {
            $stmt = $pdo->prepare("INSERT INTO tasks (title, due, priority, assigned, status, location) VALUES (?, ?, ?, ?, 'pending', ?)");
            $stmt->execute([$_POST['title'], $_POST['due'], $_POST['priority'], $_POST['assigned'], $_POST['location']]);
            $action_msg = "✓ Task scheduled!";
        }
    } catch (PDOException $e) {
        $action_msg = "❌ Error: " . $e->getMessage();
    }
}

// ---- FETCH LIVE DATA FROM DATABASE ----
$employees = $pdo->query("SELECT * FROM employees")->fetchAll();
$inventory = $pdo->query("SELECT * FROM inventory")->fetchAll();
$sales     = $pdo->query("SELECT * FROM sales ORDER BY date DESC")->fetchAll();
$tasks     = $pdo->query("SELECT * FROM tasks ORDER BY due ASC")->fetchAll();

// Note: Events are still static for now as they are usually fixed calendar items
$events = [
    ['id'=>1,'title'=>'Agri-Expo Kampala','date'=>'2026-05-15','type'=>'exhibition','desc'=>'Annual agricultural exhibition at Kololo Grounds'],
    ['id'=>2,'title'=>'Vet Visit - Livestock','date'=>'2026-05-08','type'=>'appointment','desc'=>'Quarterly veterinary inspection'],
    ['id'=>3,'title'=>'Board Meeting','date'=>'2026-05-12','type'=>'meeting','desc'=>'Q2 Performance review meeting'],
    ['id'=>4,'title'=>'Supplier Delivery - Seeds','date'=>'2026-05-07','type'=>'delivery','desc'=>'SeedCo hybrid maize seeds delivery'],
];

// ---- COMPUTED STATS (Now based on DB data) ----
$total_sales = array_sum(array_map(fn($s) => $s['qty'] * $s['price_unit'], $sales));
$completed_sales = array_filter($sales, fn($s) => $s['status'] === 'completed');
$total_completed = array_sum(array_map(fn($s) => $s['qty'] * $s['price_unit'], $completed_sales));

// Alerts and Filters
$shortage_items = array_filter($inventory, fn($i) => $i['qty'] < $i['min_qty']);
$today = date('Y-m-d');
$today_tasks = array_filter($tasks, fn($t) => $t['due'] === $today && $t['status'] !== 'completed');

// Payroll and Staff
$total_payroll = array_sum(array_column($employees, 'salary'));
$active_employees = count(array_filter($employees, fn($e) => $e['status'] === 'active'));

$active_tab = $_GET['tab'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SmartFarm — Intelligent Farm Management</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
/* ========== CSS VARIABLES & RESET ========== */
:root {
  --bg:        #0b1a12;
  --bg2:       #0f2318;
  --bg3:       #132b1c;
  --surface:   #172f20;
  --surface2:  #1d3a28;
  --border:    #2a5438;
  --border2:   #1e4030;
  --green:     #3dffa0;
  --green2:    #29e880;
  --green3:    #1ab565;
  --green-dim: #1a7a46;
  --amber:     #ffca3a;
  --red:       #ff5c5c;
  --blue:      #5ce8ff;
  --purple:    #b97dff;
  --text:      #e8f5ec;
  --text2:     #9bbfa8;
  --text3:     #5a8c6a;
  --radius:    12px;
  --radius-lg: 20px;
  --shadow:    0 4px 24px rgba(0,0,0,0.4);
  --shadow-lg: 0 8px 48px rgba(0,0,0,0.6);
  --font-head: 'Syne', sans-serif;
  --font-body: 'DM Sans', sans-serif;
  --font-mono: 'JetBrains Mono', monospace;
  --sidebar-w: 240px;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
  background: var(--bg);
  color: var(--text);
  font-family: var(--font-body);
  font-size: 14px;
  line-height: 1.6;
  min-height: 100vh;
  overflow-x: hidden;
}

/* ========== SCROLLBAR ========== */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: var(--bg2); }
::-webkit-scrollbar-thumb { background: var(--border); border-radius: 99px; }

/* ========== LAYOUT ========== */
.app { display: flex; min-height: 100vh; }

/* ========== SIDEBAR ========== */
.sidebar {
  width: var(--sidebar-w);
  background: var(--bg2);
  border-right: 1px solid var(--border2);
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0; left: 0;
  height: 100vh;
  z-index: 100;
  overflow-y: auto;
}
.sidebar-logo {
  padding: 24px 20px 16px;
  border-bottom: 1px solid var(--border2);
}
.logo-mark {
  display: flex; align-items: center; gap: 10px;
}
.logo-icon {
  width: 36px; height: 36px;
  background: linear-gradient(135deg, var(--green), var(--green3));
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}
.logo-text {
  font-family: var(--font-head);
  font-size: 18px; font-weight: 800;
  color: var(--text);
  letter-spacing: -0.5px;
}
.logo-text span { color: var(--green); }
.logo-sub {
  font-size: 10px; color: var(--text3);
  font-family: var(--font-mono);
  letter-spacing: 2px;
  text-transform: uppercase;
  margin-top: 2px;
}

.sidebar-nav { flex: 1; padding: 12px 0; }
.nav-section {
  padding: 8px 16px 4px;
  font-size: 10px;
  font-family: var(--font-mono);
  letter-spacing: 2px;
  color: var(--text3);
  text-transform: uppercase;
}
.nav-item {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 20px;
  color: var(--text2);
  text-decoration: none;
  font-size: 13.5px;
  font-weight: 500;
  border-left: 3px solid transparent;
  transition: all 0.2s;
  position: relative;
}
.nav-item:hover {
  background: var(--surface);
  color: var(--text);
}
.nav-item.active {
  background: linear-gradient(90deg, rgba(61,255,160,0.08), transparent);
  border-left-color: var(--green);
  color: var(--green);
}
.nav-item .icon { font-size: 16px; width: 20px; text-align: center; }
.nav-badge {
  margin-left: auto;
  background: var(--red);
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 99px;
  font-family: var(--font-mono);
}
.nav-badge.amber { background: var(--amber); color: #000; }
.nav-badge.green { background: var(--green3); color: #fff; }

.sidebar-footer {
  padding: 16px 20px;
  border-top: 1px solid var(--border2);
}
.user-card {
  display: flex; align-items: center; gap: 10px;
}
.user-avatar {
  width: 34px; height: 34px; border-radius: 50%;
  background: linear-gradient(135deg, var(--green3), var(--green-dim));
  display: flex; align-items: center; justify-content: center;
  font-size: 14px; font-weight: 700;
  color: #fff; flex-shrink: 0;
}
.user-name { font-size: 13px; font-weight: 600; }
.user-role { font-size: 11px; color: var(--text3); }

/* ========== MAIN CONTENT ========== */
.main {
  margin-left: var(--sidebar-w);
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* ========== TOP BAR ========== */
.topbar {
  background: var(--bg2);
  border-bottom: 1px solid var(--border2);
  padding: 0 28px;
  height: 60px;
  display: flex; align-items: center; justify-content: space-between;
  position: sticky; top: 0; z-index: 50;
  backdrop-filter: blur(12px);
}
.topbar-left { display: flex; align-items: center; gap: 12px; }
.page-title {
  font-family: var(--font-head);
  font-size: 18px; font-weight: 700;
  color: var(--text);
}
.breadcrumb {
  font-size: 12px; color: var(--text3);
  font-family: var(--font-mono);
}
.topbar-right { display: flex; align-items: center; gap: 12px; }
.topbar-time {
  font-family: var(--font-mono);
  font-size: 13px;
  color: var(--green);
  background: rgba(61,255,160,0.06);
  padding: 6px 12px;
  border-radius: 8px;
  border: 1px solid rgba(61,255,160,0.15);
}
.alert-bell {
  position: relative;
  cursor: pointer;
  font-size: 18px;
  padding: 6px;
}
.alert-dot {
  position: absolute; top: 4px; right: 4px;
  width: 8px; height: 8px;
  background: var(--red);
  border-radius: 50%;
  animation: pulse 2s infinite;
}
@keyframes pulse {
  0%,100% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.4); opacity: 0.7; }
}

/* ========== PAGE CONTENT ========== */
.content { padding: 28px; flex: 1; }

/* ========== ALERT BANNER ========== */
.alert-banner {
  background: linear-gradient(90deg, rgba(255,92,92,0.12), rgba(255,202,58,0.08));
  border: 1px solid rgba(255,92,92,0.3);
  border-radius: var(--radius);
  padding: 12px 18px;
  margin-bottom: 24px;
  display: flex; align-items: center; gap: 12px;
  font-size: 13px;
}
.alert-banner .icon { font-size: 18px; }
.alert-banner strong { color: var(--red); }

.success-banner {
  background: rgba(61,255,160,0.08);
  border: 1px solid rgba(61,255,160,0.25);
  border-radius: var(--radius);
  padding: 12px 18px;
  margin-bottom: 24px;
  display: flex; align-items: center; gap: 12px;
  font-size: 13px;
  color: var(--green);
  animation: fadeIn 0.4s ease;
}
@keyframes fadeIn { from { opacity:0; transform: translateY(-6px); } to { opacity:1; transform:none; } }

/* ========== STATS GRID ========== */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 28px;
}
.stat-card {
  background: var(--surface);
  border: 1px solid var(--border2);
  border-radius: var(--radius-lg);
  padding: 20px;
  position: relative;
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow); }
.stat-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0; height: 2px;
}
.stat-card.green::before { background: linear-gradient(90deg, var(--green), transparent); }
.stat-card.amber::before { background: linear-gradient(90deg, var(--amber), transparent); }
.stat-card.red::before { background: linear-gradient(90deg, var(--red), transparent); }
.stat-card.blue::before { background: linear-gradient(90deg, var(--blue), transparent); }
.stat-card.purple::before { background: linear-gradient(90deg, var(--purple), transparent); }

.stat-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
.stat-label { font-size: 12px; color: var(--text3); font-weight: 500; text-transform: uppercase; letter-spacing: 1px; font-family: var(--font-mono); }
.stat-icon { font-size: 20px; opacity: 0.7; }
.stat-value { font-family: var(--font-head); font-size: 28px; font-weight: 800; line-height: 1; margin-bottom: 6px; }
.stat-card.green .stat-value { color: var(--green); }
.stat-card.amber .stat-value { color: var(--amber); }
.stat-card.red .stat-value { color: var(--red); }
.stat-card.blue .stat-value { color: var(--blue); }
.stat-card.purple .stat-value { color: var(--purple); }
.stat-sub { font-size: 12px; color: var(--text3); }
.stat-trend {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 11px; font-weight: 600;
  background: rgba(61,255,160,0.1);
  color: var(--green);
  padding: 2px 8px; border-radius: 99px;
  margin-top: 6px;
}
.stat-trend.down { background: rgba(255,92,92,0.1); color: var(--red); }

/* ========== SECTION HEADER ========== */
.section-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 16px;
}
.section-title {
  font-family: var(--font-head);
  font-size: 16px; font-weight: 700;
  display: flex; align-items: center; gap: 10px;
}
.section-title .dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  background: var(--green);
}

/* ========== WEATHER WIDGET ========== */
.weather-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 28px;
}
.weather-main {
  background: var(--surface);
  border: 1px solid var(--border2);
  border-radius: var(--radius-lg);
  padding: 24px;
  background-image: radial-gradient(ellipse at top right, rgba(61,255,160,0.05) 0%, transparent 60%);
}
.weather-location {
  display: flex; align-items: center; gap: 8px;
  font-size: 12px; color: var(--text3); font-family: var(--font-mono);
  margin-bottom: 12px;
}
.weather-current {
  display: flex; align-items: center; gap: 16px;
  margin-bottom: 16px;
}
.weather-temp {
  font-family: var(--font-head);
  font-size: 52px; font-weight: 800;
  color: var(--green);
  line-height: 1;
}
.weather-icon-big { font-size: 44px; }
.weather-desc { font-size: 16px; font-weight: 600; margin-bottom: 4px; }
.weather-meta { display: flex; gap: 16px; margin-top: 12px; }
.weather-meta-item { display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--text2); }

.weather-forecast {
  background: var(--surface);
  border: 1px solid var(--border2);
  border-radius: var(--radius-lg);
  padding: 20px;
}
.forecast-title {
  font-size: 12px; color: var(--text3);
  font-family: var(--font-mono);
  letter-spacing: 1px; text-transform: uppercase;
  margin-bottom: 16px;
}
.forecast-days { display: flex; flex-direction: column; gap: 10px; }
.forecast-day {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 14px;
  background: var(--bg3);
  border-radius: 10px;
  border: 1px solid var(--border2);
}
.forecast-day-name { font-size: 12px; font-weight: 600; width: 70px; }
.forecast-day-icon { font-size: 18px; }
.forecast-day-desc { font-size: 12px; color: var(--text3); flex: 1; text-align: center; }
.forecast-temps { display: flex; gap: 8px; align-items: center; font-size: 13px; font-weight: 600; }
.forecast-temps .low { color: var(--text3); font-weight: 400; }
.forecast-day-rain { font-size: 11px; color: var(--blue); font-family: var(--font-mono); }

/* ========== DASHBOARD GRID ========== */
.dash-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 28px;
}
.dash-grid.three { grid-template-columns: 1fr 1fr 1fr; }
@media (max-width: 1100px) { .dash-grid { grid-template-columns: 1fr; } }

/* ========== CARD ========== */
.card {
  background: var(--surface);
  border: 1px solid var(--border2);
  border-radius: var(--radius-lg);
  overflow: hidden;
}
.card-header {
  padding: 16px 20px;
  border-bottom: 1px solid var(--border2);
  display: flex; align-items: center; justify-content: space-between;
}
.card-title { font-family: var(--font-head); font-size: 14px; font-weight: 700; }
.card-body { padding: 16px 20px; }

/* ========== TABLE ========== */
.data-table { width: 100%; border-collapse: collapse; }
.data-table th {
  font-family: var(--font-mono);
  font-size: 10px; font-weight: 500;
  text-transform: uppercase; letter-spacing: 1.5px;
  color: var(--text3);
  padding: 8px 12px;
  text-align: left;
  border-bottom: 1px solid var(--border2);
}
.data-table td {
  padding: 11px 12px;
  font-size: 13px;
  border-bottom: 1px solid rgba(42,84,56,0.3);
  vertical-align: middle;
}
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: rgba(61,255,160,0.03); }

/* ========== BADGES ========== */
.badge {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 3px 10px;
  border-radius: 99px;
  font-size: 11px; font-weight: 600;
  font-family: var(--font-mono);
}
.badge-green { background: rgba(61,255,160,0.1); color: var(--green2); }
.badge-amber { background: rgba(255,202,58,0.12); color: var(--amber); }
.badge-red { background: rgba(255,92,92,0.1); color: var(--red); }
.badge-blue { background: rgba(92,232,255,0.1); color: var(--blue); }
.badge-purple { background: rgba(185,125,255,0.1); color: var(--purple); }
.badge-gray { background: rgba(255,255,255,0.06); color: var(--text3); }

/* ========== TASKS LIST ========== */
.task-item {
  display: flex; align-items: flex-start; gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid rgba(42,84,56,0.3);
}
.task-item:last-child { border-bottom: none; }
.task-priority {
  width: 4px; min-height: 40px; border-radius: 99px;
  align-self: stretch; flex-shrink: 0;
}
.task-priority.high { background: var(--red); }
.task-priority.medium { background: var(--amber); }
.task-priority.low { background: var(--green3); }
.task-content { flex: 1; }
.task-title { font-size: 13px; font-weight: 600; margin-bottom: 3px; }
.task-meta { font-size: 11px; color: var(--text3); display: flex; gap: 10px; }
.task-meta span { display: flex; align-items: center; gap: 4px; }

/* ========== AI COMPANION ========== */
.ai-chat {
  display: flex; flex-direction: column; gap: 0;
  height: 400px;
}
.ai-messages {
  flex: 1; overflow-y: auto;
  padding: 16px;
  display: flex; flex-direction: column; gap: 12px;
  background: var(--bg3);
  border-radius: 10px;
  margin-bottom: 12px;
}
.ai-msg {
  max-width: 85%;
  padding: 10px 14px;
  border-radius: 12px;
  font-size: 13px;
  line-height: 1.5;
}
.ai-msg.bot {
  background: var(--surface2);
  border: 1px solid var(--border2);
  align-self: flex-start;
  border-bottom-left-radius: 4px;
}
.ai-msg.user {
  background: linear-gradient(135deg, var(--green-dim), #1a6040);
  align-self: flex-end;
  border-bottom-right-radius: 4px;
  color: var(--text);
}
.ai-msg-header {
  display: flex; align-items: center; gap: 6px;
  font-size: 10px; color: var(--text3);
  margin-bottom: 4px;
  font-family: var(--font-mono);
}
.ai-input-row {
  display: flex; gap: 8px;
}
.ai-input {
  flex: 1;
  background: var(--bg3);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 10px 14px;
  color: var(--text);
  font-family: var(--font-body);
  font-size: 13px;
  outline: none;
  transition: border-color 0.2s;
}
.ai-input:focus { border-color: var(--green); }
.ai-input::placeholder { color: var(--text3); }
.btn {
  padding: 9px 18px;
  border-radius: 10px;
  border: none;
  cursor: pointer;
  font-family: var(--font-body);
  font-size: 13px;
  font-weight: 600;
  transition: all 0.2s;
}
.btn-primary {
  background: linear-gradient(135deg, var(--green), var(--green3));
  color: #0b1a12;
}
.btn-primary:hover { opacity: 0.85; transform: translateY(-1px); }
.btn-outline {
  background: transparent;
  border: 1px solid var(--border);
  color: var(--text2);
}
.btn-outline:hover { border-color: var(--green); color: var(--green); }
.btn-sm { padding: 6px 12px; font-size: 12px; }

/* ========== FORMS ========== */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group.full { grid-column: 1/-1; }
.form-label { font-size: 12px; font-weight: 600; color: var(--text2); font-family: var(--font-mono); letter-spacing: 0.5px; }
.form-control {
  background: var(--bg3);
  border: 1px solid var(--border2);
  border-radius: 9px;
  padding: 9px 13px;
  color: var(--text);
  font-family: var(--font-body);
  font-size: 13px;
  outline: none;
  transition: border-color 0.2s;
  width: 100%;
}
.form-control:focus { border-color: var(--green); }
.form-control option { background: var(--bg2); }
select.form-control { cursor: pointer; }

/* ========== INVENTORY ========== */
.shortage-alert {
  background: linear-gradient(90deg, rgba(255,92,92,0.08), transparent);
  border-left: 3px solid var(--red);
  border-radius: 0 var(--radius) var(--radius) 0;
  padding: 10px 14px;
  margin-bottom: 8px;
  display: flex; align-items: center; justify-content: space-between;
}
.shortage-item-name { font-weight: 600; font-size: 13px; }
.shortage-qty { font-size: 12px; color: var(--red); font-family: var(--font-mono); }

/* ========== SALES CHART (CSS only) ========== */
.bar-chart { display: flex; align-items: flex-end; gap: 8px; height: 120px; padding: 0 4px; }
.bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px; }
.bar { width: 100%; border-radius: 6px 6px 0 0; transition: opacity 0.2s; cursor: pointer; }
.bar:hover { opacity: 0.75; }
.bar-label { font-size: 10px; color: var(--text3); font-family: var(--font-mono); }

/* ========== PAYROLL ========== */
.payroll-item {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid rgba(42,84,56,0.3);
}
.payroll-item:last-child { border-bottom: none; }
.emp-info { display: flex; align-items: center; gap: 10px; }
.emp-avatar {
  width: 34px; height: 34px; border-radius: 50%;
  background: linear-gradient(135deg, var(--green-dim), var(--bg3));
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 12px; color: var(--green);
  flex-shrink: 0;
}
.emp-name { font-size: 13px; font-weight: 600; }
.emp-role { font-size: 11px; color: var(--text3); }
.emp-salary { font-family: var(--font-mono); font-size: 13px; font-weight: 600; color: var(--amber); }

/* ========== CALENDAR ========== */
.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
}
.cal-day-head {
  text-align: center;
  font-size: 10px;
  color: var(--text3);
  font-family: var(--font-mono);
  padding: 4px;
  text-transform: uppercase;
}
.cal-day {
  aspect-ratio: 1;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px;
  border-radius: 8px;
  cursor: pointer;
  position: relative;
  transition: background 0.15s;
}
.cal-day:hover { background: var(--surface2); }
.cal-day.today {
  background: var(--green);
  color: #0b1a12;
  font-weight: 800;
}
.cal-day.has-event::after {
  content: '';
  position: absolute;
  bottom: 3px;
  width: 4px; height: 4px;
  border-radius: 50%;
  background: var(--amber);
}
.cal-day.empty { opacity: 0; pointer-events: none; }
.cal-day.other-month { opacity: 0.3; }

/* ========== PAGE SECTIONS (tabs) ========== */
.page-section { display: none; }
.page-section.active { display: block; }

/* ========== WEATHER LIVE LOADING ========== */
.live-dot {
  display: inline-block;
  width: 7px; height: 7px;
  border-radius: 50%;
  background: var(--green);
  margin-right: 6px;
  animation: pulse 1.5s infinite;
}

/* ========== RESPONSIVE ========== */
@media (max-width: 900px) {
  .weather-grid { grid-template-columns: 1fr; }
  .form-grid { grid-template-columns: 1fr; }
  .form-group.full { grid-column: 1; }
  .stats-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 640px) {
  .sidebar { transform: translateX(-100%); }
  .main { margin-left: 0; }
  .stats-grid { grid-template-columns: 1fr; }
}

/* ========== SCROLLABLE TABLE WRAPPER ========== */
.table-scroll { overflow-x: auto; }

/* ========== PROGRESS BAR ========== */
.progress-bar {
  height: 6px;
  background: var(--bg3);
  border-radius: 99px;
  overflow: hidden;
  margin-top: 6px;
}
.progress-fill {
  height: 100%;
  border-radius: 99px;
  transition: width 0.6s ease;
}

/* ========== FULL PAGE SECTION HEADERS ========== */
.page-section-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 24px;
}
.page-section-title {
  font-family: var(--font-head);
  font-size: 22px; font-weight: 800;
}
.page-section-sub { font-size: 13px; color: var(--text3); margin-top: 2px; }

/* ========== TABS ========== */
.sub-tabs {
  display: flex; gap: 4px;
  background: var(--bg3);
  border-radius: 10px;
  padding: 4px;
  margin-bottom: 20px;
}
.sub-tab {
  padding: 7px 16px;
  border-radius: 8px;
  font-size: 13px; font-weight: 500;
  cursor: pointer;
  color: var(--text3);
  transition: all 0.2s;
  border: none;
  background: none;
}
.sub-tab.active {
  background: var(--surface2);
  color: var(--text);
}
.sub-tab:hover { color: var(--text); }

/* ========== DISEASE SYMPTOM TAGS ========== */
.symptom-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
.symptom-tag {
  background: rgba(61,255,160,0.08);
  border: 1px solid rgba(61,255,160,0.2);
  color: var(--green2);
  padding: 4px 12px;
  border-radius: 99px;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.15s;
}
.symptom-tag:hover { background: rgba(61,255,160,0.18); }

/* ========== MOBILE MENU BTN ========== */
.mobile-menu-btn {
  display: none;
  background: none; border: none;
  color: var(--text); font-size: 22px;
  cursor: pointer;
  padding: 4px;
}
@media (max-width: 640px) { .mobile-menu-btn { display: block; } }

/* ========== GLOW EFFECT ========== */
.glow { box-shadow: 0 0 20px rgba(61,255,160,0.15); }

</style>
</head>
<body>
<div class="app">

<!-- ========== SIDEBAR ========== -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-mark">
      <div class="logo-icon">🌱</div>
      <div>
        <div class="logo-text">Smart<span>Farm</span></div>
        <div class="logo-sub">v2.1 · Uganda</div>
      </div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section">Overview</div>
    <a href="?tab=dashboard" class="nav-item <?= $active_tab==='dashboard'?'active':'' ?>">
      <span class="icon">📊</span> Dashboard
      <?php if(count($shortage_items)>0): ?>
        <span class="nav-badge"><?= count($shortage_items) ?></span>
      <?php endif; ?>
    </a>
    <a href="?tab=weather" class="nav-item <?= $active_tab==='weather'?'active':'' ?>">
      <span class="icon">🌤</span> Weather & Forecast
    </a>

    <div class="nav-section">Operations</div>
    <a href="?tab=ai" class="nav-item <?= $active_tab==='ai'?'active':'' ?>">
      <span class="icon">🤖</span> AI Disease Detector
      <span class="nav-badge green">AI</span>
    </a>
    <a href="?tab=inventory" class="nav-item <?= $active_tab==='inventory'?'active':'' ?>">
      <span class="icon">📦</span> Inventory
      <?php if(count($shortage_items)>0): ?>
        <span class="nav-badge amber"><?= count($shortage_items) ?></span>
      <?php endif; ?>
    </a>
    <a href="?tab=sales" class="nav-item <?= $active_tab==='sales'?'active':'' ?>">
      <span class="icon">💰</span> Sales & Revenue
    </a>

    <div class="nav-section">People & Planning</div>
    <a href="?tab=employees" class="nav-item <?= $active_tab==='employees'?'active':'' ?>">
      <span class="icon">👷</span> Employees & Payroll
    </a>
    <a href="?tab=tasks" class="nav-item <?= $active_tab==='tasks'?'active':'' ?>">
      <span class="icon">📅</span> Tasks & Events
      <?php if(count($today_tasks)>0): ?>
        <span class="nav-badge"><?= count($today_tasks) ?></span>
      <?php endif; ?>
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar">FM</div>
      <div>
        <div class="user-name">Farm Manager</div>
        <div class="user-role">Administrator</div>
      </div>
    </div>
  </div>
</aside>

<!-- ========== MAIN ========== -->
<div class="main">

  <!-- TOP BAR -->
  <header class="topbar">
    <div class="topbar-left">
      <button class="mobile-menu-btn" onclick="document.getElementById('sidebar').style.transform='translateX(0)'">☰</button>
      <div>
        <?php
        $titles = ['dashboard'=>'Dashboard','weather'=>'Weather & Forecast','ai'=>'AI Disease Detector','inventory'=>'Inventory Management','sales'=>'Sales & Revenue','employees'=>'Employees & Payroll','tasks'=>'Tasks & Events'];
        ?>
        <div class="page-title"><?= $titles[$active_tab] ?? 'Dashboard' ?></div>
        <div class="breadcrumb">SmartFarm / <?= ucfirst($active_tab) ?></div>
      </div>
    </div>
    <div class="topbar-right">
      <div class="topbar-time" id="clock">
        <span class="live-dot"></span>
        <?= date('D, d M Y · H:i') ?>
      </div>
      <div class="alert-bell" title="<?= count($shortage_items) ?> shortage alerts">
        🔔<div class="alert-dot"></div>
      </div>
    </div>
  </header>

  <!-- PAGE CONTENT -->
  <div class="content">

    <?php if($action_msg): ?>
    <div class="success-banner"><span>✅</span> <?= htmlspecialchars($action_msg) ?></div>
    <?php endif; ?>

    <!-- ==================== DASHBOARD ==================== -->
    <?php if($active_tab === 'dashboard'): ?>

    <?php if(count($shortage_items) > 0): ?>
    <div class="alert-banner">
      <span class="icon">⚠️</span>
      <div><strong>Shortage Alert:</strong> <?= count($shortage_items) ?> item(s) are below minimum stock levels —
        <?= implode(', ', array_column(array_values($shortage_items), 'item')) ?>.
        <a href="?tab=inventory" style="color:var(--amber);text-decoration:none;margin-left:6px;">View Inventory →</a>
      </div>
    </div>
    <?php endif; ?>

    <!-- STATS GRID -->
    <div class="stats-grid">
      <div class="stat-card green">
        <div class="stat-header">
          <div class="stat-label">Total Revenue</div>
          <div class="stat-icon">💰</div>
        </div>
        <div class="stat-value">UGX <?= number_format($total_completed/1000) ?>K</div>
        <div class="stat-sub">Confirmed sales this season</div>
        <div class="stat-trend">↑ +18% vs last month</div>
      </div>
      <div class="stat-card amber">
        <div class="stat-header">
          <div class="stat-label">Inventory Alerts</div>
          <div class="stat-icon">📦</div>
        </div>
        <div class="stat-value"><?= count($shortage_items) ?></div>
        <div class="stat-sub">Items below minimum stock</div>
        <div class="stat-trend down">⚠ Needs attention</div>
      </div>
      <div class="stat-card blue">
        <div class="stat-header">
          <div class="stat-label">Active Staff</div>
          <div class="stat-icon">👷</div>
        </div>
        <div class="stat-value"><?= $active_employees ?></div>
        <div class="stat-sub"><?= count($employees) ?> total employees</div>
        <div class="stat-trend">● All systems active</div>
      </div>
      <div class="stat-card purple">
        <div class="stat-header">
          <div class="stat-label">Monthly Payroll</div>
          <div class="stat-icon">💳</div>
        </div>
        <div class="stat-value">UGX <?= number_format($total_payroll/1000) ?>K</div>
        <div class="stat-sub">Next: May 30, 2026</div>
      </div>
      <div class="stat-card red">
        <div class="stat-header">
          <div class="stat-label">Today's Tasks</div>
          <div class="stat-icon">📋</div>
        </div>
        <div class="stat-value"><?= count($today_tasks) ?></div>
        <div class="stat-sub">Due today — needs action</div>
        <div class="stat-trend down">⟳ Pending</div>
      </div>
    </div>

    <!-- WEATHER PREVIEW + TODAY TASKS -->
    <div class="dash-grid">
      <!-- Weather Preview -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">🌤 Live Weather — Kampala</div>
          <a href="?tab=weather" style="font-size:12px;color:var(--green);text-decoration:none;">Full Forecast →</a>
        </div>
        <div class="card-body">
          <div class="weather-current">
            <div class="weather-icon-big">🌤</div>
            <div>
              <div class="weather-temp" id="dash-temp">26°C</div>
              <div class="weather-desc">Partly Cloudy</div>
              <div style="font-size:12px;color:var(--text3);">Humidity: 72% · Wind: 14 km/h NE</div>
            </div>
          </div>
          <div style="display:flex;gap:10px;margin-top:6px;">
            <div style="flex:1;background:var(--bg3);border-radius:10px;padding:10px;text-align:center;border:1px solid var(--border2);">
              <div style="font-size:18px;">🌧</div>
              <div style="font-size:11px;color:var(--text3);margin-top:2px;">Tomorrow</div>
              <div style="font-size:13px;font-weight:700;color:var(--blue);">24°C · Rain</div>
            </div>
            <div style="flex:1;background:var(--bg3);border-radius:10px;padding:10px;text-align:center;border:1px solid var(--border2);">
              <div style="font-size:18px;">☀️</div>
              <div style="font-size:11px;color:var(--text3);margin-top:2px;">Thursday</div>
              <div style="font-size:13px;font-weight:700;color:var(--amber);">29°C · Sunny</div>
            </div>
            <div style="flex:1;background:var(--bg3);border-radius:10px;padding:10px;text-align:center;border:1px solid var(--border2);">
              <div style="font-size:18px;">⛅</div>
              <div style="font-size:11px;color:var(--text3);margin-top:2px;">Friday</div>
              <div style="font-size:13px;font-weight:700;color:var(--text);">27°C · Cloudy</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Today's Tasks -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">📋 Today's Pending Tasks</div>
          <a href="?tab=tasks" style="font-size:12px;color:var(--green);text-decoration:none;">All Tasks →</a>
        </div>
        <div class="card-body">
          <?php foreach(array_values($today_tasks) as $t): ?>
          <div class="task-item">
            <div class="task-priority <?= $t['priority'] ?>"></div>
            <div class="task-content">
              <div class="task-title"><?= htmlspecialchars($t['title']) ?></div>
              <div class="task-meta">
                <span>📍 <?= htmlspecialchars($t['location']) ?></span>
                <span>👤 <?= htmlspecialchars($t['assigned']) ?></span>
                <span class="badge badge-<?= $t['priority']==='high'?'red':($t['priority']==='medium'?'amber':'green') ?>"><?= $t['priority'] ?></span>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
          <?php if(empty($today_tasks)): ?>
          <div style="text-align:center;padding:20px;color:var(--text3);">✅ No pending tasks today</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- SALES OVERVIEW + INVENTORY SHORTAGE -->
    <div class="dash-grid">
      <div class="card">
        <div class="card-header">
          <div class="card-title">💰 Recent Sales</div>
          <a href="?tab=sales" style="font-size:12px;color:var(--green);text-decoration:none;">View All →</a>
        </div>
        <div class="card-body" style="padding-top:8px;">
          <!-- CSS Bar Chart -->
          <div class="bar-chart" title="Sales in UGX">
            <?php
            $bar_data = [
              ['Jan','rgba(61,255,160,0.4)',65],
              ['Feb','rgba(61,255,160,0.5)',72],
              ['Mar','rgba(61,255,160,0.6)',58],
              ['Apr','rgba(61,255,160,0.8)',90],
              ['May','var(--green)',80],
            ];
            foreach($bar_data as $b): ?>
            <div class="bar-col">
              <div class="bar" style="height:<?= $b[2] ?>%;background:<?= $b[1] ?>;" title="<?= $b[0] ?> sales"></div>
              <div class="bar-label"><?= $b[0] ?></div>
            </div>
            <?php endforeach; ?>
          </div>
          <div style="margin-top:14px;">
            <?php foreach(array_slice($sales, 0, 4) as $s): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid rgba(42,84,56,0.3);">
              <div>
                <div style="font-size:13px;font-weight:600;"><?= htmlspecialchars($s['product']) ?></div>
                <div style="font-size:11px;color:var(--text3);"><?= $s['qty'].' '.$s['unit'] ?> · <?= $s['buyer'] ?></div>
              </div>
              <div style="text-align:right;">
                <div style="font-family:var(--font-mono);font-size:13px;color:var(--amber);">UGX <?= number_format($s['qty']*$s['price_unit']) ?></div>
                <span class="badge badge-<?= $s['status']==='completed'?'green':'amber' ?>"><?= $s['status'] ?></span>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Stock Shortages -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">⚠️ Stock Shortage Alerts</div>
          <a href="?tab=inventory" style="font-size:12px;color:var(--green);text-decoration:none;">Manage Stock →</a>
        </div>
        <div class="card-body">
          <?php if(empty($shortage_items)): ?>
          <div style="text-align:center;padding:20px;color:var(--text3);">✅ All stock levels healthy</div>
          <?php else: ?>
          <?php foreach($shortage_items as $item): ?>
          <div class="shortage-alert">
            <div>
              <div class="shortage-item-name"><?= htmlspecialchars($item['item']) ?></div>
              <div style="font-size:11px;color:var(--text3);"><?= $item['category'] ?> · Supplier: <?= $item['supplier'] ?></div>
            </div>
            <div class="shortage-qty">
              <?= $item['qty'] ?>/<?= $item['min_qty'] ?> <?= $item['unit'] ?>
            </div>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>

          <div style="margin-top:16px;">
            <div style="font-size:12px;color:var(--text3);font-family:var(--font-mono);margin-bottom:10px;text-transform:uppercase;letter-spacing:1px;">Stock Levels</div>
            <?php foreach(array_slice($inventory,0,4) as $item):
              $pct = min(100, round($item['qty']/$item['min_qty']*100));
              $color = $pct<100 ? 'var(--red)' : ($pct<150 ? 'var(--amber)' : 'var(--green)');
            ?>
            <div style="margin-bottom:10px;">
              <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px;">
                <span><?= htmlspecialchars($item['item']) ?></span>
                <span style="font-family:var(--font-mono);color:<?= $color ?>"><?= $item['qty'] ?> <?= $item['unit'] ?></span>
              </div>
              <div class="progress-bar"><div class="progress-fill" style="width:<?= min(100,$pct) ?>%;background:<?= $color ?>"></div></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- UPCOMING EVENTS -->
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header">
        <div class="card-title">📅 Upcoming Events</div>
        <a href="?tab=tasks" style="font-size:12px;color:var(--green);text-decoration:none;">Full Calendar →</a>
      </div>
      <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
          <?php foreach($events as $ev):
            $types = ['exhibition'=>['🏆','var(--amber)'],'appointment'=>['🩺','var(--blue)'],'meeting'=>['🤝','var(--purple)'],'delivery'=>['📦','var(--green2)']];
            $t = $types[$ev['type']] ?? ['📅','var(--text2)'];
          ?>
          <div style="background:var(--bg3);border:1px solid var(--border2);border-radius:12px;padding:14px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
              <div style="font-size:22px;"><?= $t[0] ?></div>
              <div>
                <div style="font-size:13px;font-weight:700;"><?= htmlspecialchars($ev['title']) ?></div>
                <div style="font-size:11px;font-family:var(--font-mono);color:<?= $t[1] ?>"><?= date('D, d M',strtotime($ev['date'])) ?></div>
              </div>
            </div>
            <div style="font-size:12px;color:var(--text3);"><?= htmlspecialchars($ev['desc']) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <?php endif; // end dashboard ?>

    <!-- ==================== WEATHER ==================== -->
    <?php if($active_tab === 'weather'): ?>

    <div class="page-section-header">
      <div>
        <div class="page-section-title">🌤 Weather Intelligence</div>
        <div class="page-section-sub">Live data & 7-day agricultural forecast for your region</div>
      </div>
      <div id="live-weather-refresh" style="font-size:12px;color:var(--green);cursor:pointer;" onclick="loadWeather()">
        <span class="live-dot"></span> Live · Last updated <?= date('H:i') ?>
      </div>
    </div>

    <div class="weather-grid" style="grid-template-columns:1fr 1.2fr;">
      <!-- Current Weather -->
      <div class="weather-main">
        <div class="weather-location">📍 Kampala, Uganda (0.32°N, 32.58°E)</div>
        <div class="weather-current">
          <div id="w-icon" class="weather-icon-big">🌤</div>
          <div>
            <div class="weather-temp" id="w-temp">26°C</div>
            <div class="weather-desc" id="w-desc">Partly Cloudy</div>
            <div style="font-size:12px;color:var(--text3);" id="w-feels">Feels like 28°C</div>
          </div>
        </div>
        <div class="weather-meta" style="flex-wrap:wrap;gap:12px;">
          <div class="weather-meta-item">💧 <span id="w-humidity">72%</span> Humidity</div>
          <div class="weather-meta-item">💨 <span id="w-wind">14 km/h</span> NE</div>
          <div class="weather-meta-item">🌡 <span id="w-pressure">1013</span> hPa</div>
          <div class="weather-meta-item">☁️ <span id="w-cloud">45%</span> Cloud Cover</div>
          <div class="weather-meta-item">🌅 Sunrise: 06:42</div>
          <div class="weather-meta-item">🌇 Sunset: 18:55</div>
          <div class="weather-meta-item">🌧 Precip: <span id="w-precip">0.0 mm</span></div>
          <div class="weather-meta-item">👁 Visibility: <span id="w-vis">10 km</span></div>
        </div>

        <div style="margin-top:20px;background:var(--bg3);border-radius:12px;padding:14px;border:1px solid var(--border2);">
          <div style="font-size:12px;color:var(--green);font-weight:700;margin-bottom:8px;">🌱 Farming Advisory</div>
          <div style="font-size:13px;color:var(--text2);" id="farm-advisory">
            Conditions are favorable for irrigation. High humidity reduces crop stress — monitor for fungal development in dense canopy areas. Wind speed is suitable for spray applications.
          </div>
        </div>
      </div>

      <!-- 7-Day Forecast -->
      <div class="weather-forecast">
        <div class="forecast-title"><span class="live-dot"></span>7-Day Forecast</div>
        <div class="forecast-days">
          <?php
          $forecast = [
            ['Wed','🌤','Partly Cloudy',26,18,'10%','Today'],
            ['Thu','🌧','Showers',24,17,'75%',null],
            ['Fri','⛅','Overcast',25,18,'40%',null],
            ['Sat','☀️','Sunny',30,19,'5%',null],
            ['Sun','🌤','Fair',28,18,'15%',null],
            ['Mon','🌦','Light Rain',23,17,'60%',null],
            ['Tue','☀️','Clear',29,20,'5%',null],
          ];
          foreach($forecast as $day): ?>
          <div class="forecast-day">
            <div class="forecast-day-name"><?= $day[6]??$day[0] ?></div>
            <div class="forecast-day-icon"><?= $day[1] ?></div>
            <div class="forecast-day-desc"><?= $day[2] ?></div>
            <div class="forecast-day-rain">🌧 <?= $day[4] ?></div>
            <div class="forecast-temps"><span><?= $day[3] ?>°</span><span class="low"><?= $day[4] ?>°</span></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Agri Weather Indicators -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:20px;">
      <?php
      $indicators = [
        ['🌱','Soil Moisture','68%','Adequate','green'],
        ['☀️','UV Index','6 — High','Apply sunscreen','amber'],
        ['💦','Evapotranspiration','4.2 mm/day','Moderate crop demand','blue'],
        ['🌿','Disease Risk','Medium','Monitor blight','amber'],
        ['🐛','Pest Pressure','Low','Favorable conditions','green'],
        ['🌊','Flood Risk','Low','No immediate risk','green'],
      ];
      foreach($indicators as $ind): ?>
      <div style="background:var(--surface);border:1px solid var(--border2);border-radius:var(--radius);padding:14px;">
        <div style="font-size:22px;margin-bottom:8px;"><?= $ind[0] ?></div>
        <div style="font-size:11px;color:var(--text3);font-family:var(--font-mono);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;"><?= $ind[1] ?></div>
        <div style="font-size:16px;font-weight:700;color:var(--<?= $ind[4] ?>);margin-bottom:2px;"><?= $ind[2] ?></div>
        <div style="font-size:11px;color:var(--text3);"><?= $ind[3] ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php endif; // end weather ?>

    <!-- ==================== AI COMPANION ==================== -->
    <?php if($active_tab === 'ai'): ?>

    <div class="page-section-header">
      <div>
        <div class="page-section-title">🤖 AI Disease Detection</div>
        <div class="page-section-sub">Describe crop or livestock symptoms for instant AI-powered diagnosis</div>
      </div>
      <span class="badge badge-green">● Powered by Claude AI</span>
    </div>

    <div class="dash-grid">
      <div class="card">
        <div class="card-header">
          <div class="card-title">💬 AI Agronomist Companion</div>
          <span style="font-size:11px;font-family:var(--font-mono);color:var(--green);">LIVE</span>
        </div>
        <div class="card-body">
          <div class="ai-chat">
            <div class="ai-messages" id="chatMessages">
              <div class="ai-msg bot">
                <div class="ai-msg-header">🤖 SmartFarm AI · Now</div>
                Hello! I'm your AI agronomist. Describe the symptoms you're observing in your crops or livestock and I'll help diagnose diseases, suggest treatments, and provide prevention strategies.
              </div>
              <div class="ai-msg bot">
                <div class="ai-msg-header">🤖 SmartFarm AI · Now</div>
                You can also click any of the common symptom tags below to get started quickly.
              </div>
            </div>
            <div class="symptom-tags" id="symptomTags">
              <span class="symptom-tag" onclick="useSuggestion(this)">Yellow leaves</span>
              <span class="symptom-tag" onclick="useSuggestion(this)">Brown spots on leaves</span>
              <span class="symptom-tag" onclick="useSuggestion(this)">Wilting plants</span>
              <span class="symptom-tag" onclick="useSuggestion(this)">White powder on leaves</span>
              <span class="symptom-tag" onclick="useSuggestion(this)">Stunted growth</span>
              <span class="symptom-tag" onclick="useSuggestion(this)">Black rot on stem</span>
              <span class="symptom-tag" onclick="useSuggestion(this)">Cattle not eating</span>
              <span class="symptom-tag" onclick="useSuggestion(this)">Leaf curl and discoloration</span>
              <span class="symptom-tag" onclick="useSuggestion(this)">Tomato blossom drop</span>
              <span class="symptom-tag" onclick="useSuggestion(this)">Maize ear rot</span>
            </div>
            <div class="ai-input-row">
              <input type="text" class="ai-input" id="aiInput"
                placeholder="Describe symptoms... e.g. 'My maize leaves have yellow stripes and the plant looks stunted'"
                onkeydown="if(event.key==='Enter') sendAIMessage()">
              <button class="btn btn-primary" onclick="sendAIMessage()">Send 🚀</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Reference -->
      <div style="display:flex;flex-direction:column;gap:16px;">
        <div class="card">
          <div class="card-header"><div class="card-title">📚 Common Diseases — Quick Ref</div></div>
          <div class="card-body" style="padding-top:8px;">
            <?php
            $diseases = [
              ['Maize Streak Virus','Yellow streaks on leaves, stunting','Viral · Control aphids, remove infected plants','red'],
              ['Tomato Blight','Dark spots, water-soaked lesions','Fungal · Apply mancozeb fungicide','amber'],
              ['Powdery Mildew','White powdery coating on leaves','Fungal · Sulfur-based spray, improve airflow','amber'],
              ['Cassava Mosaic','Yellow-green mosaic pattern','Viral · Use disease-free cuttings','red'],
              ['Black Sigatoka','Black streaks on banana leaves','Fungal · Systemic fungicide','red'],
            ];
            foreach($diseases as $d): ?>
            <div style="padding:10px 0;border-bottom:1px solid rgba(42,84,56,0.3);">
              <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px;">
                <span class="badge badge-<?= $d[3] ?>"><?= $d[0] ?></span>
              </div>
              <div style="font-size:12px;color:var(--text2);margin-bottom:2px;">Symptoms: <?= $d[1] ?></div>
              <div style="font-size:11px;color:var(--text3);">💊 <?= $d[2] ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <?php endif; // end AI ?>

    <!-- ==================== INVENTORY ==================== -->
    <?php if($active_tab === 'inventory'): ?>

    <div class="page-section-header">
      <div>
        <div class="page-section-title">📦 Inventory Management</div>
        <div class="page-section-sub"><?= count($inventory) ?> items · <?= count($shortage_items) ?> shortage alerts</div>
      </div>
      <button class="btn btn-primary" onclick="document.getElementById('addInventoryForm').style.display='block'">+ Add Item</button>
    </div>

    <!-- Add Inventory Form -->
    <div class="card" id="addInventoryForm" style="display:none;margin-bottom:20px;">
      <div class="card-header">
        <div class="card-title">➕ Add Inventory Item</div>
        <button class="btn btn-outline btn-sm" onclick="this.closest('#addInventoryForm').style.display='none'">✕ Close</button>
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="add_inventory">
          <div class="form-grid">
            <div class="form-group"><label class="form-label">Item Name</label><input class="form-control" name="item" required placeholder="e.g. NPK Fertilizer"></div>
            <div class="form-group"><label class="form-label">Category</label>
              <select class="form-control" name="category">
                <option>Inputs</option><option>Seeds</option><option>Chemicals</option><option>Equipment</option><option>Fuel</option><option>Harvest</option>
              </select>
            </div>
            <div class="form-group"><label class="form-label">Quantity</label><input class="form-control" type="number" name="qty" required></div>
            <div class="form-group"><label class="form-label">Unit</label><input class="form-control" name="unit" placeholder="kg / liters / meters"></div>
            <div class="form-group"><label class="form-label">Minimum Stock</label><input class="form-control" type="number" name="min_qty" required></div>
            <div class="form-group"><label class="form-label">Unit Cost (UGX)</label><input class="form-control" type="number" name="cost"></div>
            <div class="form-group full"><label class="form-label">Supplier</label><input class="form-control" name="supplier" placeholder="Supplier name"></div>
          </div>
          <div style="margin-top:14px;"><button type="submit" class="btn btn-primary">Save Item</button></div>
        </form>
      </div>
    </div>

    <!-- Shortage Alerts -->
    <?php if(count($shortage_items)>0): ?>
    <div class="card" style="margin-bottom:20px;border-color:rgba(255,92,92,0.3);">
      <div class="card-header" style="background:rgba(255,92,92,0.05);">
        <div class="card-title" style="color:var(--red);">⚠️ Shortage Alerts (<?= count($shortage_items) ?>)</div>
      </div>
      <div class="card-body">
        <?php foreach($shortage_items as $item): ?>
        <div class="shortage-alert">
          <div>
            <div class="shortage-item-name"><?= htmlspecialchars($item['item']) ?></div>
            <div style="font-size:11px;color:var(--text3);"><?= $item['category'] ?> · Supplier: <?= $item['supplier'] ?></div>
          </div>
          <div>
            <div class="shortage-qty">Current: <?= $item['qty'] ?> <?= $item['unit'] ?></div>
            <div style="font-size:11px;color:var(--text3);">Min required: <?= $item['min_qty'] ?> <?= $item['unit'] ?></div>
          </div>
          <button class="btn btn-outline btn-sm" style="color:var(--amber);border-color:var(--amber);">Order Now</button>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Full Inventory Table -->
    <div class="card">
      <div class="card-header"><div class="card-title">All Inventory Items</div></div>
      <div class="table-scroll">
        <table class="data-table">
          <thead>
            <tr>
              <th>#</th><th>Item</th><th>Category</th><th>Quantity</th><th>Min Stock</th><th>Status</th><th>Unit Cost</th><th>Total Value</th><th>Supplier</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($inventory as $item):
              $status = $item['qty'] < $item['min_qty'] ? ['shortage','red'] : ($item['qty'] < $item['min_qty']*1.5 ? ['low','amber'] : ['ok','green']);
            ?>
            <tr>
              <td style="font-family:var(--font-mono);color:var(--text3)"><?= $item['id'] ?></td>
              <td style="font-weight:600"><?= htmlspecialchars($item['item']) ?></td>
              <td><span class="badge badge-gray"><?= $item['category'] ?></span></td>
              <td style="font-family:var(--font-mono)"><?= $item['qty'] ?> <?= $item['unit'] ?></td>
              <td style="font-family:var(--font-mono);color:var(--text3)"><?= $item['min_qty'] ?> <?= $item['unit'] ?></td>
              <td><span class="badge badge-<?= $status[1] ?>"><?= $status[0] ?></span></td>
              <td style="font-family:var(--font-mono)">UGX <?= number_format($item['cost']) ?></td>
              <td style="font-family:var(--font-mono);color:var(--amber)">UGX <?= number_format($item['qty']*$item['cost']) ?></td>
              <td style="color:var(--text3);font-size:12px"><?= htmlspecialchars($item['supplier']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php endif; // end inventory ?>

    <!-- ==================== SALES ==================== -->
    <?php if($active_tab === 'sales'): ?>

    <div class="page-section-header">
      <div>
        <div class="page-section-title">💰 Sales & Revenue</div>
        <div class="page-section-sub">Total pipeline: UGX <?= number_format($total_sales) ?> · Confirmed: UGX <?= number_format($total_completed) ?></div>
      </div>
      <button class="btn btn-primary" onclick="document.getElementById('addSaleForm').style.display='block'">+ Record Sale</button>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="margin-bottom:20px;">
      <div class="stat-card green">
        <div class="stat-header"><div class="stat-label">Confirmed Revenue</div><div class="stat-icon">✅</div></div>
        <div class="stat-value">UGX <?= number_format($total_completed/1000000,1) ?>M</div>
        <div class="stat-sub"><?= count($completed_sales) ?> completed transactions</div>
      </div>
      <div class="stat-card amber">
        <div class="stat-header"><div class="stat-label">Pending</div><div class="stat-icon">⏳</div></div>
        <div class="stat-value">UGX <?= number_format(($total_sales-$total_completed)/1000) ?>K</div>
        <div class="stat-sub"><?= count($sales)-count($completed_sales) ?> transactions pending</div>
      </div>
      <div class="stat-card blue">
        <div class="stat-header"><div class="stat-label">Total Sales</div><div class="stat-icon">📈</div></div>
        <div class="stat-value"><?= count($sales) ?></div>
        <div class="stat-sub">This season</div>
      </div>
    </div>

    <!-- Add Sale Form -->
    <div class="card" id="addSaleForm" style="display:none;margin-bottom:20px;">
      <div class="card-header">
        <div class="card-title">➕ Record New Sale</div>
        <button class="btn btn-outline btn-sm" onclick="this.closest('#addSaleForm').style.display='none'">✕</button>
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="add_sale">
          <div class="form-grid">
            <div class="form-group"><label class="form-label">Product</label><input class="form-control" name="product" required placeholder="e.g. Maize"></div>
            <div class="form-group"><label class="form-label">Buyer</label><input class="form-control" name="buyer" required placeholder="Buyer name / company"></div>
            <div class="form-group"><label class="form-label">Quantity</label><input class="form-control" type="number" name="qty" required></div>
            <div class="form-group"><label class="form-label">Unit</label><input class="form-control" name="unit" placeholder="kg / heads / bags"></div>
            <div class="form-group"><label class="form-label">Price per Unit (UGX)</label><input class="form-control" type="number" name="price_unit" required></div>
            <div class="form-group"><label class="form-label">Sale Date</label><input class="form-control" type="date" name="date" value="<?= date('Y-m-d') ?>"></div>
            <div class="form-group"><label class="form-label">Status</label>
              <select class="form-control" name="status"><option>pending</option><option>completed</option></select>
            </div>
          </div>
          <div style="margin-top:14px;"><button type="submit" class="btn btn-primary">Record Sale</button></div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">All Sales Transactions</div></div>
      <div class="table-scroll">
        <table class="data-table">
          <thead>
            <tr><th>#</th><th>Product</th><th>Quantity</th><th>Unit Price</th><th>Total</th><th>Buyer</th><th>Date</th><th>Status</th></tr>
          </thead>
          <tbody>
            <?php foreach($sales as $s): ?>
            <tr>
              <td style="font-family:var(--font-mono);color:var(--text3)"><?= $s['id'] ?></td>
              <td style="font-weight:600"><?= htmlspecialchars($s['product']) ?></td>
              <td style="font-family:var(--font-mono)"><?= number_format($s['qty']) ?> <?= $s['unit'] ?></td>
              <td style="font-family:var(--font-mono)">UGX <?= number_format($s['price_unit']) ?></td>
              <td style="font-family:var(--font-mono);font-weight:700;color:var(--amber)">UGX <?= number_format($s['qty']*$s['price_unit']) ?></td>
              <td><?= htmlspecialchars($s['buyer']) ?></td>
              <td style="font-size:12px;color:var(--text3)"><?= $s['date'] ?></td>
              <td><span class="badge badge-<?= $s['status']==='completed'?'green':'amber' ?>"><?= $s['status'] ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php endif; // end sales ?>

    <!-- ==================== EMPLOYEES & PAYROLL ==================== -->
    <?php if($active_tab === 'employees'): ?>

    <div class="page-section-header">
      <div>
        <div class="page-section-title">👷 Employees & Payroll</div>
        <div class="page-section-sub"><?= count($employees) ?> employees · Monthly payroll: UGX <?= number_format($total_payroll) ?></div>
      </div>
      <button class="btn btn-primary" onclick="document.getElementById('addEmpForm').style.display='block'">+ Add Employee</button>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="margin-bottom:20px;">
      <div class="stat-card green">
        <div class="stat-header"><div class="stat-label">Active Staff</div><div class="stat-icon">✅</div></div>
        <div class="stat-value"><?= $active_employees ?></div>
      </div>
      <div class="stat-card amber">
        <div class="stat-header"><div class="stat-label">On Leave</div><div class="stat-icon">🏖</div></div>
        <div class="stat-value"><?= count($employees)-$active_employees ?></div>
      </div>
      <div class="stat-card purple">
        <div class="stat-header"><div class="stat-label">Monthly Payroll</div><div class="stat-icon">💳</div></div>
        <div class="stat-value">UGX <?= number_format($total_payroll/1000) ?>K</div>
      </div>
      <div class="stat-card blue">
        <div class="stat-header"><div class="stat-label">Avg. Salary</div><div class="stat-icon">📊</div></div>
        <div class="stat-value">UGX <?= number_format($total_payroll/count($employees)/1000) ?>K</div>
      </div>
    </div>

    <!-- Add Employee Form -->
    <div class="card" id="addEmpForm" style="display:none;margin-bottom:20px;">
      <div class="card-header">
        <div class="card-title">➕ Add Employee</div>
        <button class="btn btn-outline btn-sm" onclick="this.closest('#addEmpForm').style.display='none'">✕</button>
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="add_employee">
          <div class="form-grid">
            <div class="form-group"><label class="form-label">Full Name</label><input class="form-control" name="name" required></div>
            <div class="form-group"><label class="form-label">Role / Position</label><input class="form-control" name="role" required></div>
            <div class="form-group"><label class="form-label">Monthly Salary (UGX)</label><input class="form-control" type="number" name="salary" required></div>
            <div class="form-group"><label class="form-label">Phone Number</label><input class="form-control" name="phone"></div>
            <div class="form-group"><label class="form-label">Status</label>
              <select class="form-control" name="status"><option>active</option><option>on-leave</option><option>probation</option></select>
            </div>
            <div class="form-group"><label class="form-label">Start Date</label><input class="form-control" type="date" name="start_date" value="<?= date('Y-m-d') ?>"></div>
          </div>
          <div style="margin-top:14px;"><button type="submit" class="btn btn-primary">Add Employee</button></div>
        </form>
      </div>
    </div>

    <div class="dash-grid">
      <!-- Employee Directory -->
      <div class="card">
        <div class="card-header"><div class="card-title">Employee Directory</div></div>
        <div class="card-body" style="padding-top:8px;">
          <?php foreach($employees as $e): ?>
          <div class="payroll-item">
            <div class="emp-info">
              <div class="emp-avatar"><?= strtoupper(substr($e['name'],0,1)) ?></div>
              <div>
                <div class="emp-name"><?= htmlspecialchars($e['name']) ?></div>
                <div class="emp-role"><?= htmlspecialchars($e['role']) ?> · <?= $e['phone'] ?></div>
              </div>
            </div>
            <div style="text-align:right;">
              <div class="emp-salary">UGX <?= number_format($e['salary']) ?></div>
              <span class="badge badge-<?= $e['status']==='active'?'green':'amber' ?>"><?= $e['status'] ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Payroll Summary -->
      <div class="card">
        <div class="card-header"><div class="card-title">💳 Payroll Summary — May 2026</div></div>
        <div class="card-body">
          <?php foreach($employees as $e):
            $nssf = round($e['salary']*0.1);
            $paye = $e['salary']>500000 ? round(($e['salary']-500000)*0.3) : 0;
            $net = $e['salary'] - $nssf - $paye;
          ?>
          <div style="background:var(--bg3);border:1px solid var(--border2);border-radius:10px;padding:12px;margin-bottom:8px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
              <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($e['name']) ?></div>
              <span class="badge badge-<?= $e['status']==='active'?'green':'amber' ?>"><?= $e['status'] ?></span>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:4px;font-size:11px;font-family:var(--font-mono);">
              <div><div style="color:var(--text3)">Gross</div><div style="color:var(--text)"><?= number_format($e['salary']) ?></div></div>
              <div><div style="color:var(--text3)">Deductions</div><div style="color:var(--red)">-<?= number_format($nssf+$paye) ?></div></div>
              <div><div style="color:var(--text3)">Net Pay</div><div style="color:var(--green2);font-weight:700"><?= number_format($net) ?></div></div>
            </div>
          </div>
          <?php endforeach; ?>
          <div style="margin-top:14px;padding:12px;background:rgba(61,255,160,0.06);border:1px solid rgba(61,255,160,0.2);border-radius:10px;">
            <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:700;">
              <span>Total Net Payroll</span>
              <span style="font-family:var(--font-mono);color:var(--green)">UGX <?= number_format(array_sum(array_map(fn($e)=>$e['salary']-round($e['salary']*0.1)-($e['salary']>500000?round(($e['salary']-500000)*0.3):0),$employees))) ?></span>
            </div>
          </div>
          <button class="btn btn-primary" style="width:100%;margin-top:12px;" onclick="alert('Payroll approved and sent for processing!')">✅ Approve & Process Payroll</button>
        </div>
      </div>
    </div>

    <?php endif; // end employees ?>

    <!-- ==================== TASKS & EVENTS ==================== -->
    <?php if($active_tab === 'tasks'): ?>

    <div class="page-section-header">
      <div>
        <div class="page-section-title">📅 Tasks & Events</div>
        <div class="page-section-sub"><?= count($tasks) ?> tasks · <?= count($today_tasks) ?> due today · <?= count($events) ?> upcoming events</div>
      </div>
      <button class="btn btn-primary" onclick="document.getElementById('addTaskForm').style.display='block'">+ Add Task</button>
    </div>

    <!-- Add Task Form -->
    <div class="card" id="addTaskForm" style="display:none;margin-bottom:20px;">
      <div class="card-header">
        <div class="card-title">➕ Schedule New Task</div>
        <button class="btn btn-outline btn-sm" onclick="this.closest('#addTaskForm').style.display='none'">✕</button>
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="add_task">
          <div class="form-grid">
            <div class="form-group full"><label class="form-label">Task Title</label><input class="form-control" name="title" required placeholder="e.g. Apply fertilizer to Block C"></div>
            <div class="form-group"><label class="form-label">Assigned To</label>
              <select class="form-control" name="assigned">
                <?php foreach($employees as $e): ?><option><?= htmlspecialchars($e['name']) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="form-group"><label class="form-label">Due Date</label><input class="form-control" type="date" name="due" value="<?= date('Y-m-d') ?>"></div>
            <div class="form-group"><label class="form-label">Priority</label>
              <select class="form-control" name="priority"><option>high</option><option>medium</option><option>low</option></select>
            </div>
            <div class="form-group"><label class="form-label">Location</label><input class="form-control" name="location" placeholder="e.g. Block A, Workshop"></div>
          </div>
          <div style="margin-top:14px;"><button type="submit" class="btn btn-primary">Schedule Task</button></div>
        </form>
      </div>
    </div>

    <div class="dash-grid">
      <!-- Tasks List -->
      <div>
        <!-- Today's Due -->
        <?php if(count($today_tasks)>0): ?>
        <div class="card" style="margin-bottom:16px;border-color:rgba(255,92,92,0.3);">
          <div class="card-header" style="background:rgba(255,92,92,0.05);">
            <div class="card-title" style="color:var(--red);">🔴 Due Today (<?= count($today_tasks) ?>)</div>
          </div>
          <div class="card-body">
            <?php foreach($today_tasks as $t): ?>
            <div class="task-item">
              <div class="task-priority <?= $t['priority'] ?>"></div>
              <div class="task-content">
                <div class="task-title"><?= htmlspecialchars($t['title']) ?></div>
                <div class="task-meta">
                  <span>📍 <?= htmlspecialchars($t['location']) ?></span>
                  <span>👤 <?= htmlspecialchars($t['assigned']) ?></span>
                  <span class="badge badge-<?= $t['status']==='in-progress'?'blue':'amber' ?>"><?= $t['status'] ?></span>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- All Tasks -->
        <div class="card">
          <div class="card-header"><div class="card-title">All Tasks</div></div>
          <div class="table-scroll">
            <table class="data-table">
              <thead>
                <tr><th>Task</th><th>Assigned</th><th>Location</th><th>Due</th><th>Priority</th><th>Status</th></tr>
              </thead>
              <tbody>
                <?php foreach($tasks as $t): ?>
                <tr>
                  <td style="font-weight:600;font-size:13px"><?= htmlspecialchars($t['title']) ?></td>
                  <td style="font-size:12px;color:var(--text2)"><?= htmlspecialchars($t['assigned']) ?></td>
                  <td style="font-size:12px;color:var(--text3)"><?= htmlspecialchars($t['location']) ?></td>
                  <td style="font-family:var(--font-mono);font-size:12px;<?= $t['due']===$today?'color:var(--red);font-weight:700':'' ?>"><?= $t['due'] ?></td>
                  <td><span class="badge badge-<?= $t['priority']==='high'?'red':($t['priority']==='medium'?'amber':'green') ?>"><?= $t['priority'] ?></span></td>
                  <td><span class="badge badge-<?= $t['status']==='completed'?'green':($t['status']==='in-progress'?'blue':'amber') ?>"><?= $t['status'] ?></span></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Calendar & Events -->
      <div style="display:flex;flex-direction:column;gap:16px;">
        <!-- Mini Calendar -->
        <div class="card">
          <div class="card-header"><div class="card-title">📅 May 2026</div></div>
          <div class="card-body">
            <?php
            $cal_days = ['SUN','MON','TUE','WED','THU','FRI','SAT'];
            $first = mktime(0,0,0,5,1,2026);
            $first_dow = date('w', $first);
            $days_in_month = date('t', $first);
            $event_days = array_unique(array_map(fn($e)=>(int)date('j',strtotime($e['date'])), $events));
            $task_days = array_unique(array_map(fn($t)=>(int)date('j',strtotime($t['due'])), $tasks));
            ?>
            <div class="calendar-grid">
              <?php foreach($cal_days as $d): ?>
              <div class="cal-day-head"><?= $d ?></div>
              <?php endforeach; ?>
              <?php for($i=0;$i<$first_dow;$i++): ?>
              <div class="cal-day empty">-</div>
              <?php endfor; ?>
              <?php for($d=1;$d<=$days_in_month;$d++):
                $is_today = $d===6; // May 6
                $has_event = in_array($d,$event_days)||in_array($d,$task_days);
              ?>
              <div class="cal-day <?= $is_today?'today':'' ?> <?= $has_event&&!$is_today?'has-event':'' ?>"><?= $d ?></div>
              <?php endfor; ?>
            </div>
          </div>
        </div>

        <!-- Upcoming Events -->
        <div class="card">
          <div class="card-header"><div class="card-title">🗓 Upcoming Events</div>
          <button class="btn btn-outline btn-sm">+ Event</button></div>
          <div class="card-body">
            <?php
            $type_icons=['exhibition'=>'🏆','appointment'=>'🩺','meeting'=>'🤝','delivery'=>'📦'];
            $type_colors=['exhibition'=>'amber','appointment'=>'blue','meeting'=>'purple','delivery'=>'green'];
            foreach($events as $ev): ?>
            <div style="display:flex;align-items:flex-start;gap:12px;padding:10px 0;border-bottom:1px solid rgba(42,84,56,0.3);">
              <div style="font-size:24px;flex-shrink:0;margin-top:2px;"><?= $type_icons[$ev['type']]??'📅' ?></div>
              <div style="flex:1;">
                <div style="font-size:13px;font-weight:700;"><?= htmlspecialchars($ev['title']) ?></div>
                <div style="font-size:11px;font-family:var(--font-mono);color:var(--<?= $type_colors[$ev['type']]?:'text2' ?>);margin:2px 0;"><?= date('l, d M Y',strtotime($ev['date'])) ?></div>
                <div style="font-size:12px;color:var(--text3);"><?= htmlspecialchars($ev['desc']) ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <?php endif; // end tasks ?>

  </div><!-- end .content -->
</div><!-- end .main -->
</div><!-- end .app -->

<!-- ========== JS ========== -->
<script>
// ---- Live clock ----
function updateClock() {
  const now = new Date();
  const options = { weekday:'short', day:'2-digit', month:'short', year:'numeric' };
  const dateStr = now.toLocaleDateString('en-GB', options);
  const timeStr = now.toLocaleTimeString('en-GB', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
  const el = document.getElementById('clock');
  if(el) el.innerHTML = `<span class="live-dot"></span>${dateStr} · ${timeStr}`;
}
setInterval(updateClock, 1000);
updateClock();

// ---- AI Disease Companion ----
const diseaseKB = {
  'yellow leaves': {
    disease: '🌿 Nitrogen Deficiency / Maize Streak Virus',
    symptoms: 'Yellowing starts from older leaves; may progress upward',
    treatment: ['Apply urea fertilizer at 50kg/acre', 'Control aphids (viral vector) with imidacloprid', 'Remove severely infected plants to prevent spread'],
    prevention: 'Use certified disease-free seeds; maintain proper soil fertility'
  },
  'brown spots': {
    disease: '🍅 Early Blight (Alternaria solani)',
    symptoms: 'Dark brown spots with concentric rings; yellowing around spots',
    treatment: ['Apply Mancozeb 80% WP at 2.5g/L water', 'Remove and destroy infected leaves', 'Improve plant spacing for air circulation'],
    prevention: 'Crop rotation; avoid overhead irrigation; use resistant varieties'
  },
  'wilting': {
    disease: '⚠️ Fusarium Wilt / Root Rot',
    symptoms: 'Sudden wilting despite adequate moisture; browning of vascular tissue',
    treatment: ['Drench soil with Metalaxyl fungicide', 'Remove infected plants immediately', 'Improve drainage in affected areas'],
    prevention: 'Soil pH management (6.0-7.0); avoid waterlogging; use treated seeds'
  },
  'white powder': {
    disease: '🌫️ Powdery Mildew (Erysiphe spp.)',
    symptoms: 'White powdery coating on upper leaf surface; stunted growth',
    treatment: ['Spray sulfur-based fungicide at 3g/L', 'Apply potassium bicarbonate solution', 'Remove heavily infected leaves'],
    prevention: 'Avoid overhead watering; ensure good air circulation; reduce humidity'
  },
  'stunted growth': {
    disease: '🦠 Soil Nematodes / Root Knot Disease',
    symptoms: 'Poor growth; yellowing; galls on roots when examined',
    treatment: ['Apply Carbofuran nematicide to soil', 'Use organic matter to improve soil biology', 'Consider Dazomet soil treatment'],
    prevention: 'Crop rotation with non-host plants; use nematode-resistant varieties'
  },
  'black rot': {
    disease: '🖤 Black Rot (Xanthomonas campestris)',
    symptoms: 'V-shaped yellow lesions at leaf edges; black stem discoloration',
    treatment: ['Apply copper-based bactericide', 'Remove infected plant material', 'Avoid working in fields when wet'],
    prevention: 'Use disease-free seeds; 3-year crop rotation; disinfect tools'
  },
  'cattle not eating': {
    disease: '🐄 Bovine Respiratory Disease / Tick Fever',
    symptoms: 'Loss of appetite; lethargy; possible fever and nasal discharge',
    treatment: ['Contact veterinarian immediately', 'Isolate affected animals', 'Check for tick infestation; apply acaricide if needed'],
    prevention: 'Regular vaccination schedule; tick control program; proper nutrition'
  },
  'leaf curl': {
    disease: '🌀 Tomato Leaf Curl Virus (TLCV)',
    symptoms: 'Upward curling of leaves; yellowing; stunting; no fruit set',
    treatment: ['Remove infected plants', 'Control whitefly vector with thiamethoxam', 'Cover young plants with insect netting'],
    prevention: 'Use virus-free transplants; install yellow sticky traps; reflective mulch'
  },
  'blossom drop': {
    disease: '🌸 Physiological Blossom Drop',
    symptoms: 'Flowers falling before fruit set; no visible pathogen',
    treatment: ['Apply calcium spray (calcium nitrate 2g/L)', 'Regulate irrigation — avoid stress', 'Spray boron micronutrient'],
    prevention: 'Maintain consistent soil moisture; avoid temperature extremes; balanced fertilization'
  },
  'ear rot': {
    disease: '🌽 Maize Ear Rot (Gibberella / Fusarium)',
    symptoms: 'Pink/red mold on ears; shrunken kernels; mycotoxin risk',
    treatment: ['Harvest promptly at right moisture', 'Dry maize below 13% moisture immediately', 'Do NOT feed moldy grain to livestock'],
    prevention: 'Reduce insect damage; harvest at correct maturity; use resistant hybrids'
  }
};

function findDiseaseResponse(input) {
  const lower = input.toLowerCase();
  for(const [key, data] of Object.entries(diseaseKB)) {
    if(lower.includes(key.split(' ')[0]) || lower.includes(key)) {
      return `<strong>${data.disease}</strong><br><br>
        <em>Observed:</em> ${data.symptoms}<br><br>
        <strong>🔬 Recommended Treatment:</strong><br>
        ${data.treatment.map(t=>`• ${t}`).join('<br>')}<br><br>
        <strong>🛡️ Prevention:</strong><br>${data.prevention}<br><br>
        <em style="color:var(--text3);font-size:11px;">⚕️ For severe infections, consult a certified agronomist or contact your local agricultural office.</em>`;
    }
  }
  return `I've analyzed your description. While I don't have a specific match in my database for "<em>${input}</em>", here are general steps:<br><br>
    1. <strong>Document symptoms</strong> — photograph affected plants/animals<br>
    2. <strong>Check spreading pattern</strong> — is it isolated or spreading?<br>
    3. <strong>Sample collection</strong> — collect fresh samples for lab testing<br>
    4. <strong>Contact NARO</strong> (National Agricultural Research Organisation) or your local extension officer<br><br>
    <em>Try being more specific: mention the crop type, affected part (leaf/stem/root/fruit), color changes, and how long symptoms have been visible.</em>`;
}

function sendAIMessage() {
  const input = document.getElementById('aiInput');
  const messages = document.getElementById('chatMessages');
  const text = input.value.trim();
  if(!text) return;

  // User message
  const userDiv = document.createElement('div');
  userDiv.className = 'ai-msg user';
  userDiv.innerHTML = `<div class="ai-msg-header">You · Just now</div>${text}`;
  messages.appendChild(userDiv);
  input.value = '';

  // Typing indicator
  const typing = document.createElement('div');
  typing.className = 'ai-msg bot';
  typing.innerHTML = '<div class="ai-msg-header">🤖 SmartFarm AI · Analyzing...</div><span style="color:var(--text3)">⟳ Processing symptoms...</span>';
  messages.appendChild(typing);
  messages.scrollTop = messages.scrollHeight;

  setTimeout(() => {
    typing.innerHTML = `<div class="ai-msg-header">🤖 SmartFarm AI · Now</div>${findDiseaseResponse(text)}`;
    messages.scrollTop = messages.scrollHeight;
  }, 1200);
}

function useSuggestion(el) {
  document.getElementById('aiInput').value = el.textContent;
  sendAIMessage();
}

// ---- Auto-hide forms on success ----
<?php if($action_msg): ?>
document.querySelectorAll('[id$="Form"]').forEach(f => f.style.display = 'none');
<?php endif; ?>

// ---- Smooth load animation ----
document.querySelectorAll('.stat-card, .card').forEach((el, i) => {
  el.style.opacity = '0';
  el.style.transform = 'translateY(16px)';
  el.style.transition = `opacity 0.4s ${i*0.05}s ease, transform 0.4s ${i*0.05}s ease`;
  setTimeout(() => {
    el.style.opacity = '1';
    el.style.transform = 'none';
  }, 50 + i*50);
});

// ---- Weather live fetch (uses Open-Meteo free API) ----
function loadWeather() {
  // Open-Meteo: Kampala coordinates
  fetch('https://api.open-meteo.com/v1/forecast?latitude=0.32&longitude=32.58&current=temperature_2m,relative_humidity_2m,wind_speed_10m,precipitation,cloud_cover,pressure_msl,apparent_temperature,weather_code&temperature_unit=celsius')
    .then(r => r.json())
    .then(data => {
      const c = data.current;
      const wmo = c.weather_code;
      const icons = { 0:'☀️', 1:'🌤', 2:'⛅', 3:'☁️', 45:'🌫', 48:'🌫', 51:'🌦', 53:'🌦', 55:'🌧', 61:'🌧', 63:'🌧', 65:'⛈', 71:'❄️', 80:'🌦', 81:'🌧', 95:'⛈' };
      const descs = { 0:'Clear Sky', 1:'Mainly Clear', 2:'Partly Cloudy', 3:'Overcast', 45:'Foggy', 48:'Icy Fog', 51:'Light Drizzle', 53:'Drizzle', 55:'Heavy Drizzle', 61:'Light Rain', 63:'Rain', 65:'Heavy Rain', 71:'Light Snow', 80:'Rain Showers', 81:'Rain Showers', 95:'Thunderstorm' };
      const icon = icons[wmo] || '🌤';
      const desc = descs[wmo] || 'Variable';
      ['w-temp','dash-temp'].forEach(id => { const el=document.getElementById(id); if(el) el.textContent=Math.round(c.temperature_2m)+'°C'; });
      if(document.getElementById('w-icon')) document.getElementById('w-icon').textContent = icon;
      if(document.getElementById('w-desc')) document.getElementById('w-desc').textContent = desc;
      if(document.getElementById('w-feels')) document.getElementById('w-feels').textContent = `Feels like ${Math.round(c.apparent_temperature)}°C`;
      if(document.getElementById('w-humidity')) document.getElementById('w-humidity').textContent = c.relative_humidity_2m+'%';
      if(document.getElementById('w-wind')) document.getElementById('w-wind').textContent = c.wind_speed_10m+' km/h';
      if(document.getElementById('w-pressure')) document.getElementById('w-pressure').textContent = Math.round(c.pressure_msl);
      if(document.getElementById('w-cloud')) document.getElementById('w-cloud').textContent = c.cloud_cover+'%';
      if(document.getElementById('w-precip')) document.getElementById('w-precip').textContent = c.precipitation+' mm';
      // Farming advisory
      const adv = document.getElementById('farm-advisory');
      if(adv) {
        if(c.precipitation > 2) adv.textContent = '🌧 Rain expected — suspend spray operations and irrigation. Check drainage channels. Avoid field work to prevent soil compaction.';
        else if(c.temperature_2m > 30) adv.textContent = '☀️ High temperatures — increase irrigation frequency. Monitor for heat stress. Harvest of mature crops is ideal today.';
        else if(c.relative_humidity_2m > 80) adv.textContent = '💧 High humidity — elevated risk of fungal diseases. Inspect crops for blight and mold. Improve canopy ventilation.';
        else adv.textContent = '✅ Favorable farming conditions. Good window for field operations, spray applications, and soil preparation activities.';
      }
    })
    .catch(() => console.log('Weather API unavailable — showing demo data'));
}
loadWeather();
setInterval(loadWeather, 600000); // refresh every 10 min
</script>
</body>
</html>
