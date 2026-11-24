document.addEventListener('DOMContentLoaded', () => {
    const statuses = ['todo', 'in_progress', 'done'];

    document.querySelectorAll('.move-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            const card = e.target.closest('.task-card');
            const currentCol = e.target.closest('.kanban-column');
            const currentStatus = currentCol.dataset.status;
            const direction = parseInt(e.target.dataset.dir, 10);
            
            const newIndex = statuses.indexOf(currentStatus) + direction;
            if (newIndex < 0 || newIndex >= statuses.length) return; 

            const newStatus = statuses[newIndex];
            const taskId = card.dataset.taskId;

            try {
                const response = await fetch('api/update_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ task_id: taskId, status: newStatus })
                });

                if (!response.ok) {
                    const err = await response.json();
                    alert('Błąd: ' + (err.error || 'Nie udało się zmienić statusu'));
                    return;
                }

                
                const targetCol = document.querySelector(`.kanban-column[data-status="${newStatus}"]`);
                targetCol.appendChild(card);
            } catch (error) {
                alert('Błąd połączenia z serwerem.');
            }
        });
    });
});
