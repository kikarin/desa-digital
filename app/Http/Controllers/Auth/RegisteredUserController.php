<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Residents;
use App\Repositories\UsersRoleRepository;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    protected $usersRoleRepository;

    public function __construct(UsersRoleRepository $usersRoleRepository)
    {
        $this->usersRoleRepository = $usersRoleRepository;
    }

    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Validasi NIK harus ada di Residents
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|lowercase|email|max:255|unique:'.User::class,
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
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Ambil resident berdasarkan NIK
        $resident = Residents::where('nik', $request->nik)->firstOrFail();

        // Buat user dengan resident_id
        $user = User::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'resident_id' => $resident->id, // Set resident_id
        ]);

        // Assign role "Warga" (ID 37)
        $this->usersRoleRepository->addRole($user->id, 37);

        // Set current_role_id
        $user->update(['current_role_id' => 37]);

        event(new Registered($user));

        Auth::login($user);

        return to_route('dashboard');
    }
}
