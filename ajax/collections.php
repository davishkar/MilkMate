<?php
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();

$action = $_POST['action'] ?? '';
$pdo = db();

switch ($action) {
    case 'add':
        $fid   = (int)($_POST['farmer_id'] ?? 0);
        $date  = sanitize($_POST['collection_date'] ?? date('Y-m-d'));
        $shift = sanitize($_POST['shift'] ?? 'morning');
        $type  = sanitize($_POST['animal_type'] ?? 'cow');
        $qty   = (float)($_POST['quantity'] ?? 0);
        $fat   = (float)($_POST['fat'] ?? 0);
        $snf   = (float)($_POST['snf'] ?? 0);
        $clr   = $_POST['clr'] !== '' ? (float)$_POST['clr'] : null;
        $rate  = (float)($_POST['rate'] ?? 0);
        $amount= (float)($_POST['amount'] ?? 0);
        $notes = sanitize($_POST['notes'] ?? '');

        if (!$fid || $qty <= 0 || $fat <= 0 || $snf <= 0)
            jsonResponse(['success'=>false,'message'=>'All required fields must be filled.']);

        // Get farmer info
        $f = $pdo->prepare("SELECT * FROM farmers WHERE id=? LIMIT 1");
        $f->execute([$fid]); $f = $f->fetch();
        if (!$f) jsonResponse(['success'=>false,'message'=>'Farmer not found.']);

        // Get active rate
        $rateRow = $pdo->prepare("SELECT id FROM milk_rates WHERE animal_type=? AND shift=? AND is_active=1 ORDER BY effective_from DESC LIMIT 1");
        $rateRow->execute([$type, $shift]); $rateRow = $rateRow->fetch();
        $rateId = $rateRow ? $rateRow['id'] : null;

        $stmt = $pdo->prepare("INSERT INTO milk_collections
            (farmer_id,collection_date,shift,animal_type,quantity,fat,snf,clr,rate,amount,rate_id,notes,entered_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$fid,$date,$shift,$type,$qty,$fat,$snf,$clr,$rate,$amount,$rateId,$notes,$_SESSION['user_id']]);
        $id = $pdo->lastInsertId();

        logActivity('add_collection', 'collections', "Added $shift collection for {$f['name']}: {$qty}L");
        jsonResponse(['success'=>true,'id'=>$id,'farmer_name'=>$f['name'],'farmer_code'=>$f['farmer_code'],
            'quantity'=>$qty,'fat'=>$fat,'snf'=>$snf,'rate'=>$rate,'amount'=>$amount]);

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT is_billed FROM milk_collections WHERE id=?");
        $stmt->execute([$id]); $row = $stmt->fetch();
        if (!$row) jsonResponse(['success'=>false,'message'=>'Record not found.']);
        if ($row['is_billed']) jsonResponse(['success'=>false,'message'=>'Cannot delete: already billed.']);
        $pdo->prepare("DELETE FROM milk_collections WHERE id=?")->execute([$id]);
        logActivity('delete_collection', 'collections', "Deleted collection ID: $id");
        jsonResponse(['success'=>true,'message'=>'Entry deleted.']);

    default:
        jsonResponse(['success'=>false,'message'=>'Invalid action.'], 400);
}
?>
