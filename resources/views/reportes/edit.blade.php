<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Reporte - Sistema de Mantenimiento</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            max-width: 900px;
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
        }

        .header h1 {
            font-size: 24px;
            font-weight: 600;
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

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
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

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .content {
            padding: 30px;
        }

        .form-section {
            margin-bottom: 30px;
            padding: 25px;
            background: #fafafa;
            border-radius: 6px;
            border-left: 4px solid #2a5298;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #1e3c72;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        label {
            font-size: 13px;
            font-weight: 600;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        input, select, textarea {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #2a5298;
            box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }

        .btn-save {
            flex: 1;
            max-width: 200px;
            padding: 12px 24px;
            background: #2a5298;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .btn-save:hover {
            background: #1e3c72;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(42, 82, 152, 0.3);
        }

        .btn-cancel {
            flex: 1;
            max-width: 200px;
            padding: 12px 24px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: #5a6268;
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

        .alert-danger {
            background: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
        }

        .info-box {
            padding: 15px;
            background: #e7f1ff;
            border-left: 4px solid #2a5298;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #1e3c72;
        }

        .time-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
            padding: 20px;
            background: #f0f4f8;
            border-radius: 6px;
        }

        .time-item {
            text-align: center;
        }

        .time-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 5px;
        }

        .time-value {
            font-size: 18px;
            font-weight: 600;
            color: #1e3c72;
        }

        .time-toggle {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .toggle-btn {
            padding: 8px 16px;
            border: 2px solid #2a5298;
            background: white;
            color: #2a5298;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .toggle-btn.active {
            background: #2a5298;
            color: white;
        }

        .toggle-btn:hover {
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn-save, .btn-cancel {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- [Header] -->
        <div class="header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <h1><i class="fas fa-pen-to-square" style="margin-right: 8px;"></i>Editar Reporte #{{ $reporte->id }}</h1>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="https://mantenimiento.danito.tech" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Regresar al panel</a>
                <a href="{{ route('reportes.manage.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
            </div>
        </div>

        <!-- [Content] -->
        <div class="content">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle" style="margin-right: 6px;"></i> Por favor revisa los errores abajo
                </div>
            @endif

            <!-- [Info de reporte] -->
            <div class="info-box">
                <i class="fas fa-map-marker-alt" style="margin-right: 4px;"></i> Máquina: <strong>{{ $reporte->maquina->name ?? 'N/A' }}</strong> | 
                Área: <strong>{{ optional(optional($reporte->maquina)->linea)->area->name ?? 'N/A' }}</strong> |
                Reportado por: <strong>{{ $reporte->lider_nombre ?? 'Desconocido' }}</strong>
            </div>

            <form method="POST" action="{{ route('reportes.manage.update', $reporte) }}">
                @csrf
                @method('PUT')

                <!-- [Sección: Tiempos] -->
                <div class="form-section">
                    <div class="section-title"><i class="fas fa-stopwatch" style="margin-right: 8px;"></i>Tiempos</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="inicio">Inicio del Paro *</label>
                            <input type="datetime-local" id="inicio" name="inicio" 
                                   value="{{ $reporte->inicio->format('Y-m-d\TH:i') }}" required>
                            @error('inicio')
                                <span class="help-text" style="color: #dc3545;">{{ $message }}</span>
                            @enderror
                            <div class="help-text">Cuándo comenzó el problema</div>
                        </div>
                        <div class="form-group">
                            <label for="aceptado_en">Aceptación (Técnico Llega)</label>
                            <input type="datetime-local" id="aceptado_en" name="aceptado_en" 
                                   value="{{ $reporte->aceptado_en ? $reporte->aceptado_en->format('Y-m-d\TH:i') : '' }}">
                            @error('aceptado_en')
                                <span class="help-text" style="color: #dc3545;">{{ $message }}</span>
                            @enderror
                            <div class="help-text">Cuándo el técnico aceptó el reporte</div>
                        </div>
                        <div class="form-group">
                            <label for="fin">Finalización (Reparado)</label>
                            <input type="datetime-local" id="fin" name="fin" 
                                   value="{{ $reporte->fin ? $reporte->fin->format('Y-m-d\TH:i') : '' }}">
                            @error('fin')
                                <span class="help-text" style="color: #dc3545;">{{ $message }}</span>
                            @enderror
                            <div class="help-text">Cuándo se terminó la reparación</div>
                        </div>
                        <div class="form-group">
                            <label for="minutos_reaccion">Minutos de Reacción</label>
                            <input type="number" id="minutos_reaccion" name="minutos_reaccion" 
                                   value="{{ $reporte->aceptado_en ? abs($reporte->aceptado_en->diffInMinutes($reporte->inicio)) : 0 }}" 
                                   min="0" placeholder="0">
                            <div class="help-text">Tiempo desde el inicio hasta que llegó el técnico</div>
                        </div>
                        <div class="form-group">
                            <label for="minutos_mantenimiento">Minutos de Mantenimiento</label>
                            <input type="number" id="minutos_mantenimiento" name="minutos_mantenimiento" 
                                   value="{{ $reporte->fin && $reporte->aceptado_en ? abs($reporte->fin->diffInMinutes($reporte->aceptado_en)) : 10 }}" 
                                   min="1" placeholder="10">
                            <div class="help-text">Tiempo desde que llegó el técnico hasta que terminó la reparación</div>
                        </div>
                    </div>
                </div>

                <!-- [Sección: Estado] -->
                <div class="form-section">
                    <div class="section-title"><i class="fas fa-tasks" style="margin-right: 8px;"></i>Estado</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="status">Estado del Reporte *</label>
                            <select id="status" name="status" required>
                                <option value="">Selecciona un estado</option>
                                <option value="abierto" {{ $reporte->status == 'abierto' ? 'selected' : '' }}>Abierto</option>
                                <option value="en_mantenimiento" {{ $reporte->status == 'en_mantenimiento' ? 'selected' : '' }}>En Mantenimiento</option>
                                <option value="OK" {{ $reporte->status == 'OK' ? 'selected' : '' }}>Completado</option>
                            </select>
                            @error('status')
                                <span class="help-text" style="color: #dc3545;">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="tecnico_employee_number">Técnico Responsable</label>
                            <select id="tecnico_employee_number" name="tecnico_employee_number">
                                <option value="">Sin asignar</option>
                                @foreach ($tecnicos as $tecnico)
                                    <option value="{{ $tecnico->employee_number }}" 
                                            {{ $reporte->tecnico_employee_number == $tecnico->employee_number ? 'selected' : '' }}>
                                        {{ $tecnico->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tecnico_employee_number')
                                <span class="help-text" style="color: #dc3545;">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- [Sección: Detalles] -->
                <div class="form-section">
                    <div class="section-title"><i class="fas fa-file-alt" style="margin-right: 8px;"></i>Detalles</div>
                    <div class="form-group">
                        <label for="descripcion_falla">Descripción de la Falla *</label>
                        <textarea id="descripcion_falla" name="descripcion_falla" required>{{ $reporte->descripcion_falla }}</textarea>
                        @error('descripcion_falla')
                            <span class="help-text" style="color: #dc3545;">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group" style="margin-top: 20px;">
                        <label for="descripcion_resultado">Descripción del Resultado</label>
                        <textarea id="descripcion_resultado" name="descripcion_resultado">{{ $reporte->descripcion_resultado }}</textarea>
                        @error('descripcion_resultado')
                            <span class="help-text" style="color: #dc3545;">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-row" style="margin-top: 20px;">
                        <div class="form-group">
                            <label for="refaccion_utilizada">Refacción Utilizada</label>
                            <input type="text" id="refaccion_utilizada" name="refaccion_utilizada" 
                                   value="{{ $reporte->refaccion_utilizada }}">
                            @error('refaccion_utilizada')
                                <span class="help-text" style="color: #dc3545;">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="departamento">Departamento</label>
                            <input type="text" id="departamento" name="departamento" 
                                   value="{{ $reporte->departamento }}">
                            @error('departamento')
                                <span class="help-text" style="color: #dc3545;">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- [Sección: Resumen de Tiempos] -->
                @if ($reporte->fin)
                    <div class="form-section">
                        <div class="section-title"><i class="fas fa-chart-line" style="margin-right: 8px;"></i>Resumen de Tiempos</div>
                        <div class="time-toggle">
                            <button type="button" class="toggle-btn active" onclick="toggleTimeFormat('horas')"><i class="fas fa-chart-bar" style="margin-right: 4px;"></i> Horas</button>
                            <button type="button" class="toggle-btn" onclick="toggleTimeFormat('minutos')"><i class="fas fa-stopwatch" style="margin-right: 4px;"></i> Minutos</button>
                        </div>
                        <div class="time-info">
                            <div class="time-item">
                                <div class="time-label">Tiempo de Reacción</div>
                                <div class="time-value time-reaccion" data-minutos="{{ $reporte->aceptado_en ? abs($reporte->aceptado_en->diffInMinutes($reporte->inicio)) : 0 }}">
                                    {{ $reporte->aceptado_en ? number_format(abs($reporte->aceptado_en->diffInMinutes($reporte->inicio)) / 60, 2) . 'h' : '-' }}
                                </div>
                            </div>
                            <div class="time-item">
                                <div class="time-label">Tiempo de Reparación</div>
                                <div class="time-value time-reparacion" data-minutos="{{ abs($reporte->fin->diffInMinutes($reporte->aceptado_en ?? $reporte->inicio)) }}">
                                    {{ number_format(abs($reporte->fin->diffInMinutes($reporte->aceptado_en ?? $reporte->inicio)) / 60, 2) }}h
                                </div>
                            </div>
                            <div class="time-item">
                                <div class="time-label">Tiempo Total</div>
                                <div class="time-value time-total" data-minutos="{{ abs($reporte->fin->diffInMinutes($reporte->inicio)) }}">
                                    {{ number_format(abs($reporte->fin->diffInMinutes($reporte->inicio)) / 60, 2) }}h
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- [Menu de Acciones] -->
                <div class="form-actions">
                    <button type="submit" class="btn-save"><i class="fas fa-save" style="margin-right: 6px;"></i>Guardar Cambios</button>
                    <a href="{{ route('reportes.manage.index') }}" class="btn-cancel"><i class="fas fa-xmark" style="margin-right: 6px;"></i>Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // [Script para alternar formato de tiempos de minutos a horas o viceversa]
        function toggleTimeFormat(format) {
            const buttons = document.querySelectorAll('.toggle-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            const timeElements = {
                'time-reaccion': document.querySelector('.time-reaccion'),
                'time-reparacion': document.querySelector('.time-reparacion'),
                'time-total': document.querySelector('.time-total')
            };

            Object.values(timeElements).forEach(el => {
                if (!el) return;
                const minutos = parseFloat(el.dataset.minutos);
                if (format === 'minutos') {
                    el.textContent = Math.round(minutos) + ' min';
                } else {
                    el.textContent = (minutos / 60).toFixed(2) + 'h';
                }
            });
        }
    </script>
</body>
</html>
