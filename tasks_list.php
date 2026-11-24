<?php
require_once 'functions.php';
require_login();

$pdo = get_db();
$user_id = $_SESSION['user_id'];

$limit = 10;
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$offset = ($page - 1) * $limit;

$sort = ($_GET['sort'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
$order_sql = ($sort === 'desc') ? 'DESC' : 'ASC';


$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM tasks t
    JOIN project_members pm ON t.project_id = pm.project_id
    WHERE pm.user_id = ?
");
$countStmt->execute([$user_id]);
$total_tasks = (int) $countStmt->fetchColumn();
$total_pages = max(1, (int) ceil($total_tasks / $limit));


$stmt = $pdo->prepare("
    SELECT t.*, p.name AS project_name, u.username AS assignee_name
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    JOIN project_members pm ON p.id = pm.project_id
    LEFT JOIN users u ON t.assigned_to = u.id
    WHERE pm.user_id = :user_id
    ORDER BY t.due_date IS NULL ASC, t.due_date $order_sql
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$tasks = $stmt->fetchAll();

$status_labels = [
    'todo' => ['Do zrobienia', 'secondary'],
    'in_progress' => ['W trakcie', 'warning text-dark'],
    'done' => ['Gotowe', 'success']
];

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Wszystkie Twoje zadania (<?= $total_tasks ?>)</h4>
    <div>
        <span class="me-2 small text-muted">Sortuj po terminie:</span>
        <a href="?page=1&sort=asc" class="btn btn-sm <?= $sort === 'asc' ? 'btn-dark' : 'btn-outline-dark' ?>">Najbliższe ↑</a>
        <a href="?page=1&sort=desc" class="btn btn-sm <?= $sort === 'desc' ? 'btn-dark' : 'btn-outline-dark' ?>">Najdalsze ↓</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Zadanie</th>
                    <th>Projekt</th>
                    <th>Etykieta</th>
                    <th>Status</th>
                    <th>Przypisany</th>
                    <th>Termin</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tasks)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">Brak zadań do wyświetlenia.</td></tr>
                <?php else: ?>
                    <?php foreach ($tasks as $t): ?>
                        <?php [$label, $badgeClass] = $status_labels[$t['status']] ?? ['Nieznany', 'secondary']; ?>
                        <tr>
                            <td>
                                <a href="project.php?id=<?= $t['project_id'] ?>" class="text-decoration-none fw-semibold">
                                    <?= e($t['title']) ?>
                                </a>
                            </td>
                            <td><?= e($t['project_name']) ?></td>
                            <td><span class="badge bg-info text-dark"><?= e($t['tag']) ?></span></td>
                            <td><span class="badge bg-<?= $badgeClass ?>"><?= $label ?></span></td>
                            <td><?= e($t['assignee_name'] ?? 'Brak') ?></td>
                            <td><?= e($t['due_date'] ?? 'Brak terminu') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<?php if ($total_pages > 1): ?>
<nav class="mt-4">
    <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&sort=<?= $sort ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
