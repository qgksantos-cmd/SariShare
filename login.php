<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if (currentUser()) {
    header('Location: ' . (currentUser()['role'] === 'staff' ? 'employee.php' : 'manager.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = (string) ($_POST['password'] ?? '');

    if (!$email || $password === '') {
        $error = 'Enter a valid email and password.';
    } else {
        try {
            $statement = database()->prepare(
              'SELECT u.id, u.branch_id, u.role_id, u.company_id, u.full_name, u.email,
                  u.password_hash, r.role_name AS role, b.name AS branch_name
                 FROM users u
                 JOIN branches b ON b.id = u.branch_id
               JOIN roles r ON r.role_id = u.role_id
                 WHERE u.email = :email
                 LIMIT 1'
            );
            $statement->execute(['email' => $email]);
            $user = $statement->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                unset($user['password_hash']);
                $_SESSION['user'] = $user;
                session_regenerate_id(true);
                header('Location: ' . ($user['role'] === 'staff' ? 'employee.php' : 'manager.php'));
                exit;
            }

            $error = 'Those sign-in details do not match our records.';
        } catch (PDOException $exception) {
            $error = 'The database is unavailable. Start MariaDB and import database.sql first.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In | SariShare</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body class="auth-page">
  <main class="auth-shell">
    <section class="auth-intro">
      <a class="brand" href="login.php" aria-label="SariShare home"><span class="brand-name">SariShare</span><span class="brand-subtitle">File Portal</span></a>
      <div class="intro-copy"><p class="eyebrow">STA. ROSA BRANCH</p><h1>Files that keep the day moving.</h1><p>Secure access to the reports, forms, and resources your branch needs.</p></div>
      <p class="auth-footer">A simple, shared home for every branch file.</p>
    </section>
    <section class="auth-panel" aria-labelledby="login-title">
      <div class="auth-panel-inner">
        <p class="eyebrow">WELCOME BACK</p>
        <h1 id="login-title">Sign in to SariShare</h1>
        <p class="auth-note">Use your SariShare account to continue.</p>
        <?php if ($error): ?><p class="form-error" role="alert"><?= h($error) ?></p><?php endif; ?>
        <form method="post" class="login-form">
          <label for="email">Work email</label>
          <input id="email" name="email" type="email" autocomplete="email" placeholder="maria.santos@example.com" required>
          <label for="password">Password</label>
          <div class="password-field">
            <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" required>
            <button class="password-toggle" type="button" data-password-toggle="#password" aria-label="Show password" aria-pressed="false" onclick="const input=this.parentElement.querySelector('input'); const visible=input.type==='text'; input.type=visible?'password':'text'; this.textContent=visible?'Show':'Hide'; this.setAttribute('aria-pressed', String(!visible));">Show</button>
          </div>
          <button class="primary-button" type="submit">Sign in</button>
        </form>
        <p class="demo-note"><a href="signup.php" class="secondary-button" style="display:inline-flex; align-items:center; justify-content:center; text-decoration:none;">Sign up</a></p>
      </div>
    </section>
  </main>
  <script src="script.js"></script>
</body>
</html>
