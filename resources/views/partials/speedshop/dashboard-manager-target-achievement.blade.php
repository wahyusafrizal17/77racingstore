@php
    $rows = $dashboardTargetAchievement ?? [];
    $fmt = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    $pct = fn ($v) => number_format((int) $v, 0, ',', '.').'%';
    $signed = function (float $v) use ($fmt): string {
        $sign = $v > 0 ? '+' : '';

        return $sign.$fmt($v);
    };
@endphp
@if($rows !== [])
<section class="mk-perf-section ss-dash-enter" style="--ss-delay: 420ms">
    <div class="section-hd">
        <h2>Pencapaian Target Speedshop</h2>
        <p>
            Sparepart 65% · Jasa 35%
            @if(! empty($currentPeriodLabel))
                · {{ $currentPeriodLabel }}
            @endif
        </p>
    </div>
    <div class="panel-card mk-perf-card">
        <div class="table-wrap">
            <table class="mk-perf-table ss-target-table">
                <thead>
                    <tr>
                        <th class="text-center">No</th>
                        <th>Cabang</th>
                        <th class="text-right">Sparepart</th>
                        <th class="text-right">Target 65%</th>
                        <th class="text-center">%</th>
                        <th class="text-right">Jasa</th>
                        <th class="text-right">Target 35%</th>
                        <th class="text-center">%</th>
                        <th class="text-right">Total revenue</th>
                        <th class="text-right">M-1</th>
                        <th class="text-right">Growth</th>
                        <th class="text-right">Target bulan</th>
                        <th class="text-right">Target harian</th>
                        <th class="text-center">% target</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $i => $row)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td><strong>{{ $row['nama'] }}</strong></td>
                        <td class="text-right">{{ $fmt($row['sparepart_actual']) }}</td>
                        <td class="text-right">{{ $fmt($row['sparepart_target']) }}</td>
                        <td class="text-center">{{ $pct($row['sparepart_percent']) }}</td>
                        <td class="text-right">{{ $fmt($row['jasa_actual']) }}</td>
                        <td class="text-right">{{ $fmt($row['jasa_target']) }}</td>
                        <td class="text-center">{{ $pct($row['jasa_percent']) }}</td>
                        <td class="text-right amount-total">{{ $fmt($row['total_revenue']) }}</td>
                        <td class="text-right">{{ $fmt($row['prev_revenue']) }}</td>
                        <td @class(['text-right', 'text-emerald-600' => ($row['growth'] ?? 0) >= 0, 'text-red-600' => ($row['growth'] ?? 0) < 0])>{{ $signed((float) ($row['growth'] ?? 0)) }}</td>
                        <td class="text-right">{{ $fmt($row['target_bulanan']) }}</td>
                        <td class="text-right">{{ $fmt($row['target_harian']) }}</td>
                        <td class="text-center"><strong>{{ $pct($row['percent']) }}</strong></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endif
