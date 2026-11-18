$ErrorActionPreference = "Stop"

$ContainerName = "praktikum-interoperabilitas-laravel"
$ServerVersion = & docker info --format "{{.ServerVersion}}" 2>$null

if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace(($ServerVersion -join ""))) {
    Write-Host "Docker Engine belum berjalan. Buka Docker Desktop terlebih dahulu." -ForegroundColor Yellow
    exit 1
}

$ExistingContainer = & docker ps -a --filter "name=^/$ContainerName$" --format "{{.Names}}"
if ($LASTEXITCODE -ne 0) {
    throw "Tidak dapat memeriksa container Docker."
}

if ($ExistingContainer -contains $ContainerName) {
    & docker rm --force $ContainerName
    if ($LASTEXITCODE -ne 0) {
        throw "Container Laravel gagal dihentikan."
    }

    Write-Host "Container Laravel berhasil dihentikan." -ForegroundColor Green
    Write-Host "File project, .env, dan database lokal tetap tersimpan."
}
else {
    Write-Host "Container Laravel tidak sedang berjalan."
}
