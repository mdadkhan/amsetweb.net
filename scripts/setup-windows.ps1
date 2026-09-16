$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$Root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$Tools = Join-Path $Root '.tools'
$Temp = Join-Path $Tools 'tmp'
$PhpDirectory = Join-Path $Tools 'php'
$NodeDirectory = Join-Path $Tools 'node'
$ComposerDirectory = Join-Path $Tools 'composer'

@('bin', 'cache', 'composer', 'node', 'php', 'tmp') | ForEach-Object {
    New-Item -ItemType Directory -Force -Path (Join-Path $Tools $_) | Out-Null
}

if ([System.Runtime.InteropServices.RuntimeInformation]::OSArchitecture -ne 'X64') {
    throw 'The portable PHP package used by this project requires 64-bit Windows (x64).'
}

function Get-RemoteFile {
    param([string]$Uri, [string]$Destination)
    Invoke-WebRequest -UseBasicParsing -Uri $Uri -OutFile $Destination
}

$PhpExecutable = Join-Path $PhpDirectory 'php.exe'
$InstallPhp = $true
if (Test-Path $PhpExecutable) {
    $InstallPhp = -not ((& $PhpExecutable -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;') -eq '8.3')
}

if ($InstallPhp) {
    Write-Host '[AMSET] Installing portable PHP 8.3'
    $Releases = Invoke-RestMethod -Uri 'https://windows.php.net/downloads/releases/releases.json'
    $ReleaseName = $Releases.PSObject.Properties.Name | Where-Object { $_ -like '8.3.*' } | Sort-Object { [version]$_ } -Descending | Select-Object -First 1
    if (-not $ReleaseName) { throw 'Could not resolve a PHP 8.3 Windows release.' }
    $Package = $Releases.$ReleaseName.nts_vs17_x64.zip
    $PhpZip = Join-Path $Temp $Package.path
    Get-RemoteFile "https://windows.php.net/downloads/releases/$($Package.path)" $PhpZip
    if ((Get-FileHash $PhpZip -Algorithm SHA256).Hash.ToLowerInvariant() -ne $Package.sha256.ToLowerInvariant()) { throw 'PHP archive checksum verification failed.' }
    Remove-Item $PhpDirectory -Recurse -Force -ErrorAction SilentlyContinue
    New-Item -ItemType Directory -Force -Path $PhpDirectory | Out-Null
    Expand-Archive $PhpZip -DestinationPath $PhpDirectory -Force
    Copy-Item (Join-Path $PhpDirectory 'php.ini-development') (Join-Path $PhpDirectory 'php.ini') -Force
    $IniPath = Join-Path $PhpDirectory 'php.ini'
    $Ini = Get-Content $IniPath -Raw
    $Ini = $Ini -replace ';extension_dir = "ext"', 'extension_dir = "ext"'
    foreach ($Extension in @('curl', 'fileinfo', 'mbstring', 'openssl', 'pdo_mysql', 'pdo_sqlite', 'zip')) {
        $Ini = $Ini -replace ";extension=$Extension", "extension=$Extension"
    }
    Set-Content -Path $IniPath -Value $Ini -Encoding ASCII
} else {
    Write-Host '[AMSET] Compatible PHP 8.3 already installed; skipping'
}

$NodeExecutable = Join-Path $NodeDirectory 'node.exe'
$InstallNode = $true
if (Test-Path $NodeExecutable) {
    $InstallNode = -not ((& $NodeExecutable -p 'process.versions.node.split(".")[0]') -eq '22')
}

if ($InstallNode) {
    Write-Host '[AMSET] Installing portable Node.js 22'
    $NodeRelease = (Invoke-RestMethod -Uri 'https://nodejs.org/dist/index.json' | Where-Object { $_.version -like 'v22.*' } | Select-Object -First 1).version
    if (-not $NodeRelease) { throw 'Could not resolve the latest Node.js 22 release.' }
    $NodeZipName = "node-$NodeRelease-win-x64.zip"
    $NodeZip = Join-Path $Temp $NodeZipName
    Get-RemoteFile "https://nodejs.org/dist/$NodeRelease/$NodeZipName" $NodeZip
    $Checksums = (Invoke-WebRequest -UseBasicParsing -Uri "https://nodejs.org/dist/$NodeRelease/SHASUMS256.txt").Content
    $ExpectedHash = (($Checksums -split "`n" | Where-Object { $_ -match [regex]::Escape($NodeZipName) }) -split '\s+')[0]
    if ((Get-FileHash $NodeZip -Algorithm SHA256).Hash.ToLowerInvariant() -ne $ExpectedHash.ToLowerInvariant()) { throw 'Node.js archive checksum verification failed.' }
    Remove-Item $NodeDirectory -Recurse -Force -ErrorAction SilentlyContinue
    New-Item -ItemType Directory -Force -Path $NodeDirectory | Out-Null
    $ExpandedNode = Join-Path $Temp "node-$NodeRelease-win-x64"
    Remove-Item $ExpandedNode -Recurse -Force -ErrorAction SilentlyContinue
    Expand-Archive $NodeZip -DestinationPath $Temp -Force
    Copy-Item "$ExpandedNode\*" $NodeDirectory -Recurse -Force
} else {
    Write-Host '[AMSET] Compatible Node.js 22 already installed; skipping'
}

$ComposerInstaller = Join-Path $Temp 'composer-setup.php'
Get-RemoteFile 'https://getcomposer.org/installer' $ComposerInstaller
$ExpectedComposerHash = (Invoke-WebRequest -UseBasicParsing -Uri 'https://composer.github.io/installer.sig').Content.Trim()
if ((Get-FileHash $ComposerInstaller -Algorithm SHA384).Hash.ToLowerInvariant() -ne $ExpectedComposerHash.ToLowerInvariant()) { throw 'Composer installer signature verification failed.' }
$ComposerPhar = Join-Path $ComposerDirectory 'composer.phar'
if (-not (Test-Path $ComposerPhar)) {
    Write-Host '[AMSET] Installing Composer in .tools\composer'
    & $PhpExecutable $ComposerInstaller --quiet --install-dir=$ComposerDirectory --filename=composer.phar
} else {
    Write-Host '[AMSET] Composer already installed; skipping'
}

$env:Path = "$PhpDirectory;$NodeDirectory;$Tools\bin;$env:Path"
Set-Content (Join-Path $Tools 'bin\php.cmd') "@echo off`r`n`"$PhpExecutable`" %*" -Encoding ASCII
Set-Content (Join-Path $Tools 'bin\composer.cmd') "@echo off`r`n`"$PhpExecutable`" `"$ComposerPhar`" %*" -Encoding ASCII

Set-Location $Root
if (-not (Test-Path '.env')) { Copy-Item '.env.example' '.env' }
if (-not (Test-Path 'database\database.sqlite')) { New-Item -ItemType File 'database\database.sqlite' | Out-Null }

Write-Host '[AMSET] Installing application dependencies'
& $PhpExecutable $ComposerPhar install --no-interaction
& (Join-Path $NodeDirectory 'npm.cmd') install
if (-not (Select-String -Path '.env' -Pattern '^APP_KEY=base64:.+' -Quiet)) { & $PhpExecutable artisan key:generate --force }
& $PhpExecutable artisan migrate --force
try { & $PhpExecutable artisan storage:link } catch { Write-Host '[AMSET] Storage link already exists' }
& (Join-Path $NodeDirectory 'npm.cmd') run build

Write-Host '[AMSET] Setup complete'
Write-Host "PHP: $(& $PhpExecutable -r 'echo PHP_VERSION;')"
Write-Host "Node: $(& $NodeExecutable --version)"
Write-Host 'Run: .\.tools\bin\php.cmd artisan serve'
