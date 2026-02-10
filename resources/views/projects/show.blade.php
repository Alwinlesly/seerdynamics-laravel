@extends('layouts.app')

@section('content')
<div class="main">
    <div class="top-header">
        <div class="d-flex align-items-center gap-3">
            <div class="hamburger is-lg">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </div>
            <div class="logo">
                <img src="{{ asset('assets/img/logo-360x103.png') }}" alt="">
            </div>
        </div>

        <div class="top-right">
            <div class="profile-container" id="profilePopover" data-bs-toggle="popover">
                <img src="{{ asset('assets/img/mrs1.webp') }}" alt="User Profile">
                <div class="profile-info">
                    <div class="name">{{ $current_user->first_name }} {{ $current_user->last_name }}</div>
                    <div class="email">{{ $current_user->email }}</div>
                </div>
                <div class="dropdown-icon">▼</div>
            </div>
        </div>
    </div>

    <div class="row m-0">
        @include('partials.sidebar')

        <div class="right-section col-lg-9 px-0 pb-0">
            <div class="px-4">
                <div class="header px-3 align-items-center pd_header">
                    <div>
                        <h1 class="pg-hd"><b>Projects details</b></h1>
                        <div class="pg-nv">
                            <span><a href="{{ route('projects.index') }}">Projects</a></span>
                            <span class="activePage">/ Projects details</span>
                        </div>
                    </div>
                    <div class="search-create pd_sc--btns">
                        <div class="d-flex justify-content-center flex-wrap">
                            <div class="btn-group pd_sc--btn-group" role="group">
                                <button class="btn btn-purple" onclick="window.location.href='/tasks?project={{ $project->id }}'">Tickets</button>
                                <button class="btn btn-purple">Timesheet</button>
                                <button class="btn btn-outline-purple" onclick="editProject({{ $project->id }})">Edit</button>
                                <button class="btn btn-outline-red btn-rounded" onclick="deleteProject({{ $project->id }})">Delete</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-4 pb-4">
                <!-- Project Info Card -->
                <div class="row m-0 mb-4">
                    <div class="col-lg-8">
                        <div class="card border-0 business-card">
                            <div class="card-body">
                                <div class="mb-2">
                                    <h6 class="card-title fw-bold mb-2">{{ $project->project_id }} {{ $project->title }}</h6>
                                    <p class="mb-1">{{ $project->description }}</p>
                                </div>
                                <small class="text-muted d-block mb-1">Services:</small>
                                <p class="mb-3">{{ $project->services_offered ?? 'N/A' }}</p>

                                <div class="d-flex flex-wrap gap-2">
                                    @if($project->project_value)
                                    <span class="badge aed-badge">{{ $project->project_value }} {{ $project->project_currency }}</span>
                                    @endif
                                    <span class="badge support-bg">{{ $project->project_type ?? 'Project' }}</span>
                                    <span class="badge {{ strtolower(str_replace(' ', '-', $project->status)) }}-badge">{{ $project->status }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Detail & Ticket Overview -->
                <div class="row m-0 project-row">
                    <div class="col-lg-6">
                        <div class="card cd-card p-0 h-100">
                            <h2 class="card-header to-header border-0">Customer detail</h2>
                            <div class="card-body p-0">
                                <div class="p-3">
                                    <div class="mb-2 d-flex flex-wrap">
                                        <span class="info-label">Customer :</span>
                                        <span class="info-value"><b>{{ $project->customer->company ?? $project->customer->first_name }}</b></span>
                                    </div>
                                    <div class="mb-2 d-flex flex-wrap">
                                        <span class="info-label">Contact person :</span>
                                        <span class="info-value"><b>{{ $project->customer->first_name }} {{ $project->customer->last_name }}</b></span>
                                    </div>
                                    <div class="mb-3 d-flex flex-wrap">
                                        <span class="info-label">Email Id :</span>
                                        <span class="info-value"><b>{{ $project->customer->email }}</b></span>
                                    </div>
                                    <div class="mb-3 d-flex flex-wrap">
                                        <span class="info-label">Address :</span>
                                        <span class="info-value"><b>{{ $project->customer->address ?? 'N/A' }}</b></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="to-card card p-0 h-100">
                            <h2 class="card-header to-header border-0">Ticket overview</h2>
                            <div class="card-body p-0">
                                <div class="p-3">
                                    <div class="mb-2 d-flex flex-wrap">
                                        <span class="info-label">Days left :</span>
                                        <span class="info-value"><b>{{ $days_left }}</b></span>
                                    </div>
                                    <div class="mb-2 d-flex flex-wrap">
                                        <span class="info-label">Starting date :</span>
                                        <span class="info-value"><b>{{ $project->starting_date->format('d-M-Y') }}</b></span>
                                    </div>
                                    <div class="mb-3 d-flex flex-wrap">
                                        <span class="info-label">Ending date :</span>
                                        <span class="info-value"><b>{{ $project->ending_date->format('d-M-Y') }}</b></span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between p-3 to-btm">
                                    <div class="d-flex to-btm-item gap-2">
                                        <span class="info-label-btm">Total tickets :</span>
                                        <span class="info-value"><b>{{ $total_tickets }}</b></span>
                                    </div>
                                    <div class="d-flex to-btm-item gap-2">
                                        <span class="info-label-btm">Completed tickets :</span>
                                        <span class="info-value"><b>{{ $completed_tickets }}</b></span>
                                    </div>
                                    <div class="d-flex to-btm-item gap-2">
                                        <span class="info-label-btm">Pending tickets :</span>
                                        <span class="info-value"><b>{{ $pending_tickets }}</b></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project Users & Files -->
                <div class="row m-0 mt-4 project-row">
                    <div class="col-lg-6">
                        <div class="card cd-card table-container p-0">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col" class="th-color">Project users name</th>
                                                <th scope="col" class="font-medium">Email</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($project->users as $user)
                                            <tr>
                                                <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                                                <td>{{ $user->email }}</td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="2" class="text-center">No users assigned</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card cd-card table-container p-0">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col" class="th-color">Project file</th>
                                                <th scope="col" class="font-medium">Type</th>
                                                <th scope="col" class="font-medium">Size</th>
                                                <th scope="col" class="text-center font-medium"><span>Action</span></th>
                                                <th scope="col" class="text-center font-medium">
                                                    <i class="bi bi-upload download-icon" onclick="document.getElementById('fileUpload').click()"></i>
                                                    <input type="file" id="fileUpload" style="display:none" onchange="uploadFile()">
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody id="filesTableBody">
                                            @forelse($project->files as $file)
                                            <tr data-file-id="{{ $file->id }}">
                                                <td>{{ $file->file_name }}</td>
                                                <td>{{ strtoupper($file->file_type) }}</td>
                                                <td>{{ $file->file_size_formatted }}</td>
                                                <td class="text-center">
                                                    <span title="Delete" class="del-item" onclick="deleteFile({{ $file->id }})">
                                                        <i class="bi bi-trash"></i>
                                                    </span>
                                                </td>
                                                <td></td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="5" class="text-center">No files uploaded</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @include('partials.footer')
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const projectId = {{ $project->id }};

function uploadFile() {
    const fileInput = document.getElementById('fileUpload');
    const file = fileInput.files[0];
    
    if (!file) return;
    
    const formData = new FormData();
    formData.append('file', file);
    formData.append('_token', '{{ csrf_token() }}');
    
    $.ajax({
        url: `/projects/${projectId}/upload-file`,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (!response.error) {
                location.reload();
            }
        }
    });
}

function deleteFile(fileId) {
    if (!confirm('Are you sure you want to delete this file?')) return;
    
    $.ajax({
        url: `/projects/${projectId}/files/${fileId}`,
        method: 'DELETE',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (!response.error) {
                $(`tr[data-file-id="${fileId}"]`).remove();
            }
        }
    });
}

function editProject(id) {
    window.location.href = `/projects/${id}/edit`;
}

function deleteProject(id) {
    $('#deleteProjectId').val(id);
    $('#deleteProjectModal').modal('show');
}
</script>
@endpush
