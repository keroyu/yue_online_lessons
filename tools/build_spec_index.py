#!/usr/bin/env python3
"""
build_spec_index.py  v2

Generates/updates three artifacts from specs/**/*.md:
  1. specs/spec_index.json  — feature index (US → anchor + code_files + status)
  2. specs/code_index.json  — reverse index (code file → [US ids])
  3. repo_map.md            — updates main_files: sections per module

Run after any spec change:
    python tools/build_spec_index.py

Preserved fields (manual, never overwritten by re-scan):
    summary, keywords, code_files, status
"""

import os
import re
import json
from collections import defaultdict

# ── Paths ─────────────────────────────────────────────────────────────────────
ROOT          = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SPECS_DIR     = os.path.join(ROOT, "specs")
SPEC_INDEX    = os.path.join(SPECS_DIR, "spec_index.json")
CODE_INDEX    = os.path.join(SPECS_DIR, "code_index.json")
REPO_MAP      = os.path.join(ROOT, "repo_map.md")

def _discover_module_dirs() -> list:
    """Auto-discover NNN-name feature directories under SPECS_DIR, sorted."""
    dirs = []
    if os.path.isdir(SPECS_DIR):
        for entry in sorted(os.listdir(SPECS_DIR)):
            if re.match(r"^\d{3}-", entry) and os.path.isdir(os.path.join(SPECS_DIR, entry)):
                dirs.append(entry)
    return dirs

MODULE_DIRS = _discover_module_dirs()

# ── Initial status seed ────────────────────────────────────────────────────────
# Applied only when a feature has no "status" field yet.
# Values: "implemented" | "partial" | "planned"
INITIAL_STATUSES = {
    "001.us-1": "implemented", "001.us-2": "implemented", "001.us-3": "implemented",
    "001.us-4": "implemented", "001.us-5": "implemented", "001.us-6": "implemented",

    "002.us-1": "implemented", "002.us-1a": "implemented",
    "002.us-2": "implemented", "002.us-2a": "implemented", "002.us-2b": "implemented",
    "002.us-3": "implemented", "002.us-4": "implemented",  "002.us-5": "implemented",
    "002.us-6": "implemented", "002.us-7": "implemented",  "002.us-8": "implemented",
    "002.us-9": "implemented", "002.us-10": "implemented", "002.us-11": "implemented",

    "003.us-1": "implemented", "003.us-2": "implemented", "003.us-3": "implemented",
    "003.us-4": "implemented", "003.us-5": "implemented", "003.us-6": "implemented",
    "003.us-7": "implemented",

    "004.us-1": "implemented", "004.us-2": "implemented", "004.us-3": "implemented",

    "006.us-1": "implemented", "006.us-2": "implemented", "006.us-3": "implemented",
    "006.us-4": "implemented", "006.us-5": "implemented",

    "005.us-1":   "implemented", "005.us-1.5": "implemented",
    "005.us-2":   "implemented", "005.us-3":   "implemented",
    "005.us-4":   "implemented", "005.us-5":   "implemented",
    "005.us-6":   "implemented", "005.us-15":  "implemented",
    "005.us-7":   "partial",     "005.us-8":   "partial",    "005.us-9": "partial",
    "005.us-10":  "planned",     "005.us-11":  "planned",
    "005.us-12":  "planned",     "005.us-13":  "planned",    "005.us-14": "planned",
}

PRESERVED_FIELDS = {"summary", "keywords", "code_files", "status"}

STORY_RE = re.compile(
    r"^### User Story ([\d\.a-zA-Z]+)\s*[-–]\s*(.+?)\s*\(Priority: P\d\)",
    re.IGNORECASE,
)


# ── Helpers ────────────────────────────────────────────────────────────────────

def heading_to_anchor(heading_text: str) -> str:
    text = heading_text.strip().lower()
    text = re.sub(r" ", "-", text)
    text = re.sub(r"[():/,\"'!?]", "", text)
    return text


def parse_spec_file(filepath: str, module_dir: str) -> list:
    module_id = module_dir[:3]
    rel_path  = f"specs/{module_dir}/spec.md"

    with open(filepath, encoding="utf-8") as f:
        lines = f.readlines()

    features = []
    for line in lines:
        match = STORY_RE.match(line.rstrip())
        if not match:
            continue
        story_num   = match.group(1)
        title       = match.group(2).strip()
        feature_id  = f"{module_id}.us-{story_num}"
        full_heading = line.lstrip("#").strip()
        features.append({
            "id":         feature_id,
            "module":     module_dir,
            "title":      title,
            "summary":    "",
            "heading":    f"### {full_heading}",
            "file":       rel_path,
            "anchor":     heading_to_anchor(full_heading),
            "keywords":   [],
            "code_files": [],
            "status":     "planned",
        })
    return features


