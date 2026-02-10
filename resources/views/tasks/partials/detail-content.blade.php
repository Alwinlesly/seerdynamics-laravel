<!-- Title & Status -->
<div class="status-body">
    <span class="{{ strtolower(str_replace(' ', '-', $task->taskStatus->title ?? 'not-started')) }}">
        {{ $task->taskStatus->title ?? 'Not Started' }}
    </span>
</div>

<div class="td-row">
    <h2>{{ $task->title }}</h2>
    
    <div class="row mb-3 mx-0 mt-4">
        <div class="col-md-8 timezone-detail p-0">
            <div class="td-item">
                <div>
                    <span class="left-col">Project</span> 
                    <span class="right-col">{{ $task->project ? $task->project->project_id . ' ' . $task->project->title : 'N/A' }}</span>
                </div>
            </div>
            <div class="td-item">
                <div>
                    <span class="left-col">Issue type</span> 
                    <span class="right-col">
                        @php
                            $issueType = $task->issueType ?? \App\Models\IssueType::find($task->issue_type);
                        @endphp
                        {{ $issueType ? $issueType->title : 'N/A' }}
                    </span>
                </div>
            </div>
            <div class="td-item">
                <div>
                    <span class="left-col">Issue date</span> 
                    <span class="right-col">{{ $task->created ? date('d-M-Y', strtotime($task->created)) : 'N/A' }}</span>
                </div>
            </div>
            <div class="td-item">
                <div>
                    <span class="left-col">Service</span> 
                    <span class="right-col">{{ $task->service ?? 'N/A' }}</span>
                </div>
            </div>
            <div class="td-item">
                <div>
                    <span class="left-col">Estimate</span> 
                    <span class="right-col">{{ $task->estimate ?? 0 }}</span>
                </div>
            </div>
            <div class="td-item">
                <div>
                    <span class="left-col">Additional mail</span> 
                    <span class="right-col">{{ $task->additional_mail ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 p-0">
            <div class="td-item">
                <div>
                    <span class="left-col">Customer user</span> 
                    <div>
                        <div class="d-flex align-items-center avatar-group">
                            @if($task->project && $task->project->client)
                            <div class="avatar" title="{{ $task->project->client->first_name }} {{ $task->project->client->last_name }}">
                                @if($task->project->client->profile_picture)
                                <img src="{{ asset($task->project->client->profile_picture) }}" alt="{{ $task->project->client->first_name }}">
                                @else
                                <div class="avatar-placeholder">{{ strtoupper(substr($task->project->client->first_name, 0, 1)) }}</div>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="td-item">
                <div>
                    <span class="left-col">Consultants</span>
                    <div>
                        <div class="d-flex align-items-center avatar-group">
                            @if($task->users && count($task->users) > 0)
                                @foreach($task->users as $user)
                                <div class="avatar" title="{{ $user->first_name }} {{ $user->last_name }}">
                                    @if($user->profile_picture)
                                    <img src="{{ asset($user->profile_picture) }}" alt="{{ $user->first_name }}">
                                    @else
                                    <div class="avatar-placeholder">{{ strtoupper(substr($user->first_name, 0, 1)) }}</div>
                                    @endif
                                </div>
                                @endforeach
                            @else
                                <span class="text-muted">No consultants assigned</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="td-item">
                <div>
                    <span class="left-col">Priority</span> 
                    @php
                        $priority = $task->taskPriority ?? \App\Models\Priority::find($task->priority);
                        $priorityTitle = $priority ? $priority->title : 'N/A';
                        $priorityClass = strtolower(str_replace(' ', '-', $priorityTitle));
                    @endphp
                    <b class="right-col {{ $priorityClass }}-pr">{{ $priorityTitle }}</b>
                </div>
            </div>
            
            <div class="td-item">
                <div>
                    <span class="left-col">Attachment</span> 
                    @if($task->attachment)
                    <a href="{{ asset($task->attachment) }}" target="_blank" class="right-col">
                        {{ basename($task->attachment) }}
                    </a>
                    @else
                    <span class="right-col">N/A</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="tab-items">
    <!-- Tabs -->
    <ul class="nav nav-tabs mb-0" id="ticketTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="message-tab" data-bs-toggle="tab" data-bs-target="#message" type="button" role="tab">Message</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="timesheet-tab" data-bs-toggle="tab" data-bs-target="#timesheet" type="button" role="tab">Timesheet</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="estimate-tab" data-bs-toggle="tab" data-bs-target="#estimate" type="button" role="tab">Estimate</button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content p-0" id="ticketTabContent">
        <!-- Message Tab -->
        <div class="tab-pane fade show active" id="message" role="tabpanel">
            <!-- Display existing comments -->
            <div class="comments-section mb-3" style="min-height: 150px; max-height: 300px; overflow-y: auto; padding: 20px; background: #f8f9fa;">
                @if($task->relationLoaded('comments') && $task->comments && count($task->comments) > 0)
                    @foreach($task->comments as $comment)
                    <div class="comment-item mb-3 p-3 bg-white rounded shadow-sm">
                        <div class="d-flex gap-2">
                            <div class="avatar-small">
                                @if($comment->user && $comment->user->profile_picture)
                                    <img src="{{ asset($comment->user->profile_picture) }}" alt="{{ $comment->user->first_name }}" style="width: 32px; height: 32px; border-radius: 50%;">
                                @else
                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #7d6bb2; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                        {{ $comment->user ? strtoupper(substr($comment->user->first_name, 0, 1)) : 'U' }}
                                    </div>
                                @endif
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <strong>{{ $comment->user ? $comment->user->first_name . ' ' . $comment->user->last_name : 'User' }}</strong>
                                    <small class="text-muted">{{ $comment->created_at ? $comment->created_at->diffForHumans() : '' }}</small>
                                </div>
                                <p class="mb-0 mt-1">{{ $comment->message }}</p>
                                @if($comment->attachment)
                                <div class="mt-1">
                                    <a href="{{ asset($comment->attachment) }}" target="_blank" class="text-primary">
                                        <i class="bi bi-paperclip"></i> {{ basename($comment->attachment) }}
                                    </a>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="text-center" style="padding-top: 50px;">
                        <p class="text-muted">No messages yet</p>
                    </div>
                @endif
            </div>
            
            <!-- Add new comment -->
            <form id="addCommentForm" data-task-id="{{ $task->id }}">
                @csrf
                <div class="px-3 pt-3">
                    <textarea class="form-control" name="message" rows="4" placeholder="Type your message..." required style="border: 1px solid #dee2e6; resize: none;"></textarea>
                </div>
                <div class="d-flex justify-content-between align-items-center px-3 py-3" style="gap: 10px;">
                    <div class="flex-grow-1 position-relative">
                        <input type="text" class="form-control" id="fileNameDisplay" placeholder="No file chosen" readonly style="padding-right: 50px;">
                        <input type="file" name="attachment" id="commentFileInput" class="d-none">
                        <label for="commentFileInput" class="position-absolute" style="right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; background: #7d6bb2; color: white; padding: 8px 12px; border-radius: 4px;">
                            <i class="bi bi-upload"></i>
                        </label>
                    </div>
                    <button type="submit" class="btn" style="background: #7d6bb2; color: white; padding: 8px 24px; border-radius: 25px; white-space: nowrap;">Send</button>
                </div>
            </form>
        </div>
        
        <!-- Timesheet Tab -->
        <div class="tab-pane fade" id="timesheet" role="tabpanel">
            <div>
                <div class="table-responsive tt-table">
                    <table class="table table-bordered align-middle tt-table-table">
                        <thead class="table-light">
                            <tr>
                                <th>Consultant</th>
                                <th>Starting time</th>
                                <th>Ending time</th>
                                <th class="text-center">Total time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($task->relationLoaded('timesheets') && $task->timesheets && count($task->timesheets) > 0)
                                @foreach($task->timesheets as $timesheet)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($timesheet->user)
                                                @if($timesheet->user->profile_picture)
                                                    <img src="{{ asset($timesheet->user->profile_picture) }}" alt="{{ $timesheet->user->first_name }}" class="rounded-circle me-2" width="40" height="40">
                                                @else
                                                    <div class="rounded-circle me-2" style="width: 40px; height: 40px; background: #7d6bb2; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                                        {{ strtoupper(substr($timesheet->user->first_name, 0, 1)) }}
                                                    </div>
                                                @endif
                                                <span class="tt-nm">{{ $timesheet->user->first_name }} {{ $timesheet->user->last_name }}</span>
                                            @else
                                                <span class="tt-nm">Unknown</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $startTime = $timesheet->start_time ?? $timesheet->starting_time;
                                            $endTime = $timesheet->end_time ?? $timesheet->ending_time;
                                        @endphp
                                        {{ $startTime ? date('d-M-Y H:i', strtotime($startTime)) : 'N/A' }}
                                    </td>
                                    <td>{{ $endTime ? date('d-M-Y H:i', strtotime($endTime)) : 'Ongoing' }}</td>
                                    <td class="text-center">{{ $timesheet->total_hours ?? '0' }} hrs</td>
                                </tr>
                                @endforeach
                            @else
                            <tr>
                                <td colspan="4" class="text-center">No timesheet entries yet</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Estimate Tab -->
        <div class="tab-pane fade" id="estimate" role="tabpanel">
            <div>
                <form class="row align-items-center g-3 m-0 row-tt">
                    <div class="col-md-7">
                        <div class="tt-item">
                            <label for="functionalEstimate" class="form-label mb-0">Functional estimate (hrs)</label>
                            <input type="text" class="form-control" id="functionalEstimate">
                        </div>
                        <div class="tt-item">
                            <label for="technicalEstimate" class="form-label mb-0">Technical estimate (hrs)</label>
                            <input type="text" class="form-control" id="technicalEstimate">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div>
                            <div class="mb-2">Estimate in days : <span id="daysEstimate">0.0</span></div>
                            <div>Estimate in hours : <span id="hoursEstimate">0:00</span></div>
                        </div>
                        <button type="submit" class="btn btn-primary add-btn">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
