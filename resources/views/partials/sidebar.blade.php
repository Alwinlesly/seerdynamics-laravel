<aside class="col-lg-3 pt-3 sidebar">
    {{-- Projects: admin or anyone with project_view (groups 1,2 — not customers/cusers) --}}
    @if(auth()->user()->inGroup(1) || auth()->user()->inGroup(2))
    <div class="nav-item mb-3">
        <a class="menu-sidebar--item" href="{{ route('projects.index') }}">
            Projects
        </a>
    </div>
    @endif

    {{-- Tickets: admin or anyone with task_view (groups 1,2 — not customers/cusers) --}}
    @if(auth()->user()->inGroup(1) || auth()->user()->inGroup(2))
    <div class="nav-item mb-3">
        <a class="menu-sidebar--item" href="{{ route('tasks.index') }}">
            Tickets
        </a>
    </div>
    @endif

    {{-- Customers: admin only --}}
    @if(auth()->user()->inGroup(1))
    <div class="nav-item mb-3">
        <a class="menu-sidebar--item" href="{{ route('customers.index') }}">
            Customers
        </a>
    </div>
    @endif

    {{-- Customer Users: admin or customer (group 3) --}}
    @if(auth()->user()->inGroup(1) || auth()->user()->inGroup(3))
    <div class="nav-item mb-3">
        <a class="menu-sidebar--item" href="{{ route('customer-users.index') }}">
            Customer users
        </a>
    </div>
    @endif

    {{-- Timesheet dropdown: admin or consultant (groups 1,2) --}}
    @if(auth()->user()->inGroup(1) || auth()->user()->inGroup(2))
    <div class="nav-item mb-3">
        <a class="menu-sidebar--item has-submenu" href="#" onclick="toggleTimesheetSubmenu(event)">
            <span>Timesheet</span>
            <span class="dropdown-arrow" id="timesheetArrow">▶</span>
        </a>
        <div class="submenu" id="timesheetSubmenu">
            {{-- Timesheet: admin + consultant (not customer, not cuser) --}}
            <a class="menu-sidebar--item" href="{{ route('timesheets.index') }}">Timesheet</a>

            {{-- Timesheet Release: admin only --}}
            @if(auth()->user()->inGroup(1))
            <a class="menu-sidebar--item" href="{{ route('timesheets.release') }}">Timesheet Release</a>
            @endif

                {{-- Support Statement: standalone for customers (group 3) --}}
            @if(auth()->user()->inGroup(3))
  
                <a class="menu-sidebar--item" href="{{ route('support-statement.index') }}">Support Statement</a>

            @endif

            {{-- Support Statement: also visible to admin (inside their own context) --}}
            @if(auth()->user()->inGroup(1))
 
                <a class="menu-sidebar--item" href="{{ route('support-statement.index') }}">Support Statement</a>
  
            @endif
        </div>
    </div>
    @endif



    {{-- Consultants: admin only --}}
    @if(auth()->user()->inGroup(1))
    <div class="nav-item mb-3">
        <a class="menu-sidebar--item" href="{{ route('consultants.index') }}">
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

<script>
function toggleTimesheetSubmenu(event) {
    event.preventDefault();
    var submenu = document.getElementById('timesheetSubmenu');
    var arrow = document.getElementById('timesheetArrow');
    submenu.classList.toggle('show');
    arrow.classList.toggle('rotated');
}
</script>
