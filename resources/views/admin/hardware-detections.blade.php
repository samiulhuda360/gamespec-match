<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Hardware Detections - Admin</title>
  <style>
    body { font-family: system-ui; max-width: 1200px; margin: 40px auto; padding: 0 20px; }
    .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
    .stat-card { background: #f0f9ff; border: 2px solid #0ea5e9; border-radius: 8px; padding: 20px; }
    .stat-value { font-size: 32px; font-weight: bold; color: #0369a1; }
    .stat-label { color: #64748b; font-size: 14px; margin-top: 4px; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
    th { background: #f8fafc; font-weight: 600; color: #475569; }
    tr:hover { background: #f8fafc; }
    .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
    .badge-success { background: #dcfce7; color: #166534; }
    .badge-warning { background: #fef3c7; color: #92400e; }
    .section { margin: 40px 0; }
    h2 { color: #1e293b; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; }
    a { color: #0ea5e9; text-decoration: none; }
    a:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <div style="display:flex; justify-content:space-between; align-items:center;">
    <h1>📊 Hardware Detections (Crowdsourced)</h1>
    <div>
      <a href="/admin" style="margin-right:15px;">← Back to Dashboard</a>
      <form method="POST" action="/admin/logout" style="display:inline;">
        @csrf
        <button type="submit" style="padding:8px 12px;">Logout</button>
      </form>
    </div>
  </div>

  <p>Track what hardware your users have. Use this data to prioritize which GPUs/CPUs to add to your database.</p>

  {{-- Stats Overview --}}
  <div class="stats">
    <div class="stat-card">
      <div class="stat-value">{{ number_format($stats['total_detections']) }}</div>
      <div class="stat-label">Total Detections</div>
    </div>
    <div class="stat-card">
      <div class="stat-value">{{ number_format($stats['unique_gpus']) }}</div>
      <div class="stat-label">Unique GPUs Detected</div>
    </div>
    <div class="stat-card">
      <div class="stat-value">{{ $stats['gpu_match_rate'] }}%</div>
      <div class="stat-label">GPU Match Rate</div>
    </div>
    <div class="stat-card">
      <div class="stat-value">{{ $stats['cpu_match_rate'] }}%</div>
      <div class="stat-label">CPU Match Rate</div>
    </div>
  </div>

  {{-- Unmatched GPUs - Priority List --}}
  <div class="section">
    <h2>🎮 Unmatched GPUs ({{ $unmatchedGPUs->count() }} types)</h2>
    <p>These GPUs were detected but not found in your database. Add the most common ones first.</p>

    @if($unmatchedGPUs->count() > 0)
      <table>
        <thead>
          <tr>
            <th style="width:60px;">Count</th>
            <th>GPU String (Raw Detection)</th>
            <th style="width:150px;">Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach($unmatchedGPUs as $gpu)
            <tr>
              <td><strong>{{ $gpu->count }}</strong></td>
              <td><code style="background:#f1f5f9; padding:4px 8px; border-radius:4px;">{{ $gpu->gpu_raw }}</code></td>
              <td>
                <span style="color:#64748b; font-size:13px;">Manual add needed</span>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @else
      <p style="color:#64748b;">No unmatched GPUs yet. All detections are matching!</p>
    @endif
  </div>

  {{-- Unmatched CPUs - Priority List --}}
  <div class="section">
    <h2>💻 Unmatched CPUs ({{ $unmatchedCPUs->count() }} types)</h2>
    <p>These CPUs were detected but not found in your database.</p>

    @if($unmatchedCPUs->count() > 0)
      <table>
        <thead>
          <tr>
            <th style="width:60px;">Count</th>
            <th>Cores</th>
            <th>Threads</th>
            <th>CPU String (if available)</th>
          </tr>
        </thead>
        <tbody>
          @foreach($unmatchedCPUs as $cpu)
            <tr>
              <td><strong>{{ $cpu->count }}</strong></td>
              <td>{{ $cpu->cpu_cores ?? 'N/A' }}</td>
              <td>{{ $cpu->cpu_threads ?? 'N/A' }}</td>
              <td>
                @if($cpu->cpu_raw)
                  <code style="background:#f1f5f9; padding:4px 8px; border-radius:4px;">{{ $cpu->cpu_raw }}</code>
                @else
                  <span style="color:#94a3b8;">Not available (WebGL limitation)</span>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @else
      <p style="color:#64748b;">No unmatched CPUs yet.</p>
    @endif
  </div>

  {{-- Recent Detections --}}
  <div class="section">
    <h2>🕐 Recent Detections (Last 20)</h2>
    <table>
      <thead>
        <tr>
          <th style="width:150px;">Date</th>
          <th>GPU</th>
          <th>Cores/Threads</th>
          <th>RAM</th>
          <th>Platform</th>
          <th style="width:80px;">Matched</th>
        </tr>
      </thead>
      <tbody>
        @foreach($recentDetections as $detection)
          <tr>
            <td style="font-size:13px; color:#64748b;">{{ $detection->created_at->format('M d, H:i') }}</td>
            <td style="font-size:13px;">{{ Str::limit($detection->gpu_raw ?? 'N/A', 50) }}</td>
            <td style="font-size:13px;">{{ $detection->cpu_cores ?? '?' }} / {{ $detection->cpu_threads ?? '?' }}</td>
            <td style="font-size:13px;">{{ $detection->ram_gb ?? '?' }} GB</td>
            <td style="font-size:13px;">{{ ucfirst(str_replace('_', ' ', $detection->platform ?? 'N/A')) }}</td>
            <td>
              @if($detection->gpu_matched)
                <span class="badge badge-success">✓</span>
              @else
                <span class="badge badge-warning">✗</span>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div style="margin-top:40px; padding:20px; background:#f8fafc; border-radius:8px;">
    <h3 style="margin-top:0;">💡 How to Use This Data</h3>
    <ol style="line-height:1.8;">
      <li><strong>Priority:</strong> Add GPUs/CPUs with the highest count first</li>
      <li><strong>Research:</strong> Google the GPU string to find the exact model name</li>
      <li><strong>Add to Database:</strong> Use your hardware seeder to add missing models</li>
      <li><strong>Re-run Matcher:</strong> The system will automatically match future detections</li>
    </ol>
  </div>
</body>
</html>
