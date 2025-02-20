<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\MenuPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MenuItemController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin|vendor')->only('store');
    }
    public function index()
    {
        $menuItems = MenuItem::with('vendor')->get();
        $mappedMenuItems = $menuItems->map(function ($menuItem) {
            return [
                'id' => $menuItem->id,
                'name' => $menuItem->name,
                'description' => $menuItem->description,
                'vendor_id' => $menuItem->vendor_id,
                'vendor_name' => $menuItem->vendor->name,
                'image' => $menuItem->primaryImage ? asset('storage/' . $menuItem->primaryImage->url) : null,
                'price' => optional($menuItem->menuPrices->firstWhere('status', 1))->price,
                'created_at' => $menuItem->created_at->format('d-m-Y H:i:s'),
                'updated_at' => $menuItem->updated_at->format('d-m-Y H:i:s'),
            ];
        });
        return response()->json($mappedMenuItems, 200);
    }
    public function store(Request $request)
    {
        $validater = Validator::make($request->all(), [
            'vendor_id' => 'required|exists:vendors,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'=> 'required|numeric|min:0',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // รองรับหลายไฟล์
        ]);

        if ($validater->fails()) {
            return response()->json($validater->errors(), 422);
        }

        // สร้าง MenuItem
        $menuItem = MenuItem::create([
            'vendor_id' => $request->vendor_id,
            'name' => $request->name,
            'description' => $request->description,
        ]);
        MenuPrice::create([
            'menu_item_id' => $menuItem->id,
            'price' => $request->price
        ]);


        // อัปโหลดรูปภาพถ้ามี
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('menu_items', 'public'); // บันทึกลง storage/app/public/menu_items
                $menuItem->images()->create(['url' => $path]);
            }
        }

        return response()->json($menuItem->load('images'), 201);
    }
    public function show($menuItem)
    {
        $menuItem = MenuItem::find($menuItem);
        return response()->json([
            'id' => $menuItem->id,
            'name' => $menuItem->name,
            'description' => $menuItem->description,
            'vendor_id' => $menuItem->vendor_id,
            'vendor_name' => $menuItem->vendor->name,
            'created_at' => $menuItem->created_at->format('d-m-Y H:i:s'),
            'updated_at' => $menuItem->updated_at->format('d-m-Y H:i:s'),
        ],);
    }
    public function update(Request $request, $menuItem)
    {
        $validater = Validator::make($request->all(), [
            'vendor_id' => 'required|exists:vendors,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'=> 'required|numeric|min:0',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validater->fails()) {
            return response()->json($validater->errors(), 422);
        }
        $menuItem = MenuItem::find($menuItem);
        if (!$menuItem) {
            return response()->json(['message' => 'Menu item not found'], 404);
        }
        $menuItem->update([
            'vendor_id' => $request->vendor_id,
            'name' => $request->name,
            'description' => $request->description,
        ]);
        MenuPrice::where('menu_item_id', $menuItem->id)->update([
            'price' => $request->price
        ]);

        // อัปโหลดรูปภาพถ้ามี
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('menu_items', 'public'); // บันทึกลง storage/app/public/menu_items
                $menuItem->images()->create(['url' => $path]);
            }
        }

        return response()->json($menuItem->load('images'), 200);
    }
    public function destroy($menuItem)
    {
        $menuItem = MenuItem::find($menuItem);
        if (!$menuItem) {
            return response()->json(['message' => 'Menu item not found'], 404);
        }
        $menuItem->delete();
        return response()->json(['message' => 'Menu item deleted']);
    }
}
