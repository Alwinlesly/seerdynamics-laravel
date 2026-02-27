<div class="modal fade" id="deleteTimesheetModal" tabindex="-1" aria-labelledby="deleteTimesheetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteTimesheetModalLabel">Delete Timesheet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete timesheet <strong id="deleteTimesheetName"></strong>?</p>
                <input type="hidden" id="deleteTimesheetId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteTimesheet">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
$('#confirmDeleteTimesheet').on('click', function() {
    const timesheetId = $('#deleteTimesheetId').val();
    
    $.ajax({
        url: `/timesheets/${timesheetId}`,
        method: 'DELETE',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (!response.error) {
                $('#deleteTimesheetModal').modal('hide');
                loadTimesheets();
                showToast('success', 'Timesheet deleted successfully!');
            } else {
                showToast('error', 'Error deleting timesheet: ' + response.message);
            }
        },
        error: function(xhr) {
            showToast('error', 'Error deleting timesheet');
        }
    });
});
</script>
