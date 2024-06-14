<?php

namespace App\Http\Controllers;

use App\Models\Detail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Console;
use Illuminate\Support\Facades\Log;

class DetailController extends Controller
{
    public function show()
    {
        $details = Detail::with('payments')->get()->map(function ($details) {
            return [
                "id" => $details->id,
                "client_id" => $details->client_id,
                "issue_date" => $details->issue_date,
                "total_price" => $details->total_price,
                "payments" => $details->payments->map(function ($payment) {
                    return [
                        "id" => $payment->id,
                        "detail_id" => $payment->detail_id,
                        "payment_method" => $payment->payment_method,
                        "mountain" => $payment->mountain,
                        "reference" => $payment->reference,
                        "created_at" => $payment->created_at,
                        "updated_at" => $payment->updated_at
                    ];
                }),
                "created_at" => $details->created_at,
                "updated_at" => $details->updated_at,
            ];
        });
        return response()->json($details);
    }

    public function create(Request $request)
    {
        $validateData = $request->validate([
            "client_id" => "required|integer",
            "issue_date" => "required|date",
            "total_price" => "required|numeric|between:0,999999.99",
            "payment" => "required|array",
            "payment.*.payment_method" => "required|string",
            "payment.*.reference" => "required|string",
            "payment.*.mountain" => "required|numeric|between:0,999999.99"
        ]);

        if (!$validateData) {
            return response()->json([
                "message" => "Error while validating"
            ], 404);
        }

        $detail = Detail::create([
            "client_id" => $validateData["client_id"],
            "issue_date" => $validateData["issue_date"],
            "total_price" => $validateData["total_price"]
        ]);

        $detail->payments()->createMany(
            collect($validateData['payment'])->map(function ($paymentData) use ($detail) {
                return [
                    'detail_id' => $detail->id,
                    'payment_method' => $paymentData['payment_method'],
                    'mountain' => $paymentData['mountain'],
                    'reference' => $paymentData['reference'],
                ];
            })->toArray()
        );

        return response()->json(['message' => 'Orden guardada correctamente']);
    }
}