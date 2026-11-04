param(
    [string]$Php = 'C:\8.3.31\php.exe'
)

$ErrorActionPreference = 'Stop'
$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$runner = Join-Path $projectRoot 'storage\app\private\live-ai-campaign.php'
$docs = Join-Path $projectRoot 'docs'

if (-not (Test-Path -LiteralPath $Php -PathType Leaf)) { throw "PHP CLI introuvable: $Php" }
if (-not (Test-Path -LiteralPath $runner -PathType Leaf)) { throw "Runner introuvable: $runner" }

Push-Location $projectRoot
try {
    $started = Get-Date
    $output = & $Php $runner 2>&1
    $exitCode = $LASTEXITCODE
    $reports = Get-ChildItem -LiteralPath $docs -Filter 'audit-ia-live-*.json' -File |
        Where-Object { $_.LastWriteTime -ge $started.AddSeconds(-2) } |
        Sort-Object LastWriteTime -Descending

    if ($reports.Count -eq 0) {
        Write-Output $output
        throw "Le runner n'a pas produit de rapport horodaté dans docs. Code PHP: $exitCode"
    }

    $report = Get-Content -LiteralPath $reports[0].FullName -Raw | ConvertFrom-Json
    [pscustomobject]@{
        Report = $reports[0].FullName
        ExitCode = $exitCode
        CampaignStatus = $report.campaign_status
        BootstrapStatus = $report.bootstrap_status
        AssistantStatuses = (($report.tests.PSObject.Properties | ForEach-Object { "$($_.Name)=$($_.Value.status)" }) -join '; ')
    } | Format-List

    if ($exitCode -ne 0 -or $report.campaign_status -ne 'PASS') { exit 1 }
}
finally {
    Pop-Location
}
