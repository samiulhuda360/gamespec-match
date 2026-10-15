<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Dashboard</title>
</head>
<body style="font-family: system-ui; max-width: 900px; margin: 40px auto;">
  <div style="display:flex; justify-content:space-between; align-items:center;">
    <h1>Admin Dashboard</h1>

    <form method="POST" action="/admin/logout">
      @csrf
      <button type="submit" style="padding:8px 12px;">Logout</button>
    </form>
  </div>

  <p>✅ You are logged in.</p>

  <h2>Menu</h2>
  <ul>
    <li><a href="{{ route('admin.collections.index') }}">Manage Collections</a></li>
    <li><a href="{{ route('admin.hardware-detections') }}">Hardware Detections (Crowdsourced Data)</a></li>
  </ul>

  <hr>

  <h2>Next</h2>
  <p>We’ll add “Collections” (yearly, weekly, latest) with SQL-created tables and an SEO template.</p>
</body>
</html>
