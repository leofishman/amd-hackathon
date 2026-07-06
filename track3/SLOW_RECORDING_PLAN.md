# Plan para grabación LENTA (versión ideal para sincronizar voz)

Objetivo: grabar **muy despacio**, quedándote mucho tiempo en cada pantalla clave.
Después yo extraigo los segmentos exactos que mejor pegan con cada frase del voiceover.

Duración aproximada del voiceover actual: **~116 segundos**.

## Reglas de esta grabación lenta
- Usá la misma config que antes (admin/admin, zoom 110-125%, factcheck ya corrido).
- Grabá **mudo** (sin mic).
- Navegá **extremadamente lento**. Ideal: quedate 12-25 segundos quieto en cada pantalla importante.
- Señalá con el mouse las cosas que menciona la voz (los claims CONTRADICTED, el Darwin, la tabla de decisions, la página de drupal.org, etc.).
- No te apures. Cuanto más material sobrado tenga, mejor puedo elegir los frames perfectos.
- Al final **quedate bastante tiempo** en la página de drupal.org (el cierre).

## Shot list exacto (seguí este orden)

### 1. El problema (voz ~0:00-0:18)
- **http://localhost:8080/node/5** (el ensayo del estudiante)
- Scroll muy lento de arriba a abajo.
- Quedate mínimo 20-25 segundos en esta pantalla.
- La voz dice que "looks perfectly fine".

### 2. Content scan + claims detallados (voz ~0:18-0:60 — la sección más larga)
- Andá a **http://localhost:8080/node/5/factcheck**
- Si hace falta, tocá "Run scan" y esperá.
- **Quedate mucho tiempo aquí** (ideal 50-70 segundos de footage).
  - Mostrá claramente los tres CONTRADICTED:
    - "founded in 1875"
    - manuscritos perdidos
    - Nobel Prize
  - Señalalos con el mouse en el mismo orden que dice la voz.
  - Bajá a **AI likelihood** (65% o el score actual) + rationale.
  - Bajá a **Plagiarism** → la frase de Darwin con la URL de la fuente.
    Quedate 8-10 segundos quieto en la sección de Darwin.

### 3. AMD + Decision log (voz ~0:60-0:82)
- Abrí la terminal del notebook (rocm-smi + log de vLLM/Gemma).
- Después andá a **http://localhost:8080/admin/reports/ai-router-decisions**
- **Importante**: quedate varios segundos en la tabla donde se ve claramente:
  - `chosen_model = amd_vllm__google_gemma_3_12b_it`
  - costo 0
- La voz dice "Here's the decision log".

### 4. Configuración + drupal.org (voz ~0:82-1:08)
- Andá a **http://localhost:8080/admin/config/ai/providers/universal/factcheck**
  (o la pantalla de settings del factcheck).
- Mostrá el provider AMD, Evidence corpus, etc.
- Andá a **Trusted sites** (lista o editando Nature).
- Pasá rápido por un artículo del corpus propio (/node/1 o similar).
- **Final fuerte**: abrí **https://www.drupal.org/project/ai_provider_universal**
  - Quedate **bastante tiempo** (20-30+ segundos) en esta página.
  - Ideal que se vea el repo, los archivos, factcheck.md, etc.
  - La voz dice "published on drupal dot org as the Universal AI Provider module. Here is the full source..."

### 5. Cierre (voz ~1:08-1:16)
- Podés volver a la landing del demo (/node/6) o quedarte en la página de drupal.
- La voz dice el tagline final.

## Consejos para que me sea fácil editar después
- Usá nombres claros: `slow-raw-2026-07-07.mkv`
- No hagas transiciones rápidas ni scrolls muy veloces.
- Cada vez que cambies de pantalla importante, quedate quieto un rato.
- Si querés, podés decir en voz alta "sección 1", "sección 2", etc. mientras grabás (después lo saco).

## Después que grabes
1. Poné el archivo en `track3/`
2. Decime el nombre.
3. Yo voy a:
   - Extraer frames cada pocos segundos para analizar.
   - Armar un storyboard con timestamps exactos.
   - Crear clips quirúrgicos que peguen perfecto con cada párrafo de la voz.
   - Mezclar con la voz (Kokoro preferentemente).
   - Devolverte `final.mp4` limpio.

¿Querés que te arme también una versión más corta del plan (con tiempos mínimos sugeridos) o preferís ir a full slow?

