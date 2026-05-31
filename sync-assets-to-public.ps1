$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$source = Join-Path $root "assets"
$target = Join-Path $root "public\assets"

if (-not (Test-Path -LiteralPath $source)) {
    throw "Dossier source introuvable: $source"
}

if (-not (Test-Path -LiteralPath $target)) {
    New-Item -ItemType Directory -Path $target | Out-Null
}

robocopy $source $target /MIR /XD uploads /NFL /NDL /NJH /NJS /NC /NS /NP
$exitCode = $LASTEXITCODE

if ($exitCode -gt 7) {
    throw "Synchronisation échouée avec le code robocopy $exitCode"
}

Write-Host "Assets synchronisés vers public/assets."
