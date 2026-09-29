document.querySelectorAll('.kanban-column').forEach(col => {
    new Sortable(col, {
        group: 'tasks',
        animation: 150,
        ghostClass: 'sortable-ghost',
        dragClass: 'sortable-drag',
        onEnd: function(evt) {
            const taskId    = evt.item.dataset.taskId;
            const newStatus = evt.to.dataset.status;
            updateTaskStatus(taskId, newStatus);
        }
    });
});

function updateTaskStatus(taskId, status) {
    fetch('/tasks/' + taskId + '/status', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.KANBAN_CSRF },
        body: JSON.stringify({ status: status })
    });
}
