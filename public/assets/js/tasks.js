// Toggle submenu
function toggleSubmenu(event) {
  event.preventDefault();
  const submenu = document.getElementById('ticketsSubmenu');
  const arrow = event.target.querySelector('.dropdown-arrow');
  submenu.classList.toggle('show');
  arrow.classList.toggle('rotated');
  event.target.classList.toggle('active');
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {
  // Initialize datepicker
  $(function(){
    $('#datepicker').datepicker({
      format: 'dd-mm-yyyy',
      autoclose: true,
      container: '#ticketModal'
    });
  });

  // Initialize popover
  const profile = document.getElementById('profilePopover');
  if (profile) {
    new bootstrap.Popover(profile);
  }

  // Sidebar toggle
  const sidebar = document.getElementById("sidebar");
  const hamburger = document.querySelector(".hamburger");

  if (hamburger) {
    hamburger.addEventListener("click", function (e) {
      e.stopPropagation();
      sidebar.classList.toggle("active");
      toggleOverlay(sidebar.classList.contains("active"));
    });
  }

  // Close sidebar when clicking outside
  document.addEventListener("click", function (e) {
    if (sidebar) {
      const isClickInsideSidebar = sidebar.contains(e.target);
      const isClickOnHamburger = hamburger && hamburger.contains(e.target);

      if (!isClickInsideSidebar && !isClickOnHamburger) {
        sidebar.classList.remove("active");
        toggleOverlay(false);
      }
    }
  });

  // Add overlay when sidebar is open
  function toggleOverlay(show) {
    let overlay = document.querySelector(".overlay");
    if (show) {
      if (!overlay) {
        overlay = document.createElement("div");
        overlay.classList.add("overlay");
        document.body.appendChild(overlay);
      }
    } else {
      if (overlay) overlay.remove();
    }
  }
});

// Table data and functionality
var allTasks = [];
var currentPage = 1;
var rowsPerPage = 10;
var filteredTasks = [...allTasks];
var sortColumn = 'created';
var sortOrder = 'desc';

// Priority mapping for styling
var priorityMap = {
  'Low': 'low-pr',
  'Medium': 'med-pr',
  'High': 'high-pr'
};

// Status mapping for styling
var statusMap = {
  'Completed': 'completed',
  'In Progress': 'in-progress',
  'Todo': 'todo',
  'In Review': 'in-review',
  'Archived': 'archived',
  'Open': 'open',
  'Closed': 'closed'
};

