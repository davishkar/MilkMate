<?php
$pageTitle = 'Reports';
require_once dirname(dirname(__DIR__)) . '/config/app.php';
requireLogin();
$pdo = db();
$type   = sanitize($_GET['type'] ?? 'daily');
$date   = sanitize($_GET['date'] ?? date('Y-m-d'));
$month  = sanitize($_GET['month'] ?? date('Y-m'));
$fid    = (int)($_GET['farmer_id'] ?? 0);
$farmers= $pdo->query("SELECT id,farmer_code,name FROM farmers WHERE is_active=1 ORDER BY farmer_code")->fetchAll();

// Fetch report data
$rows = [];
$title = '';
switch($type){
  case 'daily':
    $title = "Daily Report — ".formatDate($date);
    $stmt = $pdo->prepare("SELECT mc.*, f.name as fname, f.farmer_code FROM milk_collections mc JOIN farmers f ON mc.farmer_id=f.id WHERE mc.collection_date=? ORDER BY mc.shift, f.farmer_code");
    $stmt->execute([$date]); $rows=$stmt->fetchAll();
    break;
  case 'monthly':
    $title = "Monthly Report — ".date('F Y',strtotime($month.'-01'));
    $stmt = $pdo->prepare("SELECT f.farmer_code, f.name as fname, SUM(mc.quantity) as qty, SUM(mc.amount) as amt, COUNT(*) as entries, AVG(mc.fat) as avg_fat, AVG(mc.snf) as avg_snf FROM milk_collections mc JOIN farmers f ON mc.farmer_id=f.id WHERE DATE_FORMAT(mc.collection_date,'%Y-%m')=? GROUP BY mc.farmer_id ORDER BY qty DESC");
    $stmt->execute([$month]); $rows=$stmt->fetchAll();
    break;
  case 'farmer':
    $title = "Farmer-wise Report";
    if($fid){
      $stmt = $pdo->prepare("SELECT mc.collection_date, mc.shift, mc.quantity, mc.fat, mc.snf, mc.rate, mc.amount FROM milk_collections mc WHERE mc.farmer_id=? AND DATE_FORMAT(mc.collection_date,'%Y-%m')=? ORDER BY mc.collection_date, mc.shift");
      $stmt->execute([$fid,$month]); $rows=$stmt->fetchAll();
    }
    break;
}

include dirname(dirname(__DIR__)).'/includes/header.php';
?>
<div class="page-header">
  <div><h1>📋 Reports</h1><p class="text-muted"><?=htmlspecialchars($title)?></p></div>
  <button onclick="window.print()" class="btn btn-ghost no-print">🖨️ Print</button>
</div>

