<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\SiteSetting;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * @codeCoverageIgnore
 */
class LandingController extends Controller
{
    public function __construct(
        private CacheService $cacheService
    ) {}

    public function index()
    {
        // Récupérer tous les paramètres du site (avec cache)
        $settings = $this->cacheService->getLandingSettings();

        // Les JSON sont déjà décodés par le CacheService
        $features = $settings['features'] ?? [];
        $pricing = is_array($settings['pricing'] ?? null)
            ? $settings['pricing']
            : (is_array($settings['pricing_plans'] ?? null) ? $settings['pricing_plans'] : []);
        $testimonials = $settings['testimonials'] ?? [];
        $howItWorksSteps = is_array($settings['how_it_works_steps'] ?? null)
            ? $settings['how_it_works_steps']
            : [];

        // URLs des APK
        $apkClient = !empty($settings['apk_client'])
            ? Storage::url($settings['apk_client'])
            : asset('downloads/ouaga-chap-client.apk');
        $apkCourier = !empty($settings['apk_courier'])
            ? Storage::url($settings['apk_courier'])
            : asset('downloads/ouaga-chap-coursier.apk');

        // Logo du site
        $siteLogo = !empty($settings['site_logo'])
            ? Storage::url($settings['site_logo'])
            : asset('images/logo-ouagachap.png');

        return view('landing', compact(
            'settings',
            'features',
            'pricing',
            'testimonials',
            'howItWorksSteps',
            'apkClient',
            'apkCourier',
            'siteLogo'
        ));
    }

    public function contact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        // Persister le message en base de données
        $contactMessage = ContactMessage::create($validated);

        // Envoyer un email de notification à l'admin
        try {
            $adminEmail = SiteSetting::get('contact_email', config('mail.from.address'));
            if ($adminEmail) {
                Mail::raw(
                    "Nouveau message de contact:\n\n" .
                    "Nom: {$validated['name']}\n" .
                    "Email: {$validated['email']}\n" .
                    "Sujet: {$validated['subject']}\n\n" .
                    "Message:\n{$validated['message']}",
                    function ($mail) use ($adminEmail, $validated) {
                        $mail->to($adminEmail)
                            ->subject("Contact: {$validated['subject']}")
                            ->replyTo($validated['email'], $validated['name']);
                    }
                );
            }
        } catch (\Exception $e) {
            Log::warning('Failed to send contact notification email', [
                'contact_id' => $contactMessage->id,
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'Message envoyé avec succès!');
    }
}
