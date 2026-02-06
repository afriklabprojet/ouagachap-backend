<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Event;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Désactiver le broadcasting pendant les tests
        // pour éviter les erreurs de connexion au serveur Reverb
        Event::fake([
            \App\Events\CourierLocationUpdated::class,
            \App\Events\OrderTrackingUpdate::class,
            \App\Events\NewOrderAvailable::class,
            \App\Events\OrderCreated::class,
            \App\Events\OrderAssigned::class,
            \App\Events\OrderStatusChanged::class,
            \App\Events\PaymentCompleted::class,
            \App\Events\CourierWentOnline::class,
        ]);
    }
}
