#!/usr/bin/env python3
"""
Spec-Kit Plugin: spec_index

Entry point for the spec index pipeline.
Regenerates all three navigation artifacts whenever specs change:

  specs/spec_index.json   — feature index  (US → anchor + code_files + status)
  specs/code_index.json   — reverse index  (code file → [US ids])
  repo_map.md             — module map     (main_files sections auto-updated)

Usage:
    python plugins/spec_index_plugin.py            # rebuild all
    python plugins/spec_index_plugin.py --check    # dry-run, exit 1 if stale

Triggered by:
  - git post-commit hook  (.git/hooks/post-commit)
  - speckit.specify       (after writing spec.md)
  - /updatespec           (after updating spec files)
  - manually at any time
"""

import sys
import os
import subprocess

ROOT   = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SCRIPT = os.path.join(ROOT, "tools", "build_spec_index.py")


def run(check_only: bool = False):
    if not os.path.exists(SCRIPT):
        print(f"[spec_index_plugin] ERROR: build script not found at {SCRIPT}")
        sys.exit(1)

    cmd = [sys.executable, SCRIPT]
    if check_only:
        cmd.append("--check")

    result = subprocess.run(cmd, cwd=ROOT)
    sys.exit(result.returncode)


if __name__ == "__main__":
    check_only = "--check" in sys.argv
    run(check_only)