// Render table
function renderTable() {
  const tbody = document.getElementById('ticketsTableBody');
  tbody.innerHTML = '';
  
  const start = (currentPage - 1) * rowsPerPage;
  const end = start + rowsPerPage;
  const paginatedTasks = filteredTasks.slice(start, end);
  
  if (paginatedTasks.length === 0) {
    const emptyRow = document.createElement('tr');
    const emptyCell = document.createElement('td');
    emptyCell.colSpan = 10;
    emptyCell.className = 'text-center';
    emptyCell.textContent = 'No tasks found';
    emptyRow.appendChild(emptyCell);
    tbody.appendChild(emptyRow);
    updatePagination();
    return;
  }
  
  paginatedTasks.forEach(task => {
    const row = document.createElement('tr');
    const priorityClass = priorityMap[task.task_priority] || 'low-pr';
    const statusClass = statusMap[task.task_status] || 'completed';
    
    // Store task data on element
    const ticketIdCell = document.createElement('td');
    const ticketLink = document.createElement('span');
    ticketLink.textContent = '#' + task.id;
    ticketLink.style.cssText = 'cursor: pointer; color: #0d6efd;';
    ticketLink.setAttribute('data-task-id', task.id);
    ticketLink.setAttribute('data-bs-toggle', 'modal');
    ticketLink.setAttribute('data-bs-target', '#ticketModalDetail');
    ticketLink.addEventListener('click', function() {
      openTaskModal(task.id, task);
    });
    ticketIdCell.appendChild(ticketLink);
    
    const titleCell = document.createElement('td');
    titleCell.textContent = task.title || '';
    
    const projectCell = document.createElement('td');
    projectCell.textContent = task.project_title || 'N/A';
    
    const companyCell = document.createElement('td');
    companyCell.textContent = task.company || 'N/A';
    
    const estimateCell = document.createElement('td');
    estimateCell.className = 'text-center';
    estimateCell.textContent = task.estimated_or_not || '-';
    
    const priorityCell = document.createElement('td');
    const prioritySpan = document.createElement('span');
    prioritySpan.className = priorityClass;
    prioritySpan.textContent = task.task_priority || 'Low';
    priorityCell.appendChild(prioritySpan);
    
    const creatorCell = document.createElement('td');
    creatorCell.textContent = task.first_name || 'N/A';
    
    const dateCell = document.createElement('td');
    dateCell.textContent = task.created || 'N/A';
    
    const statusCell = document.createElement('td');
    const statusSpan = document.createElement('span');
    statusSpan.className = 'status ' + statusClass;
    statusSpan.textContent = task.task_status || 'Todo';
    statusCell.appendChild(statusSpan);
    
    const actionCell = document.createElement('td');
    actionCell.className = 'd-flex gap-2 align-items-center justify-content-center';
    
    const editBtn = document.createElement('span');
    editBtn.style.cssText = 'cursor: pointer;';
    editBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#7d6bb2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" /><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" /></svg>';
    editBtn.addEventListener('click', function() {
      editTask(task.id, task);
    });
    
    const deleteBtn = document.createElement('span');
    deleteBtn.style.cssText = 'cursor: pointer;';
    deleteBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px" viewBox="0 0 24 24" fill="none" stroke="#7d6bb2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>';
    deleteBtn.addEventListener('click', function() {
      deleteTask(task.id, task);
    });
    
    actionCell.appendChild(editBtn);
    actionCell.appendChild(deleteBtn);
    
    row.appendChild(ticketIdCell);
    row.appendChild(titleCell);
    row.appendChild(projectCell);
    row.appendChild(companyCell);
    row.appendChild(estimateCell);
    row.appendChild(priorityCell);
    row.appendChild(creatorCell);
    row.appendChild(dateCell);
    row.appendChild(statusCell);
    row.appendChild(actionCell);
    
    tbody.appendChild(row);
  });
  
  updatePagination();
}

// Update pagination info
function updatePagination() {
  const totalPages = Math.ceil(filteredTasks.length / rowsPerPage);
  document.querySelector('.current-total').textContent = currentPage;
  document.querySelector('.text-muted').textContent = 'of ' + totalPages;
  
  // Disable prev button on first page
  const prevBtn = document.querySelector('.pagination .btn-light');
  prevBtn.disabled = currentPage === 1;
  
  // Disable next button on last page
  const nextBtn = document.querySelector('.next-btn');
  nextBtn.disabled = currentPage >= totalPages;
}

// Pagination click handlers
document.addEventListener('DOMContentLoaded', function() {
  const prevBtn = document.querySelector('.pagination .btn-light');
  const nextBtn = document.querySelector('.next-btn');
  
  if (prevBtn) {
    prevBtn.addEventListener('click', function() {
      if (currentPage > 1) {
        currentPage--;
        renderTable();
      }
    });
  }
  
  if (nextBtn) {
    nextBtn.addEventListener('click', function() {
      const totalPages = Math.ceil(filteredTasks.length / rowsPerPage);
      if (currentPage < totalPages) {
        currentPage++;
        renderTable();
      }
    });
  }

  // Search functionality
  const searchInput = document.querySelector('.search-wrap input');
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      applyFilters();
    });
  }

  // Filter functionality
  const selects = document.querySelectorAll('.sel-wrapper select');
  selects.forEach(select => {
    select.addEventListener('change', applyFilters);
  });

  // Download CSV
  const downloadBtn = document.querySelector('.btn-download');
  if (downloadBtn) {
    downloadBtn.addEventListener('click', exportToCSV);
  }

  // Initial render
  renderTable();
});

