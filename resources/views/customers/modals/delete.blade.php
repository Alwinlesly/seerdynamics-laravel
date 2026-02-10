<!-- Delete Customer Modal -->
<div class="modal fade" id="deleteCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Delete Customer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="deleteCustomerForm">
                @csrf
                @method('DELETE')
                <input type="hidden" id="deleteCustomerId" name="id">
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="bi bi-exclamation-triangle text-warning" style="font-size: 48px;"></i>
                    </div>
                    <p class="text-center mb-0">Are you sure you want to delete this customer?</p>
                    <p class="text-center text-muted small">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Submit delete customer form
    $('#deleteCustomerForm').on('submit', function(e) {
        e.preventDefault();
        
        const customerId = $('#deleteCustomerId').val();
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Deleting...');
        
        $.ajax({
            url: `/customers/${customerId}`,
            method: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (!response.error) {
                    $('#deleteCustomerModal').modal('hide');
                    loadCustomers();
                    
                    // Show success message
                    const alert = $('<div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999;">' +
                        '<i class="bi bi-check-circle me-2"></i>' + response.message +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
                    $('body').append(alert);
                    setTimeout(() => alert.remove(), 3000);
                } else {
                    alert(response.message || 'Failed to delete customer');
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Failed to delete customer';
                alert(message);
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('Delete Customer');
            }
        });
    });
});
</script>
