<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة التحكم') · رحلتي للسيارات</title>
    <style>
        :root { --navy:#102a43; --teal:#0d7c82; --mint:#dff6f2; --sky:#edf6ff; --ink:#223244; --muted:#728196; --line:#e8edf3; --card:#ffffff; --bg:#f5f8fb; --warning:#f59e0b; --danger:#e45757; --success:#26a67b; }
        * { box-sizing:border-box; }
        body { margin:0; color:var(--ink); background:var(--bg); font-family:Tahoma, Arial, sans-serif; font-size:14px; }
        a { color:inherit; text-decoration:none; }
        .app { min-height:100vh; display:flex; }
        .sidebar { position:fixed; inset:0 auto 0 0; width:248px; padding:26px 16px; background:linear-gradient(180deg, #102a43 0%, #0b2337 100%); color:#eaf4fb; display:flex; flex-direction:column; z-index:10; }
        .brand { display:flex; gap:11px; align-items:center; padding:0 10px 30px; font-weight:800; font-size:18px; }
        .brand-mark { display:grid; place-items:center; width:37px; height:37px; border-radius:12px; background:linear-gradient(135deg,#35d0c3,#0d7c82); color:white; box-shadow:0 8px 22px rgba(46,219,202,.24); }
        .nav-title { color:#88a0b6; font-size:11px; letter-spacing:.6px; padding:0 12px 8px; }
        .nav-link { display:flex; align-items:center; gap:12px; padding:13px 12px; margin:3px 0; border-radius:11px; color:#c7d7e5; transition:.2s ease; }
        .nav-link:hover, .nav-link.active { color:#fff; background:rgba(255,255,255,.12); }
        .nav-link svg { width:19px; height:19px; flex:none; }
        .side-footer { margin-top:auto; padding:13px 11px 0; border-top:1px solid rgba(255,255,255,.1); color:#b1c2d1; }
        .side-footer strong { color:white; display:block; margin-bottom:4px; }
        .main { width:calc(100% - 248px); margin-right:248px; }
        .topbar { height:78px; display:flex; align-items:center; justify-content:space-between; padding:0 38px; background:rgba(255,255,255,.9); border-bottom:1px solid var(--line); }
        .crumb { color:var(--muted); font-size:13px; margin-top:5px; }
        .profile { display:flex; align-items:center; gap:11px; }
        .avatar { display:grid; place-items:center; width:38px; height:38px; border-radius:50%; background:var(--mint); color:var(--teal); font-weight:800; }
        .logout { background:none; color:var(--muted); border:0; cursor:pointer; font-family:inherit; padding:8px; }
        .content { padding:30px 38px 42px; max-width:1480px; }
        .page-heading { display:flex; justify-content:space-between; gap:20px; align-items:flex-end; margin-bottom:26px; }
        h1 { font-size:27px; margin:0; letter-spacing:-.4px; color:var(--navy); }
        .subtle { color:var(--muted); margin:8px 0 0; }
        .button { display:inline-flex; align-items:center; gap:8px; border:0; border-radius:10px; padding:11px 15px; color:white; background:var(--teal); font:inherit; font-weight:700; cursor:pointer; box-shadow:0 7px 18px rgba(13,124,130,.18); }
        .button.secondary { color:var(--teal); background:var(--mint); box-shadow:none; }
        .grid { display:grid; gap:17px; }
        .stats { grid-template-columns:repeat(4,minmax(0,1fr)); margin-bottom:26px; }
        .card { background:var(--card); border:1px solid var(--line); border-radius:16px; box-shadow:0 2px 5px rgba(29,61,91,.025); }
        .stat { padding:20px; position:relative; overflow:hidden; }
        .stat:after { content:""; width:90px; height:90px; position:absolute; left:-35px; bottom:-43px; border-radius:50%; background:var(--sky); }
        .stat-label { color:var(--muted); margin-bottom:10px; }
        .stat-value { color:var(--navy); font-size:25px; font-weight:800; position:relative; z-index:1; }
        .stat-note { color:var(--success); font-size:12px; margin-top:8px; position:relative; z-index:1; }
        .section-card { padding:0; overflow:hidden; }
        .section-header { display:flex; align-items:center; justify-content:space-between; padding:19px 21px; border-bottom:1px solid var(--line); }
        h2 { font-size:16px; margin:0; color:var(--navy); }
        .link { color:var(--teal); font-weight:700; font-size:13px; }
        table { width:100%; border-collapse:collapse; }
        th { font-size:12px; text-align:right; color:var(--muted); background:#fafcff; font-weight:600; padding:13px 20px; }
        td { padding:16px 20px; border-top:1px solid var(--line); vertical-align:middle; }
        tr:hover td { background:#fcfeff; }
        .car-name { display:flex; align-items:center; gap:10px; font-weight:700; }
        .car-icon { display:grid; place-items:center; width:34px; height:34px; border-radius:9px; background:var(--sky); color:var(--teal); }
        .caption { display:block; color:var(--muted); font-size:12px; margin-top:3px; font-weight:400; }
        .badge { display:inline-flex; padding:5px 9px; border-radius:99px; font-size:12px; font-weight:700; }
        .badge.pending { color:#a75c00; background:#fff3d7; } .badge.confirmed,.badge.available { color:#157151; background:#dcf7ea; } .badge.active,.badge.rented { color:#176ea3; background:#dceffc; } .badge.cancelled,.badge.maintenance { color:#ad3c3c; background:#ffe4e4; } .badge.completed { color:#6554b3; background:#eeeaff; }
        .split { grid-template-columns:1.6fr 1fr; }
        .alert-list { padding:8px 20px 16px; }
        .alert { display:flex; gap:11px; align-items:flex-start; padding:14px 0; border-bottom:1px solid var(--line); }
        .alert:last-child { border:0; } .alert-icon { width:31px; height:31px; flex:none; border-radius:9px; display:grid; place-items:center; background:#fff2d6; color:#b87500; }
        .empty { color:var(--muted); padding:26px 20px; text-align:center; }
        .pagination { display:flex; gap:6px; padding:18px; color:var(--muted); }
        .pagination a,.pagination span { padding:7px 9px; border:1px solid var(--line); border-radius:7px; }
        @media(max-width:900px) { .sidebar{width:70px;padding:20px 9px}.brand span,.nav-title,.nav-link span,.side-footer{display:none}.brand{justify-content:center;padding-bottom:24px}.nav-link{justify-content:center}.main{width:calc(100% - 70px);margin-right:70px}.stats{grid-template-columns:repeat(2,1fr)}.split{grid-template-columns:1fr}.content{padding:23px}.topbar{padding:0 23px} }
        @media(max-width:580px) { .sidebar{display:none}.main{width:100%;margin:0}.topbar{height:70px;padding:0 16px}.content{padding:20px 14px}.stats{grid-template-columns:1fr 1fr;gap:10px}.stat{padding:15px}.stat-value{font-size:20px}.page-heading{align-items:flex-start;flex-direction:column;margin-bottom:18px}table{min-width:620px}.table-wrap{overflow:auto}.profile > div:not(.avatar){display:none} }
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <a href="{{ route('dashboard') }}" class="brand"><span class="brand-mark">R</span><span>رحلتي للسيارات</span></a>
        <p class="nav-title">إدارة الأعمال</p>
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg><span>نظرة عامة</span></a>
        <a href="{{ route('fleet') }}" class="nav-link {{ request()->routeIs('fleet') ? 'active' : '' }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 17h14l-1.5-7H6.5L5 17Z"/><path d="M7 17v2m10-2v2M8 12l1-4h6l1 4"/><circle cx="7" cy="19" r="1"/><circle cx="17" cy="19" r="1"/></svg><span>الأسطول والسيارات</span></a>
        <a href="{{ route('bookings') }}" class="nav-link {{ request()->routeIs('bookings') ? 'active' : '' }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg><span>الحجوزات</span></a>
        <div class="side-footer"><strong>نظام إدارة الأسطول</strong><span>إصدار 1.0 · واجهة داخلية</span></div>
    </aside>
    <main class="main">
        <header class="topbar">
            <div><strong>@yield('eyebrow', 'لوحة التحكم')</strong><div class="crumb">رحلتي للسيارات / @yield('crumb', 'نظرة عامة')</div></div>
            <div class="profile"><div class="avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</div><div><strong>{{ auth()->user()->name }}</strong><span class="caption">{{ auth()->user()->isOwner() ? 'صاحب مكتب' : 'مدير النظام' }}</span></div><form action="{{ route('logout') }}" method="POST">@csrf<button class="logout" title="تسجيل الخروج">خروج</button></form></div>
        </header>
        <section class="content">@yield('content')</section>
    </main>
</div>
</body>
</html>
