<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Collections</title>
</head>
<body style="font-family: system-ui; max-width: 1000px; margin: 40px auto;">
  <div style="display:flex; justify-content:space-between; align-items:center;">
    <h1>Collections</h1>
    <a href="{{ route('admin.collections.create') }}" style="padding:10px 12px; border:1px solid #ddd; border-radius:10px; text-decoration:none;">
      + New
    </a>
  </div>

  @if (session('ok'))
    <p style="color: green;">{{ session('ok') }}</p>
  @endif

  <table width="100%" cellpadding="10" cellspacing="0" border="1" style="border-collapse:collapse;">
    <thead>
  <tr>
    <th>Title</th>
    <th>Type</th>
    <th>Slug</th>
    <th>Published</th>
    <th>Actions</th>
  </tr>
</thead>

<tbody>
  @forelse($collections as $c)
    <tr>
      <td>{{ $c->title }}</td>
      <td>{{ $c->type }}</td>
      <td>{{ $c->slug }}</td>
      <td>{{ $c->is_published ? 'Yes' : 'No' }}</td>

      <td style="white-space: nowrap;">
        <a href="{{ route('admin.collections.edit', $c) }}">Edit</a>

        <form method="POST"
              action="{{ route('admin.collections.destroy', $c) }}"
              style="display:inline">
          @csrf
          @method('DELETE')
          <button type="submit" onclick="return confirm('Delete this collection?')">
            Delete
          </button>@if($c->is_published)
  <a href="{{ route('collections.show', $c->slug) }}" target="_blank">View</a>
@endif
        </form>
      </td>
    </tr>
  @empty
    <tr><td colspan="5">No collections yet.</td></tr>
  @endforelse
</tbody>

  </table>

  <div style="margin-top:14px;">
    {{ $collections->links() }}
  </div>
</body>
</html>
