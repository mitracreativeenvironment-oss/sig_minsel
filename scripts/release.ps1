<#
.SYNOPSIS
    Sistem Otomasi Versi, Tag, dan Release untuk SIG Minahasa Selatan.
.DESCRIPTION
    Skrip ini melakukan bumping versi SemVer (patch/minor/major/custom),
    memperbarui VERSION, package.json, CHANGELOG.md, membuat commit release,
    membuat git tag terannotasi, dan dapat langsung melakukan push ke remote GitHub.
.PARAMETER Type
    Tipe kenaikan versi: 'patch', 'minor', 'major', atau 'custom'.
.PARAMETER Version
    Versi spesifik jika -Type custom (contoh: 1.2.3).
.PARAMETER Message
    Pesan catatan rilis singkat.
.PARAMETER Push
    Kirim commit dan tag ke GitHub secara otomatis.
.PARAMETER DryRun
    Simulasi tanpa mengubah file atau git commit.
.EXAMPLE
    .\scripts\release.ps1 -Type patch -Push
    .\scripts\release.ps1 -Type minor -Message "Fitur layer batas desa" -Push
    .\scripts\release.ps1 -Type custom -Version 1.0.0 -Push
#>
[CmdletBinding()]
param(
    [Parameter(Position = 0)]
    [ValidateSet('patch', 'minor', 'major', 'custom')]
    [string]$Type = 'patch',

    [Parameter(Position = 1)]
    [string]$Version = '',

    [Parameter()]
    [string]$Message = '',

    [Parameter()]
    [switch]$Push,

    [Parameter()]
    [switch]$DryRun,

    [Parameter()]
    [switch]$AllowDirty
)

$ErrorActionPreference = 'Stop'

Write-Host "`n=======================================================" -ForegroundColor Cyan
Write-Host "       SIG MINAHASA SELATAN - RELEASE MANAGER" -ForegroundColor Cyan
Write-Host "=======================================================`n" -ForegroundColor Cyan

# 1. Pastikan repo Git
if (-not (Test-Path ".git")) {
    Write-Error "Direktori ini belum diinisialisasi sebagai repositori git!"
}

# 2. Cek status working tree
$status = git status --porcelain
if ($status -and -not $AllowDirty -and -not $DryRun) {
    Write-Host "Peringatan: Ada perubahan yang belum di-commit di working directory:" -ForegroundColor Yellow
    git status -s
    $confirm = Read-Host "Lanjutkan proses release dan sertakan perubahan ini? (y/N)"
    if ($confirm -ne 'y' -and $confirm -ne 'Y') {
        Write-Host "Release dibatalkan." -ForegroundColor Yellow
        exit 0
    }
}

# 3. Baca versi saat ini dari file VERSION
$versionFilePath = Join-Path $PSScriptRoot "..\VERSION"
if (-not (Test-Path $versionFilePath)) {
    Write-Host "File VERSION tidak ditemukan, membuat versi awal 0.1.0..." -ForegroundColor Yellow
    "0.1.0" | Out-File -FilePath $versionFilePath -Encoding utf8 -NoNewline
}

$currentVersion = (Get-Content $versionFilePath -Raw).Trim()
Write-Host "Versi saat ini : $currentVersion" -ForegroundColor Gray

# 4. Hitung versi berikutnya
if ($Type -eq 'custom') {
    if (-not $Version) {
        $Version = Read-Host "Masukkan versi baru (SemVer misal 1.0.0)"
    }
    if ($Version -notmatch '^\d+\.\d+\.\d+(-[0-9A-Za-z.-]+)?$') {
        Write-Error "Format versi '$Version' tidak valid. Gunakan format SemVer (contoh: 1.0.0 atau 0.2.1)."
    }
    $nextVersion = $Version
} else {
    if ($currentVersion -match '^(\d+)\.(\d+)\.(\d+)(.*)$') {
        $major = [int]$Matches[1]
        $minor = [int]$Matches[2]
        $patch = [int]$Matches[3]
        $extra = $Matches[4]

        switch ($Type) {
            'patch' { $patch++; break }
            'minor' { $minor++; $patch = 0; break }
            'major' { $major++; $minor = 0; $patch = 0; break }
        }
        $nextVersion = "$major.$minor.$patch"
    } else {
        Write-Error "Versi saat ini '$currentVersion' bukan format SemVer valid (X.Y.Z)."
    }
}

