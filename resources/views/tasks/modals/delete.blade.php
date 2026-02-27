<!-- Delete Task Modal -->
<div class="modal fade" id="deleteTaskModal" tabindex="-1" aria-labelledby="deleteTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteTaskModalLabel">Delete Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this task? This action cannot be undone.</p>
                <input type="hidden" id="deleteTaskId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $('#confirmDeleteBtn').on('click', function() {
        const taskId = $('#deleteTaskId').val();

        $.ajax({
            url: `/tasks/${taskId}`,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (!response.error) {
                    $('#deleteTaskModal').modal('hide');
                    loadTasks();
                    showToast('success', 'Task deleted successfully!');
                } else {
                    showToast('error', 'Error: ' + response.message);
                }
            },
            error: function(error) {
                console.error('Error deleting task:', error);
                showToast('error', 'Error deleting task');
            }
        });
    });
</script>
@endpush
