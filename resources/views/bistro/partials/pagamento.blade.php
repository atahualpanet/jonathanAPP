{{-- Metodo di pagamento + camera + pulsante. Va dentro un contenitore con x-data="bistroPagamento({...})" (vedi script-pagamento). --}}
<p class="text-sm text-slate-500 mb-2">Metodo di pagamento</p>

<div class="space-y-2 mb-5">
    @foreach (['camera' => 'Addebita in camera', 'carta' => 'Carta', 'contanti' => 'Contanti'] as $valore => $etichetta)
        <label class="flex items-center gap-3 bg-white rounded-xl border px-4 py-3.5 cursor-pointer transition"
               :class="metodo === '{{ $valore }}' ? 'border-[#C9822B] ring-1 ring-[#C9822B]' : 'border-gray-200'">
            <input type="radio" name="metodo" value="{{ $valore }}" x-model="metodo" class="accent-[#C9822B]">
            <span class="text-[15px]">{{ $etichetta }}</span>
        </label>
    @endforeach
</div>

<div x-show="metodo === 'camera'" x-cloak class="mb-5">
    <label class="block text-sm text-slate-500 mb-1" for="camera">Camera</label>
    <input id="camera" name="camera" x-model="camera" @input.debounce.400ms="verifica()" inputmode="numeric" maxlength="20" autocomplete="off"
           class="w-full border border-gray-200 rounded-xl px-4 py-3 text-[15px] bg-white focus:outline-none focus:ring-2 focus:ring-[#C9822B]">

    <div x-show="esito !== null" x-cloak class="mt-3 rounded-xl px-4 py-3 text-sm"
         :class="esito && esito.valida ? 'bg-okGreen/10 text-okGreen' : 'bg-[#B5563C]/10 text-[#B5563C]'">
        <template x-if="esito && esito.valida">
            <span><b>Camera valida · <span x-text="esito.ospite"></span></b><br>In casa fino al <span x-text="esito.fino_al"></span></span>
        </template>
        <template x-if="esito && ! esito.valida">
            <span><b>Camera non valida</b> o nessun ospite in casa.</span>
        </template>
    </div>
</div>

<button :disabled="! pronto"
        class="w-full bg-[#C9822B] hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed text-white font-medium py-3 rounded-xl text-[15px]"
        x-text="(metodo === 'camera' ? 'Addebita € ' : 'Incassa € ') + eur(importo)"></button>
