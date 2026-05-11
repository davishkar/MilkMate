<?php
$pageTitle = 'Milk Collection';
require_once dirname(dirname(__DIR__)) . '/config/app.php';
requireLogin();

$pdo = db();
$today = date('Y-m-d');
$date = sanitize($_GET['date'] ?? $today);
$shift = sanitize($_GET['shift'] ?? 'morning');

// Today's summary
$summary = $pdo->prepare("SELECT COUNT(*) as entries, COALESCE(SUM(quantity),0) as qty, COALESCE(SUM(amount),0) as amt, COALESCE(AVG(fat),0) as avg_fat, COALESCE(AVG(snf),0) as avg_snf FROM milk_collections WHERE collection_date=? AND shift=?");
$summary->execute([$date, $shift]); $summary = $summary->fetch();

// Collections for date+shift
$cols = $pdo->prepare("SELECT mc.*, f.name as farmer_name, f.farmer_code FROM milk_collections mc JOIN farmers f ON mc.farmer_id=f.id WHERE mc.collection_date=? AND mc.shift=? ORDER BY f.farmer_code");
$cols->execute([$date, $shift]); $cols = $cols->fetchAll();

// Active farmers for dropdown
$farmers = $pdo->query("SELECT id, farmer_code, name, animal_type FROM farmers WHERE is_active=1 ORDER BY farmer_code")->fetchAll();

// Active rates
$rates = $pdo->query("SELECT * FROM milk_rates WHERE is_active=1")->fetchAll();
$rateMap = [];
foreach ($rates as $r) $rateMap[$r['animal_type']][$r['shift']] = $r;

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="page-header">
  <div>
    <h1>🍼 Milk Collection</h1>
    <p class="text-muted">Entry for <?= formatDate($date) ?> — <?= ucfirst($shift) ?> Shift</p>
  </div>
  <div class="d-flex gap-2">
    <a href="?date=<?= $date ?>&shift=morning" class="btn <?= $shift==='morning'?'btn-primary':'btn-ghost' ?>">☀️ Morning</a>
    <a href="?date=<?= $date ?>&shift=evening" class="btn <?= $shift==='evening'?'btn-primary':'btn-ghost' ?>">🌙 Evening</a>
  </div>
</div>

<!-- Shift Summary Cards -->
<div class="stat-grid mb-4" style="grid-template-columns:repeat(5,1fr);">
  <div class="stat-card teal"><div class="stat-label">Entries</div><div class="stat-value"><?= $summary['entries'] ?></div></div>
  <div class="stat-card blue"><div class="stat-label">Total Qty</div><div class="stat-value"><?= number_format($summary['qty'],1) ?> L</div></div>
  <div class="stat-card amber"><div class="stat-label">Total Amount</div><div class="stat-value"><?= formatCurrency($summary['amt']) ?></div></div>
  <div class="stat-card green"><div class="stat-label">Avg FAT</div><div class="stat-value"><?= number_format($summary['avg_fat'],2) ?></div></div>
  <div class="stat-card red"><div class="stat-label">Avg SNF</div><div class="stat-value"><?= number_format($summary['avg_snf'],2) ?></div></div>
</div>

