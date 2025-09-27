@php
    $m = $metrics;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gráficas y KPIs</title>
    @vite('resources/js/app.js')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
    <style>
        :root { --bg: #0f172a; --card: #111827; --muted: #94a3b8; }
        body { background: var(--bg); color: #e5e7eb; }
        header { margin: 1rem 0 1.25rem; }
        h2 { margin: 0; }
        .toolbar { display: grid; grid-template-columns: repeat(6, minmax(140px, 1fr)); gap: .75rem; align-items: end; }
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: .75rem; }
    .grid-auto { display: grid; grid-template-columns: repeat(12, 1fr); gap: .75rem; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
        article { background: var(--card); border: 1px solid #1f2937; border-radius: 12px; padding: 1rem; }
        article h5 { color: var(--muted); margin: 0 0 .5rem; }
    canvas { width: 100% !important; height: 240px !important; }
    .chart-large { height: 320px !important; }
        a.button.secondary { margin-left: .5rem; }
        .kpi { font-size: 1.8rem; font-weight: 700; }
        details > summary { cursor: pointer; list-style: none; }
        details > summary::-webkit-details-marker { display: none; }
        .summary-row { display: flex; align-items: center; gap: .5rem; }
        .summary-row h3 { margin: 0; font-size: 1.1rem; color: var(--muted); }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<main class="container">
    <header>
        <h2>Gráficas y KPIs</h2>
        <details>
          <summary>
            <div class="summary-row">
                <h3>Filtros</h3>
                <small>(clic para {{ request()->hasAny(['day','from','to','week','month','area_id','linea_id','turno']) ? 'ocultar' : 'mostrar' }})</small>
            </div>
          </summary>
          <form method="GET" action="{{ route('graficas.index') }}" style="margin-top: .75rem;">
            <div class="toolbar">
                <div>
                    <label>Desde (día)</label>
                    <input type="date" name="day" value="{{ $filters['day'] }}">
                </div>
                <div>
                    <label>Rango: desde</label>
                    <input type="date" name="from" value="{{ $filters['from'] }}">
                </div>
                <div>
                    <label>Rango: hasta</label>
                    <input type="date" name="to" value="{{ $filters['to'] }}">
                </div>
                <div>
                    <label>Semana (ISO)</label>
                    <input type="week" name="week" value="{{ $filters['week'] }}">
                </div>
                <div>
                    <label>Mes</label>
                    <input type="month" name="month" value="{{ $filters['month'] }}">
                </div>
                <div>
                    <label>Turno</label>
                    <select name="turno">
                        <option value="">Todos</option>
                        <option value="1" @selected($filters['turno']==='1')>A</option>
                        <option value="2" @selected($filters['turno']==='2')>B</option>
                        <option value="3" @selected($filters['turno']==='3')>C</option>
                    </select>
                </div>
                <div>
                    <label>Área</label>
                    <select name="area_id">
                        <option value="">Todas</option>
                        @foreach($areas as $a)
                            <option value="{{ $a->id }}" @selected($filters['area_id']==$a->id)> {{ $a->name }} </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Línea</label>
                    <select name="linea_id">
                        <option value="">Todas</option>
                        @foreach($lineas as $l)
                            <option value="{{ $l->id }}" @selected($filters['linea_id']==$l->id)> {{ $l->name }} </option>
                        @endforeach
                    </select>
                </div>
                <div style="grid-column: span 2">
                    <label>&nbsp;</label>
                    <div>
                        <button type="submit" class="button">Aplicar</button>
                        <a class="button secondary" href="{{ route('graficas.index') }}">Limpiar</a>
                        <a class="button secondary" href="{{ route('graficas.export', request()->query()) }}">Exportar Excel</a>
                    </div>
                </div>
            </div>
          </form>
        </details>
    </header>

    <section class="cards">
        <article>
            <h5>MTTR promedio</h5>
            <div class="kpi">{{ number_format($m['cards']['mttr_avg_hours'] ?? 0, 2) }} h</div>
        </article>
        <article>
            <h5>MTBF promedio</h5>
            <div class="kpi">{{ number_format($m['cards']['mtbf_avg_hours'] ?? 0, 2) }} h</div>
        </article>
        <article>
            <h5>Tiempo total</h5>
            <div class="kpi">{{ number_format($m['cards']['total_hours'] ?? 0, 2) }} h</div>
        </article>
    </section>

    <section class="grid-auto">
        <article style="grid-column: span 3">
            <h5>Top 10 líneas por tiempo total (h)</h5>
            <canvas id="chartTopLineas"></canvas>
        </article>
        <article style="grid-column: span 3">
            <h5>Top 10 máquinas por tiempo total (h)</h5>
            <canvas id="chartTopMaquinas"></canvas>
        </article>
        <article style="grid-column: span 3">
            <h5>Tiempo total por turno (h)</h5>
            <canvas id="chartPorTurno"></canvas>
        </article>
        <article style="grid-column: span 3">
            <h5>MTTR por máquina (h)</h5>
            <canvas id="chartMttrMaquina"></canvas>
        </article>
        <article style="grid-column: span 6">
            <h5>MTTR diario (h) con meta</h5>
            <canvas id="chartSerieMttr" class="chart-large"></canvas>
        </article>
        <article style="grid-column: span 6">
            <h5>MTBF diario (h) con meta</h5>
            <canvas id="chartSerieMtbf" class="chart-large"></canvas>
        </article>
        <article style="grid-column: span 6">
            <h5>Reportes abiertos por día</h5>
            <canvas id="chartAbiertosDia"></canvas>
        </article>
        <article style="grid-column: span 6">
            <h5>MTBF por máquina (h)</h5>
            <canvas id="chartMtbfMaquina"></canvas>
        </article>
    </section>
</main>

<script>
const M = @json($metrics);

function barChart(id, labels, data, label, color){
    const ctx = document.getElementById(id);
    new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets: [{ label, data, backgroundColor: color }] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } },
            plugins: { legend: { position: 'top', labels: { boxWidth: 10 }}},
        }
    });
}

