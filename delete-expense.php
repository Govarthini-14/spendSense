<?php
require_once __DIR__ . '/includes/auth.php';
ensureAuthenticated();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: transactions.php');
    exit;
}

if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
    setFlash('error', 'Invalid request. Please try again.');
    header('Location: transactions.php');
    exit;
}

$expenseId = (int)($_POST['id'] ?? 0);
$userId = (int)currentUser()['id'];
$pdo = getPdo();
$stmt = $pdo->prepare('DELETE FROM expenses WHERE id = :id AND user_id = :user_id');
$stmt->execute(['id' => $expenseId, 'user_id' => $userId]);

setFlash('success', 'Expense deleted successfully.');
header('Location: transactions.php');
exit;
