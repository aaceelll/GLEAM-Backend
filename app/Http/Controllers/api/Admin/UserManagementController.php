<?php

// app/Http/Controllers/Admin/UserManagementController.php
namespace App\Http\Controllers\Api\Admin;


use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class UserManagementController extends Controller
{
    public function index()
    {
        try {
            $users = User::select('id','nama','username','email','nomor_telepon','role','created_at')
                ->orderBy('created_at','desc')
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $users,
                'message' => 'Data users berhasil diambil',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data users: '.$e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama'          => 'required|string|max:255',
            'username'      => 'required|string|max:255|unique:users,username',
            'email'         => 'required|email|max:255|unique:users,email',
            'nomor_telepon' => 'nullable|string|max:20',
            'password'      => 'required|string|min:6',
            'role'          => 'required|in:admin,nakes,manajemen,user',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::create([
                'nama'              => $request->nama,
                'username'          => $request->username,
                'email'             => $request->email,
                'nomor_telepon'     => $request->nomor_telepon,
                'password'          => $request->password,
                'role'              => $request->role,
                
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();

            return response()->json([
                'success' => true,
                'data'    => $user,
                'message' => 'User berhasil dibuat',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat user: '.$e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $user = User::select('id','nama','username','email','nomor_telepon','role','created_at')
                ->find((int)$id);

            if (!$user) {
                return response()->json(['success'=>false,'message'=>'User tidak ditemukan'],404);
            }

            return response()->json(['success'=>true,'data'=>$user,'message'=>'Data user berhasil diambil']);
        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message'=>'Gagal mengambil data user: '.$e->getMessage()],500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = User::find((int)$id);
            if (!$user) return response()->json(['success'=>false,'message'=>'User tidak ditemukan'],404);

            $validator = Validator::make($request->all(), [
                'nama'          => 'required|string|max:255',
                'username'      => ['required','string','max:255', Rule::unique('users','username')->ignore($user->id)],
                'email'         => ['required','email','max:255', Rule::unique('users','email')->ignore($user->id)],
                'nomor_telepon' => 'nullable|string|max:20',
                'password'      => 'nullable|string|min:6',
                'role'          => 'required|in:admin,nakes,manajemen,user',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success'=>false,
                    'message'=>'Validasi gagal',
                    'errors'=>$validator->errors(),
                ],422);
            }

            $data = [
                'nama'          => $request->nama,
                'username'      => $request->username,
                'email'         => $request->email,
                'nomor_telepon' => $request->nomor_telepon,
                'role'          => $request->role,
            ];
           
if ($request->filled('password')) {
    $data['password'] = $request->password; // biarkan cast yg meng-hash
}

            $user->update($data);

            $updated = User::select('id','nama','username','email','nomor_telepon','role','created_at')->find($user->id);

            return response()->json(['success'=>true,'data'=>$updated,'message'=>'User berhasil diupdate']);
        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message'=>'Gagal mengupdate user: '.$e->getMessage()],500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::find((int)$id);
            if (!$user) return response()->json(['success'=>false,'message'=>'User tidak ditemukan'],404);

            if (Auth::check() && $user->id === Auth::id()) {
                return response()->json(['success'=>false,'message'=>'Tidak dapat menghapus akun sendiri'],403);
            }

            $user->delete();
            return response()->json(['success'=>true,'message'=>'User berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message'=>'Gagal menghapus user: '.$e->getMessage()],500);
        }
    }
}

