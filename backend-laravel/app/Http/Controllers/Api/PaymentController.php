<?php

namespace App\Http\Controllers\Api;

use App\Models\Payment;
use App\Services\Payments\PaymobCheckoutService;
use App\Services\Payments\PaymobWebhookVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentController extends ApiController
{
    public function createCheckout(Request $request, int $booking, PaymobCheckoutService $checkoutService): JsonResponse
    {
        $reservation = $request->user()->bookings()->with(['customer', 'car'])->findOrFail($booking);
        abort_unless(in_array($reservation->status, ['pending', 'confirmed']), 422, 'This booking is not eligible for payment.');

        $payment = $reservation->payments()
            ->whereIn('status', ['pending', 'failed'])
            ->latest()
            ->first();

        if (! $payment) {
            $payment = $reservation->payments()->create([
                'amount' => $reservation->deposit_amount > 0 ? $reservation->deposit_amount : $reservation->total_amount,
                'method' => 'card',
                'provider' => 'paymob',
                'status' => 'pending',
                'reference' => 'CR-'.Str::upper(Str::random(12)),
            ]);
        }

        try {
            $checkout = $checkoutService->createCheckout($payment, $reservation);
        } catch (RuntimeException $exception) {
            report($exception);

            return response()->json(['message' => 'Online payments are not configured yet.'], 503);
        }

        $payment->update([
            ...$checkout,
            'status' => 'pending',
        ]);

        return $this->success($payment->fresh(), 201);
    }

    public function show(Request $request, int $payment): JsonResponse
    {
        $record = Payment::with('booking.customer')
            ->whereHas('booking', fn ($query) => $query->where('customer_id', $request->user()->id))
            ->findOrFail($payment);

        return $this->success($record);
    }

    public function webhook(Request $request, PaymobWebhookVerifier $verifier): JsonResponse
    {
        $payload = $request->all();
        $hmac = $request->query('hmac') ?? $request->header('X-Paymob-Hmac');

        abort_unless($verifier->isValid($payload, $hmac), 401, 'Invalid payment callback signature.');

        $object = $payload['obj'] ?? $payload;
        $reference = data_get($object, 'order.merchant_order_id')
            ?? data_get($object, 'order.special_reference')
            ?? data_get($object, 'merchant_order_id');
        $providerPaymentId = (string) data_get($object, 'id');

        $payment = Payment::query()
            ->where('reference', $reference)
            ->orWhere('provider_payment_id', $providerPaymentId)
            ->firstOrFail();

        $success = filter_var(data_get($object, 'success', false), FILTER_VALIDATE_BOOL);
        $pending = filter_var(data_get($object, 'pending', false), FILTER_VALIDATE_BOOL);
        $status = $success ? 'paid' : ($pending ? 'pending' : 'failed');

        $payment->update([
            'status' => $status,
            'provider_payment_id' => $providerPaymentId ?: $payment->provider_payment_id,
            'gateway_payload' => [
                'transaction_id' => $providerPaymentId,
                'success' => $success,
                'pending' => $pending,
                'received_at' => now()->toIso8601String(),
            ],
            'paid_at' => $success ? now() : null,
        ]);

        if ($success) {
            $payment->booking->update(['status' => 'confirmed']);
        }

        return response()->json(['received' => true]);
    }

    public function redirect(): JsonResponse
    {
        return response()->json([
            'message' => 'Payment result received. The mobile application will verify the final status securely.',
        ]);
    }
}
