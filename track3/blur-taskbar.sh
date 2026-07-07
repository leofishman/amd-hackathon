#!/bin/bash
# blur-taskbar.sh
# Blurs the bottom taskbar/panel in screen recordings to hide dates, apps, and Cinnamon panel.
# Useful when mixing footage from different days/sessions.
#
# Usage:
#   bash track3/blur-taskbar.sh input.mp4 output.mp4
#
# For 1920x1200 recordings (common in this project). Adjust BAR_HEIGHT if your panel is taller.

set -e

IN="${1:-}"
OUT="${2:-}"

if [ -z "$IN" ] || [ -z "$OUT" ]; then
  echo "Usage: $0 input.mp4 output.mp4"
  exit 1
fi

# Typical Cinnamon bottom panel height on 1920x1200 with standard theme
BAR_HEIGHT=70

# Strong blur on the bottom bar + slight overlay to make it clean
ffmpeg -i "$IN" \
  -vf "gblur=sigma=25:steps=5,drawbox=x=0:y=ih-${BAR_HEIGHT}:w=iw:h=${BAR_HEIGHT}:color=black@0.85" \
  -c:v libx264 -crf 19 -preset fast \
  -c:a copy \
  "$OUT"

echo "Done: $OUT (taskbar blurred)"