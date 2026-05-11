<?php
$pageTitle = 'Settings';
require_once dirname(dirname(__DIR__)) . '/config/app.php';
requireLogin(); if(!isAdmin()) redirect(APP_URL.'/dashboard.php');
$pdo = db();

if($_SERVER['REQUEST_METHOD']==='POST'){
    $keys = ['company_name','company_name_marathi','company_address','company_phone','company_email','company_gstin','bill_prefix','farmer_code_prefix','sms_enabled','whatsapp_enabled'];
    foreach($keys as $k){
        $val = sanitize($_POST[$k]??'');
        $pdo->prepare("INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?")->execute([$k,$val,$val]);
    }
    // Milk rates
    if(!empty($_POST['fat_rate'])){
        foreach($_POST['fat_rate'] as $rateId=>$fr){
            $pdo->prepare("UPDATE milk_rates SET fat_rate=?,snf_rate=?,base_rate=? WHERE id=?")->execute([
                (float)$fr, (float)($_POST['snf_rate'][$rateId]??0), (float)($_POST['base_rate'][$rateId]??0), (int)$rateId
            ]);
        }
    }
    logActivity('update_settings','settings','Settings updated');
    $success = true;
}

$settings = [];
$all = $pdo->query("SELECT setting_key,setting_value FROM settings")->fetchAll();
foreach($all as $s) $settings[$s['setting_key']] = $s['setting_value'];
$rates = $pdo->query("SELECT * FROM milk_rates WHERE is_active=1 ORDER BY animal_type,shift")->fetchAll();

function sv($settings,$k,$d=''){return htmlspecialchars($settings[$k]??$d);}

include dirname(dirname(__DIR__)).'/includes/header.php';
?>
<div class="page-header">
  <div><h1>⚙️ Settings</h1><p class="text-muted">System configuration and milk rate management</p></div>
</div>

<?php if(!empty($success)): ?>
<div data-toast="Settings saved successfully!" data-toast-type="success" data-toast-title="Saved ✅"></div>
<?php endif; ?>

<form method="POST">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" class="responsive-grid">
  <!-- Company Info -->
  <div class="card">
    <div class="card-header"><h3 class="card-title">🏢 Company Details</h3></div>
    <div class="form-group"><label class="form-label">Company Name (English)</label><input type="text" name="company_name" class="form-control" value="<?=sv($settings,'company_name','MilkMate Dairy')?>"></div>
    <div class="form-group"><label class="form-label">Company Name (Marathi)</label><input type="text" name="company_name_marathi" class="form-control" value="<?=sv($settings,'company_name_marathi','मिल्कमेट डेअरी')?>"></div>
    <div class="form-group"><label class="form-label">Address</label><textarea name="company_address" class="form-control"><?=sv($settings,'company_address')?></textarea></div>
    <div class="form-group"><label class="form-label">Phone</label><input type="tel" name="company_phone" class="form-control" value="<?=sv($settings,'company_phone')?>"></div>
    <div class="form-group"><label class="form-label">Email</label><input type="email" name="company_email" class="form-control" value="<?=sv($settings,'company_email')?>"></div>
    <div class="form-group"><label class="form-label">GST Number</label><input type="text" name="company_gstin" class="form-control" value="<?=sv($settings,'company_gstin')?>"></div>
  </div>

  <!-- System Config -->
  <div class="card">
    <div class="card-header"><h3 class="card-title">🔧 System Configuration</h3></div>
    <div class="form-group"><label class="form-label">Bill Number Prefix</label><input type="text" name="bill_prefix" class="form-control" value="<?=sv($settings,'bill_prefix','BILL')?>"><div class="form-hint">Bills will be named like: BILL-202501-0001</div></div>
    <div class="form-group"><label class="form-label">Farmer Code Prefix</label><input type="text" name="farmer_code_prefix" class="form-control" value="<?=sv($settings,'farmer_code_prefix','F')?>"><div class="form-hint">Farmer codes: F001, F002...</div></div>
    <div class="separator"></div>
    <h4 style="font-size:14px;margin-bottom:12px;">🔔 Notifications</h4>
    <div class="form-group">
      <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
        <input type="hidden" name="sms_enabled" value="0">
        <input type="checkbox" name="sms_enabled" value="1" <?=($settings['sms_enabled']??'0')==='1'?'checked':''?> style="width:18px;height:18px;accent-color:var(--primary-light);">
        <span class="form-label" style="margin:0;">Enable SMS Notifications</span>
      </label>
    </div>
    <div class="form-group">
      <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
        <input type="hidden" name="whatsapp_enabled" value="0">
        <input type="checkbox" name="whatsapp_enabled" value="1" <?=($settings['whatsapp_enabled']??'0')==='1'?'checked':''?> style="width:18px;height:18px;accent-color:var(--primary-light);">
        <span class="form-label" style="margin:0;">Enable WhatsApp Notifications</span>
      </label>
    </div>
  </div>
</div>

<!-- Milk Rates -->
<div class="card" style="margin-top:20px;">
  <div class="card-header"><h3 class="card-title">🥛 Milk Rate Settings</h3><span class="badge badge-warning">Active Rates</span></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Animal</th><th>Shift</th><th>Base Rate (₹/L)</th><th>FAT Rate (₹/unit)</th><th>SNF Rate (₹/unit)</th><th>Sample Amount (4.5 FAT, 8.5 SNF, 10L)</th></tr></thead>
      <tbody>
        <?php foreach($rates as $r): ?>
        <tr>
          <td><?=ucfirst($r['animal_type'])?></td>
          <td><?=ucfirst($r['shift'])?></td>
          <td><input type="number" name="base_rate[<?=$r['id']?>]" class="form-control" style="width:100px;" step="0.01" value="<?=$r['base_rate']?>" onchange="calcSample(this.closest('tr'))"></td>
          <td><input type="number" name="fat_rate[<?=$r['id']?>]" class="form-control" style="width:100px;" step="0.01" value="<?=$r['fat_rate']?>" onchange="calcSample(this.closest('tr'))"></td>
          <td><input type="number" name="snf_rate[<?=$r['id']?>]" class="form-control" style="width:100px;" step="0.01" value="<?=$r['snf_rate']?>" onchange="calcSample(this.closest('tr'))"></td>
          <td class="sample-amt" style="font-weight:700;color:var(--primary-light);">—</td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div style="margin-top:20px;">
  <button type="submit" class="btn btn-primary">💾 Save Settings</button>
</div>
</form>

<script>
function calcSample(row){
  const base=parseFloat(row.querySelector('[name^="base_rate"]').value)||0;
  const fat=parseFloat(row.querySelector('[name^="fat_rate"]').value)||0;
  const snf=parseFloat(row.querySelector('[name^="snf_rate"]').value)||0;
  const rate=base+(4.5*fat)+(8.5*snf);
  const amt=rate*10;
  row.querySelector('.sample-amt').textContent='₹'+amt.toFixed(2);
}
document.querySelectorAll('tbody tr').forEach(r=>calcSample(r));
</script>
<style>@media(max-width:768px){.responsive-grid{grid-template-columns:1fr!important}}</style>
<?php include dirname(dirname(__DIR__)).'/includes/footer.php'; ?>
