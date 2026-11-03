@extends('layouts.public')
@section('title', 'Ajukan Surat')
@section('content')
<div class="mx-auto max-w-2xl px-4 py-10">
    <h1 class="text-2xl font-bold text-slate-900">Formulir Permohonan Surat</h1>
    <p class="mt-1 text-sm text-slate-500">Setelah dikirim, Anda mendapat nomor registrasi untuk melacak status.</p>

    <form method="POST" action="{{ route('public.store') }}" class="card card-body mt-6 space-y-5" data-once>
        @csrf
        {{-- Honeypot anti-bot: jangan diisi --}}
        <div class="hidden" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="label" for="requester_type">Saya adalah</label>
                <select class="input" id="requester_type" name="requester_type" required>
                    <option value="mahasiswa" @selected(old('requester_type', 'mahasiswa') === 'mahasiswa')>Mahasiswa</option>
                    <option value="dosen" @selected(old('requester_type') === 'dosen')>Dosen</option>
                </select>
                @error('requester_type')<p class="err">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="requester_identifier">NPM / NIDN</label>
                <input class="input" id="requester_identifier" name="requester_identifier" value="{{ old('requester_identifier') }}" required maxlength="30">
                @error('requester_identifier')<p class="err">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="label" for="requester_name">Nama lengkap</label>
            <input class="input" id="requester_name" name="requester_name" value="{{ old('requester_name') }}" required maxlength="150">
            @error('requester_name')<p class="err">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="label" for="email">Email <span class="font-normal text-slate-400">(opsional)</span></label>
                <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" maxlength="150">
                @error('email')<p class="err">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="phone">No. HP <span class="font-normal text-slate-400">(opsional)</span></label>
                <input class="input" id="phone" name="phone" value="{{ old('phone') }}" maxlength="30">
                @error('phone')<p class="err">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="label" for="letter_type_id">Jenis surat</label>
            <select class="input" id="letter_type_id" name="letter_type_id" required>
                <option value="">— Pilih jenis surat —</option>
                @foreach ($types as $id => $name)
                    <option value="{{ $id }}" @selected((string) old('letter_type_id') === (string) $id)>{{ $name }}</option>
                @endforeach
            </select>
            @error('letter_type_id')<p class="err">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="label" for="subject">Perihal / keperluan</label>
            <input class="input" id="subject" name="subject" value="{{ old('subject') }}" required maxlength="255" placeholder="Contoh: Persyaratan beasiswa">
            @error('subject')<p class="err">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="label" for="details">Keterangan tambahan <span class="font-normal text-slate-400">(opsional)</span></label>
            <textarea class="input" id="details" name="details" rows="4" maxlength="2000">{{ old('details') }}</textarea>
            @error('details')<p class="err">{{ $message }}</p>@enderror
        </div>

        <button class="btn-primary w-full sm:w-auto" type="submit">Kirim Permohonan</button>
    </form>
</div>
@endsection
