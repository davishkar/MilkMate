<?php
$pageTitle = 'Edit Farmer';
require_once dirname(dirname(__DIR__)) . '/config/app.php';
requireLogin();
$pdo = db();
$id = (int)($_GET['id']??0);
if(!$id) redirect(APP_URL.'/modules/farmers/index.php');
$f = $pdo->prepare("SELECT * FROM farmers WHERE id=? LIMIT 1");
$f->execute([$id]); $f=$f->fetch();
if(!$f) redirect(APP_URL.'/modules/farmers/index.php');
include dirname(dirname(__DIR__)).'/includes/header.php';
?>
<div class="page-header">
  <div><h1>✏️ Edit Farmer</h1><div class="breadcrumb"><span>Farmers</span>/<span>Edit</span>/<span><?=htmlspecialchars($f['farmer_code'])?></span></div></div>
  <div class="d-flex gap-2">
    <a href="view.php?id=<?=$id?>" class="btn btn-ghost">👁️ View</a>
    <a href="index.php" class="btn btn-ghost">← Back</a>
  </div>
</div>

<div class="card" style="max-width:900px;">
  <form id="edit-farmer-form" enctype="multipart/form-data">
    <input type="hidden" name="action" value="edit">
    <input type="hidden" name="id" value="<?=$id?>">
    <div class="form-row">
      <div class="form-group"><label class="form-label">Full Name *</label><input type="text" name="name" class="form-control" required value="<?=htmlspecialchars($f['name'])?>"></div>
      <div class="form-group"><label class="form-label">Name (Marathi)</label><input type="text" name="name_marathi" class="form-control" value="<?=htmlspecialchars($f['name_marathi']??'')?>"></div>
      <div class="form-group"><label class="form-label">Farmer Code</label><input type="text" class="form-control" value="<?=htmlspecialchars($f['farmer_code'])?>" disabled></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Phone *</label><input type="tel" name="phone" class="form-control" required value="<?=htmlspecialchars($f['phone'])?>"></div>
      <div class="form-group"><label class="form-label">WhatsApp</label><input type="tel" name="whatsapp" class="form-control" value="<?=htmlspecialchars($f['whatsapp']??'')?>"></div>
      <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?=htmlspecialchars($f['email']??'')?>"></div>
    </div>
    <div class="form-group"><label class="form-label">Address *</label><textarea name="address" class="form-control" required><?=htmlspecialchars($f['address'])?></textarea></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Village</label><input type="text" name="village" class="form-control" value="<?=htmlspecialchars($f['village']??'')?>"></div>
      <div class="form-group"><label class="form-label">Taluka</label><input type="text" name="taluka" class="form-control" value="<?=htmlspecialchars($f['taluka']??'')?>"></div>
      <div class="form-group"><label class="form-label">District</label><input type="text" name="district" class="form-control" value="<?=htmlspecialchars($f['district']??'')?>"></div>
    </div>
    <div class="separator"></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Animal Type</label>
        <select name="animal_type" class="form-control">
          <?php foreach(['cow','buffalo','both'] as $at): ?><option value="<?=$at?>" <?=$f['animal_type']===$at?'selected':''?>><?=ucfirst($at)?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Animal Count</label><input type="number" name="animal_count" class="form-control" value="<?=$f['animal_count']?>" min="0"></div>
      <div class="form-group"><label class="form-label">Joining Date</label><input type="date" name="joining_date" class="form-control" value="<?=$f['joining_date']?>"></div>
    </div>
    <div class="separator"></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Bank Name</label><input type="text" name="bank_name" class="form-control" value="<?=htmlspecialchars($f['bank_name']??'')?>"></div>
      <div class="form-group"><label class="form-label">Account Number</label><input type="text" name="bank_account" class="form-control" value="<?=htmlspecialchars($f['bank_account']??'')?>"></div>
      <div class="form-group"><label class="form-label">IFSC Code</label><input type="text" name="ifsc_code" class="form-control" value="<?=htmlspecialchars($f['ifsc_code']??'')?>"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Aadhar</label><input type="text" name="aadhar" class="form-control" value="<?=htmlspecialchars($f['aadhar']??'')?>"></div>
      <div class="form-group"><label class="form-label">Profile Photo</label><input type="file" name="photo" class="form-control" accept="image/*"></div>
      <div class="form-group"><label class="form-label">Status</label>
        <select name="is_active" class="form-control"><option value="1" <?=$f['is_active']?'selected':''?>>Active</option><option value="0" <?=!$f['is_active']?'selected':''?>>Inactive</option></select>
      </div>
    </div>
    <div class="form-group"><label class="form-label">Notes</label><textarea name="notes" class="form-control"><?=htmlspecialchars($f['notes']??'')?></textarea></div>
    <div class="d-flex gap-3"><button type="submit" class="btn btn-primary" id="submit-btn">💾 Update Farmer</button><a href="index.php" class="btn btn-ghost">Cancel</a></div>
  </form>
</div>

<script>
document.getElementById('edit-farmer-form').addEventListener('submit',async function(e){
  e.preventDefault();
  const btn=document.getElementById('submit-btn');
  Form.setLoading(btn,true);
  const res=await Ajax.post('<?=APP_URL?>/ajax/farmers.php',new FormData(this));
  Form.setLoading(btn,false);
  if(res.success){Toast.success('Updated!',res.message);setTimeout(()=>window.location.href='index.php',1500);}
  else Toast.error('Error',res.message);
});
</script>
<?php include dirname(dirname(__DIR__)).'/includes/footer.php'; ?>
