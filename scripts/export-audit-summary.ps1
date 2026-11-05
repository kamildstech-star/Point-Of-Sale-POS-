param([string]$ReportPath = 'docs/audit/private/source-baseline.json')
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$report = Get-Content -LiteralPath (Join-Path $projectRoot $ReportPath) -Raw | ConvertFrom-Json
if (-not $report.database.snapshot_complete) { throw 'Cannot publish an incomplete schema audit.' }
$schemaDocument = New-Object System.Text.StringBuilder
[void]$schemaDocument.AppendLine('# Observed local database schema')
[void]$schemaDocument.AppendLine('')
[void]$schemaDocument.AppendLine('Read-only local snapshot, 2026-10-19. This contains DDL only; no customer or credential records. Production schema has not been verified.')
foreach ($table in $report.tables.PSObject.Properties) {
    [void]$schemaDocument.AppendLine('')
    [void]$schemaDocument.AppendLine('## ' + $table.Name)
    [void]$schemaDocument.AppendLine('')
    [void]$schemaDocument.AppendLine('Observed rows: ' + $table.Value.row_count)
    [void]$schemaDocument.AppendLine('')
    [void]$schemaDocument.AppendLine('```sql')
    [void]$schemaDocument.AppendLine($table.Value.ddl + ';')
    [void]$schemaDocument.AppendLine('```')
}
[IO.File]::WriteAllText((Join-Path $projectRoot 'docs/audit/local-schema.md'), $schemaDocument.ToString())
$inventory = New-Object System.Text.StringBuilder
[void]$inventory.AppendLine('# Local audit evidence')
[void]$inventory.AppendLine('')
[void]$inventory.AppendLine('Captured 2026-10-19. Driver: ' + $report.database.driver + '. Server: ' + $report.database.server_version + '.')
[void]$inventory.AppendLine('')
[void]$inventory.AppendLine('| Table | Observed rows |')
[void]$inventory.AppendLine('| --- | --- |')
foreach ($table in $report.tables.PSObject.Properties) {
    [void]$inventory.AppendLine('| ' + $table.Name + ' | ' + $table.Value.row_count + ' |')
}
[void]$inventory.AppendLine('')
[void]$inventory.AppendLine('All 45 legacy migrations are recorded as applied. Local inventory, sales, returns, dealers, ledgers, expenses and daily accounts are empty. No duplicate stock combinations/codes, negative quantities or checked orphan relationships were found. These checks on an empty local business database do not establish production data integrity.')
[void]$inventory.AppendLine('')
[void]$inventory.AppendLine('Private JSON reports retain exact stock-by-ID balances, business-row fingerprints, aggregate financial baselines, indexes and foreign keys. Customer identities, password hashes and connection secrets are not reproduced here.')
[void]$inventory.AppendLine('')
[void]$inventory.AppendLine('## Existing route issues')
[void]$inventory.AppendLine('')
[void]$inventory.AppendLine('| Route | Action | Issue |')
[void]$inventory.AppendLine('| --- | --- | --- |')
foreach ($route in $report.routes | Where-Object issue) {
    [void]$inventory.AppendLine('| ' + $route.uri + ' | ' + $route.action + ' | ' + $route.issue + ' |')
}
[void]$inventory.AppendLine('')
[void]$inventory.AppendLine('A full `route:list` still fails on the missing BusinessDealerController. The audit reads route registrations without invoking their business actions. Baseline scaffold tests initially produced 1 pass/1 failure; the guest redirect expectation was corrected, and the new API suite uses an isolated test database. See the implementation and recovery documents for validation evidence and remaining deployment gates.')
[IO.File]::WriteAllText((Join-Path $projectRoot 'docs/audit/local-audit.md'), $inventory.ToString())
Write-Output 'Published sanitized local schema and audit summaries.'
