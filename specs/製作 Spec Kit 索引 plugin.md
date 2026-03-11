

下面是一個可落地的 **Spec-Kit plugin 設計**。
目標是：每次新增或修改 spec 時，自動更新 agent 的索引資料，使 agent 不需要掃描整個 repo。整體設計包含三部分：plugin 觸發、索引生成、以及 agent 導航。

先看插件放置位置。假設 Spec-Kit 的 repo 已有 `specs/` 目錄與 CLI，你可以新增一個 plugins 目錄：

```
repo/
 ├ specs/
 ├ src/
 ├ tools/
 ├ plugins/
 │   └ spec_index_plugin.py
 ├ .agent_index/
 │   ├ repo_map.json
 │   ├ spec_index.json
 │   └ dependency_graph.json
 └ AGENT_PROTOCOL.md
```

第一部分是 plugin 的觸發機制。插件在兩個時機執行：  
1）新增 spec 檔案  
2）修改 spec 檔案

在 Spec-Kit CLI 或 Git hook 中觸發，例如：

```
speckit generate-spec
→ run plugin: spec_index_plugin.py
```

或在 git hook：

```
.git/hooks/post-commit
```

內容：

```
python tools/build_repo_index.py
```

第二部分是 plugin 的核心邏輯。插件會做三件事：

掃描 spec  
建立 feature index  
更新 repo map

下面是一個簡化 plugin。

plugins/spec_index_plugin.py

```
import os
import json
import yaml
import re

SPEC_DIR = "specs"
OUT_DIR = ".agent_index"

os.makedirs(OUT_DIR, exist_ok=True)

features = []

def extract_frontmatter(text):
    if text.startswith("---"):
        parts = text.split("---",2)
        return yaml.safe_load(parts[1])
    return {}

def extract_anchor(text):
    m = re.search(r"\{#(.*?)\}", text)
    return m.group(1) if m else ""

for root,dirs,files in os.walk(SPEC_DIR):

    for file in files:

        if not file.endswith(".md"):
            continue

        path = os.path.join(root,file)

        with open(path,"r",encoding="utf8") as f:
            text = f.read()

        fm = extract_frontmatter(text)

        if "feature_id" not in fm:
            continue

        anchor = extract_anchor(text)

        features.append({
            "id": fm["feature_id"],
            "module": fm.get("module"),
            "file": path,
            "anchor": anchor,
            "keywords": fm.get("keywords",[]),
            "code_files": fm.get("code_files",[])
        })

index = { "features": features }

with open(f"{OUT_DIR}/spec_index.json","w") as f:
    json.dump(index,f,indent=2)

print("spec_index updated")
```

第三部分是 repo map 的自動生成。這個文件幫 agent理解整體模組。

tools/build_repo_map.py

```
import os
import json

SRC_DIR="src"
OUT=".agent_index/repo_map.json"

modules={}

for root,dirs,files in os.walk(SRC_DIR):

    module=root.split("/")[1] if "/" in root else root

    modules.setdefault(module,[])

    for file in files:

        if file.endswith((".ts",".js",".py")):

            modules[module].append(os.path.join(root,file))

repo_map={
    "modules":[
        {
            "name":k,
            "files":v[:5]
        }
        for k,v in modules.items()
    ]
}

with open(OUT,"w") as f:
    json.dump(repo_map,f,indent=2)
```

第四部分是 agent 的導航規則。這是整個系統最重要的地方。Agent 必須先讀 index，而不是掃 repo。

AGENT_PROTOCOL.md：

```
Agent navigation rules

1 read .agent_index/repo_map.json
2 search .agent_index/spec_index.json
3 locate feature id
4 read spec section
5 read listed code_files

never scan the whole repo unless necessary
prefer targeted retrieval
```

第五部分是 agent 任務時的實際流程。

當 agent 收到任務：

```
implement login validation
```

流程會是：

讀 repo_map.json  
→ 找 auth module

查 spec_index.json  
→ feature auth.login

讀 spec  
→ specs/auth.md#login-flow

讀 code_files  
→ src/auth/service.ts

只在必要時再讀其他檔案。

這種流程的效果通常是：

context token 使用量下降  
定位速度更快  
agent hallucination 減少

最後補一個實務建議。不要把 spec index 做得太複雜。最有用的字段只有五個：

feature_id  
module  
file  
anchor  
code_files

只要這五個欄位穩定存在，agent 就能可靠導航。
