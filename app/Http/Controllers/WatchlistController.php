<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Watchlist;
use Illuminate\Support\Facades\Auth;

class WatchlistController extends Controller
{
    public function add($id)
    {
        $item = Item::find($id);
        $user = Auth::user();
        
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item niet gevonden'
            ], 404);
        }
        
        // Controleer of item al in watchlist staat
        $bestaatAlInWatchlist = Watchlist::where('user_id', $user->id)
            ->where('item_id', $id)
            ->exists();
        
        if ($bestaatAlInWatchlist) {
            return response()->json([
                'success' => false,
                'message' => 'Item staat al in je watchlist'
            ], 409);
        }
        
        // Voeg toe aan watchlist
        Watchlist::create([
            'user_id' => $user->id,
            'item_id' => $id
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Item toegevoegd aan watchlist'
        ]);
    }
    
    public function remove($id)
    {
        $user = Auth::user();
        
        $watchlistItem = Watchlist::where('user_id', $user->id)
            ->where('item_id', $id)
            ->first();
        
        if (!$watchlistItem) {
            return response()->json([
                'success' => false,
                'message' => 'Item staat niet in je watchlist'
            ], 404);
        }
        
        $watchlistItem->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Item verwijderd uit watchlist'
        ]);
    }
}
