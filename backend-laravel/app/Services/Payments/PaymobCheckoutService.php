<?php

namespace App\Services\Payments;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaymobCheckoutService
{
    /**
     * @return array{provider_payment_id:string, checkout_url:string, expires_at:?string, payload:array}
     */
    public function createCheckout(Payment $payment, Booking $booking): array
    {
        $this->ensureConfigured();

        try {
            $response = Http::acceptJson()
                ->withToken(config('payments.paymob.secret_key'))
                ->post(rtrim(config('payments.paymob.base_url'), '/').'/v1/intention', [
                    'amount' => (int) round(((float) $payment->amount) * 100),
                    'currency' => config('payments.paymob.currency'),
                    'payment_methods' => [(int) config('payments.paymob.integration_id')],
                    'items' => [[
                        'name' => "Car rental booking #{$booking->id}",
                        'amount' => (int) round(((float) $payment->amount) * 100),
                        'quantity' => 1,
                    ]],
                    'billing_data' => [
                        'first_name' => $booking->customer->name,
                        'last_name' => '-',
                        'email' => $booking->customer->email,
                        'phone_number' => $booking->customer->phone ?? 'NA',
                    ],
                    'special_reference' => $payment->reference,
                    'notification_url' => route('payments.webhook'),
                    'redirection_url' => route('payments.redirect'),
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Unable to reach the payment provider.', previous: $exception);
        }

        if ($response->failed()) {
            report(new RuntimeException('Paymob intention request failed: '.$response->body()));
            throw new RuntimeException('Payment provider did not create a checkout session.');
        }

        $payload = $response->json();
        $clientSecret = data_get($payload, 'client_secret');
        $providerPaymentId = (string) (data_get($payload, 'id') ?? data_get($payload, 'intention_id'));

        if (! $clientSecret || ! $providerPaymentId) {
            report(new RuntimeException('Unexpected Paymob intention response: '.json_encode($payload)));
            throw new RuntimeException('Payment provider returned an incomplete checkout session.');
        }

        $checkoutUrl = strtr(config('payments.paymob.checkout_template'), [
            '{public_key}' => urlencode(config('payments.paymob.public_key')),
            '{client_secret}' => urlencode($clientSecret),
        ]);

        return [
            'provider_payment_id' => $providerPaymentId,
            'checkout_url' => $checkoutUrl,
            'expires_at' => data_get($payload, 'expires_at'),
            'gateway_payload' => [
                'intention_id' => $providerPaymentId,
                'currency' => data_get($payload, 'currency', config('payments.paymob.currency')),
                'created_at' => data_get($payload, 'created_at'),
            ],
        ];
    }

    private function ensureConfigured(): void
    {
        foreach (['secret_key', 'public_key', 'integration_id', 'hmac_secret'] as $key) {
            if (blank(config("payments.paymob.{$key}"))) {
                throw new RuntimeException('Payment provider has not been configured.');
            }
        }
    }
}
