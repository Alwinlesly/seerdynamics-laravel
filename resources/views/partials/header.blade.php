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
        <div class="dropdown">
            @php
                $profile = $current_user->profile ?? '';
                $profileUrl = '';
                if (!empty($profile)) {
                    if (preg_match('/^https?:\/\//i', $profile)) {
                        $profileUrl = $profile;
                    } elseif (strpos($profile, '/') !== false) {
                        $profileUrl = asset(ltrim($profile, '/'));
                    } else {
                        $assetsPath = public_path('assets/uploads/profiles/' . $profile);
                        $legacyPath = public_path('uploads/profile/' . $profile);
                        if (file_exists($assetsPath)) {
                            $profileUrl = asset('assets/uploads/profiles/' . $profile);
                        } elseif (file_exists($legacyPath)) {
                            $profileUrl = asset('uploads/profile/' . $profile);
                        }
                    }
                }
                $shortName = strtoupper(mb_substr($current_user->first_name ?? '', 0, 1) . mb_substr($current_user->last_name ?? '', 0, 1));
            @endphp
            <div class="profile-container" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                @if(!empty($profileUrl))
                    <img src="{{ $profileUrl }}" alt="User Profile" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                @else
                    <div class="d-flex align-items-center justify-content-center" style="width:40px;height:40px;border-radius:50%;background:#6f42c1;color:#fff;font-size:13px;font-weight:600;">
                        {{ $shortName ?: 'U' }}
                    </div>
                @endif
                <div class="profile-info">
                    <div class="name">{{ $current_user->first_name }} {{ $current_user->last_name }}</div>
                    <div class="email">{{ $current_user->email }}</div>
                </div>
                <div class="dropdown-icon">▼</div>
            </div>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                <li>
                    <a class="dropdown-item" href="{{ route('users.profile') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-2">
                            <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                            <path fill-rule="evenodd" d="M8 9a5 5 0 0 0-4.546 2.916A.5.5 0 0 0 3.91 12.5h8.18a.5.5 0 0 0 .455-.584A5 5 0 0 0 8 9z"/>
                        </svg>
                        Profile
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="{{ route('logout') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-2">
                            <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z"/>
                            <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/>
                        </svg>
                        Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
