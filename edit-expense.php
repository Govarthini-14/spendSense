<?php
require_once __DIR__ . '/includes/auth.php';
ensureAuthenticated();

$user = currentUser();
$pdo = getPdo();
$expenseId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM expenses WHERE id = :id AND user_id = :user_id LIMIT 1');
$stmt->execute(['id' => $expenseId, 'user_id' => (int)$user['id']]);
$expense = $stmt->fetch();
if (!$expense) {
    setFlash('error', 'Expense not found.');
    header('Location: transactions.php');
    exit;
}

$pageTitle = 'Edit Expense';
$categories = ['Food', 'Travel', 'Education', 'Shopping', 'Entertainment', 'Accommodation', 'Health', 'Bills and Recharge', 'Other'];
$paymentMethods = ['UPI', 'Card', 'Cash', 'Bank Transfer', 'Wallet'];
$errors = [];
$formData = [
    'title' => $expense['title'],
    'amount' => $expense['amount'],
    'category' => $expense['category'],
    'expense_date' => $expense['expense_date'],
    'payment_method' => $expense['payment_method'],
    'notes' => $expense['notes'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid request. Please try again.';
    }

    $formData = [
        'title' => trim($_POST['title'] ?? ''),
        'amount' => trim($_POST['amount'] ?? ''),
        'category' => trim($_POST['category'] ?? ''),
        'expense_date' => trim($_POST['expense_date'] ?? ''),
        'payment_method' => trim($_POST['payment_method'] ?? ''),
        'notes' => trim($_POST['notes'] ?? ''),
    ];

    if ($formData['title'] === '') {
        $errors[] = 'Expense title is required.';
    }
    if ($formData['amount'] === '' || !is_numeric($formData['amount']) || (float)$formData['amount'] <= 0) {
        $errors[] = 'Amount must be greater than zero.';
    }
    if (!in_array($formData['category'], $categories, true)) {
        $errors[] = 'Please select a valid category.';
    }
    if (!in_array($formData['payment_method'], $paymentMethods, true)) {
        $errors[] = 'Please select a valid payment method.';
    }
    if ($formData['expense_date'] === '' || strtotime($formData['expense_date']) === false) {
        $errors[] = 'Expense date is required.';
    } elseif (new DateTime($formData['expense_date']) > new DateTime('today')) {
        $errors[] = 'Expense date cannot be in the future.';
    }

    if (empty($errors)) {
        $update = $pdo->prepare('UPDATE expenses SET title = :title, amount = :amount, category = :category, expense_date = :expense_date, payment_method = :payment_method, notes = :notes, updated_at = NOW() WHERE id = :id AND user_id = :user_id');
        $update->execute([
            'title' => $formData['title'],
            'amount' => (float)$formData['amount'],
            'category' => $formData['category'],
            'expense_date' => $formData['expense_date'],
            'payment_method' => $formData['payment_method'],
            'notes' => $formData['notes'],
            'id' => $expenseId,
            'user_id' => (int)$user['id'],
        ]);

        setFlash('success', 'Expense updated successfully.');
        header('Location: transactions.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="app-shell">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <main class="content">
        <header class="topbar">
            <div>
                <h1 class="page-title">Edit Expense</h1>
                <p class="page-subtitle">Update your transaction details</p>
            </div>
        </header>

        <div class="card form-card form-page">
            <?php if (!empty($errors)): ?>
                <div class="error-text" style="margin-bottom: 18px;">
                    <?php foreach ($errors as $error): ?>
                        <div><?= e($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" data-validate="true">
                <?= csrfField() ?>
                <div class="form-grid">
                    <div class="field">
                        <label for="title">Expense title</label>
                        <input id="title" name="title" type="text" value="<?= e($formData['title']) ?>" required>
                    </div>
                    <div class="field">
                        <label for="amount">Amount (₹)</label>
                        <input id="amount" name="amount" type="number" step="0.01" min="0.01" value="<?= e((string)$formData['amount']) ?>" required>
                    </div>
                    <div class="field">
                        <label for="category">Category</label>
                        <select id="category" name="category" required>
                            <option value="">Select category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= e($category) ?>" <?= $formData['category'] === $category ? 'selected' : '' ?>><?= e($category) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="payment_method">Payment method</label>
                        <select id="payment_method" name="payment_method" required>
                            <option value="">Select method</option>
                            <?php foreach ($paymentMethods as $method): ?>
                                <option value="<?= e($method) ?>" <?= $formData['payment_method'] === $method ? 'selected' : '' ?>><?= e($method) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field full">
                        <label for="expense_date">Expense date</label>
                        <input id="expense_date" name="expense_date" type="date" value="<?= e($formData['expense_date']) ?>" required>
                    </div>
                    <div class="field full">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes"><?= e($formData['notes']) ?></textarea>
                    </div>
                </div>
                <div class="form-actions">
                    <a class="btn btn-outline" href="transactions.php">Cancel</a>
                    <button class="btn btn-primary" type="submit">Update</button>
                </div>
            </form>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
