<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>{{ $page_title }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="shortcut icon" href="{{ asset('assets/uploads/logos/' . favicon()) }}">
  <link rel="stylesheet" href="{{ asset('assets/css/login/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/login/style.css') }}">
</head>
<body>
<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <h4>Reset Password</h4>
        </div>
        <div class="card-body">
          @if(session('message'))
            <div class="alert alert-{{ session('message_type', 'info') }}">
              {{ session('message') }}
            </div>
          @endif
          @if($errors->any())
            <div class="alert alert-danger">
              @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
              @endforeach
            </div>
          @endif
          <form method="POST" action="{{ url('auth/reset-password') }}">
            @csrf
            <input type="hidden" name="code" value="{{ $code }}">
            
            <div class="form-group">
              <label>New Password</label>
              <input type="password" name="new" class="form-control" required minlength="8">
            </div>
            <div class="form-group">
              <label>Confirm Password</label>
              <input type="password" name="new_confirmation" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Reset Password</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>