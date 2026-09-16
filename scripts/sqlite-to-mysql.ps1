$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$Root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$Candidates = @(
    (Join-Path $Root '.tools\bin\php.cmd'),
    (Join-Path $Root '.tools\php\php.exe')
)
$PhpExecutable = $Candidates | Where-Object { Test-Path $_ } | Select-Object -First 1

if (-not $PhpExecutable) {
    $PhpCommand = Get-Command php -ErrorAction SilentlyContinue
    if (-not $PhpCommand) {
        throw 'PHP 8.3 or newer is required. Run scripts\setup-windows.ps1 first.'
    }
    $PhpExecutable = $PhpCommand.Source
}

Set-Location $Root
& $PhpExecutable artisan amset:sqlite-to-mysql @args
exit $LASTEXITCODE
