<?php
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$pdo = db();

function nextFarmerCode($pdo) {
    $prefix = getSetting('farmer_code_prefix', 'F');
    $last = $pdo->query("SELECT farmer_code FROM farmers ORDER BY id DESC LIMIT 1")->fetchColumn();
    if (!$last) return $prefix . '001';
    $num = (int) preg_replace('/\D/', '', $last);
    return $prefix . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
}

switch ($action) {
    case 'add':
        $name   = sanitize($_POST['name'] ?? '');
        $phone  = sanitize($_POST['phone'] ?? '');
        $addr   = sanitize($_POST['address'] ?? '');
        $jdate  = sanitize($_POST['joining_date'] ?? date('Y-m-d'));
        $errors = [];
        if (!$name)  $errors['name']    = 'Name is required.';
        if (!$phone) $errors['phone']   = 'Phone is required.';
        if (!$addr)  $errors['address'] = 'Address is required.';
        if ($errors) jsonResponse(['success'=>false,'message'=>'Validation failed.','errors'=>$errors]);

        $code = sanitize($_POST['farmer_code'] ?? '') ?: nextFarmerCode($pdo);
        // Check unique code/phone
        $dup = $pdo->prepare("SELECT id FROM farmers WHERE farmer_code=? OR phone=? LIMIT 1");
        $dup->execute([$code, $phone]);
        if ($dup->fetch()) jsonResponse(['success'=>false,'message'=>'Farmer code or phone already exists.']);

        $photo = null;
        if (!empty($_FILES['photo']['tmp_name'])) {
            $up = uploadFile($_FILES['photo'], 'farmers');
            if (!$up['success']) jsonResponse(['success'=>false,'message'=>$up['message']]);
            $photo = $up['filename'];
        }

        $stmt = $pdo->prepare("INSERT INTO farmers
            (farmer_code,name,name_marathi,phone,whatsapp,email,address,village,taluka,district,
             bank_name,bank_account,ifsc_code,aadhar,photo,animal_type,animal_count,joining_date,notes,created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $code, $name,
            sanitize($_POST['name_marathi'] ?? ''),
            $phone,
            sanitize($_POST['whatsapp'] ?? ''),
            sanitize($_POST['email'] ?? ''),
            $addr,
            sanitize($_POST['village'] ?? ''),
            sanitize($_POST['taluka'] ?? ''),
            sanitize($_POST['district'] ?? ''),
            sanitize($_POST['bank_name'] ?? ''),
            sanitize($_POST['bank_account'] ?? ''),
            sanitize($_POST['ifsc_code'] ?? ''),
            sanitize($_POST['aadhar'] ?? ''),
            $photo,
            sanitize($_POST['animal_type'] ?? 'cow'),
            (int)($_POST['animal_count'] ?? 0),
            $jdate,
            sanitize($_POST['notes'] ?? ''),
            $_SESSION['user_id']
        ]);
        logActivity('add_farmer', 'farmers', "Added farmer: $name ($code)");
        jsonResponse(['success'=>true,'message'=>"Farmer $name added successfully.", 'code'=>$code]);

    case 'edit':
        $id   = (int)($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        if (!$id || !$name) jsonResponse(['success'=>false,'message'=>'Invalid data.']);
        $farmer = $pdo->prepare("SELECT * FROM farmers WHERE id=? LIMIT 1");
        $farmer->execute([$id]); $farmer = $farmer->fetch();
        if (!$farmer) jsonResponse(['success'=>false,'message'=>'Farmer not found.']);

        $photo = $farmer['photo'];
        if (!empty($_FILES['photo']['tmp_name'])) {
            $up = uploadFile($_FILES['photo'], 'farmers');
            if ($up['success']) $photo = $up['filename'];
        }

        $stmt = $pdo->prepare("UPDATE farmers SET
            name=?,name_marathi=?,phone=?,whatsapp=?,email=?,address=?,village=?,taluka=?,district=?,
            bank_name=?,bank_account=?,ifsc_code=?,aadhar=?,photo=?,animal_type=?,animal_count=?,
            joining_date=?,notes=?,is_active=?
            WHERE id=?");
        $stmt->execute([
            $name, sanitize($_POST['name_marathi']??''), sanitize($_POST['phone']??''),
            sanitize($_POST['whatsapp']??''), sanitize($_POST['email']??''),
            sanitize($_POST['address']??''), sanitize($_POST['village']??''),
            sanitize($_POST['taluka']??''), sanitize($_POST['district']??''),
            sanitize($_POST['bank_name']??''), sanitize($_POST['bank_account']??''),
            sanitize($_POST['ifsc_code']??''), sanitize($_POST['aadhar']??''),
            $photo, sanitize($_POST['animal_type']??'cow'),
            (int)($_POST['animal_count']??0), sanitize($_POST['joining_date']??date('Y-m-d')),
            sanitize($_POST['notes']??''), (int)($_POST['is_active']??1), $id
        ]);
        logActivity('edit_farmer', 'farmers', "Updated farmer: $name");
        jsonResponse(['success'=>true,'message'=>'Farmer updated successfully.']);

    case 'delete':
        if (!isAdmin()) jsonResponse(['success'=>false,'message'=>'Access denied.']);
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) jsonResponse(['success'=>false,'message'=>'Invalid ID.']);
        $check = $pdo->prepare("SELECT COUNT(*) FROM milk_collections WHERE farmer_id=?");
        $check->execute([$id]);
        if ($check->fetchColumn() > 0)
            jsonResponse(['success'=>false,'message'=>'Cannot delete: farmer has collection records.']);
        $pdo->prepare("DELETE FROM farmers WHERE id=?")->execute([$id]);
        logActivity('delete_farmer', 'farmers', "Deleted farmer ID: $id");
        jsonResponse(['success'=>true,'message'=>'Farmer deleted.']);

    case 'next_code':
        jsonResponse(['success'=>true,'code'=>nextFarmerCode($pdo)]);

    default:
        jsonResponse(['success'=>false,'message'=>'Invalid action.'], 400);
}
?>
