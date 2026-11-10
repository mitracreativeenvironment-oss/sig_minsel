<#
.SYNOPSIS
    Sistem Otomasi Versi, Tag, dan Release untuk SIG Minahasa Selatan.
.DESCRIPTION
    Skrip ini melakukan bumping versi SemVer, mengumpulkan daftar perubahan
    secara otomatis dari Git commit, memperbarui VERSION, package.json,
    CHANGELOG.md, membuat commit release, membuat tag dengan catatan perubahan lengkap,
    dan dapat langsung melakukan push ke remote GitHub.
.PARAMETER Type
    Tipe kenaikan versi: 'patch', 'minor', 'major', atau 'custom'.
.PARAMETER Version
    Versi spesifik jika -Type custom (contoh: 1.0.0).
.PARAMETER Message
    Ringkasan pesan / judul catatan perubahan.
.PARAMETER Push
    Kirim commit dan tag ke GitHub secara otomatis.
.PARAMETER DryRun
    Simulasi tanpa mengubah berkas atau commit git.
.EXAMPLE
    .\scripts\release.ps1 -Type patch -Push
    .\scripts\release.ps1 -Type minor -Message "Layer fasilitas kesehatan & sekolah" -Push
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
Write-Host "   SIG MINAHASA SELATAN - RELEASE & CHANGELOG MANAGER" -ForegroundColor Cyan
Write-Host "=======================================================`n" -ForegroundColor Cyan

# 1. Pastikan repo Git
if (-not (Test-Path ".git")) {
    Write-Error "Direktori ini bukan repositori Git!"
}

# 2. Cek status working tree
$status = git status --porcelain
if ($status -and -not $AllowDirty -and -not $DryRun) {
    Write-Host "Peringatan: Ada perubahan yang belum di-commit di working directory:" -ForegroundColor Yellow
    git status -s
    $confirm = Read-Host "Lanjutkan proses release dan sertakan perubahan ini ke dalam rilis? (y/N)"
    if ($confirm -ne 'y' -and $confirm -ne 'Y') {
        Write-Host "Release dibatalkan." -ForegroundColor Yellow
        exit 0
    }
}

# 3. Baca versi saat ini dari berkas VERSION
$versionFilePath = Join-Path $PSScriptRoot "..\VERSION"
if (-not (Test-Path $versionFilePath)) {
    "0.1.0" | Out-File -FilePath $versionFilePath -Encoding utf8 -NoNewline
}

$currentVersion = (Get-Content $versionFilePath -Raw).Trim()
Write-Host "Versi saat ini  : $currentVersion" -ForegroundColor Gray

# 4. Cari tag terakhir untuk mengumpulkan commit perubahan
$lastTag = $null
try {
    $lastTag = (git describe --tags --abbrev=0 2>$null).Trim()
} catch {}

$commitLogs = @()
if ($lastTag) {
    Write-Host "Tag sebelumnya  : $lastTag" -ForegroundColor Gray
    $rawLogs = git log "$lastTag..HEAD" --pretty=format:"%s (%h)" 2>$null
    if ($rawLogs) {
        $commitLogs = $rawLogs -split "`n" | Where-Object { $_ -notmatch '^chore\(release\):' }
    }
} else {
    $rawLogs = git log --pretty=format:"%s (%h)" -n 10 2>$null
    if ($rawLogs) {
        $commitLogs = $rawLogs -split "`n" | Where-Object { $_ -notmatch '^chore\(release\):' }
    }
}

# 5. Hitung versi berikutnya (SemVer)
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

        switch ($Type) {
            'patch' { $patch++; break }
            'minor' { $minor++; $patch = 0; break }
            'major' { $major++; $minor = 0; $patch = 0; break }
        }
        $nextVersion = "$major.$minor.$patch"
    } else {
        Write-Error "Versi saat ini '$currentVersion' bukan format SemVer valid."
    }
}

$tagName = "v$nextVersion"
$releaseDate = (Get-Date).ToString("yyyy-MM-dd")

Write-Host "Kenaikan versi  : [$Type]" -ForegroundColor Green
Write-Host "Versi baru      : $nextVersion" -ForegroundColor Green
Write-Host "Tag Git         : $tagName" -ForegroundColor Green
Write-Host "Tanggal Rilis   : $releaseDate" -ForegroundColor Gray

# 6. Susun Rincian Perubahan (Changelog Notes)
Write-Host "`n--- Rincian Perubahan ---" -ForegroundColor Cyan
$changeLines = @()

if ($Message) {
    $changeLines += "- $Message"
}

