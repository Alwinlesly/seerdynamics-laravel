<div class="modal fade" id="deleteProjectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-otd modal-header d-flex justify-content-between align-items-center gap-4">
                <h5 class="modal-title">You want to delete this project?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body create-ticket-body pb-4">
                <p>All related data with this project also will be deleted.</p>
                <input type="hidden" id="deleteProjectId">
                <div class="d-flex justify-content-end gap-2 flex-wrap mt-4">
                    <button class="cancel-btn" data-bs-dismiss="modal">Cancel</button>
                    <button class="delete-btn" onclick="confirmDelete()">Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete() {
    const projectId = $('#deleteProjectId').val();
    
    $.ajax({
        url: `/projects/${projectId}`,
        method: 'DELETE',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (!response.error) {
                alert(response.message);
                $('#deleteProjectModal').modal('hide');
                window.location.href = '{{ route("projects.index") }}';
            } else {
                alert(response.message);
            }
        }
    });
}
</script>
