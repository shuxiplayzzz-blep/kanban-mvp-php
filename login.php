<?php
require_once 'functions.php';
if (is_logged_in()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Nieprawidłowy login lub hasło.';
    }
}
require_once 'includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="card-title mb-3">Logowanie</h4>
                <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Login</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hasło</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Zaloguj się</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
