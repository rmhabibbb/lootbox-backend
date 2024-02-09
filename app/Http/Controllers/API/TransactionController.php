<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        try {
            $id = $request->input('id');
            $limit = $request->input('limit');
            $status = $request->input('status');


            if ($id) {
                $transaction = Transaction::with(['items.product'])->find($id);

                if ($transaction) {
                    return ResponseFormatter::success(
                        $transaction,
                        'Data transaction berhasil diambil'
                    );
                } else {
                    return ResponseFormatter::error(
                        null,
                        'Data transaction tidak ditemukan',
                        404
                    );
                }
            }

            $transaction = Transaction::with(['items.product'])->where('user_id', Auth::user()->id);

            if ($status)
                $transaction->where('status', $status);

            return ResponseFormatter::success(
                $transaction->paginate($limit),
                'Data produk berhasil diambil'
            );
        } catch (Exception $e) {
            return ResponseFormatter::error(
                null,
                (env('APP_DEBUG')) ?  $e->getMessage() : 'Terjadi Kesalahan',
                500
            );
        }
    }

    public function checkout(Request $request){
        try {
           $rules = [
            'items' => ['required', 'array'],
            'items.*.id' => ['exists:products,id'],
            'total_price' => ['required'],
            'shipping_price' => ['required'],
            'status' => ['required', 'in:PENDING,SUCCESS,CANCELLED,FAILED,SHIPPING'],
           ];

           
           $validated = Validator::make($request->all(), $rules);

           if ($validated->fails()) {
               return ResponseFormatter::error(
                   $validated->errors(),
                   'Permintaan Data tidak sesuai.',
                   422
               );
           }

           $transaction = Transaction::create([
            'user_id' => Auth::user()->id,
            'address' => $request->address,
            'total_price' => $request->total_price,
            'shipping_price' => $request->shipping_price,
            'status' => $request->status
           ]);

           foreach ($request->items as $product) {
                TransactionItem::create([
                    'users_id' => Auth::user()->id,
                    'products_id' => $product['id'],
                    'transactions_id' => $transaction->id,
                    'quantity' => $product['quantity']
                ]);
           }

           return ResponseFormatter::success( 
            $transaction->load('items.product'),
            'Transaction berhasil'
        );


        } catch (Exception $e) {
            return ResponseFormatter::error(
                null,
                (env('APP_DEBUG')) ?  $e->getMessage() : 'Terjadi Kesalahan',
                500
            );
        }
    }
}
