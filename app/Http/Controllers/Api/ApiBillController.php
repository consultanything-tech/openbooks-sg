<?php

namespace App\Http\Controllers\Api;

use App\Models\Bill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ApiBillController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $paginator = Bill::with('vendor')->latest()->paginate($perPage);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $bill = Bill::with(['items', 'vendor'])->findOrFail($id);

        return response()->json(['data' => $bill]);
    }
}
