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
        * { transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease; }
        
        /* Tema claro por defecto */
        :root {
            --primary: #0052cc;
            --primary-dark: #003d99;
            --bg: #f8f9fa;
            --bg-secondary: #ffffff;
            --card: #ffffff;
            --border: #e0e4e8;
            --text: #1f2937;
            --text-muted: #6b7280;
            --text-light: #9ca3af;
        }
        
        /* Tema oscuro */
        html[data-theme="dark"] {
            --bg: #0f1419;
            --bg-secondary: #161b22;
            --card: #1c2128;
            --border: #30363d;
            --text: #e5e7eb;
            --text-muted: #c9cace;
            --text-light: #8b8b8b;
        }
        
        body {
            background: var(--bg);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Roboto", "Oxygen", "Ubuntu", "Cantarell", sans-serif;
        }
        
        header {
            margin: 2rem 0 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        h2 {
            margin: 0;
            color: var(--primary);
            font-size: 1.75rem;
            font-weight: 700;
        }
        
        .theme-toggle {
            display: flex;
            align-items: center;
            gap: .75rem;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: .5rem 1rem;
            cursor: pointer;
            font-size: .9rem;
        }
        
        .theme-toggle:hover {
            border-color: var(--primary);
        }
        
        .toolbar {
            display: grid;
            grid-template-columns: repeat(7, minmax(140px, 1fr));
            gap: 1rem;
            align-items: end;
            margin: 1.5rem 0;
        }
        
        .toolbar input,
        .toolbar select {
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: .5rem;
            background: var(--bg-secondary);
            color: var(--text);
            font-size: .95rem;
        }
        
        .toolbar select[multiple] {
            padding: .25rem;
        }
        
        .toolbar select[multiple] option {
            padding: .5rem;
            margin: .25rem 0;
        }
        
        .dept-selector {
            position: relative;
        }
        
        .dept-toggle {
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: .5rem;
            background: var(--bg-secondary);
            color: var(--text);
            font-size: .95rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        
        .dept-toggle:hover {
            border-color: var(--primary);
        }
        
        .dept-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-top: none;
            border-radius: 0 0 6px 6px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            z-index: 10;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .dept-dropdown.open {
            max-height: 300px;
        }
        
        .dept-options {
            padding: .5rem;
            display: flex;
            flex-direction: column;
            gap: .5rem;
        }
        
        .dept-option {
            display: flex;
            align-items: center;
            gap: .5rem;
            cursor: pointer;
            padding: .4rem .5rem;
            border-radius: 4px;
            transition: background 0.2s ease;
        }
        
        .dept-option:hover {
            background: rgba(0,82,204,0.08);
        }
        
        .dept-option input[type="checkbox"] {
            cursor: pointer;
            accent-color: var(--primary);
            width: 18px;
            height: 18px;
        }
        
        .dept-option label {
            cursor: pointer;
            margin: 0;
            flex: 1;
            font-weight: 500;
        }
        
        .dept-arrow {
            display: inline-block;
            transition: transform 0.3s ease;
            margin-left: .5rem;
        }
        
        .dept-dropdown.open .dept-arrow {
            transform: rotate(180deg);
        }
        
        .toolbar input:focus,
        .toolbar select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 82, 204, 0.1);
        }
        
        .toolbar label {
            display: block;
            margin-bottom: .5rem;
            font-size: .85rem;
            font-weight: 600;
            color: var(--text);
        }
        
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .grid-auto {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .chart-full { grid-column: span 12; }
        .chart-half { grid-column: span 6; }
        .chart-third { grid-column: span 4; }
        .chart-quarter { grid-column: span 3; }
        
        article {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }
        
        article:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }
        
        article h5 {
            color: var(--text-muted);
            margin: 0 0 1rem;
            font-size: .85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        
        canvas {
            width: 100% !important;
            height: 240px !important;
        }
        
        .chart-large {
            height: 320px !important;
        }
        
        .button {
            background: var(--primary);
            color: white;
            border: 1px solid var(--primary);
            border-radius: 6px;
            padding: .6rem 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .button:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            box-shadow: 0 2px 8px rgba(0, 82, 204, 0.2);
        }
        
        .button.secondary {
            background: var(--bg-secondary);
            color: var(--primary);
            border: 1px solid var(--border);
            margin-left: .5rem;
        }
        
        .button.secondary:hover {
            background: var(--bg);
            border-color: var(--primary);
        }
        
        details > summary {
            cursor: pointer;
            list-style: none;
            padding: 1rem;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 1rem;
            user-select: none;
        }
        
        details > summary::-webkit-details-marker {
            display: none;
        }
        
        details > summary::before {
            content: "▶ ";
            display: inline-block;
            margin-right: .75rem;
            transition: transform 0.2s ease;
            color: var(--primary);
        }
        
        details[open] > summary::before {
            transform: rotate(90deg);
        }
        
        .summary-row {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .summary-row h3 {
            margin: 0;
            font-size: 1.1rem;
            color: var(--text);
            font-weight: 600;
        }
        
        .summary-row small {
            color: var(--text-light);
            font-size: .9rem;
        }
        
        .chart-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .chart-header h5 {
            margin: 0;
            flex: 1;
        }
        
        .chart-header .button.secondary {
            margin: 0;
            padding: .4rem .8rem;
            font-size: .85rem;
        }
        
        article:fullscreen {
            background: var(--card);
            padding: 1.5rem;
        }
        
        article:fullscreen canvas {
            height: 82vh !important;
        }
        
        .filters-summary {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: .75rem;
            margin: 1rem 0 2rem;
            color: var(--text-muted);
            font-size: .95rem;
        }
        
        .filters-summary .label {
            margin-right: .5rem;
            font-weight: 600;
            color: var(--text);
        }
        
        .chip {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .4rem .8rem;
            border-radius: 20px;
            border: 1px solid var(--border);
            background: var(--bg-secondary);
            color: var(--text);
            font-size: .85rem;
        }
        
        .chip b {
            color: var(--primary);
            font-weight: 700;
        }
        
        .chip--primary {
            border-color: var(--primary);
            background: rgba(0, 82, 204, 0.08);
            color: var(--text);
        }
        
        .chip--primary b {
            color: var(--primary);
        }
        
        .chip--muted {
            opacity: 0.7;
        }
        
        .kpi {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
        }
        
        @media (max-width: 1024px) {
            .toolbar {
                grid-template-columns: repeat(3, minmax(140px, 1fr));
            }
            .grid-auto {
                grid-template-columns: repeat(6, 1fr);
            }
            .chart-full { grid-column: span 6; }
            .chart-half { grid-column: span 6; }
            .chart-third { grid-column: span 3; }
            .chart-quarter { grid-column: span 3; }
            canvas {
                height: 220px !important;
            }
            .chart-large {
                height: 280px !important;
            }
            header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
        
        @media (max-width: 768px) {
            .toolbar {
                grid-template-columns: repeat(2, 1fr);
            }
            .grid-auto {
                grid-template-columns: repeat(2, 1fr);
            }
            .chart-full { grid-column: span 2; }
            .chart-half { grid-column: span 2; }
            .chart-third { grid-column: span 1; }
            .chart-quarter { grid-column: span 1; }
            canvas {
                height: 200px !important;
            }
            .chart-large {
                height: 240px !important;
            }
        }
        
        @media (max-width: 640px) {
            header {
                margin: 1rem 0 1rem;
            }
            .toolbar {
                grid-template-columns: 1fr;
            }
            .cards {
                grid-template-columns: 1fr;
            }
            .grid-auto {
                grid-template-columns: 1fr;
            }
            .chart-full { grid-column: 1 / -1; }
            .chart-half { grid-column: 1 / -1; }
            .chart-third { grid-column: 1 / -1; }
            .chart-quarter { grid-column: 1 / -1; }
            .chart-header {
                flex-wrap: wrap;
                gap: .5rem;
            }
            .chart-header .button.secondary {
                margin-left: auto;
            }
            canvas {
                height: 180px !important;
            }
            .chart-large {
                height: 220px !important;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<main class="container">
    <header>
        <h2>📊 Gráficas y KPIs</h2>
        <div class="theme-toggle" onclick="toggleTheme()">
            <span id="themeIcon">🌙</span>
            <span id="themeLabel">Modo Oscuro</span>
        </div>
    </header>
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
                <div>
                    <label>Departamento</label>
                    <div class="dept-selector">
                        <button type="button" class="dept-toggle" onclick="toggleDeptDropdown(event)">
                            <span id="deptLabel">Seleccionar departamentos</span>
                            <span class="dept-arrow">▼</span>
                        </button>
                        <div class="dept-dropdown" id="deptDropdown">
                            <div class="dept-options">
                                @php $selectedDepts = explode(',', $filters['departamento'] ?? ''); @endphp
                                @forelse($departamentos ?? [] as $dept)
                                    @if(!empty($dept))
                                        <div class="dept-option">
                                            <input type="checkbox" name="departamento[]" value="{{ $dept }}" id="dept_{{ $loop->index }}"
                                                @checked(in_array($dept, $selectedDepts) && !empty($dept))
                                                onchange="updateDeptLabel()">
                                            <label for="dept_{{ $loop->index }}">{{ $dept }}</label>
                                        </div>
                                    @endif
                                @empty
                                    <div style="padding: .5rem; color: var(--text-muted); font-size: .9rem;">
                                        Sin departamentos
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
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
        @if(!empty($filters['departamento']))
            @php $hasAny=true; $depts = explode(',', $filters['departamento']); @endphp
            @foreach($depts as $dept)
                <span class="chip chip--primary"><b>Departamento</b> {{ trim($dept) }}</span>
            @endforeach
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
        <article class="chart-third">
            <div class="chart-header"><h5>Top 10 líneas por tiempo total (h)</h5><button class="button secondary" onclick="toggleFullscreen('chartTopLineas')">Pantalla completa</button></div>
            <canvas id="chartTopLineas"></canvas>
        </article>
        <article class="chart-third">
            <div class="chart-header"><h5>Top 10 máquinas por tiempo total (h)</h5><button class="button secondary" onclick="toggleFullscreen('chartTopMaquinas')">Pantalla completa</button></div>
            <canvas id="chartTopMaquinas"></canvas>
        </article>
        <article class="chart-third">
            <div class="chart-header"><h5>Fallas por departamento</h5><button class="button secondary" onclick="toggleFullscreen('chartFallasPorDepartamento')">Pantalla completa</button></div>
            <canvas id="chartFallasPorDepartamento"></canvas>
        </article>
        <article class="chart-third">
            <div class="chart-header"><h5>Tiempo total por turno (h)</h5><button class="button secondary" onclick="toggleFullscreen('chartPorTurno')">Pantalla completa</button></div>
            <canvas id="chartPorTurno"></canvas>
        </article>
        <article class="chart-third">
            <div class="chart-header"><h5>MTTR por máquina (h)</h5><button class="button secondary" onclick="toggleFullscreen('chartMttrMaquina')">Pantalla completa</button></div>
            <canvas id="chartMttrMaquina"></canvas>
        </article>
        <article class="chart-third">
            <div class="chart-header"><h5>MTBF por máquina (h)</h5><button class="button secondary" onclick="toggleFullscreen('chartMtbfMaquina')">Pantalla completa</button></div>
            <canvas id="chartMtbfMaquina"></canvas>
        </article>
        <article class="chart-half">
            <div class="chart-header"><h5>MTTR diario (h) con meta</h5><button class="button secondary" onclick="toggleFullscreen('chartSerieMttr')">Pantalla completa</button></div>
            <canvas id="chartSerieMttr" class="chart-large"></canvas>
        </article>
        <article class="chart-half">
            <div class="chart-header"><h5>MTBF diario (h) con meta</h5><button class="button secondary" onclick="toggleFullscreen('chartSerieMtbf')">Pantalla completa</button></div>
            <canvas id="chartSerieMtbf" class="chart-large"></canvas>
        </article>
        <article class="chart-half">
            <div class="chart-header"><h5>Reportes abiertos por día</h5><button class="button secondary" onclick="toggleFullscreen('chartAbiertosDia')">Pantalla completa</button></div>
            <canvas id="chartAbiertosDia"></canvas>
        </article>
        <article class="chart-half">
            <div class="chart-header"><h5>Información adicional</h5><button class="button secondary" onclick="toggleFullscreen('chartResume')">Pantalla completa</button></div>
            <canvas id="chartResume"></canvas>
        </article>
    </section>
</main>

<script>
// Sistema de tema oscuro/claro
function initTheme() {
    const saved = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
    updateThemeUI(saved === 'dark');
}

function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    const newTheme = current === 'light' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeUI(newTheme === 'dark');
}

function updateThemeUI(isDark) {
    const icon = document.getElementById('themeIcon');
    const label = document.getElementById('themeLabel');
    if (isDark) {
        icon.textContent = '☀️';
        label.textContent = 'Modo Claro';
    } else {
        icon.textContent = '🌙';
        label.textContent = 'Modo Oscuro';
    }
}

// Inicializar tema al cargar
initTheme();

// Sistema de selector de departamentos retractil
function toggleDeptDropdown(e) {
    e.preventDefault();
    const dropdown = document.getElementById('deptDropdown');
    dropdown.classList.toggle('open');
}

function updateDeptLabel() {
    const checkboxes = document.querySelectorAll('input[name="departamento[]"]:checked');
    const label = document.getElementById('deptLabel');
    
    if (checkboxes.length === 0) {
        label.textContent = 'Seleccionar departamentos';
    } else if (checkboxes.length === 1) {
        label.textContent = checkboxes[0].nextElementSibling.textContent;
    } else {
        label.textContent = checkboxes.length + ' departamentos seleccionados';
    }
}

// Cerrar dropdown al hacer click fuera
document.addEventListener('click', function(e) {
    const deptSelector = document.querySelector('.dept-selector');
    const dropdown = document.getElementById('deptDropdown');
    if (!deptSelector.contains(e.target) && dropdown.classList.contains('open')) {
        dropdown.classList.remove('open');
    }
});

// Inicializar label al cargar
updateDeptLabel();

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

// Colores mejorados y empresariales para gráficas
const colors = {
    blue: '#0052cc',
    green: '#10b981',
    red: '#ef4444',
    orange: '#f59e0b',
    purple: '#8b5cf6',
    cyan: '#06b6d4',
    yellow: '#fbbf24'
};

function pieChart(id, labels, data){
    const ctx = document.getElementById(id);
    const colors = ['#0052cc', '#10b981', '#ef4444', '#f59e0b', '#8b5cf6', '#06b6d4', '#fbbf24', '#ec4899'];
    const borderColors = ['#003d99', '#059669', '#dc2626', '#d97706', '#7c3aed', '#0891b2', '#f59e0b', '#be185d'];
    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data,
                backgroundColor: colors,
                borderColor: borderColors,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, padding: 15, font: { size: 11, weight: '600' } }
                }
            }
        }
    });
}

