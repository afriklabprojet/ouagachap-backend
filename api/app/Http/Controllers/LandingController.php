<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function index()
    {
        // Récupérer tous les paramètres du site
        $settings = SiteSetting::getAll();
        
        return view('landing', compact('settings'));
    }
    
    public function contact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);
        
        // TODO: Envoyer email ou stocker en base
        // Mail::to(SiteSetting::get('contact_email'))->send(new ContactMail($validated));
        
        return back()->with('success', 'Message envoyé avec succès!');
    }
}