// Apply filters
function applyFilters() {
  const searchValue = document.querySelector('.search-wrap input').value.toLowerCase();
  const filters = {
    customer: document.querySelectorAll('.sel-wrapper select')[0].value,
    projectType: document.querySelectorAll('.sel-wrapper select')[1].value,
    project: document.querySelectorAll('.sel-wrapper select')[2].value,
    status: document.querySelectorAll('.sel-wrapper select')[3].value,
    date: document.querySelectorAll('.sel-wrapper select')[4].value
  };

  filteredTasks = allTasks.filter(task => {
    // Search filter - search in title, id, project title, company name
    const searchMatch = searchValue === '' ||
      (task.title && task.title.toLowerCase().includes(searchValue)) ||
      (task.id && task.id.toString().includes(searchValue)) ||
      (task.project_title && task.project_title.toLowerCase().includes(searchValue)) ||
      (task.company && task.company.toLowerCase().includes(searchValue));

    // Customer filter - match by company field or customer name
    const customerMatch = filters.customer === '' || 
      (task.company && task.company.toLowerCase() === filters.customer.toLowerCase()) ||
      (task.id && task.id.toString() === filters.customer);

    // Project type filter
    const projectTypeMatch = filters.projectType === '' || 
      (task.project_id && task.project_id.toString() === filters.projectType) ||
      (task.issue_type && task.issue_type.toString() === filters.projectType);

    // Project filter
    const projectMatch = filters.project === '' || 
      (task.project_id && task.project_id.toString() === filters.project) ||
      (task.project_title && task.project_title.toLowerCase() === filters.project.toLowerCase());

    // Status filter - match by task_status or status id
    const statusMatch = filters.status === '' || 
      (task.task_status && task.task_status.toLowerCase() === filters.status.toLowerCase()) ||
      (task.status && task.status.toString() === filters.status);

    // Date filter
    let dateMatch = true;
    if (filters.date === 'today') {
      const today = new Date().toISOString().split('T')[0];
      dateMatch = task.created && task.created.includes(today);
    } else if (filters.date === '7') {
      const now = new Date();
      const sevenDaysAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
      dateMatch = task.created && task.created.split(' ')[0] >= sevenDaysAgo;
    }

    return searchMatch && customerMatch && projectTypeMatch && projectMatch && statusMatch && dateMatch;
  });

  currentPage = 1;
  renderTable();
}

// Sort function
function sortTable(column) {
  if (sortColumn === column) {
    sortOrder = sortOrder === 'asc' ? 'desc' : 'asc';
  } else {
    sortColumn = column;
    sortOrder = 'asc';
  }

  filteredTasks.sort((a, b) => {
    let aVal = a[column];
    let bVal = b[column];

    if (aVal === null || aVal === undefined) aVal = '';
    if (bVal === null || bVal === undefined) bVal = '';

    if (typeof aVal === 'string') {
      aVal = aVal.toLowerCase();
      bVal = bVal.toLowerCase();
    }

    if (sortOrder === 'asc') {
      return aVal > bVal ? 1 : -1;
    } else {
      return aVal < bVal ? 1 : -1;
    }
  });

  currentPage = 1;
  renderTable();
}

// Export to CSV
function exportToCSV() {
  const headers = ['ID', 'Title', 'Project', 'Customer', 'Estimate', 'Priority', 'Created By', 'Created Date', 'Status'];
  const rows = filteredTasks.map(task => [
    task.id,
    task.title,
    task.project_title || 'N/A',
    task.company || 'N/A',
    task.estimated_or_not || '-',
    task.task_priority || 'Low',
    task.first_name || 'N/A',
    task.created || 'N/A',
    task.task_status || 'Todo'
  ]);

  let csv = headers.join(',') + '\n';
  rows.forEach(row => {
    csv += row.map(cell => `"${cell}"`).join(',') + '\n';
  });

  const blob = new Blob([csv], { type: 'text/csv' });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'tasks_' + new Date().getTime() + '.csv';
  a.click();
  window.URL.revokeObjectURL(url);
}

