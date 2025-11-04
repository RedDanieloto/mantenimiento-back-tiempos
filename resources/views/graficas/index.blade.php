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
    .chart-header { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
    .chart-header h5 { margin: 0; }
    .chart-header .button.secondary { margin: 0; padding: .25rem .5rem; font-size: .8rem; }
    article:fullscreen { background: var(--card); padding: 1rem; }
    article:fullscreen canvas { height: 82vh !important; }

        /* Responsivo */
        @media (max-width: 1024px) {
            .toolbar { grid-template-columns: repeat(3, minmax(140px, 1fr)); }
            .grid-auto { grid-template-columns: repeat(6, 1fr); }
            .grid-auto > article { grid-column: span 6 !important; }
            canvas { height: 220px !important; }
            .chart-large { height: 280px !important; }
        }
        @media (max-width: 640px) {
            header { margin: .75rem 0 1rem; }
            .toolbar { grid-template-columns: 1fr; }
            .cards { grid-template-columns: repeat(2, 1fr); }
            .grid-auto { grid-template-columns: 1fr; }
            .grid-auto > article { grid-column: 1 / -1 !important; }
            .chart-header { flex-wrap: wrap; gap: .25rem; }
            .chart-header .button.secondary { margin-left: auto; }
            canvas { height: 200px !important; }
            .chart-large { height: 240px !important; }
        }

        /* Chips de filtros */
        .filters-summary { display: flex; align-items: center; flex-wrap: wrap; gap: .5rem; margin: .5rem 0 1rem; color: var(--muted); }
        .filters-summary .label { margin-right: .25rem; }
        .chip { display: inline-flex; align-items: center; gap: .25rem; padding: .25rem .6rem; border-radius: 999px; border: 1px solid #334155; background: #0b1220; color: #cbd5e1; font-size: .85rem; }
        .chip b { color: #e5e7eb; font-weight: 600; }
        .chip--primary { border-color: #3b82f6; background: rgba(59,130,246,.15); color: #dbeafe; }
        .chip--muted { opacity: .9; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<main class="container">
    <header>
        <h2>Gráficas y KPIs</h2>
        @php
            // Construye un resumen legible de los filtros activos
            $aplicados = [];
            if (!empty($filters['day'])) {
                $aplicados[] = 'Día: ' . $filters['day'];
            }
            if (!empty($filters['from']) && !empty($filters['to'])) {
                $aplicados[] = 'Rango: ' . $filters['from'] . ' a ' . $filters['to'];
            } elseif (!empty($filters['from'])) {
                $aplicados[] = 'Desde: ' . $filters['from'];
            } elseif (!empty($filters['to'])) {
                $aplicados[] = 'Hasta: ' . $filters['to'];
            }
            if (!empty($filters['week'])) {
                $aplicados[] = 'Semana: ' . $filters['week'];
            }
            if (!empty($filters['month'])) {
                $aplicados[] = 'Mes: ' . $filters['month'];
            }
            if (!empty($filters['turno'])) {
                $mapTurno = ['1' => 'A', '2' => 'B', '3' => 'C'];
                $aplicados[] = 'Turno: ' . ($mapTurno[$filters['turno']] ?? $filters['turno']);
            }
            if (!empty($filters['area_id'])) {
                $areaObj = $areas->firstWhere('id', (int) $filters['area_id']);
                $aplicados[] = 'Área: ' . ($areaObj->name ?? $filters['area_id']);
            }
            if (!empty($filters['linea_id'])) {
                $lineaObj = $lineas->firstWhere('id', (int) $filters['linea_id']);
                $aplicados[] = 'Línea: ' . ($lineaObj->name ?? $filters['linea_id']);
            }
            $textoFiltros = count($aplicados) ? implode(' • ', $aplicados) : 'Vista general (sin filtros)';
        @endphp
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
    <div class="filters-summary">
        <span class="label">Mostrando:</span>
        @php $hasAny=false; @endphp
        @if(!empty($filters['day']))
            @php $hasAny=true; @endphp
            <span class="chip"><b>Día</b> {{ $filters['day'] }}</span>
        @endif
        @if(!empty($filters['from']) && !empty($filters['to']))
            @php $hasAny=true; @endphp
            <span class="chip"><b>Rango</b> {{ $filters['from'] }} – {{ $filters['to'] }}</span>
        @elseif(!empty($filters['from']))
            @php $hasAny=true; @endphp
            <span class="chip"><b>Desde</b> {{ $filters['from'] }}</span>
        @elseif(!empty($filters['to']))
            @php $hasAny=true; @endphp
            <span class="chip"><b>Hasta</b> {{ $filters['to'] }}</span>
        @endif
        @if(!empty($filters['week']))
            @php $hasAny=true; @endphp
            <span class="chip"><b>Semana</b> {{ $filters['week'] }}</span>
        @endif
        @if(!empty($filters['month']))
            @php $hasAny=true; @endphp
            <span class="chip"><b>Mes</b> {{ $filters['month'] }}</span>
        @endif
        @if(!empty($filters['turno']))
            @php $hasAny=true; $mapTurno=['1'=>'A','2'=>'B','3'=>'C']; @endphp
            <span class="chip"><b>Turno</b> {{ $mapTurno[$filters['turno']] ?? $filters['turno'] }}</span>
        @endif
        @if(!empty($filters['area_id']))
            @php $hasAny=true; $areaObj = $areas->firstWhere('id', (int) $filters['area_id']); @endphp
            <span class="chip chip--primary"><b>Área</b> {{ $areaObj->name ?? $filters['area_id'] }}</span>
        @endif
        @if(!empty($filters['linea_id']))
            @php $hasAny=true; $lineaObj = $lineas->firstWhere('id', (int) $filters['linea_id']); @endphp
            <span class="chip chip--primary"><b>Línea</b> {{ $lineaObj->name ?? $filters['linea_id'] }}</span>
        @endif
        @if(!$hasAny)
            <span class="chip chip--muted">Vista general (sin filtros)</span>
        @endif
    </div>

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
            <div class="chart-header"><h5>Top 10 líneas por tiempo total (h)</h5><button class="button secondary" onclick="toggleFullscreen('chartTopLineas')">Pantalla completa</button></div>
            <canvas id="chartTopLineas"></canvas>
        </article>
        <article style="grid-column: span 3">
            <div class="chart-header"><h5>Top 10 máquinas por tiempo total (h)</h5><button class="button secondary" onclick="toggleFullscreen('chartTopMaquinas')">Pantalla completa</button></div>
            <canvas id="chartTopMaquinas"></canvas>
        </article>
        <article style="grid-column: span 3">
            <div class="chart-header"><h5>Tiempo total por turno (h)</h5><button class="button secondary" onclick="toggleFullscreen('chartPorTurno')">Pantalla completa</button></div>
            <canvas id="chartPorTurno"></canvas>
        </article>
        <article style="grid-column: span 3">
            <div class="chart-header"><h5>MTTR por máquina (h)</h5><button class="button secondary" onclick="toggleFullscreen('chartMttrMaquina')">Pantalla completa</button></div>
            <canvas id="chartMttrMaquina"></canvas>
        </article>
        <article style="grid-column: span 6">
            <div class="chart-header"><h5>MTTR diario (h) con meta</h5><button class="button secondary" onclick="toggleFullscreen('chartSerieMttr')">Pantalla completa</button></div>
            <canvas id="chartSerieMttr" class="chart-large"></canvas>
        </article>
        <article style="grid-column: span 6">
            <div class="chart-header"><h5>MTBF diario (h) con meta</h5><button class="button secondary" onclick="toggleFullscreen('chartSerieMtbf')">Pantalla completa</button></div>
            <canvas id="chartSerieMtbf" class="chart-large"></canvas>
        </article>
        <article style="grid-column: span 6">
            <div class="chart-header"><h5>Reportes abiertos por día</h5><button class="button secondary" onclick="toggleFullscreen('chartAbiertosDia')">Pantalla completa</button></div>
            <canvas id="chartAbiertosDia"></canvas>
        </article>
        <article style="grid-column: span 6">
            <div class="chart-header"><h5>MTBF por máquina (h)</h5><button class="button secondary" onclick="toggleFullscreen('chartMtbfMaquina')">Pantalla completa</button></div>
            <canvas id="chartMtbfMaquina"></canvas>
        </article>
    </section>
</main>

<script>
const M = @json($metrics);

// Pantalla completa para un artículo contenedor del canvas
function toggleFullscreen(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const article = canvas.closest('article') || canvas;
    if (!document.fullscreenElement) {
        if (article.requestFullscreen) article.requestFullscreen();
        else if (article.webkitRequestFullscreen) article.webkitRequestFullscreen();
        else if (article.msRequestFullscreen) article.msRequestFullscreen();
    } else {
        if (document.fullscreenElement === article) {
            if (document.exitFullscreen) document.exitFullscreen();
            else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
            else if (document.msExitFullscreen) document.msExitFullscreen();
        } else {
            const exit = document.exitFullscreen || document.webkitExitFullscreen || document.msExitFullscreen;
            if (exit) {
                Promise.resolve(exit.call(document)).finally(() => {
                    article.requestFullscreen && article.requestFullscreen();
                });
            } else {
                article.requestFullscreen && article.requestFullscreen();
            }
        }
    }
}

// Forzar re-cálculo de tamaño de Chart.js al entrar/salir de fullscreen
document.addEventListener('fullscreenchange', () => {
    setTimeout(() => window.dispatchEvent(new Event('resize')), 50);
});

function barChart(id, labels, data, label, color){
    const ctx = document.getElementById(id);
    return new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets: [{ label, data, backgroundColor: color }] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } },
            plugins: { legend: { position: 'top', labels: { boxWidth: 10 }} }
        }
    });
}

function lineChart(id, labels, series, suggestedMax){
    const ctx = document.getElementById(id);
    return new Chart(ctx, {
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
