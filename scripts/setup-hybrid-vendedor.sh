#!/bin/bash
# setup-hybrid-vendedor.sh
#
# "Script más vendedor" para el pitch:
# - Usa hardware AMD local (minisforum ROCm / notebook) para extracción barata y rápida.
# - Usa Fireworks con un modelo denso (Gemma grande) para verificación de claims complejos.
# - Mantiene costo 0 en lo posible y muestra tiered inference en el demo.
#
# Uso (en el minisforum o donde corra Drupal):
#   AMD_VLLM_URL=http://localhost:11435/v1 \
#   FIREWORKS_API_KEY=sk-... \
#   bash scripts/setup-hybrid-vendedor.sh
#
# Luego revisá:
#   drush config:get ai_provider_universal_factcheck.settings
#   /admin/reports/ai-router-decisions

set -e

echo "=== AMD Hackathon - Setup Híbrido Vendedor ==="

DRUSH="drush"

# 1. Asegurar que los servers existen (AMD + Fireworks)
echo "--- Provisioning servers..."
$DRUSH scr /hackathon-scripts/provision-servers.php

# 2. Descubrir modelos
echo "--- Discovering models..."
if [ -n "${AMD_VLLM_URL:-}" ]; then
  $DRUSH aip:discover-models amd_vllm || echo "WARN: amd_vllm discovery failed"
fi
if [ -n "${FIREWORKS_API_KEY:-}" ]; then
  $DRUSH aip:discover-models fireworks || echo "WARN: fireworks discovery failed"
fi
$DRUSH aip:discover-models local_ollama || true

# 3. Elegir modelos
echo "--- Selecting models for hybrid factcheck..."

# AMD / local barato para extracción (rápido, costo 0)
AMD_MODEL=$($DRUSH aip:list-models --format=csv 2>/dev/null | grep -E 'amd_vllm|local_ollama' | grep -i gemma | head -1 | cut -d, -f1 || true)
if [ -z "$AMD_MODEL" ]; then
  AMD_MODEL=$($DRUSH aip:list-models --format=csv 2>/dev/null | grep -E 'amd_vllm|local_ollama' | head -1 | cut -d, -f1 || true)
fi

# Fireworks denso para verificación de claims complejos
FW_MODEL=$($DRUSH aip:list-models --format=csv 2>/dev/null | grep -i fireworks | grep -iE 'gemma|27b|12b' | head -1 | cut -d, -f1 || true)
if [ -z "$FW_MODEL" ]; then
  FW_MODEL=$($DRUSH aip:list-models --format=csv 2>/dev/null | grep fireworks | head -1 | cut -d, -f1 || true)
fi

echo "AMD / cheap model for extractor: ${AMD_MODEL:-<ninguno>}"
echo "Fireworks dense model for checker: ${FW_MODEL:-<ninguno>}"

if [ -z "$AMD_MODEL" ] && [ -z "$FW_MODEL" ]; then
  echo "ERROR: No se encontraron modelos. Corré discover-models primero."
  exit 1
fi

# 4. Configurar factcheck con tiered (extractor barato, checker premium si existe)
echo "--- Configuring factcheck settings (hybrid)..."

$DRUSH config:set ai_provider_universal_factcheck.settings max_claims 12 --yes

if [ -n "$AMD_MODEL" ]; then
  $DRUSH config:set ai_provider_universal_factcheck.settings extractor_model "$AMD_MODEL" --yes
  echo "  Extractor → $AMD_MODEL (AMD / local, rápido y barato)"
else
  echo "  WARN: sin modelo AMD para extractor"
fi

if [ -n "$FW_MODEL" ]; then
  $DRUSH config:set ai_provider_universal_factcheck.settings checker_model "$FW_MODEL" --yes
  echo "  Checker  → $FW_MODEL (Fireworks denso, para claims complejos)"
else
  if [ -n "$AMD_MODEL" ]; then
    $DRUSH config:set ai_provider_universal_factcheck.settings checker_model "$AMD_MODEL" --yes
    echo "  Checker  → $AMD_MODEL (fallback al mismo modelo AMD)"
  fi
fi

# Opcional: si tenés Tavily/Serper keys ya configuradas, se mantienen.

$DRUSH cr

echo ""
echo "=== Configuración híbrida lista ==="
$DRUSH config:get ai_provider_universal_factcheck.settings extractor_model
$DRUSH config:get ai_provider_universal_factcheck.settings checker_model

echo ""
echo "Próximos pasos recomendados:"
echo "  1. Probá Content scan en un ensayo de prueba."
echo "  2. Mirá /admin/reports/ai-router-decisions para ver qué modelo usó cada parte."
echo "  3. Actualizá la slide del deck / README con 'Tiered inference: AMD local para extracción + Fireworks premium para verificación'."
echo ""
echo "Si querés forzar el mismo modelo barato para todo, seteá ambas a $AMD_MODEL."
