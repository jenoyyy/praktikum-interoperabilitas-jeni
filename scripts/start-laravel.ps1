$ErrorActionPreference = "Stop"

$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
$ImageName = "praktikum-interoperabilitas-laravel:local"
$ContainerName = "praktikum-interoperabilitas-laravel"

Write-Host "Memeriksa Docker Engine..."
$ServerVersion = & docker info --format "{{.ServerVersion}}" 2>$null

if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace(($ServerVersion -join ""))) {
    Write-Host "Docker Engine belum berjalan. Buka Docker Desktop, tunggu sampai Engine berstatus Running, lalu jalankan script ini lagi." -ForegroundColor Yellow
    exit 1
}

Write-Host "Docker Engine aktif: $ServerVersion"
Write-Host "Membangun image PHP 8.4 dan Composer..."

$ExistingContainer = & docker ps -a --filter "name=^/$ContainerName$" --format "{{.Names}}"
if ($LASTEXITCODE -ne 0) {
    throw "Tidak dapat memeriksa container Docker."
}

if ($ExistingContainer -contains $ContainerName) {
    & docker rm --force $ContainerName | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw "Container lama tidak dapat dihentikan."
    }
}

& docker build --file (Join-Path $ProjectRoot "Dockerfile.laravel") --tag $ImageName $ProjectRoot
if ($LASTEXITCODE -ne 0) {
    throw "Pembuatan image Laravel gagal. Periksa pesan Docker di atas."
}

$Mount = "type=bind,source=" + $ProjectRoot + ",target=/var/www/html"

Write-Host "Menyalakan Laravel dan menyiapkan database SQLite..."
& docker run --detach --name $ContainerName --publish "8000:8000" --mount $Mount --workdir "/var/www/html" --env "APP_ENV=local" --env "APP_DEBUG=true" --env "APP_URL=http://127.0.0.1:8000" --env "DB_CONNECTION=sqlite" --env "DB_DATABASE=/var/www/html/database/database.sqlite" --env "CACHE_STORE=file" --env "SESSION_DRIVER=file" --env "QUEUE_CONNECTION=sync" $ImageName sh /var/www/html/docker/laravel-entrypoint.sh

if ($LASTEXITCODE -ne 0) {
    throw "Container Laravel gagal dijalankan."
}

$Ready = $false
for ($Attempt = 1; $Attempt -le 60; $Attempt++) {
    $State = & docker inspect --format "{{.State.Status}}" $ContainerName 2>$null
    if ($LASTEXITCODE -ne 0 -or $State -ne "running") {
        break
    }

    try {
        $Response = Invoke-WebRequest -Uri "http://127.0.0.1:8000/up" -UseBasicParsing -TimeoutSec 3
        if ($Response.StatusCode -eq 200) {
            $Ready = $true
            break
        }
    }
    catch {
        Start-Sleep -Seconds 2
    }
}

if (-not $Ready) {
    Write-Host "Laravel belum siap. Log container:" -ForegroundColor Yellow
    & docker logs $ContainerName
    throw "Container masih ada agar log dapat diperiksa. Jangan hapus file project."
}

Write-Host ""
Write-Host "Laravel berhasil berjalan!" -ForegroundColor Green
Write-Host "API: http://127.0.0.1:8000/api"
Write-Host "Health check: http://127.0.0.1:8000/up"
Write-Host "Dokumentasi: http://127.0.0.1:8000/docs/api"
Write-Host "Insomnia base URL: http://127.0.0.1:8000/api"
Write-Host ""
Write-Host "Untuk melihat log: docker logs -f $ContainerName"
Write-Host "Untuk menghentikan: powershell -ExecutionPolicy Bypass -File .\scripts\stop-laravel.ps1"
