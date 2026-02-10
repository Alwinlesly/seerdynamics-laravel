<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>{{ $page_title }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <link rel="shortcut icon" href="{{ asset('assets/uploads/logos/' . favicon()) }}">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Custom Styles -->
  <link rel="stylesheet" href="{{ asset('assets/css/login/style.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/login/newstyle.css') }}">

  @php $google_analytics = google_analytics(); @endphp
  @if($google_analytics)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $google_analytics }}"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '{{ $google_analytics }}');
    </script>
  @endif
</head>
<body>

<!-- Preloader -->
<div id="preloader" class="preloader-type-default">
  <div class="clear-loading loading-effect">
    <svg width="300px" height="200px" viewBox="0 0 187.3 93.7" preserveAspectRatio="xMidYMid meet">
      <path id="infinity-outline" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" 
        d="M93.9,46.4c9.3,9.5,13.8,17.9,23.5,17.9s17.5-7.8,17.5-17.5s-7.8-17.6-17.5-17.5c-9.7,0.1-13.3,7.2-22.1,17.1 c-8.9,8.8-15.7,17.9-25.4,17.9s-17.5-7.8-17.5-17.5s7.8-17.5,17.5-17.5S86.2,38.6,93.9,46.4z" />
      <path id="infinity-bg" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" 
        d="M93.9,46.4c9.3,9.5,13.8,17.9,23.5,17.9s17.5-7.8,17.5-17.5s-7.8-17.6-17.5-17.5c-9.7,0.1-13.3,7.2-22.1,17.1 c-8.9,8.8-15.7,17.9-25.4,17.9s-17.5-7.8-17.5-17.5s7.8-17.5,17.5-17.5S86.2,38.6,93.9,46.4z" />
    </svg>
  </div>
</div>

<!-- Background & Logos -->
<div class="background-image">
  <img src="{{ asset('assets/img/Seer_Background_image.jpg') }}" alt="Background">
</div>
<div class="top-right-logos">
  <img src="{{ asset('assets/img/seer_microsoft.png') }}" alt="Seer Dynamics Logo" />
</div>

<!-- Main Content -->
<div class="center-wrapper">
  <div class="scale-wrapper">
    <div class="login-container">

      <div class="logo-wrapper">
        <img src="{{ asset('assets/img/logo_Icon.png') }}" class="logo" alt="Icon" />
      </div>

      <h2>Welcome back</h2>
      <p class="subtitle">We are glad to see you again!</p>

      <form id="login" method="POST" action="{{ route('login') }}">
        @csrf
        <input id="identity" type="email" name="identity" placeholder="Email" required autofocus />
        <input id="password" type="password" name="password" placeholder="Password" required />

        <a href="#" class="forgot" id="modal-forgot-password">
          Forgot password?
        </a>

        <button type="submit">Login</button>

        <div class="result">
          @if(session('message'))
            <div class="alert alert-{{ session('message_type', 'info') }}">
              {{ session('message') }}
            </div>
          @endif
        </div>
      </form>

      <!-- Social + Stores -->
      <div class="social-store-section">
        <div class="social-icons">
          <a href="https://www.linkedin.com/company/seer-dynamics" target="_blank" class="icon-circle">
            <img src="{{ asset('assets/img/linkedin.png') }}" alt="LinkedIn">
          </a>
          <a href="https://www.seerdynamics.com" target="_blank" class="icon-circle">
            <img src="{{ asset('assets/img/global.png') }}" alt="Website">
          </a>
          <a href="mailto:contact@seerdynamics.com" class="icon-circle">
            <img src="{{ asset('assets/img/mail.png') }}" alt="Email">
          </a>
        </div>
        <div class="store-links">
          <a href="https://appsource.microsoft.com/en-us/marketplace/apps?search=seer%20dynamics%20technologies&page=1" target="_blank">
            <img src="{{ asset('assets/img/microsoft-badge.png') }}" alt="Microsoft Store" />
          </a>
          <a href="https://play.google.com/store/apps/developer?id=Seer+Dynamics+Technologies" target="_blank">
            <img src="{{ asset('assets/img/google-play-badge.png') }}" alt="Google Play" />
          </a>
          <a href="https://apps.apple.com/kh/developer/seerdynamics-consulting-private-limited/id1580792251" target="_blank">
            <img src="{{ asset('assets/img/app-store-badge.png') }}" alt="App Store" />
          </a>
        </div>
      </div>

      <p class="footer">{{ footer_text() }}</p>
    </div>
  </div>
</div>

