<?php
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
$action = $_POST['action'] ?? '';
$pdo = db();

switch($action){
  case 'add_expense':
    $cat  = sanitize($_POST['category']??'Other');
    $desc = sanitize($_POST['description']??'');
    $amt  = (float)($_POST['amount']??0);
    $date = sanitize($_POST['expense_date']??date('Y-m-d'));
    $mode = sanitize($_POST['payment_mode']??'cash');
    if(!$desc||$amt<=0) jsonResponse(['success'=>false,'message'=>'Description and amount required.']);
    $pdo->prepare("INSERT INTO expenses (category,description,amount,expense_date,payment_mode,added_by) VALUES (?,?,?,?,?,?)")->execute([$cat,$desc,$amt,$date,$mode,$_SESSION['user_id']]);
    logActivity('add_expense','accounts',"Added expense: $desc = $amt");
    jsonResponse(['success'=>true,'message'=>'Expense saved.']);
  default:
    jsonResponse(['success'=>false,'message'=>'Invalid action.'],400);
}
?>
