<aside class="col-lg-3 pt-3 sidebar">
    @if(!auth()->user()->inGroup(3))
    <div class="nav-item mb-3">
        <a class="menu-sidebar--item" href="{{ route('projects.index') }}">
            Projects
        </a>
    </div>
    <div class="nav-item mb-3">
        <a class="menu-sidebar--item" href="{{ route('tasks.index') }}">
            Tickets
        </a>
    </div>
    @endif
    
    @if(auth()->user()->inGroup(1))
    <div class="nav-item mb-3">
        <a class="menu-sidebar--item" href="{{ route('customers.index') }}">
            Customers
        </a>
    </div>
    <div class="nav-item mb-3">
        <a class="menu-sidebar--item" href="{{ route('customer-users.index') }}">
            Customer users
        </a>
    </div>
    @endif
    
    @if(auth()->user()->inGroup(1) || auth()->user()->inGroup(2))
    <div class="nav-item mb-3">
        <a class="menu-sidebar--item has-submenu" href="#" onclick="toggleTimesheetSubmenu(event)">
            <span>Timesheet</span>
            <span class="dropdown-arrow" id="timesheetArrow">▶</span>
        </a>
        <div class="submenu" id="timesheetSubmenu">
            <a class="menu-sidebar--item" href="{{ route('timesheets.index') }}">Timesheet</a>
            <a class="menu-sidebar--item" href="{{ route('timesheets.release') }}">Timesheet Release</a>
            <a class="menu-sidebar--item" href="{{ route('support-statement.index') }}">Support Statement</a>
        </div>
    </div>
    @endif

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
