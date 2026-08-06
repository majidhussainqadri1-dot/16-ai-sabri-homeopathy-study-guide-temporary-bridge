#!/usr/bin/env python3
from pathlib import Path
import hashlib
import json
import stat
import zipfile

root = Path(__file__).resolve().parents[1]
plugin = root / '16-sabri-classical-homeopathy-ai'
dist = root / 'dist'
dist.mkdir(exist_ok=True)
version = '1.0.0'
zip_path = dist / f'16-sabri-classical-homeopathy-ai-{version}.zip'
exclude_parts = {'tests', 'scripts', '.git', '.github', '__pycache__'}
files = [p for p in plugin.rglob('*') if p.is_file() and not (set(p.relative_to(plugin).parts) & exclude_parts)]
files.sort(key=lambda p: p.as_posix())
with zipfile.ZipFile(zip_path, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=9) as zf:
    for path in files:
        relative = Path(plugin.name) / path.relative_to(plugin)
        info = zipfile.ZipInfo(relative.as_posix(), (2026, 8, 6, 12, 0, 0))
        info.compress_type = zipfile.ZIP_DEFLATED
        mode = 0o755 if path.name == 'uninstall.php' else 0o644
        info.external_attr = (stat.S_IFREG | mode) << 16
        zf.writestr(info, path.read_bytes())
sha = hashlib.sha256(zip_path.read_bytes()).hexdigest()
checksum = zip_path.with_suffix('.zip.sha256')
checksum.write_text(f'{sha}  {zip_path.name}\n', encoding='utf-8')
manifest = {
    'package': zip_path.name,
    'sha256': sha,
    'version': version,
    'schema_version': '1.1.0',
    'plan': 'SSH-F16-PLAN-2026-v1.0',
    'file_count': len(files),
    'top_level_folder': plugin.name,
    'build_timestamp': '2026-08-06T20:49:00+05:00',
}
(dist / 'manifest.json').write_text(json.dumps(manifest, indent=2) + '\n', encoding='utf-8')
print(zip_path)
print(sha)
