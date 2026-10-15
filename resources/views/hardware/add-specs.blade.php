@extends('layouts.app')

@section('title', 'Can I Run It? – PC Game Compatibility Checker | GameSpecMatch')

@section('meta_description', 'Test your PC against thousands of games. Select your CPU, GPU, and RAM to instantly discover which games your computer can run. Free compatibility checker.')

@push('styles')
<style>
/* =========================
   ADD-SPECS PAGE REDESIGN
   ========================= */

.hero-section{
  text-align:center;
  padding:48px 24px;
  background:
    radial-gradient(ellipse at top, rgba(110,168,255,.12), transparent 60%),
    linear-gradient(180deg, rgba(47,209,197,.08), transparent);
  border-radius:24px;
  margin-bottom:40px;
  position:relative;
  overflow:hidden;
}

.hero-section::before{
  content:'';
  position:absolute;
  top:-50%;
  left:-50%;
  width:200%;
  height:200%;
  background:radial-gradient(circle, rgba(110,168,255,.06) 0%, transparent 50%);
  animation:heroPulse 8s ease-in-out infinite;
}

@keyframes heroPulse{
  0%, 100%{transform:scale(1) rotate(0deg)}
  50%{transform:scale(1.1) rotate(180deg)}
}

.hero-content{
  position:relative;
  z-index:1;
}

.hero-section h1{
  margin:0 0 12px;
  font-size:clamp(28px, 5vw, 42px);
  font-weight:900;
  background:linear-gradient(135deg, #fff 0%, var(--primary) 100%);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  background-clip:text;
}

.hero-section p{
  margin:0 0 32px;
  font-size:clamp(14px, 2vw, 18px);
  color:var(--muted);
  max-width:600px;
  margin-left:auto;
  margin-right:auto;
}

.specs-card{
  border:1px solid var(--line);
  border-radius:20px;
  padding:32px;
  background:rgba(255,255,255,.04);
  backdrop-filter:blur(8px);
  margin-bottom:32px;
}

.specs-card h2{
  margin:0 0 24px;
  font-size:22px;
  display:flex;
  align-items:center;
  gap:10px;
}

.specs-form{
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(250px,1fr));
  gap:20px;
}

.form-group{
  display:flex;
  flex-direction:column;
}

.form-group label{
  display:block;
  margin-bottom:8px;
  font-size:13px;
  font-weight:600;
  color:var(--muted);
}

.form-group select,
.form-group input{
  width:100%;
  padding:12px 14px;
  border-radius:12px;
  border:1px solid var(--line);
  background:rgba(255,255,255,.06);
  color:var(--text);
  font-size:14px;
  transition:all .2s;
}

.form-group select:focus,
.form-group input:focus{
  outline:none;
  border-color:var(--primary);
  box-shadow:0 0 0 3px rgba(110,168,255,.1);
}

.form-group select{
  cursor:pointer;
  appearance:none;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 14 14'%3E%3Cpath fill='%23a9b4d0' d='M7 10L2 5h10z'/%3E%3C/svg%3E");
  background-repeat:no-repeat;
  background-position:right 14px center;
  background-size:14px;
  padding-right:40px;
}

.form-group select option{
  background:#0f1a2f;
  color:#e9eefc;
  padding:10px;
}

.form-group select optgroup{
  background:#0a0f1a;
  color:#6ea8ff;
  font-weight:700;
  font-size:12px;
  text-transform:uppercase;
  letter-spacing:.5px;
}

.submit-btn{
  grid-column:1 / -1;
  padding:16px 32px;
  border:none;
  border-radius:14px;
  background:linear-gradient(135deg, var(--primary), var(--accent));
  color:#fff;
  font-weight:700;
  font-size:16px;
  cursor:pointer;
  transition:all .3s;
  box-shadow:0 6px 24px rgba(110,168,255,.3);
}

.submit-btn:hover{
  transform:translateY(-2px);
  box-shadow:0 8px 32px rgba(110,168,255,.4);
}

.submit-btn:active{
  transform:translateY(0);
}

.how-it-works{
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));
  gap:24px;
  margin-bottom:48px;
}

.step-card{
  padding:28px 24px;
  border:1px solid var(--line);
  border-radius:16px;
  background:rgba(255,255,255,.04);
  text-align:center;
  position:relative;
  transition:all .3s;
}

