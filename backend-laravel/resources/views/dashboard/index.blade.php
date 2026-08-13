@extends('layouts.dashboard')

@section('title', 'نظرة عامة')
@section('crumb', 'نظرة عامة')

@section('content')
<div class="page-heading">
    <div><h1>صباح الخير، {{ auth()->user()->name }}</h1><p class="subtle">هذا ملخص أداء أسطولك خلال شهر {{ now()->translatedFormat('F Y') }}.</p></div>
    <a class="button secondary" href="{{ route('fleet') }}">عرض كل السيارات ←</a>
</div>

<div class="grid stats">
    <article class="card stat"><div class="stat-label">إيرادات الشهر</div><div class="stat-value">{{ number_format($stats['revenue'], 0) }} ج.م</div><div class="stat-note">من الحجوزات المؤكدة والنشطة</div></article>
    <article class="card stat"><div class="stat-label">مصروفات الشهر</div><div class="stat-value">{{ number_format($stats['expenses'], 0) }} ج.م</div><div class="stat-note" style="color:#b87900">متابعة دورية لتكاليف التشغيل</div></article>
    <article class="card stat"><div class="stat-label">السيارات المتاحة</div><div class="stat-value">{{ $stats['available'] }} <small style="font-size:13px;font-weight:500">من {{ $stats['fleet'] }}</small></div><div class="stat-note">جاهزة للحجز الآن</div></article>
    <article class="card stat"><div class="stat-label">طلبات بانتظارك</div><div class="stat-value">{{ $stats['pending'] }}</div><div class="stat-note">راجعها لتأكيد الحجز سريعاً</div></article>
</div>

<div class="grid split">
    <section class="card section-card">
        <div class="section-header"><h2>أحدث الحجوزات</h2><a class="link" href="{{ route('bookings') }}">كل الحجوزات</a></div>
        @if($recentBookings->isEmpty())<div class="empty">لا توجد حجوزات بعد. ستظهر الطلبات الجديدة هنا فور وصولها.</div>
        @else<div class="table-wrap"><table><thead><tr><th>العميل والسيارة</th><th>الفترة</th><th>القيمة</th><th>الحالة</th></tr></thead><tbody>
            @foreach($recentBookings as $booking)<tr>
                <td><div class="car-name"><span class="car-icon">🚘</span><span>{{ $booking->customer->name }}<span class="caption">{{ $booking->car->make }} {{ $booking->car->model }}</span></span></div></td>
                <td>{{ $booking->start_date->format('d M') }} — {{ $booking->end_date->format('d M') }}</td>
                <td><strong>{{ number_format($booking->total_amount, 0) }} ج.م</strong></td>
                <td><span class="badge {{ $booking->status }}">{{ ['pending'=>'بانتظار التأكيد','confirmed'=>'مؤكد','active'=>'نشط','completed'=>'مكتمل','cancelled'=>'ملغى','rejected'=>'مرفوض'][$booking->status] }}</span></td>
            </tr>@endforeach
        </tbody></table></div>@endif
    </section>
    <section class="card section-card">
        <div class="section-header"><h2>تنبيهات الصيانة</h2><span class="badge maintenance">{{ $maintenanceDue->count() }} تنبيه</span></div>
        <div class="alert-list">
        @forelse($maintenanceDue as $car)<article class="alert"><span class="alert-icon">⚠</span><div><strong>{{ $car->make }} {{ $car->model }}</strong><span class="caption">لوحة {{ $car->plate_number }} · عداد {{ number_format($car->current_odometer_km) }} كم</span></div></article>
        @empty<div class="empty">ممتاز، لا توجد استحقاقات صيانة عاجلة خلال 14 يوماً.</div>@endforelse
        </div>
    </section>
</div>
@endsection
