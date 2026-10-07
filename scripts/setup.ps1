$ErrorActionPreference = 'Stop'
Set-Location (Split-Path $PSScriptRoot -Parent)
foreach ($tool in @('php', 'composer', 'node', 'npm')) {
    if (-not (Get-Command $tool -ErrorAction SilentlyContinue)) { throw "$tool belum tersedia di PATH. Baca README.md." }
}
function Invoke-Step([scriptblock]$Action) {
    & $Action
    if ($LASTEXITCODE -ne 0) { throw 'Langkah gagal. Perbaiki pesan di atas sebelum melanjutkan.' }
}
if (-not (Test-Path '.env')) {
    Copy-Item '.env.example' '.env'
    Write-Host '.env dibuat. Isi DB_DATABASE, DB_USERNAME, DB_PASSWORD. Pastikan database kosong sudah tersedia.'
    Write-Host 'Setelah selesai, jalankan skrip ini kembali.'
    exit 0
}
Invoke-Step { composer install --no-interaction --prefer-dist }
if (-not (Select-String -Path '.env' -Pattern '^APP_KEY=base64:.+' -Quiet)) {
    Invoke-Step { php artisan key:generate }
}
Invoke-Step { php artisan config:clear }
Invoke-Step { php artisan migrate }
Invoke-Step { php artisan db:seed }
Invoke-Step { npm ci }
Invoke-Step { npm run build }
Write-Host 'Setup selesai. Jalankan php artisan serve --host=127.0.0.1 --port=8000 --no-reload'
Write-Host 'Baca README.md untuk akun demo, worker, scheduler, dan ClamAV.'
