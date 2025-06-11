<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * Haal alle actieve categorieën op
     */
    public function index()
    {
        try {
            $categories = Category::active()
                ->ordered()
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'Categorieën succesvol opgehaald'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fout bij ophalen categorieën: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Haal items op per categorie (via slug)
     */
    public function getItemsByCategory($categorySlug)
    {
        try {
            // Controleer of categorie bestaat
            $category = Category::where('slug', $categorySlug)
                ->where('active', true)
                ->first();

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Categorie niet gevonden'
                ], 404);
            }

            $items = Item::where('category_id', $category->id)
                ->where('available', true)
                ->with(['user', 'images', 'category'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'category' => $category,
                    'items' => $items
                ],
                'message' => "Items voor categorie '{$category->name}' succesvol opgehaald"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fout bij ophalen items: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toon specifieke categorie details
     */
    public function show($slug)
    {
        try {
            $category = Category::where('slug', $slug)
                ->where('active', true)
                ->with(['items' => function($query) {
                    $query->where('available', true)->with(['user', 'images']);
                }])
                ->first();

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Categorie niet gevonden'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Categorie succesvol opgehaald'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fout bij ophalen categorie: ' . $e->getMessage()
            ], 500);
        }
    }
} 