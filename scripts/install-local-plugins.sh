#!/usr/bin/env bash

set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
plugin_root="$repo_root/plugins"
lock_file="$repo_root/docker/third-party-plugins.lock"

for command_name in curl unzip sed awk head; do
	if ! command -v "$command_name" >/dev/null 2>&1; then
		printf 'Required command not found: %s\n' "$command_name" >&2
		exit 1
	fi
done

if [[ ! -f "$lock_file" ]]; then
	printf 'Plugin lock file not found: %s\n' "$lock_file" >&2
	exit 1
fi

tmp_dir="$(mktemp -d "${TMPDIR:-/tmp}/tuougm-plugins.XXXXXX")"
trap 'rm -rf "$tmp_dir"' EXIT

read_plugin_version() {
	local plugin_file="$1"
	sed -nE 's/^[[:space:]*/#@]*Version:[[:space:]]*([^[:space:]*/]+).*/\1/p' "$plugin_file" | head -n 1
}

while IFS=$'\t' read -r slug version install_path archive_root entry_file state; do
	[[ -z "${slug:-}" || "$slug" == \#* ]] && continue

	if [[ "$install_path" == "." ]]; then
		target_path="$plugin_root"
		target_file="$plugin_root/$entry_file"
	else
		case "$install_path" in
			/*|*..*)
				printf 'Unsafe plugin install path in lock file: %s\n' "$install_path" >&2
				exit 1
				;;
		esac
		target_path="$plugin_root/$install_path"
		target_file="$target_path/$entry_file"
	fi

	if [[ -f "$target_file" ]]; then
		installed_version="$(read_plugin_version "$target_file")"
		if [[ "$installed_version" == "$version" ]]; then
			printf 'OK %s %s (%s)\n' "$slug" "$version" "$state"
			continue
		fi
		printf 'Refusing to replace existing %s: expected %s, found %s\n' \
			"$target_file" "$version" "${installed_version:-unknown version}" >&2
		exit 1
	fi

	if [[ "$install_path" != "." && -e "$target_path" ]]; then
		printf 'Refusing to overwrite an existing plugin directory without its expected entry file: %s\n' "$target_path" >&2
		exit 1
	fi

	archive="$tmp_dir/$slug.$version.zip"
	unpack_dir="$tmp_dir/unpacked-$slug"
	archive_url="https://downloads.wordpress.org/plugin/$slug.$version.zip"
	mkdir -p "$unpack_dir"
	curl --fail --location --silent --show-error "$archive_url" --output "$archive"
	unzip -q "$archive" -d "$unpack_dir"

	package_dir="$unpack_dir/$archive_root"
	package_file="$package_dir/$entry_file"
	if [[ ! -f "$package_file" ]]; then
		printf 'Expected entry file not found in %s: %s\n' "$archive_url" "$entry_file" >&2
		exit 1
	fi

	archive_version="$(read_plugin_version "$package_file")"
	if [[ "$archive_version" != "$version" ]]; then
		printf 'Version mismatch in %s: expected %s, found %s\n' \
			"$archive_url" "$version" "${archive_version:-unknown version}" >&2
		exit 1
	fi

	if [[ "$install_path" == "." ]]; then
		cp -p "$package_file" "$target_file"
	else
		mkdir -p "$(dirname "$target_path")"
		mv "$package_dir" "$target_path"
	fi

	printf 'Installed %s %s from WordPress.org\n' "$slug" "$version"
done < "$lock_file"
