[CmdletBinding()]
param(
    [string]$Version = "2.1.0-beta1",
    [string]$OutputDirectory = "dist"
)

$ErrorActionPreference = "Stop"
$repositoryRoot = (Resolve-Path (Join-Path $PSScriptRoot "..\")).Path

$null = git -C $repositoryRoot diff --quiet
$workingTreeDirty = $LASTEXITCODE -ne 0
$null = git -C $repositoryRoot diff --cached --quiet
$indexDirty = $LASTEXITCODE -ne 0

if ($workingTreeDirty -or $indexDirty) {
    throw "Working tree is not clean. Commit the release changes before building a package."
}

$outputRoot = Join-Path $repositoryRoot $OutputDirectory
$stagingRoot = Join-Path $env:TEMP ("simweb-release-" + [guid]::NewGuid().ToString("N"))
$packageRoot = Join-Path $stagingRoot "simweb-$Version"
$archivePath = Join-Path $outputRoot "simweb-$Version.zip"

try {
    New-Item -ItemType Directory -Path $packageRoot -Force | Out-Null
    New-Item -ItemType Directory -Path $outputRoot -Force | Out-Null

    git -C $repositoryRoot archive --format=tar HEAD | tar -xf - -C $packageRoot

    $excludedPaths = @(
        ".git",
        ".github",
        ".vscode",
        "config.ini",
        "scripts/logs",
        "tools/airportFixer",
        "saas",
        "docker",
        "Dockerfile",
        "docker-compose.yml",
        "config.ini.docker"
    )

    foreach ($relativePath in $excludedPaths) {
        $path = Join-Path $packageRoot ($relativePath -replace '/', '\\')
        if (Test-Path $path) {
            Remove-Item -LiteralPath $path -Recurse -Force
        }
    }

    Compress-Archive -Path (Join-Path $packageRoot '*') -DestinationPath $archivePath -CompressionLevel Optimal -Force
    Write-Output "Release créée : $archivePath"
}
finally {
    if (Test-Path $stagingRoot) {
        Remove-Item -LiteralPath $stagingRoot -Recurse -Force
    }
}
