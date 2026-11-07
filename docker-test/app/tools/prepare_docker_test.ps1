[CmdletBinding()]
param(
    [string]$Version = "2.1.0-beta1"
)

$ErrorActionPreference = "Stop"
$repositoryRoot = (Resolve-Path (Join-Path $PSScriptRoot "..\")).Path
$archivePath = Join-Path $repositoryRoot "dist\simweb-$Version.zip"
$testAppRoot = Join-Path $repositoryRoot "docker-test\app"

if (-not (Test-Path $archivePath)) {
    throw "Package absent: $archivePath. Build it first with tools/build_release.ps1."
}

if (Test-Path $testAppRoot) {
    Remove-Item -LiteralPath $testAppRoot -Recurse -Force
}

New-Item -ItemType Directory -Path $testAppRoot -Force | Out-Null
Expand-Archive -LiteralPath $archivePath -DestinationPath $testAppRoot -Force

Write-Output "Application de test extraite dans : $testAppRoot"
Write-Output "Lancez ensuite : docker compose -f docker-compose.test.yml up --build"
Write-Output "Site de test : http://localhost:8090/install/"
