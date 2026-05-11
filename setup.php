<?php
/**
 * MilkMate Setup Script
 * Run this ONCE to initialize the database: http://localhost/MilkMate/setup.php
 * DELETE THIS FILE after setup is complete!
 */

// Security token - change this before running
define('SETUP_TOKEN', 'milkmate_setup_2024');

if (($_GET['token'] ?? '') !== SETUP_TOKEN) {
    die('<h2 style="font-family:sans-serif;color:red;">❌ Access Denied. Add ?token=milkmate_setup_2024 to URL.</h2>');
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'milkmate_db');

$log = [];
$errors = [];

function logMsg($msg) { global $log; $log[] = $msg; }
function errMsg($msg) { global $errors; $errors[] = $msg; }

try {
    // Connect without DB first
    $pdo = new PDO("mysql:host=".DB_HOST.";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    logMsg("✅ Connected to MySQL successfully.");

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `".DB_NAME."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `".DB_NAME."`");
    logMsg("✅ Database `".DB_NAME."` created/selected.");

    // Create tables
    $tables = [
        "users" => "CREATE TABLE IF NOT EXISTS `users` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(150) NOT NULL UNIQUE,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `role` ENUM('admin','staff') DEFAULT 'staff',
            `photo` VARCHAR(255) DEFAULT NULL,
            `phone` VARCHAR(15) DEFAULT NULL,
            `is_active` TINYINT(1) DEFAULT 1,
            `last_login` DATETIME DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "farmers" => "CREATE TABLE IF NOT EXISTS `farmers` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `farmer_code` VARCHAR(20) NOT NULL UNIQUE,
            `name` VARCHAR(100) NOT NULL,
            `name_marathi` VARCHAR(100) DEFAULT NULL,
            `phone` VARCHAR(15) NOT NULL,
            `whatsapp` VARCHAR(15) DEFAULT NULL,
            `email` VARCHAR(150) DEFAULT NULL,
            `address` TEXT NOT NULL,
            `village` VARCHAR(100) DEFAULT NULL,
            `taluka` VARCHAR(100) DEFAULT NULL,
            `district` VARCHAR(100) DEFAULT NULL,
            `bank_name` VARCHAR(100) DEFAULT NULL,
            `bank_account` VARCHAR(30) DEFAULT NULL,
            `ifsc_code` VARCHAR(20) DEFAULT NULL,
            `aadhar` VARCHAR(20) DEFAULT NULL,
            `photo` VARCHAR(255) DEFAULT NULL,
            `animal_type` ENUM('cow','buffalo','both') DEFAULT 'cow',
            `animal_count` INT DEFAULT 0,
            `joining_date` DATE NOT NULL,
            `is_active` TINYINT(1) DEFAULT 1,
            `notes` TEXT DEFAULT NULL,
            `created_by` INT UNSIGNED DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "milk_rates" => "CREATE TABLE IF NOT EXISTS `milk_rates` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `animal_type` ENUM('cow','buffalo') NOT NULL,
            `shift` ENUM('morning','evening') NOT NULL,
            `fat_rate` DECIMAL(8,2) NOT NULL,
            `snf_rate` DECIMAL(8,2) NOT NULL,
            `base_rate` DECIMAL(8,2) NOT NULL,
            `effective_from` DATE NOT NULL,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "milk_collections" => "CREATE TABLE IF NOT EXISTS `milk_collections` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `farmer_id` INT UNSIGNED NOT NULL,
            `collection_date` DATE NOT NULL,
            `shift` ENUM('morning','evening') NOT NULL,
            `animal_type` ENUM('cow','buffalo') DEFAULT 'cow',
            `quantity` DECIMAL(8,2) NOT NULL,
            `fat` DECIMAL(4,2) NOT NULL,
            `snf` DECIMAL(4,2) NOT NULL,
            `clr` DECIMAL(4,2) DEFAULT NULL,
            `rate` DECIMAL(8,2) NOT NULL,
            `amount` DECIMAL(10,2) NOT NULL,
            `rate_id` INT UNSIGNED DEFAULT NULL,
            `is_billed` TINYINT(1) DEFAULT 0,
            `bill_id` INT UNSIGNED DEFAULT NULL,
            `notes` VARCHAR(255) DEFAULT NULL,
            `entered_by` INT UNSIGNED DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`farmer_id`) REFERENCES `farmers`(`id`) ON DELETE RESTRICT,
            INDEX `idx_date` (`collection_date`),
            INDEX `idx_farmer` (`farmer_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "bills" => "CREATE TABLE IF NOT EXISTS `bills` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `bill_number` VARCHAR(30) NOT NULL UNIQUE,
            `farmer_id` INT UNSIGNED NOT NULL,
            `bill_type` ENUM('weekly','monthly','custom') DEFAULT 'monthly',
            `period_from` DATE NOT NULL,
            `period_to` DATE NOT NULL,
            `total_quantity` DECIMAL(10,2) DEFAULT 0,
            `total_amount` DECIMAL(12,2) DEFAULT 0,
            `deductions` DECIMAL(10,2) DEFAULT 0,
            `bonus` DECIMAL(10,2) DEFAULT 0,
            `net_amount` DECIMAL(12,2) DEFAULT 0,
            `payment_status` ENUM('pending','partial','paid') DEFAULT 'pending',
            `payment_date` DATE DEFAULT NULL,
            `payment_mode` ENUM('cash','bank_transfer','upi','cheque') DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `generated_by` INT UNSIGNED DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`farmer_id`) REFERENCES `farmers`(`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "payments" => "CREATE TABLE IF NOT EXISTS `payments` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `bill_id` INT UNSIGNED NOT NULL,
            `farmer_id` INT UNSIGNED NOT NULL,
            `amount` DECIMAL(10,2) NOT NULL,
            `payment_date` DATE NOT NULL,
            `payment_mode` ENUM('cash','bank_transfer','upi','cheque') NOT NULL,
            `reference` VARCHAR(100) DEFAULT NULL,
            `notes` VARCHAR(255) DEFAULT NULL,
            `received_by` INT UNSIGNED DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`bill_id`) REFERENCES `bills`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "expenses" => "CREATE TABLE IF NOT EXISTS `expenses` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `category` VARCHAR(100) NOT NULL,
            `description` TEXT NOT NULL,
            `amount` DECIMAL(10,2) NOT NULL,
            `expense_date` DATE NOT NULL,
            `receipt` VARCHAR(255) DEFAULT NULL,
            `payment_mode` ENUM('cash','bank_transfer','upi','cheque') DEFAULT 'cash',
            `notes` VARCHAR(255) DEFAULT NULL,
            `added_by` INT UNSIGNED DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "activity_logs" => "CREATE TABLE IF NOT EXISTS `activity_logs` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT UNSIGNED DEFAULT NULL,
            `action` VARCHAR(100) NOT NULL,
            `module` VARCHAR(50) NOT NULL,
            `description` TEXT DEFAULT NULL,
            `ip_address` VARCHAR(45) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "settings" => "CREATE TABLE IF NOT EXISTS `settings` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `setting_key` VARCHAR(100) NOT NULL UNIQUE,
            `setting_value` TEXT DEFAULT NULL,
            `setting_group` VARCHAR(50) DEFAULT 'general',
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    foreach ($tables as $name => $sql) {
        $pdo->exec($sql);
        logMsg("✅ Table `$name` created/verified.");
    }

    // Seed users (generate fresh hash)
    $hash = password_hash('Admin@1234', PASSWORD_BCRYPT, ['cost' => 12]);
    $pdo->exec("INSERT IGNORE INTO `users` (`name`,`email`,`username`,`password`,`role`,`phone`) VALUES
        ('Administrator','admin@milkmate.local','admin','$hash','admin','9999999999'),
        ('Staff User','staff@milkmate.local','staff','$hash','staff','8888888888')");
    logMsg("✅ Default users seeded (admin/staff | password: Admin@1234).");

    // Seed rates
    $pdo->exec("INSERT IGNORE INTO `milk_rates` (`animal_type`,`shift`,`fat_rate`,`snf_rate`,`base_rate`,`effective_from`,`is_active`) VALUES
        ('cow','morning',6.50,4.00,25.00,CURDATE(),1),
        ('cow','evening',6.50,4.00,25.00,CURDATE(),1),
        ('buffalo','morning',8.00,5.00,35.00,CURDATE(),1),
        ('buffalo','evening',8.00,5.00,35.00,CURDATE(),1)");
    logMsg("✅ Default milk rates seeded.");

    // Seed settings
    $defaultSettings = [
        ['company_name','MilkMate Dairy','company'],
        ['company_name_marathi','मिल्कमेट डेअरी','company'],
        ['company_address','Village Rd, Maharashtra, India','company'],
        ['company_phone','9999999999','company'],
        ['company_email','info@milkmate.local','company'],
        ['company_logo','','company'],
        ['currency_symbol','₹','general'],
        ['language','en','general'],
        ['bill_prefix','BILL','billing'],
        ['farmer_code_prefix','F','farmers'],
        ['sms_enabled','0','notifications'],
        ['whatsapp_enabled','0','notifications'],
    ];
    $sStmt = $pdo->prepare("INSERT IGNORE INTO `settings` (`setting_key`,`setting_value`,`setting_group`) VALUES (?,?,?)");
    foreach ($defaultSettings as $s) { $sStmt->execute($s); }
    logMsg("✅ Default settings seeded.");

    // Sample farmers
    $pdo->exec("INSERT IGNORE INTO `farmers` (`farmer_code`,`name`,`phone`,`address`,`village`,`taluka`,`district`,`animal_type`,`animal_count`,`joining_date`) VALUES
        ('F001','Ramesh Patil','9876543210','Shivaji Nagar, Ward 3','Pune','Haveli','Pune','cow',3,'2024-01-01'),
        ('F002','Suresh Jadhav','9876543211','Gandhi Chowk, Near School','Nashik','Niphad','Nashik','buffalo',2,'2024-01-15'),
        ('F003','Anita Shinde','9876543212','Patel Wadi','Aurangabad','Paithan','Aurangabad','both',5,'2024-02-01')");
    logMsg("✅ Sample farmers seeded (F001, F002, F003).");

    logMsg("🎉 Setup completed successfully!");

} catch (PDOException $e) {
    errMsg("❌ Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html><head>
<meta charset="UTF-8">
<title>MilkMate Setup</title>
<style>
  body{font-family:sans-serif;background:#0a0f1e;color:#f1f5f9;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;}
  .setup-card{background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);border-radius:20px;padding:40px;max-width:640px;width:100%;}
  h1{font-size:28px;margin-bottom:8px;} h1 span{color:#14b8a6;}
  .log{margin:20px 0;background:rgba(0,0,0,0.3);border-radius:12px;padding:16px;font-size:13px;line-height:1.8;}
  .err{background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.3);border-radius:12px;padding:16px;color:#fca5a5;font-size:13px;line-height:1.8;}
  .go-btn{display:inline-flex;align-items:center;gap:8px;background:#0f766e;color:#fff;padding:12px 28px;border-radius:12px;text-decoration:none;font-weight:600;font-size:15px;margin-top:20px;}
  .warn{background:rgba(245,158,11,0.15);border:1px solid rgba(245,158,11,0.3);border-radius:10px;padding:14px;color:#fcd34d;font-size:13px;margin-top:16px;}
</style>
</head>
<body>
<div class="setup-card">
  <h1>🥛 Milk<span>Mate</span> Setup</h1>
  <p style="color:#94a3b8;">Database initialization script</p>

  <?php if(!empty($errors)): ?>
  <div class="err">
    <?php foreach($errors as $e) echo htmlspecialchars($e)."<br>"; ?>
    <br><strong>💡 Check: Is XAMPP MySQL running? Is DB_USER/DB_PASS correct in config/database.php?</strong>
  </div>
  <?php else: ?>
  <div class="log">
    <?php foreach($log as $l) echo htmlspecialchars($l)."<br>"; ?>
  </div>
  <a href="http://localhost/MilkMate/auth/login.php" class="go-btn">🚀 Go to Login Page</a>
  <div class="warn">
    ⚠️ <strong>Security:</strong> Delete <code>setup.php</code> from your server after setup!
    <br><strong>Credentials:</strong> admin / Admin@1234 &nbsp;|&nbsp; staff / Admin@1234
  </div>
  <?php endif; ?>
</div>
</body></html>
