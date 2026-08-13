<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>دخول لوحة التحكم · رحلتي للسيارات</title>
    <style>
        :root{--navy:#102a43;--teal:#0d7c82;--mint:#dff6f2;--line:#dce6ed;--muted:#718096}*{box-sizing:border-box}body{min-height:100vh;margin:0;font-family:Tahoma,Arial,sans-serif;color:var(--navy);background:linear-gradient(130deg,#eef8fa 0%,#f6f8fc 46%,#e8f2f9 100%);display:grid;place-items:center;padding:24px}.shell{width:min(100%,980px);min-height:580px;display:grid;grid-template-columns:1.1fr .9fr;background:white;border:1px solid rgba(255,255,255,.8);border-radius:26px;overflow:hidden;box-shadow:0 22px 70px rgba(16,42,67,.13)}.hero{position:relative;padding:55px 55px;background:linear-gradient(155deg,#102a43,#0b3e53);color:white;overflow:hidden}.hero:before,.hero:after{content:"";position:absolute;border-radius:50%;border:1px solid rgba(136,236,227,.17)}.hero:before{width:420px;height:420px;left:-160px;bottom:-250px}.hero:after{width:280px;height:280px;right:-150px;top:-110px}.brand{position:relative;display:flex;align-items:center;gap:10px;font-weight:800;font-size:19px}.brand-mark{display:grid;place-items:center;width:39px;height:39px;border-radius:12px;background:linear-gradient(135deg,#31d2c2,#0d7c82)}.hero-copy{position:relative;margin-top:115px}.hero h1{font-size:34px;line-height:1.35;margin:0 0 16px}.hero p{line-height:1.9;color:#c9dce7;max-width:360px}.perks{position:relative;list-style:none;padding:10px 0 0;margin:0;color:#e3f8f6}.perks li{margin:13px 0}.perks li:before{content:"✓";display:inline-grid;place-items:center;width:18px;height:18px;margin-left:8px;border-radius:50%;background:#2a968f;font-size:12px}.form-side{display:flex;align-items:center;padding:55px}.form{width:100%}.form h2{margin:0 0 8px;font-size:25px}.form p{margin:0 0 30px;color:var(--muted)}label{display:block;font-weight:700;margin:16px 0 8px;font-size:13px}input{width:100%;border:1px solid var(--line);border-radius:10px;padding:13px 14px;font:inherit;color:var(--navy);outline:none;transition:.2s}input:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(13,124,130,.11)}.remember{display:flex;align-items:center;gap:8px;color:var(--muted);font-size:13px;margin:17px 0 23px}.remember input{width:auto;accent-color:var(--teal)}button{border:0;border-radius:10px;width:100%;padding:14px;color:white;background:var(--teal);font:inherit;font-weight:700;cursor:pointer;box-shadow:0 9px 21px rgba(13,124,130,.2)}.error{background:#fff0f0;color:#b53d3d;border-radius:9px;padding:11px 13px;font-size:13px;margin-bottom:16px}.foot{margin-top:26px;text-align:center;color:var(--muted);font-size:12px}@media(max-width:760px){.shell{grid-template-columns:1fr;min-height:0}.hero{padding:32px}.hero-copy{margin-top:35px}.hero h1{font-size:26px}.perks{display:none}.form-side{padding:35px 28px}}
    </style>
</head>
<body>
<div class="shell">
    <section class="hero">
        <div class="brand"><span class="brand-mark">R</span><span>رحلتي للسيارات</span></div>
        <div class="hero-copy"><h1>كل تفاصيل أسطولك،<br>في مكان واحد.</h1><p>تابع السيارات والحجوزات والمصروفات والصيانة في لوحة تحكم مصممة لاتخاذ قرار أسرع.</p><ul class="perks"><li>متابعة مالية شهرية واضحة</li><li>تنبيهات الزيت والصيانة والفحص</li><li>نظرة فورية على الحجوزات والأسطول</li></ul></div>
    </section>
    <section class="form-side"><form class="form" method="POST" action="{{ route('login.store') }}">@csrf
        <h2>مرحباً بعودتك</h2><p>ادخل بياناتك للوصول إلى لوحة مكتبك.</p>
        @if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif
        <label for="email">البريد الإلكتروني</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
        <label for="password">كلمة المرور</label><input id="password" name="password" type="password" autocomplete="current-password" required>
        <label class="remember"><input name="remember" type="checkbox"> تذكر دخولي على هذا الجهاز</label>
        <button type="submit">دخول لوحة التحكم</button><div class="foot">منصة إدارة وتأجير السيارات · نسخة MVP</div>
    </form></section>
</div>
</body>
</html>
