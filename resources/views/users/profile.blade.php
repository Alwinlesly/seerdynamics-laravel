@extends('layouts.app')

@section('content')
<div class="main">
    @include('partials.header')

    <div class="row m-0">
        @include('partials.sidebar')

        <div class="right-section col-lg-9 px-0 pb-0">
            <div class="px-4 pb-100">
                <div class="header">
                    <h1 class="pg-hd"><b>Profile</b></h1>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body d-flex flex-column flex-md-row align-items-md-center gap-4">
                        <div class="d-flex align-items-center gap-3">
                            @if(!empty($profile_user['profile']))
                                <img src="{{ asset('assets/uploads/profiles/' . $profile_user['profile']) }}" alt="Profile" class="rounded-circle" style="width:80px;height:80px;object-fit:cover;">
                            @else
                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:80px;height:80px;background:#513998;color:#fff;font-weight:600;font-size:24px;">
                                    {{ strtoupper($profile_user['short_name']) }}
                                </div>
                            @endif

                            <div>
                                <div class="fw-semibold fs-5">{{ $profile_user['first_name'] }} {{ $profile_user['last_name'] }}</div>
                                <div class="text-muted">{{ $profile_user['email'] }}</div>
                                <div class="text-muted">{{ $profile_user['role'] }}</div>
                            </div>
                        </div>

                        <div class="ms-md-auto d-flex flex-wrap gap-3">
                            <div class="text-center px-3 py-2 border rounded">
                                <div class="text-muted small">Projects</div>
                                <div class="fw-semibold">{{ $profile_user['projects_count'] }}</div>
                            </div>

                            @if(!auth()->user()->inGroup(3))
                            <div class="text-center px-3 py-2 border rounded">
                                <div class="text-muted small">Tasks</div>
                                <div class="fw-semibold">{{ $profile_user['tasks_count'] }}</div>
                            </div>
                            @endif

                            <div class="text-center px-3 py-2 border rounded">
                                <div class="text-muted small">Status</div>
                                <div class="fw-semibold">
                                    @if((int)$profile_user['active'] === 1)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Deactive</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <form action="{{ route('users.profile.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">First name <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $profile_user['first_name']) }}" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Last name <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $profile_user['last_name']) }}" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="{{ $profile_user['email'] }}" disabled readonly>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $profile_user['phone']) }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" placeholder="Leave empty to keep current password">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Confirm Password</label>
                                    <input type="password" name="password_confirm" class="form-control" placeholder="Leave empty to keep current password">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">User profile</label>
                                    <input type="file" name="profile" class="form-control" accept="image/*">
                                </div>
                            </div>

                            @if($errors->any())
                                <div class="alert alert-danger mt-3 mb-0">
                                    <ul class="mb-0 ps-3">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn btn-create">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            @include('partials.footer')
        </div>
    </div>
</div>
@endsection

