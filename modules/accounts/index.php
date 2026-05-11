<?php
$pageTitle = 'Accounts';
require_once dirname(dirname(__DIR__)) . '/config/app.php';
requireLogin();
$pdo = db();
$month = sanitize($_GET['month'] ?? date('Y-m'));

$income  = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM milk_collections WHERE DATE_FORMAT(collection_date,'%Y-%m')=?");
$income->execute([$month]); $income=$income->fetchColumn();
$expense = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE DATE_FORMAT(expense_date,'%Y-%m')=?");
$expense->execute([$month]); $expense=$expense->fetchColumn();
$profit = $income - $expense;

$transactions = $pdo->prepare("SELECT 'income' as type, collection_date as txn_date, amount, CONCAT(f.name,' - Milk') as description FROM milk_collections mc JOIN farmers f ON mc.farmer_id=f.id WHERE DATE_FORMAT(mc.collection_date,'%Y-%m')=?
  UNION ALL
  SELECT 'expense', expense_date, amount, description FROM expenses WHERE DATE_FORMAT(expense_date,'%Y-%m')=?
  ORDER BY txn_date DESC LIMIT 50");
$transactions->execute([$month,$month]); $transactions=$transactions->fetchAll();

include dirname(dirname(__DIR__)).'/includes/header.php';
?>
<div class="page-header">
  <div><h1>💰 Accounts</h1><p class="text-muted">Financial overview and ledger</p></div>
  <input type="month" class="form-control" style="width:180px;" value="<?=$month?>" onchange="window.location.href='?month='+this.value">
</div>

<div class="stat-grid mb-4" style="grid-template-columns:repeat(3,1fr);">
  <div class="stat-card green"><div class="stat-icon">📈</div><div class="stat-label">Total Income</div><div class="stat-value"><?=formatCurrency($income)?></div><div class="stat-sub">From milk sales</div></div>
  <div class="stat-card red"><div class="stat-icon">📉</div><div class="stat-label">Total Expenses</div><div class="stat-value"><?=formatCurrency($expense)?></div></div>
  <div class="stat-card <?=$profit>=0?'teal':'red'?>"><div class="stat-icon">💹</div><div class="stat-label">Net Profit</div><div class="stat-value"><?=formatCurrency($profit)?></div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" class="responsive-grid">
  <div class="card">
    <div class="card-header"><h3 class="card-title">📋 Ledger — <?=date('F Y',strtotime($month.'-01'))?></h3></div>
    <div class="table-wrapper">
      <table><thead><tr><th>Date</th><th>Description</th><th>Type</th><th>Amount</th></tr></thead>
      <tbody>
        <?php if(empty($transactions)): ?>
        <tr><td colspan="4" class="table-empty">No transactions this month.</td></tr>
        <?php else: foreach($transactions as $t): ?>
        <tr>
          <td><?=formatDate($t['txn_date'])?></td>
          <td><?=htmlspecialchars(substr($t['description'],0,40))?></td>
          <td><span class="badge <?=$t['type']==='income'?'badge-success':'badge-danger'?>"><?=ucfirst($t['type'])?></span></td>
          <td style="font-weight:600;color:<?=$t['type']==='income'?'var(--success)':'var(--danger)'?>;"><?=($t['type']==='income'?'+':'-')?>₹<?=number_format($t['amount'],2)?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody></table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3 class="card-title">➕ Add Expense</h3></div>
    <form id="expense-form">
      <input type="hidden" name="action" value="add_expense">
      <div class="form-group"><label class="form-label">Category</label>
        <select name="category" class="form-control">
          <?php foreach(['Fuel','Electricity','Salary','Equipment','Maintenance','Transport','Other'] as $c): ?>
          <option><?=$c?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Description *</label><input type="text" name="description" class="form-control" required placeholder="Expense details..."></div>
      <div class="form-row" style="grid-template-columns:1fr 1fr;">
        <div class="form-group"><label class="form-label">Amount (₹) *</label><input type="number" name="amount" class="form-control" required step="0.01" min="0.01" placeholder="500"></div>
        <div class="form-group"><label class="form-label">Date</label><input type="date" name="expense_date" class="form-control" value="<?=date('Y-m-d')?>"></div>
      </div>
      <div class="form-group"><label class="form-label">Payment Mode</label>
        <select name="payment_mode" class="form-control">
          <option value="cash">Cash</option><option value="bank_transfer">Bank Transfer</option><option value="upi">UPI</option><option value="cheque">Cheque</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary w-full" id="exp-btn" style="justify-content:center;">💾 Save Expense</button>
    </form>
  </div>
</div>

<script>
document.getElementById('expense-form').addEventListener('submit',async function(e){
  e.preventDefault();
  const btn=document.getElementById('exp-btn');
  Form.setLoading(btn,true);
  const res=await Ajax.post('<?=APP_URL?>/ajax/accounts.php',new FormData(this));
  Form.setLoading(btn,false);
  if(res.success){Toast.success('Saved!','Expense recorded.');this.reset();setTimeout(()=>location.reload(),1200);}
  else Toast.error('Error',res.message);
});
</script>
<style>@media(max-width:768px){.responsive-grid{grid-template-columns:1fr!important}}</style>
<?php include dirname(dirname(__DIR__)).'/includes/footer.php'; ?>
