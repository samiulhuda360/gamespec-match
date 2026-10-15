<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\App;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    /**
     * Display the user's wishlist
     */
    public function index()
    {
        $wishlists = Auth::user()->wishlists()
            ->with(['app' => function($query) {
                $query->with(['pricing' => function($q) {
                    $q->where('currency', 'USD');
                }, 'reviews']);
            }])
            ->latest()
            ->get();

        return view('wishlist.index', compact('wishlists'));
    }

    /**
     * Add a game to the user's wishlist
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'app_id' => 'required|exists:apps,app_id',
            'notes' => 'nullable|string|max:1000',
            'target_price' => 'nullable|numeric|min:0',
            'notify_on_sale' => 'boolean',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['notify_on_sale'] = $request->boolean('notify_on_sale', true);

        try {
            $wishlist = Wishlist::create($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Game added to wishlist',
                    'wishlist' => $wishlist
                ]);
            }

            return redirect()->back()->with('success', 'Game added to your wishlist!');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Game is already in your wishlist'
                ], 422);
            }

            return redirect()->back()->with('error', 'This game is already in your wishlist');
        }
    }

    /**
     * Update wishlist item (notes, target price, notifications)
     */
    public function update(Request $request, Wishlist $wishlist)
    {
        // Ensure user owns this wishlist item
        if ($wishlist->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
            'target_price' => 'nullable|numeric|min:0',
            'notify_on_sale' => 'boolean',
        ]);

        $wishlist->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Wishlist updated',
                'wishlist' => $wishlist
            ]);
        }

        return redirect()->back()->with('success', 'Wishlist updated!');
    }

    /**
     * Remove a game from the user's wishlist
     */
    public function destroy(Wishlist $wishlist)
    {
        // Ensure user owns this wishlist item
        if ($wishlist->user_id !== Auth::id()) {
            abort(403);
        }

        $wishlist->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Game removed from wishlist'
            ]);
        }

        return redirect()->back()->with('success', 'Game removed from your wishlist');
    }

    /**
     * Check if a game is in the user's wishlist (AJAX)
     */
    public function check($appId)
    {
        $inWishlist = Wishlist::where('user_id', Auth::id())
            ->where('app_id', $appId)
            ->exists();

        return response()->json(['in_wishlist' => $inWishlist]);
    }
}