function lineChart(id, labels, series, suggestedMax){
    const ctx = document.getElementById(id);
    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: series
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: { y: { beginAtZero: true, suggestedMax: suggestedMax ?? 12, ticks: { stepSize: (suggestedMax ?? 12) <= 3 ? 0.25 : undefined } } },
            plugins: { legend: { position: 'top', labels: { boxWidth: 10 } } },
            elements: { point: { radius: 2 }, line: { borderWidth: 2 } }
        }
    });
}

barChart('chartTopLineas', M.top_lineas.labels, M.top_lineas.data, 'Horas', '#6366f1');
barChart('chartTopMaquinas', M.top_maquinas.labels, M.top_maquinas.data, 'Horas', '#f59e0b');
barChart('chartPorTurno', M.por_turno.labels, M.por_turno.data, 'Horas', '#10b981');
barChart('chartMttrMaquina', M.mttr_por_maquina.labels, M.mttr_por_maquina.data, 'MTTR (h)', '#3b82f6');
barChart('chartMtbfMaquina', M.mtbf_por_maquina.labels, M.mtbf_por_maquina.data, 'MTBF (h)', '#22c55e');

lineChart('chartSerieMttr', M.serie_diaria.labels, [
    { label: 'MTTR', data: M.serie_diaria.mttr, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,.15)', tension: .3 },
    { label: 'Meta MTTR 1h', data: M.serie_diaria.goal_mttr, borderColor: '#ef4444', borderDash: [6,6], pointRadius: 0, tension: 0 },
], 2);

lineChart('chartSerieMtbf', M.serie_diaria.labels, [
    { label: 'MTBF', data: M.serie_diaria.mtbf, borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,.15)', tension: .3 },
    { label: 'Meta MTBF 10h', data: M.serie_diaria.goal_mtbf, borderColor: '#22c55e', borderDash: [6,6], pointRadius: 0, tension: 0 },
], 12);

const abiertosLabels = M.abiertos_dia.labels;
const abiertosData = M.abiertos_dia.data;
const abiertosGoal = M.abiertos_dia.goal || new Array(abiertosLabels.length).fill(10);
const ctxAb = document.getElementById('chartAbiertosDia');
new Chart(ctxAb, {
    type: 'bar',
    data: { labels: abiertosLabels, datasets: [
        { label: 'Abiertos', data: abiertosData, backgroundColor: '#ef4444' },
        { label: 'Meta 10', data: abiertosGoal, type: 'line', borderColor: '#ef4444', borderDash: [6,6], pointRadius: 0 }
    ]},
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
});
</script>

</body>
</html>