if ($commitLogs.Count -gt 0) {
    Write-Host "Daftar commit yang terdeteksi:" -ForegroundColor Gray
    foreach ($log in $commitLogs) {
        if ($log.Trim()) {
            Write-Host "  * $($log.Trim())" -ForegroundColor DarkGray
            $changeLines += "- $($log.Trim())"
        }
    }
}

if ($changeLines.Count -eq 0) {
    $manualNote = Read-Host "Masukkan keterangan perubahan untuk rilis $tagName (kosongkan untuk default)"
    if ($manualNote) {
        $changeLines += "- $manualNote"
    } else {
        $changeLines += "- Pembaruan dan penyempurnaan fitur WebGIS Minahasa Selatan versi $nextVersion."
    }
}

$formattedChanges = $changeLines -join "`n"

if ($DryRun) {
    Write-Host "`n[DRY RUN] Catatan perubahan yang akan disimpan:" -ForegroundColor Yellow
    Write-Host $formattedChanges -ForegroundColor White
    exit 0
}

# 7. Update file VERSION
Set-Content -Path $versionFilePath -Value $nextVersion -NoNewline
Write-Host " [OK] Berkas VERSION diperbarui: $nextVersion" -ForegroundColor Green

# 8. Update package.json jika ada
$packageJsonPath = Join-Path $PSScriptRoot "..\package.json"
if (Test-Path $packageJsonPath) {
    try {
        $json = Get-Content $packageJsonPath -Raw | ConvertFrom-Json
        $json.version = $nextVersion
        $json | ConvertTo-Json -Depth 10 | Set-Content $packageJsonPath -Encoding utf8
        Write-Host " [OK] package.json diperbarui: $nextVersion" -ForegroundColor Green
    } catch {
        Write-Host " [!] Gagal update package.json: $_" -ForegroundColor Yellow
    }
}

# 9. Update CHANGELOG.md
$changelogPath = Join-Path $PSScriptRoot "..\CHANGELOG.md"
if (Test-Path $changelogPath) {
    $changelogContent = Get-Content $changelogPath -Raw
    $newEntry = @"
## [Unreleased]

---

## [$nextVersion] - $releaseDate

### Keterangan Perubahan
$formattedChanges
"@

    if ($changelogContent -match '## \[Unreleased\]') {
        $changelogContent = $changelogContent -replace '## \[Unreleased\]', $newEntry
        Set-Content -Path $changelogPath -Value $changelogContent -Encoding utf8
        Write-Host " [OK] CHANGELOG.md diperbarui dengan detail perubahan rilis [$nextVersion]" -ForegroundColor Green
    }
}

# 10. Git Commit & Annotated Tag (dengan detail perubahan)
$commitMsg = if ($Message) { "chore(release): $tagName - $Message" } else { "chore(release): $tagName" }
$tagAnnotation = @"
Release $tagName ($releaseDate)

Keterangan Perubahan:
$formattedChanges
"@

Write-Host "`nMembuat commit dan tag di Git..." -ForegroundColor Cyan
git add -A
git commit -m $commitMsg
git tag -a $tagName -m $tagAnnotation

Write-Host " [OK] Commit berhasil : $commitMsg" -ForegroundColor Green
Write-Host " [OK] Tag berhasil    : $tagName dengan deskripsi perubahan lengkap" -ForegroundColor Green

# 11. Push ke GitHub
$currentBranch = (git branch --show-current).Trim()
if (-not $currentBranch) { $currentBranch = "main" }

if ($Push) {
    Write-Host "`nMengirim (push) commit dan tag ke GitHub ($currentBranch)..." -ForegroundColor Cyan
    git push origin $currentBranch
    git push origin $tagName
    Write-Host "`n [SELESAI] Tag dan rilis berhasil di-push ke GitHub!" -ForegroundColor Green
} else {
    Write-Host "`nCatatan: Perubahan belum di-push ke remote. Jalankan perintah berikut saat siap:" -ForegroundColor Yellow
    Write-Host "  git push origin $currentBranch" -ForegroundColor White
    Write-Host "  git push origin $tagName" -ForegroundColor White
    Write-Host "Atau gunakan flag -Push: .\scripts\release.ps1 -Type $Type -Push`n" -ForegroundColor Gray
}

Write-Host "=======================================================" -ForegroundColor Cyan
Write-Host " Rilis $tagName berhasil disiapkan beserta keterangannya!" -ForegroundColor Cyan
Write-Host "=======================================================`n" -ForegroundColor Cyan
