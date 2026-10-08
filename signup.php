<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if (currentUser()) {
    header('Location: ' . (currentUser()['role'] === 'staff' ? 'employee.php' : 'manager.php'));
    exit;
}

$error = '';
$branchOptions = [];

try {
    $branchStatement = database()->query('SELECT id, name FROM branches ORDER BY name ASC');
    $branchOptions = $branchStatement->fetchAll();
} catch (PDOException $exception) {
    $error = 'The database is unavailable. Start MariaDB and import database.sql first.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $branchId = filter_input(INPUT_POST, 'branch_id', FILTER_VALIDATE_INT);

    if ($fullName === '' || !$email || $password === '' || $confirmPassword === '' || !$branchId) {
        $error = 'Please complete all fields to create your account.';
    } elseif ($password !== $confirmPassword) {
        $error = 'The passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Choose a password with at least 6 characters.';
    } else {
        try {
            $existingUser = database()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $existingUser->execute(['email' => $email]);

            if ($existingUser->fetch()) {
                $error = 'An account with that email already exists.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $insertUser = database()->prepare(
                  'INSERT INTO users (branch_id, role_id, full_name, email, password_hash)
                   VALUES (:branch_id, :role_id, :full_name, :email, :password_hash)'
                );
                $insertUser->execute([
                    'branch_id' => $branchId,
                  'role_id' => 1,
                    'full_name' => $fullName,
                    'email' => $email,
                    'password_hash' => $passwordHash,
                ]);

                $userStatement = database()->prepare(
                  'SELECT u.id, u.branch_id, u.role_id, u.company_id, u.full_name, u.email,
                      r.role_name AS role, b.name AS branch_name
                     FROM users u
                     JOIN branches b ON b.id = u.branch_id
                   JOIN roles r ON r.role_id = u.role_id
                     WHERE u.email = :email
                     LIMIT 1'
                );
                $userStatement->execute(['email' => $email]);
                $user = $userStatement->fetch();

                if ($user) {
                    $_SESSION['user'] = $user;
                    session_regenerate_id(true);
                    header('Location: employee.php');
                    exit;
                }

                $success = 'Account created successfully. You can sign in now.';
            }
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
  <title>Sign Up | SariShare</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css?v=2">
</head>
<body class="auth-page">
  <main class="auth-shell">
    <section class="auth-intro">
      <a class="brand" href="login.php" aria-label="SariShare home"><span class="brand-name">SariShare</span><span class="brand-subtitle">File Portal</span></a>
      <div class="intro-copy"><p class="eyebrow">JOIN THE BRANCH</p><h1>Access shared files from day one.</h1><p>Set up your account to continue with the branch’s daily workflow.</p></div>
      <p class="auth-footer">A simple, shared home for every branch file.</p>
    </section>
    <section class="auth-panel" aria-labelledby="signup-title">
      <div class="auth-panel-inner">
        <p class="eyebrow">CREATE ACCOUNT</p>
        <h1 id="signup-title">Sign up</h1>
        <p class="auth-note">Set up your SariShare profile to continue.</p>
        <?php if ($error): ?><p class="form-error" role="alert"><?= h($error) ?></p><?php endif; ?>
        <?php if (!empty($success)): ?><p class="login-message"><?= h($success) ?></p><?php endif; ?>
        <form method="post" class="login-form">
          <label for="full_name">Full name</label>
          <input class="auth-input" id="full_name" name="full_name" type="text" autocomplete="name" placeholder="Maria Santos" required>
          <label for="email">Work email</label>
          <input class="auth-input" id="email" name="email" type="email" autocomplete="email" placeholder="maria.santos@example.com" required>
          <label for="password">Password</label>
          <div class="password-field">
            <input class="auth-input" id="password" name="password" type="password" autocomplete="new-password" placeholder="Create a password" required>
            <button class="password-toggle" type="button" data-password-toggle="#password" aria-label="Show password" aria-pressed="false" onclick="const input=this.parentElement.querySelector('input'); const visible=input.type==='text'; input.type=visible?'password':'text'; this.textContent=visible?'Show':'Hide'; this.setAttribute('aria-pressed', String(!visible));">Show</button>
          </div>
          <label for="confirm_password">Confirm password</label>
          <div class="password-field">
            <input class="auth-input" id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" placeholder="Re-enter your password" required>
            <button class="password-toggle" type="button" data-password-toggle="#confirm_password" aria-label="Show confirmed password" aria-pressed="false" onclick="const input=this.parentElement.querySelector('input'); const visible=input.type==='text'; input.type=visible?'password':'text'; this.textContent=visible?'Show':'Hide'; this.setAttribute('aria-pressed', String(!visible));">Show</button>
          </div>
          <label for="branch_id">Branch</label>
          <select class="auth-input" id="branch_id" name="branch_id" required>
            <option value="">Select your branch</option>
            <?php foreach ($branchOptions as $branch): ?>
              <option value="<?= (int) $branch['id'] ?>"><?= h((string) $branch['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="primary-button" type="submit">Create account</button>
        </form>
        <p class="demo-note">Already have an account? <a href="login.php">Sign in</a></p>
      </div>
    </section>
  </main>
  <script src="script.js"></script>
</body>
</html>
