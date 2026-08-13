<?php

namespace App\Console\Commands;

use App\Models\Car;
use App\Notifications\VehicleReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendVehicleReminders extends Command
{
    protected $signature = 'notifications:send-vehicle-reminders';

    protected $description = 'Send maintenance and insurance reminder notifications to rental shop owners.';

    public function handle(): int
    {
        $insuranceLeadDays = config('notifications.insurance_lead_days');
        $maintenanceLeadDays = config('notifications.maintenance_lead_days');
        $today = now()->startOfDay();

        $cars = Car::with('rentalShop.owner')
            ->whereHas('rentalShop', fn ($shop) => $shop->where('is_active', true))
            ->where(function ($query) use ($today, $maintenanceLeadDays): void {
                $query->where(function ($insurance): void {
                    $insurance->whereNotNull('insurance_expires_at');
                })->orWhere(function ($oil): void {
                    $oil->whereNotNull('next_oil_change_at_km')
                        ->whereColumn('current_odometer_km', '>=', 'next_oil_change_at_km');
                })->orWhere(function ($inspection) use ($today, $maintenanceLeadDays): void {
                    $inspection->whereNotNull('next_inspection_date')
                        ->whereDate('next_inspection_date', '<=', $today->copy()->addDays($maintenanceLeadDays));
                });
            })
            ->get();

        $sent = 0;
        foreach ($cars as $car) {
            $owner = $car->rentalShop->owner;
            if (! $owner) {
                continue;
            }

            foreach ($this->dueReminders($car, $today, $insuranceLeadDays, $maintenanceLeadDays) as $reminder) {
                $key = "vehicle-reminder:{$reminder['kind']}:{$car->id}:{$today->toDateString()}";
                if (! Cache::add($key, true, $today->copy()->endOfDay())) {
                    continue;
                }

                $owner->notify(new VehicleReminder(
                    car: $car,
                    kind: $reminder['kind'],
                    title: $reminder['title'],
                    body: $reminder['body'],
                    severity: $reminder['severity'],
                ));
                $sent++;
            }
        }

        $this->info("{$sent} vehicle reminder(s) dispatched.");

        return self::SUCCESS;
    }

    private function dueReminders(Car $car, $today, int $insuranceLeadDays, int $maintenanceLeadDays): array
    {
        $vehicle = "{$car->make} {$car->model} ({$car->plate_number})";
        $reminders = [];

        if ($car->insurance_expires_at) {
            $days = $today->diffInDays($car->insurance_expires_at, false);
            if ($days < 0) {
                $reminders[] = [
                    'kind' => 'insurance_expired',
                    'title' => 'انتهى تأمين المركبة',
                    'body' => "تأمين {$vehicle} منتهٍ. أوقف التأجير وجدد الوثيقة فوراً.",
                    'severity' => 'critical',
                ];
            } elseif ($days <= $insuranceLeadDays) {
                $reminders[] = [
                    'kind' => 'insurance_expiry',
                    'title' => 'تأمين المركبة يقترب من الانتهاء',
                    'body' => "يتبقى {$days} يوم على انتهاء تأمين {$vehicle}.",
                    'severity' => $days <= 7 ? 'critical' : 'warning',
                ];
            }
        }

        if ($car->next_oil_change_at_km && $car->current_odometer_km >= $car->next_oil_change_at_km) {
            $reminders[] = [
                'kind' => 'oil_change_due',
                'title' => 'موعد تغيير الزيت مستحق',
                'body' => "وصلت {$vehicle} إلى {$car->current_odometer_km} كم، وتجاوزت موعد الزيت المحدد.",
                'severity' => 'warning',
            ];
        }

        if ($car->next_inspection_date && $car->next_inspection_date->lessThanOrEqualTo($today->copy()->addDays($maintenanceLeadDays))) {
            $days = $today->diffInDays($car->next_inspection_date, false);
            $reminders[] = [
                'kind' => 'inspection_due',
                'title' => 'فحص دوري قريب',
                'body' => $days < 0 ? "موعد فحص {$vehicle} متأخر." : "يتبقى {$days} يوم على فحص {$vehicle}.",
                'severity' => $days <= 0 ? 'critical' : 'warning',
            ];
        }

        return $reminders;
    }
}
