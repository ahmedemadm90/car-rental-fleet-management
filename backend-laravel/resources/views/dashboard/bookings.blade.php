@extends('layouts.dashboard')

@section('title', 'الحجوزات')
@section('crumb', 'الحجوزات')

@section('content')
<div class="page-heading">
    <div><h1>الحجوزات</h1><p class="subtle">راقب طلبات العملاء وحالة كل عملية تأجير من مكان واحد.</p></div>
    <span class="button secondary">{{ $bookings->total() }} حجز مسجل</span>
</div>

<section class="card section-card"><div class="table-wrap"><table><thead><tr><th>العميل</th><th>السيارة</th><th>نوع وفترة التأجير</th><th>الإجمالي</th><th>الحالة</th></tr></thead><tbody>
@forelse($bookings as $booking)<tr>
    <td><strong>{{ $booking->customer->name }}</strong><span class="caption">{{ $booking->customer->phone ?? $booking->customer->email }}</span></td>
    <td><div class="car-name"><span class="car-icon">🚘</span><span>{{ $booking->car->make }} {{ $booking->car->model }}<span class="caption">{{ $booking->car->rentalShop->name }}</span></span></div></td>
    <td>{{ ['daily'=>'يومي','travel'=>'سفر','wedding'=>'زفاف'][$booking->rental_type] }}<span class="caption">{{ $booking->start_date->format('d/m/Y') }} — {{ $booking->end_date->format('d/m/Y') }}</span></td>
    <td><strong>{{ number_format($booking->total_amount, 0) }} ج.م</strong><span class="caption">عربون {{ number_format($booking->deposit_amount, 0) }} ج.م</span></td>
    <td><span class="badge {{ $booking->status }}">{{ ['pending'=>'بانتظار التأكيد','confirmed'=>'مؤكد','active'=>'نشط','completed'=>'مكتمل','cancelled'=>'ملغى','rejected'=>'مرفوض'][$booking->status] }}</span></td>
</tr>@empty
<tr><td colspan="5"><div class="empty">لا توجد حجوزات لعرضها حالياً.</div></td></tr>@endforelse
</tbody></table></div>
@if($bookings->hasPages())<div class="pagination">{{ $bookings->links() }}</div>@endif
</section>
@endsection