$tagName = "v$nextVersion"
$releaseDate = (Get-Date).ToString("yyyy-MM-dd")

Write-Host "Kenaikan versi : [$Type]" -ForegroundColor Green
Write-Host "Versi baru     : $nextVersion" -ForegroundColor Green
Write-Host "Tag Git        : $tagName" -ForegroundColor Green
Write-Host "Tanggal Rilis  : $releaseDate" -ForegroundColor Gray

if ($DryRun) {
    Write-Host "`n[DRY RUN] Tidak ada berkas yang dimodifikasi dan tidak ada commit/tag yang dibuat." -ForegroundColor Yellow
    exit 0
}

# 5. Update file VERSION
Set-Content -Path $versionFilePath -Value $nextVersion -NoNewline
Write-Host " -> Berkas VERSION diperbarui: $nextVersion" -ForegroundColor Green

# 6. Update package.json jika ada
$packageJsonPath = Join-Path $PSScriptRoot "..\package.json"
if (Test-Path $packageJsonPath) {
    try {
        $json = Get-Content $packageJsonPath -Raw | ConvertFrom-Json
        $json.version = $nextVersion
        $json | ConvertTo-Json -Depth 10 | Set-Content $packageJsonPath -Encoding utf8
        Write-Host " -> package.json diperbarui: $nextVersion" -ForegroundColor Green
    } catch {
        Write-Host " Peringatan: Gagal memperbarui package.json: $_" -ForegroundColor Yellow
    }
}

# 7. Update CHANGELOG.md jika ada
$changelogPath = Join-Path $PSScriptRoot "..\CHANGELOG.md"
if (Test-Path $changelogPath) {
    $changelogContent = Get-Content $changelogPath -Raw
    $noteDesc = if ($Message) { "- $Message" } else { "- Rilis versi $nextVersion." }
    $newEntry = @"
## [Unreleased]

---

## [$nextVersion] - $releaseDate

### Added
$noteDesc
"@

    if ($changelogContent -match '## \[Unreleased\]') {
        $changelogContent = $changelogContent -replace '## \[Unreleased\]', $newEntry
        Set-Content -Path $changelogPath -Value $changelogContent -Encoding utf8
        Write-Host " -> CHANGELOG.md diperbarui dengan seksi [$nextVersion]" -ForegroundColor Green
    }
}

# 8. Git Commit & Tag
$commitMsg = if ($Message) { "chore(release): $tagName - $Message" } else { "chore(release): $tagName" }
$tagMsg = if ($Message) { "Release $tagName: $Message" } else { "Release $tagName" }

Write-Host "`nMembuat commit dan tag di Git..." -ForegroundColor Cyan
git add -A
git commit -m $commitMsg
git tag -a $tagName -m $tagMsg

Write-Host " Commit berhasil : $commitMsg" -ForegroundColor Green
Write-Host " Tag berhasil    : $tagName" -ForegroundColor Green

# 9. Push ke GitHub jika diminta
$currentBranch = (git branch --show-current).Trim()
if (-not $currentBranch) { $currentBranch = "main" }

if ($Push) {
    Write-Host "`nMengirim (push) commit dan tag ke origin ($currentBranch)..." -ForegroundColor Cyan
    git push origin $currentBranch
    git push origin $tagName
    Write-Host "`n Berhasil di-push ke GitHub!" -ForegroundColor Green
} else {
    Write-Host "`nCatatan: Perubahan belum di-push ke remote. Jalankan perintah berikut untuk push manual:" -ForegroundColor Yellow
    Write-Host "  git push origin $currentBranch" -ForegroundColor White
    Write-Host "  git push origin $tagName" -ForegroundColor White
    Write-Host "Atau gunakan opsi -Push pada skrip ini: .\scripts\release.ps1 -Type $Type -Push`n" -ForegroundColor Gray
}

Write-Host "Proses release $tagName selesai dengan sukses!`n" -ForegroundColor Green
