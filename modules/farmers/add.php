<?php
$pageTitle = 'Add Farmer';
require_once dirname(dirname(__DIR__)) . '/config/app.php';
requireLogin();
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="page-header">
  <div>
    <h1>➕ Add Farmer</h1>
    <div class="breadcrumb"><span>Farmers</span> / <span>Add New</span></div>
  </div>
  <a href="index.php" class="btn btn-ghost">← Back</a>
</div>

<div class="card" style="max-width:900px;">
  <form id="add-farmer-form" enctype="multipart/form-data">
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;" class="form-row">
      <div class="form-group">
        <label class="form-label">Full Name *</label>
        <input type="text" name="name" class="form-control" required placeholder="Ramesh Patil">
      </div>
      <div class="form-group">
        <label class="form-label">Name (Marathi)</label>
        <input type="text" name="name_marathi" class="form-control" placeholder="रमेश पाटील">
      </div>
      <div class="form-group">
        <label class="form-label">Farmer Code</label>
        <input type="text" name="farmer_code" class="form-control" placeholder="Auto-generated" id="farmer-code">
        <div class="form-hint">Leave blank to auto-generate</div>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Phone *</label>
        <input type="tel" name="phone" class="form-control" required placeholder="9876543210" maxlength="15">
      </div>
      <div class="form-group">
        <label class="form-label">WhatsApp</label>
        <input type="tel" name="whatsapp" class="form-control" placeholder="Same as phone">
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" placeholder="farmer@example.com">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Address *</label>
      <textarea name="address" class="form-control" required placeholder="House No, Street, Area..."></textarea>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Village</label>
        <input type="text" name="village" class="form-control" placeholder="Pune">
      </div>
      <div class="form-group">
        <label class="form-label">Taluka</label>
        <input type="text" name="taluka" class="form-control" placeholder="Haveli">
      </div>
      <div class="form-group">
        <label class="form-label">District</label>
        <input type="text" name="district" class="form-control" placeholder="Pune">
      </div>
    </div>

    <div class="separator"></div>
    <h3 style="font-size:15px;margin-bottom:16px;">🐄 Animal Details</h3>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Animal Type *</label>
        <select name="animal_type" class="form-control" required>
          <option value="cow">🐄 Cow</option>
          <option value="buffalo">🐃 Buffalo</option>
          <option value="both">Both</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Animal Count</label>
        <input type="number" name="animal_count" class="form-control" placeholder="3" min="0">
      </div>
      <div class="form-group">
        <label class="form-label">Joining Date *</label>
        <input type="date" name="joining_date" class="form-control" required value="<?= date('Y-m-d') ?>">
      </div>
    </div>

    <div class="separator"></div>
    <h3 style="font-size:15px;margin-bottom:16px;">🏦 Bank Details</h3>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Bank Name</label>
        <input type="text" name="bank_name" class="form-control" placeholder="State Bank of India">
      </div>
      <div class="form-group">
        <label class="form-label">Account Number</label>
        <input type="text" name="bank_account" class="form-control" placeholder="123456789012">
      </div>
      <div class="form-group">
        <label class="form-label">IFSC Code</label>
        <input type="text" name="ifsc_code" class="form-control" placeholder="SBIN0001234">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Aadhar Number</label>
        <input type="text" name="aadhar" class="form-control" placeholder="1234 5678 9012" maxlength="20">
      </div>
      <div class="form-group">
        <label class="form-label">Profile Photo</label>
        <input type="file" name="photo" class="form-control" accept="image/*" id="photo-input">
      </div>
      <div class="form-group" style="display:flex;align-items:center;justify-content:center;">
        <img id="photo-preview" src="" alt="Preview" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:2px solid var(--border);display:none;">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Notes</label>
      <textarea name="notes" class="form-control" placeholder="Additional notes about farmer..."></textarea>
    </div>

    <div class="d-flex gap-3">
      <button type="submit" class="btn btn-primary" id="submit-btn">💾 Save Farmer</button>
      <a href="index.php" class="btn btn-ghost">Cancel</a>
    </div>
  </form>
</div>

<script>
// Photo preview
document.getElementById('photo-input').addEventListener('change', function() {
  const file = this.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    const img = document.getElementById('photo-preview');
    img.src = e.target.result;
    img.style.display = 'block';
  };
  reader.readAsDataURL(file);
});

// Form submit via AJAX
document.getElementById('add-farmer-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('submit-btn');
  Form.setLoading(btn, true);
  Form.clearErrors(this);

  const fd = new FormData(this);
  fd.append('action', 'add');

  const res = await Ajax.post('<?= APP_URL ?>/ajax/farmers.php', fd);
  Form.setLoading(btn, false);

  if (res.success) {
    Toast.success('Farmer Added!', res.message || 'Farmer registered successfully.');
    setTimeout(() => window.location.href = 'index.php', 1500);
  } else {
    Toast.error('Failed', res.message || 'Could not save farmer.');
    if (res.errors) {
      Object.entries(res.errors).forEach(([f,m]) => Form.showError(this, f, m));
    }
  }
});
</script>
<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
