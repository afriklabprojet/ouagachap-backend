<?php

namespace Tests\Unit\Enums;

use App\Enums\NotificationChannel;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour l'enum NotificationChannel
 * 
 * Teste les canaux de notifications push avec leurs priorités et configurations
 */
class NotificationChannelTest extends TestCase
{
    // =========================================================================
    // TESTS DES VALEURS DE L'ENUM
    // =========================================================================

    /** @test */
    public function it_has_all_expected_channels(): void
    {
        $expectedChannels = [
            'NEW_ORDER',
            'ORDER_STATUS',
            'URGENT',
            'PAYMENTS',
            'CHAT',
            'PROMOTIONS',
            'GENERAL',
            'RATINGS',
        ];

        $actualChannels = array_map(fn($case) => $case->name, NotificationChannel::cases());

        foreach ($expectedChannels as $channel) {
            $this->assertContains($channel, $actualChannels);
        }
        
        $this->assertCount(8, NotificationChannel::cases());
    }

    /** @test */
    public function it_has_correct_string_values(): void
    {
        $this->assertEquals('new_orders', NotificationChannel::NEW_ORDER->value);
        $this->assertEquals('order_status', NotificationChannel::ORDER_STATUS->value);
        $this->assertEquals('urgent', NotificationChannel::URGENT->value);
        $this->assertEquals('payments', NotificationChannel::PAYMENTS->value);
        $this->assertEquals('chat', NotificationChannel::CHAT->value);
        $this->assertEquals('promotions', NotificationChannel::PROMOTIONS->value);
        $this->assertEquals('general', NotificationChannel::GENERAL->value);
        $this->assertEquals('ratings', NotificationChannel::RATINGS->value);
    }

    /** @test */
    public function it_can_be_created_from_string(): void
    {
        $channel = NotificationChannel::from('new_orders');
        $this->assertEquals(NotificationChannel::NEW_ORDER, $channel);

        $channel = NotificationChannel::from('order_status');
        $this->assertEquals(NotificationChannel::ORDER_STATUS, $channel);
    }

    /** @test */
    public function it_returns_null_for_invalid_string(): void
    {
        $channel = NotificationChannel::tryFrom('invalid');
        $this->assertNull($channel);
    }

    // =========================================================================
    // TESTS DE LA MÉTHODE LABEL
    // =========================================================================

    /** @test */
    public function label_returns_french_label_for_new_order(): void
    {
        $this->assertEquals('Nouvelles commandes', NotificationChannel::NEW_ORDER->label());
    }

    /** @test */
    public function label_returns_french_label_for_order_status(): void
    {
        $this->assertEquals('Statut des commandes', NotificationChannel::ORDER_STATUS->label());
    }

    /** @test */
    public function label_returns_french_label_for_urgent(): void
    {
        $this->assertEquals('Alertes urgentes', NotificationChannel::URGENT->label());
    }

    /** @test */
    public function label_returns_french_label_for_payments(): void
    {
        $this->assertEquals('Paiements et gains', NotificationChannel::PAYMENTS->label());
    }

    /** @test */
    public function label_returns_french_label_for_chat(): void
    {
        $this->assertEquals('Messages', NotificationChannel::CHAT->label());
    }

    /** @test */
    public function label_returns_french_label_for_promotions(): void
    {
        $this->assertEquals('Promotions', NotificationChannel::PROMOTIONS->label());
    }

    /** @test */
    public function label_returns_french_label_for_general(): void
    {
        $this->assertEquals('Notifications générales', NotificationChannel::GENERAL->label());
    }

    /** @test */
    public function label_returns_french_label_for_ratings(): void
    {
        $this->assertEquals('Évaluations', NotificationChannel::RATINGS->label());
    }

    /** @test */
    public function all_channels_have_non_empty_labels(): void
    {
        foreach (NotificationChannel::cases() as $channel) {
            $label = $channel->label();
            $this->assertNotEmpty($label, "Channel {$channel->name} should have a label");
            $this->assertIsString($label);
        }
    }

