# Estrategia — AMD Developer Hackathon ACT II, Track 1

Track 1: Hybrid Token-Efficient Routing Agent (6–11 julio 2026). Equipo
"Drupal AI Router". Este documento consolida las decisiones tomadas antes
del kickoff; lo que dependa de las reglas reveladas se marca **[KICKOFF]**.

## Tesis

No competimos escribiendo un agente desde cero contra 18k participantes.
Competimos con un **sistema de routing configurable dentro de un CMS real**:
`ai_provider_universal` (router cheapest-capable + factcheck + límites de
uso + log de decisiones) ya implementa la consigna del track — "local
cuenta 0 tokens" es exactamente el caso `cost_input/cost_output = 0` de los
modelos locales, y el router elige el candidato más barato capaz de la
tarea. El diferencial a vender:

- Flexibilidad: backends, rutas, tiers, costos, factcheck y límites son
  entidades de configuración de Drupal, no código.
- Trazabilidad nativa: cada decisión queda en `ai_universal_router_log`,
  expuesta por Views como página HTML y como JSON.
- Verificación: el factcheck (claims → evidencia → veredictos) es una
  defensa contra respuestas manipuladas que ningún agente standalone trae.
- No es un juguete: corre en el CMS que usan gobiernos y universidades.

## Riesgo #1: el contrato de interfaz con el harness de scoring

El scoring corre en un entorno estandarizado. La incógnita crítica no es
si el entorno banca el stack (un compose corre en cualquier lado) sino
**cómo le hablan a nuestro agente**. Mitigación: una sola lógica con tres
puertas de entrada sobre el mismo servicio `TaskRunner`
(módulo `amd_hackathon`):

1. **Drush**: `drush amd:task "..."` — si el harness es CLI, y para debug
   desde el minuto uno.
2. **Controller JSON**: `POST /agent/task` — si el harness es HTTP. Sin
   form de Drupal para esto (CSRF/cookies/HTML lo hacen inviable para un
   harness automático).
3. **Content type `agent_task`** — pipeline de demo: cada tarea es un
   nodo, ECA reacciona al insert, corre el TaskRunner y guarda
   respuesta/modelo/score en campos. Es la demo más drupalera y da Views
   sobre resultados, pero **no** es la puerta de scoring (auth + overhead
   de insertar nodos). **[KICKOFF]**: si el scoring pide throughput,
   la puerta es el controller o drush, nunca nodos.

El día 1 solo se re-mapea el formato de entrada/salida al que pidan.

## Seguridad (esperan que nos ataquen)

Es probable que los jueces prueben prompt injection. La inyección es
semántica, no se filtra con regex. Defensas reales, ya decididas:

- **Privilegio mínimo**: el TaskRunner solo llama al LLM y devuelve texto.
  Jamás ejecuta nada que venga en la respuesta del modelo.
- **Prompt como dato**: la tarea va siempre en el mensaje `user`; el
  system prompt fija el rol y ordena ignorar instrucciones embebidas.
- **Validación de salida**: formato esperado (JSON válido, campos
  presentes) antes de devolver; timeout duro.
- **Factcheck como defensa**: verificamos afirmaciones contra evidencia —
  argumento de seguridad diferencial para el README.
- Sin límite artificial de longitud de prompt (decisión de Leo); el límite
  real es el context length del modelo, que el router ya conoce.

## Performance (criterio probable de scoring)

- Modelos locales chicos y rápidos; pull configurable por env var
  (`OLLAMA_MODELS`), **no horneados en la imagen** — los modelos del track
  se revelan en el kickoff. Si hay bonus Gemma, default local = Gemma.
- Timeouts de servidor bajos (60 s, no 600) — un harness con timeout corto
  no perdona.
- Caches de Drupal activos (page/dynamic/render); el controller de tareas
  es kill switch de cache por definición (cada tarea es única), lo que
  importa es que el *bootstrap* sea barato: opcache activo, sin theming en
  la ruta JSON.
