<?php
require_once __DIR__ . '/../functions.php';
header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(403);
    echo json_encode(['error' => 'Brak autoryzacji']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['error' => 'Wymagana metoda POST']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$task_id = filter_var($input['task_id'] ?? null, FILTER_VALIDATE_INT);
$new_status = $input['status'] ?? '';
$allowed_statuses = ['todo', 'in_progress', 'done'];

if (!$task_id || !in_array($new_status, $allowed_statuses, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Nieprawidłowe dane wejściowe']);
    exit;
}

$pdo = get_db();


$stmt = $pdo->prepare("SELECT id, title, project_id FROM tasks WHERE id = ?");
$stmt->execute([$task_id]);
$task = $stmt->fetch();

if (!$task) {
    http_response_code(404);
    echo json_encode(['error' => 'Zadanie nie istnieje']);
    exit;
}


if (!is_project_member($pdo, $task['project_id'], $_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Brak dostępu do tego projektu']);
    exit;
}


$update = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
$update->execute([$new_status, $task_id]);


$log = $pdo->prepare("INSERT INTO activity_logs (project_id, user_id, action_text) VALUES (?, ?, ?)");
$log->execute([
    $task['project_id'],
    $_SESSION['user_id'],
    "zmienił(a) status zadania „{$task['title']}” na: {$new_status}"
]);

http_response_code(200);
echo json_encode(['success' => true, 'task_id' => $task_id, 'new_status' => $new_status]);
