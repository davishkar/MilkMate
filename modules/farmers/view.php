<?php
$pageTitle = 'Farmer Details';
require_once dirname(dirname(__DIR__)).'/config/app.php';
requireLogin();
$pdo = db();
$id = (int)($_GET['id']??0);
if(!$id) redirect(APP_URL.'/modules/farmers/index.php');
$f = $pdo->prepare("SELECT * FROM farmers WHERE id=? LIMIT 1");
$f->execute([$id]); $f=$f->fetch();
if(!$f) redirect(APP_URL.'/modules/farmers/index.php');
$month = date('Y-m');
$stats = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) as qty, COALESCE(SUM(amount),0) as amt, COUNT(*) as entries FROM milk_collections WHERE farmer_id=? AND DATE_FORMAT(collection_date,'%Y-%m')=?");
$stats->execute([$id,$month]); $stats=$stats->fetch();
$totalAmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM milk_collections WHERE farmer_id=?");
$totalAmt->execute([$id]); $totalAmt=$totalAmt->fetchColumn();
$recentCols = $pdo->prepare("SELECT * FROM milk_collections WHERE farmer_id=? ORDER BY collection_date DESC, shift LIMIT 20");
$recentCols->execute([$id]); $recentCols=$recentCols->fetchAll();
$bills = $pdo->prepare("SELECT * FROM bills WHERE farmer_id=? ORDER BY created_at DESC LIMIT 10");
$bills->execute([$id]); $bills=$bills->fetchAll();
include dirname(dirname(__DIR__)).'/includes/header.php';
?>
<div class="page-header">
  <div>
    <h1>👨‍🌾 <?=htmlspecialchars($f['name'])?></h1>
    <p class="text-muted"><?=htmlspecialchars($f['farmer_code'])?> | <?=htmlspecialchars($f['village']??'')?>, <?=htmlspecialchars($f['district']??'')?></p>
  </div>
  <div class="d-flex gap-2">
    <a href="edit.php?id=<?=$id?>" class="btn btn-primary">✏️ Edit</a>
    <a href="index.php" class="btn btn-ghost">← Back</a>
  </div>
</div>

