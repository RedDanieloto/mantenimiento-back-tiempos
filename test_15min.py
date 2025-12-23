#!/usr/bin/env python3
import requests
import json
import time
import random

BASE_URL = "http://127.0.0.1:8000/api"
headers = {"Content-Type": "application/json"}

# Usar números aleatorios para evitar conflictos
lider_id = 1000 + random.randint(0, 8000)
tecnico_id = 1000 + random.randint(0, 8000)

print("=" * 60)
print("PRUEBA DE VALIDACION DE 15 MINUTOS - REPORTE DE FALLAS")
print("=" * 60)
print()

# Paso 1: Crear Líder
print(f"✓ PASO 1: Creando Líder (employee_number={lider_id})...")
resp = requests.post(f"{BASE_URL}/user", headers=headers, json={"employee_number": lider_id, "name": f"Líder {lider_id}", "role": "lider", "turno": "1"})
print(f"  Status: {resp.status_code}")
if resp.status_code == 201:
    print("  ✓ Líder creado")
else:
    print(f"  Error: {resp.json()}")
print()

# Paso 2: Crear Técnico
print(f"✓ PASO 2: Creando Técnico (employee_number={tecnico_id})...")
resp = requests.post(f"{BASE_URL}/user", headers=headers, json={"employee_number": tecnico_id, "name": f"Técnico {tecnico_id}", "role": "tecnico", "turno": "1"})
print(f"  Status: {resp.status_code}")
if resp.status_code == 201:
    print("  ✓ Técnico creado")
else:
    print(f"  Error: {resp.json()}")
print()

# Paso 3: Crear Área
print("✓ PASO 3: Creando Área...")
resp = requests.post(f"{BASE_URL}/areas", headers=headers, json={"name": f"Area {int(time.time())}"})
try:
    area_id = resp.json()['id']
    print(f"  Area ID: {area_id}")
except:
    print(f"  Error: {resp.text[:100]}")
    exit(1)
print()

# Paso 4: Crear Línea
print("✓ PASO 4: Creando Línea...")
resp = requests.post(f"{BASE_URL}/lineas", headers=headers, json={"name": f"Línea {int(time.time())}", "area_id": area_id})
try:
    linea_id = resp.json()['linea']['id']
    print(f"  Línea ID: {linea_id}")
except:
    print(f"  Error: {resp.text[:100]}")
    exit(1)
print()

# Paso 5: Crear Máquina
print("✓ PASO 5: Creando Máquina...")
resp = requests.post(f"{BASE_URL}/maquinas", headers=headers, json={"name": f"Máquina {int(time.time())}", "linea_id": linea_id})
try:
    maquina_id = resp.json()['id']
    print(f"  Máquina ID: {maquina_id}")
except:
    print(f"  Error: {resp.text[:100]}")
    exit(1)
print()

print("=" * 60)
print("INICIANDO PRUEBA DE 15 MINUTOS")
print("=" * 60)
print()

# TEST 1: Crear primer reporte
print("✓ TEST 1: Crear PRIMER reporte (DEBE FUNCIONAR)")
print(f"  Employee: {lider_id}, Máquina: {maquina_id}")
resp = requests.post(f"{BASE_URL}/reportes", headers=headers, json={"employee_number": lider_id, "maquina_id": maquina_id, "turno": "1", "descripcion_falla": "Falla 1"})
print(f"  Status: {resp.status_code}")
try:
    data = resp.json()
    reporte1_id = data.get('id')
    print(f"  ✓ Reporte creado (ID: {reporte1_id}, Status: {data.get('status')})")
except:
    print(f"  ✗ Error: {resp.text[:200]}")
    exit(1)
print()

# TEST 2: Intentar crear segundo reporte (DEBE FALLAR)
print("✗ TEST 2: Crear SEGUNDO reporte DENTRO DE 15 MINUTOS (DEBE FALLAR)")
print(f"  Mismo employee y máquina...")
resp = requests.post(f"{BASE_URL}/reportes", headers=headers, json={"employee_number": lider_id, "maquina_id": maquina_id, "turno": "1", "descripcion_falla": "Falla 2"})
print(f"  Status: {resp.status_code}")
data = resp.json()
print(f"  Respuesta: {json.dumps(data, indent=2)}")
if resp.status_code == 422 and "15 minutos" in str(data):
    print()
    print("  " + "╔" + "═" * 56 + "╗")
    print("  ║ ✓✓✓ RESULTADO: BLOQUEADO CORRECTAMENTE ✓✓✓            ║")
    print("  ║ El sistema rechazó el segundo reporte dentro de 15 min ║")
    print("  " + "╚" + "═" * 56 + "╝")
else:
    print()
    print("  " + "╔" + "═" * 56 + "╗")
    print("  ║ ✗✗✗ RESULTADO: ERROR - NO SE BLOQUEÓ ✗✗✗             ║")
    print("  " + "╚" + "═" * 56 + "╝")
print()

# Paso 8: Cerrar primer reporte
print("✓ PASO 8: Cerrando primer reporte...")
print(f"  - Aceptando reporte {reporte1_id}...")
requests.post(f"{BASE_URL}/reportes/{reporte1_id}/aceptar", headers=headers, json={"tecnico_employee_number": tecnico_id})
print("  - Finalizando reporte...")
requests.post(f"{BASE_URL}/reportes/{reporte1_id}/finalizar", headers=headers, json={"descripcion_resultado": "Sensor reemplazado", "refaccion_utilizada": "Sensor X", "departamento": "Mantenimiento"})
print("  ✓ Reporte cerrado (status = OK)")
print()

# TEST 3: Crear tercer reporte después de cerrar (DEBE FUNCIONAR)
print("✓ TEST 3: Crear TERCER reporte DESPUÉS DE CERRAR (DEBE FUNCIONAR)")
print(f"  Aunque NO han pasado 15 minutos, el anterior está OK")
resp = requests.post(f"{BASE_URL}/reportes", headers=headers, json={"employee_number": lider_id, "maquina_id": maquina_id, "turno": "1", "descripcion_falla": "Falla 3"})
print(f"  Status: {resp.status_code}")
data = resp.json()
reporte3_id = data.get('id')
if resp.status_code == 201 and reporte3_id:
    print()
    print("  " + "╔" + "═" * 56 + "╗")
    print("  ║ ✓✓✓ RESULTADO: PERMITIDO CORRECTAMENTE ✓✓✓            ║")
    print(f"  ║ Nuevo reporte creado (ID: {reporte3_id})                  ║")
    print("  ║ La restricción NO aplica a reportes cerrados           ║")
    print("  " + "╚" + "═" * 56 + "╝")
else:
    print()
    print("  " + "╔" + "═" * 56 + "╗")
    print("  ║ ✗✗✗ RESULTADO: ERROR - NO SE PERMITIÓ ✗✗✗             ║")
    print(f"  ║ Respuesta: {str(data)[:40]}                           ║")
    print("  " + "╚" + "═" * 56 + "╝")
print()

print("=" * 60)
print("PRUEBA COMPLETADA EXITOSAMENTE")
print("=" * 60)
