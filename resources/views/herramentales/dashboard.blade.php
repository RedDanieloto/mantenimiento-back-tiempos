@php
    $m = []; // metrics placeholder
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas de Herramentales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #eff6ff;
            --secondary: #64748b;
            --success: #059669;
            --success-light: #ecfdf5;
            --warning: #d97706;
            --warning-light: #fffbeb;
            --danger: #dc2626;
            --danger-light: #fef2f2;
            --info: #0891b2;
            --info-light: #ecfeff;
            --dark: #0f172a;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --radius: 12px;
            --shadow-sm: 0 1px 2px 0 rgba(0,0,0,.05);
            --shadow: 0 1px 3px 0 rgba(0,0,0,.1), 0 1px 2px -1px rgba(0,0,0,.1);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -2px rgba(0,0,0,.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -4px rgba(0,0,0,.1);
        }

        * { box-sizing: border-box; }

        body {
            background-color: var(--gray-50);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--gray-800);
            -webkit-font-smoothing: antialiased;
        }

        /* ── Header ── */
        .page-header {
            background: linear-gradient(135deg, #1a365d 0%, #2d5a8c 100%);
            padding: 2.5rem 2rem;
            margin: -1.5rem -0.75rem 2.5rem;
            border-radius: 0 0 16px 16px;
            border-bottom: 1px solid rgba(255,255,255,.1);
            box-shadow: 0 10px 30px -10px rgba(0,0,0,.2);
        }
        .page-header h1 {
            color: #fff;
            font-size: 1.55rem;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        /* ── Cards ── */
        .card-custom {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 4px 12px -3px rgba(0, 0, 0, .08);
            transition: box-shadow .3s ease, transform .3s ease;
            overflow: hidden;
        }
        .card-custom:hover {
            box-shadow: 0 12px 24px -8px rgba(0, 0, 0, .12);
            transform: translateY(-2px);
        }

        /* ── Filter Bar ── */
        .filter-bar {
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 1.5rem 1.75rem;
            box-shadow: 0 4px 12px -3px rgba(0, 0, 0, .08);
        }
        .filter-bar label {
            font-size: 0.73rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            margin-bottom: 0.5rem;
        }
        .filter-bar .form-control {
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.65rem 1rem;
            font-size: 0.9rem;
            font-weight: 500;
            background: #fff;
            transition: all .2s;
        }
        .filter-bar .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
            background: #fff;
        }
        .btn-filter {
            background: linear-gradient(135deg, var(--primary) 0%, #1d4ed8 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 0.87rem;
            font-weight: 700;
            padding: 0.65rem 1.75rem;
            transition: all .2s;
            cursor: pointer;
            box-shadow: 0 4px 12px -3px rgba(37, 99, 235, .3);
        }
        .btn-filter:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            transform: translateY(-1px);
            box-shadow: 0 8px 16px -4px rgba(37, 99, 235, .4);
        }
        .btn-filter-reset {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 0.87rem;
            font-weight: 600;
            padding: 0.65rem 1.75rem;
            transition: all .2s;
            cursor: pointer;
        }
        .btn-filter-reset:hover {
            background: #e2e8f0;
            color: #1e293b;
            border-color: #94a3b8;
        }

        /* ── KPI Cards ── */
        .kpi-card {
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            border: 1px solid rgba(226, 232, 240, .8);
            border-radius: 16px;
            padding: 1.75rem;
            position: relative;
            overflow: hidden;
            transition: box-shadow .3s ease, transform .3s ease, border-color .3s ease;
        }
        .kpi-card:hover {
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, .15);
            transform: translateY(-4px);
            border-color: rgba(226, 232, 240, 1);
        }
        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 4px;
            border-radius: 16px 16px 0 0;
        }
        .kpi-card.kpi-blue::before  { background: linear-gradient(90deg, var(--primary) 0%, #1d4ed8 100%); }
        .kpi-card.kpi-amber::before { background: linear-gradient(90deg, var(--warning) 0%, #b45309 100%); }
        .kpi-card.kpi-teal::before  { background: linear-gradient(90deg, var(--info) 0%, #0d9488 100%); }
        .kpi-card.kpi-red::before   { background: linear-gradient(90deg, var(--danger) 0%, #b91c1c 100%); }

        .kpi-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem;
        }
        .kpi-blue .kpi-icon  { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: var(--primary); }
        .kpi-amber .kpi-icon { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: var(--warning); }
        .kpi-teal .kpi-icon  { background: linear-gradient(135deg, #ccfbf1 0%, #a7f3d0 100%); color: var(--info); }
        .kpi-red .kpi-icon   { background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: var(--danger); }

        .kpi-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            margin-bottom: 0.5rem;
        }
        .kpi-value {
            font-size: 2.2rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.04em;
        }
        .kpi-blue .kpi-value  { color: #1e40af; }
        .kpi-amber .kpi-value { color: #a16207; }
        .kpi-teal .kpi-value  { color: #0d6366; }
        .kpi-red .kpi-value   { color: #7f1d1d; }

        .kpi-sub {
            font-size: 0.76rem;
            color: #cbd5e1;
            margin-top: 0.3rem;
            font-weight: 500;
        }

        /* ── Section headers ── */
        .section-header {
            background: linear-gradient(90deg, #ffffff 0%, #f9fafb 100%);
            border-bottom: 1px solid #e2e8f0;
            padding: 1.25rem 1.5rem;
            border-radius: 14px 14px 0 0;
        }
        .section-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            letter-spacing: -0.015em;
        }
        .section-title i {
            color: #64748b;
            margin-right: 0.5rem;
        }

        /* ── Sort Buttons ── */
        .sort-group { display: flex; gap: 4px; flex-wrap: wrap; }
        .sort-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 0.3rem 0.65rem;
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid var(--gray-200);
            background: var(--gray-50);
            color: var(--gray-500);
            transition: all .2s;
            white-space: nowrap;
        }
        .sort-btn:hover {
            background: var(--gray-100);
            border-color: var(--gray-300);
            color: var(--gray-700);
        }
        .sort-btn.active-blue {
            background: var(--primary-light);
            border-color: var(--primary);
            color: var(--primary);
        }
        .sort-btn.active-red {
            background: var(--danger-light);
            border-color: var(--danger);
            color: var(--danger);
        }

        /* ── Tables ── */
        .table-wrapper { overflow-x: auto; }
        .table-enterprise {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.85rem;
        }
        .table-enterprise thead th {
            background: var(--gray-50);
            color: var(--gray-500);
            font-weight: 600;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 0.85rem 1.25rem;
            border-bottom: 1px solid var(--gray-200);
            white-space: nowrap;
        }
        .table-enterprise tbody td {
            padding: 0.9rem 1.25rem;
            border-bottom: 1px solid var(--gray-100);
            vertical-align: middle;
            color: var(--gray-700);
        }
        .table-enterprise tbody tr:last-child td { border-bottom: none; }
        .table-enterprise tbody tr { transition: background .15s; }
        .table-enterprise tbody tr:hover { background: var(--gray-50); }

        /* ── Pills / Badges ── */
        .pill {
            display: inline-flex; align-items: center;
            padding: 0.3rem 0.7rem;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 600;
        }
        .pill-primary { background: var(--primary-light); color: var(--primary); }
        .pill-info    { background: var(--info-light); color: var(--info); }
        .pill-warning { background: var(--warning-light); color: var(--warning); }
        .pill-danger  { background: var(--danger-light); color: var(--danger); }

        .count-badge {
            display: inline-flex;
            align-items: center; justify-content: center;
            min-width: 32px; height: 28px;
            padding: 0 8px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 700;
        }

        /* ── Charts ── */
        .chart-body { padding: 1.25rem; }
        canvas { max-height: 300px !important; }

        /* ── Empty State ── */
        .empty-state {
            padding: 3rem 1rem;
            text-align: center;
            color: var(--gray-400);
        }
        .empty-state i { font-size: 2rem; margin-bottom: 0.5rem; }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .page-header { padding: 1.25rem 1rem; margin: -1rem -0.75rem 1.5rem; }
            .kpi-value { font-size: 1.5rem; }
            .section-header { flex-direction: column; gap: 0.75rem; align-items: flex-start !important; }
        }
    </style>
</head>
<body>
<div class="container-fluid py-4 px-3 px-lg-4">

    <!-- ══ Header ══ -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="mb-0">
                <i class="fas fa-wrench me-2" style="opacity:.6"></i>Estadísticas de Herramentales
            </h1>
        </div>
    </div>

    <!-- ══ Filter Bar ══ -->
    <div class="filter-bar mb-4">
        <form id="filterForm" class="row g-3 align-items-end">
            <div class="col-sm-4">
                <label for="desde"><i class="fas fa-calendar-alt me-1"></i>Desde</label>
                <input type="date" id="desde" name="desde" class="form-control" value="{{ $desde }}">
            </div>
            <div class="col-sm-4">
                <label for="hasta"><i class="fas fa-calendar-alt me-1"></i>Hasta</label>
                <input type="date" id="hasta" name="hasta" class="form-control" value="{{ $hasta }}">
            </div>
            <div class="col-sm-4 d-flex gap-2">
                <button type="submit" class="btn-filter flex-grow-1">
                    <i class="fas fa-search me-1"></i> Filtrar
                </button>
                <button type="reset" class="btn-filter-reset flex-grow-1">
                    <i class="fas fa-rotate-right me-1"></i> Limpiar
                </button>
            </div>
        </form>
    </div>

    <!-- ══ KPIs ══ -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="kpi-card kpi-blue">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    @if($totalFallas > 0)
                        <span class="pill pill-primary" style="font-size:.7rem">Activo</span>
                    @endif
                </div>
                <div class="kpi-label">Total de Fallos</div>
                <div class="kpi-value">{{ $totalFallas }}</div>
                <div class="kpi-sub">Reportes con herramental</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card kpi-amber">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="kpi-icon"><i class="fas fa-stopwatch"></i></div>
                </div>
                <div class="kpi-label">MTTR (Minutos)</div>
                <div class="kpi-value">{{ $mttr }}</div>
                <div class="kpi-sub">Tiempo promedio de reparación</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card kpi-teal">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="kpi-icon"><i class="fas fa-chart-line"></i></div>
                </div>
                <div class="kpi-label">MTBF (Horas)</div>
                <div class="kpi-value">{{ $mtbf }}</div>
                <div class="kpi-sub">Tiempo entre fallos</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card kpi-red">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="kpi-icon"><i class="fas fa-hourglass-end"></i></div>
                </div>
                <div class="kpi-label">Downtime Total</div>
                <div class="kpi-value">{{ $tiempoDowntime }}h</div>
                <div class="kpi-sub">Horas de parada acumuladas</div>
            </div>
        </div>
    </div>

    <!-- ══ Charts ══ -->
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card-custom">
                <div class="section-header">
                    <h5 class="section-title"><i class="fas fa-trophy"></i> Top 10 Herramentales con Más Fallos</h5>
                </div>
                <div class="chart-body">
                    <canvas id="chartTop10" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card-custom">
                <div class="section-header">
                    <h5 class="section-title"><i class="fas fa-industry"></i> Fallos por Máquina</h5>
                </div>
                <div class="chart-body">
                    <canvas id="chartMaquinas" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ Table: Detalle por Herramental ══ -->
    <div class="card-custom mb-4">
        <div class="section-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="section-title"><i class="fas fa-list-ul"></i> Detalle por Herramental</h5>
            <div class="sort-group">
                <a href="{{ route('herramentales.stats', array_merge(request()->query(), ['sort_by' => 'fallos', 'sort_order' => 'desc'])) }}"
                   class="sort-btn {{ $sortBy === 'fallos' && $sortOrder === 'desc' ? 'active-blue' : '' }}">
                    <i class="fas fa-arrow-down-wide-short"></i> Más Fallos
                </a>
                <a href="{{ route('herramentales.stats', array_merge(request()->query(), ['sort_by' => 'fallos', 'sort_order' => 'asc'])) }}"
                   class="sort-btn {{ $sortBy === 'fallos' && $sortOrder === 'asc' ? 'active-blue' : '' }}">
                    <i class="fas fa-arrow-up-short-wide"></i> Menos Fallos
                </a>
                <a href="{{ route('herramentales.stats', array_merge(request()->query(), ['sort_by' => 'downtime', 'sort_order' => 'desc'])) }}"
                   class="sort-btn {{ $sortBy === 'downtime' && $sortOrder === 'desc' ? 'active-red' : '' }}">
                    <i class="fas fa-arrow-down-wide-short"></i> Más Downtime
                </a>
                <a href="{{ route('herramentales.stats', array_merge(request()->query(), ['sort_by' => 'downtime', 'sort_order' => 'asc'])) }}"
                   class="sort-btn {{ $sortBy === 'downtime' && $sortOrder === 'asc' ? 'active-red' : '' }}">
                    <i class="fas fa-arrow-up-short-wide"></i> Menos Downtime
                </a>
            </div>
        </div>
        <div class="table-wrapper">
            <table class="table-enterprise">
                <thead>
                    <tr>
                        <th>Herramental</th>
                        <th>Total Fallos</th>
                        <th>Promedio (min)</th>
                        <th>Mínimo (min)</th>
                        <th>Máximo (min)</th>
                        <th>Total Downtime (min)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($estadisticas as $item)
                        <tr>
                            <td><span class="pill pill-primary">{{ $item['herramental_nombre'] }}</span></td>
                            <td><span class="count-badge pill-warning">{{ $item['total_fallos'] }}</span></td>
                            <td>{{ round($item['tiempo_promedio_minutos'], 2) }}</td>
                            <td>{{ round($item['tiempo_minimo_minutos'], 2) }}</td>
                            <td>{{ round($item['tiempo_maximo_minutos'], 2) }}</td>
                            <td><span class="fw-bold" style="color:var(--danger)">{{ round($item['tiempo_total_minutos'], 2) }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="fas fa-inbox d-block"></i>
                                    No hay datos disponibles
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ══ Table: Máquinas Afectadas ══ -->
    <div class="card-custom mb-4">
        <div class="section-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="section-title"><i class="fas fa-cogs"></i> Máquinas Afectadas</h5>
            <div class="sort-group">
                <a href="{{ route('herramentales.stats', array_merge(request()->query(), ['sort_by_maquina' => 'fallos', 'sort_order_maquina' => 'desc'])) }}"
                   class="sort-btn {{ $sortByMaquina === 'fallos' && $sortOrderMaquina === 'desc' ? 'active-blue' : '' }}">
                    <i class="fas fa-arrow-down-wide-short"></i> Más Fallos
                </a>
                <a href="{{ route('herramentales.stats', array_merge(request()->query(), ['sort_by_maquina' => 'fallos', 'sort_order_maquina' => 'asc'])) }}"
                   class="sort-btn {{ $sortByMaquina === 'fallos' && $sortOrderMaquina === 'asc' ? 'active-blue' : '' }}">
                    <i class="fas fa-arrow-up-short-wide"></i> Menos Fallos
                </a>
                <a href="{{ route('herramentales.stats', array_merge(request()->query(), ['sort_by_maquina' => 'downtime', 'sort_order_maquina' => 'desc'])) }}"
                   class="sort-btn {{ $sortByMaquina === 'downtime' && $sortOrderMaquina === 'desc' ? 'active-red' : '' }}">
                    <i class="fas fa-arrow-down-wide-short"></i> Más Downtime
                </a>
                <a href="{{ route('herramentales.stats', array_merge(request()->query(), ['sort_by_maquina' => 'downtime', 'sort_order_maquina' => 'asc'])) }}"
                   class="sort-btn {{ $sortByMaquina === 'downtime' && $sortOrderMaquina === 'asc' ? 'active-red' : '' }}">
                    <i class="fas fa-arrow-up-short-wide"></i> Menos Downtime
                </a>
            </div>
        </div>
        <div class="table-wrapper">
            <table class="table-enterprise">
                <thead>
                    <tr>
                        <th>Máquina</th>
                        <th>Línea</th>
                        <th>Área</th>
                        <th>Nº de Fallos</th>
                        <th>Downtime (Horas)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($porMaquina as $maq)
                        <tr>
                            <td><span class="pill pill-info">{{ $maq['maquina_nombre'] }}</span></td>
                            <td>{{ $maq['linea_nombre'] ?? 'N/A' }}</td>
                            <td>{{ $maq['area_nombre'] ?? 'N/A' }}</td>
                            <td><span class="count-badge pill-danger">{{ $maq['numero_fallas'] }}</span></td>
                            <td><span class="fw-bold" style="color:var(--gray-700)">{{ $maq['tiempo_downtime_horas'] }}h</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fas fa-inbox d-block"></i>
                                    No hay máquinas afectadas
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ══ Footer ══ -->
    <div class="text-center py-3">
        <small style="color:var(--gray-400)">Generado el {{ now()->format('d/m/Y H:i') }} · Sistema de Mantenimiento</small>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const top10Data = @json($top10);
    const porMaquinaData = @json($porMaquina);

    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = '#64748b';

    // ── Top 10 Herramentales ──
    if (top10Data.length > 0) {
        new Chart(document.getElementById('chartTop10'), {
            type: 'bar',
            data: {
                labels: top10Data.map(i => i.herramental_nombre),
                datasets: [{
                    label: 'Nº de Fallos',
                    data: top10Data.map(i => i.numero_fallos),
                    backgroundColor: 'rgba(37, 99, 235, .75)',
                    borderColor: 'rgba(37, 99, 235, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                    barPercentage: .7
                }, {
                    label: 'Downtime (min)',
                    data: top10Data.map(i => Math.abs(i.tiempo_downtime_total_minutos)),
                    backgroundColor: 'rgba(220, 53, 69, .65)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                    barPercentage: .7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true, pointStyle: 'rectRounded' } }
                },
                scales: {
                    x: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                    y: { grid: { display: false } }
                }
            }
        });
    }

    // ── Fallos por Máquina ──
    if (porMaquinaData.length > 0) {
        const colors = ['#2563eb','#0891b2','#7c3aed','#059669','#d97706','#dc2626','#db2777','#4f46e5'];
        new Chart(document.getElementById('chartMaquinas'), {
            type: 'bar',
            data: {
                labels: porMaquinaData.map(i => i.maquina_nombre),
                datasets: [{
                    label: 'Nº de Fallos',
                    data: porMaquinaData.map(i => i.numero_fallas),
                    backgroundColor: porMaquinaData.map((_, idx) => colors[idx % colors.length] + 'c0'),
                    borderColor: porMaquinaData.map((_, idx) => colors[idx % colors.length]),
                    borderWidth: 1,
                    borderRadius: 6,
                    barPercentage: .55
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true, pointStyle: 'rectRounded' } }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
});

// ── Filter form ──
document.getElementById('filterForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const desde = document.getElementById('desde').value;
    const hasta = document.getElementById('hasta').value;
    window.location.href = `{{ route('herramentales.stats') }}?desde=${desde}&hasta=${hasta}`;
});

document.getElementById('filterForm').addEventListener('reset', function() {
    setTimeout(() => { window.location.href = '{{ route('herramentales.stats') }}'; }, 50);
});
</script>
</body>
</html>
