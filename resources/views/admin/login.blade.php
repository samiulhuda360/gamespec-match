<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login</title>
</head>
<body style="font-family: system-ui; max-width: 420px; margin: 60px auto;">
  <h1>Admin Login</h1>

  @if ($errors->any())
    <div style="color: #b00020; margin: 12px 0;">
      {{ $errors->first() }}
    </div>
  @endif

  <form method="POST" action="/admin/login">
    @csrf
    <label>Password</label><br>
    <input type="password" name="password" style="width:100%; padding:10px; margin:8px 0;" autofocus>
    <button type="submit" style="padding:10px 14px;">Login</button>
  </form>
</body>
</html>
