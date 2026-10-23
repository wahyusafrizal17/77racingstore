@php
    $fmt = $fmt ?? fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    $showCabang = $showCabang ?? true;
    $rows = $rows ?? [];
    $colspan = $showCabang ? 10 : 9;
@endphp
<div class="panel-card mk-perf-card">
    <div class="panel-card-hd">
        <div>
            <div class="panel-card-title">{{ $title }}</div>
            <p class="panel-card-sub">Diurutkan dari pencapaian tertinggi</p>
        </div>
    </div>
    <div class="table-wrap">
        <table class="mk-perf-table">
            <thead>
                <tr>
                    <th class="text-center">Rank</th>
                    <th>Nama Mekanik</th>
                    <th>Jabatan</th>
                    @if($showCabang)
                    <th>Cabang</th>
                    @endif
                    <th class="text-center">Jumlah WO</th>
                    <th class="text-right">Pendapatan Jasa</th>
                    <th class="text-right">Pendapatan Sparepart</th>
                    <th class="text-right">Pencapaian</th>
                    <th class="text-right">Target</th>
                    <th class="text-center">%</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                <tr>
                    <td class="text-center"><span class="mk-perf-rank">{{ $row['rank'] ?? '' }}</span></td>
                    <td><strong>{{ $row['nama'] ?? '-' }}</strong></td>
                    <td>{{ $row['jabatan'] ?? 'Mekanik' }}</td>
                    @if($showCabang)
                    <td>{{ $row['cabang'] ?? '-' }}</td>
                    @endif
                    <td class="text-center"><strong>{{ number_format((int) ($row['wo_count'] ?? 0), 0, ',', '.') }}</strong></td>
                    <td class="text-right">{{ $fmt($row['jasa'] ?? 0) }}</td>
                    <td class="text-right">{{ $fmt($row['sparepart'] ?? 0) }}</td>
                    <td class="text-right amount-total">{{ $fmt($row['pencapaian'] ?? 0) }}</td>
                    <td class="text-right">{{ $fmt($row['target'] ?? 0) }}</td>
                    <td class="text-center"><strong>{{ number_format((int) ($row['percent'] ?? 0), 0, ',', '.') }}%</strong></td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $colspan }}" class="table-empty">Belum ada work order selesai pada periode ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
