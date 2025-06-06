<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\ItemImage;

class ItemController extends Controller
{
    public function index()
    {
        $items = Item::with(['images', 'reservations'])->get();
        return response()->json($items);
    }

    public function show($id)
    {
        $item = Item::with(['images', 'reservations'])->find($id);
        
        if (!$item) {
            return response()->json(['message' => 'Item niet gevonden'], 404);
        }
        
        return response()->json($item);
    }

    public function store(Request $request)
    {
        $item = Item::create($request->all());
        return response()->json($item, 201);
    }

    public function update(Request $request, $id)
    {
        $item = Item::find($id);
        $item->update($request->all());
        return response()->json($item);
    }

    public function destroy($id)
    {
        $item = Item::find($id);
        $item->delete();
        return response()->json(null, 204);
    }

    
    
}
