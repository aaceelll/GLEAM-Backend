<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    // GET /api/admin/users (Menampilkan daftar semua pengguna dalam sistem)
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

    // POST /api/admin/users (Membuat akun pengguna baru)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama'          => 'required|string|max:255',
            'username'      => 'required|string|max:255|unique:users,username',
            'email' => [
                'required',
                'email',
                'regex:/^[A-Za-z0-9._%+-]+@gmail\.com$/',
                'unique:users,email',
            ],
            'nomor_telepon' => [
                'required',
                'regex:/^08[0-9]{8,11}$/'
            ],
            'password'      => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/'
            ],
            'role'          => 'required|in:admin,nakes,manajemen',
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
                'password'          => Hash::make($request->password),
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

    // Melakukan pencarian nama pengguna
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

    // PATCH /api/admin/users/{id} (Memperbarui data pengguna)
    public function update(Request $request, $id)
{
    try {
        $user = User::find((int)$id);
        if (!$user) return response()->json(['success'=>false,'message'=>'User tidak ditemukan'],404);

        // Batasi admin agar tidak bisa edit akun role "user"
        $currentUser = Auth::user();
        if ($currentUser && $currentUser->role === 'admin' && $user->role === 'user') {
            return response()->json([
                'success' => false,
                'message' => 'Admin tidak diizinkan mengedit akun pengguna (user/pasien)',
            ], 403);
        } 

        $validator = Validator::make($request->all(), [
            'nama'          => 'required|string|max:255',
            'username'      => ['required','string','max:255', Rule::unique('users','username')->ignore($user->id)],
            'email' => [
                'required',
                'email',
                'regex:/^[A-Za-z0-9._%+-]+@gmail\.com$/',
                Rule::unique('users','email')->ignore($user->id),
            ],
            'nomor_telepon' => [
                'required',
                'regex:/^08[0-9]{8,11}$/'
            ],
            'password'      => [
                'nullable',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/'
            ],
            'role'          => 'required|in:admin,nakes,manajemen',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>'Validasi gagal',
                'errors'=>$validator->errors(),
            ],422);
        }

        // Update data 
        $data = [
            'nama'          => $request->nama,
            'username'      => $request->username,
            'email'         => $request->email,
            'nomor_telepon' => $request->nomor_telepon,
            'role'          => $request->role,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        $updated = User::select('id','nama','username','email','nomor_telepon','role','created_at')->find($user->id);

        return response()->json(['success'=>true,'data'=>$updated,'message'=>'User berhasil diupdate']);
    } catch (\Exception $e) {
        return response()->json(['success'=>false,'message'=>'Gagal mengupdate user: '.$e->getMessage()],500);
    }
}

    // DELETE /api/admin/users/{id} (Menghapus pengguna berdasarkan ID)
    public function destroy($id)
    {
        try {
            $user = User::find((int)$id);
            if (!$user) return response()->json(['success'=>false,'message'=>'User tidak ditemukan'],404);

            // Tidak boleh hapus akun sendiri
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