.step-card:hover{
  transform:translateY(-4px);
  border-color:var(--primary);
  background:rgba(110,168,255,.08);
}

.step-number{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  width:48px;
  height:48px;
  border-radius:50%;
  background:linear-gradient(135deg, var(--primary), var(--accent));
  color:#fff;
  font-size:24px;
  font-weight:900;
  margin-bottom:16px;
}

.step-card h3{
  margin:0 0 8px;
  font-size:18px;
  font-weight:700;
}

.step-card p{
  margin:0;
  font-size:14px;
  color:var(--muted);
  line-height:1.5;
}

.existing-hardware{
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));
  gap:16px;
  padding:20px;
  border:1px solid rgba(110,168,255,.3);
  border-radius:16px;
  background:linear-gradient(135deg, rgba(110,168,255,.1), rgba(47,209,197,.08));
  margin-bottom:24px;
}

.hw-item{
  display:flex;
  flex-direction:column;
  gap:4px;
}

.hw-label{
  font-size:11px;
  text-transform:uppercase;
  letter-spacing:.5px;
  color:var(--muted);
  font-weight:600;
}

.hw-value{
  font-size:15px;
  font-weight:700;
  color:var(--text);
}

.edit-btn{
  padding:10px 20px;
  border-radius:10px;
  border:1px solid var(--primary);
  background:rgba(110,168,255,.1);
  color:var(--primary);
  font-weight:600;
  cursor:pointer;
  transition:all .2s;
}

.edit-btn:hover{
  background:var(--primary);
  color:#fff;
}

@media(max-width:768px){
  .specs-form{
    grid-template-columns:1fr;
  }
  .how-it-works{
    grid-template-columns:1fr;
  }
}

/* Loading Spinner */
.loading-spinner{
  width:60px;
  height:60px;
  border:6px solid rgba(110,168,255,.2);
  border-top-color:var(--primary);
  border-radius:50%;
  animation:spin 1s linear infinite;
  margin:0 auto;
}

@keyframes spin{
  to{transform:rotate(360deg)}
}

/* Validation feedback */
.form-group.error select,
.form-group.error input{
  border-color:#ff4444;
  box-shadow:0 0 0 3px rgba(255,68,68,.1);
}

.error-message{
  color:#ff4444;
  font-size:12px;
  margin-top:4px;
  display:none;
}

.form-group.error .error-message{
  display:block;
}

/* Describe / detect */
.assist{display:grid; grid-template-columns:minmax(0, 3fr) minmax(0, 2fr); gap:20px; margin-bottom:28px;
  padding:20px; border:1px solid var(--line); border-radius:14px; background:rgba(47,209,197,.05)}
.assist label{display:block; font-weight:700; margin-bottom:10px}
.assist textarea{width:100%; min-height:92px; padding:12px 14px; border-radius:10px; border:1px solid var(--line);
  background:rgba(255,255,255,.04); color:var(--text); font:inherit; font-size:15px; line-height:1.5; resize:vertical}
.assist textarea:focus{outline:none; border-color:var(--primary)}
.assist-actions{display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-top:12px}
.assist-or{color:var(--muted); font-size:13px}
.assist-result{border-left:1px solid var(--line); padding-left:20px}
.assist-result h4{font-size:15px; margin-bottom:10px}
.assist-result ul{list-style:none; display:grid; gap:8px; font-size:14px}
.assist-result li{display:flex; gap:8px; align-items:baseline; flex-wrap:wrap}
.assist-result li b{min-width:40px; color:var(--muted); font-weight:600}
.how{font-size:11px; font-weight:700; padding:2px 7px; border-radius:999px; white-space:nowrap}
.how-text{background:rgba(78,255,187,.1); color:var(--green)}
.how-ai{background:rgba(47,209,197,.14); color:var(--accent)}
.how-browser{background:rgba(110,168,255,.14); color:var(--primary)}
.assist-notes{margin-top:10px}
.assist-notes li{color:var(--orange); font-size:13px}
.assist-hint{color:var(--muted); font-size:13px; margin-top:12px}
.filled{box-shadow:0 0 0 3px rgba(78,255,187,.35) !important; transition:box-shadow .4s}
@media(max-width:800px){
  .assist{grid-template-columns:1fr}
  .assist-result{border-left:0; padding-left:0; border-top:1px solid var(--line); padding-top:16px}
}
</style>
@endpush

