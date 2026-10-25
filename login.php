<?php
require_once __DIR__ . '/includes/auth.php';

if (currentUser()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Login';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid form request. Please try again.';
    }

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $remember = !empty($_POST['remember_me']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        $pdo = getPdo();
        $user = getUserByEmail($pdo, $email);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
            ];
            $_SESSION['user_id'] = (int)$user['id'];

            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_me', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                $stmt = $pdo->prepare('UPDATE users SET remember_token = :token WHERE id = :id');
                $stmt->execute(['token' => $token, 'id' => $user['id']]);
            }

            $_SESSION['flash']['success'] = 'Welcome back, ' . e($user['name']) . '!';
            header('Location: index.php');
            exit;
        }

        $errors[] = 'Invalid email or password.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<main class="login-page">
    <div class="login-shell">
        <section class="login-showcase" aria-label="SpendSense introduction">
            <a class="login-brand" href="login.php" aria-label="SpendSense home">
                <span class="login-brand-icon"><i class="fa-solid fa-wallet" aria-hidden="true"></i></span>
                <span>Spend<span>Sense</span></span>
            </a>

            <div class="showcase-copy">
                <span class="showcase-eyebrow"><i class="fa-solid fa-sparkles" aria-hidden="true"></i> YOUR MONEY, IN FOCUS</span>
                <h2>Make every<br>rupee <span>count.</span></h2>
                <p>A clearer picture of your spending starts here. Stay on top of your goals and feel good about your finances.</p>
            </div>

            <div class="showcase-card" aria-hidden="true">
                <div class="showcase-card-heading">
                    <span class="showcase-card-icon"><i class="fa-solid fa-arrow-trend-up"></i></span>
                    <span class="showcase-card-label">SAVINGS GROWTH</span>
                    <span class="showcase-card-menu"><i class="fa-solid fa-ellipsis"></i></span>
                </div>
                <div class="showcase-card-value">Looking good <span>↗</span></div>
                <div class="showcase-chart">
                    <span style="--bar-height: 35%"></span>
                    <span style="--bar-height: 52%"></span>
                    <span style="--bar-height: 43%"></span>
                    <span style="--bar-height: 68%"></span>
                    <span style="--bar-height: 57%"></span>
                    <span style="--bar-height: 82%"></span>
                    <span style="--bar-height: 100%"></span>
                </div>
                <div class="showcase-card-footer"><span>WEEKLY OVERVIEW</span><span>ON THE RIGHT TRACK</span></div>
            </div>

            <p class="showcase-note"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Your financial details stay private and secure.</p>
        </section>

        <section class="login-form-panel" aria-labelledby="login-title">
            <div class="login-form-content">
                <div class="login-mobile-brand">
                    <span class="login-brand-icon"><i class="fa-solid fa-wallet" aria-hidden="true"></i></span>
                    <span>Spend<span>Sense</span></span>
                </div>
                <span class="login-kicker">WELCOME BACK</span>
                <h1 id="login-title">Sign in to your account</h1>
                <p class="login-intro">Pick up where you left off.</p>

                <form method="post" data-validate="true" class="login-form">
                    <?= csrfField() ?>
                    <div class="field">
                        <label for="email">Email address</label>
                        <div class="login-input-wrap">
                            <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                            <input id="email" name="email" type="email" placeholder="you@example.com" autocomplete="email" value="<?= e($email ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <div class="login-input-wrap">
                            <i class="fa-solid fa-lock" aria-hidden="true"></i>
                            <input id="password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required>
                        </div>
                    </div>
                    <label class="remember-option">
                        <input type="checkbox" name="remember_me" value="1" <?= !empty($_POST['remember_me']) ? 'checked' : '' ?>>
                        <span class="remember-check" aria-hidden="true"></span>
                        <span>Keep me signed in</span>
                    </label>

                    <?php if (!empty($errors)): ?>
                        <div class="login-errors" role="alert">
                            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                            <div>
                                <?php foreach ($errors as $error): ?>
                                    <div><?= e($error) ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <button class="login-submit" type="submit">Sign in <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
                </form>

                <p class="login-register">New to SpendSense? <a href="register.php">Create an account <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></p>
            </div>
            <p class="login-copyright">© <?= date('Y') ?> SpendSense <span>·</span> A little more clarity, every day.</p>
        </section>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
