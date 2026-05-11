<?php
$pageTitle = 'Generate Bill';
require_once dirname(dirname(__DIR__)) . '/config/app.php';
requireLogin();

$pdo = db();
$farmers = $pdo->query("SELECT id,farmer_code,name FROM farmers WHERE is_active=1 ORDER BY farmer_code")->fetchAll();

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div class="page-header">
  <div><h1>🧾 Generate Bill</h1><p class="text-muted">Create a new payment bill for a farmer</p></div>
  <a href="index.php" class="btn btn-ghost">← Back</a>
</div>

<div style="display:grid;grid-template-columns:420px 1fr;gap:20px;" class="responsive-grid">
  <div class="card">
    <div class="card-header"><h3 class="card-title">Bill Settings</h3></div>
    <form id="gen-form">
      <input type="hidden" name="action" value="generate">
      <div class="form-group">
        <label class="form-label">Farmer *</label>
        <select name="farmer_id" class="form-control" required id="farmer-select">
          <option value="">— Select Farmer —</option>
          <?php foreach($farmers as $f): ?>
          <option value="<?=$f['id']?>"><?=htmlspecialchars($f['farmer_code'].' - '.$f['name'])?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Bill Type</label>
        <select name="bill_type" class="form-control" id="bill-type">
          <option value="monthly">Monthly</option>
          <option value="weekly">Weekly</option>
          <option value="custom">Custom Range</option>
        </select>
      </div>
      <div class="form-row" style="grid-template-columns:1fr 1fr;">
        <div class="form-group">
          <label class="form-label">From Date *</label>
          <input type="date" name="period_from" class="form-control" id="date-from" required value="<?=date('Y-m-01')?>">
        </div>
        <div class="form-group">
          <label class="form-label">To Date *</label>
          <input type="date" name="period_to" class="form-control" id="date-to" required value="<?=date('Y-m-d')?>">
        </div>
      </div>
      <div class="form-row" style="grid-template-columns:1fr 1fr;">
        <div class="form-group">
          <label class="form-label">Deductions (₹)</label>
          <input type="number" name="deductions" class="form-control" step="0.01" min="0" value="0" placeholder="0.00">
        </div>
        <div class="form-group">
          <label class="form-label">Bonus (₹)</label>
          <input type="number" name="bonus" class="form-control" step="0.01" min="0" value="0" placeholder="0.00">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" placeholder="Additional notes..." style="min-height:70px;"></textarea>
      </div>
      <button type="button" onclick="previewBill()" class="btn btn-ghost w-full mb-3" style="justify-content:center;">🔍 Preview</button>
      <button type="submit" class="btn btn-primary w-full" id="gen-btn" style="justify-content:center;">🧾 Generate Bill</button>
    </form>
  </div>

  <!-- Preview -->
  <div class="card" id="preview-card" style="display:none;">
    <div class="card-header">
      <h3 class="card-title">📋 Bill Preview</h3>
      <span id="preview-period" class="badge badge-info"></span>
    </div>
    <div id="preview-content">
      <div style="text-align:center;padding:40px;color:var(--text-secondary);">Select farmer and click Preview</div>
    </div>
  </div>
</div>

<script>
document.getElementById('bill-type').addEventListener('change', function(){
  const t = this.value;
  const now = new Date();
  if(t==='monthly'){
    document.getElementById('date-from').value = now.getFullYear()+'-'+String(now.getMonth()+1).padStart(2,'0')+'-01';
    document.getElementById('date-to').value = now.toISOString().split('T')[0];
  } else if(t==='weekly'){
    const mon = new Date(now); mon.setDate(now.getDate()-now.getDay()+1);
    document.getElementById('date-from').value = mon.toISOString().split('T')[0];
    document.getElementById('date-to').value = now.toISOString().split('T')[0];
  }
});

async function previewBill(){
  const fid = document.getElementById('farmer-select').value;
  const from = document.getElementById('date-from').value;
  const to = document.getElementById('date-to').value;
  if(!fid||!from||!to){Toast.warning('Select farmer and dates first.');return;}
  document.getElementById('preview-card').style.display='block';
  document.getElementById('preview-content').innerHTML='<div style="text-align:center;padding:40px;"><span class="spinner" style="width:30px;height:30px;"></span></div>';
  const res = await Ajax.post('<?=APP_URL?>/ajax/billing.php',{action:'preview',farmer_id:fid,period_from:from,period_to:to});
  if(!res.success){document.getElementById('preview-content').innerHTML=`<p class="text-muted text-center" style="padding:32px;">${res.message}</p>`;return;}
  const d = res.data;
  document.getElementById('preview-period').textContent = `${res.from} – ${res.to}`;
  document.getElementById('preview-content').innerHTML = `
    <div class="stat-grid mb-3" style="grid-template-columns:repeat(3,1fr);">
      <div class="stat-card teal"><div class="stat-label">Entries</div><div class="stat-value">${d.entries}</div></div>
      <div class="stat-card blue"><div class="stat-label">Total Qty</div><div class="stat-value">${parseFloat(d.total_qty).toFixed(1)} L</div></div>
      <div class="stat-card amber"><div class="stat-label">Total Amount</div><div class="stat-value">₹${parseFloat(d.total_amt).toFixed(0)}</div></div>
    </div>
    <div class="table-wrapper"><table>
      <thead><tr><th>Date</th><th>Shift</th><th>Qty(L)</th><th>FAT</th><th>SNF</th><th>Rate</th><th>Amount</th></tr></thead>
      <tbody>${d.rows.map(r=>`<tr><td>${r.collection_date}</td><td>${r.shift}</td><td>${parseFloat(r.quantity).toFixed(2)}</td><td>${parseFloat(r.fat).toFixed(2)}</td><td>${parseFloat(r.snf).toFixed(2)}</td><td>₹${parseFloat(r.rate).toFixed(2)}</td><td>₹${parseFloat(r.amount).toFixed(2)}</td></tr>`).join('')}</tbody>
    </table></div>`;
}

document.getElementById('gen-form').addEventListener('submit',async function(e){
  e.preventDefault();
  const btn=document.getElementById('gen-btn');
  Form.setLoading(btn,true);
  const res=await Ajax.post('<?=APP_URL?>/ajax/billing.php',new FormData(this));
  Form.setLoading(btn,false);
  if(res.success){Toast.success('Bill Generated!',`Bill ${res.bill_number} created.`);setTimeout(()=>window.location.href=`view.php?id=${res.id}`,1500);}
  else Toast.error('Error',res.message);
});
</script>
<style>@media(max-width:900px){.responsive-grid{grid-template-columns:1fr!important}}</style>
<?php include dirname(dirname(__DIR__)).'/includes/footer.php'; ?>