// Open task modal
function openTaskModal(taskId, task) {
  const modal = document.getElementById('ticketModalDetail');
  
  // Update title and status
  document.getElementById('modalTaskTitle').textContent = task.title || 'N/A';
  document.getElementById('modalTaskStatus').textContent = task.task_status || 'N/A';
  
  // Update detail fields
  document.getElementById('modalProject').textContent = task.project_title || 'N/A';
  document.getElementById('modalIssueType').textContent = task.issue_type_text || 'N/A';
  document.getElementById('modalIssueDate').textContent = task.due_date || 'N/A';
  document.getElementById('modalService').textContent = task.service || 'N/A';
  document.getElementById('modalEstimate').textContent = task.estimated_or_not || '-';
  document.getElementById('modalAdditionalMail').textContent = task.additional_mail || 'N/A';
  
  // Update priority with appropriate class
  const priorityElement = document.getElementById('modalPriority');
  priorityElement.textContent = task.task_priority || 'Low';
  priorityElement.className = 'right-col ' + (priorityMap[task.task_priority] || 'low-pr');
  
  // Update task users/consultants if available
  if (task.task_users && task.task_users.length > 0) {
    const consultantsDiv = document.getElementById('modalConsultants');
    consultantsDiv.innerHTML = '';
    
    task.task_users.forEach(user => {
      const avatar = document.createElement('div');
      avatar.className = 'avatar';
      avatar.title = user.first_name + ' ' + user.last_name;
      const img = document.createElement('img');
      img.src = baseUrl + 'assets/newdesign/img/mrs1.webp';
      img.alt = user.first_name || 'User';
      avatar.appendChild(img);
      consultantsDiv.appendChild(avatar);
    });
  } else {
    document.getElementById('modalConsultants').innerHTML = '<div class="text-muted">No consultants assigned</div>';
  }
  
  // Update attachment
  const attachmentElement = document.getElementById('modalAttachment');
  if (task.attachment) {
    attachmentElement.textContent = task.attachment;
    attachmentElement.href = task.attachment_url || '#';
  } else {
    attachmentElement.textContent = 'No attachment';
    attachmentElement.href = '#';
  }
  
  // Fetch and populate timesheet data for this task
  fetchTaskTimesheet(taskId);
}

// Fetch timesheet entries for a specific task
function fetchTaskTimesheet(taskId) {
  $.ajax({
    url: baseUrl + 'projects/get_timesheet_by_task',
    type: 'POST',
    dataType: 'json',
    data: { task_id: taskId },
    success: function(response) {
      if (!response.error && response.data && response.data.length > 0) {
        populateTimesheetTable(response.data);
      } else {
        // Show empty state
        const tbody = document.querySelector('#timesheet tbody');
        if (tbody) {
          tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No timesheet entries found</td></tr>';
        }
      }
    },
    error: function(xhr, status, error) {
      console.error('Error fetching timesheet:', error);
      const tbody = document.querySelector('#timesheet tbody');
      if (tbody) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Error loading timesheet</td></tr>';
      }
    }
  });
}

