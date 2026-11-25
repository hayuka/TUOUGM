#!/usr/bin/env python3
"""Print a stable SHA-256 for a plugin tree, including paths and file contents."""

from __future__ import annotations

import hashlib
import os
import sys
from pathlib import Path


def main() -> int:
    if len(sys.argv) != 3:
        print("Usage: plugin-tree-hash.py DIRECTORY EXCLUDED_PATHS", file=sys.stderr)
        return 2

    root = Path(sys.argv[1]).resolve(strict=True)
    if not root.is_dir():
        print(f"Not a directory: {root}", file=sys.stderr)
        return 2

    excluded = {part.strip("/") for part in sys.argv[2].split(",") if part and part != "-"}
    digest = hashlib.sha256()

    for path in sorted(root.rglob("*"), key=lambda item: item.relative_to(root).as_posix()):
        relative = path.relative_to(root).as_posix()
        if any(relative == item or relative.startswith(item + "/") for item in excluded):
            continue

        relative_bytes = os.fsencode(relative)
        if path.is_symlink():
            digest.update(b"L\0" + relative_bytes + b"\0" + os.fsencode(os.readlink(path)) + b"\n")
        elif path.is_dir():
            digest.update(b"D\0" + relative_bytes + b"\n")
        elif path.is_file():
            file_digest = hashlib.sha256()
            with path.open("rb") as source:
                for chunk in iter(lambda: source.read(1024 * 1024), b""):
                    file_digest.update(chunk)
            digest.update(b"F\0" + relative_bytes + b"\0" + file_digest.digest() + b"\n")

    print(digest.hexdigest())
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
