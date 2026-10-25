<?php
require_once __DIR__ . '/includes/auth.php';
ensureAuthenticated();

$user = currentUser();
$userId = (int)$user['id'];
$month = (int)date('n');
$year = (int)date('Y');

$summary = getMonthlyExpenseSummary($userId, $month, $year);
$totalSpent = (float)($summary['total_expenses'] ?? 0);
$monthlyBudget = getMonthlyBudget($userId, $month, $year);
$budgetLimit = (float)($monthlyBudget['monthly_limit'] ?? 0);
$usedPercent = $budgetLimit > 0 ? ($totalSpent / $budgetLimit) * 100 : 0;
$categories = getCategoryBreakdown($userId, $month, $year);
$topCategories = array_slice($categories, 0, 3);
$smallPurchases = 0;
$smallSpendingThreshold = 150;

$pdo = getPdo();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM expenses WHERE user_id = :user_id AND MONTH(expense_date) = :month AND YEAR(expense_date) = :year AND amount <= :threshold');
$stmt->execute(['user_id' => $userId, 'month' => $month, 'year' => $year, 'threshold' => $smallSpendingThreshold]);
$smallPurchases = (int)$stmt->fetchColumn();

$tips = [];
if (!empty($categories)) {
    $highestCategory = $categories[0];
    $tips[] = 'Food is your highest spending category this month. Try setting a weekly food budget.';
    $tips[] = 'Your top spending category is ' . $highestCategory['category'] . '. Consider reducing non-essential purchases in this area.';
    if ($smallPurchases > 0) {
        $tips[] = 'You have made several small purchases this month. Review recurring costs and remove avoidable spending.';
    }
    if ($usedPercent >= 80 && $usedPercent < 100) {
        $tips[] = 'You have already used ' . number_format($usedPercent, 1) . '% of your monthly budget. Reduce discretionary spending for the rest of the month.';
    }
    if ($usedPercent >= 100) {
        $tips[] = 'Your spending has exceeded the monthly budget. Consider cutting non-essential expenses to restore your balance.';
    }
    if ($usedPercent < 80) {
        $tips[] = 'You are staying within your budget. Consider setting aside part of your remaining monthly budget as savings.';
    }
}

if (empty($tips)) {
    $tips[] = 'You need a bit more spending data before personalized savings tips can be generated. Add a few expenses to receive recommendations.';
}

$pageTitle = 'Savings Tips';
require_once __DIR__ . '/includes/header.php';
?>
<div class="app-shell">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <main class="content">
        <header class="topbar">
            <div>
                <h1 class="page-title">Savings tips</h1>
                <p class="page-subtitle">Personalized guidance based on your spending behavior</p>
            </div>
        </header>

        <div class="grid panel-grid">
            <div class="card">
                <h3>Smart recommendations</h3>
                <ul>
                    <?php foreach ($tips as $tip): ?>
                        <li style="margin-bottom: 12px;"><?= e($tip) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="card">
                <h3>Monthly saving progress</h3>
                <div class="progress-block">
                    <div class="progress-label">
                        <span>Budget utilization</span>
                        <span><?= $budgetLimit > 0 ? number_format($usedPercent, 1) : 0 ?>%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar <?= $usedPercent >= 100 ? 'danger' : ($usedPercent >= 80 ? 'warning' : '') ?>" style="width: <?= $budgetLimit > 0 ? min($usedPercent, 100) : 0 ?>%;"></div>
                    </div>
                </div>
                <div style="margin-top: 18px;">
                    <strong>Spent:</strong> <?= formatCurrency($totalSpent) ?><br>
                    <strong>Budget:</strong> <?= formatCurrency($budgetLimit) ?><br>
                    <strong>Remaining:</strong> <?= formatCurrency($budgetLimit - $totalSpent) ?>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top: 20px;">
            <h3>Top spending categories</h3>
            <?php if (empty($topCategories)): ?>
                <div class="empty-box">Not enough spending data to suggest category insights yet.</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Spent</th>
                                <th>Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topCategories as $row): ?>
                                <tr>
                                    <td><?= e($row['category']) ?></td>
                                    <td><?= formatCurrency((float)$row['total_amount']) ?></td>
                                    <td><?= $totalSpent > 0 ? number_format(((float)$row['total_amount'] / $totalSpent) * 100, 1) : 0 ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
