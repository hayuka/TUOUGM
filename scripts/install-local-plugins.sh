#!/usr/bin/env bash

set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
plugin_root="$repo_root/plugins"
lock_file="$repo_root/docker/third-party-plugins.lock"
hash_script="$repo_root/scripts/plugin-tree-hash.py"
tmp_dir="$(mktemp -d "${TMPDIR:-/tmp}/tuougm-plugins.XXXXXX")"
staging_path=""

cleanup() {
	if [[ -n "$staging_path" && -e "$staging_path" ]]; then
		rm -rf -- "$staging_path"
	fi
	rm -rf -- "$tmp_dir"
}
trap cleanup EXIT

for command_name in curl unzip sed awk head python3 cp mv mkdir; do
	if ! command -v "$command_name" >/dev/null 2>&1; then
		printf 'Required command not found: %s\n' "$command_name" >&2
		exit 1
	fi
done

if [[ ! -f "$lock_file" || ! -f "$hash_script" ]]; then
	printf 'Plugin lock or tree-hash helper is missing.\n' >&2
	exit 1
fi

read_plugin_version() {
	local plugin_file="$1"
	sed -nE 's/^[[:space:]*/#@]*Version:[[:space:]]*([^[:space:]*/]+).*/\1/p' "$plugin_file" | head -n 1
}

tree_hash() {
	python3 "$hash_script" "$1" "$2"
}

while IFS=$'\t' read -r slug version install_path archive_root entry_file state expected_hash archive_url ignored_paths; do
	[[ -z "${slug:-}" || "$slug" == \#* ]] && continue

	if [[ ! "$expected_hash" =~ ^[a-f0-9]{64}$ ]]; then
		printf 'Invalid SHA-256 fingerprint in lock file for %s.\n' "$slug" >&2
		exit 1
	fi
	case "$install_path" in
		""|/*|*..*)
			printf 'Unsafe plugin install path in lock file: %s\n' "$install_path" >&2
			exit 1
			;;
	esac
	case "$archive_root" in
		""|/*|*..*)
			printf 'Unsafe archive root in lock file: %s\n' "$archive_root" >&2
			exit 1
			;;
	esac
	if [[ "$ignored_paths" != "-" ]]; then
		IFS=',' read -r -a ignore_parts <<< "$ignored_paths"
		for ignore_part in "${ignore_parts[@]}"; do
			case "$ignore_part" in
				""|/*|*..*)
					printf 'Unsafe excluded path in lock file for %s: %s\n' "$slug" "$ignore_part" >&2
					exit 1
					;;
			esac
		done
	fi

	if [[ "$archive_url" == "-" ]]; then
		archive_url="https://downloads.wordpress.org/plugin/$slug.$version.zip"
	fi
	case "$archive_url" in
		https://downloads.wordpress.org/plugin/*) ;;
		*)
			printf 'Only official WordPress.org plugin archives are allowed: %s\n' "$slug" >&2
			exit 1
			;;
	esac

	target_path="$plugin_root/$install_path"
	target_file="$target_path/$entry_file"
	if [[ -L "$target_path" ]]; then
		printf 'Refusing to verify a symlinked plugin path: %s\n' "$target_path" >&2
		exit 1
	fi

	if [[ -e "$target_path" ]]; then
		if [[ ! -f "$target_file" ]]; then
			printf 'Plugin %s is incomplete at %s; it was left untouched. Back up and inspect it before retrying.\n' "$slug" "$target_path" >&2
			exit 1
		fi
		installed_version="$(read_plugin_version "$target_file")"
		if [[ "$installed_version" != "$version" ]]; then
			printf 'Plugin %s version mismatch: expected %s, found %s. No files were changed.\n' \
				"$slug" "$version" "${installed_version:-unknown}"
			exit 1
		fi
		actual_hash="$(tree_hash "$target_path" "$ignored_paths")"
		if [[ "$actual_hash" != "$expected_hash" ]]; then
			printf 'Plugin %s content/tree mismatch at %s. Expected %s, found %s. No files were changed.\n' \
				"$slug" "$target_path" "$expected_hash" "$actual_hash" >&2
			printf 'Back up and inspect the path; see docs/production-local-sync.md before retrying.\n' >&2
			exit 1
		fi
		printf 'OK %s %s (%s; verified locally, no download)\n' "$slug" "$version" "$state"
		continue
	fi

	archive="$tmp_dir/$slug.$version.zip"
	unpack_dir="$tmp_dir/unpacked-$slug"
	package_dir="$unpack_dir/$archive_root"
	mkdir -p "$unpack_dir"
	curl --fail --location --silent --show-error "$archive_url" --output "$archive"
	if ! unzip -tq "$archive" >/dev/null; then
		printf 'Archive integrity check failed for %s; no plugin files were changed.\n' "$slug" >&2
		exit 1
	fi
	if unzip -Z1 "$archive" | awk '/^(\/|[[:alpha:]]:)|(^|\/)\.\.(\/|$)/ { bad=1 } END { if (bad) exit 0; exit 1 }'; then
		printf 'Unsafe path found in archive for %s; no plugin files were changed.\n' "$slug" >&2
		exit 1
	fi
	unzip -q "$archive" -d "$unpack_dir"
	if [[ ! -f "$package_dir/$entry_file" ]]; then
		printf 'Expected entry file not found in %s: %s\n' "$archive_url" "$entry_file" >&2
		exit 1
	fi
	archive_version="$(read_plugin_version "$package_dir/$entry_file")"
	if [[ "$archive_version" != "$version" ]]; then
		printf 'Version mismatch in %s: expected %s, found %s\n' \
			"$archive_url" "$version" "${archive_version:-unknown version}" >&2
		exit 1
	fi
	archive_hash="$(tree_hash "$package_dir" "$ignored_paths")"
	if [[ "$archive_hash" != "$expected_hash" ]]; then
		printf 'Official archive fingerprint mismatch for %s %s: expected %s, found %s. Nothing was installed.\n' \
			"$slug" "$version" "$expected_hash" "$archive_hash" >&2
		exit 1
	fi

	mkdir -p "$(dirname "$target_path")"
	staging_path="${target_path}.installing.$$"
	if [[ -e "$staging_path" ]]; then
		printf 'Refusing to overwrite an existing staging path: %s\n' "$staging_path" >&2
		exit 1
	fi
	cp -R "$package_dir" "$staging_path"
	if [[ "$(tree_hash "$staging_path" "$ignored_paths")" != "$expected_hash" ]]; then
		printf 'Staged copy verification failed for %s; target was not changed.\n' "$slug" >&2
		exit 1
	fi
	mv "$staging_path" "$target_path"
	staging_path=""
	printf 'Installed and verified %s %s from WordPress.org\n' "$slug" "$version"
done < "$lock_file"
