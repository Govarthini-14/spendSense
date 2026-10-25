<?php
require_once __DIR__ . '/includes/auth.php';
ensureAuthenticated();

$user = currentUser();
$pdo = getPdo();
$profile = getUserById($pdo, (int)$user['id']);
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid request. Please try again.';
    }

    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $currentPassword = $_POST['current_password'] ?? '';

    if ($name === '') {
        $errors[] = 'Name is required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($email !== $profile['email']) {
        $existing = getUserByEmail($pdo, $email);
        if ($existing && (int)$existing['id'] !== (int)$profile['id']) {
            $errors[] = 'This email is already in use by another account.';
        }
    }

    if ($newPassword !== '') {
        if (strlen($newPassword) < 6) {
            $errors[] = 'New password must be at least 6 characters long.';
        }
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New password and confirmation do not match.';
        }
        if (!password_verify($currentPassword, $profile['password'])) {
            $errors[] = 'Your current password is incorrect.';
        }
    }

    if (empty($errors)) {
        $sql = 'UPDATE users SET name = :name, email = :email, updated_at = NOW()';
        $params = ['name' => $name, 'email' => $email, 'id' => $profile['id']];
        if ($newPassword !== '') {
            $sql .= ', password = :password';
            $params['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE id = :id';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['email'] = $email;
        $success = 'Profile updated successfully.';
        $profile = getUserById($pdo, (int)$user['id']);
    }
}

$pageTitle = 'Profile';
require_once __DIR__ . '/includes/header.php';
?>
<div class="app-shell">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <main class="content">
        <header class="topbar">
            <div>
                <h1 class="page-title">Profile</h1>
                <p class="page-subtitle">Manage your account information</p>
            </div>
        </header>

        <div class="card form-card form-page">
            <?php if ($success !== ''): ?>
                <div class="status-badge status-good" style="margin-bottom: 18px;">
                    <i class="fa-solid fa-circle-check"></i> <?= e($success) ?>
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
                        <label for="name">Name</label>
                        <input id="name" name="name" type="text" value="<?= e($profile['name']) ?>" required>
                    </div>
                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="<?= e($profile['email']) ?>" required>
                    </div>
                    <div class="field full">
                        <label for="current_password">Current password</label>
                        <input id="current_password" name="current_password" type="password">
                    </div>
                    <div class="field">
                        <label for="new_password">New password</label>
                        <input id="new_password" name="new_password" type="password">
                    </div>
                    <div class="field">
                        <label for="confirm_password">Confirm password</label>
                        <input id="confirm_password" name="confirm_password" type="password">
                    </div>
                </div>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Update profile</button>
                </div>
            </form>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
