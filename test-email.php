<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Email Verification Test ===\n";
echo "Environment: " . config('app.env') . "\n";
echo "Mail From: " . config('mail.from.address') . "\n";
echo "Mail Name: " . config('mail.from.name') . "\n\n";

try {
    $service = new App\Services\ResendEmailService();
    
    echo "Testing OTP email verification...\n";
    
    // Generate OTP using OtpService
    $otpService = new App\Services\OtpService();
    $user = App\Models\User::first(); // Get first user for testing
    
    if (!$user) {
        echo "❌ No users found in database. Please create a user first.\n";
        exit(1);
    }
    
    $otp = $otpService->generateOtp($user, 'seyisensei@gmail.com');
    
    echo "Generated OTP: " . $otp->otp_code . "\n";
    echo "Expires at: " . $otp->expires_at . "\n\n";
    
    $result = $service->sendEmailVerification(
        'seyisensei@gmail.com', 
        $otp->otp_code, 
        'Test User'
    );
    
    if ($result !== false) {
        echo "✅ SUCCESS!\n";
        echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ FAILED!\n";
        echo "Check the Laravel logs for details.\n";
    }
    
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== Check Laravel logs for detailed information ===\n";
