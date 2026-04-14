#!/bin/bash
# ------------------------------------------------------------------
# Isolated Setup – executed INSIDE the WP container
# ------------------------------------------------------------------
# Runs before the *run* phase of THIS package only.
# Safe place to create test data that must not leak to other packages.

set -euo pipefail

echo "[setup] Creating sample data ..."
# Example:
# wp wc product create --name="Test Product" --type=simple --price=9.99
echo "[setup] Done."