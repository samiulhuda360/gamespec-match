<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- ⚠️ TESTING MODE: Block all search engines ⚠️ --}}
  <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
  <meta name="googlebot" content="noindex, nofollow">
  <meta name="bingbot" content="noindex, nofollow">

  {{-- Title --}}
  <title>@yield('title', 'GameSpecMatch – Can I Run It? PC Game Compatibility Checker')</title>

  {{-- Meta Description --}}
  <meta name="description" content="@yield('meta_description', 'Check if your PC can run any Steam game. Compare CPU, GPU, RAM, storage, and DirectX vs minimum and recommended requirements. Find games your system can actually run.')">

  {{-- Canonical URL --}}
  <link rel="canonical" href="@yield('canonical', url()->current())">

  {{-- Open Graph (Social) --}}
  <meta property="og:site_name" content="GameSpecMatch">
  <meta property="og:type" content="@yield('og_type', 'website')">
  <meta property="og:title" content="@yield('og_title', trim($__env->yieldContent('title', 'GameSpecMatch – Can I Run It?')) )">
  <meta property="og:description" content="@yield('og_description', trim($__env->yieldContent('meta_description', 'Check if your PC can run any Steam game.')) )">
  <meta property="og:url" content="@yield('og_url', url()->current())">
  <meta property="og:image" content="@yield('og_image', asset('images/og-default.png'))">

  {{-- Twitter Card --}}
  <meta name="twitter:card" content="@yield('twitter_card', 'summary_large_image')">
  <meta name="twitter:title" content="@yield('twitter_title', trim($__env->yieldContent('title', 'GameSpecMatch – Can I Run It?')) )">
  <meta name="twitter:description" content="@yield('twitter_description', trim($__env->yieldContent('meta_description', 'Check if your PC can run any Steam game.')) )">
  <meta name="twitter:image" content="@yield('twitter_image', asset('images/og-default.png'))">

  {{-- Structured Data (JSON-LD) --}}
  @stack('jsonld')

  {{-- GLOBAL CSS - Used across all pages --}}
  <style>
    /* ===== CSS Variables ===== */
    :root{
      --bg:#0b1220;
      --card:#0f1a2f;
      --text:#e9eefc;
      --muted:#a9b4d0;
      --line:rgba(255,255,255,.08);
      --primary:#6ea8ff;
      --primary-dark:#4a7bc8;
      --accent:#2fd1c5;
      --green:#4effbb;
      --orange:#ffcd6e;
      --red:#ff6b6b;
    }

    /* ===== Reset & Base ===== */
    *{box-sizing:border-box; margin:0; padding:0}
    body{
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      background:
        radial-gradient(1200px 600px at 20% 0%, rgba(110,168,255,.18), transparent 60%),
        radial-gradient(900px 500px at 90% 10%, rgba(47,209,197,.16), transparent 55%),
        var(--bg);
      color:var(--text);
      padding:24px 14px;
      min-height:100vh;
    }
    .wrap{max-width:1200px; margin:0 auto}

    /* ===== Navbar ===== */
    .nav{
      position:sticky; top:0; z-index:100;
      backdrop-filter:blur(10px);
      background:rgba(11,18,32,.75);
      border-bottom:1px solid var(--line);
      margin:-24px -14px 24px;
    }
    .nav-inner{
      max-width:1200px; margin:0 auto; padding:10px 14px;
      display:flex; align-items:center; justify-content:space-between; gap:12px;
    }
    .brand{
      font-weight:800; letter-spacing:.2px;
      color:var(--text); text-decoration:none;
    }
    .nav-center{display:flex; gap:14px}
    .nav-link{
      color:var(--muted); text-decoration:none;
      padding:8px 10px; border-radius:10px; transition:all .2s;
    }
    .nav-link:hover{color:var(--text); background:rgba(255,255,255,.06)}
    .nav-right{display:flex; align-items:center; gap:10px}
    .nav-btn{
      padding:8px 12px; border-radius:10px;
      border:1px solid var(--line);
      color:var(--text); text-decoration:none;
      background:rgba(255,255,255,.04); transition:all .2s;
    }
    .nav-btn:hover{border-color:rgba(110,168,255,.35)}

    /* ===== Grid Layout ===== */
    .grid{
      display:grid;
      grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));
      gap:16px;
    }

    /* ===== Unified Game Card ===== */
    .card, .game-card{
      display:flex; flex-direction:column;
      border:1px solid var(--line);
      border-radius:16px; overflow:hidden;
      background:linear-gradient(180deg, rgba(255,255,255,.08), rgba(255,255,255,.04));
      text-decoration:none; color:inherit;
      transition:all .3s ease;
      position:relative;
    }
    .card::before, .game-card::before{
      content:''; position:absolute;
      top:0; left:0; right:0; height:4px;
      background:linear-gradient(90deg, var(--primary), var(--accent));
      opacity:0; transition:opacity .3s;
    }
    .card:hover, .game-card:hover{
      transform:translateY(-6px);
      border-color:rgba(110,168,255,.4);
      box-shadow:0 16px 48px rgba(0,0,0,.2);
    }
    .card:hover::before, .game-card:hover::before{opacity:1}

    .thumb{
      width:100%; height:140px;
      object-fit:cover;
      background:rgba(255,255,255,.05);
      transition:transform .3s;
      flex-shrink:0;
    }
    .card:hover .thumb, .game-card:hover .thumb{transform:scale(1.05)}

    .card-content, .p{
      padding:14px;
      display:flex; flex-direction:column;
      gap:10px; flex:1;
    }

    .title{
      font-weight:700; font-size:16px; line-height:1.3;
      display:-webkit-box;
      -webkit-line-clamp:2;
      -webkit-box-orient:vertical;
      overflow:hidden;
    }

    .meta{
      color:var(--muted); font-size:13px;
      display:flex; align-items:center;
      gap:8px; flex-wrap:wrap;
    }
    .meta-dot{
      width:3px; height:3px;
      background:var(--muted);
      border-radius:50%;
    }

    /* ===== Badges / Pills ===== */
    .badges{
      display:flex; gap:8px;
      align-items:center; flex-wrap:wrap;
      margin-top:auto;
    }
    .pill{
      font-size:12px; padding:6px 12px;
      border-radius:999px; font-weight:600;
      letter-spacing:.3px; white-space:nowrap;
    }
    .pill.free{
      background:rgba(78,255,187,.15);
      color:var(--green);
      border:1px solid rgba(78,255,187,.3);
    }
    .pill.paid{
      background:rgba(255,205,110,.15);
      color:var(--orange);
      border:1px solid rgba(255,205,110,.3);
    }
    .pill.discount{
      background:linear-gradient(135deg, rgba(255,107,107,.2), rgba(255,205,110,.2));
      color:#FFB84D;
      border:1px solid rgba(255,205,110,.4);
      font-weight:700;
    }
    .pill.positive{
      background:rgba(78,255,187,.15);
      color:var(--green);
      border:1px solid rgba(78,255,187,.3);
    }
    .pill.mixed{
      background:rgba(255,205,110,.15);
      color:var(--orange);
      border:1px solid rgba(255,205,110,.3);
    }
    .pill.negative{
      background:rgba(255,107,107,.15);
      color:var(--red);
      border:1px solid rgba(255,107,107,.3);
    }

    /* ===== Page Headers ===== */
    .page-header{
      text-align:center;
      padding:40px 20px;
      border-radius:18px;
      margin-bottom:24px;
    }
    .page-header h1{
      margin:0 0 8px 0;
      font-size:36px;
      font-weight:900;
    }
    .page-header .sub-text{
      color:var(--muted);
      font-size:14px;
    }

    /* ===== Filters Bar ===== */
    .filters-bar{
      display:flex; gap:12px;
      align-items:center; flex-wrap:wrap;
      margin-bottom:20px; padding:16px;
      background:rgba(255,255,255,.04);
      border:1px solid var(--line);
      border-radius:14px;
    }
    .filters-bar label{
      font-size:13px;
      color:var(--muted);
      margin-right:6px;
    }
    .filters-bar select,
    .filters-bar input[type="text"],
    .filters-bar input[type="number"]{
      padding:8px 12px;
      border-radius:8px;
      border:1px solid var(--line);
      background:rgba(255,255,255,.06);
      color:var(--text);
      font-size:13px;
      outline:none;
    }
    
    /* Style dropdown options */
    select option{
      background:#0f1a2f;
      color:#e9eefc;
      padding:8px;
    }
    
    /* Custom dropdown arrow */
    select{
      appearance:none;
      background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23a9b4d0' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
      background-repeat:no-repeat;
      background-position:right 12px center;
      background-size:12px;
      padding-right:36px;
    }
    
    .filters-bar select:focus,
    .filters-bar input:focus{
      border-color:var(--primary);
    }
    .filters-bar button[type="submit"]{
      padding:8px 16px;
      border:none;
      border-radius:8px;
      background:var(--primary);
      color:white;
      cursor:pointer;
      font-weight:600;
      transition:all .2s;
    }
    .filters-bar button[type="submit"]:hover{
      opacity:.9;
      transform:translateY(-1px);
    }

    /* ===== Period/Category Tabs ===== */
    .period-tabs{
      display:flex; gap:8px;
      flex-wrap:wrap;
      justify-content:center;
      margin-bottom:20px;
    }
    .period-tab{
      padding:10px 20px;
      border-radius:10px;
      border:1px solid rgba(255,255,255,.1);
      background:rgba(255,255,255,.04);
      color:var(--text);
      text-decoration:none;
      font-size:14px;
      font-weight:500;
      transition:all .2s;
    }
    .period-tab:hover{
      background:rgba(255,255,255,.08);
      border-color:var(--primary);
    }
    .period-tab.active{
      background:var(--primary);
      border-color:var(--primary);
      color:white;
    }

    /* ===== Empty State ===== */
    .empty{
      padding:60px 20px;
      text-align:center;
      border:1px dashed var(--line);
      border-radius:16px;
    }
    .empty .emoji{
      font-size:64px;
      margin-bottom:16px;
    }

    /* ===== Pagination ===== */
    .pager{margin-top:24px}
    .steam-pager{
      display:flex; gap:6px;
      justify-content:center;
      align-items:center;
      flex-wrap:wrap;
    }
    .steam-pager .p{
      padding:8px 12px;
      border-radius:8px;
      border:1px solid var(--line);
      background:rgba(255,255,255,.04);
      color:var(--text);
      text-decoration:none;
      transition:all .2s;
      min-width:40px;
      text-align:center;
    }
    .steam-pager .p:hover:not(.disabled):not(.active){
      border-color:var(--primary);
      background:rgba(110,168,255,.1);
    }
    .steam-pager .p.active{
      background:var(--primary);
      border-color:var(--primary);
      color:white;
      font-weight:600;
    }
    .steam-pager .p.disabled{
      opacity:.3;
      cursor:not-allowed;
    }

    /* ===== Homepage Hero Section ===== */
    .hero{
      position:relative;
      text-align:center;
      padding:20px 20px 20px;
      background:radial-gradient(ellipse at top, rgba(110,168,255,.15), transparent 70%),
                 linear-gradient(180deg, rgba(47,209,197,.08), transparent);
      border-radius:24px;
      margin-bottom:48px;
      overflow:hidden;
    }
    .hero::before{
      content:'';
      position:absolute;
      top:-50%;
      left:-50%;
      width:200%;
      height:200%;
      background:radial-gradient(circle, rgba(110,168,255,.08) 0%, transparent 50%);
      animation:heroAnimPulse 8s ease-in-out infinite;
    }
    @keyframes heroAnimPulse{
      0%, 100%{transform:scale(1)}
      50%{transform:scale(1.1)}
    }
    .hero-content{
      position:relative;
      z-index:1;
    }
    .hero h1{
      margin:0 0 20px;
      font-size:clamp(32px, 6vw, 56px);
      font-weight:900;
      letter-spacing:-1px;
      line-height:1.1;
      background:linear-gradient(135deg, #fff 0%, var(--primary) 100%);
      -webkit-background-clip:text;
      -webkit-text-fill-color:transparent;
      background-clip:text;
    }
    .hero .tagline{
      font-size:clamp(16px, 2.5vw, 22px);
      color:rgba(255,255,255,.8);
      margin:0 0 16px;
      font-weight:500;
      line-height:1.4;
    }
    .hero .stats{
      display:flex;
      gap:32px;
      justify-content:center;
      flex-wrap:wrap;
      margin-top:32px;
    }
    .stat{
      text-align:center;
    }
    .stat-num{
      display:block;
      font-size:32px;
      font-weight:800;
      color:var(--primary);
      line-height:1;
    }
    .stat-label{
      font-size:13px;
      color:var(--muted);
      margin-top:4px;
    }

    /* ===== Homepage Search Bar ===== */
    .search-section{
      max-width:780px;
      margin:0 auto 56px;
    }
    .search-wrap{
      position:relative;
    }
    .search-input{
      width:100%;
      padding:20px 60px 20px 24px;
      border-radius:16px;
      border:2px solid rgba(110,168,255,.2);
      background:rgba(255,255,255,.06);
      backdrop-filter:blur(12px);
      color:var(--text);
      outline:none;
      font-size:16px;
      transition:all .2s ease;
      box-shadow:0 8px 32px rgba(0,0,0,.1);
    }
    .search-input:focus{
      border-color:var(--primary);
      box-shadow:0 8px 32px rgba(110,168,255,.2), 0 0 0 4px rgba(110,168,255,.1);
      background:rgba(255,255,255,.08);
    }
    .search-icon{
      position:absolute;
      right:20px;
      top:50%;
      transform:translateY(-50%);
      width:24px;
      height:24px;
      opacity:.5;
      pointer-events:none;
    }
/* ===== Simple 3-Column CTA Grid (REPLACE the .cta-grid-enhanced section) ===== */
.cta-grid-simple{
  display:grid;
  grid-template-columns:repeat(3, 1fr);
  gap:24px;
  max-width:1200px;
  margin:0 auto 64px;
}

.cta-card-new{
  padding:36px 32px;
  border:1px solid rgba(255,255,255,.12);
  border-radius:20px;
  background:linear-gradient(135deg, rgba(255,255,255,.08), rgba(255,255,255,.04));
  backdrop-filter:blur(8px);
  text-align:center;
  transition:all .3s ease;
  cursor:pointer;
  position:relative;
}

.cta-card-new:hover{
  transform:translateY(-6px);
  border-color:rgba(110,168,255,.4);
  box-shadow:0 16px 56px rgba(110,168,255,.25);
}

.cta-card-new .cta-icon{
  font-size:56px;
  margin-bottom:20px;
  display:block;
}

.cta-card-new h2{
  margin:0 0 12px;
  font-size:24px;
  font-weight:700;
  color:var(--text);
}

.cta-card-new p{
  margin:0 0 24px;
  color:var(--muted);
  font-size:15px;
  line-height:1.6;
  min-height:48px;
}

.cta-card-new .cta-btn{
  padding:14px 32px;
  font-size:15px;
}

/* Primary Card - Subtle emphasis */
.primary-card{
  border-color:rgba(110,168,255,.25);
  background:linear-gradient(135deg, rgba(110,168,255,.12), rgba(47,209,197,.08));
}

.primary-card:hover{
  border-color:var(--primary);
  box-shadow:0 16px 56px rgba(110,168,255,.3);
}

/* Deals Card - Green accent */
.deals-card{
  border-color:rgba(78,255,187,.25);
  background:linear-gradient(135deg, rgba(78,255,187,.1), rgba(110,168,255,.06));
}

.deals-card:hover{
  border-color:var(--green);
  box-shadow:0 16px 56px rgba(78,255,187,.25);
}

.deals-btn{
  background:linear-gradient(135deg, var(--green), var(--primary));
}

/* Responsive - Stack on smaller screens */
@media(max-width:968px){
  .cta-grid-simple{
    grid-template-columns:1fr;
    gap:20px;
  }
  
  .cta-card-new{
    padding:32px 28px;
  }
  
  .cta-card-new .cta-icon{
    font-size:48px;
  }
  
  .cta-card-new p{
    min-height:auto;
  }
}
    /* Hero CTA - Large primary card */
    .hero-cta{
      grid-column:span 8;
      padding:48px 40px;
      border:2px solid rgba(110,168,255,.4);
      border-radius:24px;
      background:
        linear-gradient(135deg, rgba(110,168,255,.18), rgba(47,209,197,.12)),
        linear-gradient(180deg, rgba(255,255,255,.08), rgba(255,255,255,.02));
      backdrop-filter:blur(12px);
      text-align:center;
      transition:all .3s ease;
      cursor:pointer;
      position:relative;
      overflow:hidden;
    }

    .hero-cta::before{
      content:'';
      position:absolute;
      top:-50%;
      left:-50%;
      width:200%;
      height:200%;
      background:radial-gradient(circle, rgba(110,168,255,.15) 0%, transparent 70%);
      animation:heroCtaPulse 4s ease-in-out infinite;
    }

    @keyframes heroCtaPulse{
      0%, 100%{transform:rotate(0deg) scale(1)}
      50%{transform:rotate(180deg) scale(1.1)}
    }

    .hero-cta-content{
      position:relative;
      z-index:1;
    }

    .hero-cta:hover{
      transform:translateY(-6px);
      border-color:var(--primary);
      box-shadow:0 20px 60px rgba(110,168,255,.3);
    }

    .hero-cta .cta-icon{
      font-size:72px;
      margin-bottom:24px;
      display:block;
      animation:bounce 2s ease-in-out infinite;
    }

    @keyframes bounce{
      0%, 100%{transform:translateY(0)}
      50%{transform:translateY(-10px)}
    }

    .hero-cta h2{
      margin:0 0 16px;
      font-size:32px;
      font-weight:900;
      background:linear-gradient(135deg, #fff 0%, var(--primary) 100%);
      -webkit-background-clip:text;
      -webkit-text-fill-color:transparent;
      background-clip:text;
    }

    .hero-cta p{
      margin:0 0 28px;
      color:rgba(255,255,255,.85);
      font-size:17px;
      line-height:1.6;
      max-width:500px;
      margin-left:auto;
      margin-right:auto;
    }

    .hero-cta .cta-btn{
      font-size:18px;
      padding:18px 48px;
      box-shadow:0 8px 32px rgba(110,168,255,.4);
    }

    .hero-cta .cta-btn:hover{
      transform:scale(1.08);
      box-shadow:0 12px 48px rgba(110,168,255,.5);
    }

    /* Secondary CTAs - Smaller cards */
    .secondary-cta{
      grid-column:span 4;
      padding:32px 28px;
      border:1px solid rgba(255,255,255,.12);
      border-radius:18px;
      background:linear-gradient(135deg, rgba(255,255,255,.08), rgba(255,255,255,.04));
      backdrop-filter:blur(8px);
      text-align:center;
      transition:all .3s ease;
      cursor:pointer;
    }

    .secondary-cta:hover{
      transform:translateY(-4px);
      border-color:rgba(110,168,255,.4);
      box-shadow:0 12px 40px rgba(0,0,0,.2);
    }

    .secondary-cta .cta-icon{
      font-size:52px;
      margin-bottom:20px;
      display:block;
    }

    .secondary-cta h2{
      margin:0 0 12px;
      font-size:22px;
      font-weight:700;
    }

    .secondary-cta p{
      margin:0 0 24px;
      color:var(--muted);
      font-size:14px;
      line-height:1.6;
    }

    .secondary-cta .cta-btn{
      padding:12px 28px;
      font-size:15px;
    }

    /* CTA Button Base */
    .cta-btn{
      display:inline-block;
      padding:14px 32px;
      border-radius:12px;
      background:linear-gradient(135deg, var(--primary), var(--accent));
      color:#fff;
      text-decoration:none;
      font-weight:700;
      border:none;
      transition:all .2s ease;
      cursor:pointer;
      box-shadow:0 4px 16px rgba(110,168,255,.3);
    }

    .cta-btn:hover{
      transform:scale(1.05);
      box-shadow:0 6px 24px rgba(110,168,255,.4);
    }

    /* Special styling for deals card */
    .deals-cta{
      border-color:rgba(78,255,187,.35);
      background:linear-gradient(135deg, rgba(78,255,187,.12), rgba(110,168,255,.08));
    }

    .deals-cta:hover{
      border-color:var(--green);
    }

    .deals-cta .cta-btn{
      background:linear-gradient(135deg, var(--green), var(--primary));
    }

    /* ===== Game Sections (Homepage) ===== */
    .section{
      margin-bottom:56px;
    }
    .section-header{
      display:flex;
      justify-content:space-between;
      align-items:center;
      margin-bottom:24px;
      padding-bottom:16px;
      border-bottom:2px solid rgba(255,255,255,.08);
    }
    .section-header h2{
      margin:0;
      font-size:28px;
      font-weight:800;
      display:flex;
      align-items:center;
      gap:12px;
    }
    .section-emoji{
      font-size:32px;
    }
    .section-link{
      color:var(--primary);
      text-decoration:none;
      font-size:15px;
      font-weight:600;
      display:flex;
      align-items:center;
      gap:6px;
      transition:all .2s ease;
    }
    .section-link:hover{
      color:var(--primary-dark);
      gap:10px;
    }

    /* ===== SEO Content Block ===== */
    .seo-content{
      max-width:900px;
      margin:80px auto 60px;
      padding:48px;
      border:1px solid rgba(255,255,255,.08);
      border-radius:24px;
      background:linear-gradient(135deg, rgba(255,255,255,.04), rgba(255,255,255,.02));
      backdrop-filter:blur(8px);
      line-height:1.8;
    }
    .seo-content h2{
      margin:0 0 24px;
      font-size:28px;
      font-weight:800;
      color:var(--primary);
    }
    .seo-content h3{
      margin:32px 0 16px;
      font-size:20px;
      font-weight:700;
    }
    .seo-content p{
      margin:0 0 20px;
      color:rgba(255,255,255,.8);
      font-size:16px;
    }
    .seo-content ul{
      margin:0 0 24px;
      padding-left:24px;
      color:rgba(255,255,255,.8);
    }
    .seo-content li{
      margin-bottom:12px;
      line-height:1.6;
    }
    .seo-content strong{
      color:#fff;
      font-weight:700;
    }
    .seo-content a{
      color:var(--primary);
      text-decoration:none;
      font-weight:600;
      transition:color .2s ease;
    }
    .seo-content a:hover{
      color:var(--primary-dark);
      text-decoration:underline;
    }

    /* ===== Autocomplete Dropdown ===== */
    .suggestbox{
      position:absolute;
      top:calc(100% + 8px);
      left:0;
      right:0;
      border:1px solid rgba(110,168,255,.3);
      background:rgba(15,26,47,.98);
      backdrop-filter:blur(16px);
      border-radius:16px;
      overflow:hidden;
      display:none;
      z-index:100;
      max-height:400px;
      overflow-y:auto;
      box-shadow:0 16px 48px rgba(0,0,0,.3);
    }
    .suggest-item{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
      padding:14px 16px;
      text-decoration:none;
      color:var(--text);
      border-bottom:1px solid rgba(255,255,255,.06);
      transition:all .15s ease;
    }
    .suggest-item:hover{
      background:rgba(110,168,255,.12);
      padding-left:20px;
    }
    .suggest-item:last-child{
      border-bottom:none;
    }
    .suggest-name{
      font-weight:500;
    }
    .badge{
      font-size:11px;
      color:var(--muted);
      border:1px solid rgba(255,255,255,.15);
      padding:4px 10px;
      border-radius:999px;
      background:rgba(255,255,255,.05);
    }

    /* ===== Helper text classes ===== */
    .release-date,
    .recommendation-count,
    .review-count{
      font-size:12px;
      color:var(--muted);
      margin-top:4px;
    }
    .recommendation-count{
      color:var(--primary);
      font-weight:600;
    }

    /* ===== Responsive ===== */
    @media(max-width:968px){
      .cta-grid-enhanced{
        grid-template-columns:1fr;
      }
      .hero-cta,
      .secondary-cta{
        grid-column:span 1;
      }
      .hero-cta{
        padding:36px 28px;
      }
      .hero-cta h2{
        font-size:28px;
      }
      .hero-cta .cta-icon{
        font-size:56px;
      }
    }

    @media(max-width:768px){
      .grid{grid-template-columns:repeat(auto-fill, minmax(240px, 1fr))}
      .thumb{height:120px}
      .nav-center{display:none}
      .hero{padding:60px 20px 40px}
      .hero h1{font-size:32px}
      .hero .tagline{font-size:16px}
      .seo-content{padding:32px 24px}
      .section-header{flex-direction:column; align-items:flex-start; gap:12px}
    }

    @media(max-width:640px){
      .hero-cta{
        padding:32px 24px;
      }
      .hero-cta h2{
        font-size:24px;
      }
      .hero-cta p{
        font-size:15px;
      }
      .hero-cta .cta-btn{
        padding:14px 32px;
        font-size:16px;
      }
    }

    /* ===== Animations ===== */
    @keyframes pulse{
      0%, 100%{transform:scale(1)}
      50%{transform:scale(1.05)}
    }

    /* ===== Forms (sign-in, account, AI tools) ===== */
    .panel{background:var(--card); border:1px solid var(--line); border-radius:16px; padding:24px}
    .panel h1, .panel h2{font-size:20px; margin-bottom:6px}
    .panel .sub{color:var(--muted); font-size:14px; margin-bottom:18px; line-height:1.5}
    .field{margin-bottom:16px}
    .field label{display:block; font-size:13px; font-weight:600; color:var(--muted); margin-bottom:6px}
    .field input, .field select, .field textarea{
      width:100%; padding:11px 13px; border-radius:10px; border:1px solid var(--line);
      background:rgba(255,255,255,.04); color:var(--text); font:inherit; font-size:15px;
    }
    .field textarea{min-height:110px; resize:vertical; line-height:1.5}
    .field input:focus, .field select:focus, .field textarea:focus{outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(110,168,255,.18)}
    .field-error{list-style:none; color:var(--red); font-size:13px; margin-top:6px}
    .check{display:flex; align-items:center; gap:8px; font-size:14px; color:var(--muted)}
    .form-actions{display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-top:20px}
    .btn-solid, .btn-quiet, .btn-danger{
      display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:11px 18px;
      border-radius:10px; font:inherit; font-size:14px; font-weight:700; cursor:pointer; text-decoration:none; border:1px solid transparent;
    }
    .btn-solid{background:var(--primary); color:#0b1220}
    .btn-solid:hover{filter:brightness(1.08)}
    .btn-solid[disabled]{opacity:.6; cursor:wait}
    .btn-quiet{background:transparent; color:var(--text); border-color:var(--line)}
    .btn-quiet:hover{border-color:var(--primary)}
    .btn-danger{background:rgba(255,107,107,.12); color:var(--red); border-color:rgba(255,107,107,.35)}
    .text-link{color:var(--primary); font-size:14px; text-decoration:none; background:none; border:0; cursor:pointer; font:inherit}
    .text-link:hover{text-decoration:underline}
    .notice{padding:12px 14px; border-radius:10px; font-size:14px; margin-bottom:16px; line-height:1.5}
    .notice-ok{background:rgba(78,255,187,.08); border:1px solid rgba(78,255,187,.3); color:var(--green)}
    .notice-warn{background:rgba(255,205,110,.08); border:1px solid rgba(255,205,110,.3); color:var(--orange)}
  </style>

  {{-- PAGE-SPECIFIC CSS --}}
  @stack('styles')
</head>

<body>
  <nav class="nav">
    <div class="nav-inner">
      <a href="{{ route('apps.index') }}" class="brand">
        🎮 Game Spec Match
      </a>

      <div class="nav-center">
        <a href="{{ route('ask') }}" class="nav-link">✨ Ask</a>
        <a href="{{ route('search.advanced') }}" class="nav-link">🎛️ Advanced Search</a>
        <a href="{{ route('apps.latest') }}" class="nav-link">🆕 New</a>
        <a href="{{ route('deals.index') }}" class="nav-link">🔥 Deals</a>
        <a href="{{ route('apps.free') }}" class="nav-link">💰 Free</a>
        <a href="{{ route('hardware.add-specs') }}" class="nav-link">📊 Can I Run It?</a>
      </div>

      <div class="nav-right">
        <a href="{{ route('hardware.add-specs') }}" class="nav-btn">
          ➕ My PC
        </a>

        @auth
          <a href="{{ route('dashboard') }}" class="nav-btn" style="background:rgba(110,168,255,.15); border-color:var(--primary)">
            👤 {{ Auth::user()->username ?? 'Account' }}
          </a>
        @else
          <a href="{{ route('login') }}" class="nav-link">
            Login
          </a>
          <a href="{{ route('register') }}" class="nav-btn" style="background:linear-gradient(135deg, var(--primary), var(--accent)); border:none">
            Sign Up
          </a>
        @endauth
      </div>
    </div>
  </nav>

  @yield('content')
@stack('scripts')
</body>
</html>