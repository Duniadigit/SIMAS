{{-- Taruh logo resmi di public/images/logo-kop.png (dipakai juga di kop surat). Tanpa file itu, tampil huruf "S". --}}
@if (file_exists(public_path('images/logo-kop.png')))
    <img src="{{ asset('images/logo-kop.png') }}" alt="Logo" class="h-10 w-10 object-contain">
@else
    <span class="grid h-10 w-10 place-items-center rounded-lg bg-ink-900 font-extrabold text-brand-500">S</span>
@endif