// Populate timesheet table with data
function populateTimesheetTable(timesheetEntries) {
  const tbody = document.querySelector('#timesheet tbody');
  if (!tbody) return;
  
  tbody.innerHTML = '';
  
  if (!timesheetEntries || timesheetEntries.length === 0) {
    const emptyRow = document.createElement('tr');
    emptyRow.innerHTML = '<td colspan="4" class="text-center text-muted">No timesheet entries found</td>';
    tbody.appendChild(emptyRow);
    return;
  }
  
  timesheetEntries.forEach(entry => {
    const row = document.createElement('tr');
    
    // Consultant name
    const consultantCell = document.createElement('td');
    const consultantDiv = document.createElement('div');
    consultantDiv.className = 'd-flex align-items-center';
    
    // Use a default avatar - in production, you'd want to fetch the user's actual avatar
    const img = document.createElement('img');
    img.src = baseUrl + 'assets/newdesign/img/mrs1.webp';
    img.alt = 'Consultant';
    img.className = 'rounded-circle me-2';
    img.width = 40;
    img.height = 40;
    
    const nameSpan = document.createElement('span');
    nameSpan.className = 'tt-nm';
    nameSpan.textContent = entry.user || 'N/A';
    
    consultantDiv.appendChild(img);
    consultantDiv.appendChild(nameSpan);
    consultantCell.appendChild(consultantDiv);
    
    // Starting time
    const startingCell = document.createElement('td');
    startingCell.textContent = entry.starting_time ? formatDate(entry.starting_time) : 'N/A';
    
    // Ending time
    const endingCell = document.createElement('td');
    endingCell.textContent = entry.ending_time ? formatDate(entry.ending_time) : 'N/A';
    
    // Total time
    const totalCell = document.createElement('td');
    totalCell.className = 'text-center';
    
    if (entry.starting_time && entry.ending_time) {
      const totalHours = calculateHoursDifference(entry.starting_time, entry.ending_time);
      totalCell.textContent = totalHours;
    } else {
      totalCell.textContent = '-';
    }
    
    row.appendChild(consultantCell);
    row.appendChild(startingCell);
    row.appendChild(endingCell);
    row.appendChild(totalCell);
    
    tbody.appendChild(row);
  });
}

// Helper function to format date
function formatDate(dateStr) {
  if (!dateStr) return 'N/A';
  const date = new Date(dateStr);
  const options = { day: 'numeric', month: 'short', year: 'numeric' };
  return date.toLocaleDateString('en-US', options);
}

// Helper function to calculate hours difference
function calculateHoursDifference(startTime, endTime) {
  if (!startTime || !endTime) return '-';
  
  const start = new Date(startTime);
  const end = new Date(endTime);
  const diffMs = end - start;
  const diffHours = diffMs / (1000 * 60 * 60);
  
  return diffHours.toFixed(2);
}

// Edit task - open edit modal
function editTask(taskId, task) {
  console.log('Edit task:', taskId, task);
  
  // Populate edit form with task data
  document.getElementById('taskIdEdit').value = task.id || '';
  document.getElementById('ticketTitleEdit').value = task.title || '';
  document.getElementById('descriptionEdit').value = task.description || '';
  document.getElementById('issueTypeEdit').value = task.issue_type || '';
  
  // For service, use service_id if available, otherwise try to match by service name
  const serviceSelect = document.getElementById('selectServiceEdit');
  if (task.service_id) {
    serviceSelect.value = task.service_id || '';
  } else if (task.service) {
    // Try to find matching option by service name
    const options = serviceSelect.querySelectorAll('option');
    let found = false;
    options.forEach(option => {
      if (option.text === task.service || option.value === task.service) {
        serviceSelect.value = option.value;
        found = true;
      }
    });
    if (!found) {
      serviceSelect.value = task.service || '';
    }
  }
  
  // For issue type, use issue_type_id if available
  const issueTypeSelect = document.getElementById('issueTypeEdit');
  if (task.issue_type_id) {
    issueTypeSelect.value = task.issue_type_id || '';
  }
  
  document.getElementById('priorityEdit').value = task.priority || '';
  document.getElementById('dueDateEdit').value = task.due_date || '';
  document.getElementById('statusEdit').value = task.status || '';
  document.getElementById('additionalMailEdit').value = task.additional_mail || '';
  
  // Initialize datepicker for edit form
  const editDateInput = document.getElementById('dueDateEdit');
  if (editDateInput && $.fn.datepicker) {
    $(editDateInput).datepicker({
      format: 'dd-mm-yyyy',
      autoclose: true
    });
  }
  
  // Show edit modal
  const editModal = new bootstrap.Modal(document.getElementById('editTicketModal'));
  editModal.show();
}

// Delete task
function deleteTask(taskId, task) {
  // Store the taskId for use in confirmation
  window.pendingDeleteTaskId = taskId;
  
  // Show delete confirmation modal
  const deleteModal = new bootstrap.Modal(document.getElementById('ticketModalccdel'));
  deleteModal.show();
}

