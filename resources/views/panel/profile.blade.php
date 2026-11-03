@extends('layouts.panel')
@section('title', 'Profil & TTD')
@section('heading', 'Profil & TTD')
@section('content')
<form method="POST" action="{{ route('panel.profile.update') }}" enctype="multipart/form-data" class="card card-body max-w-2xl space-y-5" data-once>
    @csrf @method('PUT')
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label class="label" for="name">Nama</label>
            <input class="input" id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="150">
            @error('name')<p class="err">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="nip">NIP</label>
            <input class="input" id="nip" name="nip" value="{{ old('nip', $user->nip) }}" maxlength="30">
        </div>
    </div>

    <div>
        <label class="label" for="signature">Gambar tanda tangan (PNG transparan, maks 1 MB)</label>
        <input class="input" id="signature" type="file" name="signature" accept="image/png">
        @error('signature')<p class="err">{{ $message }}</p>@enderror
        @if ($user->signature_path)
            <div class="mt-3 inline-block rounded-lg border border-dashed border-slate-300 bg-slate-50 p-3">
                <img src="{{ route('panel.profile.signature') }}" alt="TTD" class="h-20">
            </div>
        @endif
        <p class="mt-2 text-xs text-slate-500">Dipakai untuk pembubuhan TTD otomatis pada surat yang di-ACC (hanya akun Kaprodi).</p>
    </div>

    <fieldset class="space-y-4 border-t border-slate-200 pt-5">
        <legend class="text-sm font-semibold text-slate-900">Ganti password <span class="font-normal text-slate-400">(kosongkan bila tidak diganti)</span></legend>
        <div>
            <label class="label" for="current_password">Password saat ini</label>
            <input class="input" id="current_password" type="password" name="current_password" autocomplete="current-password">
            @error('current_password')<p class="err">{{ $message }}</p>@enderror
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="label" for="password">Password baru</label>
                <input class="input" id="password" type="password" name="password" autocomplete="new-password">
                @error('password')<p class="err">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="password_confirmation">Ulangi password baru</label>
                <input class="input" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password">
            </div>
        </div>
    </fieldset>

    <button class="btn-primary" type="submit">Simpan</button>
</form>
@endsection
