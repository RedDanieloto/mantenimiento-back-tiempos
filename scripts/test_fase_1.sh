#!/bin/bash

# Script de Prueba FASE 1 Backend
# Validar que las optimizaciones funcionan

echo "=========================================="
echo "🧪 PRUEBA FASE 1 - OPTIMIZACIÓN BACKEND"
echo "=========================================="
echo ""

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

PROJECT_DIR="/Users/red/Documents/GitHub/mantenimiento-back-tiempos"
API_URL="http://localhost:8000/api"

# Test 1: Verificar que el servidor está corriendo
echo -e "${YELLOW}[Test 1]${NC} Verificar conexión al servidor..."
if curl -s "$API_URL/areas" > /dev/null; then
    echo -e "${GREEN}✓${NC} Servidor corriendo"
else
    echo -e "${RED}✗${NC} Servidor no responde. Iniciar con: php artisan serve"
    exit 1
fi

# Test 2: Verificar que los índices fueron creados
echo ""
echo -e "${YELLOW}[Test 2]${NC} Verificar índices en BD..."
INDEXES=$(cd "$PROJECT_DIR" && php artisan tinker --execute "
\$table = DB::select(\"SHOW INDEX FROM reportes WHERE Key_name != 'PRIMARY' AND Key_name NOT LIKE '%_foreign%'\");
echo count(\$table);
" 2>/dev/null | tail -1)

if [ "$INDEXES" -ge 8 ]; then
    echo -e "${GREEN}✓${NC} Se encontraron $INDEXES índices"
else
    echo -e "${RED}✗${NC} Solo se encontraron $INDEXES índices (esperaba >= 8)"
fi

# Test 3: Prueba de endpoint con filtro de fecha
echo ""
echo -e "${YELLOW}[Test 3]${NC} Prueba endpoint: GET /api/areas/1/reportes?day=2026-01-16"

RESPONSE=$(curl -s "$API_URL/areas/1/reportes?day=2026-01-16&page=1&per_page=10" \
    -H "Accept: application/json" \
    -w "\n%{http_code}")

HTTP_CODE=$(echo "$RESPONSE" | tail -1)
BODY=$(echo "$RESPONSE" | sed '$d')

if [ "$HTTP_CODE" -eq 200 ]; then
    echo -e "${GREEN}✓${NC} Respuesta HTTP 200"
    
    # Verificar que tiene estructura de paginación
    if echo "$BODY" | jq -e '.data | type' > /dev/null 2>&1; then
        echo -e "${GREEN}✓${NC} Respuesta contiene 'data'"
        
        TOTAL=$(echo "$BODY" | jq -r '.meta.total' 2>/dev/null)
        echo -e "${GREEN}✓${NC} Total de reportes del día: $TOTAL"
        
        if [ "$TOTAL" -lt 100 ]; then
            echo -e "${GREEN}✓${NC} Filtro de fecha funciona (solo $TOTAL del día, no 10,000+)"
        fi
    else
        echo -e "${RED}✗${NC} Respuesta no es JSON válido"
        echo "$BODY"
    fi
else
    echo -e "${RED}✗${NC} Respuesta HTTP $HTTP_CODE"
    echo "$BODY"
fi

# Test 4: Prueba de caché (segunda solicitud debe ser más rápida)
echo ""
echo -e "${YELLOW}[Test 4]${NC} Prueba de caché (primera solicitud vs segunda)..."

echo -n "  Primera solicitud: "
START=$(date +%s%N)
curl -s "$API_URL/areas/1/reportes?day=2026-01-16&page=1" \
    -H "Accept: application/json" > /dev/null
END=$(date +%s%N)
FIRST=$((($END - $START) / 1000000))
echo "${FIRST}ms"

echo -n "  Segunda solicitud: "
START=$(date +%s%N)
curl -s "$API_URL/areas/1/reportes?day=2026-01-16&page=1" \
    -H "Accept: application/json" > /dev/null
END=$(date +%s%N)
SECOND=$((($END - $START) / 1000000))
echo "${SECOND}ms"

if [ $SECOND -lt $((FIRST / 3)) ]; then
    echo -e "${GREEN}✓${NC} Caché funciona (segunda solicitud ${FIRST/$SECOND}x más rápida)"
else
    echo -e "${YELLOW}⚠${NC} Caché podría no estar activo (diferencia pequeña)"
fi

# Test 5: Verificar relaciones
echo ""
echo -e "${YELLOW}[Test 5]${NC} Verificar que las relaciones se cargan..."

RESPONSE=$(curl -s "$API_URL/areas/1/reportes?day=2026-01-16&page=1&per_page=1" \
    -H "Accept: application/json")

if echo "$RESPONSE" | jq -e '.data[0].maquina' > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} Relación 'maquina' cargada"
else
    echo -e "${RED}✗${NC} Relación 'maquina' no presente"
fi

if echo "$RESPONSE" | jq -e '.data[0].user' > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} Relación 'user' cargada"
else
    echo -e "${RED}✗${NC} Relación 'user' no presente"
fi

# Test 6: Verificar Select limitado
echo ""
echo -e "${YELLOW}[Test 6]${NC} Verificar que solo se cargan columnas necesarias..."

RESPONSE=$(curl -s "$API_URL/areas/1/reportes?day=2026-01-16&page=1&per_page=1" \
    -H "Accept: application/json")

# Contar campos en el primer reporte
FIELDS=$(echo "$RESPONSE" | jq '.data[0] | keys | length' 2>/dev/null)
if [ "$FIELDS" -lt 30 ]; then
    echo -e "${GREEN}✓${NC} Respuesta optimizada ($FIELDS campos en lugar de 50+)"
else
    echo -e "${YELLOW}⚠${NC} Muchos campos en respuesta ($FIELDS)"
fi

# Resumen
echo ""
echo "=========================================="
echo "✅ Pruebas completadas"
echo "=========================================="
echo ""
echo "Próximos pasos:"
echo "1. Revisar logs: tail -f storage/logs/laravel.log"
echo "2. Ver documentación: docs/PRUEBA_FASE_1_BACKEND.md"
echo "3. Implementar siguiente fase"
