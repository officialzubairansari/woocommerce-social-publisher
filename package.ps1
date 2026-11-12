# PowerShell wrapper to package WooCommerce Social Publisher
# Uses PHP ZipArchive to ensure standard UNIX forward slashes (/) so WordPress can extract and find the main plugin file.

$pluginDir = $PSScriptRoot
$buildScript = Join-Path $pluginDir "build-zip.php"

if (Test-Path $buildScript) {
    php $buildScript
    exit $LASTEXITCODE
} else {
    Write-Host "build-zip.php not found at $buildScript" -ForegroundColor Red
    exit 1
}
