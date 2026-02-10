<div>
    <!-- Customer Info -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h6 class="text-muted mb-3">Personal Information</h6>
            <table class="table table-sm">
                <tr>
                    <th width="40%">Name:</th>
                    <td>{{ $customer->first_name }} {{ $customer->last_name }}</td>
                </tr>
                <tr>
                    <th>Email:</th>
                    <td>{{ $customer->email }}</td>
                </tr>
                <tr>
                    <th>Company:</th>
                    <td>{{ $customer->company ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Phone:</th>
                    <td>{{ $customer->phone ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Contact Person:</th>
                    <td>{{ $customer->contact_person_desg ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>
        <div class="col-md-6">
            <h6 class="text-muted mb-3">Additional Details</h6>
            <table class="table table-sm">
                <tr>
                    <th width="40%">Address:</th>
                    <td>{{ $customer->address ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Country:</th>
                    <td>{{ $customer->country ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Status:</th>
                    <td>
                        @if($customer->active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-danger">Inactive</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Created On:</th>
                    <td>{{ $customer->created_on ? date('d-M-Y', $customer->created_on) : 'N/A' }}</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Projects -->
    <div class="mb-3">
        <h6 class="text-muted mb-3">Associated Projects ({{ count($projects) }})</h6>
        @if(count($projects) > 0)
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Project ID</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($projects as $project)
                        <tr>
                            <td>{{ $project->project_id }}</td>
                            <td>{{ $project->title }}</td>
                            <td>
                                <span class="badge bg-primary">{{ $project->status }}</span>
                            </td>
                            <td>{{ $project->starting_date ? date('d-M-Y', strtotime($project->starting_date)) : 'N/A' }}</td>
                            <td>{{ $project->ending_date ? date('d-M-Y', strtotime($project->ending_date)) : 'N/A' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted text-center py-3">No projects associated with this customer</p>
        @endif
    </div>
</div>
