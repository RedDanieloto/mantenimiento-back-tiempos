<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Reportes - Sistema de Mantenimiento</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 600;
            letter-spacing: -0.5px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #2a5298;
            color: white;
        }

        .btn-primary:hover {
            background: #1e3c72;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(42, 82, 152, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .btn-sm {
            padding: 8px 14px;
            font-size: 13px;
        }

        .filters {
            padding: 25px;
            background: #fafafa;
            border-bottom: 1px solid #e9ecef;
        }

        .filter-title {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        label {
            font-size: 13px;
            font-weight: 600;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        input, select {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s ease;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #2a5298;
            box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.1);
        }

        .bulk-actions {
            display: none;
            padding: 15px 25px;
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 6px;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .bulk-actions.active {
            display: flex;
        }

        .bulk-count {
            font-weight: 600;
            color: #1e3c72;
            font-size: 14px;
        }

        .content {
            padding: 25px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        td {
            padding: 14px 15px;
            border-bottom: 1px solid #dee2e6;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #2a5298;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .status-abierto {
            background: #fff3cd;
            color: #856404;
        }

        .status-en_mantenimiento {
            background: #cfe2ff;
            color: #084298;
        }

        .status-ok {
            background: #d1e7dd;
            color: #0f5132;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .btn-edit {
            background: #0d6efd;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-edit:hover {
            background: #0b5ed7;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .pagination a, .pagination span {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            padding: 0 8px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            text-decoration: none;
            color: #2a5298;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background: #e7f1ff;
            border-color: #2a5298;
            transform: translateY(-2px);
        }

        .pagination .active {
            background: #2a5298;
            color: white;
            border-color: #2a5298;
        }

        .pagination .disabled {
            color: #ccc;
            border-color: #e9ecef;
            cursor: not-allowed;
        }

        .pagination .disabled:hover {
            background: transparent;
            transform: none;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }

        .alert-error {
            background: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 10px;
            }

            .actions {
                flex-direction: column;
                gap: 5px;
            }

            .bulk-actions {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1>📋 Gestión de Reportes</h1>
            </div>
            <div class="header-actions">
                <a href="{{ route('reportes.manage.index') }}" class="btn btn-secondary">🔄 Actualizar</a>
                <a href="/" class="btn btn-secondary">← Volver</a>
            </div>
        </div>

        <!-- Alerts -->
        @if (session('success'))
            <div style="padding: 20px; padding-top: 25px;">
                <div class="alert alert-success">
                    ✓ {{ session('success') }}
                </div>
            </div>
        @endif

        @if (session('error'))
            <div style="padding: 20px; padding-top: 25px;">
                <div class="alert alert-error">
                    ✗ {{ session('error') }}
                </div>
            </div>
        @endif

        <!-- Filters -->
        <div class="filters">
            <div class="filter-title">Filtros de Búsqueda</div>
            <form method="GET" action="{{ route('reportes.manage.index') }}" style="display: contents;">
                <div class="filter-grid">
                    <div class="form-group">
                        <label for="search">Búsqueda (Máquina, Descripción)</label>
                        <input type="text" id="search" name="search" placeholder="Escribe para buscar..." 
                               value="{{ request('search') }}">
                    </div>
                    <div class="form-group">
                        <label for="status">Estado</label>
                        <select id="status" name="status">
                            <option value="">Todos los Estados</option>
                            <option value="abierto" {{ request('status') == 'abierto' ? 'selected' : '' }}>Abierto</option>
                            <option value="en_mantenimiento" {{ request('status') == 'en_mantenimiento' ? 'selected' : '' }}>En Mantenimiento</option>
                            <option value="OK" {{ request('status') == 'OK' ? 'selected' : '' }}>Completado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="area_id">Área</label>
                        <select id="area_id" name="area_id">
                            <option value="">Todas las Áreas</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}" {{ request('area_id') == $area->id ? 'selected' : '' }}>
                                    {{ $area->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="from_date">Desde</label>
                        <input type="date" id="from_date" name="from_date" value="{{ request('from_date') }}">
                    </div>
                    <div class="form-group">
                        <label for="to_date">Hasta</label>
                        <input type="date" id="to_date" name="to_date" value="{{ request('to_date') }}">
                    </div>
                    <div class="form-group" style="align-self: flex-end;">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">🔍 Filtrar</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Bulk Actions -->
        <div style="padding: 0 25px; padding-top: 20px;">
            <div class="bulk-actions" id="bulkActions">
                <span class="bulk-count">✓ <span id="selectedCount">0</span> reportes seleccionados</span>
                <form method="POST" action="{{ route('reportes.manage.destroy-multiple') }}" style="display: flex; gap: 10px;">
                    @csrf
                    <input type="hidden" id="selectedIds" name="ids" value="">
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de que deseas eliminar estos reportes?')">
                        🗑️ Eliminar Seleccionados
                    </button>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="content">
            @if ($reportes->count() > 0)
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="selectAll" class="checkbox" title="Seleccionar todos">
                                </th>
                                <th>ID</th>
                                <th>Máquina</th>
                                <th>Área</th>
                                <th>Líder</th>
                                <th>Estado</th>
                                <th>Inicio</th>
                                <th>Duración</th>
                                <th style="width: 140px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reportes as $reporte)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="checkbox reporte-checkbox" 
                                               value="{{ $reporte->id }}" title="Seleccionar reporte">
                                    </td>
                                    <td><strong>#{{ $reporte->id }}</strong></td>
                                    <td>{{ $reporte->maquina->name ?? 'N/A' }}</td>
                                    <td>{{ optional(optional($reporte->maquina)->linea)->area->name ?? 'N/A' }}</td>
                                    <td>{{ $reporte->lider_nombre ?? 'Desconocido' }}</td>
                                    <td>
                                        <span class="status-badge status-{{ strtolower(str_replace(' ', '_', $reporte->status)) }}">
                                            {{ $reporte->status }}
                                        </span>
                                    </td>
                                    <td>{{ $reporte->inicio->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if ($reporte->fin)
                                            {{ $reporte->fin->diffInHours($reporte->inicio) }}h 
                                            {{ $reporte->fin->diffInMinutes($reporte->inicio) % 60 }}m
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="{{ route('reportes.manage.edit', $reporte) }}" class="btn-edit">✏️ Editar</a>
                                            <a href="{{ route('reportes.manage.confirm-delete', $reporte) }}" class="btn-delete">🗑️ Borrar</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination">
                    {{-- Previous Page Link --}}
                    @if ($reportes->onFirstPage())
                        <span class="disabled">← Anterior</span>
                    @else
                        <a href="{{ $reportes->previousPageUrl() }}">← Anterior</a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($reportes->getUrlRange(1, $reportes->lastPage()) as $page => $url)
                        @if ($page == $reportes->currentPage())
                            <span class="active">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($reportes->hasMorePages())
                        <a href="{{ $reportes->nextPageUrl() }}">Siguiente →</a>
                    @else
                        <span class="disabled">Siguiente →</span>
                    @endif
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <p style="font-size: 16px; margin-bottom: 10px;">No hay reportes para mostrar</p>
                    <p style="font-size: 14px; color: #ccc;">Intenta cambiar los filtros de búsqueda</p>
                </div>
            @endif
        </div>
    </div>

    <script>
        const selectAllCheckbox = document.getElementById('selectAll');
        const reporteCheckboxes = document.querySelectorAll('.reporte-checkbox');
        const bulkActions = document.getElementById('bulkActions');
        const selectedCount = document.getElementById('selectedCount');
        const selectedIds = document.getElementById('selectedIds');

        selectAllCheckbox.addEventListener('change', function() {
            reporteCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActions();
        });

        reporteCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkActions);
        });

        function updateBulkActions() {
            const checkedCount = Array.from(reporteCheckboxes).filter(cb => cb.checked).length;
            selectedCount.textContent = checkedCount;

            if (checkedCount > 0) {
                bulkActions.classList.add('active');
                const ids = Array.from(reporteCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value)
                    .join(',');
                selectedIds.value = ids;
            } else {
                bulkActions.classList.remove('active');
                selectAllCheckbox.checked = false;
            }
        }
    </script>
</body>
</html>
