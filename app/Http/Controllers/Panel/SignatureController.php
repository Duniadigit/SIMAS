<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

/** Profil akun: NIP, gambar TTD (PNG transparan) untuk pembubuhan otomatis, dan ganti password. */
class SignatureController extends Controller
{
    public function edit(Request $request)
    {
        return view('panel.profile', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'nip' => ['nullable', 'string', 'max:30'],
            'signature' => ['nullable', 'file', 'mimes:png', 'mimetypes:image/png', 'max:1024'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        $user->name = $data['name'];
        $user->nip = $data['nip'] ?? null;

        if ($request->hasFile('signature')) {
            $new = $request->file('signature')->store('signatures', 'local');
            if ($user->signature_path) {
                Storage::disk('local')->delete($user->signature_path);
            }
            $user->signature_path = $new;
        }

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return back()->with('success', 'Profil diperbarui.');
    }

    public function preview(Request $request)
    {
        $path = $request->user()->signature_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, 'ttd.png', ['Content-Type' => 'image/png']);
    }
}