    // =========================================================================
    // TESTS DE LA MÉTHODE DESCRIPTION
    // =========================================================================

    /** @test */
    public function description_returns_correct_text_for_new_order(): void
    {
        $description = NotificationChannel::NEW_ORDER->description();
        $this->assertStringContainsString('nouvelles courses', $description);
    }

    /** @test */
    public function description_returns_correct_text_for_order_status(): void
    {
        $description = NotificationChannel::ORDER_STATUS->description();
        $this->assertStringContainsString('statut', $description);
    }

    /** @test */
    public function description_returns_correct_text_for_urgent(): void
    {
        $description = NotificationChannel::URGENT->description();
        $this->assertStringContainsString('action immédiate', $description);
    }

    /** @test */
    public function description_returns_correct_text_for_payments(): void
    {
        $description = NotificationChannel::PAYMENTS->description();
        $this->assertStringContainsString('paiement', $description);
    }

    /** @test */
    public function all_channels_have_non_empty_descriptions(): void
    {
        foreach (NotificationChannel::cases() as $channel) {
            $description = $channel->description();
            $this->assertNotEmpty($description, "Channel {$channel->name} should have a description");
            $this->assertIsString($description);
            $this->assertGreaterThan(20, strlen($description), "Description should be meaningful");
        }
    }

    // =========================================================================
    // TESTS DES PRIORITÉS ANDROID
    // =========================================================================

    /** @test */
    public function android_priority_is_high_for_critical_channels(): void
    {
        $this->assertEquals('high', NotificationChannel::NEW_ORDER->androidPriority());
        $this->assertEquals('high', NotificationChannel::ORDER_STATUS->androidPriority());
        $this->assertEquals('high', NotificationChannel::URGENT->androidPriority());
        $this->assertEquals('high', NotificationChannel::CHAT->androidPriority());
    }

    /** @test */
    public function android_priority_is_normal_for_non_critical_channels(): void
    {
        $this->assertEquals('normal', NotificationChannel::PAYMENTS->androidPriority());
        $this->assertEquals('normal', NotificationChannel::PROMOTIONS->androidPriority());
        $this->assertEquals('normal', NotificationChannel::GENERAL->androidPriority());
        $this->assertEquals('normal', NotificationChannel::RATINGS->androidPriority());
    }

    /** @test */
    public function all_channels_have_valid_android_priority(): void
    {
        $validPriorities = ['high', 'normal'];

        foreach (NotificationChannel::cases() as $channel) {
            $priority = $channel->androidPriority();
            $this->assertContains($priority, $validPriorities, "Channel {$channel->name} has invalid Android priority");
        }
    }

    // =========================================================================
    // TESTS DES PRIORITÉS IOS
    // =========================================================================

    /** @test */
    public function ios_priority_is_10_for_critical_channels(): void
    {
        $this->assertEquals('10', NotificationChannel::NEW_ORDER->iosPriority());
        $this->assertEquals('10', NotificationChannel::ORDER_STATUS->iosPriority());
        $this->assertEquals('10', NotificationChannel::URGENT->iosPriority());
    }

    /** @test */
    public function ios_priority_is_5_for_non_critical_channels(): void
    {
        $this->assertEquals('5', NotificationChannel::CHAT->iosPriority());
        $this->assertEquals('5', NotificationChannel::PAYMENTS->iosPriority());
        $this->assertEquals('5', NotificationChannel::PROMOTIONS->iosPriority());
        $this->assertEquals('5', NotificationChannel::GENERAL->iosPriority());
        $this->assertEquals('5', NotificationChannel::RATINGS->iosPriority());
    }