<!-- Forgot Password Modal -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius: 10px;">
      <div class="modal-header" style="border-bottom: none; padding: 20px 20px 10px;">
        <h5 class="modal-title" style="font-weight: 600; color: #333;">Forgot Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding: 20px 30px 30px;">
        <form id="forgot-password-form" action="{{ url('auth/forgot-password') }}">
          @csrf
          <div class="mb-3">
            <label class="form-label" style="font-size: 14px; color: #666; font-weight: 500;">Email</label>
            <input type="email" 
                   class="form-control" 
                   placeholder="" 
                   name="identity" 
                   required
                   style="border: 1px solid #ddd; padding: 10px 15px; border-radius: 5px; font-size: 14px;">
          </div>
          
          <p style="font-size: 13px; color: #666; margin-bottom: 20px;">
            We will send a link to reset your password.
          </p>

          <div id="forgot-result" style="margin-bottom: 15px;"></div>

          <button type="button" 
                  class="btn w-100" 
                  id="send-reset-link"
                  style="background: #6f42c1; color: white; padding: 12px; border-radius: 5px; border: none; font-weight: 500; font-size: 14px;">
            Send
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<style>
/* Forgot Password Modal Styling */
#forgotPasswordModal .modal-content {
  box-shadow: 0 10px 40px rgba(0,0,0,0.15);
}

#forgotPasswordModal .btn-close {
  font-size: 12px;
  opacity: 0.5;
}

#forgotPasswordModal .btn-close:hover {
  opacity: 1;
}

#forgotPasswordModal .form-control:focus {
  border-color: #6f42c1;
  box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.15);
}

#send-reset-link:hover {
  background: #5a34a0 !important;
}

#send-reset-link:disabled {
  background: #9b7bc0 !important;
  cursor: not-allowed;
}
</style>

<!-- JavaScript - CDN -->
<script>
  var base_url = "{{ url('/') }}/";
</script>

<!-- jQuery CDN -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- iziToast CDN -->
<script src="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/js/iziToast.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/css/iziToast.min.css">

<script>
$(document).ready(function() {
  // Hide preloader
  setTimeout(function() {
    $('#preloader').fadeOut('slow');
  }, 500);

  // CSRF Token Setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Login Form Handler
  $('#login').on('submit', function(e) {
    e.preventDefault();
    
    var submitBtn = $(this).find('button[type="submit"]');
    var originalText = submitBtn.text();
    
    $.ajax({
      url: $(this).attr('action'),
      type: 'POST',
      data: $(this).serialize(),
      dataType: 'json',
      beforeSend: function() {
        submitBtn.prop('disabled', true).text('Please wait...');
        $('.result').html('<div class="alert alert-info">Logging in...</div>');
      },
      success: function(response) {
        if (response.error) {
          $('.result').html('<div class="alert alert-danger">' + response.message + '</div>');
          submitBtn.prop('disabled', false).text(originalText);
        } else {
          $('.result').html('<div class="alert alert-success">' + response.message + '</div>');
          setTimeout(function() {
            window.location.href = base_url + 'home';
          }, 500);
        }
      },
      error: function(xhr) {
        var errorMsg = 'An error occurred. Please try again.';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMsg = xhr.responseJSON.message;
        }
        $('.result').html('<div class="alert alert-danger">' + errorMsg + '</div>');
        submitBtn.prop('disabled', false).text(originalText);
      }
    });
  });

  // Forgot Password Modal
  $('#modal-forgot-password').on('click', function(e) {
    e.preventDefault();
    $('#forgotPasswordModal').modal('show');
  });

  // Send Reset Link
  $('#send-reset-link').on('click', function() {
    var form = $('#forgot-password-form');
    var email = form.find('input[name="identity"]').val();
    
    if (!email) {
      $('#forgot-result').html('<div class="alert alert-danger">Please enter your email.</div>');
      return;
    }

    $.ajax({
      url: form.attr('action'),
      type: 'POST',
      data: form.serialize(),
      dataType: 'json',
      beforeSend: function() {
        $('#send-reset-link').prop('disabled', true).text('Sending...');
        $('#forgot-result').html('<div class="alert alert-info">Sending reset link...</div>');
      },
      success: function(response) {
        if (response.error) {
          $('#forgot-result').html('<div class="alert alert-danger">' + response.message + '</div>');
          $('#send-reset-link').prop('disabled', false).text('Send');
        } else {
          $('#forgot-result').html('<div class="alert alert-success">' + response.message + '</div>');
          setTimeout(function() {
            $('#forgotPasswordModal').modal('hide');
            form[0].reset();
            $('#forgot-result').html('');
            $('#send-reset-link').prop('disabled', false).text('Send');
          }, 2000);
        }
      },
      error: function() {
        $('#forgot-result').html('<div class="alert alert-danger">An error occurred. Please try again.</div>');
        $('#send-reset-link').prop('disabled', false).text('Send');
      }
    });
  });
});
</script>

</body>
</html>