<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Residents;
use App\Repositories\UsersRoleRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    protected $usersRoleRepository;
    protected $roleWargaId = 37; // Role ID untuk Warga/Resident

    public function __construct(UsersRoleRepository $usersRoleRepository)
    {
        $this->usersRoleRepository = $usersRoleRepository;
    }

    /**
     * Register untuk warga/resident
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'name'     => 'required|string|max:255',
                'email'    => 'required|string|lowercase|email|max:255|unique:users',
                'nik'      => [
                    'required',
                    'string',
                    function ($attribute, $value, $fail) {
                        $resident = Residents::where('nik', $value)->first();
                        if (!$resident) {
                            $fail('NIK tidak ditemukan dalam data warga.');
                        }
                        // Cek apakah NIK ini sudah punya user
                        if ($resident && User::where('resident_id', $resident->id)->exists()) {
                            $fail('NIK ini sudah terdaftar.');
                        }
                    },
                ],
                'password' => ['required', 'confirmed', Password::defaults()],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            // Ambil resident berdasarkan NIK
            $resident = Residents::where('nik', $request->nik)->firstOrFail();

            // Buat user dengan resident_id
            $user = User::create([
                'name'        => $request->name,
                'email'       => $request->email,
                'password'    => Hash::make($request->password),
                'resident_id' => $resident->id,
                'is_active'   => 1, // Aktifkan langsung untuk PWA
            ]);

            // Assign role "Warga" (ID 37)
            $this->usersRoleRepository->addRole($user->id, $this->roleWargaId);

            // Set current_role_id
            $user->update(['current_role_id' => $this->roleWargaId]);

            // Sync dengan Spatie Permission
            $user->syncRoles([$this->roleWargaId]);

            // Generate token untuk API
            $token = $user->createToken('pwa-token')->plainTextToken;

            // Load relasi yang diperlukan
            $user->load(['resident', 'role']);

            return response()->json([
                'success' => true,
                'message' => 'Registrasi berhasil',
                'data'    => [
                    'user'  => [
                        'id'           => $user->id,
                        'name'          => $user->name,
                        'email'         => $user->email,
                        'resident_id'   => $user->resident_id,
                        'resident'      => $user->resident ? [
                            'id'   => $user->resident->id,
                            'nik'  => $user->resident->nik,
                            'nama' => $user->resident->nama,
                        ] : null,
                        'role'          => $user->role ? [
                            'id'   => $user->role->id,
                            'name' => $user->role->name,
                        ] : null,
                    ],
                    'token' => $token,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan registrasi',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login untuk warga/resident
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'email'    => 'required|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            // Cek credentials
            $credentials = $request->only('email', 'password');

            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email atau password salah',
                ], 401);
            }

            $user = Auth::user();

            // Cek apakah user sudah terverifikasi
            if ($user->verification_token != null) {
                Auth::logout();
                return response()->json([
                    'success' => false,
                    'message' => 'Akun belum terverifikasi. Silakan verifikasi email terlebih dahulu.',
                ], 403);
            }

            // Cek apakah akun aktif
            if ($user->is_active == 0) {
                Auth::logout();
                return response()->json([
                    'success' => false,
                    'message' => 'Akun Anda tidak aktif. Silakan hubungi administrator.',
                ], 403);
            }

            // Cek apakah user memiliki role Warga (37)
            $hasWargaRole = $user->users_role()
                ->where('role_id', $this->roleWargaId)
                ->exists();

            if (!$hasWargaRole) {
                Auth::logout();
                return response()->json([
                    'success' => false,
                    'message' => 'Akun ini tidak memiliki akses sebagai warga.',
                ], 403);
            }

            // Update last login
            $user->update(['last_login' => now()]);

            // Log activity
            activity()->event('Login PWA')->performedOn($user)->log('Auth');

            // Hapus token lama (optional, untuk security)
            $user->tokens()->delete();

            // Generate token baru
            $token = $user->createToken('pwa-token')->plainTextToken;

            // Load relasi yang diperlukan
            $user->load(['resident', 'role']);

            return response()->json([
                'success' => true,
                'message' => 'Login berhasil',
                'data'    => [
                    'user'  => [
                        'id'           => $user->id,
                        'name'          => $user->name,
                        'email'         => $user->email,
                        'resident_id'   => $user->resident_id,
                        'resident'      => $user->resident ? [
                            'id'            => $user->resident->id,
                            'nik'           => $user->resident->nik,
                            'nama'          => $user->resident->nama,
                            'tempat_lahir'  => $user->resident->tempat_lahir,
                            'tanggal_lahir' => $user->resident->tanggal_lahir,
                            'jenis_kelamin' => $user->resident->jenis_kelamin,
                        ] : null,
                        'role'          => $user->role ? [
                            'id'   => $user->role->id,
                            'name' => $user->role->name,
                        ] : null,
                    ],
                    'token' => $token,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan login',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout untuk warga/resident
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            if ($user) {
                // Hapus token yang sedang digunakan
                $request->user()->currentAccessToken()->delete();

                // Log activity
                activity()->event('Logout PWA')->performedOn($user)->log('Auth');
            }

            return response()->json([
                'success' => true,
                'message' => 'Logout berhasil',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan logout',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current authenticated user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak terautentikasi',
                ], 401);
            }

            // Load relasi yang diperlukan
            $user->load(['resident', 'role']);

            return response()->json([
                'success' => true,
                'data'    => [
                    'user' => [
                        'id'           => $user->id,
                        'name'          => $user->name,
                        'email'         => $user->email,
                        'resident_id'   => $user->resident_id,
                        'resident'      => $user->resident ? [
                            'id'            => $user->resident->id,
                            'nik'           => $user->resident->nik,
                            'nama'          => $user->resident->nama,
                            'tempat_lahir'  => $user->resident->tempat_lahir,
                            'tanggal_lahir' => $user->resident->tanggal_lahir,
                            'jenis_kelamin' => $user->resident->jenis_kelamin,
                        ] : null,
                        'role'          => $user->role ? [
                            'id'   => $user->role->id,
                            'name' => $user->role->name,
                        ] : null,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data user',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get profile data lengkap untuk warga/resident
     * Mengambil data NIK, Nama, Tempat Lahir, Tanggal Lahir, Jenis Kelamin, dan Kartu Keluarga
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak terautentikasi',
                ], 401);
            }

            // Cek apakah user punya resident_id
            if (!$user->resident_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak memiliki data resident',
                ], 404);
            }

            // Load relasi resident dengan family
            $user->load(['resident.family']);

            if (!$user->resident) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data resident tidak ditemukan',
                ], 404);
            }

            $resident = $user->resident;

            // Format tanggal lahir dari YYYY-MM-DD menjadi DD-MM-YYYY
            $tanggalLahir = null;
            if ($resident->tanggal_lahir) {
                $tanggalLahir = Carbon::parse($resident->tanggal_lahir)->format('d-m-Y');
            }

            // Format jenis kelamin
            $jenisKelamin = null;
            if ($resident->jenis_kelamin) {
                $jenisKelamin = $resident->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan';
            }

            // Ambil nomor kartu keluarga dari family
            $noKk = null;
            if ($resident->family) {
                $noKk = $resident->family->no_kk;
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'nik'            => $resident->nik,
                    'nama'           => $resident->nama,
                    'tempat_lahir'   => $resident->tempat_lahir,
                    'tanggal_lahir'  => $tanggalLahir,
                    'jenis_kelamin'  => $jenisKelamin,
                    'kartu_keluarga' => $noKk,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data profile',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}

