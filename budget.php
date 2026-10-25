<?php
require_once __DIR__ . '/includes/auth.php';
ensureAuthenticated();

$user = currentUser();
$selectedMonth = (int)($_GET['month'] ?? date('n'));
$selectedYear = (int)($_GET['year'] ?? date('Y'));
$categories = ['Food', 'Travel', 'Education', 'Shopping', 'Entertainment', 'Accommodation', 'Health', 'Bills and Recharge', 'Other'];
$budgetMessage = '';
$userId = (int)$user['id'];
$monthlyBudget = getMonthlyBudget($userId, $selectedMonth, $selectedYear);
$categoryBudgets = getCategoryBudgetStatus($userId, $selectedMonth, $selectedYear);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        setFlash('error', 'Invalid request. Please try again.');
        header('Location: budget.php');
        exit;
    }

    $selectedMonth = (int)($_POST['month'] ?? date('n'));
    $selectedYear = (int)($_POST['year'] ?? date('Y'));
    $monthlyLimit = (float)($_POST['monthly_limit'] ?? 0);

    if ($monthlyLimit <= 0) {
        setFlash('error', 'Monthly budget must be greater than zero.');
        header('Location: budget.php?month=' . $selectedMonth . '&year=' . $selectedYear);
        exit;
    }

    $pdo = getPdo();
    $existingBudget = getMonthlyBudget($userId, $selectedMonth, $selectedYear);
    if ($existingBudget) {
        $stmt = $pdo->prepare('UPDATE budgets SET monthly_limit = :limit, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['limit' => $monthlyLimit, 'id' => $existingBudget['id']]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO budgets (user_id, budget_month, budget_year, monthly_limit) VALUES (:user_id, :month, :year, :limit)');
        $stmt->execute(['user_id' => $userId, 'month' => $selectedMonth, 'year' => $selectedYear, 'limit' => $monthlyLimit]);
    }

    $categoryLimits = $_POST['category_limit'] ?? [];
    if (!empty($categoryLimits)) {
        foreach ($categoryLimits as $category => $limit) {
            $category = trim((string)$category);
            $limitValue = (float)$limit;
            if ($category !== '' && $limitValue > 0) {
                $existing = $pdo->prepare('SELECT * FROM category_budgets WHERE user_id = :user_id AND budget_month = :month AND budget_year = :year AND category = :category LIMIT 1');
                $existing->execute(['user_id' => $userId, 'month' => $selectedMonth, 'year' => $selectedYear, 'category' => $category]);
                $row = $existing->fetch();
                if ($row) {
                    $pdo->prepare('UPDATE category_budgets SET category_limit = :limit, updated_at = NOW() WHERE id = :id')->execute(['limit' => $limitValue, 'id' => $row['id']]);
                } else {
                    $pdo->prepare('INSERT INTO category_budgets (user_id, budget_month, budget_year, category, category_limit) VALUES (:user_id, :month, :year, :category, :limit)')->execute([
                        'user_id' => $userId,
                        'month' => $selectedMonth,
                        'year' => $selectedYear,
                        'category' => $category,
                        'limit' => $limitValue,
                    ]);
                }
            }
        }
    }

    setFlash('success', 'Budget saved successfully.');
    header('Location: budget.php?month=' . $selectedMonth . '&year=' . $selectedYear);
    exit;
}

$monthlySpending = (float)(getMonthlyExpenseSummary($userId, $selectedMonth, $selectedYear)['total_expenses'] ?? 0);
$progressValue = !empty($monthlyBudget['monthly_limit']) ? min((($monthlySpending / (float)$monthlyBudget['monthly_limit']) * 100), 100) : 0;
$budgetStatus = $monthlyBudget && $monthlyBudget['monthly_limit'] > 0 ? budgetStatus(($monthlySpending / (float)$monthlyBudget['monthly_limit']) * 100) : 'good';
$budgetMessage = $monthlyBudget && $monthlyBudget['monthly_limit'] > 0 ? getBudgetWarningMessage($monthlySpending, (float)$monthlyBudget['monthly_limit']) : 'Set your monthly budget first.';

