<?php require_once __DIR__ . '/../functions.php'; ?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kanban MVP</title>
   
   <link rel="stylesheet" href="<?= 'https://' . 'cdn.jsdelivr.net' . '/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' ?>">


</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">KanbanApp</a>
        <?php if (is_logged_in()): ?>
            <div class="navbar-nav me-auto">
                <a class="nav-link" href="index.php">Projekty</a>
                <a class="nav-link" href="tasks_list.php">Wszystkie zadania</a>
            </div>
            <span class="navbar-text text-white me-3">
                Zalogowany: <strong><?= e($_SESSION['username']) ?></strong>
            </span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Wyloguj</a>
        <?php endif; ?>
    </div>
</nav>
<div class="container">
