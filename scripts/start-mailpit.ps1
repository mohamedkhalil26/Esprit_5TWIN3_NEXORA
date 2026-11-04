$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$mailpitDirectory = Join-Path $projectRoot 'storage/app/private/recette-tools/mailpit'
$mailpitExecutable = Join-Path $mailpitDirectory 'mailpit.exe'

try {
    $info = Invoke-RestMethod 'http://127.0.0.1:8025/api/v1/info' -TimeoutSec 2
    if ($info.Version) {
        Write-Output 'Mailpit est déjà disponible : http://127.0.0.1:8025'
        return
    }
} catch {
    # Mailpit doit être démarré après chaque redémarrage de Windows.
}

if (-not (Test-Path -LiteralPath $mailpitExecutable)) {
    throw "Mailpit est absent du dossier local storage/app/private/recette-tools/mailpit."
}

$databasePath = Join-Path $mailpitDirectory 'messages.sqlite'
$arguments = '--listen 127.0.0.1:8025 --smtp 127.0.0.1:1025 --database "' + $databasePath + '"'
$process = Start-Process -FilePath $mailpitExecutable -ArgumentList $arguments -WindowStyle Hidden -PassThru
for ($attempt = 0; $attempt -lt 20; $attempt++) {
    Start-Sleep -Milliseconds 250
    if ($process.HasExited) {
        throw 'Mailpit ne démarre pas. Vérifiez que les ports 1025 et 8025 sont disponibles.'
    }
    try {
        $info = Invoke-RestMethod 'http://127.0.0.1:8025/api/v1/info' -TimeoutSec 1
        if ($info.Version) {
            Write-Output 'Mailpit démarré : http://127.0.0.1:8025 (SMTP : 127.0.0.1:1025)'
            return
        }
    } catch {
        # Attendre que l'interface soit prête.
    }
}
throw 'Mailpit ne répond pas dans le délai prévu.'
