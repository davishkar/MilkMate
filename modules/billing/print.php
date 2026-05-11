<?php
require_once dirname(dirname(__DIR__)) . '/config/app.php';
requireLogin();
$pdo = db();
$id = (int)($_GET['id']??0);
if(!$id) redirect(APP_URL.'/modules/billing/index.php');
$bill = $pdo->prepare("SELECT b.*, f.name as fname, f.farmer_code, f.phone, f.address, f.village, f.bank_name, f.bank_account FROM bills b JOIN farmers f ON b.farmer_id=f.id WHERE b.id=? LIMIT 1");
$bill->execute([$id]); $bill=$bill->fetch();
if(!$bill) redirect(APP_URL.'/modules/billing/index.php');
$items = $pdo->prepare("SELECT * FROM milk_collections WHERE bill_id=? ORDER BY collection_date, shift");
$items->execute([$id]); $items=$items->fetchAll();
$company = ['name'=>getSetting('company_name','MilkMate Dairy'),'marathi'=>getSetting('company_name_marathi',''),'address'=>getSetting('company_address',''),'phone'=>getSetting('company_phone',''),'email'=>getSetting('company_email','')];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bill <?=htmlspecialchars($bill['bill_number'])?></title>
  <link rel="stylesheet" href="<?=APP_URL?>/assets/css/fonts.css">
  <style>
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'Poppins',sans-serif;background:#f1f5f9;color:#1e293b;font-size:13px;}
    .print-btn{position:fixed;bottom:24px;right:24px;background:#0f766e;color:#fff;border:none;padding:12px 24px;border-radius:12px;cursor:pointer;font-family:'Poppins',sans-serif;font-size:14px;font-weight:600;box-shadow:0 4px 16px rgba(15,118,110,0.4);}
    .bill-container{max-width:800px;margin:24px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.1);}
    .bill-header{background:linear-gradient(135deg,#0f766e,#14b8a6);color:#fff;padding:32px;display:flex;justify-content:space-between;align-items:flex-start;}
    .company-name{font-family:'Raleway',sans-serif;font-size:24px;font-weight:700;}
    .company-name-mr{font-size:16px;opacity:0.85;margin-top:4px;}
    .bill-meta{text-align:right;}
    .bill-number{font-family:'Raleway',sans-serif;font-size:20px;font-weight:700;}
    .section{padding:24px 32px;border-bottom:1px solid #e2e8f0;}
    .info-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
    .info-label{font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:3px;}
    .info-value{font-size:13.5px;font-weight:600;color:#1e293b;}
    table{width:100%;border-collapse:collapse;font-size:12.5px;}
    thead th{background:#f1f5f9;padding:10px 12px;text-align:left;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.8px;}
    tbody td{padding:9px 12px;border-top:1px solid #f1f5f9;}
    .total-row td{font-weight:700;background:#f8fafc;}
    .bill-footer{padding:24px 32px;display:flex;justify-content:space-between;align-items:center;}
    .net-amount{font-family:'Raleway',sans-serif;font-size:28px;font-weight:700;color:#0f766e;}
    .status-badge{padding:6px 14px;border-radius:99px;font-size:13px;font-weight:700;}
    .status-paid{background:#dcfce7;color:#16a34a;}
    .status-pending{background:#fef9c3;color:#ca8a04;}
    .qr-placeholder{width:80px;height:80px;border:2px dashed #cbd5e1;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:10px;color:#94a3b8;text-align:center;}
    @media print{.print-btn{display:none!important;}body{background:#fff;}.bill-container{margin:0;box-shadow:none;border-radius:0;max-width:100%;}}
  </style>
</head>
<body>
<button class="print-btn" onclick="window.print()">🖨️ Print Bill</button>
<div class="bill-container">
  <div class="bill-header">
    <div>
      <div class="company-name"><?=htmlspecialchars($company['name'])?></div>
      <?php if($company['marathi']): ?><div class="company-name-mr"><?=htmlspecialchars($company['marathi'])?></div><?php endif; ?>
      <div style="margin-top:8px;opacity:0.8;font-size:12.5px;"><?=htmlspecialchars($company['address'])?></div>
      <div style="opacity:0.8;font-size:12.5px;">📞 <?=htmlspecialchars($company['phone'])?></div>
    </div>
    <div class="bill-meta">
      <div class="bill-number"><?=htmlspecialchars($bill['bill_number'])?></div>
      <div style="margin-top:6px;opacity:0.85;">Generated: <?=formatDate($bill['created_at'])?></div>
      <div style="margin-top:4px;"><span class="status-badge <?=$bill['payment_status']==='paid'?'status-paid':'status-pending'?>"><?=strtoupper($bill['payment_status'])?></span></div>
    </div>
  </div>

  <div class="section">
    <div class="info-grid">
      <div><div class="info-label">Farmer</div><div class="info-value"><?=htmlspecialchars($bill['fname'])?></div><div style="color:#64748b;font-size:12px;"><?=htmlspecialchars($bill['farmer_code'])?> | 📞 <?=htmlspecialchars($bill['phone'])?></div><div style="color:#64748b;font-size:12px;"><?=htmlspecialchars($bill['address']??'')?>, <?=htmlspecialchars($bill['village']??'')?></div></div>
      <div>
        <div class="info-label">Bill Period</div>
        <div class="info-value"><?=formatDate($bill['period_from'])?> — <?=formatDate($bill['period_to'])?></div>
        <?php if($bill['bank_name']): ?><div style="color:#64748b;font-size:12px;margin-top:8px;">🏦 <?=htmlspecialchars($bill['bank_name'])?><br>A/C: <?=htmlspecialchars($bill['bank_account']??'')?></div><?php endif; ?>
      </div>
    </div>
  </div>

  <div class="section" style="padding:0;">
    <table>
      <thead><tr><th>Date</th><th>Shift</th><th>Animal</th><th>Qty (L)</th><th>FAT</th><th>SNF</th><th>Rate/L</th><th>Amount</th></tr></thead>
      <tbody>
        <?php foreach($items as $it): ?>
        <tr><td><?=date('d/m',strtotime($it['collection_date']))?></td><td><?=ucfirst($it['shift'])?></td><td><?=ucfirst($it['animal_type'])?></td><td><?=number_format($it['quantity'],2)?></td><td><?=number_format($it['fat'],2)?></td><td><?=number_format($it['snf'],2)?></td><td>₹<?=number_format($it['rate'],2)?></td><td>₹<?=number_format($it['amount'],2)?></td></tr>
        <?php endforeach; ?>
        <tr class="total-row"><td colspan="3">TOTALS</td><td><?=number_format($bill['total_quantity'],2)?> L</td><td colspan="3" style="text-align:right;">Gross Amount:</td><td>₹<?=number_format($bill['total_amount'],2)?></td></tr>
        <?php if($bill['deductions']>0): ?><tr><td colspan="7" style="text-align:right;color:#dc2626;">Deductions:</td><td style="color:#dc2626;">- ₹<?=number_format($bill['deductions'],2)?></td></tr><?php endif; ?>
        <?php if($bill['bonus']>0): ?><tr><td colspan="7" style="text-align:right;color:#16a34a;">Bonus:</td><td style="color:#16a34a;">+ ₹<?=number_format($bill['bonus'],2)?></td></tr><?php endif; ?>
        <tr class="total-row" style="background:#dcfce7;"><td colspan="7" style="text-align:right;font-size:15px;">NET PAYABLE:</td><td style="font-size:15px;color:#0f766e;">₹<?=number_format($bill['net_amount'],2)?></td></tr>
      </tbody>
    </table>
  </div>

  <div class="bill-footer">
    <div>
      <div class="net-amount">₹<?=number_format($bill['net_amount'],2)?></div>
      <div style="color:#64748b;font-size:12px;margin-top:4px;">Total Payable Amount</div>
      <?php if($bill['payment_date']): ?><div style="color:#16a34a;font-size:12px;margin-top:4px;">✅ Paid on <?=formatDate($bill['payment_date'])?> via <?=ucfirst($bill['payment_mode']??'')?></div><?php endif; ?>
    </div>
    <div style="text-align:center;">
      <div class="qr-placeholder">QR<br>Code</div>
      <div style="font-size:10px;color:#94a3b8;margin-top:4px;">Scan to Pay</div>
    </div>
    <div style="text-align:right;">
      <div style="border-top:1px solid #cbd5e1;padding-top:8px;margin-top:40px;font-size:12px;color:#64748b;">Authorized Signature</div>
    </div>
  </div>
  <div style="background:#f8fafc;padding:12px 32px;text-align:center;font-size:11px;color:#94a3b8;">Generated by MilkMate Dairy Management System | <?=date('d/m/Y H:i')?></div>
</div>
</body></html>
