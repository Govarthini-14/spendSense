<?php
require_once __DIR__ . '/includes/auth.php';
ensureAuthenticated();

$pageTitle = 'Add Expense';
$user = currentUser();
$styles = ['Food', 'Travel', 'Education', 'Shopping', 'Entertainment', 'Accommodation', 'Health', 'Bills and Recharge', 'Other'];
$paymentMethods = ['UPI', 'Card', 'Cash', 'Bank Transfer', 'Wallet'];
$errors = [];
$formData = [
    'title' => '',
    'amount' => '',
    'category' => '',
    'expense_date' => date('Y-m-d'),
    'payment_method' => '',
    'notes' => '',
];
$budgetWarning = '';

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
    if (!in_array($formData['category'], $styles, true)) {
        $errors[] = 'Please select a valid category.';
    }
    if ($formData['payment_method'] === '' || !in_array($formData['payment_method'], $paymentMethods, true)) {
        $errors[] = 'Please select a payment method.';
    }
    if ($formData['expense_date'] === '' || strtotime($formData['expense_date']) === false) {
        $errors[] = 'Expense date is required.';
    } else {
        $expenseDate = new DateTime($formData['expense_date']);
        $today = new DateTime('today');
        if ($expenseDate > $today) {
            $errors[] = 'Expense date cannot be in the future.';
        }
    }

    if (empty($errors)) {
        $pdo = getPdo();
        $month = (int)date('n', strtotime($formData['expense_date']));
        $year = (int)date('Y', strtotime($formData['expense_date']));
        $statement = $pdo->prepare(
            'INSERT INTO expenses (user_id, title, amount, category, expense_date, payment_method, notes, created_at, updated_at)
             VALUES (:user_id, :title, :amount, :category, :expense_date, :payment_method, :notes, NOW(), NOW())'
        );
        $statement->execute([
            'user_id' => (int)$user['id'],
            'title' => $formData['title'],
            'amount' => (float)$formData['amount'],
            'category' => $formData['category'],
            'expense_date' => $formData['expense_date'],
            'payment_method' => $formData['payment_method'],
            'notes' => $formData['notes'],
        ]);

        setFlash('success', 'Expense added successfully.');
        header('Location: index.php');
        exit;
    }
}

$month = (int)date('n');
$year = (int)date('Y');
$budgetInfo = getMonthlyBudget((int)$user['id'], $month, $year);
if ($budgetInfo && !empty($budgetInfo['monthly_limit'])) {
    $budgetTotal = (float)$budgetInfo['monthly_limit'];
    $currentSpent = (float)(getMonthlyExpenseSummary((int)$user['id'], $month, $year)['total_expenses'] ?? 0);
    $estimatedSpent = $currentSpent + (float)($formData['amount'] ?: 0);
    $percent = $budgetTotal > 0 ? ($estimatedSpent / $budgetTotal) * 100 : 0;
    if ($budgetTotal > 0 && $estimatedSpent >= 0) {
        if ($percent < 80) {
            $budgetWarning = 'Projected total: ' . number_format($percent, 1) . '% of your monthly budget.';
        } elseif ($percent < 100) {
            $remaining = $budgetTotal - $estimatedSpent;
            $budgetWarning = 'Warning: You are projected to use ' . number_format($percent, 1) . '% of your monthly budget. Only ' . formatCurrency($remaining) . ' remains.';
        } else {
            $budgetWarning = 'Budget exceeded: This expense would push you beyond your monthly budget.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="app-shell">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <main class="content">
        <header class="topbar">
            <div>
                <h1 class="page-title">Add Expense</h1>
                <p class="page-subtitle">Record a new daily expense</p>
            </div>
        </header>

        <div class="card form-card form-page">
            <?php if ($budgetWarning !== ''): ?>
                <div class="status-badge status-warning" style="margin-bottom: 18px;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <?= e($budgetWarning) ?>
                </div>
            <?php endif; ?>

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
                        <input id="title" name="title" type="text" value="<?= e($formData['title']) ?>" placeholder="e.g. Groceries" required>
                    </div>
                    <div class="field">
                        <label for="amount">Amount (₹)</label>
                        <input id="amount" name="amount" type="number" step="0.01" min="0.01" value="<?= e($formData['amount']) ?>" placeholder="0.00" required>
                    </div>
                    <div class="field">
                        <label for="category">Category</label>
                        <select id="category" name="category" required>
                            <option value="">Select category</option>
                            <?php foreach ($styles as $category): ?>
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
                        <label for="notes">Notes (optional)</label>
                        <textarea id="notes" name="notes" placeholder="Add context or reminders"><?= e($formData['notes']) ?></textarea>
                    </div>
                </div>
                <div class="form-actions">
                    <a class="btn btn-outline" href="index.php">Cancel</a>
                    <button class="btn btn-primary" type="submit">Save Expense</button>
                </div>
            </form>
        </div>
    </main>
</div>
<script>
const amountInput = document.getElementById('amount');
const budgetWarningNode = document.querySelector('.status-badge.status-warning');
const currentBudget = <?= json_encode((float)($budgetInfo['monthly_limit'] ?? 0)) ?>;
const currentSpent = <?= json_encode((float)(getMonthlyExpenseSummary((int)$user['id'], $month, $year)['total_expenses'] ?? 0)) ?>;

amountInput?.addEventListener('input', function () {
    if (!currentBudget || Number.isNaN(Number(this.value))) {
        return;
    }
    const projected = currentSpent + Number(this.value || 0);
    const percent = (projected / currentBudget) * 100;
    if (percent >= 100) {
        if (budgetWarningNode) {
            budgetWarningNode.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Budget exceeded: This expense would push you beyond your monthly limit.';
        }
    } else if (percent >= 80) {
        if (budgetWarningNode) {
            budgetWarningNode.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Warning: You are projected to use ' + percent.toFixed(1) + '% of your monthly budget.';
        }
    }
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
