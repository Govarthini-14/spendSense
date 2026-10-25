<?php
require_once __DIR__ . '/includes/auth.php';

if (currentUser()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Register';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid form request. Please try again.';
    }

    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '') {
        $errors[] = 'Name is required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $pdo = getPdo();
        $existing = getUserByEmail($pdo, $email);
        if ($existing) {
            $errors[] = 'An account already exists with this email.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (:name, :email, :password)');
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'password' => $hash,
            ]);
            $_SESSION['flash']['success'] = 'Registration successful. Please log in.';
            header('Location: login.php');
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="container form-page" style="padding-top: 50px;">
    <div class="card form-card">
        <h1 style="margin-top:0;">Create account</h1>
        <form method="post" data-validate="true">
            <?= csrfField() ?>
            <div class="form-grid">
                <div class="field full">
                    <label for="name">Full Name</label>
                    <input id="name" name="name" type="text" value="<?= e($name ?? '') ?>" required>
                </div>
                <div class="field full">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="<?= e($email ?? '') ?>" required>
                </div>
                <div class="field full">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required>
                </div>
                <div class="field full">
                    <label for="confirm_password">Confirm Password</label>
                    <input id="confirm_password" name="confirm_password" type="password" required>
                </div>
            </div>
            <?php if (!empty($errors)): ?>
                <div class="error-text" style="margin-top: 16px;">
                    <?php foreach ($errors as $error): ?>
                        <div><?= e($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="form-actions">
                <a class="btn btn-outline" href="login.php">Back to Login</a>
                <button class="btn btn-primary" type="submit">Register</button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
