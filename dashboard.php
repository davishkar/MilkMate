<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/config/app.php';
requireLogin();

$pdo = db();
$today = date('Y-m-d');
$thisMonth = date('Y-m');

// Stats
$totalFarmers  = $pdo->query("SELECT COUNT(*) FROM farmers WHERE is_active=1")->fetchColumn();
$todayQty      = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM milk_collections WHERE collection_date=?");
$todayQty->execute([$today]); $todayQty = $todayQty->fetchColumn();
$todayAmt      = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM milk_collections WHERE collection_date=?");
$todayAmt->execute([$today]); $todayAmt = $todayAmt->fetchColumn();
$monthQty      = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM milk_collections WHERE DATE_FORMAT(collection_date,'%Y-%m')=?");
$monthQty->execute([$thisMonth]); $monthQty = $monthQty->fetchColumn();
$monthAmt      = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM milk_collections WHERE DATE_FORMAT(collection_date,'%Y-%m')=?");
$monthAmt->execute([$thisMonth]); $monthAmt = $monthAmt->fetchColumn();
$pendingBills  = $pdo->query("SELECT COUNT(*) FROM bills WHERE payment_status='pending'")->fetchColumn();
$pendingAmt    = $pdo->query("SELECT COALESCE(SUM(net_amount),0) FROM bills WHERE payment_status='pending'")->fetchColumn();
$monthExpense  = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE DATE_FORMAT(expense_date,'%Y-%m')=?");
$monthExpense->execute([$thisMonth]); $monthExpense = $monthExpense->fetchColumn();

// Last 7 days chart data
$last7 = $pdo->query("SELECT collection_date as d, SUM(quantity) as qty, SUM(amount) as amt
  FROM milk_collections WHERE collection_date >= DATE_SUB(CURDATE(),INTERVAL 6 DAY)
  GROUP BY collection_date ORDER BY collection_date")->fetchAll();

// Recent activities
$recent = $pdo->query("SELECT al.*, u.name as uname FROM activity_logs al LEFT JOIN users u ON al.user_id=u.id ORDER BY al.created_at DESC LIMIT 8")->fetchAll();

// Top farmers this month
$topFarmers = $pdo->prepare("SELECT f.name, SUM(mc.quantity) as qty, SUM(mc.amount) as amt
  FROM milk_collections mc JOIN farmers f ON mc.farmer_id=f.id
  WHERE DATE_FORMAT(mc.collection_date,'%Y-%m')=?
  GROUP BY mc.farmer_id ORDER BY qty DESC LIMIT 5");
$topFarmers->execute([$thisMonth]); $topFarmers = $topFarmers->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div>
    <h1>Dashboard 📊</h1>
    <p class="text-muted">Welcome back, <strong><?= htmlspecialchars($_SESSION['name']) ?></strong> — <?= date('l, d F Y') ?></p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= APP_URL ?>/modules/collections/add.php" class="btn btn-primary">➕ Add Collection</a>
    <a href="<?= APP_URL ?>/modules/billing/generate.php" class="btn btn-accent">🧾 Generate Bill</a>
  </div>
</div>

<!-- Stat Cards -->
<div class="stat-grid mb-4">
  <div class="stat-card teal">
    <span class="stat-icon">👨‍🌾</span>
    <div class="stat-label">Active Farmers</div>
    <div class="stat-value"><?= $totalFarmers ?></div>
    <div class="stat-sub">Total registered</div>
  </div>
  <div class="stat-card amber">
    <span class="stat-icon">🍼</span>
    <div class="stat-label">Today's Collection</div>
    <div class="stat-value"><?= number_format($todayQty,1) ?> L</div>
    <div class="stat-sub"><?= formatCurrency($todayAmt) ?> earned</div>
  </div>
  <div class="stat-card blue">
    <span class="stat-icon">📅</span>
    <div class="stat-label">Month Collection</div>
    <div class="stat-value"><?= number_format($monthQty,0) ?> L</div>
    <div class="stat-sub"><?= formatCurrency($monthAmt) ?></div>
  </div>
  <div class="stat-card green">
    <span class="stat-icon">💰</span>
    <div class="stat-label">Month Revenue</div>
    <div class="stat-value"><?= formatCurrency($monthAmt) ?></div>
    <div class="stat-sub">Expense: <?= formatCurrency($monthExpense) ?></div>
  </div>
  <div class="stat-card red">
    <span class="stat-icon">🧾</span>
    <div class="stat-label">Pending Bills</div>
    <div class="stat-value"><?= $pendingBills ?></div>
    <div class="stat-sub"><?= formatCurrency($pendingAmt) ?> due</div>
  </div>
</div>

<!-- Charts + Top Farmers -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px;" class="responsive-grid">
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">📈 Last 7 Days Collection</h3>
      <span class="badge badge-primary">Quantity (L)</span>
    </div>
    <div class="chart-box">
      <canvas id="collectionChart"></canvas>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h3 class="card-title">🏆 Top Farmers</h3>
      <span class="badge badge-secondary"><?= date('M Y') ?></span>
    </div>
    <div style="display:flex;flex-direction:column;gap:12px;">
      <?php if (empty($topFarmers)): ?>
        <p class="text-muted text-center" style="padding:24px;">No data yet</p>
      <?php else: foreach ($topFarmers as $i => $f): ?>
      <div style="display:flex;align-items:center;gap:12px;">
        <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-light));display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;"><?= $i+1 ?></div>
        <div style="flex:1;">
          <div style="font-size:13.5px;font-weight:600;"><?= htmlspecialchars($f['name']) ?></div>
          <div class="text-muted" style="font-size:12px;"><?= number_format($f['qty'],1) ?> L</div>
        </div>
        <div style="font-size:13px;font-weight:600;color:var(--primary-light);"><?= formatCurrency($f['amt']) ?></div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<!-- Quick Actions + Recent Activity -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" class="responsive-grid">
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">⚡ Quick Actions</h3>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
      <?php $actions = [
        ['➕','Add Farmer','farmers/add.php','teal'],
        ['🍼','Morning Entry','collections/add.php?shift=morning','blue'],
        ['🌙','Evening Entry','collections/add.php?shift=evening','amber'],
        ['🧾','Generate Bill','billing/generate.php','green'],
        ['💸','Add Expense','accounts/expenses.php','red'],
        ['📋','View Report','reports/index.php','secondary'],
      ]; foreach ($actions as $a): ?>
      <a href="<?= APP_URL ?>/modules/<?= $a[2] ?>" class="btn btn-ghost" style="justify-content:flex-start;gap:10px;padding:12px 14px;">
        <span style="font-size:20px;"><?= $a[0] ?></span>
        <span style="font-size:13px;"><?= $a[1] ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h3 class="card-title">🕐 Recent Activity</h3>
      <a href="<?= APP_URL ?>/modules/reports/index.php" class="btn btn-ghost btn-sm">View All</a>
    </div>
    <div style="display:flex;flex-direction:column;gap:8px;max-height:260px;overflow-y:auto;">
      <?php if (empty($recent)): ?>
        <p class="text-muted text-center" style="padding:24px;">No activity yet</p>
      <?php else: foreach ($recent as $log): ?>
      <div style="display:flex;align-items:flex-start;gap:10px;padding:8px;border-radius:8px;background:var(--glass);">
        <div style="font-size:18px;">
          <?= match($log['module']) {
            'auth' => '🔐', 'farmers' => '👨‍🌾', 'collections' => '🍼',
            'billing' => '🧾', 'accounts' => '💰', default => '📌'
          } ?>
        </div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:12.5px;font-weight:500;"><?= htmlspecialchars($log['action']) ?></div>
          <div class="text-muted" style="font-size:11.5px;"><?= htmlspecialchars($log['uname'] ?? 'System') ?> · <?= date('d M, h:i A', strtotime($log['created_at'])) ?></div>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<script>