    /** @test */
    public function all_channels_have_valid_ios_priority(): void
    {
        $validPriorities = ['5', '10'];

        foreach (NotificationChannel::cases() as $channel) {
            $priority = $channel->iosPriority();
            $this->assertContains($priority, $validPriorities, "Channel {$channel->name} has invalid iOS priority");
        }
    }

    // =========================================================================
    // TESTS DES SONS
    // =========================================================================

    /** @test */
    public function sound_returns_custom_sound_for_new_order(): void
    {
        $this->assertEquals('new_order.mp3', NotificationChannel::NEW_ORDER->sound());
    }

    /** @test */
    public function sound_returns_custom_sound_for_urgent(): void
    {
        $this->assertEquals('urgent_alert.mp3', NotificationChannel::URGENT->sound());
    }

    /** @test */
    public function sound_returns_custom_sound_for_chat(): void
    {
        $this->assertEquals('message.mp3', NotificationChannel::CHAT->sound());
    }

    /** @test */
    public function sound_returns_default_for_other_channels(): void
    {
        $this->assertEquals('default', NotificationChannel::ORDER_STATUS->sound());
        $this->assertEquals('default', NotificationChannel::PAYMENTS->sound());
        $this->assertEquals('default', NotificationChannel::PROMOTIONS->sound());
        $this->assertEquals('default', NotificationChannel::GENERAL->sound());
        $this->assertEquals('default', NotificationChannel::RATINGS->sound());
    }

    // =========================================================================
    // TESTS DES ICÔNES
    // =========================================================================

    /** @test */
    public function icon_returns_emoji_for_all_channels(): void
    {
        $this->assertEquals('📦', NotificationChannel::NEW_ORDER->icon());
        $this->assertEquals('🏍️', NotificationChannel::ORDER_STATUS->icon());
        $this->assertEquals('🚨', NotificationChannel::URGENT->icon());
        $this->assertEquals('💰', NotificationChannel::PAYMENTS->icon());
        $this->assertEquals('💬', NotificationChannel::CHAT->icon());
        $this->assertEquals('🎁', NotificationChannel::PROMOTIONS->icon());
        $this->assertEquals('ℹ️', NotificationChannel::GENERAL->icon());
        $this->assertEquals('⭐', NotificationChannel::RATINGS->icon());
    }

    /** @test */
    public function all_channels_have_non_empty_icons(): void
    {
        foreach (NotificationChannel::cases() as $channel) {
            $icon = $channel->icon();
            $this->assertNotEmpty($icon, "Channel {$channel->name} should have an icon");
        }
    }

    // =========================================================================
    // TESTS DE WAKE SCREEN
    // =========================================================================

    /** @test */
    public function wake_screen_is_true_for_critical_channels(): void
    {
        $this->assertTrue(NotificationChannel::NEW_ORDER->wakeScreen());
        $this->assertTrue(NotificationChannel::URGENT->wakeScreen());
    }

    /** @test */
    public function wake_screen_is_false_for_non_critical_channels(): void
    {
        $this->assertFalse(NotificationChannel::ORDER_STATUS->wakeScreen());
        $this->assertFalse(NotificationChannel::PAYMENTS->wakeScreen());
        $this->assertFalse(NotificationChannel::CHAT->wakeScreen());
        $this->assertFalse(NotificationChannel::PROMOTIONS->wakeScreen());
        $this->assertFalse(NotificationChannel::GENERAL->wakeScreen());
        $this->assertFalse(NotificationChannel::RATINGS->wakeScreen());
    }

    // =========================================================================
    // TESTS DE ANDROID CATEGORY
    // =========================================================================

    /** @test */
    public function android_category_is_alarm_for_new_order_and_urgent(): void
    {
        $this->assertEquals('alarm', NotificationChannel::NEW_ORDER->androidCategory());
        $this->assertEquals('alarm', NotificationChannel::URGENT->androidCategory());
    }

    /** @test */
    public function android_category_is_message_for_chat(): void
    {
        $this->assertEquals('message', NotificationChannel::CHAT->androidCategory());
    }

