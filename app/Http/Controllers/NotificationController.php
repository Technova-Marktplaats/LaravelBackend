<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Haal alle notificaties van de ingelogde gebruiker op
     */
    public function index()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gebruiker niet geauthenticeerd'
                ], 401);
            }

            $notificaties = Notification::with(['item:id,title'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $notificaties
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fout bij ophalen van notificaties: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Markeer een notificatie als gelezen
     */
    public function markAsRead($id)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gebruiker niet geauthenticeerd'
                ], 401);
            }

            $notificatie = Notification::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$notificatie) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notificatie niet gevonden'
                ], 404);
            }

            $notificatie->update(['is_read' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Notificatie gemarkeerd als gelezen'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fout bij updaten van notificatie: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Markeer alle notificaties als gelezen
     */
    public function markAllAsRead()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gebruiker niet geauthenticeerd'
                ], 401);
            }

            $aantalGeupdate = Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return response()->json([
                'success' => true,
                'message' => "{$aantalGeupdate} notificatie(s) gemarkeerd als gelezen"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fout bij updaten van notificaties: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Haal aantal ongelezen notificaties op
     */
    public function getUnreadCount()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gebruiker niet geauthenticeerd'
                ], 401);
            }

            $ongelezen = Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();

            return response()->json([
                'success' => true,
                'ongelezen_count' => $ongelezen
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fout bij ophalen van ongelezen count: ' . $e->getMessage()
            ], 500);
        }
    }
}
