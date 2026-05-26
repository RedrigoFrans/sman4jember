<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "PHP now(): " . now()->toDateTimeString() . "\n";
echo "Config Timezone: " . config('app.timezone') . "\n";

$notification = \App\Models\MemberNotification::latest()->first();
if ($notification) {
    echo "Latest Notification created_at: " . $notification->created_at->toDateTimeString() . "\n";
    echo "Latest Notification diffForHumans: " . $notification->created_at->diffForHumans() . "\n";
} else {
    echo "No notifications found.\n";
}
