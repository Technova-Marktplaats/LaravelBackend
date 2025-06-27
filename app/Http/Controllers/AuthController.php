<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Gebruiker succesvol geregistreerd',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer'
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['De ingevoerde gegevens zijn onjuist.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Succesvol ingelogd',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'message' => 'Gebruikersgegevens opgehaald',
            'user' => $request->user()
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Succesvol uitgelogd'
        ]);
    }

    /**
     * Stuur de gebruiker door naar Google voor authenticatie (SPA-flow).
     * Geeft enkel de redirect-URL terug zodat de frontend de browser
     * kan omleiden.  We gebruiken een stateless flow omdat er geen
     * sessie aanwezig is in een API-context.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function redirectToGoogle()
    {
        try {
            // Start een sessie voor Socialite
            if (!session()->isStarted()) {
                session()->start();
            }
            
            $driver = Socialite::driver('google');
            $url = $driver->redirect()->getTargetUrl();

            return response()->json([
                'url' => $url,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'Er is een fout opgetreden bij het verbinden met Google',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bepaal de juiste frontend URL op basis van de request bron.
     *
     * @param  Request  $request
     * @return string
     */
    private function getFrontendUrl(Request $request)
    {
        $host = $request->getHost();
        
        // Controleer of we op localhost draaien
        if (in_array($host, ['localhost', '127.0.0.1', '::1'])) {
            return 'http://localhost:5173/api/';
        }
        
        // Voor productie omgeving - bepaal frontend URL op basis van backend host
        if (str_contains($host, 'srv856957.hstgr.cloud')) {
            return 'https://srv856957.hstgr.cloud/api/'; // Pas dit aan naar je frontend URL
        }
        
        // Fallback naar localhost
        return 'http://localhost:5173';
    }

    /**
     * Verwerk de callback van Google en redirect naar frontend.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            // Start een sessie voor Socialite
            if (!session()->isStarted()) {
                session()->start();
            }
            
            $driver = Socialite::driver('google');
            $googleUser = $driver->user();

            $user = User::firstOrCreate(
                ['email' => $googleUser->email],
                [
                    'name'      => $googleUser->name,
                    'password'  => Hash::make(uniqid()),
                    'google_id' => $googleUser->id,
                ],
            );

            $token = $user->createToken('auth_token')->plainTextToken;

            $frontendUrl = $this->getFrontendUrl($request);

            // Redirect naar frontend met success data
            return redirect($frontendUrl . '/auth/google/callback?' . http_build_query([
                'success' => 'true',
                'token' => $token,
                'user' => base64_encode(json_encode([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]))
            ]));
        } catch (\Throwable $e) {
            $frontendUrl = $this->getFrontendUrl($request);
            
            // Redirect naar frontend met error
            return redirect($frontendUrl . '/auth/google/callback?' . http_build_query([
                'success' => 'false',
                'error' => 'Er is een fout opgetreden bij het inloggen met Google'
            ]));
        }
    }

    /**
     * Update gebruikersprofiel (naam en locatie).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'location_lat' => 'nullable|numeric|between:-90,90',
            'location_lon' => 'nullable|numeric|between:-180,180',
        ]);

        $user = $request->user();
        
        $user->update([
            'name' => $request->name,
            'location_lat' => $request->location_lat,
            'location_lon' => $request->location_lon,
        ]);

            return response()->json([
            'message' => 'Profiel succesvol bijgewerkt',
            'user' => $user->fresh()
        ]);
    }
}