- Insertar nodos por tarea tiene overhead: reservado solo para la demo.
- RAG solo cuando la ruta lo pide (factcheck), no en cada request.

## ECA: alcance acotado

ECA orquesta el pipeline de demo (nodo → TaskRunner → campos), no la
decisión de routing (eso ya lo hace el router solo, en PHP compilado, más
rápido y más debuggeable). Si el track exige flujos multi-paso reales,
se amplía **[KICKOFF]**.

## Jueces LLM

Gran parte de la evaluación será por agentes LLM → optimizar para lectura
por máquina:

- README auto-explicativo con arquitectura, cómo correr, cómo evaluar.
- `/agent-decisions` (HTML) y `/agent-decisions.json` (REST export de la
  view de decisiones): timestamp, complejidad, modelo elegido,
  local/remoto, tokens estimados, costo elegido vs peor caso.
- Salidas JSON estructuradas en todas las puertas.

Theming (Haven + Gin) es prioridad baja: los jueces LLM leen texto, no CSS.

## Entregable: Docker

`docker compose up` debe funcionar en máquina limpia sin pasos manuales.

- Servicios: `web` (Drupal + drush horneados en build), `db` (MariaDB),
  `ollama`. Sin Traefik (puerto directo).
- Dependencias por composer **en build**, nunca en runtime; el módulo se
  instala desde git.drupalcode.org (branch 1.0.x).
- El entrypoint instala el sitio si no existe y aprovisiona: servidores
  (Ollama local sin costo + Fireworks por env var), pull de modelos vía
  la API de Ollama, descubrimiento de modelos, ruta smart, view de
  decisiones. Todo idempotente.
- API keys solo por variables de entorno (`.env`), nunca en la imagen ni
  en dumps.

## Checklist pre-kickoff

- [ ] Compose completo probado de punta a punta en máquina limpia.
- [ ] Módulo `amd_hackathon`: TaskRunner + drush command + controller.
- [ ] Timeouts locales bajados a 60 s en el provisioning.
- [ ] README orientado a jueces (humanos y LLM).

## Reglas confirmadas del Track 1 (2026-07-05)

- Scoring: **solo token count remoto + accuracy**, en entorno
  estandarizado. Local = 0 tokens. No puntúan latencia, UI ni README.
- Tareas y modelos se revelan en el kickoff. Fine-tuning permitido y
  puntuado igual que prompting.
- Recomiendan un "local eval step" antes de entregar output.

Consecuencias:

1. **Estrategia de mínimo token: local-first con verificación.** Como la
   latencia es gratis y lo local también, la jugada óptima no es
   clasificar a priori sino: generar local → verificar calidad local
   (0 tokens; p.ej. bespoke-minicheck o Gemma fine-tuneada como juez) →
   escalar a Fireworks **solo si la verificación falla**. Implementado
   nativo en el módulo: campo `verifier_model` en la ruta (una llamada
   local sí/no, rechazo escala al mejor candidato, fail-open). Se setea
   con `AGENT_VERIFIER_MODEL` en `.env` o en el form de la ruta.
2. **Podar tokens de la rama remota**: el system prompt viaja en cada
   llamada a Fireworks y cuenta. Versión mínima para remoto.
3. El fine-tune de Gemma más rentable puede ser el **verificador**
   ("¿es correcta esta respuesta? sí/no") además del clasificador; el
   seam `classifier_model` sirve para ambos patrones.
4. Theming/dashboard: solo para el video de presentación, cero impacto
   en score.

## Checklist kickoff (día 1)

- [ ] Mapear formato de entrada/salida del harness a la puerta que pidan.
- [ ] Cambiar `OLLAMA_MODELS` a los modelos revelados; ajustar tiers.
- [ ] Ajustar candidatos/tiers de las rutas a las tareas reales.
- [ ] Decidir factcheck on/off por ruta según el peso de accuracy en el
      scoring.
