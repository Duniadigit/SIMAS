@extends('layouts.panel')
@section('title', 'Catat Surat Masuk')
@section('heading', 'Catat Surat Masuk')
@section('content')
<form method="POST" action="{{ route('panel.incoming.store') }}" enctype="multipart/form-data" class="card card-body max-w-3xl space-y-5" data-once>
    @csrf
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label class="label" for="letter_number">Nomor surat</label>
            <input class="input" id="letter_number" name="letter_number" value="{{ old('letter_number') }}" required maxlength="100">
            @error('letter_number')<p class="err">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="origin">Asal surat</label>
            <input class="input" id="origin" name="origin" value="{{ old('origin') }}" required maxlength="200" placeholder="Mis. Fakultas Hukum / instansi">
            @error('origin')<p class="err">{{ $message }}</p>@enderror
        </div>
    </div>
    <div>
        <label class="label" for="subject">Perihal</label>
        <input class="input" id="subject" name="subject" value="{{ old('subject') }}" required maxlength="255">
        @error('subject')<p class="err">{{ $message }}</p>@enderror
    </div>
    <div class="grid gap-5 sm:grid-cols-3">
        <div>
            <label class="label" for="category">Kategori</label>
            <input class="input" id="category" name="category" list="cats" value="{{ old('category') }}" maxlength="80">
            <datalist id="cats">@foreach (config('simas.incoming_categories') as $c)<option value="{{ $c }}">@endforeach</datalist>
            @error('category')<p class="err">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="letter_date">Tanggal surat</label>
            <input class="input" id="letter_date" type="date" name="letter_date" value="{{ old('letter_date') }}" required>
            @error('letter_date')<p class="err">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="received_date">Tanggal diterima</label>
            <input class="input" id="received_date" type="date" name="received_date" value="{{ old('received_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
            @error('received_date')<p class="err">{{ $message }}</p>@enderror
        </div>
    </div>
    <div>
        <label class="label" for="scan">Berkas scan (PDF, maks {{ round(config('simas.pdf_max_kb') / 1024) }} MB)</label>
        <input class="input" id="scan" type="file" name="scan" accept="application/pdf" required>
        @error('scan')<p class="err">{{ $message }}</p>@enderror
    </div>
    <div class="flex gap-2">
        <button class="btn-primary" type="submit">Simpan</button>
        <a class="btn-ghost" href="{{ route('panel.incoming.index') }}">Batal</a>
    </div>
</form>
@endsection
