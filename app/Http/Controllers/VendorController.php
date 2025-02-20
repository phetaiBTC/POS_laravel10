<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VendorController extends Controller
{
    public function __construct() {
        $this->middleware('role:admin|vendor');
    }
    public function index() {
        return response()->json(Vendor::all());
    }

    public function store(Request $request) {
        $vaildator = Validator::make($request->all(), [
            'name' => 'required',
            'owner_id' => 'required|exists:users,id',
        ]);
        if ($vaildator->fails()) {
            return response()->json($vaildator->errors(), 422);
        }
        $vendor = Vendor::create($request->all());
        return response()->json($vendor, 201);
    }

    public function show(Vendor $vendor) {
        return response()->json($vendor);
    }

    public function update(Request $request, Vendor $vendor) {
        $vaildator = Validator::make($request->all(), [
            'name' => 'required',
            'owner_id' => 'required|exists:users,id',
        ]);
        if ($vaildator->fails()) {
            return response()->json($vaildator->errors(), 422);
        }
        $vendor->update($request->all());
        return response()->json($vendor);
    }
    public function destroy($vendor) {
        $vendor = Vendor::find($vendor);
        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }
        $vendor->delete();
        return response()->json(['message' => 'Vendor deleted']);
    }
}
