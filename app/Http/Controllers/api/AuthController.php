<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * Register User
     */
    public function registerUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
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
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/'
            ],
            'password_confirmation' => 'required|same:password',
            'tanggal_lahir' => [
                'required',
                'date',
                'before_or_equal:' . now()->subYears(10)->format('Y-m-d'),
            ],
            'jenis_kelamin' => 'nullable|in:Laki-laki,Perempuan',
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'nama' => $request->nama,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'nomor_telepon' => $request->nomor_telepon,
            'tanggal_lahir' => $request->tanggal_lahir,
            'jenis_kelamin' => $request->jenis_kelamin,
            // 'alamat' => $request->alamat,
            'rt' => $request->rt,
            'rw' => $request->rw,
        ]);
        
        $user->markEmailAsVerified();  
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    /**
     * Login - Support Email OR Username
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'nullable|string|email',
            'username' => 'nullable|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Tentukan apakah login menggunakan email atau username
        if ($request->filled('email')) {
            $loginField = 'email';
            $loginValue = $request->email;
        } elseif ($request->filled('username')) {
            $loginField = 'username';
            $loginValue = $request->username;
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Email atau username harus diisi.'
            ], 422);
        }

        // Cari user berdasarkan email atau username
        $user = User::where($loginField, $loginValue)->first();

        // Validasi user dan password
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email/Username atau password salah'
            ], 401);
        }

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'user' => [
                'id' => $user->id,
                'nama' => $user->nama,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'nomor_telepon' => $user->nomor_telepon,
                'tanggal_lahir' => $user->tanggal_lahir,
                'jenis_kelamin' => $user->jenis_kelamin,
                // 'alamat' => $user->alamat,
                'rt' => $user->rt,
                'rw' => $user->rw,
            ],
            'token' => $token
        ], 200);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ]);
    }

    /**
     * Get Current User
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'nama' => $user->nama,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'nomor_telepon' => $user->nomor_telepon,
                'tanggal_lahir' => $user->tanggal_lahir,
                'jenis_kelamin' => $user->jenis_kelamin,
                'rt' => $user->rt,
                'rw' => $user->rw,
            ]
        ]);
    }

    /**
     * Change Password - Untuk user yang lupa password
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',
            'old_password' => 'required|string',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/',
                'confirmed',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::where($loginField, $request->login)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email/Username tidak ditemukan'
            ], 404);
        }

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password lama tidak sesuai'
            ], 401);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah.'
        ], 200);
    }

    /**
     * Forgot Password - Kirim reset token ke email
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak ditemukan di sistem',
                'errors' => $validator->errors()
            ], 404);
        }

        // Generate token
        $token = Str::random(64);
        
        // Hapus token lama jika ada
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();
        
        // Simpan token baru
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => $token,
            'created_at' => now()
        ]);

        // Kirim email
        $user = User::where('email', $request->email)->first();
        
        try {
            Mail::send('emails.reset-password', [
                'token' => $token,
                'email' => $request->email,
                'nama' => $user->nama
            ], function($message) use ($request) {
                $message->to($request->email);
                $message->subject('Reset Password - ' . config('app.name'));
            });

            return response()->json([
                'success' => true,
                'message' => 'Link reset password telah dikirim ke email Anda'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset Password - Verifikasi token dan update password
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/',
                'confirmed',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Cek token
        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$passwordReset) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid atau sudah kadaluarsa'
            ], 404);
        }

        // Cek apakah token lebih dari 60 menit
        if (now()->diffInMinutes($passwordReset->created_at) > 60) {
            return response()->json([
                'success' => false,
                'message' => 'Token sudah kadaluarsa. Silakan request ulang'
            ], 401);
        }

        // Cari user
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        // Update password (TIDAK perlu validasi password lama karena ini forgot password)
        $user->password = Hash::make($request->password);
        $user->save();

        // Hapus token setelah berhasil
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah. Silakan login dengan password baru'
        ], 200);
    }
}