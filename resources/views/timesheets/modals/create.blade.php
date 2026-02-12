<div class="modal fade" id="createTimesheetModal" tabindex="-1" aria-labelledby="createTimesheetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-otd modal-header d-flex justify-content-between align-items-center gap-4">
                <h5 class="modal-title" id="createTimesheetModalLabel">Create new timesheet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body create-ticket-body pb-4">
                <form id="createTimesheetForm">
                    @csrf
                    
                    <!-- Start Date / End Date -->
                    <div class="row mb-3">
                        <div class="col-lg-6">
                            <div>
                                <label for="startDate" class="form-label">Start Date <span class="req">*</span></label>
                                <input type="date" class="form-control" id="startDate" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div>
                                <label for="endDate" class="form-label">End Date <span class="req">*</span></label>
                                <input type="date" class="form-control" id="endDate" name="end_date" required>
                            </div>
                        </div>
                    </div>

                    <!-- Billable / Non-Billable Hours -->
                    <div class="row mb-3">
                        <div class="col-lg-6">
                            <div>
                                <label for="billableHours" class="form-label">Billable Hours</label>
                                <input type="number" step="0.5" class="form-control" id="billableHours" name="billable_hours" value="0">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div>
                                <label for="nonBillableHours" class="form-label">Non-Billable Hours</label>
                                <input type="number" step="0.5" class="form-control" id="nonBillableHours" name="non_billable_hours" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 flex-wrap mt-4">
                        <button type="submit" name="submit_or_draft" value="draft" class="btn btn-secondary">Save as Draft</button>
                        <button type="submit" name="submit_or_draft" value="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$('#createTimesheetForm').on('submit', function(e) {
    e.preventDefault();
    
    const submitButton = $(document.activeElement);
    const submitType = submitButton.val();
    
    const formData = {
        _token: '{{ csrf_token() }}',
        start_date: $('#startDate').val(),
        end_date: $('#endDate').val(),
        billable_hours: $('#billableHours').val(),
        non_billable_hours: $('#nonBillableHours').val(),
        submit_or_draft: submitType
    };
    
    $.ajax({
        url: '{{ route("timesheets.store") }}',
        method: 'POST',
        data: formData,
        success: function(response) {
            if (!response.error) {
                $('#createTimesheetModal').modal('hide');
                $('#createTimesheetForm')[0].reset();
                loadTimesheets();
                alert('Timesheet created successfully!');
            } else {
                alert('Error creating timesheet: ' + response.message);
            }
        },
        error: function(xhr) {
            const error = xhr.responseJSON?.message || 'Please check all fields.';
            alert('Error creating timesheet: ' + error);
        }
    });
});

$('#createTimesheetModal').on('hidden.bs.modal', function() {
    $('#createTimesheetForm')[0].reset();
});
</script>
