<div class="modal fade" id="deleteCustomerUserModal" tabindex="-1" aria-labelledby="deleteCustomerUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCustomerUserModalLabel">Delete Customer User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="#dc3545" viewBox="0 0 16 16">
                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                        <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                    </svg>
                </div>
                <p class="text-center">Are you sure you want to delete this customer user?</p>
                <p class="text-center text-muted">This action cannot be undone.</p>
                <input type="hidden" id="deleteCustomerUserId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteCustomerUser">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
$('#confirmDeleteCustomerUser').on('click', function() {
    const cuserId = $('#deleteCustomerUserId').val();
    
    $.ajax({
        url: `/users/client/${cuserId}`,
        method: 'DELETE',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (!response.error) {
                $('#deleteCustomerUserModal').modal('hide');
                loadCustomerUsers();
                showToast('success', 'Customer user deleted successfully!');
            } else {
                showToast('error', 'Error deleting customer user: ' + response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            showToast('error', 'Error deleting customer user: ' + (response?.message || 'Unknown error'));
        }
    });
});
</script>
