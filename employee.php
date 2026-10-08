<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$user = requireRole(['staff']);
$pdo = database();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request_access') {
    $fileId = filter_input(INPUT_POST, 'file_id', FILTER_VALIDATE_INT);
    $reason = trim((string) ($_POST['reason'] ?? ''));
    $duration = trim((string) ($_POST['duration'] ?? '1 day'));

    if ($fileId && $reason !== '') {
        $statement = $pdo->prepare(
            'INSERT INTO access_requests (file_id, requester_id, status, review_note)
             SELECT f.id, :requester_id, \'pending\', :reason
             FROM files f
             JOIN users uploader ON uploader.id = f.uploaded_by
             WHERE f.id = :file_id
               AND (f.visibility = \'all_staff\' OR (f.visibility = \'branch\' AND uploader.branch_id = :branch_id))
               AND NOT EXISTS (
                   SELECT 1 FROM access_requests existing
                   WHERE existing.file_id = f.id AND existing.requester_id = :existing_requester_id
                     AND existing.status IN (\'pending\', \'approved\')
               )'
        );
        $statement->execute([
            'requester_id' => $user['id'],
            'reason' => $reason . ' Duration: ' . $duration,
            'file_id' => $fileId,
            'branch_id' => $user['branch_id'],
            'existing_requester_id' => $user['id'],
        ]);
    }

    header('Location: employee.php');
    exit;
}

$fileStatement = $pdo->prepare(
    'SELECT f.id, f.file_name, c.name AS category, f.file_size_bytes, f.updated_at,
            ar.status AS request_status
     FROM files f
     JOIN categories c ON c.id = f.category_id
     JOIN users uploader ON uploader.id = f.uploaded_by
     LEFT JOIN access_requests ar ON ar.file_id = f.id
       AND ar.requester_id = :requester_id
       AND ar.status IN (\'pending\', \'approved\')
     WHERE f.visibility = \'all_staff\'
        OR (f.visibility = \'branch\' AND uploader.branch_id = :branch_id)
     ORDER BY f.updated_at DESC'
);
$fileStatement->execute(['requester_id' => $user['id'], 'branch_id' => $user['branch_id']]);
$files = $fileStatement->fetchAll();

$requestStatement = $pdo->prepare(
    'SELECT f.file_name, ar.requested_at, ar.review_note, ar.status
     FROM access_requests ar
     JOIN files f ON f.id = ar.file_id
     WHERE ar.requester_id = :requester_id
     ORDER BY ar.requested_at DESC'
);
$requestStatement->execute(['requester_id' => $user['id']]);
$requests = $requestStatement->fetchAll();

function formattedSize(int $bytes): string
{
    return number_format($bytes / 1024) . ' KB';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Portal | SariShare</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="styles.css">
</head>
<body>
  <header class="topbar">
    <a class="brand" href="employee.php" aria-label="SariShare home"><span class="brand-name">SariShare</span><span class="brand-subtitle">File Portal</span></a>
    <a class="logout-link" href="logout.php">Sign out</a>
    <div class="identity-bar"><strong><?= h($user['full_name']) ?></strong><span aria-hidden="true">&gt;</span><span><?= h(roleLabel($user['role'])) ?></span><span aria-hidden="true">&gt;</span><span><?= h($user['branch_name']) ?></span></div>
  </header>
  <nav class="tabs" aria-label="File sections"><button class="tab is-active" type="button" data-tab="all-files">All Files</button><button class="tab" type="button" data-tab="my-requests">My Requests</button></nav>
  <main>
    <section class="file-list" id="all-files" aria-label="All files">
      <?php foreach ($files as $file): ?>
        <article class="file-row">
          <div class="file-icon" aria-hidden="true"><?= h(strtoupper(pathinfo($file['file_name'], PATHINFO_EXTENSION))) ?></div>
          <div class="file-copy"><h2><?= h($file['file_name']) ?></h2><p><?= h($file['category']) ?> <span>·</span> <?= formattedSize((int) $file['file_size_bytes']) ?> <span>·</span> Updated <?= h(date('M j, Y', strtotime($file['updated_at']))) ?></p></div>
          <?php if ($file['request_status'] === 'pending'): ?>
            <button class="file-action pending" type="button"><span class="status-dot"></span>Pending</button>
          <?php elseif ($file['request_status'] === 'approved'): ?>
            <button class="file-action neutral" type="button">Open</button>
          <?php else: ?>
            <form class="request-form" method="post"><input type="hidden" name="action" value="request_access"><input type="hidden" name="file_id" value="<?= (int) $file['id'] ?>"><input type="hidden" name="reason" value=""><input type="hidden" name="duration" value="1 day"><button class="file-action request" type="submit">Request Access</button></form>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
      <?php if (!$files): ?><p class="empty-state">No files are available for your branch yet.</p><?php endif; ?>
    </section>
    <section class="request-list is-hidden" id="my-requests" aria-label="My requests" aria-hidden="true">
      <?php foreach ($requests as $request): ?>
        <article class="request-card"><div class="request-heading"><h2><?= h($request['file_name']) ?></h2><p>Requested <?= h(date('M j, Y — g:i A', strtotime($request['requested_at']))) ?></p></div><div class="request-reason"><strong>Your Reason</strong><span><?= h($request['review_note'] ?: 'No reason provided.') ?></span></div><p class="request-duration"><span>Status:</span> <strong><?= h(ucfirst($request['status'])) ?></strong></p></article>
      <?php endforeach; ?>
      <?php if (!$requests): ?><p class="empty-state">You have not submitted any access requests.</p><?php endif; ?>
    </section>
  </main>
  <script src="script.js"></script>
</body>
</html>
