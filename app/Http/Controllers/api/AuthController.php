<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function registerUser(Request $request)
    {
        $data = $request->validate([
            'nama'              => ['required','string','max:255'],
            'email'             => ['required','email','max:255','unique:users,email'],
            'username'          => ['nullable','string','max:255','unique:users,username'],
            'nomor_telepon'     => [' required','string','max:50'],
            'tanggal_lahir'     => ['nullable','date'],
            'jenis_kelamin'     => ['nullable','in:Laki-laki,Perempuan,'],
            'alamat'            => ['nullable','string','max:255'],
            'password'          => ['required','string','min:8','confirmed'],
        ]);

        $user = User::create([
            'nama'           => $data['nama'],
            'email'          => $data['email'],
            'username'       => $data['username'] ?? null,
            'nomor_telepon'  => $data['nomor_telepon'],
            'tanggal_lahir'  => $data['tanggal_lahir'] ?? null,
            'jenis_kelamin'  => $data['jenis_kelamin'] ?? null,
            'alamat'         => $data['alamat'] ?? null,
            'role'           => 'user',
            'password'       => Hash::make($data['password']),
        ]);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user'  => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $cred = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required','string'],
        ]);

        $user = User::where('email', $cred['email'])->first();

        if (!$user || !Hash::check($cred['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah'
            ], 401);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user'  => [
                'id'            => $user->id,
                'nama'          => $user->nama,
                'email'         => $user->email,
                'username'      => $user->username,
                'role'          => $user->role,
                'nomor_telepon' => $user->nomor_telepon,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    }
}