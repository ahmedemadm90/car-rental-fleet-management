<?php

namespace App\Services\Payments;

use Illuminate\Support\Arr;

class PaymobWebhookVerifier
{
    public function isValid(array $payload, ?string $providedHmac): bool
    {
        $secret = config('payments.paymob.hmac_secret');
        if (blank($secret) || blank($providedHmac)) {
            return false;
        }

        $object = $payload['obj'] ?? $payload;
        $fields = [
            'amount_cents',
            'created_at',
            'currency',
            'error_occured',
            'has_parent_transaction',
            'id',
            'integration_id',
            'is_3d_secure',
            'is_auth',
            'is_capture',
            'is_refunded',
            'is_standalone_payment',
            'is_voided',
            'order.id',
            'owner',
            'pending',
            'source_data.pan',
            'source_data.sub_type',
            'source_data.type',
            'success',
        ];

        $concatenated = collect($fields)
            ->map(function (string $field) use ($object): string {
                $value = Arr::get($object, $field, '');

                return is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
            })
            ->implode('');

        return hash_equals(hash_hmac('sha512', $concatenated, $secret), $providedHmac);
    }
}
