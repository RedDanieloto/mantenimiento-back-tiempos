@extends('layouts.app')

@section('content')
<style>
    .download-animation {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-top: 60px;
    }
    .cloud {
        width: 120px;
        height: 80px;
        background: #e0e7ef;
        border-radius: 60px 60px 40px 40px;
        position: relative;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }
    .cloud:before {
        content: '';
        position: absolute;
        left: 20px;
        top: -30px;
        width: 80px;
        height: 80px;
        background: #e0e7ef;
        border-radius: 50%;
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    }
    .arrow {
        margin-top: -20px;
        width: 40px;
        height: 40px;
        position: relative;
        animation: arrowDown 1.2s infinite cubic-bezier(.68,-0.55,.27,1.55);
    }
    .arrow svg {
        width: 100%;
        height: 100%;
        fill: #3498db;
    }
    @keyframes arrowDown {
        0% { transform: translateY(0); opacity: 1; }
        50% { transform: translateY(24px); opacity: 0.7; }
        100% { transform: translateY(0); opacity: 1; }
    }
    .epic-text {
        font-size: 2.2rem;
        font-weight: bold;
        color: #3498db;
        margin-top: 32px;
        letter-spacing: 2px;
        text-shadow: 0 2px 8px rgba(52,152,219,0.12);
    }
    .desc-text {
        font-size: 1.1rem;
        color: #333;
        margin-top: 12px;
        margin-bottom: 24px;
    }
    .btn-descarga {
        background: linear-gradient(90deg,#3498db,#6dd5fa);
        color: #fff;
        border: none;
        border-radius: 24px;
        padding: 12px 32px;
        font-size: 1.1rem;
        font-weight: 600;
        box-shadow: 0 4px 16px rgba(52,152,219,0.12);
        transition: background 0.3s;
    }
    .btn-descarga:hover {
        background: linear-gradient(90deg,#6dd5fa,#3498db);
        color: #fff;
    }
</style>
<div class="download-animation">
    <div class="cloud">
        <div class="arrow">
            <svg viewBox="0 0 40 40">
                <path d="M20 10v16M20 26l-8-8M20 26l8-8" stroke="#3498db" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            </svg>
        </div>
    </div>
    <div class="epic-text">¡Descarga épica iniciada!</div>
    <div class="desc-text">Tu archivo se está descargando.<br>Si no comienza automáticamente, puedes descargarlo manualmente.</div>
    <a href="{{ $downloadUrl }}" class="btn btn-descarga">Descargar manualmente</a>
    <div class="mt-4">
        <a href="/" class="btn btn-primary">Volver al inicio</a>
    </div>
</div>
<script>
    // Descarga automática
    setTimeout(function() {
        window.location.href = "{{ $downloadUrl }}";
    }, 1200);
</script>
@endsection
