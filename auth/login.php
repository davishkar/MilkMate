<?php
require_once dirname(__DIR__) . '/config/app.php';
if (isLoggedIn()) redirect(APP_URL . '/dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$username || !$password) {
        $error = 'Please enter username and password.';
    } else {
        $stmt = db()->prepare("SELECT * FROM users WHERE (username=? OR email=?) AND is_active=1 LIMIT 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['email']   = $user['email'];
            db()->prepare("UPDATE users SET last_login=NOW() WHERE id=?")->execute([$user['id']]);
            logActivity('login', 'auth', 'User logged in');
            redirect(APP_URL . '/dashboard.php');
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
$companyName = getSetting('company_name', 'MilkMate Dairy');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — <?= htmlspecialchars($companyName) ?></title>
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/fonts.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-logo">
      <div class="login-logo-icon">🥛</div>
      <h1>Milk<span>Mate</span></h1>
      <p><?= htmlspecialchars($companyName) ?></p>
    </div>

    <?php if ($error): ?>
    <div class="toast error" style="position:relative;animation:none;margin-bottom:16px;min-width:auto;">
      <span class="toast-icon">❌</span>
      <div class="toast-body"><div class="toast-title"><?= htmlspecialchars($error) ?></div></div>
    </div>
    <?php endif; ?>

    <form method="POST" id="login-form">
      <div class="form-group">
        <label class="form-label">Username or Email</label>
        <input type="text" name="username" class="form-control" placeholder="admin" required
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" autocomplete="username">
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <div style="position:relative;">
          <input type="password" name="password" class="form-control" placeholder="••••••••" required id="pwd-input" autocomplete="current-password">
          <button type="button" onclick="togglePwd()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:16px;" id="pwd-toggle">👁️</button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary w-full" style="justify-content:center;padding:12px;" id="login-btn">
        🔐 Sign In
      </button>
    </form>

    <div style="margin-top:20px;padding:14px;background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.2);border-radius:10px;font-size:12.5px;color:var(--text-secondary);">
      <strong style="color:var(--accent);">Demo Credentials</strong><br>
      Admin: <code style="color:var(--primary-light);">admin</code> / <code style="color:var(--primary-light);">Admin@1234</code><br>
      Staff: <code style="color:var(--primary-light);">staff</code> / <code style="color:var(--primary-light);">Admin@1234</code>
    </div>
  </div>
</div>
<script>
function togglePwd() {
  const i = document.getElementById('pwd-input');
  const b = document.getElementById('pwd-toggle');
  i.type = i.type === 'password' ? 'text' : 'password';
  b.textContent = i.type === 'password' ? '👁️' : '🙈';
}
document.getElementById('login-form').addEventListener('submit', function() {
  const btn = document.getElementById('login-btn');
  btn.innerHTML = '<span class="spinner"></span> Signing in...';
  btn.disabled = true;
});
</script>
</body>
</html>
