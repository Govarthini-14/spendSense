<?php
require_once __DIR__ . '/includes/auth.php';
ensureAuthenticated();

$user = currentUser();
$search = trim($_GET['search'] ?? '');
$categoryFilter = $_GET['category'] ?? '';
$paymentFilter = $_GET['payment'] ?? '';
$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

$pdo = getPdo();
$where = ['user_id = :user_id'];
$params = ['user_id' => (int)$user['id']];

if ($search !== '') {
    $where[] = 'title LIKE :search';
    $params['search'] = '%' . $search . '%';
}
if ($categoryFilter !== '') {
    $where[] = 'category = :category';
    $params['category'] = $categoryFilter;
}
if ($paymentFilter !== '') {
    $where[] = 'payment_method = :payment_method';
    $params['payment_method'] = $paymentFilter;
}
if ($fromDate !== '') {
    $where[] = 'expense_date >= :from_date';
    $params['from_date'] = $fromDate;
}
if ($toDate !== '') {
    $where[] = 'expense_date <= :to_date';
    $params['to_date'] = $toDate;
}

$sortMap = [
    'newest' => 'expense_date DESC, created_at DESC',
    'oldest' => 'expense_date ASC, created_at ASC',
    'highest' => 'amount DESC',
    'lowest' => 'amount ASC',
];
$orderBy = $sortMap[$sort] ?? $sortMap['newest'];

$countSql = 'SELECT COUNT(*) as total FROM expenses WHERE ' . implode(' AND ', $where);
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRecords / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = 'SELECT * FROM expenses WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . $orderBy . ' LIMIT :limit OFFSET :offset';
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$transactions = $stmt->fetchAll();

$monthSummary = getMonthlyExpenseSummary((int)$user['id'], (int)date('n'), (int)date('Y'));
$totalAmount = (float)array_sum(array_map(fn($row) => (float)$row['amount'], $transactions));
$categories = $pdo->query("SELECT DISTINCT category FROM expenses WHERE user_id = " . (int)$user['id'] . " ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);
$paymentMethods = $pdo->query("SELECT DISTINCT payment_method FROM expenses WHERE user_id = " . (int)$user['id'] . " ORDER BY payment_method ASC")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Transactions';
require_once __DIR__ . '/includes/header.php';
?>
<div class="app-shell">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <main class="content">
        <header class="topbar">
            <div>
                <h1 class="page-title">Transaction history</h1>
                <p class="page-subtitle">Search, filter, and manage your expenses</p>
            </div>
            <a class="btn btn-primary" href="add-expense.php"><i class="fa-solid fa-plus"></i> Add Expense</a>
        </header>

        <div class="summary-row">
            <div class="summary-box">
                <div class="label">This month</div>
                <div class="value"><?= formatCurrency((float)($monthSummary['total_expenses'] ?? 0)) ?></div>
            </div>
            <div class="summary-box">
                <div class="label">Transactions</div>
                <div class="value"><?= (int)($monthSummary['total_transactions'] ?? 0) ?></div>
            </div>
            <div class="summary-box">
                <div class="label">All records</div>
                <div class="value"><?= formatCurrency((float)$totalAmount) ?></div>
            </div>
        </div>

        <div class="card form-card">
            <form method="get" class="filters">
                <div class="field filter-field">
                    <label for="search">Search title</label>
                    <input id="search" name="search" type="text" value="<?= e($search) ?>" placeholder="e.g. groceries">
                </div>
                <div class="field filter-field">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="">All categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= e($category) ?>" <?= $categoryFilter === $category ? 'selected' : '' ?>><?= e($category) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field filter-field">
                    <label for="payment">Payment method</label>
                    <select id="payment" name="payment">
                        <option value="">All methods</option>
                        <?php foreach ($paymentMethods as $method): ?>
                            <option value="<?= e($method) ?>" <?= $paymentFilter === $method ? 'selected' : '' ?>><?= e($method) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field filter-field">
                    <label for="from_date">From</label>
                    <input id="from_date" name="from_date" type="date" value="<?= e($fromDate) ?>">
                </div>
                <div class="field filter-field">
                    <label for="to_date">To</label>
                    <input id="to_date" name="to_date" type="date" value="<?= e($toDate) ?>">
                </div>
                <div class="field filter-field">
                    <label for="sort">Sort by</label>
                    <select id="sort" name="sort">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest</option>
                        <option value="highest" <?= $sort === 'highest' ? 'selected' : '' ?>>Highest amount</option>
                        <option value="lowest" <?= $sort === 'lowest' ? 'selected' : '' ?>>Lowest amount</option>
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">Apply</button>
            </form>
        </div>

        <div class="card" style="margin-top: 20px;">
            <?php if (empty($transactions)): ?>
                <div class="empty-box">No expenses match your current filter.</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Payment</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?= e($transaction['title']) ?></td>
                                    <td><span class="badge"><?= e($transaction['category']) ?></span></td>
                                    <td class="amount"><?= formatCurrency((float)$transaction['amount']) ?></td>
                                    <td><?= date('d M Y', strtotime($transaction['expense_date'])) ?></td>
                                    <td><?= e($transaction['payment_method']) ?></td>
                                    <td><?= e($transaction['notes'] ?: '—') ?></td>
                                    <td>
                                        <div class="action-group">
                                            <a class="action-btn" href="edit-expense.php?id=<?= (int)$transaction['id'] ?>">Edit</a>
                                            <form method="post" action="delete-expense.php" style="display:inline;">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="id" value="<?= (int)$transaction['id'] ?>">
                                                <button class="action-btn delete" type="submit" data-confirm-delete="yes">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                    <div>Showing <?= count($transactions) ?> of <?= (int)$totalRecords ?> records</div>
                    <div class="action-group">
                        <?php if ($page > 1): ?>
                            <a class="action-btn" href="transactions.php?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                        <?php endif; ?>
                        <?php if ($page < $totalPages): ?>
                            <a class="action-btn" href="transactions.php?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
