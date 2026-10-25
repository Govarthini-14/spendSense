<?php
function currentUser(): ?array
{
    if (!isset($_SESSION['user'])) {
        return null;
    }

    return $_SESSION['user'];
}

function requireLogin(): void
{
    if (!currentUser()) {
        $_SESSION['flash_error'] = 'Please log in to continue.';
        header('Location: login.php');
        exit;
    }
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function formatCurrency(float $amount): string
{
    return '₹' . number_format($amount, 2, '.', ',');
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

function getFlashMessages(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function renderFlashMessages(): void
{
    $flashes = getFlashMessages();
    foreach ($flashes as $type => $message) {
        echo '<div class="toast show ' . e($type) . '" role="alert">' . e($message) . '</div>';
    }
}

function getPdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        die('Database connection failed. Please check your XAMPP MySQL configuration.');
    }

    return $pdo;
}

function getMonthAndYear(?string $month = null, ?string $year = null): array
{
    $month = (int)($month ?? date('n'));
    $year = (int)($year ?? date('Y'));

    if ($month < 1 || $month > 12) {
        $month = (int)date('n');
    }

    return [$month, $year];
}

function getMonthlyExpenseSummary(int $userId, int $month, int $year): array
{
    $pdo = getPdo();

    $stmt = $pdo->prepare(
        'SELECT COALESCE(SUM(amount), 0) AS total_expenses, COUNT(*) AS total_transactions
         FROM expenses
         WHERE user_id = :user_id AND MONTH(expense_date) = :month AND YEAR(expense_date) = :year'
    );
    $stmt->execute(['user_id' => $userId, 'month' => $month, 'year' => $year]);

    return $stmt->fetch() ?: ['total_expenses' => 0, 'total_transactions' => 0];
}

function getMonthlyBudget(int $userId, int $month, int $year): ?array
{
    $pdo = getPdo();
    $stmt = $pdo->prepare(
        'SELECT * FROM budgets WHERE user_id = :user_id AND budget_month = :month AND budget_year = :year LIMIT 1'
    );
    $stmt->execute(['user_id' => $userId, 'month' => $month, 'year' => $year]);
    return $stmt->fetch() ?: null;
}

function getCategoryBreakdown(int $userId, int $month, int $year): array
{
    $pdo = getPdo();
    $stmt = $pdo->prepare(
        'SELECT category, SUM(amount) AS total_amount
         FROM expenses
         WHERE user_id = :user_id AND MONTH(expense_date) = :month AND YEAR(expense_date) = :year
         GROUP BY category
         ORDER BY total_amount DESC'
    );
    $stmt->execute(['user_id' => $userId, 'month' => $month, 'year' => $year]);

    return $stmt->fetchAll();
}

function getDailySpendingData(int $userId, int $month, int $year): array
{
    $pdo = getPdo();
    $stmt = $pdo->prepare(
        'SELECT DAY(expense_date) AS day_label, COALESCE(SUM(amount), 0) AS total_amount
         FROM expenses
         WHERE user_id = :user_id AND MONTH(expense_date) = :month AND YEAR(expense_date) = :year
         GROUP BY DAY(expense_date)
         ORDER BY day_label ASC'
    );
    $stmt->execute(['user_id' => $userId, 'month' => $month, 'year' => $year]);

    $data = [];
    foreach ($stmt->fetchAll() as $row) {
        $data[(int)$row['day_label']] = (float)$row['total_amount'];
    }

    return $data;
}

function getUserByEmail(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => strtolower(trim($email))]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function getUserById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function budgetStatus(float $usedPercent): string
{
    if ($usedPercent < 80) {
        return 'good';
    }

    if ($usedPercent < 100) {
        return 'warning';
    }

    return 'danger';
}

function getBudgetWarningMessage(float $spent, float $budget): string
{
    if ($budget <= 0) {
        return 'Set a monthly budget to start tracking your spending.';
    }

    $usedPercent = ($spent / $budget) * 100;

    if ($usedPercent < 80) {
        return 'Great! You are staying within your budget.';
    }

    if ($usedPercent < 100) {
        $remaining = $budget - $spent;
        return 'Careful! You have used ' . number_format($usedPercent, 1) . '% of your monthly budget. Only ' . formatCurrency($remaining) . ' remains.';
    }

    $exceededBy = $spent - $budget;
    return 'Budget exceeded by ' . formatCurrency($exceededBy) . '. Consider reducing non-essential expenses.';
}

function recentTransactions(int $userId, int $limit = 5): array
{
    $pdo = getPdo();
    $stmt = $pdo->prepare(
        'SELECT * FROM expenses
         WHERE user_id = :user_id
         ORDER BY expense_date DESC, created_at DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getCategoryBudgetStatus(int $userId, int $month, int $year): array
{
    $pdo = getPdo();
    $stmt = $pdo->prepare(
        'SELECT cb.category, cb.category_limit,
                COALESCE(SUM(e.amount), 0) AS spent,
                (cb.category_limit - COALESCE(SUM(e.amount), 0)) AS remaining
         FROM category_budgets cb
         LEFT JOIN expenses e ON e.user_id = cb.user_id
             AND e.category = cb.category
             AND MONTH(e.expense_date) = cb.budget_month
             AND YEAR(e.expense_date) = cb.budget_year
         WHERE cb.user_id = :user_id AND cb.budget_month = :month AND cb.budget_year = :year
         GROUP BY cb.category, cb.category_limit'
    );
    $stmt->execute(['user_id' => $userId, 'month' => $month, 'year' => $year]);
    return $stmt->fetchAll();
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}
