# ============================================================
#  fix_ai_stream.ps1
#  Fix: AI assistant thinking/answer is not streamed in real-time.
#
#  Root cause: Apache runs PHP via mod_fcgid, whose default output
#  buffer is 64KB (FcgidOutputBufferSize = 65536). This buffers the
#  PHP SSE output and flushes it all at once when the request ends.
#  Fix: write "FcgidOutputBufferSize 0" into the vhost config, then
#  restart Apache.
#
#  Usage: double-click fix_ai_stream.bat in the same folder.
#  (It asks for administrator rights, needed to stop/start httpd.)
# ============================================================

$ErrorActionPreference = 'Stop'

$apacheDir = 'D:\phpstudy_pro\Extensions\Apache2.4.39'
$vhostConf = Join-Path $apacheDir 'conf\vhosts\0localhost_80.conf'
$httpdExe  = Join-Path $apacheDir 'bin\httpd.exe'
$httpdConf = Join-Path $apacheDir 'conf\httpd.conf'

# ---- 1. self-elevate to Administrator ----
$principal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Write-Host 'Requesting administrator rights (click Yes on the UAC prompt)...'
    Start-Process -FilePath 'powershell.exe' `
        -ArgumentList "-NoProfile -ExecutionPolicy Bypass -File `"$PSCommandPath`"" `
        -Verb RunAs
    exit 0
}

# ---- 2. ensure the config has FcgidOutputBufferSize 0 (idempotent) ----
if (-not (Test-Path -LiteralPath $vhostConf)) {
    Write-Host "ERROR: vhost config not found: $vhostConf"
    Read-Host 'Press Enter to exit'
    exit 1
}

$content = [System.IO.File]::ReadAllText($vhostConf)

if ($content -notmatch 'FcgidOutputBufferSize') {
    $add = "    # Disable mod_fcgid output buffering for real-time SSE streaming`r`n" +
           "    FcgidOutputBufferSize 0`r`n" +
           "    FcgidBusyTimeout 3600`r`n" +
           "    FcgidIOTimeout 3600`r`n"
    if ($content -match '(?m)^[ \t]*FcgidWrapper[^\r\n]*\r?\n') {
        # insert after the FcgidWrapper line
        $content = [regex]::Replace($content, '(?m)^([ \t]*FcgidWrapper[^\r\n]*\r?\n)', ('$1' + $add))
    } else {
        # fallback: insert before </VirtualHost>
        $content = $content -replace '(?m)^([ \t]*</VirtualHost>)', ($add + '$1')
    }
    [System.IO.File]::WriteAllText($vhostConf, $content)
    Write-Host "Wrote FcgidOutputBufferSize 0 -> $vhostConf"
} else {
    Write-Host 'FcgidOutputBufferSize already present, skipping write.'
}

# ---- 3. validate Apache config ----
Write-Host 'Validating Apache config...'
& $httpdExe -t -f $httpdConf
if ($LASTEXITCODE -ne 0) {
    Write-Host 'Apache config validation FAILED. Aborting.'
    Read-Host 'Press Enter to exit'
    exit 1
}
Write-Host 'Config OK (Syntax OK)'

# ---- 4. restart Apache ----
Write-Host 'Restarting Apache...'
taskkill /F /IM httpd.exe 2>$null | Out-Null
Start-Sleep -Seconds 2
Start-Process -FilePath $httpdExe -ArgumentList @('-f', $httpdConf) -WindowStyle Hidden
Start-Sleep -Seconds 3

# ---- 5. verify ----
$proc = Get-Process -Name httpd -ErrorAction SilentlyContinue
$port = Get-NetTCPConnection -LocalPort 80 -State Listen -ErrorAction SilentlyContinue
if ($proc -and $port) {
    Write-Host "Apache restarted, port 80 listening (PID $($proc[0].Id)). Done!"
} else {
    Write-Host 'Apache failed to start. Check log:'
    Write-Host "  $apacheDir\logs\error.log"
    Read-Host 'Press Enter to exit'
    exit 1
}

Write-Host ''
Read-Host 'Press Enter to exit'
