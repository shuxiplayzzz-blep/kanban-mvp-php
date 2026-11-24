<?php
require_once 'functions.php';
require_login();

$pdo = get_db();
$user_id = $_SESSION['user_id'];
$message = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_project'])) {
    $del_id = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
    if ($del_id) {
        $stmtDel = $pdo->prepare("DELETE FROM projects WHERE id = ? AND created_by = ?");
        $stmtDel->execute([$del_id, $user_id]);
        $message = 'Projekt został usunięty.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_project'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $members = $_POST['members'] ?? []; 

    if ($name !== '') {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO projects (name, description, created_by) VALUES (?, ?, ?)");
            $stmt->execute([$name, $description, $user_id]);
            $project_id = (int) $pdo->lastInsertId();

            $stmtMember = $pdo->prepare("INSERT IGNORE INTO project_members (project_id, user_id) VALUES (?, ?)");
            $stmtMember->execute([$project_id, $user_id]);

            foreach ($members as $member_id) {
                $m_id = filter_var($member_id, FILTER_VALIDATE_INT);
                if ($m_id) {
                    $stmtMember->execute([$project_id, $m_id]);
                }
            }

            $stmtLog = $pdo->prepare("INSERT INTO activity_logs (project_id, user_id, action_text) VALUES (?, ?, ?)");
            $stmtLog->execute([$project_id, $user_id, "utworzył(a) projekt „{$name}”"]);

            $pdo->commit();
            $message = 'Projekt został utworzony!';
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Wystąpił błąd podczas tworzenia projektu.';
        }
    }
}

$stmtUsers = $pdo->prepare("SELECT id, username FROM users WHERE id != ? ORDER BY username ASC");
$stmtUsers->execute([$user_id]);
$other_users = $stmtUsers->fetchAll();

$stmtProjects = $pdo->prepare("
    SELECT p.*, u.username AS creator_name 
    FROM projects p
    JOIN project_members pm ON p.id = pm.project_id
    JOIN users u ON p.created_by = u.id
    WHERE pm.user_id = ?
    ORDER BY p.created_at DESC
");
$stmtProjects->execute([$user_id]);
$projects = $stmtProjects->fetchAll();

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">Nowy projekt</h5>
                <?php if ($message): ?>
                    <div class="alert alert-info py-2"><?= e($message) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="create_project" value="1">
                    <div class="mb-3">
                        <label class="form-label">Nazwa projektu *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Opis</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dodaj członków zespołu</label>
                        <select name="members[]" class="form-select" multiple size="3">
                            <?php foreach ($other_users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= e($u['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Przytrzymaj Ctrl (lub Cmd), aby zaznaczyć wiele osób.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Utwórz projekt</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <h4 class="mb-3">Twoje projekty</h4>
        <?php if (empty($projects)): ?>
            <div class="alert alert-secondary">Nie należysz jeszcze do żadnego projektu. Utwórz pierwszy po lewej!</div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($projects as $proj): ?>
                    <div class="col-md-6">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= e($proj['name']) ?></h5>
                                <p class="card-text text-muted small flex-grow-1"><?= e($proj['description'] ?: 'Brak opisu') ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                    <small class="text-muted">Autor: <?= e($proj['creator_name']) ?></small>
                                    <div class="d-flex gap-2">
                                        <?php if ((int)$proj['created_by'] === (int)$user_id): ?>
                                            <form method="POST" onsubmit="return confirm('Na pewno usunąć ten projekt i wszystkie jego zadania?');">
                                                <input type="hidden" name="delete_project" value="1">
                                                <input type="hidden" name="project_id" value="<?= $proj['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Usuń</button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="project.php?id=<?= $proj['id'] ?>" class="btn btn-sm btn-dark">Otwórz tablicę →</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
