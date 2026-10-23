@php
    $woInputClass = 'wo-input wo-native';
    $idPrefix = $idPrefix ?? '';
@endphp

<div class="space-y-4">
    @if(\Illuminate\Support\Facades\Schema::hasColumn('speedshop_keuangan_rekenings', 'jenis'))
    <div>
        <label for="{{ $idPrefix }}jenis" class="wo-label">Jenis</label>
        <select id="{{ $idPrefix }}jenis" name="jenis" required class="{{ $woInputClass }}">
            @foreach(\App\Models\SpeedshopKeuanganRekening::jenisOptions() as $value => $label)
            <option value="{{ $value }}" @selected(old('jenis', $rekening->jenis ?? \App\Models\SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('jenis')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    @endif
    <div>
        <label for="{{ $idPrefix }}nama" class="wo-label">Nama rekening</label>
        <input id="{{ $idPrefix }}nama" type="text" name="nama" value="{{ old('nama', $rekening->nama ?? '') }}" required maxlength="160" class="{{ $woInputClass }}" placeholder="Contoh: Rekening Operasional">
        @error('nama')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="{{ $idPrefix }}nama_bank" class="wo-label">Nama bank</label>
        <input id="{{ $idPrefix }}nama_bank" type="text" name="nama_bank" value="{{ old('nama_bank', $rekening->nama_bank ?? '') }}" maxlength="120" class="{{ $woInputClass }}" placeholder="Contoh: BCA">
        @error('nama_bank')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="{{ $idPrefix }}nomor_rekening" class="wo-label">Nomor rekening</label>
        <input id="{{ $idPrefix }}nomor_rekening" type="text" name="nomor_rekening" value="{{ old('nomor_rekening', $rekening->nomor_rekening ?? '') }}" required maxlength="64" class="{{ $woInputClass }} font-mono tabular-nums" inputmode="numeric" autocomplete="off" placeholder="1234567890">
        @error('nomor_rekening')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    @if(\Illuminate\Support\Facades\Schema::hasColumn('speedshop_keuangan_rekenings', 'keterangan'))
    <div>
        <label for="{{ $idPrefix }}keterangan" class="wo-label">Keterangan <span class="font-normal text-gray-400">(opsional)</span></label>
        <textarea id="{{ $idPrefix }}keterangan" name="keterangan" rows="3" maxlength="5000" class="{{ $woInputClass }} min-h-[5.5rem] resize-y py-2.5" placeholder="Catatan tambahan">{{ old('keterangan', $rekening->keterangan ?? '') }}</textarea>
        @error('keterangan')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    @endif
</div>
