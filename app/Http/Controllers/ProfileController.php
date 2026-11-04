<?php

namespace App\Http\Controllers;

use App\Models\Equipement;
use App\Models\Reservation;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('auth.profile');
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|min:3|max:100',
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
            'telephone' => ['nullable', 'regex:/^\+?[0-9 ]{8,15}$/'],
            'ville' => 'nullable|string|max:100',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'current_password' => 'required|current_password',
        ]);
        unset($data['current_password'], $data['photo']);
        $oldPhoto = $user->photo_path;
        if ($request->hasFile('photo')) {
            $user->photo_path = $request->file('photo')->store('profiles', 'public');
        }
        $changed = $user->email !== $data['email'];
        $user->fill($data);
        if ($changed) {
            $user->email_verified_at = null;
        }
        $user->save();
        if ($oldPhoto && $oldPhoto !== $user->photo_path) {
            Storage::disk('public')->delete($oldPhoto);
        }
        if ($changed) {
            event(new Registered($user));
        }

        return back()->with('success', 'Profil mis à jour.');
    }

    public function password(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        $request->user()->forceFill(['password' => $request->input('password'), 'remember_token' => Str::random(60)])->save();
        $request->session()->regenerate();

        return back()->with('success', 'Mot de passe modifié.');
    }

    public function destroy(Request $request)
    {
        $request->validate(['password' => 'required|current_password', 'confirmation' => 'required|in:SUPPRIMER']);
        $user = $request->user();
        DB::transaction(function () use ($user) {
            // Les cascades historiques suppriment aussi les locations des équipements du membre.
            // Conserver les comptes ayant un historique métier jusqu'à son traitement administratif.
            $user->newQuery()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $linked = Equipement::where('user_id', $user->id)->exists()
                || Reservation::where('user_id', $user->id)->exists()
                || DB::table('articles')->where('user_id', $user->id)->exists()
                || DB::table('participations')->where('user_id', $user->id)->where('statut', '!=', 'annule')->exists();
            if ($linked || $user->isAdmin()) {
                throw ValidationException::withMessages(['confirmation' => 'Contactez un administrateur pour traiter vos équipements, locations, articles ou participations avant la suppression du compte.']);
            }
            $user->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
        });
        if ($user->photo_path) {
            Storage::disk('public')->delete($user->photo_path);
        }
        Auth::guard()->logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Votre compte a été supprimé.');
    }
}
