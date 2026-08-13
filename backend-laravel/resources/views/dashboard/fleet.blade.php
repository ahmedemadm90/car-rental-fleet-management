@extends('layouts.dashboard')

@section('title', 'الأسطول والسيارات')
@section('crumb', 'الأسطول والسيارات')

@section('content')
<div class="page-heading">
    <div><h1>الأسطول والسيارات</h1><p class="subtle">نظرة عملية على توافر السيارات والمصروفات وسجل التشغيل.</p></div>
    <a class="button" href="{{ url('/api/v1/owner/shops') }}" target="_blank">إدارة السيارات عبر API ↗</a>
</div>

<section class="card section-card"><div class="table-wrap"><table><thead><tr><th>السيارة</th><th>المكتب</th><th>السعر اليومي</th><th>الحالة</th><th>النشاط</th></tr></thead><tbody>
@forelse($cars as $car)<tr>
    <td><div class="car-name"><span class="car-icon">🚘</span><span>{{ $car->make }} {{ $car->model }}<span class="caption">{{ $car->year }} · لوحة {{ $car->plate_number }}</span></span></div></td>
    <td>{{ $car->rentalShop->name }}<span class="caption">{{ $car->rentalShop->city }}</span></td>
    <td><strong>{{ number_format($car->daily_rate, 0) }} ج.م</strong><span class="caption">سعر اليوم</span></td>
    <td><span class="badge {{ $car->status }}">{{ ['available'=>'متاحة','rented'=>'مؤجرة','maintenance'=>'في الصيانة','inactive'=>'غير نشطة'][$car->status] }}</span></td>
    <td>{{ $car->bookings_count }} حجز <span class="caption">{{ $car->expenses_count }} مصروف · {{ $car->maintenance_records_count }} صيانة</span></td>
</tr>@empty
<tr><td colspan="5"><div class="empty">لم تضف أي سيارة بعد. ابدأ بإضافة سياراتك من تطبيق الهاتف أو واجهة API.</div></td></tr>@endforelse
</tbody></table></div>
@if($cars->hasPages())<div class="pagination">{{ $cars->links() }}</div>@endif
</section>
@endsection
