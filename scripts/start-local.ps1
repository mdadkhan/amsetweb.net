param(
    [string]$HostAddress = $env:AMSET_HOST,
    [int]$Port = $(if ($env:AMSET_PORT) { [int]$env:AMSET_PORT } else { 8000 }),
    [switch]$CheckOnly
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

if (-not $HostAddress) { $HostAddress = '127.0.0.1' }
$Root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$Tools = Join-Path $Root '.tools'

function Test-CompatiblePhp {
    param([string]$Executable)
    if (-not $Executable -or -not (Test-Path $Executable)) { return $false }
    return (& $Executable -r 'echo version_compare(PHP_VERSION, "8.3.0", ">=") ? "yes" : "no";') -eq 'yes'
}

$PhpCandidates = @(
    (Join-Path $Tools 'bin\php.cmd'),
    (Join-Path $Tools 'php\php.exe'),
    $(if (Get-Command php -ErrorAction SilentlyContinue) { (Get-Command php).Source })
)
$PhpExecutable = $PhpCandidates | Where-Object { Test-CompatiblePhp $_ } | Select-Object -First 1
if (-not $PhpExecutable) { throw 'PHP 8.3 or newer is required. Run scripts\setup-windows.ps1 first.' }

$NodeCandidates = @(
    (Join-Path $Tools 'node\node.exe'),
    $(if (Get-Command node -ErrorAction SilentlyContinue) { (Get-Command node).Source })
)
$NodeExecutable = $NodeCandidates | Where-Object { $_ -and (Test-Path $_) } | Select-Object -First 1
if (-not $NodeExecutable) { throw 'Node.js is required. Run scripts\setup-windows.ps1 first.' }
& $NodeExecutable -e 'const [major, minor] = process.versions.node.split(".").map(Number); process.exit(major > 22 || (major === 22 && minor >= 12) ? 0 : 1)'
if ($LASTEXITCODE -ne 0) { throw 'Node.js 22.12 or newer is required.' }
$NodeDirectory = Split-Path $NodeExecutable
$NpmExecutable = Join-Path $NodeDirectory 'npm.cmd'
if (-not (Test-Path $NpmExecutable)) { throw "npm was not found next to $NodeExecutable." }

$ComposerPhar = Join-Path $Tools 'composer\composer.phar'
if (-not (Test-Path $ComposerPhar)) { throw 'Project-local Composer is required. Run scripts\setup-windows.ps1 first.' }

Set-Location $Root
if (-not (Test-Path '.env')) { Copy-Item '.env.example' '.env' }
if ((Select-String -Path '.env' -Pattern '^DB_CONNECTION=sqlite$' -Quiet) -and -not (Test-Path 'database\database.sqlite')) {
    New-Item -ItemType File 'database\database.sqlite' | Out-Null
}

Write-Host '[AMSET] Installing PHP dependencies'
& $PhpExecutable $ComposerPhar install --no-interaction
Write-Host '[AMSET] Installing frontend dependencies'
& $NpmExecutable install
if (-not (Select-String -Path '.env' -Pattern '^APP_KEY=base64:.+' -Quiet)) { & $PhpExecutable artisan key:generate --force }
Write-Host '[AMSET] Applying database migrations'
& $PhpExecutable artisan migrate --force
Write-Host '[AMSET] Building production assets'
& $NpmExecutable run build

if ($CheckOnly) {
    Write-Host '[AMSET] Build check complete. Server launch skipped.'
    exit 0
}

$ExistingListeners = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue |
    Select-Object -ExpandProperty OwningProcess -Unique
if ($ExistingListeners) {
    Write-Host "[AMSET] Stopping existing server on port $Port (PID: $($ExistingListeners -join ', '))"
    $ExistingListeners | ForEach-Object { Stop-Process -Id $_ -Force -ErrorAction Stop }
}

Write-Host "[AMSET] Starting server at http://${HostAddress}:$Port"
& $PhpExecutable artisan serve --host=$HostAddress --port=$Port
