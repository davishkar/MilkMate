<?php
$pageTitle = 'Billing';
require_once dirname(dirname(__DIR__)) . '/config/app.php';
requireLogin();

$pdo = db();
$status = sanitize($_GET['status'] ?? 'all');
$search = sanitize($_GET['search'] ?? '');
$page = max(1,(int)($_GET['page']??1)); $perPage = 15; $offset = ($page-1)*$perPage;

$where = "WHERE 1=1";
$params = [];
if ($search) { $where.=" AND (f.name LIKE ? OR b.bill_number LIKE ?)"; $like="%$search%"; $params=[$like,$like]; }
if ($status !== 'all') { $where.=" AND b.payment_status=?"; $params[]=$status; }

$total = $pdo->prepare("SELECT COUNT(*) FROM bills b JOIN farmers f ON b.farmer_id=f.id $where");
$total->execute($params); $total = $total->fetchColumn();
$totalPages = ceil($total/$perPage);

$bills = $pdo->prepare("SELECT b.*, f.name as farmer_name, f.farmer_code, f.phone as farmer_phone
  FROM bills b JOIN farmers f ON b.farmer_id=f.id $where ORDER BY b.created_at DESC LIMIT $perPage OFFSET $offset");
$bills->execute($params); $bills = $bills->fetchAll();

$pendingAmt = $pdo->query("SELECT COALESCE(SUM(net_amount),0) FROM bills WHERE payment_status='pending'")->fetchColumn();

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div class="page-header">
  <div>
    <h1>🧾 Billing</h1>
    <p class="text-muted">Generate and manage farmer payment bills</p>
  </div>
  <a href="generate.php" class="btn btn-primary">➕ Generate Bill</a>
</div>

<div class="stat-grid mb-4" style="grid-template-columns:repeat(4,1fr);">
  <div class="stat-card teal"><div class="stat-label">Total Bills</div><div class="stat-value"><?= $pdo->query("SELECT COUNT(*) FROM bills")->fetchColumn() ?></div></div>
  <div class="stat-card red"><div class="stat-label">Pending</div><div class="stat-value"><?= $pdo->query("SELECT COUNT(*) FROM bills WHERE payment_status='pending'")->fetchColumn() ?></div><div class="stat-sub"><?= formatCurrency($pendingAmt) ?></div></div>
  <div class="stat-card amber"><div class="stat-label">Partial</div><div class="stat-value"><?= $pdo->query("SELECT COUNT(*) FROM bills WHERE payment_status='partial'")->fetchColumn() ?></div></div>
  <div class="stat-card green"><div class="stat-label">Paid</div><div class="stat-value"><?= $pdo->query("SELECT COUNT(*) FROM bills WHERE payment_status='paid'")->fetchColumn() ?></div></div>
</div>

<div class="card mb-4" style="padding:16px 20px;">
  <div style="display:flex;gap:12px;flex-wrap:wrap;">
    <div class="navbar-search" style="width:260px;"><span>🔍</span><input type="text" id="srch" placeholder="Search farmer, bill no..." value="<?= htmlspecialchars($search) ?>"></div>
    <select id="status-f" class="form-control" style="width:160px;">
      <?php foreach(['all'=>'All Status','pending'=>'Pending','partial'=>'Partial','paid'=>'Paid'] as $k=>$v): ?>
      <option value="<?=$k?>" <?=$status===$k?'selected':''?>><?=$v?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<div class="card">
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Bill #</th><th>Farmer</th><th>Period</th><th>Qty(L)</th><th>Amount</th><th>Net</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if(empty($bills)): ?>
        <tr><td colspan="8" class="table-empty">No bills found. <a href="generate.php" style="color:var(--primary-light);">Generate first bill →</a></td></tr>
        <?php else: foreach($bills as $b): ?>
        <tr id="bill-<?=$b['id']?>">
          <td><strong><?=htmlspecialchars($b['bill_number'])?></strong></td>
          <td><?=htmlspecialchars($b['farmer_name'])?><br><span class="text-muted" style="font-size:11.5px;"><?=htmlspecialchars($b['farmer_code'])?></span></td>
          <td style="font-size:12.5px;"><?=formatDate($b['period_from'])?> – <?=formatDate($b['period_to'])?></td>
          <td><?=number_format($b['total_quantity'],1)?></td>
          <td><?=formatCurrency($b['total_amount'])?></td>
          <td style="font-weight:700;color:var(--primary-light);"><?=formatCurrency($b['net_amount'])?></td>
          <td><?php
            $badge = match($b['payment_status']){
              'paid'=>'badge-success','partial'=>'badge-warning',default=>'badge-danger'
            };
            echo "<span class='badge $badge'>".ucfirst($b['payment_status'])."</span>";
          ?></td>
          <td>
            <div class="d-flex gap-2">
              <a href="view.php?id=<?=$b['id']?>" class="btn btn-ghost btn-icon btn-sm" title="View">👁️</a>
              <a href="print.php?id=<?=$b['id']?>" target="_blank" class="btn btn-ghost btn-icon btn-sm" title="Print">🖨️</a>
              <?php if($b['payment_status']!=='paid'): ?>
              <button onclick="markPaid(<?=$b['id']?>)" class="btn btn-primary btn-sm">✅ Pay</button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <?php if($totalPages>1): ?>
  <div style="display:flex;justify-content:center;gap:6px;padding:16px;">
    <?php for($i=1;$i<=$totalPages;$i++): ?>
    <a href="?page=<?=$i?>&status=<?=$status?>&search=<?=urlencode($search)?>" class="btn <?=$i===$page?'btn-primary':'btn-ghost'?> btn-sm"><?=$i?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Pay Modal -->
<div class="modal-overlay hidden" id="pay-modal">
  <div class="modal">
    <div class="modal-header"><h3>💳 Mark as Paid</h3><button onclick="Modal.close('pay-modal')" style="background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:20px;">✕</button></div>
    <div class="modal-body">
      <form id="pay-form">
        <input type="hidden" name="bill_id" id="pay-bill-id">
        <input type="hidden" name="action" value="pay">
        <div class="form-group"><label class="form-label">Payment Mode</label>
          <select name="payment_mode" class="form-control">
            <option value="cash">💵 Cash</option><option value="bank_transfer">🏦 Bank Transfer</option>
            <option value="upi">📱 UPI</option><option value="cheque">🧾 Cheque</option>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Payment Date</label><input type="date" name="payment_date" class="form-control" value="<?=date('Y-m-d')?>"></div>
        <div class="form-group"><label class="form-label">Reference / UTR</label><input type="text" name="reference" class="form-control" placeholder="Optional"></div>
        <div class="modal-footer" style="padding:0;margin-top:16px;">
          <button type="button" onclick="Modal.close('pay-modal')" class="btn btn-ghost">Cancel</button>
          <button type="submit" class="btn btn-primary" id="pay-submit-btn">✅ Confirm Payment</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
let srchTimer;
document.getElementById('srch').addEventListener('input',function(){clearTimeout(srchTimer);srchTimer=setTimeout(()=>{window.location.href=`?search=${encodeURIComponent(this.value)}&status=${document.getElementById('status-f').value}`;},500);});
document.getElementById('status-f').addEventListener('change',function(){window.location.href=`?status=${this.value}&search=${encodeURIComponent(document.getElementById('srch').value)}`;});

function markPaid(id){
  document.getElementById('pay-bill-id').value=id;
  Modal.open('pay-modal');
}
document.getElementById('pay-form').addEventListener('submit',async function(e){
  e.preventDefault();
  const btn=document.getElementById('pay-submit-btn');
  Form.setLoading(btn,true);
  const res=await Ajax.post('<?=APP_URL?>/ajax/billing.php',new FormData(this));
  Form.setLoading(btn,false);
  if(res.success){Toast.success('Paid!','Payment recorded.');Modal.close('pay-modal');setTimeout(()=>location.reload(),1000);}
  else Toast.error('Error',res.message);
});
</script>
<?php include dirname(dirname(__DIR__)).'/includes/footer.php'; ?>
