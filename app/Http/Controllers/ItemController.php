<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\ItemImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ItemController extends Controller
{
    public function index()
    {
        $items = Item::with(['images', 'reservations', 'category'])->get();
        return response()->json($items);
    }

    /**
     * Haal alle items van de ingelogde gebruiker op
     */
    public function myItems()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gebruiker niet geauthenticeerd'
                ], 401);
            }

            $items = Item::with(['images', 'reservations', 'category'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $items
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fout bij ophalen van items: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $item = Item::with(['images', 'reservations', 'category', 'user:id,location_lat,location_lon'])->find($id);
        
        if (!$item) {
            return response()->json(['message' => 'Item niet gevonden'], 404);
        }
        
        // Controleer of huidige gebruiker dit item op watchlist heeft
        $user = Auth::user();
        $opWatchlist = false;
        
        if ($user) {
            $opWatchlist = \App\Models\Watchlist::where('user_id', $user->id)
                ->where('item_id', $id)
                ->exists();
        }
        
        // Voeg de boolean toe aan het item object
        $itemArray = $item->toArray();
        $itemArray['op_watchlist'] = $opWatchlist;
        
        return response()->json($itemArray);
    }

    public function store(Request $request)
    {
        try {
            // Debug logging
            Log::info('Item store request data:', [
                'all_data' => $request->all(),
                'has_files' => $request->hasFile('images'),
                'files_info' => $request->hasFile('images') ? 
                    collect($request->file('images'))->map(function($file) {
                        return [
                            'name' => $file->getClientOriginalName(),
                            'size' => $file->getSize(),
                            'mime' => $file->getMimeType(),
                            'valid' => $file->isValid()
                        ];
                    })->toArray() : []
            ]);
    
            // Eenvoudige validatie eerst
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'category_id' => 'required|exists:categories,id',
                'available' => 'nullable|boolean',
            ]);
    
            // Alleen afbeelding validatie toevoegen als er files zijn
            if ($request->hasFile('images')) {
                $validator->addRules([
                    'images' => 'array|max:5',
                    'images.*' => 'file|mimes:jpeg,jpg,png,gif,webp,avif,bmp|max:5120' // AVIF toegevoegd
                ]);
            }
    
            if ($validator->fails()) {
                Log::error('Validation failed:', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Validatie mislukt',
                    'errors' => $validator->errors()
                ], 422);
            }
    
            // Haal de ingelogde gebruiker op
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gebruiker niet geauthenticeerd'
                ], 401);
            }
    
            // Maak het item aan
            $itemData = $request->only(['title', 'description', 'category_id']);
            $itemData['user_id'] = $user->id;
            $itemData['available'] = $request->boolean('available', true);
    
            $item = Item::create($itemData);
            Log::info('Item created:', ['item_id' => $item->id]);
    
            // Verwerk geüploade afbeeldingen
            $uploadedImages = [];
            if ($request->hasFile('images') && is_array($request->file('images'))) {
                
                // Zorg ervoor dat storage/app/public/items directory bestaat
                if (!Storage::disk('public')->exists('items')) {
                    Storage::disk('public')->makeDirectory('items');
                }
                
                foreach ($request->file('images') as $index => $image) {
                    if ($image && $image->isValid()) {
                        try {
                            // Genereer unieke filename
                            $filename = time() . '_' . $index . '_' . $image->getClientOriginalName();
                            
                            // Sla afbeelding op in storage/app/public/items
                            $relativePath = 'items/' . $filename;
                            Storage::disk('public')->put($relativePath, file_get_contents($image));
                            $imagePath = $relativePath;
                            
                            Log::info('Image stored:', [
                                'path' => $imagePath,
                                'filename' => $filename,
                                'url' => Storage::url($imagePath)
                            ]);
                            
                            // Maak ItemImage record aan (zonder filename veld!)
                            $itemImage = ItemImage::create([
                                'item_id' => $item->id,
                                'url' => url(Storage::url($imagePath))
                            ]);
    
                            $uploadedImages[] = $itemImage;
                            
                        } catch (\Throwable $e) {
                            Log::error('Image upload failed:', [
                                'error' => $e->getMessage(),
                                'image_index' => $index
                            ]);
                        }
                    } else {
                        Log::warning('Invalid image file:', ['index' => $index]);
                    }
                }
            }
    
            // Laad het item opnieuw met relaties
            $item->load(['images', 'category', 'user']);
    
            Log::info('Item creation completed:', [
                'item_id' => $item->id,
                'images_uploaded' => count($uploadedImages)
            ]);
    
            return response()->json([
                'success' => true,
                'message' => 'Item succesvol aangemaakt',
                'data' => $item,
                'uploaded_images_count' => count($uploadedImages)
            ], 201);
    
        } catch (\Exception $e) {
            Log::error('Item creation failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Fout bij aanmaken item: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $item = Item::find($id);
            
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item niet gevonden'
                ], 404);
            }

            // Controleer of gebruiker eigenaar is
            $user = Auth::user();
            if (!$user || $item->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Geen toestemming om dit item te bewerken'
                ], 403);
            }

            // Validatie
            $validator = Validator::make($request->all(), [
                'title' => 'string|max:255',
                'description' => 'nullable|string|max:1000',
                'category_id' => 'exists:categories,id',
                'available' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validatie mislukt',
                    'errors' => $validator->errors()
                ], 422);
            }

            $item->update($request->only(['title', 'description', 'category_id', 'available']));
            $item->load(['images', 'category', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Item succesvol bijgewerkt',
                'data' => $item
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fout bij bijwerken item: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $item = Item::find($id);
            
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item niet gevonden'
                ], 404);
            }

            // Controleer of gebruiker eigenaar is
            $user = Auth::user();
            if (!$user || $item->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Geen toestemming om dit item te verwijderen'
                ], 403);
            }

            // Controleer of item een actieve reservering heeft
            $heeftActieveReservering = $item->reservations()
                ->whereIn('status', ['pending', 'confirmed'])
                ->whereDate('end_date', '>=', now())
                ->exists();

            if ($heeftActieveReservering) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item kan niet verwijderd worden, er is een lopende of aanstaande reservering.'
                ], 409);
            }

            // Verwijder bijbehorende afbeeldingen van disk
            foreach ($item->images as $image) {
                // Haal het pad op vanaf de URL
                $path = str_replace('/storage/', '', parse_url($image->url, PHP_URL_PATH));
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Item succesvol verwijderd'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fout bij verwijderen item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verwijder een specifieke afbeelding van een item
     */
    public function deleteImage($itemId, $imageId)
    {
        try {
            $item = Item::find($itemId);
            
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item niet gevonden'
                ], 404);
            }

            // Controleer of gebruiker eigenaar is
            $user = Auth::user();
            if (!$user || $item->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Geen toestemming om deze afbeelding te verwijderen'
                ], 403);
            }

            $image = ItemImage::where('id', $imageId)
                ->where('item_id', $itemId)
                ->first();

            if (!$image) {
                return response()->json([
                    'success' => false,
                    'message' => 'Afbeelding niet gevonden'
                ], 404);
            }

            // Verwijder bestand van disk
            $path = str_replace('/storage/', '', parse_url($image->url, PHP_URL_PATH));
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            // Verwijder database record
            $image->delete();

            return response()->json([
                'success' => true,
                'message' => 'Afbeelding succesvol verwijderd'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fout bij verwijderen afbeelding: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Haal share link op of maak een nieuwe aan
     */
    public function getShareLink($id)
    {
        try {
            $item = Item::find($id);
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item niet gevonden'
                ], 404);
            }

            // Controleer of de ingelogde gebruiker eigenaar is
            $user = Auth::user();
            if (!$user || $item->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Geen toestemming om dit item te delen'
                ], 403);
            }

            // Als er al een share_link is, geef die terug
            if ($item->share_link) {
                return response()->json([
                    'success' => true,
                    'share_link' => $item->share_link,
                    'share_url' => $item->share_url,
                    'message' => 'Bestaande share link gevonden'
                ]);
            }

            // Anders maak een nieuwe share link aan
            $shareToken = Str::uuid()->toString();
            $item->update(['share_link' => $shareToken]);

            return response()->json([
                'success' => true,
                'share_link' => $shareToken,
                'share_url' => url('/shared/' . $shareToken),
                'message' => 'Share link aangemaakt'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fout bij ophalen/aanmaken share link: ' . $e->getMessage()
            ], 500);
        }
    }
}
