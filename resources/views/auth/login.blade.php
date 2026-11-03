@extends('layouts.public')
@section('title', 'Masuk Staf')
@section('content')
<div class="mx-auto max-w-md px-4 py-16">
    <div class="card card-body">
        <h1 class="text-xl font-bold text-slate-900">Masuk Staf Prodi</h1>
        <p class="mt-1 text-sm text-slate-500">Untuk Admin Prodi dan Kaprodi. Mahasiswa/dosen tidak perlu login.</p>

        <form method="POST" action="{{ url('/masuk') }}" class="mt-6 space-y-4" data-once>
            @csrf
            <div>
                <label class="label" for="email">Email</label>
                <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                @error('email')<p class="err">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="password">Password</label>
                <input class="input" id="password" type="password" name="password" required autocomplete="current-password">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" class="rounded border-slate-300"> Ingat saya</label>
            <button class="btn-primary w-full" type="submit">Masuk</button>
        </form>
    </div>
</div>
@endsection