<div style="display:grid;grid-template-columns:400px 1fr;gap:20px;" class="responsive-grid">
  <!-- Entry Form -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">➕ New Entry</h3>
    </div>
    <form id="collection-form">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="shift" value="<?= $shift ?>">
      <div class="form-group">
        <label class="form-label">Date</label>
        <input type="date" name="collection_date" class="form-control" value="<?= $date ?>" max="<?= $today ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Farmer *</label>
        <select name="farmer_id" class="form-control" required id="farmer-select">
          <option value="">— Select Farmer —</option>
          <?php foreach ($farmers as $f): ?>
          <option value="<?= $f['id'] ?>" data-type="<?= $f['animal_type'] ?>"><?= htmlspecialchars($f['farmer_code'].' - '.$f['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Animal Type</label>
        <select name="animal_type" class="form-control" id="animal-type">
          <option value="cow">🐄 Cow</option>
          <option value="buffalo">🐃 Buffalo</option>
        </select>
      </div>
      <div class="form-row" style="grid-template-columns:1fr 1fr;">
        <div class="form-group">
          <label class="form-label">Quantity (L) *</label>
          <input type="number" name="quantity" class="form-control" required step="0.1" min="0.1" placeholder="10.5" id="qty-input">
        </div>
        <div class="form-group">
          <label class="form-label">FAT *</label>
          <input type="number" name="fat" class="form-control" required step="0.1" min="0" max="15" placeholder="4.5" id="fat-input">
        </div>
      </div>
      <div class="form-row" style="grid-template-columns:1fr 1fr;">
        <div class="form-group">
          <label class="form-label">SNF *</label>
          <input type="number" name="snf" class="form-control" required step="0.1" min="0" max="15" placeholder="8.5" id="snf-input">
        </div>
        <div class="form-group">
          <label class="form-label">CLR</label>
          <input type="number" name="clr" class="form-control" step="0.1" placeholder="26" id="clr-input">
        </div>
      </div>

      <!-- Auto-calculated rate/amount -->
      <div style="background:rgba(20,184,166,0.08);border:1px solid rgba(20,184,166,0.2);border-radius:12px;padding:14px;margin-bottom:16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
          <span class="text-muted" style="font-size:13px;">Rate / Liter</span>
          <strong id="rate-display">₹0.00</strong>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;">
          <span class="text-muted" style="font-size:13px;">Total Amount</span>
          <strong style="font-size:18px;color:var(--primary-light);" id="amount-display">₹0.00</strong>
        </div>
        <input type="hidden" name="rate" id="rate-hidden" value="0">
        <input type="hidden" name="amount" id="amount-hidden" value="0">
      </div>

      <div class="form-group">
        <label class="form-label">Notes</label>
        <input type="text" name="notes" class="form-control" placeholder="Optional note">
      </div>

      <button type="submit" class="btn btn-primary w-full" id="save-btn" style="justify-content:center;">💾 Save Entry</button>
    </form>
  </div>

  <!-- Entries Table -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">📋 <?= ucfirst($shift) ?> Entries — <?= formatDate($date) ?></h3>
      <div class="d-flex gap-2">
        <input type="date" class="form-control" style="width:160px;padding:6px 10px;" value="<?= $date ?>" onchange="window.location.href='?date='+this.value+'&shift=<?= $shift ?>'">
      </div>
    </div>
    <div class="table-wrapper">
      <table id="collections-table">
        <thead>
          <tr><th>Farmer</th><th>Animal</th><th>Qty(L)</th><th>FAT</th><th>SNF</th><th>Rate</th><th>Amount</th><th>Actions</th></tr>
        </thead>
        <tbody id="collections-body">
          <?php if (empty($cols)): ?>
          <tr><td colspan="8" class="table-empty">No entries yet for this shift.</td></tr>
          <?php else: foreach ($cols as $c): ?>
          <tr id="col-<?= $c['id'] ?>">
            <td><strong><?= htmlspecialchars($c['farmer_code']) ?></strong><br><span class="text-muted" style="font-size:11.5px;"><?= htmlspecialchars($c['farmer_name']) ?></span></td>
            <td><?= ucfirst($c['animal_type']) ?></td>
            <td><?= number_format($c['quantity'],2) ?></td>
            <td><?= number_format($c['fat'],2) ?></td>
            <td><?= number_format($c['snf'],2) ?></td>
            <td><?= formatCurrency($c['rate']) ?></td>
            <td style="font-weight:600;color:var(--primary-light);"><?= formatCurrency($c['amount']) ?></td>
            <td>
              <button onclick="deleteCollection(<?= $c['id'] ?>, this.closest('tr'))" class="btn btn-danger btn-icon btn-sm">🗑️</button>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
const rateMap = <?= json_encode($rateMap) ?>;
const shift = '<?= $shift ?>';

function calcRate() {
  const type = document.getElementById('animal-type').value;
  const fat = parseFloat(document.getElementById('fat-input').value) || 0;
  const snf = parseFloat(document.getElementById('snf-input').value) || 0;
  const qty = parseFloat(document.getElementById('qty-input').value) || 0;
  const r = rateMap[type]?.[shift];
  if (!r) return;
  const rate = parseFloat(r.base_rate) + (fat * parseFloat(r.fat_rate)) + (snf * parseFloat(r.snf_rate));
  const amount = rate * qty;
  document.getElementById('rate-display').textContent = '₹' + rate.toFixed(2);
  document.getElementById('amount-display').textContent = '₹' + amount.toFixed(2);
  document.getElementById('rate-hidden').value = rate.toFixed(2);
  document.getElementById('amount-hidden').value = amount.toFixed(2);
}

['fat-input','snf-input','qty-input','animal-type'].forEach(id =>
  document.getElementById(id)?.addEventListener('input', calcRate));

// Auto-set animal type from farmer
document.getElementById('farmer-select').addEventListener('change', function() {
  const opt = this.selectedOptions[0];
  if (opt?.dataset.type) {
    const t = opt.dataset.type === 'both' ? 'cow' : opt.dataset.type;
    document.getElementById('animal-type').value = t;
    calcRate();
  }
});

// Form submit
document.getElementById('collection-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('save-btn');
  Form.setLoading(btn, true);
  const res = await Ajax.post('<?= APP_URL ?>/ajax/collections.php', new FormData(this));
  Form.setLoading(btn, false);
  if (res.success) {
    Toast.success('Saved!', 'Collection entry added.');
    // Add row to table
    const tbody = document.getElementById('collections-body');
    if (tbody.querySelector('.table-empty')) tbody.innerHTML = '';
    const tr = document.createElement('tr');
    tr.id = 'col-' + res.id;
    tr.innerHTML = `<td><strong>${res.farmer_code}</strong><br><span class="text-muted" style="font-size:11.5px;">${res.farmer_name}</span></td>
      <td>${document.getElementById('animal-type').value}</td>
      <td>${parseFloat(res.quantity).toFixed(2)}</td>
      <td>${parseFloat(res.fat).toFixed(2)}</td>
      <td>${parseFloat(res.snf).toFixed(2)}</td>
      <td>₹${parseFloat(res.rate).toFixed(2)}</td>
      <td style="font-weight:600;color:var(--primary-light);">₹${parseFloat(res.amount).toFixed(2)}</td>
      <td><button onclick="deleteCollection(${res.id}, this.closest('tr'))" class="btn btn-danger btn-icon btn-sm">🗑️</button></td>`;
    tbody.prepend(tr);
    this.reset();
    document.getElementById('rate-display').textContent = '₹0.00';
    document.getElementById('amount-display').textContent = '₹0.00';
  } else {
    Toast.error('Error', res.message);
  }
});

function deleteCollection(id, row) {
  deleteRecord('<?= APP_URL ?>/ajax/collections.php', id, row, 'entry');
}
</script>
<style>@media(max-width:900px){.responsive-grid{grid-template-columns:1fr!important}}</style>
<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
