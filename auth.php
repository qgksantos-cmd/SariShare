<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireRole(array $roles): array
{
    $user = currentUser();

    if (!$user || !in_array($user['role'], $roles, true)) {
        header('Location: login.php');
        exit;
    }

    return $user;
}

function roleLabel(string $role): string
{
    return $role === 'staff' ? 'Branch Cashier' : 'Branch Manager';
}