// Chart.js CDN alternative - simple canvas bar chart
<?php
$chartLabels = []; $chartQty = []; $chartAmt = [];
$map = [];
foreach ($last7 as $r) { $map[$r['d']] = $r; }
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('d M', strtotime($d));
    $chartQty[] = isset($map[$d]) ? round($map[$d]['qty'],1) : 0;
    $chartAmt[] = isset($map[$d]) ? round($map[$d]['amt'],0) : 0;
}
?>
const labels = <?= json_encode($chartLabels) ?>;
const qtyData = <?= json_encode($chartQty) ?>;
const amtData = <?= json_encode($chartAmt) ?>;

function drawBarChart(canvasId, labels, data, color) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  const W = canvas.offsetWidth, H = canvas.offsetHeight;
  canvas.width = W; canvas.height = H;
  const pad = {top:20,right:20,bottom:40,left:50};
  const chartW = W - pad.left - pad.right;
  const chartH = H - pad.top - pad.bottom;
  const max = Math.max(...data, 1);
  const barW = (chartW / data.length) * 0.6;
  const gap = chartW / data.length;

  ctx.clearRect(0,0,W,H);

  // Grid lines
  ctx.strokeStyle = 'rgba(255,255,255,0.06)';
  ctx.lineWidth = 1;
  for (let i=0;i<=4;i++) {
    const y = pad.top + chartH - (chartH/4)*i;
    ctx.beginPath(); ctx.moveTo(pad.left,y); ctx.lineTo(pad.left+chartW,y); ctx.stroke();
    ctx.fillStyle = 'rgba(148,163,184,0.6)';
    ctx.font = '11px Poppins,sans-serif';
    ctx.textAlign = 'right';
    ctx.fillText(Math.round((max/4)*i), pad.left-6, y+4);
  }

  // Bars
  data.forEach((v,i) => {
    const barH = (v/max)*chartH;
    const x = pad.left + gap*i + (gap-barW)/2;
    const y = pad.top + chartH - barH;
    const grad = ctx.createLinearGradient(x,y,x,y+barH);
    grad.addColorStop(0, color+'ff');
    grad.addColorStop(1, color+'44');
    ctx.fillStyle = grad;
    ctx.beginPath();
    ctx.roundRect(x,y,barW,barH,4);
    ctx.fill();

    // Label
    ctx.fillStyle = 'rgba(148,163,184,0.8)';
    ctx.font = '11px Poppins,sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText(labels[i], x+barW/2, H-10);

    // Value
    if (v > 0) {
      ctx.fillStyle = '#fff';
      ctx.font = '11px Poppins,sans-serif';
      ctx.fillText(v, x+barW/2, y-5);
    }
  });
}

window.addEventListener('load', () => {
  drawBarChart('collectionChart', labels, qtyData, '#14b8a6');
});
window.addEventListener('resize', () => {
  drawBarChart('collectionChart', labels, qtyData, '#14b8a6');
});
</script>

<style>
@media(max-width:768px) { .responsive-grid { grid-template-columns:1fr !important; } }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
