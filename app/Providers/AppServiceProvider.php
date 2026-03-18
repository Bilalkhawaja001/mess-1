<?php

namespace App\Providers;

use App\Services\Otp\FakeOtpDeliveryService;
use App\Services\Otp\LogOtpDeliveryService;
use App\Services\Otp\OtpDeliveryService;
use App\Services\Payments\Gateways\EasyPaisaPlaceholderGatewayAdapter;
use App\Services\Payments\Gateways\InternalFakeGatewayAdapter;
use App\Services\Payments\Gateways\JazzCashPlaceholderGatewayAdapter;
use App\Services\Payments\PaymentGatewayInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OtpDeliveryService::class, function () {
            return match (config('member_registration.otp.driver')) {
                'fake' => new FakeOtpDeliveryService(),
                'log' => new LogOtpDeliveryService(),
                default => new LogOtpDeliveryService(),
            };
        });

        $this->app->bind(PaymentGatewayInterface::class, function () {
            return match (config('payment.default_gateway')) {
                'jazzcash' => new JazzCashPlaceholderGatewayAdapter(),
                'easypaisa' => new EasyPaisaPlaceholderGatewayAdapter(),
                default => new InternalFakeGatewayAdapter(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
