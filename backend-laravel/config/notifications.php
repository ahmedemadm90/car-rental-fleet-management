<?php

return [
    'fcm_enabled' => filter_var(env('FCM_ENABLED', false), FILTER_VALIDATE_BOOL),
    'maintenance_lead_days' => (int) env('MAINTENANCE_REMINDER_LEAD_DAYS', 14),
    'insurance_lead_days' => (int) env('INSURANCE_REMINDER_LEAD_DAYS', 30),
];
