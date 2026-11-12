@if (session('ok'))
    <div class="mb-5 bg-okGreen/10 border border-okGreen/30 text-okGreen text-sm font-medium px-4 py-2.5 rounded-lg">
        {{ session('ok') }}
    </div>
@endif
@if ($errors->any())
    <div class="mb-5 bg-[#B5563C]/10 border border-[#B5563C]/30 text-[#B5563C] text-sm font-medium px-4 py-2.5 rounded-lg">
        {{ $errors->first() }}
    </div>
@endif
