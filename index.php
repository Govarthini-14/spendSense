<?php
require_once __DIR__ . '/includes/auth.php';
ensureAuthenticated();

$user = currentUser();
$month = (int)date('n');
$year = (int)date('Y');
$summary = getMonthlyExpenseSummary((int)$user['id'], $month, $year);
$budget = getMonthlyBudget((int)$user['id'], $month, $year);
$monthlyBudget = (float)($budget['monthly_limit'] ?? 0);
$totalSpent = (float)($summary['total_expenses'] ?? 0);
$remaining = $monthlyBudget - $totalSpent;
$usedPercent = $monthlyBudget > 0 ? ($totalSpent / $monthlyBudget) * 100 : 0;
$budgetStatus = budgetStatus($usedPercent);
$budgetMessage = getBudgetWarningMessage($totalSpent, $monthlyBudget);

$categoryBreakdown = getCategoryBreakdown((int)$user['id'], $month, $year);
$highestCategory = null;
if (!empty($categoryBreakdown)) {
    $highestCategory = $categoryBreakdown[0];
}

$recent = recentTransactions((int)$user['id'], 5);
$dailyData = getDailySpendingData((int)$user['id'], $month, $year);
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$dailyLabels = [];
$dailyValues = [];
for ($day = 1; $day <= $daysInMonth; $day++) {
    $dailyLabels[] = (string)$day;
    $dailyValues[] = (float)($dailyData[$day] ?? 0);
}

$categoryLabels = [];
$categoryValues = [];
foreach ($categoryBreakdown as $row) {
    $categoryLabels[] = $row['category'];
    $categoryValues[] = (float)$row['total_amount'];
}

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>
<div class="app-shell">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <main class="content">
        <header class="topbar">
            <div>
                <h1 class="page-title">Dashboard</h1>
                <p class="page-subtitle">Overview of your monthly spending</p>
            </div>
            <div class="topbar-actions">
                <div class="user-pill">
                    <div class="avatar"><?= htmlspecialchars(substr($user['name'], 0, 1), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div>
                        <strong><?= e($user['name']) ?></strong><br>
                        <small><?= e($user['email']) ?></small>
                    </div>
                </div>
                <a class="btn btn-primary" href="add-expense.php"><i class="fa-solid fa-plus"></i> Add Expense</a>
            </div>
        </header>

        <div class="card" style="margin-bottom: 24px;">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                <div>
                    <div class="label">Budget Status</div>
                    <div class="status-badge status-<?= $budgetStatus ?>">
                        <?php if ($budgetStatus === 'good'): ?>
                            <i class="fa-solid fa-circle-check"></i>
                        <?php elseif ($budgetStatus === 'warning'): ?>
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        <?php else: ?>
                            <i class="fa-solid fa-circle-xmark"></i>
                        <?php endif; ?>
                        <?= $budgetStatus === 'good' ? 'Under control' : ($budgetStatus === 'warning' ? 'Warning' : 'Exceeded'); ?>
                    </div>
                </div>
                <div style="text-align:right;">
                    <strong><?= e($budgetMessage) ?></strong>
                </div>
            </div>
        </div>

        <section class="grid stats-grid">
            <div class="card stat-card">
                <div class="label">Total Spent</div>
                <p class="value"><?= formatCurrency($totalSpent) ?></p>
                <div class="delta">This month</div>
            </div>
            <div class="card stat-card">
                <div class="label">Monthly Budget</div>
                <p class="value"><?= formatCurrency($monthlyBudget) ?></p>
                <div class="delta">Plan limit</div>
            </div>
            <div class="card stat-card">
                <div class="label">Remaining</div>
                <p class="value"><?= formatCurrency($remaining) ?></p>
                <div class="delta">Available</div>
            </div>
            <div class="card stat-card">
                <div class="label">Budget Used</div>
                <p class="value"><?= number_format($usedPercent, 1) ?>%</p>
                <div class="delta"><?= $monthlyBudget > 0 ? 'of target' : 'No budget set' ?></div>
            </div>
            <div class="card stat-card">
                <div class="label">Transactions</div>
                <p class="value"><?= (int)($summary['total_transactions'] ?? 0) ?></p>
                <div class="delta">This month</div>
            </div>
        </section>

        <section class="grid panel-grid">
            <div class="card chart-card">
                <h3>Daily spending</h3>
                <div class="chart-wrap">
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>
            <div class="card chart-card">
                <h3>Category-wise spending</h3>
                <div class="chart-wrap">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </section>

        <section class="grid panel-grid" style="margin-top: 20px;">
            <div class="card">
                <h3>Recent transactions</h3>
                <?php if (empty($recent)): ?>
                    <div class="empty-box">No transactions yet. Add your first expense to begin tracking.</div>
                <?php else: ?>
                    <div class="list">
                        <?php foreach ($recent as $row): ?>
                            <div class="transaction-item">
                                <div class="transaction-main">
                                    <div class="transaction-icon"><i class="fa-solid fa-wallet"></i></div>
                                    <div class="meta">
                                        <h4><?= e($row['title']) ?></h4>
                                        <p><?= e($row['category']) ?> • <?= date('d M Y', strtotime($row['expense_date'])) ?> • <?= e($row['payment_method']) ?></p>
                                    </div>
                                </div>
                                <div class="amount"><?= formatCurrency((float)$row['amount']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card">
                <h3>Highest spending category</h3>
                <?php if ($highestCategory): ?>
                    <div style="font-size: 1.8rem; font-weight: 700; margin: 10px 0;">
                        <?= e($highestCategory['category']) ?>
                    </div>
                    <div class="amount"><?= formatCurrency((float)$highestCategory['total_amount']) ?></div>
                    <div class="progress-block">
                        <div class="progress-label">
                            <span>Monthly share</span>
                            <span><?= $totalSpent > 0 ? number_format(((float)$highestCategory['total_amount'] / $totalSpent) * 100, 1) : 0 ?>%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar" style="width: <?= $totalSpent > 0 ? (min(((float)$highestCategory['total_amount'] / $totalSpent) * 100, 100)) : 0 ?>%;"></div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-box">No expense data available.</div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>

<script>
const dailyCtx = document.getElementById('dailyChart');
new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($dailyLabels) ?>,
        datasets: [{
            label: 'Daily spending',
            data: <?= json_encode($dailyValues) ?>,
            borderColor: '#176B63',
            backgroundColor: 'rgba(23, 107, 99, 0.12)',
            borderWidth: 3,
            tension: 0.35,
            fill: true,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});

const categoryCtx = document.getElementById('categoryChart');
new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($categoryLabels) ?>,
        datasets: [{
            data: <?= json_encode($categoryValues) ?>,
            backgroundColor: ['#176B63', '#2a8a76', '#F4C95D', '#7fc8b9', '#d9f2e8', '#e8938d', '#c4d9d7', '#84b7d5', '#FFB0B0'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
