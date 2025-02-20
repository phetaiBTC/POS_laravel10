<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MenuPrice;
use Illuminate\Support\Facades\Validator;

class MenuPriceController extends Controller
{
    public function index()
    {
        return response()->json(MenuPrice::with('menuItem')->get());
    }

    public function store(Request $request)
    {
        $lastMenuPrice = MenuPrice::orderBy('id', 'desc')->first();
        if($lastMenuPrice){
            $lastMenuPrice->status = false;
            $lastMenuPrice->save();
        }
        $validater = Validator::make($request->all(), [
            'menu_item_id' => 'required|exists:menu_items,id',
            'price' => 'required|numeric|min:0',
            'status' => 'required|boolean',
        ]);
        if ($validater->fails()) {
            return response()->json($validater->errors(), 422);
        }
        $menuPrice = MenuPrice::create($request->all());

        return response()->json($menuPrice, 201);
    }

    public function show($menuPrice)
    {
        $menuPrice = MenuPrice::with('menuItem')->find($menuPrice);
        if(!$menuPrice) {
            return response()->json(['message' => 'Menu price not found'], 404);
        }
        return response()->json([
            'id' => $menuPrice->id,
            'menu_item_id' => $menuPrice->menu_item_id,
            'price' => $menuPrice->price,
            'status' => $menuPrice->status,
            'created_at' => $menuPrice->created_at->format('d-m-Y H:i:s'),
            'updated_at' => $menuPrice->updated_at->format('d-m-Y H:i:s'),
            'menu_item' => $menuPrice->menuItem
        ]);
    }
}
