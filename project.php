<?php
require_once 'functions.php';
require_login();

$project_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$pdo = get_db();
$user_id = $_SESSION['user_id'];

if (!$project_id || !is_project_member($pdo, $project_id, $user_id)) {
    die('Brak dostępu lub projekt nie istnieje.');
}


$stmtProj = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmtProj->execute([$project_id]);
$project = $stmtProj->fetch();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $assigned_to = filter_input(INPUT_POST, 'assigned_to', FILTER_VALIDATE_INT) ?: null;
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $tag = trim($_POST['tag'] ?? 'Ogólne');

    if ($title !== '') {
        $stmtInsert = $pdo->prepare("
            INSERT INTO tasks (project_id, assigned_to, title, description, status, tag, due_date) 
            VALUES (?, ?, ?, ?, 'todo', ?, ?)
        ");
        $stmtInsert->execute([$project_id, $assigned_to, $title, $description, $tag, $due_date]);

        $stmtLog = $pdo->prepare("INSERT INTO activity_logs (project_id, user_id, action_text) VALUES (?, ?, ?)");
        $stmtLog->execute([$project_id, $user_id, "utworzył(a) zadanie „{$title}”"]);

        header("Location: project.php?id={$project_id}");
        exit;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_task'])) {
    $del_task_id = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
    if ($del_task_id) {
        $stmtCheck = $pdo->prepare("SELECT title FROM tasks WHERE id = ? AND project_id = ?");
        $stmtCheck->execute([$del_task_id, $project_id]);
        $task_to_delete = $stmtCheck->fetch();

        if ($task_to_delete) {
            $stmtDel = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND project_id = ?");
            $stmtDel->execute([$del_task_id, $project_id]);

            $stmtLog = $pdo->prepare("INSERT INTO activity_logs (project_id, user_id, action_text) VALUES (?, ?, ?)");
            $stmtLog->execute([$project_id, $user_id, "usunął/ęła zadanie „{$task_to_delete['title']}”"]);
        }

        header("Location: project.php?id={$project_id}");
        exit;
    }
}


$stmtMembers = $pdo->prepare("
    SELECT u.id, u.username 
    FROM users u 
    JOIN project_members pm ON u.id = pm.user_id 
    WHERE pm.project_id = ?
");
$stmtMembers->execute([$project_id]);
$members = $stmtMembers->fetchAll();


$stmtTasks = $pdo->prepare("
    SELECT t.*, u.username AS assignee_name 
    FROM tasks t 
    LEFT JOIN users u ON t.assigned_to = u.id 
    WHERE t.project_id = ? 
    ORDER BY t.due_date IS NULL ASC, t.due_date ASC
");
$stmtTasks->execute([$project_id]);
$all_tasks = $stmtTasks->fetchAll();

$columns = [
    'todo' => ['title' => 'Do zrobienia', 'tasks' => []],
    'in_progress' => ['title' => 'W trakcie', 'tasks' => []],
    'done' => ['title' => 'Gotowe', 'tasks' => []],
];
foreach ($all_tasks as $t) {
    $columns[$t['status']]['tasks'][] = $t;
}

// Fetch activity history
$stmtLogs = $pdo->prepare("
    SELECT a.action_text, a.created_at, u.username 
    FROM activity_logs a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.project_id = ? 
    ORDER BY a.created_at DESC 
    LIMIT 15
");
$stmtLogs->execute([$project_id]);
$logs = $stmtLogs->fetchAll();

$tag_colors = [
    'Bug' => 'danger',
    'Feature' => 'primary',
    'Pilne' => 'warning text-dark',
    'Ogólne' => 'info text-dark'
];

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-0"><?= e($project['name']) ?></h3>
        <small class="text-muted"><?= e($project['description']) ?></small>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTaskModal">+ Nowe zadanie</button>
</div>

<!-- Kanban Board (3 Columns) -->
<div class="row g-3 mb-5" id="kanban-board">
    <?php foreach ($columns as $status_key => $col): ?>
        <div class="col-md-4">
            <div class="card bg-white shadow-sm">
                <div class="card-header fw-bold"><?= e($col['title']) ?></div>
                <div class="card-body kanban-column" data-status="<?= $status_key ?>" style="min-height: 400px;">
                    <?php foreach ($col['tasks'] as $task): ?>
                        <?php $badgeColor = $tag_colors[$task['tag']] ?? 'secondary'; ?>
                        <div class="card mb-2 border task-card" data-task-id="<?= $task['id'] ?>">
                            <div class="card-body p-2">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <span class="badge bg-<?= $badgeColor ?>"><?= e($task['tag']) ?></span>
                                    <form method="POST" onsubmit="return confirm('Usunąć to zadanie?');" class="m-0">
                                        <input type="hidden" name="delete_task" value="1">
                                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0 text-decoration-none fw-bold" title="Usuń zadanie">×</button>
                                    </form>
                                </div>
                                <h6 class="card-title mb-1"><?= e($task['title']) ?></h6>
                                <p class="card-text small text-muted mb-2"><?= e($task['description']) ?></p>
                                <div class="d-flex justify-content-between align-items-center small">
                                    <span> <?= e($task['assignee_name'] ?? 'Brak') ?></span>
                                    <span> <?= e($task['due_date'] ?? '-') ?></span>
                                </div>
                                <div class="mt-2 d-flex justify-content-between">
                                    <button class="btn btn-sm btn-outline-secondary move-btn" data-dir="-1">←</button>
                                    <button class="btn btn-sm btn-outline-secondary move-btn" data-dir="1">→</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>


<div class="card shadow-sm mb-4">
    <div class="card-header fw-bold">Historia aktywności w projekcie</div>
    <ul class="list-group list-group-flush small">
        <?php if (empty($logs)): ?>
            <li class="list-group-item text-muted">Brak zapisanej aktywności.</li>
        <?php else: ?>
            <?php foreach ($logs as $log): ?>
                <li class="list-group-item">
                    <strong><?= e($log['username']) ?></strong> <?= e($log['action_text']) ?>
                    <span class="text-muted float-end"><?= e($log['created_at']) ?></span>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</div>


<div class="modal fade" id="newTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="add_task" value="1">
            <div class="modal-header">
                <h5 class="modal-title">Dodaj nowe zadanie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Tytuł *</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Opis</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Osoba odpowiedzialna</label>
                        <select name="assigned_to" class="form-select">
                            <option value="">-- Brak --</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= e($m['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Termin wykonania</label>
                        <input type="date" name="due_date" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Etykieta / Tag</label>
                    <select name="tag" class="form-select">
                        <option value="Ogólne">Ogólne</option>
                        <option value="Feature">Feature</option>
                        <option value="Bug">Bug</option>
                        <option value="Pilne">Pilne</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                <button type="submit" class="btn btn-primary">Zapisz zadanie</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
