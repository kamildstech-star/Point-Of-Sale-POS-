param([ValidateSet('backup', 'restore')][string]$Action = 'backup', [string]$BackupId)
$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Security
$projectRoot = Split-Path -Parent $PSScriptRoot
$privateRoot = Join-Path $projectRoot 'docs/audit/private'
if ($Action -eq 'backup') { $BackupId = 'backup_' + [Guid]::NewGuid().ToString('N') }
if ($BackupId -notmatch '^backup_[a-f0-9]{32}$') { throw 'A valid backup ID is required.' }
$backupDirectory = Join-Path $privateRoot $BackupId
$keyPath = Join-Path $backupDirectory 'key.dpapi'
if ($Action -eq 'backup') {
    New-Item -ItemType Directory -Path $backupDirectory -ErrorAction Stop | Out-Null
    $keyBytes = New-Object byte[] 32
    $randomSource = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    $randomSource.GetBytes($keyBytes)
    $randomSource.Dispose()
    $protectedKey = [System.Security.Cryptography.ProtectedData]::Protect($keyBytes, $null, [System.Security.Cryptography.DataProtectionScope]::CurrentUser)
    [System.IO.File]::WriteAllBytes($keyPath, $protectedKey)
} else {
    $keyBytes = [System.Security.Cryptography.ProtectedData]::Unprotect([System.IO.File]::ReadAllBytes($keyPath), $null, [System.Security.Cryptography.DataProtectionScope]::CurrentUser)
}
try {
    $env:LEGACY_BACKUP_KEY = [Convert]::ToBase64String($keyBytes)
    & php (Join-Path $PSScriptRoot 'legacy-recovery.php') $Action $BackupId
    if ($LASTEXITCODE -ne 0) { throw 'Recovery operation failed. See the sanitized message above.' }
} finally {
    Remove-Item Env:LEGACY_BACKUP_KEY -ErrorAction SilentlyContinue
    [Array]::Clear($keyBytes, 0, $keyBytes.Length)
}
