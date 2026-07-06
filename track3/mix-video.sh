#!/usr/bin/env fish
# mix-video.sh — agrega la voz en off al condensed screen recording
# Uso:
#   1. Generá voiceover.mp3 desde VOICEOVER.txt (Gemini / 11labs / etc)
#   2. ./mix-video.sh
#   3. Revisá final-demo.mp4

set -l VIDEO "condensed-screen.mp4"
set -l AUDIO "voiceover.mp3"
set -l OUT "final-demo.mp4"

if not test -f $AUDIO
    echo "ERROR: No encuentro $AUDIO"
    echo "Generá el audio a partir de VOICEOVER.txt y guardalo como voiceover.mp3 (o .wav)"
    echo "Luego volvé a correr este script."
    exit 1
end

if not test -f $VIDEO
    echo "ERROR: No encuentro $VIDEO"
    exit 1
end

echo "==> Mezclando $VIDEO + $AUDIO → $OUT"

ffmpeg -y -i $VIDEO -i $AUDIO \
    -map 0:v -map 1:a \
    -c:v libx264 -preset fast -crf 19 \
    -c:a aac -b:a 160k \
    -shortest -movflags +faststart \
    $OUT

echo "==> Listo: $OUT"
ffprobe -v quiet -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 $OUT
echo "Duración objetivo del voiceover ~110-130s + margen."
