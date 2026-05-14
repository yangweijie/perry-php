#!/bin/bash
# E2E Test: SwiftUI Calculator
# Generates, compiles, and runs the calculator to verify visual output.

set -euo pipefail
cd "$(dirname "$0")/.."

OUTPUT_DIR="/tmp/perry-e2e"
mkdir -p "$OUTPUT_DIR"

echo "=== E2E: SwiftUI Calculator ==="

# 1. Generate Swift code
echo "1. Generating Swift code..."
php examples/calculator.php swiftui > "$OUTPUT_DIR/Calculator.swift" 2>/dev/null

# 2. Compile
echo "2. Compiling..."
swiftc -o "$OUTPUT_DIR/Calculator" "$OUTPUT_DIR/Calculator.swift" \
    -framework SwiftUI -parse-as-library 2>&1 | grep -v "warning:" | grep -v "note:" || true

# Check if binary was created
if [ -f "$OUTPUT_DIR/Calculator" ]; then
    echo "   ✅ Compiled successfully"
else
    echo "   ❌ Compilation failed"
    exit 1
fi

# 3. Run (background), wait, screenshot, kill
echo "3. Running..."
open "$OUTPUT_DIR/Calculator"
sleep 3

# Take screenshot
SCREENSHOT="$OUTPUT_DIR/screenshot.png"
screencapture -T 0 "$SCREENSHOT" 2>/dev/null || true

# Kill app
kill $(pgrep -f "$OUTPUT_DIR/Calculator") 2>/dev/null || true

echo ""
echo "=== Done ==="
echo "Screenshot: $SCREENSHOT"
echo "Source:     $OUTPUT_DIR/Calculator.swift"
echo "Binary:     $OUTPUT_DIR/Calculator"
