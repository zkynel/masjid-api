<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Mosque;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register user baru + otomatis buat 1 masjid (default) + attach ke pivot mosque_user.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        // 1) Buat user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // 2) Buat masjid default untuk user (multi-masjid langsung kebukti)
        $slug = 'masjid-' . $user->id;

        $mosque = Mosque::create([
            'name' => 'Masjid Default',
            'slug' => $slug,
            'address' => null,
            'description' => null,
            'contact' => null,
        ]);

        // 3) Hubungkan user dengan masjid via pivot mosque_user
        $user->mosques()->attach($mosque->id, ['role_in_mosque' => 'admin']);

        // 4) Buat token Sanctum
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil.',
            'data' => [
                'user' => $user,
                'mosque' => $mosque,
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * Login -> menghasilkan token baru.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // Opsional: hapus token lama biar rapi (1 user = 1 token aktif)
        $user->tokens()->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        // Ambil masjid pertama user (untuk memudahkan Postman)
        $mosque = $user->mosques()->first();

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'user' => $user,
                'mosque' => $mosque,
                'token' => $token,
            ],
        ]);
    }

    /**
     * Logout -> hapus token aktif saat ini.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
            'data' => null,
        ]);
    }

    /**
     * Me -> cek user yang sedang login (protected).
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $mosque = $user->mosques()->first();

        return response()->json([
            'success' => true,
            'message' => 'Profil pengguna.',
            'data' => [
                'user' => $user,
                'mosque' => $mosque,
            ],
        ]);
    }
}
