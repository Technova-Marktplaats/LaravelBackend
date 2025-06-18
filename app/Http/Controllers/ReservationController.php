<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Item;
use App\Models\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReservationController extends Controller
{
    /**
     * Toon alle reserveringen van de ingelogde gebruiker.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Gebruiker niet geauthenticeerd',
            ], 401);
        }

        $query = Reservation::with('item')
            ->where('borrower_id', $user->id);

        // Als item_id is meegegeven, filter dan op dat specifieke item
        if ($request->has('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        $reservations = $query->orderByDesc('start_date')->get();

        return response()->json([
            'success' => true,
            'data' => $reservations,
        ]);
    }

    /**
     * Toon alle reserveringen voor items die eigendom zijn van de ingelogde gebruiker.
     */
    public function myItemReservations(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Gebruiker niet geauthenticeerd',
            ], 401);
        }

        $query = Reservation::with(['item', 'borrower'])
            ->whereHas('item', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });

        // Als item_id is meegegeven, filter dan op dat specifieke item
        if ($request->has('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        $reservations = $query->orderByDesc('start_date')->get();

        return response()->json([
            'success' => true,
            'data' => $reservations,
        ]);
    }

    /**
     * Maak een nieuwe reservering aan voor een item.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Gebruiker niet geauthenticeerd',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'item_id'    => 'required|exists:items,id',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validatie mislukt',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $item      = Item::find($request->item_id);
        $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
        $endDate   = Carbon::parse($request->input('end_date'))->endOfDay();

        // Controleer of er overlappende reserveringen zijn.
        $overlap = Reservation::where('item_id', $item->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($q) use ($startDate, $endDate) {
                // Simpele overlap check: periodes overlappen als ze NIET volledig gescheiden zijn
                $q->where(function ($q2) use ($startDate, $endDate) {
                    // Overlap als: start_date <= nieuwe_end_date EN end_date >= nieuwe_start_date
                    $q2->whereDate('start_date', '<=', $endDate->toDateString())
                       ->whereDate('end_date', '>=', $startDate->toDateString());
                });
            })
            ->exists();

        if ($overlap) {
            return response()->json([
                'success' => false,
                'message' => 'De geselecteerde periode overlapt met een bestaande reservering.',
            ], 409);
        }

        try {
            $reservation = Reservation::create([
                'item_id'     => $item->id,
                'borrower_id' => $user->id,
                'start_date'  => $startDate,
                'end_date'    => $endDate,
                'status'      => 'pending',
            ]);

            Log::info('Reservering aangemaakt', [
                'reservation_id' => $reservation->id,
                'user_id'        => $user->id,
            ]);

            // Verstuur notificatie naar eigenaar van het item
            $eigenaar = $item->user;
            if ($eigenaar && $eigenaar->id !== $user->id) {
                Notification::create([
                    'user_id' => $eigenaar->id,
                    'item_id' => $item->id,
                    'message' => "Er is een nieuwe reservering aangemaakt voor je item '{$item->title}' door {$user->name}.",
                    'is_read' => false
                ]);
                
                Log::info('Notificatie verstuurd naar eigenaar', [
                    'eigenaar_id' => $eigenaar->id,
                    'item_id' => $item->id,
                    'reservation_id' => $reservation->id
                ]);
            }

            return response()->json([
                'success'     => true,
                'message'     => 'Reservering succesvol aangemaakt',
                'reservation' => $reservation,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Fout bij aanmaken reservering', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fout bij aanmaken reservering',
            ], 500);
        }
    }

    /**
     * Keur een reservering goed (alleen eigenaar van het item).
     */
    public function approve($id)
    {
        return $this->updateStatusInternal($id, 'confirmed');
    }

    /**
     * Wijs een reservering af (alleen eigenaar van het item).
     */
    public function reject($id)
    {
        return $this->updateStatusInternal($id, 'cancelled');
    }

    /**
     * Annuleer een pending reservering (alleen door aanvrager zelf).
     */
    public function destroy($id)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Gebruiker niet geauthenticeerd',
            ], 401);
        }

        // Zoek reservering
        $reservation = Reservation::with('item')->find($id);

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Reservering niet gevonden',
            ], 404);
        }

        // Alleen de aanvrager kan zijn eigen reservering annuleren
        if ($reservation->borrower_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Alleen de aanvrager kan zijn eigen reservering annuleren',
            ], 403);
        }

        // Alleen pending reserveringen kunnen geannuleerd worden
        if ($reservation->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Alleen wachtende reserveringen kunnen geannuleerd worden',
            ], 409);
        }

        // Verstuur notificatie naar eigenaar dat reservering is geannuleerd
        $eigenaar = $reservation->item->user;
        if ($eigenaar) {
            Notification::create([
                'user_id' => $eigenaar->id,
                'item_id' => $reservation->item_id,
                'message' => "De reservering voor je item '{$reservation->item->title}' is geannuleerd door {$user->name}.",
                'is_read' => false
            ]);
            
            Log::info('Notificatie verstuurd naar eigenaar - annulering door aanvrager', [
                'eigenaar_id' => $eigenaar->id,
                'item_id' => $reservation->item_id,
                'reservation_id' => $reservation->id
            ]);
        }

        $reservation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reservering succesvol geannuleerd',
        ]);
    }

    /**
     * Interne helper om status bij te werken.
     */
    private function updateStatusInternal($id, string $nieuweStatus)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Gebruiker niet geauthenticeerd',
            ], 401);
        }

        Log::info('Reservation status update attempt', [
            'reservation_id' => $id,
            'user_id' => $user->id,
            'new_status' => $nieuweStatus
        ]);

        // Zoek eerst de reservering
        $reservation = Reservation::with('item')->find($id);

        if ($reservation) {
            Log::info('Found reservation', [
                'reservation_id' => $reservation->id,
                'item_id' => $reservation->item_id,
                'item_owner_id' => $reservation->item->user_id,
                'borrower_id' => $reservation->borrower_id
            ]);
            
            // Extra debug: laat het hele item zien
            Log::info('Item details', [
                'item' => $reservation->item->toArray()
            ]);
            
            // Controleer ook direct in database
            $directItem = \App\Models\Item::find($reservation->item_id);
            Log::info('Direct item query', [
                'direct_item' => $directItem ? $directItem->toArray() : 'not found'
            ]);
        }

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Reservering niet gevonden',
                'debug' => [
                    'requested_id' => $id,
                    'user_id' => $user->id
                ]
            ], 404);
        }

        // Controleer of de huidige gebruiker eigenaar is van het item
        if ($reservation->item->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Alleen de eigenaar van het item kan reserveringen goedkeuren/afwijzen',
                'debug' => [
                    'item_owner_id' => $reservation->item->user_id,
                    'current_user_id' => $user->id,
                    'borrower_id' => $reservation->borrower_id,
                    'reservation_item_id' => $reservation->item_id,
                    'loaded_item_id' => $reservation->item->id,
                    'item_title' => $reservation->item->title
                ]
            ], 403);
        }

        if ($reservation->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Reservering is al verwerkt',
            ], 409);
        }

        // Check of het goedkeuren overlapt met andere goedgekeurde reservering
        if ($nieuweStatus === 'confirmed') {
            $overlap = Reservation::where('item_id', $reservation->item_id)
                ->where('id', '!=', $reservation->id) // Exclude current reservation
                ->where('status', 'confirmed')
                ->where(function ($q) use ($reservation) {
                    // Simpele overlap check (same as in store method)
                    $q->whereDate('start_date', '<=', $reservation->end_date->toDateString())
                      ->whereDate('end_date', '>=', $reservation->start_date->toDateString());
                })
                ->exists();

            if ($overlap) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kan niet bevestigen: overlapt met bestaande bevestigde reservering.',
                ], 409);
            }
        }

        $reservation->status = $nieuweStatus;
        $reservation->save();

        // Als reservatie wordt bevestigd, zet item op niet beschikbaar
        if ($nieuweStatus === 'confirmed') {
            $reservation->item->update(['available' => false]);
            Log::info("Item {$reservation->item->id} ({$reservation->item->title}) niet meer beschikbaar na bevestiging reservatie {$reservation->id}");
            
            // Verstuur notificatie naar aanvrager dat reservatie is goedgekeurd
            $aanvrager = $reservation->borrower;
            if ($aanvrager) {
                Notification::create([
                    'user_id' => $aanvrager->id,
                    'item_id' => $reservation->item_id,
                    'message' => "Goed nieuws! Je reservering voor '{$reservation->item->title}' is goedgekeurd door de eigenaar.",
                    'is_read' => false
                ]);
                
                Log::info('Notificatie verstuurd naar aanvrager - goedgekeurd', [
                    'aanvrager_id' => $aanvrager->id,
                    'item_id' => $reservation->item_id,
                    'reservation_id' => $reservation->id
                ]);
            }
        }
        
        // Als reservatie wordt afgewezen, verstuur notificatie en verwijder reservatie
        if ($nieuweStatus === 'cancelled') {
            // Verstuur notificatie naar aanvrager dat reservatie is afgewezen
            $aanvrager = $reservation->borrower;
            if ($aanvrager) {
                Notification::create([
                    'user_id' => $aanvrager->id,
                    'item_id' => $reservation->item_id,
                    'message' => "Je reservering voor '{$reservation->item->title}' is helaas afgewezen door de eigenaar.",
                    'is_read' => false
                ]);
                
                Log::info('Notificatie verstuurd naar aanvrager - afgewezen', [
                    'aanvrager_id' => $aanvrager->id,
                    'item_id' => $reservation->item_id,
                    'reservation_id' => $reservation->id
                ]);
            }
            
            // Controleer of er geen andere bevestigde reserveringen zijn voor dit item
            $andereBevestigdeReserveringen = Reservation::where('item_id', $reservation->item_id)
                ->where('id', '!=', $reservation->id)
                ->where('status', 'confirmed')
                ->exists();
            
            if (!$andereBevestigdeReserveringen) {
                $reservation->item->update(['available' => true]);
                Log::info("Item {$reservation->item->id} ({$reservation->item->title}) weer beschikbaar na afwijzing reservatie {$reservation->id}");
            }
            
            // Verwijder de afgewezen reservatie
            $reservatieId = $reservation->id;
            $itemTitel = $reservation->item->title;
            $reservation->delete();
            
            Log::info("Afgewezen reservatie {$reservatieId} voor item '{$itemTitel}' verwijderd");
            
            return response()->json([
                'success' => true,
                'message' => 'Reservering afgewezen en verwijderd',
            ]);
        }

        return response()->json([
            'success'     => true,
            'message'     => 'Status succesvol bijgewerkt',
            'reservation' => $reservation,
        ]);
    }
} 