def load_existing(filepath: str) -> list:
    if os.path.exists(filepath):
        with open(filepath, encoding="utf-8") as f:
            return json.load(f).get("features", [])
    return []


def merge(existing: list, parsed: list) -> list:
    by_id = {f["id"]: f for f in existing}

    for feature in parsed:
        fid = feature["id"]
        if fid in by_id:
            # Update structural fields
            for key in ("title", "heading", "anchor", "file", "module"):
                by_id[fid][key] = feature[key]
            # Fill missing preserved fields with defaults
            for key in PRESERVED_FIELDS:
                if key not in by_id[fid]:
                    by_id[fid][key] = (
                        INITIAL_STATUSES.get(fid, "planned") if key == "status"
                        else feature[key]
                    )
        else:
            feature["status"] = INITIAL_STATUSES.get(fid, "planned")
            by_id[fid] = feature

    return sorted(by_id.values(), key=lambda f: f["id"])


# ── Writers ────────────────────────────────────────────────────────────────────

def write_spec_index(features: list):
    with open(SPEC_INDEX, "w", encoding="utf-8") as f:
        json.dump({"features": features}, f, ensure_ascii=False, indent=2)
    implemented = sum(1 for f in features if f.get("status") == "implemented")
    partial     = sum(1 for f in features if f.get("status") == "partial")
    planned     = sum(1 for f in features if f.get("status") == "planned")
    print(f"  spec_index.json : {len(features)} features "
          f"({implemented} implemented / {partial} partial / {planned} planned)")


def write_code_index(features: list):
    code_map = defaultdict(list)
    for f in features:
        for path in f.get("code_files", []):
            if path and f["id"] not in code_map[path]:
                code_map[path].append(f["id"])

    result = {k: sorted(v) for k, v in sorted(code_map.items())}
    with open(CODE_INDEX, "w", encoding="utf-8") as f:
        json.dump({"files": result}, f, ensure_ascii=False, indent=2)
    print(f"  code_index.json : {len(result)} files indexed")


def update_repo_map(features: list):
    if not os.path.exists(REPO_MAP):
        print("  repo_map.md     : not found, skipping")
        return

    with open(REPO_MAP, encoding="utf-8") as f:
        content = f.read()

    # Group sorted code_files by module prefix
    files_by_module: dict[str, list] = defaultdict(set)
    for feat in features:
        prefix = feat["id"].split(".")[0]
        for path in feat.get("code_files", []):
            if path:
                files_by_module[prefix].add(path)

    files_by_module = {k: sorted(v) for k, v in files_by_module.items()}

    # Replace each module's "main_files:\n- ...\n" block
    changed = False
    for module_id, files in files_by_module.items():
        if not files:
            continue

        new_list = "\n".join(f"- {p}" for p in files) + "\n"

        # Matches: ## <anything> (NNN)\n...\nmain_files:\n<list of "- ...\n">
        pattern = re.compile(
            rf"(## [^\n]+\({module_id}\)[^\n]*\n(?:(?!##)[^\n]*\n)*?main_files:\n)"
            rf"((?:- [^\n]+\n)*)",
        )

        def replacer(m, new_list=new_list):
            nonlocal changed
            if m.group(2) != new_list:
                changed = True
            return m.group(1) + new_list

        content = pattern.sub(replacer, content)

    if changed:
        with open(REPO_MAP, "w", encoding="utf-8") as f:
            f.write(content)
        print("  repo_map.md     : main_files sections updated")
    else:
        print("  repo_map.md     : no changes needed")


# ── Main ───────────────────────────────────────────────────────────────────────

def main():
    print("Building spec index…\n")

    existing = load_existing(SPEC_INDEX)
    parsed   = []

    for module_dir in MODULE_DIRS:
        spec_file = os.path.join(SPECS_DIR, module_dir, "spec.md")
        if not os.path.exists(spec_file):
            print(f"  SKIP {module_dir}/spec.md")
            continue
        stories = parse_spec_file(spec_file, module_dir)
        print(f"  {module_dir}: {len(stories)} stories")
        parsed.extend(stories)

    merged = merge(existing, parsed)

    print()
    write_spec_index(merged)
    write_code_index(merged)
    update_repo_map(merged)
    print("\nDone.")


if __name__ == "__main__":
    main()
