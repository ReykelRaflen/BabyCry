<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class ParentController extends Controller
{
    // List Parent
    public function index()
    {
        $parents = User::where('role', 'parent')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($parents);
    }

    // Detail Parent
    public function show($id)
    {
        $parent = User::findOrFail($id);

        return response()->json($parent);
    }

    // Tambah Parent
    public function store(Request $request)
    {
        $parent = User::create([
            'nama' => $request->nama,
            'email' => $request->email,
            'password' => $request->password,
            'role' => 'parent',
        ]);

        return response()->json([
            'message' => 'Parent berhasil ditambahkan',
            'data' => $parent
        ]);
    }

    // Edit Parent
    public function update(Request $request, $id)
    {
        $parent = User::findOrFail($id);

        $parent->update([
            'nama' => $request->nama,
            'email' => $request->email,
        ]);

        return response()->json([
            'message' => 'Parent berhasil diupdate'
        ]);
    }

    // Hapus Parent
    public function destroy($id)
    {
        User::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Parent berhasil dihapus'
        ]);
    }
}