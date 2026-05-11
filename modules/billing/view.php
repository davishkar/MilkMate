<?php
$pageTitle = 'Bill Details';
require_once dirname(dirname(__DIR__)).'/config/app.php';
requireLogin();
$pdo = db();
$id = (int)($_GET['id']??0);
if(!$id) redirect(APP_URL.'/modules/billing/index.php');
$bill = $pdo->prepare("SELECT b.*, f.name as fname, f.farmer_code, f.phone FROM bills b JOIN farmers f ON b.farmer_id=f.id WHERE b.id=? LIMIT 1");
$bill->execute([$id]); $bill=$bill->fetch();
if(!$bill) redirect(APP_URL.'/modules/billing/index.php');
$items = $pdo->prepare("SELECT * FROM milk_collections WHERE bill_id=? ORDER BY collection_date,shift");
$items->execute([$id]); $items=$items->fetchAll();
include dirname(dirname(__DIR__)).'/includes/header.php';
?>
<div class="page-header">
  <div><h1>🧾 Bill <?=htmlspecialchars($bill['bill_number'])?></h1>
  <p class="text-muted"><?=htmlspecialchars($bill['fname'])?> | <?=formatDate($bill['period_from'])?> – <?=formatDate($bill['period_to'])?></p></div>
  <div class="d-flex gap-2">
    <a href="print.php?id=<?=$id?>" target="_blank" class="btn btn-ghost">🖨️ Print</a>
    <a href="index.php" class="btn btn-ghost">← Back</a>
  </div>
</div>

<div class="stat-grid mb-4" style="grid-template-columns:repeat(4,1fr);">
  <div class="stat-card blue"><div class="stat-label">Total Quantity</div><div class="stat-value"><?=number_format($bill['total_quantity'],1)?> L</div></div>
  <div class="stat-card teal"><div class="stat-label">Total Amount</div><div class="stat-value"><?=formatCurrency($bill['total_amount'])?></div></div>
  <div class="stat-card amber"><div class="stat-label">Net Payable</div><div class="stat-value"><?=formatCurrency($bill['net_amount'])?></div></div>
  <div class="stat-card <?=$bill['payment_status']==='paid'?'green':'red'?>">
    <div class="stat-label">Status</div>
    <div class="stat-value"><?=ucfirst($bill['payment_status'])?></div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Collection Details</h3>
    <span class="badge badge-info"><?=count($items)?> entries</span>
  </div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Date</th><th>Shift</th><th>Animal</th><th>Qty(L)</th><th>FAT</th><th>SNF</th><th>Rate</th><th>Amount</th></tr></thead>
      <tbody>
        <?php foreach($items as $it): ?>
        <tr><td><?=formatDate($it['collection_date'])?></td><td><?=ucfirst($it['shift'])?></td><td><?=ucfirst($it['animal_type'])?></td><td><?=number_format($it['quantity'],2)?></td><td><?=number_format($it['fat'],2)?></td><td><?=number_format($it['snf'],2)?></td><td><?=formatCurrency($it['rate'])?></td><td style="font-weight:600;"><?=formatCurrency($it['amount'])?></td></tr>
        <?php endforeach; ?>
        <tr style="background:rgba(20,184,166,0.08);">
          <td colspan="3" style="font-weight:700;">Totals</td>
          <td style="font-weight:700;"><?=number_format($bill['total_quantity'],2)?> L</td>
          <td colspan="3" style="text-align:right;font-weight:700;padding-right:16px;">Gross:</td>
          <td style="font-weight:700;color:var(--primary-light);"><?=formatCurrency($bill['total_amount'])?></td>
        </tr>
        <?php if($bill['deductions']>0): ?>
        <tr><td colspan="7" style="text-align:right;color:var(--danger);">Deductions:</td><td style="color:var(--danger);">- <?=formatCurrency($bill['deductions'])?></td></tr>
        <?php endif; ?>
        <?php if($bill['bonus']>0): ?>
        <tr><td colspan="7" style="text-align:right;color:var(--success);">Bonus:</td><td style="color:var(--success);">+ <?=formatCurrency($bill['bonus'])?></td></tr>
        <?php endif; ?>
        <tr style="background:rgba(20,184,166,0.15);">
          <td colspan="7" style="text-align:right;font-weight:700;font-size:15px;">NET PAYABLE:</td>
          <td style="font-weight:700;font-size:15px;color:var(--primary-light);"><?=formatCurrency($bill['net_amount'])?></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<?php if($bill['payment_status']!=='paid'): ?>
<div class="card" style="margin-top:20px;">
  <div class="card-header"><h3 class="card-title">💳 Record Payment</h3></div>
  <form id="pay-form" style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;align-items:flex-end;">
    <input type="hidden" name="action" value="pay">
    <input type="hidden" name="bill_id" value="<?=$id?>">
    <div class="form-group" style="margin:0;"><label class="form-label">Payment Mode</label><select name="payment_mode" class="form-control"><option value="cash">Cash</option><option value="bank_transfer">Bank Transfer</option><option value="upi">UPI</option><option value="cheque">Cheque</option></select></div>
    <div class="form-group" style="margin:0;"><label class="form-label">Date</label><input type="date" name="payment_date" class="form-control" value="<?=date('Y-m-d')?>"></div>
    <div class="form-group" style="margin:0;"><label class="form-label">Reference</label><input type="text" name="reference" class="form-control" placeholder="UTR / Cheque No."></div>
    <button type="submit" class="btn btn-primary" id="pay-btn">✅ Mark Paid</button>
  </form>
</div>
<script>
document.getElementById('pay-form').addEventListener('submit',async function(e){
  e.preventDefault();
  const btn=document.getElementById('pay-btn');
  Form.setLoading(btn,true);
  const res=await Ajax.post('<?=APP_URL?>/ajax/billing.php',new FormData(this));
  Form.setLoading(btn,false);
  if(res.success){Toast.success('Paid!','Payment recorded.');setTimeout(()=>location.reload(),1200);}
  else Toast.error('Error',res.message);
});
</script>
<?php endif; ?>
<?php include dirname(dirname(__DIR__)).'/includes/footer.php'; ?>
