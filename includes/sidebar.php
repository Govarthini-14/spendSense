<aside class="sidebar">
    <div class="brand">
        <div class="brand-badge"><i class="fa-solid fa-wallet"></i></div>
        <div>
            <h2>SpendSense</h2>
        </div>
    </div>

    <nav class="nav-list">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" href="index.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'add-expense.php' ? 'active' : ''; ?>" href="add-expense.php"><i class="fa-solid fa-circle-plus"></i> Add Expense</a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'transactions.php' ? 'active' : ''; ?>" href="transactions.php"><i class="fa-solid fa-table-list"></i> Transactions</a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>" href="reports.php"><i class="fa-solid fa-chart-column"></i> Monthly Report</a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'budget.php' ? 'active' : ''; ?>" href="budget.php"><i class="fa-solid fa-piggy-bank"></i> Budget Planner</a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'savings-tips.php' ? 'active' : ''; ?>" href="savings-tips.php"><i class="fa-solid fa-lightbulb"></i> Savings Tips</a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>" href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
        <a class="nav-link" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
    </nav>
</aside>
