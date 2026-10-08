<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$user = requireRole(['manager', 'admin']);
$pdo = database();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'review_request') {
    $requestId = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    $status = in_array($_POST['status'] ?? '', ['approved', 'denied'], true) ? $_POST['status'] : null;

    if ($requestId && $status) {
        $statement = $pdo->prepare(
            'UPDATE access_requests ar
             JOIN files f ON f.id = ar.file_id
             JOIN users uploader ON uploader.id = f.uploaded_by
             SET ar.status = :status, ar.reviewed_by = :reviewed_by, ar.reviewed_at = NOW()
             WHERE ar.id = :request_id AND ar.status = \'pending\' AND uploader.branch_id = :branch_id'
        );
        $statement->execute(['status' => $status, 'reviewed_by' => $user['id'], 'request_id' => $requestId, 'branch_id' => $user['branch_id']]);
    }

    header('Location: manager.php#requests');
    exit;
}

$countStatement = $pdo->prepare('SELECT COUNT(*) FROM access_requests ar JOIN files f ON f.id = ar.file_id JOIN users uploader ON uploader.id = f.uploaded_by WHERE ar.status = \'pending\' AND uploader.branch_id = :branch_id');
$countStatement->execute(['branch_id' => $user['branch_id']]);
$pendingCount = (int) $countStatement->fetchColumn();

$fileCountStatement = $pdo->prepare('SELECT COUNT(*) FROM files f JOIN users uploader ON uploader.id = f.uploaded_by WHERE uploader.branch_id = :branch_id');
$fileCountStatement->execute(['branch_id' => $user['branch_id']]);
$fileCount = (int) $fileCountStatement->fetchColumn();

$employeeCountStatement = $pdo->prepare('SELECT COUNT(*) FROM users WHERE branch_id = :branch_id AND role = \'staff\'');
$employeeCountStatement->execute(['branch_id' => $user['branch_id']]);
$employeeCount = (int) $employeeCountStatement->fetchColumn();

$requestStatement = $pdo->prepare(
    'SELECT ar.id, requester.full_name, f.file_name, ar.requested_at
     FROM access_requests ar
     JOIN files f ON f.id = ar.file_id
     JOIN users uploader ON uploader.id = f.uploaded_by
     JOIN users requester ON requester.id = ar.requester_id
     WHERE ar.status = \'pending\' AND uploader.branch_id = :branch_id
     ORDER BY ar.requested_at DESC'
);
$requestStatement->execute(['branch_id' => $user['branch_id']]);
$requests = $requestStatement->fetchAll();

$fileStatement = $pdo->prepare(
    'SELECT f.file_name, c.name AS category, f.updated_at
     FROM files f
     JOIN categories c ON c.id = f.category_id
     JOIN users uploader ON uploader.id = f.uploaded_by
     WHERE uploader.branch_id = :branch_id
     ORDER BY f.updated_at DESC
     LIMIT 5'
);
$fileStatement->execute(['branch_id' => $user['branch_id']]);
$files = $fileStatement->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Manager Dashboard | SariShare</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="styles.css">
</head>
<body>
  <header class="topbar manager-topbar"><a class="brand" href="manager.php" aria-label="SariShare home"><span class="brand-name">SariShare</span><span class="brand-subtitle">File Portal</span></a><a class="logout-link" href="logout.php">Sign out</a><div class="identity-bar"><strong><?= h($user['full_name']) ?></strong><span aria-hidden="true">&gt;</span><span><?= h(roleLabel($user['role'])) ?></span><span aria-hidden="true">&gt;</span><span><?= h($user['branch_name']) ?></span></div></header>
  <nav class="manager-nav"><a class="manager-nav-link is-active" href="#overview">Overview</a><a class="manager-nav-link" href="#requests">Access Requests <span class="nav-count"><?= $pendingCount ?></span></a><a class="manager-nav-link" href="#files">Shared Files</a></nav>
  <main class="manager-main" id="overview">
    <div class="manager-heading"><div><p class="eyebrow"><?= h(strtoupper($user['branch_name'])) ?></p><h1>Good morning, <?= h(strtok($user['full_name'], ' ')) ?>.</h1><p>Here is what needs your attention today.</p></div><button class="primary-button compact" type="button">+ Upload file</button></div>
    <section class="stat-grid" aria-label="Branch overview"><article class="stat-card"><span class="stat-label">Pending requests</span><strong><?= $pendingCount ?></strong><span class="stat-trend">Waiting for review</span></article><article class="stat-card"><span class="stat-label">Shared files</span><strong><?= $fileCount ?></strong><span class="stat-trend">Branch file library</span></article><article class="stat-card"><span class="stat-label">Active employees</span><strong><?= $employeeCount ?></strong><span class="stat-trend">Staff accounts</span></article></section>
    <section class="manager-section" id="requests"><div class="section-heading"><div><p class="eyebrow">REVIEW QUEUE</p><h2>Access requests</h2></div></div><div class="approval-list">
      <?php foreach ($requests as $request): ?><article class="approval-row"><div class="avatar"><?= h(strtoupper(substr($request['full_name'], 0, 1) . substr(strrchr($request['full_name'], ' ') ?: '', 1, 1))) ?></div><div class="approval-copy"><strong><?= h($request['full_name']) ?></strong><span>Requested access to <b><?= h($request['file_name']) ?></b></span><small><?= h(date('M j, Y · g:i A', strtotime($request['requested_at']))) ?></small></div><div class="approval-actions"><form method="post"><input type="hidden" name="action" value="review_request"><input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>"><input type="hidden" name="status" value="denied"><button class="secondary-button" type="submit">Deny</button></form><form method="post"><input type="hidden" name="action" value="review_request"><input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>"><input type="hidden" name="status" value="approved"><button class="primary-button compact" type="submit">Approve</button></form></div></article><?php endforeach; ?>
      <?php if (!$requests): ?><p class="empty-state">There are no pending access requests.</p><?php endif; ?>
    </div></section>
    <section class="manager-section" id="files"><div class="section-heading"><div><p class="eyebrow">FILE LIBRARY</p><h2>Recently shared</h2></div></div><div class="shared-file-grid">
      <?php foreach ($files as $file): ?><article class="shared-file"><div class="file-icon"><?= h(strtoupper(pathinfo($file['file_name'], PATHINFO_EXTENSION))) ?></div><div><strong><?= h($file['file_name']) ?></strong><span><?= h($file['category']) ?> · Updated <?= h(date('M j, Y', strtotime($file['updated_at']))) ?></span></div><button class="icon-button" type="button" aria-label="More options">•••</button></article><?php endforeach; ?>
      <?php if (!$files): ?><p class="empty-state">No files have been shared yet.</p><?php endif; ?>
    </div></section>
  </main>
</body>
</html>
