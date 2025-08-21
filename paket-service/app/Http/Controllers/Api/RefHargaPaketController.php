<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RefHargaPaket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RefHargaPaketController extends Controller
{
    public function index(Request $request)
    {
        $perPage = max(1, (int) $request->query('per_page', 10));
        $page    = max(1, (int) $request->query('page', 1));

        $query = RefHargaPaket::orderBy('log_key', 'asc');

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'status'         => 'success',
            'data'           => $paginated->items(),
            'requested_page' => $page,
            'current_page'   => $paginated->currentPage(),
            'last_page'      => $paginated->lastPage(),
            'per_page'       => $paginated->perPage(),
            'total'          => $paginated->total(),
        ]);
    }

    public function show($id)
    {
        $paket = RefHargaPaket::where('log_key', $id)->first();

        if (!$paket) {
            return response()->json(['status' => 'error', 'message' => 'Paket tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $paket,
        ]);
    }

    public function store(Request $request)
    {
        Log::info('API Store Harga Paket', ['data' => $request->all()]);

        $validated = $request->validate([
            'alias_paket'    => 'required|string|max:150',
            'paket'          => 'required|string|max:10',
            'ref_gol'        => 'required|string|max:10',
            'dpp'            => 'nullable|numeric',
            'ppn'            => 'nullable|numeric',
            'total_amount'   => 'nullable|numeric',
            'margin'         => 'nullable|numeric',
            'status'         => 'nullable|string',
            'create_log'     => 'nullable|date',
            'jenis_paket'    => 'nullable|string|max:100',
        ]);

        $paket = RefHargaPaket::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Paket berhasil ditambahkan',
            'data'    => $paket,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        Log::info('API Update Harga Paket', ['id' => $id, 'data' => $request->all()]);

        $paket = RefHargaPaket::where('log_key', $id)->first();
        if (!$paket) {
            return response()->json(['status' => 'error', 'message' => 'Paket tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'alias_paket'    => 'sometimes|required|string|max:150',
            'paket'          => 'sometimes|required|string|max:10',
            'ref_gol'        => 'sometimes|required|string|max:10',
            'dpp'            => 'nullable|numeric',
            'ppn'            => 'nullable|numeric',
            'total_amount'   => 'nullable|numeric',
            'margin'         => 'nullable|numeric',
            'status'         => 'nullable|string',
            'create_log'     => 'nullable|date',
            'jenis_paket'    => 'nullable|string|max:100',
        ]);

        $paket->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Paket berhasil diperbarui',
            'data'    => $paket,
        ]);
    }

    public function destroy($id)
    {
        $paket = RefHargaPaket::where('log_key', $id)->first();

        if (!$paket) {
            return response()->json(['status' => 'error', 'message' => 'Paket tidak ditemukan'], 404);
        }

        $paket->delete();

        return response()->json(['status' => 'success', 'message' => 'Paket berhasil dihapus']);
    }

    public function all()
    {
        $allHargaPaket = RefHargaPaket::orderBy('log_key', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data'   => $allHargaPaket
        ]);
    }
}
