<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\OrderPayment;
use App\Models\OrderItem;
use App\Models\MenuItem;

class OrderController extends Controller
{
    public function __construct()
    {
        // $this->middleware('role:customer')->only('store');
        // $this->middleware('role:admin|vendor')->except('store');
    }

    public function index()
    {
        try {
            $order = Order::all();
            $mapper = $order->map(function ($i) {
                $order_items = $i->orderItems;
                $mapOrderitems = $order_items->map(function ($j) {
                    return [
                        'menu_item_id' => $j->menu_item_id,
                        'menu_item_name' => $j->menuItem->name,
                        'price' => $j->price,
                        'quantity' => $j->quantity,
                        'subtotal' => $j->subtotal
                    ];
                });
                return [
                    'id' => $i->id,
                    'user_id' => $i->user_id,
                    "status" => $i->status,
                    "total_price" => $i->total_price,
                    'created_at' => $i->created_at->format('d-m-Y H:i:s'),
                    'updated_at' => $i->updated_at->format('d-m-Y H:i:s'),
                    'order_items' => $mapOrderitems,
                    'order_payments' => $i->orderPayments->map(function ($p) {
                        return [
                            'vendor_id' => $p->vendor_id,
                            'vendor_name' => $p->vendor->name,
                            'amount' => $p->amount,
                            'payment_method' => $p->payment_method,
                            'payment_id'=>$p->id,
                            'status' => $p->status,
                            'transaction_id' => $p->transaction_id
                        ];
                    })
                ];
            });
            return response()->json($mapper, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to retrieve order', 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payments' => 'required|array|min:1',
            'payments.*.payment_method' => 'required|in:cash,qr_code',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        try {
            DB::beginTransaction();

            // 1️⃣ สร้าง Order
            $order = Order::create([
                'user_id' => $request->user_id,
                'status' => 'pending',
                'total_price' => 0
            ]);

            $totalPrice = 0;
            $vendorId = null; // ใช้ตัวแปรนี้เก็บ vendor_id

            // 2️⃣ เพิ่ม Order Items และคำนวณราคา
            foreach ($request->items as $item) {
                $menuItem = MenuItem::findOrFail($item['menu_item_id']);
                $price = optional($menuItem->price)->price ?? 0;
                $subtotal = $price * $item['quantity'];
                $totalPrice += $subtotal;

                // บันทึก vendor_id ของสินค้ารายการแรก
                if (!$vendorId) {
                    $vendorId = $menuItem->vendor_id;
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $item['menu_item_id'],
                    'vendor_id' => $menuItem->vendor_id,
                    'price' => $price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $subtotal
                ]);
            }

            // อัปเดตราคาสั่งซื้อ
            $order->update(['total_price' => $totalPrice]);

            // 3️⃣ เพิ่ม Order Payment โดยใช้ vendor_id จากสินค้าแรก
            foreach ($request->payments as $payment) {
                OrderPayment::create([
                    'order_id' => $order->id,
                    'vendor_id' => $vendorId, // ดึง vendor_id จาก Order Items
                    'amount' => $totalPrice,
                    'payment_method' => $payment['payment_method'],
                    'status' => 'pending'
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Order created successfully', 'order_id' => $order->id], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create order', 'message' => $e->getMessage()], 500);
        }
    }

    public function show($order)
    {
        try {
            $order = Order::find($order);
            return response()->json([
                'id' => $order->id,
                'user_id' => $order->user_id,
                "status" => $order->status,
                "total_price" => $order->total_price,
                'created_at' => $order->created_at->format('d-m-Y H:i:s'),
                'updated_at' => $order->updated_at->format('d-m-Y H:i:s'),
                'order_items' => $order->orderItems->map(function ($j) {
                    return [
                        'menu_item_id' => $j->menu_item_id,
                        'menu_item_name' => $j->menuItem->name,
                        'price' => $j->price,
                        'quantity' => $j->quantity,
                        'subtotal' => $j->subtotal
                    ];
                }),

                'order_payments' => $order->orderPayments->map(function ($p) {
                    return [
                        'vendor_id' => $p->vendor_id,
                        'vendor_name' => $p->vendor->name,
                        'amount' => $p->amount,
                        'payment_method' => $p->payment_method,
                        'status' => $p->status,
                        'transaction_id' => $p->transaction_id
                    ];
                })
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to retrieve order', 'message' => $e->getMessage()], 500);
        }
    }
    public function delete($order)
    {
        try {
            $order = Order::find($order);
            $order->delete();
            return response()->json(['message' => 'Order deleted successfully']);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to delete order', 'message' => $e->getMessage()], 500);
        }
    }
    public function updateStatus($Pid)
    {
        // return response()->json(['message' => 'Payment status updated successfully']);
        try {
            $payment = OrderPayment::find($Pid);
            $payment->status = 'paid';
            $payment->save();
            return response()->json(['message' => 'Payment status updated successfully']);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to update payment status', 'message' => $e->getMessage()], 500);
        }
    }
    public function orderStatus($Order,Request $request){
        try {
            $order = Order::find($Order);
            $order->status = $request->status;
            $order->save();
            return response()->json(['message' => 'Order status updated successfully']);
        }catch (Exception $e) {
            return response()->json(['error' => 'Failed to update payment status', 'message' => $e->getMessage()], 500);
        }
    }
}