$pageTitle = 'Budget Planner';
require_once __DIR__ . '/includes/header.php';
?>
<div class="app-shell">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <main class="content">
        <header class="topbar">
            <div>
                <h1 class="page-title">Budget planner</h1>
                <p class="page-subtitle">Set and monitor your monthly spending plan</p>
            </div>
        </header>

        <div class="card" style="margin-bottom:20px;">
            <div class="status-badge status-<?= $budgetStatus ?>">
                <?php if ($budgetStatus === 'good'): ?>
                    <i class="fa-solid fa-circle-check"></i>
                <?php elseif ($budgetStatus === 'warning'): ?>
                    <i class="fa-solid fa-triangle-exclamation"></i>
                <?php else: ?>
                    <i class="fa-solid fa-circle-xmark"></i>
                <?php endif; ?>
                <?= e($budgetMessage) ?>
            </div>
        </div>

        <div class="grid panel-grid">
            <div class="card">
                <h3>Monthly budget</h3>
                <form method="post">
                    <?= csrfField() ?>
                    <div class="form-grid">
                        <div class="field">
                            <label for="month">Month</label>
                            <select id="month" name="month">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" <?= $selectedMonth === $m ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="year">Year</label>
                            <select id="year" name="year">
                                <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                                    <option value="<?= $y ?>" <?= $selectedYear === $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="field full">
                            <label for="monthly_limit">Monthly budget</label>
                            <input id="monthly_limit" name="monthly_limit" type="number" step="0.01" min="0.01" value="<?= e((string)($monthlyBudget['monthly_limit'] ?? 0)) ?>" placeholder="5000">
                        </div>
                    </div>
                    <div class="progress-block">
                        <div class="progress-label">
                            <span>Budget used</span>
                            <span><?= $monthlyBudget && $monthlyBudget['monthly_limit'] > 0 ? number_format(($monthlySpending / (float)$monthlyBudget['monthly_limit']) * 100, 1) : 0 ?>%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar <?= $budgetStatus === 'warning' ? 'warning' : ($budgetStatus === 'danger' ? 'danger' : '') ?>" style="width: <?= $monthlyBudget && $monthlyBudget['monthly_limit'] > 0 ? min((($monthlySpending / (float)$monthlyBudget['monthly_limit']) * 100), 100) : 0 ?>%;"></div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit">Save budget</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <h3>Category budgets</h3>
                <form method="post">
                    <?= csrfField() ?>
                    <input type="hidden" name="month" value="<?= $selectedMonth ?>">
                    <input type="hidden" name="year" value="<?= $selectedYear ?>">
                    <?php foreach ($categories as $category): ?>
                        <?php $categoryEntry = null; foreach ($categoryBudgets as $row) { if ($row['category'] === $category) { $categoryEntry = $row; break; } } ?>
                        <div class="field" style="margin-bottom: 14px;">
                            <label for="category_limit_<?= e(strtolower(str_replace(' ', '_', $category))) ?>"><?= e($category) ?></label>
                            <input id="category_limit_<?= e(strtolower(str_replace(' ', '_', $category))) ?>" name="category_limit[<?= e($category) ?>]" type="number" step="0.01" min="0" value="<?= e((string)($categoryEntry['category_limit'] ?? 0)) ?>">
                        </div>
                    <?php endforeach; ?>
                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit">Update category budgets</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card" style="margin-top:20px;">
            <h3>Budget summary</h3>
            <?php if (empty($categoryBudgets)): ?>
                <div class="empty-box">No category budgets yet. Add limits to track spending by category.</div>
            <?php else: ?>
                <?php foreach ($categoryBudgets as $row): ?>
                    <?php $spent = (float)$row['spent']; $limit = (float)$row['category_limit']; $remaining = $limit - $spent; $percent = $limit > 0 ? ($spent / $limit) * 100 : 0; $status = $percent >= 100 ? 'danger' : ($percent >= 80 ? 'warning' : 'good'); ?>
                    <div class="progress-block" style="margin-bottom: 18px;">
                        <div class="progress-label">
                            <span><?= e($row['category']) ?></span>
                            <span><?= formatCurrency($spent) ?> / <?= formatCurrency($limit) ?></span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar <?= $status === 'warning' ? 'warning' : ($status === 'danger' ? 'danger' : '') ?>" style="width: <?= min($percent, 100) ?>%;"></div>
                        </div>
                        <div class="progress-label" style="margin-top:8px;">
                            <span>Remaining: <?= formatCurrency($remaining) ?></span>
                            <span><?= number_format($percent, 1) ?>%</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