function barChart(id, labels, data, label, color){
    const ctx = document.getElementById(id);
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label,
                data,
                backgroundColor: color,
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: { boxWidth: 12, padding: 15, font: { size: 11, weight: '600' } }
                }
            }
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
            scales: {
                y: {
                    beginAtZero: true,
                    suggestedMax: suggestedMax ?? 12,
                    ticks: { stepSize: (suggestedMax ?? 12) <= 3 ? 0.25 : undefined },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: { boxWidth: 12, padding: 15, font: { size: 11, weight: '600' } }
                }
            },
            elements: { point: { radius: 3, hitRadius: 8 }, line: { borderWidth: 2.5 } }
        }
    });
}

// Crear gráficas con colores mejorados
barChart('chartTopLineas', M.top_lineas.labels, M.top_lineas.data, 'Horas', colors.blue);
barChart('chartTopMaquinas', M.top_maquinas.labels, M.top_maquinas.data, 'Horas', colors.orange);
pieChart('chartFallasPorDepartamento', M.top_departamentos.labels, M.top_departamentos.data);
barChart('chartPorTurno', M.por_turno.labels, M.por_turno.data, 'Horas', colors.green);
barChart('chartMttrMaquina', M.mttr_por_maquina.labels, M.mttr_por_maquina.data, 'MTTR (h)', colors.red);
barChart('chartMtbfMaquina', M.mtbf_por_maquina.labels, M.mtbf_por_maquina.data, 'MTBF (h)', colors.green);

