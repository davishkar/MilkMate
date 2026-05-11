<?php
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
$action = $_POST['action'] ?? '';
$pdo = db();

switch($action){
  case 'preview':
    $fid  = (int)($_POST['farmer_id']??0);
    $from = sanitize($_POST['period_from']??'');
    $to   = sanitize($_POST['period_to']??'');
    if(!$fid||!$from||!$to) jsonResponse(['success'=>false,'message'=>'Missing fields.']);
    $rows = $pdo->prepare("SELECT * FROM milk_collections WHERE farmer_id=? AND collection_date BETWEEN ? AND ? AND is_billed=0 ORDER BY collection_date,shift");
    $rows->execute([$fid,$from,$to]); $rows=$rows->fetchAll();
    if(empty($rows)) jsonResponse(['success'=>false,'message'=>'No unbilled collection entries for this period.']);
    $totalQty = array_sum(array_column($rows,'quantity'));
    $totalAmt = array_sum(array_column($rows,'amount'));
    jsonResponse(['success'=>true,'from'=>$from,'to'=>$to,'data'=>['entries'=>count($rows),'total_qty'=>$totalQty,'total_amt'=>$totalAmt,'rows'=>$rows]]);

  case 'generate':
    $fid     = (int)($_POST['farmer_id']??0);
    $from    = sanitize($_POST['period_from']??'');
    $to      = sanitize($_POST['period_to']??'');
    $type    = sanitize($_POST['bill_type']??'monthly');
    $deduct  = (float)($_POST['deductions']??0);
    $bonus   = (float)($_POST['bonus']??0);
    $notes   = sanitize($_POST['notes']??'');
    if(!$fid||!$from||!$to) jsonResponse(['success'=>false,'message'=>'Missing fields.']);

    $rows = $pdo->prepare("SELECT * FROM milk_collections WHERE farmer_id=? AND collection_date BETWEEN ? AND ? AND is_billed=0");
    $rows->execute([$fid,$from,$to]); $rows=$rows->fetchAll();
    if(empty($rows)) jsonResponse(['success'=>false,'message'=>'No unbilled entries for this period.']);

    $totalQty = array_sum(array_column($rows,'quantity'));
    $totalAmt = array_sum(array_column($rows,'amount'));
    $netAmt   = $totalAmt - $deduct + $bonus;
    $prefix   = getSetting('bill_prefix','BILL');
    $billNum  = $prefix.'-'.date('Ym').'-'.str_pad($pdo->query("SELECT COUNT(*)+1 FROM bills")->fetchColumn(),4,'0',STR_PAD_LEFT);

    $pdo->beginTransaction();
    try {
      $stmt=$pdo->prepare("INSERT INTO bills (bill_number,farmer_id,bill_type,period_from,period_to,total_quantity,total_amount,deductions,bonus,net_amount,notes,generated_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
      $stmt->execute([$billNum,$fid,$type,$from,$to,$totalQty,$totalAmt,$deduct,$bonus,$netAmt,$notes,$_SESSION['user_id']]);
      $billId=$pdo->lastInsertId();
      $ids = array_column($rows,'id');
      $ph  = implode(',',array_fill(0,count($ids),'?'));
      $pdo->prepare("UPDATE milk_collections SET is_billed=1, bill_id=? WHERE id IN ($ph)")->execute(array_merge([$billId],$ids));
      $pdo->commit();
      logActivity('generate_bill','billing',"Generated $billNum for farmer ID $fid");
      jsonResponse(['success'=>true,'bill_number'=>$billNum,'id'=>$billId]);
    } catch(Exception $e) { $pdo->rollBack(); jsonResponse(['success'=>false,'message'=>$e->getMessage()]); }

  case 'pay':
    $billId  = (int)($_POST['bill_id']??0);
    $mode    = sanitize($_POST['payment_mode']??'cash');
    $pdate   = sanitize($_POST['payment_date']??date('Y-m-d'));
    $ref     = sanitize($_POST['reference']??'');
    if(!$billId) jsonResponse(['success'=>false,'message'=>'Invalid bill.']);
    $bill=$pdo->prepare("SELECT * FROM bills WHERE id=? LIMIT 1");
    $bill->execute([$billId]); $bill=$bill->fetch();
    if(!$bill) jsonResponse(['success'=>false,'message'=>'Bill not found.']);
    $pdo->prepare("UPDATE bills SET payment_status='paid',payment_date=?,payment_mode=? WHERE id=?")->execute([$pdate,$mode,$billId]);
    $pdo->prepare("INSERT INTO payments (bill_id,farmer_id,amount,payment_date,payment_mode,reference,received_by) VALUES (?,?,?,?,?,?,?)")->execute([$billId,$bill['farmer_id'],$bill['net_amount'],$pdate,$mode,$ref,$_SESSION['user_id']]);
    logActivity('payment','billing',"Payment received for bill $billId");
    jsonResponse(['success'=>true,'message'=>'Payment recorded.']);

  default:
    jsonResponse(['success'=>false,'message'=>'Invalid action.'],400);
}
?>
