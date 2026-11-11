<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Game;
use App\Models\Order;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function home(): View
    {
        $categories = Category::query()
            ->active()
            ->with(['games' => fn ($query) => $query->active()])
            ->get();

        return view('storefront.home', ['categories' => $categories]);
    }

    public function game(Game $game): View
    {
        abort_unless($game->is_active, 404);

        $products = $game->products()
            ->active()
            ->withStock()
            ->get()
            ->groupBy(fn ($product) => $product->type->getLabel());

        return view('storefront.game', ['game' => $game, 'products' => $products]);
    }

    public function invoice(Order $order): View
    {
        $order->load(['game', 'gameAccount']);

        return view('storefront.invoice', ['order' => $order]);
    }
}
