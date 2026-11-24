<?php
require_once __DIR__ . '/../functions.php';
header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(403);
    echo json_encode(['error' => 'Brak autoryzacji']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(400);
    echo json_encode(['error' => 'Wymagana metoda GET']);
    exit;
}

$project_id = filter_input(INPUT_GET, 'project_id', FILTER_VALIDATE_INT);
if (!$project_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Brak lub nieprawidłowy parametr project_id']);
    exit;
}

$pdo = get_db();


$stmtProj = $pdo->prepare("SELECT id FROM projects WHERE id = ?");
$stmtProj->execute([$project_id]);
if (!$stmtProj->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Projekt nie istnieje']);
    exit;
}


if (!is_project_member($pdo, $project_id, $_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Brak dostępu do tego projektu']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT t.id, t.title, t.description, t.status, t.tag, t.due_date, u.username AS assignee 
    FROM tasks t 
    LEFT JOIN users u ON t.assigned_to = u.id 
    WHERE t.project_id = ?
    ORDER BY t.id DESC
");
$stmt->execute([$project_id]);

http_response_code(200);
echo json_encode(['project_id' => $project_id, 'tasks' => $stmt->fetchAll()]);