@section('content')
<div class="wrap">

  {{-- Hero Section --}}
  <div class="hero-section">
    <div class="hero-content">
      <h1>🎮 Can I Run It?</h1>
      <p>Pick your parts, or just describe your PC, and see every game it can run.</p>
    </div>
  </div>

  {{-- Existing Hardware Display --}}
  @isset($existingHardware)
    <div class="existing-hardware">
      <div class="hw-item">
        <span class="hw-label">Profile</span>
        <span class="hw-value">{{ $existingHardware->profile_name }}</span>
      </div>
      <div class="hw-item">
        <span class="hw-label">CPU</span>
        <span class="hw-value">{{ $existingHardware->cpu_model ?? 'N/A' }}</span>
      </div>
      <div class="hw-item">
        <span class="hw-label">GPU</span>
        <span class="hw-value">{{ $existingHardware->gpu_model ?? 'N/A' }}</span>
      </div>
      <div class="hw-item">
        <span class="hw-label">RAM</span>
        <span class="hw-value">{{ $existingHardware->ram_gb ?? 0 }} GB</span>
      </div>
      <div class="hw-item">
        <span class="hw-label">Platform</span>
        <span class="hw-value">{{ $platformOptions[$existingHardware->platform] ?? 'PC' }}</span>
      </div>
      <div class="hw-item" style="display:flex; align-items:flex-end">
        <button type="button" class="edit-btn" onclick="toggleSpecsForm()">
          ✏️ Update Specs
        </button>
        <a href="{{ route('upgrade') }}" class="edit-btn" style="text-decoration:none; margin-left:8px">⬆️ What to upgrade</a>
      </div>
    </div>
  @endisset

  {{-- How It Works --}}
  @if(!isset($existingHardware))
    <div class="how-it-works">
      <div class="step-card">
        <div class="step-number">1</div>
        <h3>Enter Your PC Specs</h3>
        <p>Pick your CPU, GPU, RAM and OS, or describe your PC in your own words and let the form fill itself</p>
      </div>

      <div class="step-card">
        <div class="step-number">2</div>
        <h3>Instant Analysis</h3>
        <p>Your parts are scored on the same scale as each game's minimum and recommended requirements</p>
      </div>

      <div class="step-card">
        <div class="step-number">3</div>
        <h3>Get Your Results</h3>
        <p>See which games you can run, with performance predictions and upgrade recommendations</p>
      </div>
    </div>
  @endif

  {{-- Specs Form --}}
  <div class="specs-card" id="specsForm" @isset($existingHardware) style="display:none" @endisset>
    <h2>
      <span>💻</span>
      {{ isset($existingHardware) ? 'Update Your Hardware' : 'Enter Your Hardware' }}
    </h2>

    {{-- Fill the form from a description, or from what this browser can see --}}
    <div class="assist" data-describe-url="{{ route('hardware.describe') }}">
      <div class="assist-describe">
        <label for="describeText">✍️ Describe your PC in your own words</label>
        <textarea id="describeText" maxlength="2000" rows="3"
          placeholder="e.g. ryzen 5 3600, 1660 super, 16 gigs of ram, win 10 — or paste from dxdiag / System Information"></textarea>
        <div class="assist-actions">
          <button type="button" class="btn-solid" id="describeBtn">Read my specs</button>
          <span class="assist-or">or</span>
          <button type="button" class="btn-quiet" id="detectHardwareBtn">Detect from this browser</button>
        </div>
      </div>
      <div class="assist-result" id="assistResult" hidden>
        <h4 id="assistTitle">What we read</h4>
        <ul id="assistParts"></ul>
        <ul class="assist-notes" id="assistNotes"></ul>
        <p class="assist-hint">Check the form below, change anything that is off, then save.</p>
      </div>
    </div>

    <form method="POST" action="{{ route('hardware.store') }}" class="specs-form">
      @csrf

      {{-- Profile Name --}}
      <div class="form-group">
        <label for="profile_name">Profile Name</label>
        <input
          type="text"
          id="profile_name"
          name="profile_name"
          value="{{ old('profile_name', $existingHardware->profile_name ?? 'My PC') }}"
          placeholder="e.g., Gaming Rig, Work Laptop"
          required>
      </div>

      {{-- CPU Selector --}}
      <div class="form-group">
        <label for="cpu_id">Processor (CPU)</label>
        <select id="cpu_id" name="cpu_id" required>
          <option value="">Select your CPU...</option>
          @foreach($cpus as $manufacturer => $cpuList)
            <optgroup label="{{ strtoupper(str_replace('_', ' ', $manufacturer)) }}">
              @foreach($cpuList as $cpu)
                <option value="{{ $cpu->id }}"
                  {{ old('cpu_id', $existingHardware->cpu_id ?? '') == $cpu->id ? 'selected' : '' }}>
                  {{ $cpu->name }}
                </option>
              @endforeach
            </optgroup>
          @endforeach
        </select>
      </div>

      {{-- GPU Selector --}}
      <div class="form-group">
        <label for="gpu_id">Graphics Card (GPU)</label>
        <select id="gpu_id" name="gpu_id" required>
          <option value="">Select your GPU...</option>
          @foreach($gpus as $manufacturer => $gpuList)
            <optgroup label="{{ strtoupper($manufacturer) }}">
              @foreach($gpuList as $gpu)
                <option value="{{ $gpu->id }}"
                  {{ old('gpu_id', $existingHardware->gpu_id ?? '') == $gpu->id ? 'selected' : '' }}>
                  {{ $gpu->name }}
                </option>
              @endforeach
            </optgroup>
          @endforeach
        </select>
      </div>

      {{-- RAM Selector --}}
      <div class="form-group">
        <label for="ram_gb">Memory (RAM)</label>
        <select id="ram_gb" name="ram_gb" required>
          <option value="">Select RAM amount...</option>
          @foreach($ramOptions as $value => $label)
            <option value="{{ $value }}"
              {{ old('ram_gb', $existingHardware->ram_gb ?? '') == $value ? 'selected' : '' }}>
              {{ $label }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Platform/OS Selector --}}
      <div class="form-group">
        <label for="platform">Operating System</label>
        <select id="platform" name="platform" required>
          <option value="">Select OS...</option>
          @foreach($platformOptions as $value => $label)
            <option value="{{ $value }}"
              {{ old('platform', $existingHardware->platform ?? 'windows_11') == $value ? 'selected' : '' }}>
              {{ $label }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Submit Button --}}
      <button type="submit" class="submit-btn" id="submitBtn">
        {{ isset($existingHardware) ? '🔄 Update Hardware & Re-scan' : '🚀 Find Compatible Games' }}
      </button>

    </form>
  </div>

  {{-- Loading Overlay --}}
  <div id="loadingOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; align-items:center; justify-content:center;">
    <div style="text-align:center; color:#fff;">
      <div class="loading-spinner"></div>
      <h2 style="margin:20px 0 10px; font-size:24px;">🔍 Scanning Games...</h2>
      <p style="color:var(--muted); font-size:16px;" id="loadingText">Analyzing your hardware compatibility</p>
      <p style="color:var(--primary); font-size:14px; margin-top:10px;">This may take a few seconds</p>
    </div>
  </div>

  {{-- Compatible Games Results --}}
  @if(!empty($compatibleGames) && $compatibleGames->count() > 0)
    <div style="margin-bottom:16px">
      <h2 style="margin:0 0 8px">🎯 Compatible Games</h2>
      <p style="margin:0; color:var(--muted); font-size:14px">
        Showing <strong style="color:var(--primary)">{{ $compatibleGames->count() }}</strong> compatible games
        <span style="color:var(--muted); font-size:13px">(most popular first, {{ $compatibleGames->perPage() }} per page)</span>
      </p>
    </div>

    <div id="gamesGrid" class="grid">
      @foreach($compatibleGames as $app)
        <a class="game-card" href="{{ $app->slug ? route('apps.show', ['app' => $app->slug]) : url('/game/' . $app->app_id) }}">
          <img class="thumb"
               src="{{ $app->header_image ?: 'https://placehold.co/600x300/1a2332/6EA8FF?text=' . urlencode($app->name) }}"
               alt="{{ $app->name }}"
               loading="lazy">

          <div class="card-content">
            <div class="title">{{ $app->name }}</div>

            <div class="meta">
              <span>{{ number_format($app->total_recommendations ?? 0) }} 👍</span>
              @if($app->release_date)
                <span class="meta-dot"></span>
                <span>{{ \Carbon\Carbon::parse($app->release_date)->format('Y') }}</span>
              @endif
            </div>

            <div class="badges">
              @if($app->is_free)
                <span class="pill free">Free to Play</span>
              @else
                @if(isset($app->pricing) && $app->pricing)
                  @if($app->pricing->discount_percent > 0)
                    <span class="pill discount">
                      -{{ $app->pricing->discount_percent }}%
                      ${{ number_format($app->pricing->final_price / 100, 2) }}
                    </span>
                  @else
                    <span class="pill paid">${{ number_format($app->pricing->final_price / 100, 2) }}</span>
                  @endif
                @endif
              @endif

              @if(isset($app->reviews) && $app->reviews && $app->reviews->total_reviews > 0)
                @php
                  $positivePercent = ($app->reviews->total_positive / $app->reviews->total_reviews) * 100;
                  $reviewClass = $positivePercent >= 80 ? 'positive' : ($positivePercent >= 50 ? 'mixed' : 'negative');
                @endphp
                <span class="pill {{ $reviewClass }}">
                  {{ round($positivePercent) }}% Positive
                </span>
              @endif
            </div>
          </div>
        </a>
      @endforeach
    </div>

    {{-- Pagination Links --}}
    <div class="pager">
      {{ $compatibleGames->links() }}
    </div>
  @endif

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/hardware-detector.js') }}"></script>
<script>
// Toggle specs form visibility
function toggleSpecsForm() {
  const form = document.getElementById('specsForm');
  const btn = event.target;

  if (form.style.display === 'none') {
    form.style.display = 'block';
    btn.innerHTML = '✖️ Cancel';
    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
  } else {
    form.style.display = 'none';
    btn.innerHTML = '✏️ Update Specs';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
}

// ---- Fill the form from a description, or from what this browser can see ----
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
const HOW = { text: ['found in your text', 'how-text'], ai: ['read by AI', 'how-ai'], browser: ['from this browser', 'how-browser'] };
const PLATFORM_NAMES = { windows_11: 'Windows 11', windows_10: 'Windows 10', macos: 'macOS', linux: 'Linux' };

function escapeHtml(s) {
  return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function setField(id, value) {
  const el = document.getElementById(id);
  const option = el && Array.from(el.options).find(o => o.value === String(value));
  if (!option) return false;
  el.value = option.value;
  el.classList.add('filled');
  setTimeout(() => el.classList.remove('filled'), 2500);
  return true;
}

function closestRam(gb) {
  const sizes = Array.from(document.getElementById('ram_gb').options).map(o => parseInt(o.value, 10)).filter(n => !isNaN(n));
  return sizes.reduce((a, b) => (Math.abs(b - gb) < Math.abs(a - gb) ? b : a), sizes[0]);
}

function showReading(r, title, forceHow = null) {
  const tag = how => (HOW[how] ? `<span class="how ${HOW[how][1]}">${HOW[how][0]}</span>` : '');
  const items = [];
  if (r.cpu && setField('cpu_id', r.cpu.id)) items.push(`<li><b>CPU</b> ${escapeHtml(r.cpu.name)} ${tag(forceHow || r.cpu.how)}</li>`);
  if (r.gpu && setField('gpu_id', r.gpu.id)) items.push(`<li><b>GPU</b> ${escapeHtml(r.gpu.name)} ${tag(forceHow || r.gpu.how)}</li>`);
  if (r.ram_gb) {
    const ram = closestRam(r.ram_gb);
    if (setField('ram_gb', ram)) items.push(`<li><b>RAM</b> ${ram} GB</li>`);
  }
  if (r.platform && setField('platform', r.platform)) items.push(`<li><b>OS</b> ${PLATFORM_NAMES[r.platform]}</li>`);

  document.getElementById('assistTitle').textContent = title;
  document.getElementById('assistParts').innerHTML = items.join('') || '<li>Nothing we could match.</li>';
  document.getElementById('assistNotes').innerHTML = (r.notes || []).map(n => `<li>${escapeHtml(n)}</li>`).join('');
  document.getElementById('assistResult').hidden = false;
}

async function readSpecs(text, useAi) {
  const res = await fetch(document.querySelector('.assist').dataset.describeUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    body: JSON.stringify({ text, ai: useAi }),
  });
  if (res.status === 429) throw new Error('Too many requests. Wait a minute and try again.');
  if (!res.ok) throw new Error('Could not read that. Pick your parts in the form below.');
  return res.json();
}

async function describePc() {
  const input = document.getElementById('describeText');
  const btn = document.getElementById('describeBtn');
  if (input.value.trim().length < 3) {
    input.focus();
    return;
  }
  btn.disabled = true;
  btn.textContent = 'Reading…';
  try {
    showReading(await readSpecs(input.value.trim(), true), 'What we read');
  } catch (e) {
    showReading({ notes: [e.message] }, 'What we read');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Read my specs';
  }
}

// A browser shows the graphics card (through WebGL) and the OS, but not the processor model or
// the exact amount of memory, so those two stay for the visitor to set.
async function detectHardware() {
  const btn = document.getElementById('detectHardwareBtn');
  btn.disabled = true;
  btn.textContent = 'Detecting…';
  try {
    const detector = new HardwareDetector();
    await detector.detectAll();
    const found = detector.getFormattedResults();
    saveDetectionData(detector.detectedHardware);

    // The WebGL renderer string names the card exactly, so the rules are enough here.
    const reading = found.gpu && found.gpu !== 'Not detected'
      ? await readSpecs(found.gpu, false)
      : { notes: ['This browser did not reveal your graphics card.'] };
    reading.cpu = null;
    reading.ram_gb = null;
    reading.platform = found.platformValue || null;
    reading.notes = (reading.notes || []).filter(n => !/processor/.test(n));
    reading.notes.push('Browsers cannot see your processor model or exact memory. Set those in the form, or describe your PC above.');
    showReading(reading, 'What this browser shows', 'browser');
  } catch (e) {
    showReading({ notes: ['Detection did not work in this browser. Describe your PC above or pick your parts below.'] }, 'What this browser shows');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Detect from this browser';
  }
}

// Keep what browsers report, so the catalogue can grow to cover unmatched cards.
async function saveDetectionData(raw) {
  try {
    await fetch('{{ route("api.hardware.detection") }}', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({
        gpu_raw: raw.gpuRenderer || raw.gpu,
        cpu_raw: null,
        cpu_cores: raw.cpuCores,
        cpu_threads: raw.cpuThreads,
        ram_gb: raw.ramEstimate,
        platform: raw.platform,
        detection_method: 'webgl',
      }),
    });
  } catch (e) {
    // Analytics only; never block the visitor.
  }
}

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('detectHardwareBtn')?.addEventListener('click', detectHardware);
  document.getElementById('describeBtn')?.addEventListener('click', describePc);

  // Form validation and loading feedback
  const form = document.querySelector('.specs-form');
  const submitBtn = document.getElementById('submitBtn');
  const loadingOverlay = document.getElementById('loadingOverlay');
  const loadingText = document.getElementById('loadingText');

  if (form && submitBtn) {
    form.addEventListener('submit', function(e) {
      // Clear previous errors
      document.querySelectorAll('.form-group.error').forEach(el => {
        el.classList.remove('error');
      });

      // Validate form
      let isValid = true;
      const requiredFields = ['cpu_id', 'gpu_id', 'ram_gb', 'platform'];

      requiredFields.forEach(fieldName => {
        const field = document.getElementById(fieldName);
        if (field && !field.value) {
          field.closest('.form-group').classList.add('error');
          isValid = false;
        }
      });

      if (!isValid) {
        e.preventDefault();
        alert('Please fill in all required fields');
        return false;
      }

      // Show loading overlay
      if (loadingOverlay) {
        loadingOverlay.style.display = 'flex';

        // Animate loading text
        let dots = 0;
        const loadingMessages = [
          'Analyzing your hardware compatibility',
          'Comparing against every game in the catalogue',
          'Calculating performance scores',
          'Finding compatible games'
        ];
        let messageIndex = 0;

        const interval = setInterval(() => {
          dots = (dots + 1) % 4;
          const dotsStr = '.'.repeat(dots);
          loadingText.textContent = loadingMessages[messageIndex] + dotsStr;

          if (dots === 0) {
            messageIndex = (messageIndex + 1) % loadingMessages.length;
          }
        }, 500);

        // Store interval ID to clear it if needed
        window.loadingInterval = interval;
      }

      // Disable submit button
      submitBtn.disabled = true;
      submitBtn.style.opacity = '0.6';
      submitBtn.style.cursor = 'not-allowed';
    });
  }

  // Auto-scroll to results if they exist
  @if(!empty($compatibleGames) && $compatibleGames->count() > 0)
    const gamesSection = document.getElementById('gamesGrid');
    if (gamesSection) {
      setTimeout(() => {
        gamesSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }, 300);
    }
  @endif
});
</script>
@endpush
