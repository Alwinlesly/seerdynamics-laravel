// Client-side rendering and filtering for tasks table
document.addEventListener('DOMContentLoaded', function(){
  // tasks variable will be injected from PHP as JSON
  if(typeof tasks === 'undefined') tasks = [];

  const tableBody = document.getElementById('ticketsTableBody');
  const searchInput = document.querySelector('.search-wrap input[type="search"]');
  const downloadBtn = document.querySelector('.btn-download');
  const filters = {
    customer: document.querySelectorAll('.sel-wrapper select')[0],
    projectType: document.querySelectorAll('.sel-wrapper select')[1],
    project: document.querySelectorAll('.sel-wrapper select')[2],
    status: document.querySelectorAll('.sel-wrapper select')[3],
    date: document.querySelectorAll('.sel-wrapper select')[4]
  };

  let currentData = tasks.slice();
  let sortState = {key: 'id', dir: 'desc'};
  // Pagination
  let pageSize = 10;
  let currentPage = 1;

  function renderTable(data){
    tableBody.innerHTML = '';
    if(!data || data.length === 0){
      tableBody.innerHTML = '<tr><td colspan="10" class="text-center">No records found</td></tr>';
      updatePagination(0,0);
      return;
    }
    const total = data.length;
    const totalPages = Math.max(1, Math.ceil(total / pageSize));
    if(currentPage > totalPages) currentPage = totalPages;
    const start = (currentPage - 1) * pageSize;
    const end = start + pageSize;
    const pageSlice = data.slice(start, end);
    pageSlice.forEach(function(row){
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><span class="ticket-id" data-id="${row.id}" data-bs-toggle="modal" data-bs-target="#ticketModalDetail">#${row.id}</span></td>
        <td>${escapeHtml(row.title||'')}</td>
        <td>${escapeHtml(row.project_title||'')}</td>
        <td>${escapeHtml(row.company||'')}</td>
        <td class="text-center">${escapeHtml(String(row.estimated_or_not||''))}</td>
        <td>${priorityLabel(row.priority||row.task_priority)}</td>
        <td>${escapeHtml(row.first_name||'')}</td>
        <td>${escapeHtml(row.created||'')}</td>
        <td>${statusLabel(row.task_status)}</td>
        <td></td>
      `;
      tableBody.appendChild(tr);
    });
    updatePagination(currentPage, total);
    // attach click handlers to ticket ids to populate modal details
    document.querySelectorAll('.ticket-id').forEach(function(el){
      el.addEventListener('click', function(){
        const id = this.getAttribute('data-id');
        let row = tasks.find(r => String(r.id) === String(id));
        if(row) populateTicketModal(row);
      });
    });
  }

  function priorityLabel(p){
    if(!p) return '';
    p = p.toLowerCase();
    if(p.indexOf('high')!==-1) return '<span class="high-pr">High</span>';
    if(p.indexOf('med')!==-1 || p.indexOf('medium')!==-1) return '<span class="med-pr">Medium</span>';
    return '<span class="low-pr">Low</span>';
  }
  function statusLabel(s){
    if(!s) return '';
    s = s.toLowerCase();
    if(s.indexOf('completed')!==-1) return '<span class="status completed">Completed</span>';
    if(s.indexOf('in progress')!==-1 || s.indexOf('in-progress')!==-1) return '<span class="status in-progress">In progress</span>';
    if(s.indexOf('archived')!==-1) return '<span class="status archived">Archived</span>';
    if(s.indexOf('open')!==-1) return '<span class="status open">Open</span>';
    if(s.indexOf('closed')!==-1) return '<span class="status closed">Closed</span>';
    return '<span class="status">'+escapeHtml(s)+'</span>';
  }

  function escapeHtml(unsafe) {
    return unsafe
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/\"/g, "&quot;")
         .replace(/'/g, "&#039;");
 }

  function applyFilters(){
    let filtered = tasks.slice();
    const q = (searchInput && searchInput.value) ? searchInput.value.toLowerCase() : '';
    if(q){
      filtered = filtered.filter(r=> (r.title||'').toLowerCase().includes(q) || (r.project_title||'').toLowerCase().includes(q) || (r.company||'').toLowerCase().includes(q));
    }
    const cust = filters.customer ? filters.customer.value : '';
    if(cust){ filtered = filtered.filter(r=>String(r.customer_id||r.customer||'')===String(cust)); }
    const ptype = filters.projectType ? filters.projectType.value : '';
    if(ptype){ filtered = filtered.filter(r=>String(r.project_type_id||'')===String(ptype)); }
    const proj = filters.project ? filters.project.value : '';
    if(proj){ filtered = filtered.filter(r=>String(r.project_id||r.project||'')===String(proj)); }
    const status = filters.status ? filters.status.value : '';
    if(status){ filtered = filtered.filter(r=>String(r.task_status_id||r.status||'')===String(status) || String(r.task_status||'').toLowerCase().includes(String(status).toLowerCase())); }
    // date filter (basic)
    const dateSel = filters.date ? filters.date.value : '';
    if(dateSel){
      const now = new Date();
      if(dateSel==='today'){
        filtered = filtered.filter(r=> sameDay(new Date(r.created)) );
      }else if(dateSel==='7'){
        const seven = new Date(); seven.setDate(now.getDate()-7);
        filtered = filtered.filter(r=> new Date(r.created) >= seven );
      }
    }
    currentData = filtered;
    currentPage = 1; // reset to first page when filtering
    sortAndRender();
  }

  function sameDay(d){
    if(!d) return false;
    const nd = new Date(d);
    const now = new Date();
    return nd.getFullYear()===now.getFullYear() && nd.getMonth()===now.getMonth() && nd.getDate()===now.getDate();
  }

  function sortAndRender(){
    const key = sortState.key; const dir = sortState.dir;
    currentData.sort((a,b)=>{
      let va = (a[key]===undefined||a[key]===null)?'':a[key];
      let vb = (b[key]===undefined||b[key]===null)?'':b[key];
      if(typeof va === 'string') va = va.toLowerCase();
      if(typeof vb === 'string') vb = vb.toLowerCase();
      if(va < vb) return dir==='asc'?-1:1;
      if(va > vb) return dir==='asc'?1:-1;
      return 0;
    });
    renderTable(currentData);
  }

  // Pagination helpers
  function updatePagination(page, totalRecords){
    const currentTotalEl = document.querySelector('.current-total');
    const mutedEl = document.querySelector('.pagination .text-muted');
    const prevBtn = document.querySelector('.pagination button[disabled]') ? document.querySelector('.pagination button[disabled]') : document.querySelector('.pagination button');
    const buttons = document.querySelectorAll('.pagination button');
    // update display
    if(currentTotalEl) currentTotalEl.textContent = page || 0;
    if(mutedEl) mutedEl.textContent = 'of ' + (totalRecords || 0);
    // enable/disable next/prev
    const prev = document.querySelector('.pagination button:first-of-type');
    const next = document.querySelector('.pagination .next-btn');
    const totalPages = Math.max(1, Math.ceil((totalRecords||0)/pageSize));
    if(prev) prev.disabled = page <= 1;
    if(next) next.disabled = page >= totalPages;
  }

  // prev/next handlers
  const prevButton = document.querySelector('.pagination button:first-of-type');
  const nextButton = document.querySelector('.pagination .next-btn');
  if(prevButton) prevButton.addEventListener('click', function(){ if(currentPage>1){ currentPage--; sortAndRender(); } });
  if(nextButton) nextButton.addEventListener('click', function(){ const totalPages = Math.max(1, Math.ceil(currentData.length / pageSize)); if(currentPage<totalPages){ currentPage++; sortAndRender(); } });

  // Hook up filters
  if(searchInput) searchInput.addEventListener('input', debounce(applyFilters, 200));
  Object.values(filters).forEach(function(el){ if(el) el.addEventListener('change', applyFilters); });

  // Sorting by clicking header
  document.querySelectorAll('.my-table thead th').forEach(function(th){
    th.style.cursor = 'pointer';
    th.addEventListener('click', function(){
      const key = th.getAttribute('data-key');
      if(!key) return;
      if(sortState.key===key) sortState.dir = (sortState.dir==='asc')?'desc':'asc'; else { sortState.key = key; sortState.dir='asc'; }
      sortAndRender();
    });
  });

  // Download CSV
  if(downloadBtn) downloadBtn.addEventListener('click', function(){
    const csv = toCSV(currentData);
    const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'tickets-list.csv';
    document.body.appendChild(link);
    link.click();
    link.remove();
  });

  function toCSV(arr){
    if(!arr || !arr.length) return '';
    const keys = ['id','title','project_title','company','estimated_or_not','priority','first_name','created','task_status'];
    const lines = [keys.join(',')];
    arr.forEach(r=>{
      const row = keys.map(k=> '"'+String((r[k]||'')).replace(/"/g,'""')+'"');
      lines.push(row.join(','));
    });
    return lines.join('\n');
  }

  function populateTicketModal(row){
    try{
      document.getElementById('modal_ticket_title').textContent = row.title || '';
      document.getElementById('modal_project').textContent = row.project_title || '';
      // more fields can be set here if needed
    }catch(e){console.warn(e)}
  }

  function debounce(fn, wait){ let t; return function(){ clearTimeout(t); t=setTimeout(()=>fn.apply(this,arguments), wait); }; }

  // initialize table
  renderTable(tasks);

  // datepicker in modal
  $('#datepicker').datepicker({ format: 'dd-mm-yyyy', autoclose: true, container: '#ticketModal' });
  // profile popover
  var popoverTrigger = document.getElementById('profilePopover');
  if(popoverTrigger) new bootstrap.Popover(popoverTrigger);

});
