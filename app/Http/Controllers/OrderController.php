<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::recentOrders()->get();
        return view('order_list', compact('orders'));
    }

    public function detail($id)
    {
        $order = Order::find($id);
        $orders = Order::recentOrders()->get();
        $order_details = OrderDetail::where('order_id', $id)->get();
        $this->authenticateByIP($id);
        $authenticated = session()->get("order_{$order->id}", 'false');

        return view('order_detail', compact(
            'order',
            'orders',
            'order_details',
            'authenticated',
        ));
    }

    public function add(Request $request)
    {
        $request->validate([
            'order_data_format' => 'nullable',
        ]);

        $orderDataFormat = $request->get('order_data_format', 'text');

        switch ($orderDataFormat) {
            case 'text':
                return $this->handleAddData($request);
            case 'json':
                return $this->handleAddJsonData($request);
            default:
                Log::warning("Invalid order data format.");
                abort(422);
        }
    }

    private function handleAddData(Request $request)
    {
        $request->validate([
            'order_data_format' => 'nullable',
            'order_data' => 'required|string',
            'order_name' => 'required|string',
            'order_secret' => 'required|string',
        ]);

        $data = $request->get('order_data');

        $items = explode("$", $data);
        // remove first empty item
        unset($items[0]);
        $orderAggregation = [];
        $userOrderAggregation = [];

        foreach ($items as $item) {
            preg_match('/([1-9][0-9]{0,15})(.*)(\*)([1-9][0-9]{0,15})( )(\()(.*)(\))/', $item, $match);
            $itemPrice = $match[1];
            $itemName  = str_replace(" ", "", $match[2]);
            $itemQuantity = intval($match[4]);
            $userOrderListString = $match[7];
            $this->aggregateUserOrder(
                $itemName,
                $itemPrice,
                $userOrderListString,
                $orderAggregation,
                $userOrderAggregation
            );

            if ($itemQuantity !== $orderAggregation['itemsAmount'][$itemName]) {
                return redirect()->route('order.list')->with('alert',
                    sprintf('【%s】Quantity (*%d) and order count (*%d) mismatch，Please check first!',
                        $itemName,
                        $itemQuantity,
                        $orderAggregation['itemsAmount'][$itemName]
                    )
                );
            }
        }

        $orderDetails = [];
        foreach ($userOrderAggregation as $name => $userOrder) {
            $orderDetails[] = [
                'name' => $name,
                'items' => implode(
                    '、',
                    array_map(
                        fn($key, $value) => "$key*$value",
                        array_keys($userOrder['itemsAmount']),
                        $userOrder['itemsAmount']
                    )
                ),
                'total_price' => $userOrder['totalPrice'],
            ];
        }

        DB::beginTransaction();

        try {
            $order = Order::create([
                'order_name' => $request->get('order_name'),
                'date' => date("Y-m-d H:i:s"),
                'data' => json_encode($userOrderAggregation),
                'items_amount' => array_sum($orderAggregation['itemsAmount']),
                'total_price' => $orderAggregation['totalPrice'],
                'secret' => Hash::make($request->get('order_secret')),
            ]);

            $order->orderDetails()->createMany($orderDetails);

            DB::commit();

            return redirect()->route('order.detail', ['id' => $order->id]);
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return redirect()->route('order.list')->with('alert', 'Database write error'. $e->getMessage());
        }

    }

    private function handleAddJsonData(Request $request) {
        $request->validate([
            'order_name' => 'nullable|string',
            'order_data' => 'required|array',
            'items_amount' => 'required',
            'total_price' => 'required',
            'secret' => 'required',
        ]);

        DB::beginTransaction();

        try {
            $order = Order::create([
                'order_name' => $request->input('order_name'),
                'date' => date("Y-m-d H:i:s"),
                'data' => json_encode($request->input('order_data')),
                'items_amount' => $request->input('items_amount'),
                'total_price' => $request->input('total_price'),
                'secret' => Hash::make($request->input('secret')),
            ]);

            $order->orderDetails()->createMany($request->input('order_data'));

            DB::commit();

            return response()->json(['success' => true, 'redirect' => route('order.detail', ['id' => $order->id])]);
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'database processing error'], 422);
        }
    }

    private function aggregateUserOrder($itemName, $itemPrice, $userOrderListString, &$orderAggregation, &$userOrderAggregation) {
        $userOrderList = explode("、", $userOrderListString);

        foreach ($userOrderList as $userOrder) {
            preg_match('/(.*)(\*)([1-9][0-9]{0,15})/', $userOrder, $match);
            if ($match) {
                $userName = $match[1];
                $userQuantity = $match[3];
            } else {
                $userName = $userOrder;
                $userQuantity = 1;
            }

            $userOrderAggregation[$userName]['itemsAmount'][$itemName] = $userQuantity;
            if (isset($userOrderAggregation[$userName]['totalPrice'])) {
                $userOrderAggregation[$userName]['totalPrice'] += $userQuantity * $itemPrice;
            } else {
                $userOrderAggregation[$userName]['totalPrice'] = $userQuantity * $itemPrice;
            }

            if (isset($orderAggregation['itemsAmount'][$itemName])) {
                $orderAggregation['itemsAmount'][$itemName] += $userQuantity;
            } else {
                $orderAggregation['itemsAmount'][$itemName] = intval($userQuantity);
            }

            if (isset($orderAggregation['totalPrice'])) {
                $orderAggregation['totalPrice'] += $userQuantity * $itemPrice;
            } else {
                $orderAggregation['totalPrice'] = $userQuantity * $itemPrice;
            }
        }
    }

    public function updatePayment(Request $request)
    {
        $request->validate([
            'orderDetailId' => 'required',
            'status' => 'required',
        ]);

        $orderDetail = OrderDetail::find($request->get('orderDetailId'));

        if (!$orderDetail) {
            return response()->json(['success' => false, 'message' => 'Order record not found'], 404);
        }

        if (!$this->verifyAuth($orderDetail->order_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 404);
        }

        $orderDetail->pay_status = $request->get('status');

        if ($orderDetail->save()) {
            return response()->json(['success' => true, 'message' => 'Update successful']);
        } else {
            return response()->json(['success' => false, 'message' => 'Update failed'], 500);
        }
    }

    public function auth(Request $request) {

        $request->validate([
            'orderId' => 'required',
            'secret' => 'required',
        ]);

        $orderId = $request->get('orderId');
        $secret = $request->get('secret');
        $result = $this->authenticateByIP($orderId);
        if (!$result) {
            $result = $this->authenticate($orderId, $secret);
        }

        if ($result === true) {
            return response()->json(['success' => true, 'message' => 'Update successful']);
        } else {
            return response()->json(['success' => false, 'message' => $result], 404);
        }
    }

    /**
     * Verify if the user's input secret matches the secret of each order
     * @return bool|string Return true if successful, error message (string) if an exception occurs
     */
    private function authenticate($orderId, $secret): bool|string
    {
        $order = Order::find($orderId);

        if (!$order) {
            return 'Order not found';
        }

        if (Hash::check($secret, $order->secret)) {
            session()->put("order_{$orderId}", 'true');
            return true;
        }

        return 'Incorrect password';
    }

    private function authenticateByIP($orderId): bool
    {
        $requestIp = request()->ip();
        // To imporve user experience, use the client's source IP address as the secret in a private environment.
        return $this->authenticate($orderId, "{$requestIp}{$requestIp}") === true;
    }

    private function verifyAuth($orderId): bool
    {
        return session()->get("order_{$orderId}", 'false') === 'true';
    }
}
