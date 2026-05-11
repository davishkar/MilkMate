<?php
$pageTitle = 'Farmers';
require_once dirname(dirname(__DIR__)) . '/config/app.php';
requireLogin();

$pdo = db();
$search = sanitize($_GET['search'] ?? '');
$status = sanitize($_GET['status'] ?? 'all');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = "WHERE 1=1";
$params = [];
if ($search) {
    $where .= " AND (f.name LIKE ? OR f.farmer_code LIKE ? OR f.phone LIKE ? OR f.village LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like,$like,$like,$like]);
}
if ($status !== 'all') {
    $where .= " AND f.is_active=?";
    $params[] = $status === 'active' ? 1 : 0;
}

$total = $pdo->prepare("SELECT COUNT(*) FROM farmers f $where");
$total->execute($params); $total = $total->fetchColumn();
$totalPages = ceil($total / $perPage);

$stmt = $pdo->prepare("SELECT f.*, 
    (SELECT COUNT(*) FROM milk_collections mc WHERE mc.farmer_id=f.id AND MONTH(mc.collection_date)=MONTH(CURDATE()) AND YEAR(mc.collection_date)=YEAR(CURDATE())) as month_entries,
    (SELECT COALESCE(SUM(mc.amount),0) FROM milk_collections mc WHERE mc.farmer_id=f.id AND MONTH(mc.collection_date)=MONTH(CURDATE()) AND YEAR(mc.collection_date)=YEAR(CURDATE())) as month_amount
  FROM farmers f $where ORDER BY f.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$farmers = $stmt->fetchAll();

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="page-header">
  <div>
    <h1>👨‍🌾 Farmers</h1>
    <p class="text-muted">Manage registered milk suppliers</p>
  </div>
  <a href="<?= APP_URL ?>/modules/farmers/add.php" class="btn btn-primary">➕ Add Farmer</a>
</div>

<!-- Filters -->
<div class="card mb-4" style="padding:16px 20px;">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <div class="navbar-search" style="width:280px;">
      <span>🔍</span>
      <input type="text" id="search-input" placeholder="Search name, code, phone..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <select id="status-filter" class="form-control" style="width:160px;">
      <option value="all" <?= $status==='all'?'selected':'' ?>>All Status</option>
      <option value="active" <?= $status==='active'?'selected':'' ?>>Active</option>
      <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option>
    </select>
    <span class="text-muted" style="font-size:13px;">Total: <strong><?= $total ?></strong></span>
  </div>
</div>

<div class="card">
  <div class="table-wrapper">
    <table id="farmers-table">
      <thead>
        <tr>
          <th>Code</th><th>Farmer</th><th>Phone</th><th>Village</th>
          <th>Animal</th><th>Month Qty (L)</th><th>Month Amt</th>
          <th>Status</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($farmers)): ?>
        <tr><td colspan="9" class="table-empty">🐄 No farmers found. <a href="<?= APP_URL ?>/modules/farmers/add.php" style="color:var(--primary-light);">Add first farmer →</a></td></tr>
        <?php else: foreach ($farmers as $f): ?>
        <tr id="row-<?= $f['id'] ?>">
          <td><span class="badge badge-secondary"><?= htmlspecialchars($f['farmer_code']) ?></span></td>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <?php if ($f['photo'] && file_exists(UPLOAD_PATH . $f['photo'])): ?>
              <img src="<?= UPLOAD_URL . htmlspecialchars($f['photo']) ?>" alt="" style="width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid var(--border);">
              <?php else: ?>
              <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0;"><?= strtoupper(substr($f['name'],0,1)) ?></div>
              <?php endif; ?>
              <div>
                <div style="font-weight:600;font-size:13.5px;"><?= htmlspecialchars($f['name']) ?></div>
                <div class="text-muted" style="font-size:11.5px;"><?= htmlspecialchars($f['name_marathi'] ?? '') ?></div>
              </div>
            </div>
          </td>
          <td><?= htmlspecialchars($f['phone']) ?></td>
          <td><?= htmlspecialchars($f['village'] ?? '—') ?></td>
          <td><span class="badge badge-info"><?= ucfirst($f['animal_type']) ?></span></td>
          <td><?= number_format($f['month_amount'] > 0 ? ($f['month_amount']/$f['month_amount'])*0 + $f['month_entries'],1) ?></td>
          <td style="font-weight:600;color:var(--primary-light);"><?= formatCurrency($f['month_amount']) ?></td>
          <td>
            <?php if ($f['is_active']): ?>
              <span class="badge badge-success">● Active</span>
            <?php else: ?>
              <span class="badge badge-danger">● Inactive</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="d-flex gap-2">
              <a href="view.php?id=<?= $f['id'] ?>" class="btn btn-ghost btn-icon btn-sm" title="View">👁️</a>
              <a href="edit.php?id=<?= $f['id'] ?>" class="btn btn-ghost btn-icon btn-sm" title="Edit">✏️</a>
              <?php if (isAdmin()): ?>
              <button onclick="deleteFarmer(<?= $f['id'] ?>, this.closest('tr'))" class="btn btn-danger btn-icon btn-sm" title="Delete">🗑️</button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div style="display:flex;justify-content:center;gap:6px;padding:16px;">
    <?php for ($i=1;$i<=$totalPages;$i++): ?>
    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>"
       class="btn <?= $i===$page ? 'btn-primary' : 'btn-ghost' ?> btn-sm"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<script>
// Live search redirect
let searchTimer;
document.getElementById('search-input').addEventListener('input', function() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    window.location.href = `?search=${encodeURIComponent(this.value)}&status=${document.getElementById('status-filter').value}`;
  }, 500);
});
document.getElementById('status-filter').addEventListener('change', function() {
  window.location.href = `?status=${this.value}&search=${encodeURIComponent(document.getElementById('search-input').value)}`;
});

function deleteFarmer(id, row) {
  deleteRecord('<?= APP_URL ?>/ajax/farmers.php', id, row, 'farmer');
}
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