<!-- Filter Bar -->
<div class="card mb-4 no-print" style="padding:16px 20px;">
  <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
    <div class="form-group" style="margin:0;">
      <label class="form-label">Report Type</label>
      <select name="type" class="form-control" style="width:160px;" onchange="this.form.submit()">
        <option value="daily" <?=$type==='daily'?'selected':''?>>📅 Daily</option>
        <option value="monthly" <?=$type==='monthly'?'selected':''?>>📆 Monthly</option>
        <option value="farmer" <?=$type==='farmer'?'selected':''?>>👨‍🌾 Farmer-wise</option>
      </select>
    </div>
    <?php if($type==='daily'): ?>
    <div class="form-group" style="margin:0;"><label class="form-label">Date</label><input type="date" name="date" class="form-control" value="<?=$date?>" onchange="this.form.submit()"></div>
    <?php elseif($type==='monthly'||$type==='farmer'): ?>
    <div class="form-group" style="margin:0;"><label class="form-label">Month</label><input type="month" name="month" class="form-control" value="<?=$month?>" onchange="this.form.submit()"></div>
    <?php endif; ?>
    <?php if($type==='farmer'): ?>
    <div class="form-group" style="margin:0;"><label class="form-label">Farmer</label>
      <select name="farmer_id" class="form-control" style="width:220px;" onchange="this.form.submit()">
        <option value="">— All Farmers —</option>
        <?php foreach($farmers as $f): ?><option value="<?=$f['id']?>" <?=$fid==$f['id']?'selected':''?>><?=htmlspecialchars($f['farmer_code'].' - '.$f['name'])?></option><?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="table-wrapper">
  <?php if($type==='daily'): ?>
    <table id="report-table">
      <thead><tr><th>Farmer</th><th>Shift</th><th>Animal</th><th>Qty(L)</th><th>FAT</th><th>SNF</th><th>Rate</th><th>Amount</th></tr></thead>
      <tbody>
        <?php if(empty($rows)): ?><tr><td colspan="8" class="table-empty">No data for this date.</td></tr>
        <?php else: $tot=0; foreach($rows as $r): $tot+=$r['amount']; ?>
        <tr><td><?=htmlspecialchars($r['farmer_code'].' - '.$r['fname'])?></td><td><?=ucfirst($r['shift'])?></td><td><?=ucfirst($r['animal_type'])?></td><td><?=number_format($r['quantity'],2)?></td><td><?=number_format($r['fat'],2)?></td><td><?=number_format($r['snf'],2)?></td><td><?=formatCurrency($r['rate'])?></td><td style="font-weight:600;"><?=formatCurrency($r['amount'])?></td></tr>
        <?php endforeach; ?>
        <tr style="background:rgba(20,184,166,0.1);"><td colspan="7" style="font-weight:700;text-align:right;padding-right:16px;">Total:</td><td style="font-weight:700;color:var(--primary-light);"><?=formatCurrency($tot)?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  <?php elseif($type==='monthly'): ?>
    <table id="report-table">
      <thead><tr><th>Code</th><th>Farmer</th><th>Entries</th><th>Total Qty(L)</th><th>Avg FAT</th><th>Avg SNF</th><th>Total Amount</th></tr></thead>
      <tbody>
        <?php if(empty($rows)): ?><tr><td colspan="7" class="table-empty">No data for this month.</td></tr>
        <?php else: $tot=0; foreach($rows as $r): $tot+=$r['amt']; ?>
        <tr><td><?=htmlspecialchars($r['farmer_code'])?></td><td><?=htmlspecialchars($r['fname'])?></td><td><?=$r['entries']?></td><td><?=number_format($r['qty'],2)?></td><td><?=number_format($r['avg_fat'],2)?></td><td><?=number_format($r['avg_snf'],2)?></td><td style="font-weight:700;color:var(--primary-light);"><?=formatCurrency($r['amt'])?></td></tr>
        <?php endforeach; ?>
        <tr style="background:rgba(20,184,166,0.1);"><td colspan="6" style="font-weight:700;text-align:right;padding-right:16px;">Total:</td><td style="font-weight:700;color:var(--primary-light);"><?=formatCurrency($tot)?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  <?php elseif($type==='farmer'): ?>
    <table id="report-table">
      <thead><tr><th>Date</th><th>Shift</th><th>Qty(L)</th><th>FAT</th><th>SNF</th><th>Rate</th><th>Amount</th></tr></thead>
      <tbody>
        <?php if(empty($rows)&&$fid): ?><tr><td colspan="7" class="table-empty">No data.</td></tr>
        <?php elseif(!$fid): ?><tr><td colspan="7" class="table-empty">Select a farmer to view report.</td></tr>
        <?php else: $tot=0; foreach($rows as $r): $tot+=$r['amount']; ?>
        <tr><td><?=formatDate($r['collection_date'])?></td><td><?=ucfirst($r['shift'])?></td><td><?=number_format($r['quantity'],2)?></td><td><?=number_format($r['fat'],2)?></td><td><?=number_format($r['snf'],2)?></td><td><?=formatCurrency($r['rate'])?></td><td style="font-weight:600;"><?=formatCurrency($r['amount'])?></td></tr>
        <?php endforeach; ?>
        <tr style="background:rgba(20,184,166,0.1);"><td colspan="6" style="font-weight:700;text-align:right;padding-right:16px;">Total:</td><td style="font-weight:700;color:var(--primary-light);"><?=formatCurrency($tot)?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  <?php endif; ?>
  </div>
</div>
<?php include dirname(dirname(__DIR__)).'/includes/footer.php'; ?>
