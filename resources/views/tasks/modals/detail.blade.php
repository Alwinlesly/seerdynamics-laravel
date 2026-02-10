<!-- Task Detail Modal -->
<div class="modal fade" id="taskDetailModal" tabindex="-1" aria-labelledby="taskDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-otd modal-header d-flex justify-content-between align-items-center gap-4">
        <h5 class="modal-title" id="taskDetailModalLabel">Ticket details</h5>
        <button type="button" class="timer-btn" id="timerBtn" data-task-id="">
          <span><img src="{{ asset('assets/img/clock.svg') }}" alt=""></span>
          <span id="timerBtnText">Start timer</span>
        </button>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body" id="taskDetailContent">
        <!-- Content will be loaded here via AJAX -->
        <div class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
