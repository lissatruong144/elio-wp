# Packs elio/ into dist/elio-theme.zip for Appearance → Themes → Add New → Upload.
# Uses forward slashes in entry names (Compress-Archive on Windows PowerShell writes
# backslashes, which Linux servers unpack as flat file names).
$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression, System.IO.Compression.FileSystem

$root = Split-Path -Parent $PSScriptRoot
$src  = Join-Path $root 'elio'
$dist = Join-Path $root 'dist'
$zip  = Join-Path $dist 'elio-theme.zip'

New-Item -ItemType Directory -Force $dist | Out-Null
if (Test-Path $zip) { Remove-Item $zip }

$archive = [System.IO.Compression.ZipFile]::Open($zip, 'Create')
try {
	Get-ChildItem $src -Recurse -File | Where-Object { $_.Name -notlike '_*' } | ForEach-Object {
		$rel = 'elio/' + $_.FullName.Substring($src.Length + 1).Replace('\', '/')
		[System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive, $_.FullName, $rel, 'Optimal') | Out-Null
	}
} finally {
	$archive.Dispose()
}
'{0} ({1:N1} MB)' -f $zip, ((Get-Item $zip).Length / 1MB)