// Handle delete confirmation
document.addEventListener('DOMContentLoaded', function() {
  const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
  if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener('click', function() {
      const taskId = window.pendingDeleteTaskId;
      if (taskId) {
        console.log('Deleting task:', taskId);
        
        $.ajax({
          url: baseUrl + 'projects/delete_task',
          type: 'POST',
          dataType: 'json',
          data: { task_id: taskId },
          success: function(response) {
            // Hide the modal
            const deleteModal = bootstrap.Modal.getInstance(document.getElementById('ticketModalccdel'));
            if (deleteModal) deleteModal.hide();
            
            if (!response.error) {
              alert(response.message || 'Task deleted successfully');
              // Remove task from local array
              allTasks = allTasks.filter(t => t.id != taskId);
              applyFilters();
            } else {
              alert('Error: ' + (response.message || 'Failed to delete task'));
            }
          },
          error: function(xhr, status, error) {
            alert('Error: ' + error);
          }
        });
      }
    });
  }
});

// Handle create form submission
document.addEventListener('DOMContentLoaded', function() {
  const createForm = document.getElementById('createTicketForm');
  if (createForm) {
    createForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      // Get project ID - first try from hidden field, then from context, then from first task in list
      let projectId = document.getElementById('projectIdCreate').value || projectIdFromView || '';
      
      // If no project ID yet, try to use first available project from tasks
      if (!projectId && allTasks && allTasks.length > 0) {
        projectId = allTasks[0].project_id;
      }
      
      // If still no project ID, try from projectslist dropdown
      if (!projectId) {
        const projectSelects = document.querySelectorAll('.sel-wrapper select');
        if (projectSelects.length >= 3) {
          projectId = projectSelects[2].value; // Project dropdown is 3rd
        }
      }
      
      if (!projectId) {
        alert('Please select or navigate to a project first');
        return;
      }
      
      const formData = new FormData(createForm);
      formData.set('project_id', projectId);
      
      $.ajax({
        url: baseUrl + 'projects/create_task',
        type: 'POST',
        dataType: 'json',
        data: formData,
        contentType: false,
        processData: false,
        success: function(response) {
          if (!response.error) {
            alert('Task created successfully');
            createForm.reset();
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('ticketModal'));
            if (modal) modal.hide();
            // Reload tasks
            location.reload();
          } else {
            alert('Error: ' + (response.message || 'Failed to create task'));
          }
        },
        error: function(xhr, status, error) {
          alert('Error: ' + error);
        }
      });
    });
  }
  
  // Handle edit form submission
  const editForm = document.getElementById('editTicketForm');
  if (editForm) {
    editForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(editForm);
      
      $.ajax({
        url: baseUrl + 'projects/edit_task',
        type: 'POST',
        dataType: 'json',
        data: formData,
        contentType: false,
        processData: false,
        success: function(response) {
          if (!response.error) {
            alert('Task updated successfully');
            editForm.reset();
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editTicketModal'));
            if (modal) modal.hide();
            // Reload tasks
            location.reload();
          } else {
            alert('Error: ' + (response.message || 'Failed to update task'));
          }
        },
        error: function(xhr, status, error) {
          alert('Error: ' + error);
        }
      });
    });
  }
  
  // Initialize datepickers
  if ($.fn.datepicker) {
    $('#dueDatePickerCreate').datepicker({
      format: 'dd-mm-yyyy',
      autoclose: true
    });
    
    $('#dueDatePickerEdit').datepicker({
      format: 'dd-mm-yyyy',
      autoclose: true
    });
  }
  
  // Handle file input labels
  const attachCreateInput = document.getElementById('attachmentCreate');
  if (attachCreateInput) {
    attachCreateInput.addEventListener('change', function() {
      document.getElementById('attachmentNameCreate').textContent = this.files[0] ? this.files[0].name : '';
    });
  }
  
  const attachEditInput = document.getElementById('attachmentEdit');
  if (attachEditInput) {
    attachEditInput.addEventListener('change', function() {
      document.getElementById('attachmentNameEdit').textContent = this.files[0] ? this.files[0].name : '';
    });
  }
});

// Escape HTML
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