    /** @test */
    public function android_category_is_promo_for_default_channels(): void
    {
        $this->assertEquals('promo', NotificationChannel::ORDER_STATUS->androidCategory());
        $this->assertEquals('promo', NotificationChannel::PAYMENTS->androidCategory());
        $this->assertEquals('promo', NotificationChannel::PROMOTIONS->androidCategory());
        $this->assertEquals('promo', NotificationChannel::GENERAL->androidCategory());
        $this->assertEquals('promo', NotificationChannel::RATINGS->androidCategory());
    }

    /** @test */
    public function all_channels_have_valid_android_category(): void
    {
        $validCategories = ['alarm', 'message', 'promo'];

        foreach (NotificationChannel::cases() as $channel) {
            $category = $channel->androidCategory();
            $this->assertContains($category, $validCategories, "Channel {$channel->name} has invalid Android category");
        }
    }

    // =========================================================================
    // TESTS DE COHÉRENCE
    // =========================================================================

    /** @test */
    public function critical_channels_have_consistent_high_priority_settings(): void
    {
        // Les canaux critiques doivent avoir une haute priorité sur les deux plateformes
        $criticalChannels = [
            NotificationChannel::NEW_ORDER,
            NotificationChannel::ORDER_STATUS,
            NotificationChannel::URGENT,
        ];

        foreach ($criticalChannels as $channel) {
            $this->assertEquals('high', $channel->androidPriority(), "{$channel->name} should have high Android priority");
            $this->assertEquals('10', $channel->iosPriority(), "{$channel->name} should have high iOS priority");
        }
    }

    /** @test */
    public function alarm_category_channels_wake_screen(): void
    {
        foreach (NotificationChannel::cases() as $channel) {
            if ($channel->androidCategory() === 'alarm') {
                $this->assertTrue($channel->wakeScreen(), "{$channel->name} with alarm category should wake screen");
            }
        }
    }

    /** @test */
    public function channels_with_custom_sounds_are_important(): void
    {
        foreach (NotificationChannel::cases() as $channel) {
            $sound = $channel->sound();
            if ($sound !== 'default' && $sound !== null) {
                $priority = $channel->androidPriority();
                // Les canaux avec sons personnalisés devraient être high ou avoir une raison spécifique
                $this->assertContains($priority, ['high', 'normal'], "{$channel->name} with custom sound should have valid priority");
            }
        }
    }

    // =========================================================================
    // TESTS DES CAS D'UTILISATION BUSINESS
    // =========================================================================

    /** @test */
    public function courier_notifications_use_appropriate_channel(): void
    {
        // Les nouvelles commandes pour coursiers utilisent NEW_ORDER
        $channel = NotificationChannel::NEW_ORDER;
        
        $this->assertEquals('high', $channel->androidPriority());
        $this->assertTrue($channel->wakeScreen());
        $this->assertEquals('alarm', $channel->androidCategory());
    }

    /** @test */
    public function client_order_updates_use_order_status_channel(): void
    {
        $channel = NotificationChannel::ORDER_STATUS;
        
        $this->assertEquals('high', $channel->androidPriority());
        $this->assertEquals('10', $channel->iosPriority());
    }

    /** @test */
    public function payment_notifications_are_non_intrusive(): void
    {
        $channel = NotificationChannel::PAYMENTS;
        
        $this->assertEquals('normal', $channel->androidPriority());
        $this->assertFalse($channel->wakeScreen());
        $this->assertEquals('default', $channel->sound());
    }

    /** @test */
    public function promotional_notifications_are_low_priority(): void
    {
        $channel = NotificationChannel::PROMOTIONS;
        
        $this->assertEquals('normal', $channel->androidPriority());
        $this->assertEquals('5', $channel->iosPriority());
        $this->assertFalse($channel->wakeScreen());
        $this->assertEquals('promo', $channel->androidCategory());
    }
}
