# Local TTS models (2026) para el video

## Recomendados

### 1. Kokoro-82M (mejor calidad general en 2026)
- Modelo: kokoro-v1.0.onnx + voices-v1.0.bin
- Calidad: excelente (gana la mayoría de comparaciones vs modelos más grandes)
- Velocidad: muy rápido incluso en CPU
- Voces: decenas (af_bella, af_sarah, am_adam, etc.)
- CLI: `kokoro-tts input.txt output.wav --voice af_bella`
- Descarga:
  wget https://github.com/nazdridoy/kokoro-tts/releases/download/v1.0.0/kokoro-v1.0.onnx
  wget https://github.com/nazdridoy/kokoro-tts/releases/download/v1.0.0/voices-v1.0.bin

### 2. Piper (más liviano y rápido para edge)
- Modelos .onnx (60MB)
- CLI: `piper -m model.onnx -c model.json -f out.wav`
- Voces: https://huggingface.co/rhasspy/piper-voices
- Ideal si querés algo minimal.

### 3. Ollama (Orpheus)
- `ollama run legraphista/Orpheus`
- TTS nativo vía Ollama (3B params)
- Bueno si ya vivís en el ecosistema Ollama + querés una sola tool.
- Ver: https://ollama.com/legraphista/Orpheus y LocalOrpheusTTS

## vLLM / llama.cpp
No sirven para generar audio (son para LLMs / texto).

## En este repo
- voiceover-kokoro.mp3 / .wav  ← generado con Kokoro (recomendado)
- voiceover.mp3 / .wav         ← generado con Piper

Para minisforum: copiá los comandos de arriba (o scp los binarios/modelos).
Si el minisforum tiene GPU AMD/ROCm, Kokoro se puede acelerar (usa torch o onnx).

