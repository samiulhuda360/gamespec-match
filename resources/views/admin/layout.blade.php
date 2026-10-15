<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Admin')</title>
  <style>
    body{font-family:Arial, sans-serif; margin:20px;}
    nav a{margin-right:12px;}
    table{border-collapse:collapse; width:100%;}
    th,td{border:1px solid #ddd; padding:8px;}
    th{background:#f5f5f5; text-align:left;}
    .card{border:1px solid #ddd; padding:14px; border-radius:10px; margin-bottom:14px;}
  </style>
</head>
<body>

  <nav style="margin-bottom:16px;">
    <a href="{{ route('admin.dashboard') }}">Dashboard</a>
    <a href="{{ route('admin.collections.index') }}">Collections</a>

    <form method="POST" action="{{ route('admin.logout') }}" style="display:inline;">
      @csrf
      <button type="submit">Logout</button>
    </form>
  </nav>

  @if(session('ok'))
    <div class="card" style="border-color:#5cb85c;">
      {{ session('ok') }}
    </div>
  @endif

  @yield('content')

</body>
</html>
