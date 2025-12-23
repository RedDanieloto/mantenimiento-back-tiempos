<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Eliminación - Sistema de Mantenimiento</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .dialog-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
        }

        .dialog-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .dialog-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .dialog-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .dialog-subtitle {
            font-size: 14px;
            opacity: 0.9;
        }

        .dialog-body {
            padding: 30px;
        }

        .warning-box {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            color: #842029;
            font-size: 14px;
            line-height: 1.6;
        }

        .reporte-info {
            background: #f9fafb;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #333;
        }

        .info-value {
            color: #666;
            text-align: right;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
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

        .dialog-actions {
            display: flex;
            gap: 12px;
            padding: 20px 30px;
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        .btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-confirm {
            background: #dc3545;
            color: white;
        }

        .btn-confirm:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .btn-cancel {
            background: #e9ecef;
            color: #333;
        }

        .btn-cancel:hover {
            background: #dee2e6;
        }

        @media (max-width: 600px) {
            .dialog-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="dialog-container">
        <!-- Header -->
        <div class="dialog-header">
            <div class="dialog-icon">⚠️</div>
            <div class="dialog-title">Eliminar Reporte</div>
            <div class="dialog-subtitle">Esta acción no se puede deshacer</div>
        </div>

        <!-- Body -->
        <div class="dialog-body">
            <div class="warning-box">
                ⚠️ Al eliminar este reporte, se perderán todos los datos relacionados.
                Esta acción es permanente y no se puede recuperar.
            </div>

            <div class="reporte-info">
                <div class="info-item">
                    <span class="info-label">ID del Reporte</span>
                    <span class="info-value">#{{ $reporte->id }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Máquina</span>
                    <span class="info-value">{{ $reporte->maquina->name ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Área</span>
                    <span class="info-value">
                        {{ optional(optional($reporte->maquina)->linea)->area->name ?? 'N/A' }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Estado</span>
                    <span class="info-value">
                        <span class="status-badge status-{{ strtolower(str_replace(' ', '_', $reporte->status)) }}">
                            {{ $reporte->status }}
                        </span>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Líder</span>
                    <span class="info-value">{{ $reporte->lider_nombre ?? 'Desconocido' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Fecha</span>
                    <span class="info-value">{{ $reporte->inicio->format('d/m/Y H:i') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Falla</span>
                    <span class="info-value">{{ substr($reporte->descripcion_falla, 0, 50) }}...</span>
                </div>
            </div>

            <p style="color: #666; font-size: 13px; line-height: 1.6;">
                ¿Estás seguro de que deseas eliminar este reporte? Si es un reporte duplicado o erróneo,
                considera editarlo primero en lugar de eliminarlo.
            </p>
        </div>

        <!-- Actions -->
        <div class="dialog-actions">
            <a href="{{ route('reportes.manage.index') }}" class="btn btn-cancel">
                ✕ Cancelar
            </a>
            <form method="POST" action="{{ route('reportes.manage.destroy', $reporte) }}" style="flex: 1;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-confirm" style="width: 100%; margin: 0;">
                    🗑️ Sí, Eliminar
                </button>
            </form>
        </div>
    </div>
</body>
</html>