<div style="display:grid;grid-template-columns:300px 1fr;gap:20px;" class="responsive-grid">
  <!-- Profile Card -->
  <div>
    <div class="card" style="text-align:center;padding:32px;">
      <?php if($f['photo']&&file_exists(UPLOAD_PATH.$f['photo'])): ?>
      <img src="<?=UPLOAD_URL.htmlspecialchars($f['photo'])?>" style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--primary-light);margin-bottom:16px;">
      <?php else: ?>
      <div style="width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-size:42px;font-weight:700;margin:0 auto 16px;"><?=strtoupper(substr($f['name'],0,1))?></div>
      <?php endif; ?>
      <h2 style="font-size:20px;"><?=htmlspecialchars($f['name'])?></h2>
      <?php if($f['name_marathi']): ?><p class="text-muted"><?=htmlspecialchars($f['name_marathi'])?></p><?php endif; ?>
      <div class="d-flex gap-2 mt-3" style="justify-content:center;">
        <span class="badge badge-<?=$f['is_active']?'success':'danger'?>"><?=$f['is_active']?'Active':'Inactive'?></span>
        <span class="badge badge-info"><?=ucfirst($f['animal_type'])?></span>
      </div>
    </div>
    <div class="card mt-3">
      <h3 style="font-size:14px;margin-bottom:12px;">📋 Details</h3>
      <?php $details=[['📞','Phone',$f['phone']],['💬','WhatsApp',$f['whatsapp']??'—'],['📧','Email',$f['email']??'—'],['🏘️','Village',$f['village']??'—'],['📍','Taluka',$f['taluka']??'—'],['🗺️','District',$f['district']??'—'],['📅','Joined',formatDate($f['joining_date'])],['🐄','Animals',$f['animal_count'].' '.ucfirst($f['animal_type'])]]; ?>
      <?php foreach($details as [$ic,$lb,$vl]): ?>
      <div style="display:flex;gap:8px;padding:6px 0;border-bottom:1px solid var(--border);font-size:13px;">
        <span><?=$ic?></span><span class="text-muted" style="width:80px;flex-shrink:0;"><?=$lb?></span><span style="font-weight:500;"><?=htmlspecialchars($vl)?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if($f['bank_name']): ?>
    <div class="card mt-3">
      <h3 style="font-size:14px;margin-bottom:12px;">🏦 Bank Details</h3>
      <div style="font-size:13px;line-height:2;"><div><?=htmlspecialchars($f['bank_name'])?></div><div class="text-muted">A/C: <?=htmlspecialchars($f['bank_account']??'')?></div><div class="text-muted">IFSC: <?=htmlspecialchars($f['ifsc_code']??'')?></div></div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Stats + History -->
  <div>
    <div class="stat-grid mb-4" style="grid-template-columns:repeat(3,1fr);">
      <div class="stat-card teal"><div class="stat-label">This Month Qty</div><div class="stat-value"><?=number_format($stats['qty'],1)?> L</div></div>
      <div class="stat-card amber"><div class="stat-label">This Month Amt</div><div class="stat-value"><?=formatCurrency($stats['amt'])?></div></div>
      <div class="stat-card green"><div class="stat-label">Total Earned</div><div class="stat-value"><?=formatCurrency($totalAmt)?></div></div>
    </div>

    <div class="card mb-4">
      <div class="card-header"><h3 class="card-title">🍼 Recent Collections</h3><a href="<?=APP_URL?>/modules/collections/index.php" class="btn btn-ghost btn-sm">Add Entry</a></div>
      <div class="table-wrapper">
        <table><thead><tr><th>Date</th><th>Shift</th><th>Qty(L)</th><th>FAT</th><th>SNF</th><th>Rate</th><th>Amount</th><th>Billed</th></tr></thead>
        <tbody>
          <?php if(empty($recentCols)): ?><tr><td colspan="8" class="table-empty">No collection data.</td></tr>
          <?php else: foreach($recentCols as $c): ?>
          <tr><td><?=formatDate($c['collection_date'])?></td><td><?=ucfirst($c['shift'])?></td><td><?=number_format($c['quantity'],2)?></td><td><?=number_format($c['fat'],2)?></td><td><?=number_format($c['snf'],2)?></td><td><?=formatCurrency($c['rate'])?></td><td style="font-weight:600;"><?=formatCurrency($c['amount'])?></td><td><?=$c['is_billed']?'<span class="badge badge-success">Yes</span>':'<span class="badge badge-warning">No</span>'?></td></tr>
          <?php endforeach; endif; ?>
        </tbody></table>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3 class="card-title">🧾 Bills</h3><a href="<?=APP_URL?>/modules/billing/generate.php?farmer_id=<?=$id?>" class="btn btn-primary btn-sm">Generate Bill</a></div>
      <div class="table-wrapper">
        <table><thead><tr><th>Bill No.</th><th>Period</th><th>Amount</th><th>Net</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
          <?php if(empty($bills)): ?><tr><td colspan="6" class="table-empty">No bills yet.</td></tr>
          <?php else: foreach($bills as $b): ?>
          <tr><td><?=htmlspecialchars($b['bill_number'])?></td><td style="font-size:12px;"><?=formatDate($b['period_from'])?> – <?=formatDate($b['period_to'])?></td><td><?=formatCurrency($b['total_amount'])?></td><td style="font-weight:700;"><?=formatCurrency($b['net_amount'])?></td>
          <td><span class="badge badge-<?=$b['payment_status']==='paid'?'success':($b['payment_status']==='partial'?'warning':'danger')?>"><?=ucfirst($b['payment_status'])?></span></td>
          <td><a href="<?=APP_URL?>/modules/billing/view.php?id=<?=$b['id']?>" class="btn btn-ghost btn-sm">View</a></td></tr>
          <?php endforeach; endif; ?>
        </tbody></table>
      </div>
    </div>
  </div>
</div>
<style>@media(max-width:900px){.responsive-grid{grid-template-columns:1fr!important}}</style>
<?php include dirname(dirname(__DIR__)).'/includes/footer.php'; ?>
