<?php
require_once __DIR__ . '/includes/auth.php';
ensureAuthenticated();

$user = currentUser();
$selectedMonth = (int)($_GET['month'] ?? date('n'));
$selectedYear = (int)($_GET['year'] ?? date('Y'));

if ($selectedMonth < 1 || $selectedMonth > 12) {
    $selectedMonth = (int)date('n');
}

$summary = getMonthlyExpenseSummary((int)$user['id'], $selectedMonth, $selectedYear);
$monthTotal = (float)($summary['total_expenses'] ?? 0);
$transactionCount = (int)($summary['total_transactions'] ?? 0);
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);
$averageDaily = $daysInMonth > 0 ? $monthTotal / $daysInMonth : 0;

$categoryBreakdown = getCategoryBreakdown((int)$user['id'], $selectedMonth, $selectedYear);
$highestCategory = $categoryBreakdown[0] ?? ['category' => 'N/A', 'total_amount' => 0];
$labels = [];
$values = [];
foreach ($categoryBreakdown as $row) {
    $labels[] = $row['category'];
    $values[] = (float)$row['total_amount'];
}

$dailyMap = getDailySpendingData((int)$user['id'], $selectedMonth, $selectedYear);
$dailyLabels = [];
$dailyValues = [];
for ($day = 1; $day <= $daysInMonth; $day++) {
    $dailyLabels[] = (string)$day;
    $dailyValues[] = (float)($dailyMap[$day] ?? 0);
}

$comparison = [];
$startYear = $selectedYear;
$startMonth = $selectedMonth - 5;
for ($i = 5; $i >= 0; $i--) {
    $month = $selectedMonth - $i;
    $year = $selectedYear;
    while ($month <= 0) {
        $month += 12;
        $year--;
    }
    while ($month > 12) {
        $month -= 12;
        $year++;
    }
    $summaryRow = getMonthlyExpenseSummary((int)$user['id'], $month, $year);
    $comparison[] = [
        'label' => date('M Y', mktime(0, 0, 0, $month, 1, $year)),
        'amount' => (float)($summaryRow['total_expenses'] ?? 0),
    ];
}

$pageTitle = 'Monthly Report';
require_once __DIR__ . '/includes/header.php';
?>
<div class="app-shell">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <main class="content">
        <header class="topbar">
            <div>
                <h1 class="page-title">Monthly report</h1>
                <p class="page-subtitle">Detailed spending overview and trend analysis</p>
            </div>
        </header>

        <div class="card" style="margin-bottom: 20px;">
            <form method="get" class="filters">
                <div class="field filter-field">
                    <label for="month">Month</label>
                    <select id="month" name="month">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $selectedMonth === $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="field filter-field">
                    <label for="year">Year</label>
                    <select id="year" name="year">
                        <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                            <option value="<?= $y ?>" <?= $selectedYear === $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">View report</button>
            </form>
        </div>

        <div class="summary-row">
            <div class="summary-box">
                <div class="label">Monthly spending</div>
                <div class="value"><?= formatCurrency($monthTotal) ?></div>
            </div>
            <div class="summary-box">
                <div class="label">Average / day</div>
                <div class="value"><?= formatCurrency($averageDaily) ?></div>
            </div>
            <div class="summary-box">
                <div class="label">Transactions</div>
                <div class="value"><?= $transactionCount ?></div>
            </div>
        </div>

        <div class="grid panel-grid">
            <div class="card chart-card">
                <h3>Category-wise doughnut</h3>
                <div class="chart-wrap" style="min-height: 300px;">
                    <canvas id="categoryReportChart"></canvas>
                </div>
            </div>
            <div class="card chart-card">
                <h3>Daily spending</h3>
                <div class="chart-wrap" style="min-height: 300px;">
                    <canvas id="dailyReportChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid panel-grid" style="margin-top: 20px;">
            <div class="card">
                <h3>Insights</h3>
                <p><strong>Highest category:</strong> <?= e($highestCategory['category']) ?></p>
                <p><strong>Amount:</strong> <?= formatCurrency((float)$highestCategory['total_amount']) ?></p>
                <p><strong>Average daily spend:</strong> <?= formatCurrency($averageDaily) ?></p>
            </div>
            <div class="card">
                <h3>6-month comparison</h3>
                <div class="chart-wrap" style="min-height: 260px;">
                    <canvas id="comparisonChart"></canvas>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top: 20px;">
            <h3>Category breakdown</h3>
            <?php if (empty($categoryBreakdown)): ?>
                <div class="empty-box">No spending history for this month.</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categoryBreakdown as $row): ?>
                                <tr>
                                    <td><?= e($row['category']) ?></td>
                                    <td><?= formatCurrency((float)$row['total_amount']) ?></td>
                                    <td><?= $monthTotal > 0 ? number_format(((float)$row['total_amount'] / $monthTotal) * 100, 1) : 0 ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
new Chart(document.getElementById('categoryReportChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            data: <?= json_encode($values) ?>,
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

new Chart(document.getElementById('dailyReportChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($dailyLabels) ?>,
        datasets: [{
            label: 'Daily spend',
            data: <?= json_encode($dailyValues) ?>,
            backgroundColor: '#176B63',
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true } }
    }
});

new Chart(document.getElementById('comparisonChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($comparison, 'label')) ?>,
        datasets: [{
            label: 'Monthly spending',
            data: <?= json_encode(array_column($comparison, 'amount')) ?>,
            borderColor: '#176B63',
            backgroundColor: 'rgba(23, 107, 99, 0.1)',
            fill: true,
            tension: 0.35
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
