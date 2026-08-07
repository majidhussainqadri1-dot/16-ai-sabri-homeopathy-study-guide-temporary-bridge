#!/usr/bin/env python3
import hashlib, json, pathlib, sys
root = pathlib.Path(sys.argv[1]).resolve()
out = pathlib.Path(sys.argv[2])
files=[]
for path in sorted(p for p in root.rglob('*') if p.is_file()):
    data=path.read_bytes()
    files.append({'path': path.relative_to(root).as_posix(), 'size': len(data), 'sha256': hashlib.sha256(data).hexdigest()})
payload={'schema':'SCHA-source-manifest-v2','package':'16-sabri-classical-homeopathy-ai','version':'2.1.0','files':files}
out.write_text(json.dumps(payload,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
