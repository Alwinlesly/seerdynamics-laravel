<aside class="col-lg-3 pt-3 sidebar">
    {{-- Projects: admin or consultant (groups 1,2) --}}
    @if(auth()->user()->inGroup(1) || auth()->user()->inGroup(2))
    <div class="nav-item mb-3">
        <a class="menu-sidebar--item {{ request()->routeIs('projects.*') ? 'active-menu' : '' }}" href="{{ route('projects.index') }}">
            Projects
        </a>
    </div>
    @endif

    {{-- Tickets: admin or consultant (groups 1,2) --}}
    @if(auth()->user()->inGroup(1) || auth()->user()->inGroup(2))
    <div class="nav-item mb-3">
        <a class="menu-sidebar--item {{ request()->routeIs('tasks.*') ? 'active-menu' : '' }}" href="{{ route('tasks.index') }}">
            Tickets
        </a>
    </div>
    @endif

    {{-- Customers: admin only --}}
    @if(auth()->user()->inGroup(1))
    <div class="nav-item mb-3">
        <a class="menu-sidebar--item {{ request()->routeIs('customers.*') ? 'active-menu' : '' }}" href="{{ route('customers.index') }}">
            Customers
        </a>
    </div>
    @endif

    {{-- Customer Users: admin or customer (group 3) --}}
    @if(auth()->user()->inGroup(1) || auth()->user()->inGroup(3))
    <div class="nav-item mb-3">
        <a class="menu-sidebar--item {{ request()->routeIs('customer-users.*') ? 'active-menu' : '' }}" href="{{ route('customer-users.index') }}">
            Customer users
        </a>
    </div>
    @endif

    {{-- Timesheet dropdown: admin or consultant (groups 1,2) --}}
    @if(auth()->user()->inGroup(1) || auth()->user()->inGroup(2))
    @php
        $timesheetActive = request()->routeIs('timesheets.*') || request()->routeIs('support-statement.*');
    @endphp
    <div class="nav-item mb-3">
        <a class="menu-sidebar--item has-submenu {{ $timesheetActive ? 'active-menu' : '' }}" href="#" onclick="toggleTimesheetSubmenu(event)">
            <span>Timesheet</span>
            <span class="dropdown-arrow {{ $timesheetActive ? 'rotated' : '' }}" id="timesheetArrow">▶</span>
        </a>
        <div class="submenu {{ $timesheetActive ? 'show' : '' }}" id="timesheetSubmenu">
            <a class="menu-sidebar--item {{ request()->routeIs('timesheets.index') || request()->routeIs('timesheets.create') ? 'active-menu' : '' }}" href="{{ route('timesheets.index') }}">Timesheet</a>

            @if(auth()->user()->inGroup(1))
            <a class="menu-sidebar--item {{ request()->routeIs('timesheets.release') ? 'active-menu' : '' }}" href="{{ route('timesheets.release') }}">Timesheet Release</a>
            @endif

            <a class="menu-sidebar--item {{ request()->routeIs('support-statement.*') ? 'active-menu' : '' }}" href="{{ route('support-statement.index') }}">Support Statement</a>
        </div>
    </div>
    @endif

    {{-- Support Statement: standalone for customers (group 3) only --}}
    @if(auth()->user()->inGroup(3))
    <div class="nav-item mb-3">
        <a class="menu-sidebar--item {{ request()->routeIs('support-statement.*') ? 'active-menu' : '' }}" href="{{ route('support-statement.index') }}">
            Support Statement
        </a>
    </div>
    @endif

    {{-- Consultants: admin only --}}
    @if(auth()->user()->inGroup(1))
    <div class="nav-item mb-3">
        <a class="menu-sidebar--item {{ request()->routeIs('consultants.*') ? 'active-menu' : '' }}" href="{{ route('consultants.index') }}">
            Consultants
        </a>
    </div>
    @endif

    <div class="nav-item mb-3">
        <a class="menu-sidebar--item" href="{{ route('logout') }}">
            Logout
        </a>
    </div>
</aside>

<style>
    .active-menu {
        color: #000 !important;
        font-weight: 700 !important;
    }
</style>

<script>
function toggleTimesheetSubmenu(event) {
    event.preventDefault();
    var submenu = document.getElementById('timesheetSubmenu');
    var arrow = document.getElementById('timesheetArrow');
    submenu.classList.toggle('show');
    arrow.classList.toggle('rotated');
}
</script>