lineChart('chartSerieMttr', M.serie_diaria.labels, [
    { label: 'MTTR', data: M.serie_diaria.mttr, borderColor: colors.red, backgroundColor: 'rgba(239,68,68,.12)', tension: .4, fill: true },
    { label: 'Meta MTTR 1h', data: M.serie_diaria.goal_mttr, borderColor: colors.red, borderDash: [8,4], pointRadius: 0, tension: 0, fill: false },
], 2);

lineChart('chartSerieMtbf', M.serie_diaria.labels, [
    { label: 'MTBF', data: M.serie_diaria.mtbf, borderColor: colors.green, backgroundColor: 'rgba(16,185,129,.12)', tension: .4, fill: true },
    { label: 'Meta MTBF 10h', data: M.serie_diaria.goal_mtbf, borderColor: colors.green, borderDash: [8,4], pointRadius: 0, tension: 0, fill: false },
], 12);

const abiertosLabels = M.abiertos_dia.labels;
const abiertosData = M.abiertos_dia.data;
const abiertosGoal = M.abiertos_dia.goal || new Array(abiertosLabels.length).fill(10);
const ctxAb = document.getElementById('chartAbiertosDia');
new Chart(ctxAb, {
    type: 'bar',
    data: {
        labels: abiertosLabels,
        datasets: [
            {
                label: 'Abiertos',
                data: abiertosData,
                backgroundColor: colors.red,
                borderRadius: 6,
                borderSkipped: false
            },
            {
                label: 'Meta 10',
                data: abiertosGoal,
                type: 'line',
                borderColor: colors.orange,
                borderDash: [8,4],
                pointRadius: 0,
                borderWidth: 2.5,
                fill: false
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,0.05)' }
            },
            x: {
                grid: { display: false }
            }
        },
        plugins: {
            legend: {
                position: 'top',
                labels: { boxWidth: 12, padding: 15, font: { size: 11, weight: '600' } }
            }
        }
    }
});

// Gráfica resumen con estadísticas principales
const ctxResume = document.getElementById('chartResume');
new Chart(ctxResume, {
    type: 'doughnut',
    data: {
        labels: ['MTTR Promedio', 'MTBF Promedio', 'Tiempo Total'],
        datasets: [{
            data: [
                M.cards.mttr_avg_hours || 0,
                M.cards.mtbf_avg_hours || 0,
                Math.min(M.cards.total_hours || 0, 24)
            ],
            backgroundColor: [colors.red, colors.green, colors.blue],
            borderColor: ['#dc2626', '#059669', '#003d99'],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { boxWidth: 12, padding: 15, font: { size: 11, weight: '600' } }
            }
        }
    }
});
</script>

</body>
</html>
