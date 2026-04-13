# Script de Optimización de Imágenes
# Requiere ImageMagick: https://imagemagick.org/script/download.php

param(
    [string]$InputPath = "frontend\public\Assets",
    [int]$Quality = 85,
    [int]$MaxWidth = 1920
)

Write-Host "🖼️  Optimizando imágenes en: $InputPath" -ForegroundColor Cyan
Write-Host "Configuración: Quality=$Quality, MaxWidth=$MaxWidth" -ForegroundColor Gray

$images = Get-ChildItem -Path $InputPath -Include *.jpg,*.jpeg,*.png -Recurse
$count = $images.Count
$current = 0

foreach ($image in $images) {
    $current++
    $percent = [math]::Round(($current / $count) * 100)
    
    Write-Progress -Activity "Optimizando imágenes" -Status "$current de $count" -PercentComplete $percent
    
    $sizeBefore = $image.Length
    
    # Optimizar con ImageMagick
    & magick convert $image.FullName `
        -resize "${MaxWidth}x${MaxWidth}>" `
        -quality $Quality `
        -strip `
        -define jpeg:dct-method=float `
        $image.FullName
    
    $sizeAfter = (Get-Item $image.FullName).Length
    $saved = $sizeBefore - $sizeAfter
    $savedPercent = [math]::Round(($saved / $sizeBefore) * 100)
    
    if ($saved -gt 0) {
        Write-Host "✅ $($image.Name): $([math]::Round($saved/1KB)) KB ahorrados ($savedPercent%)" -ForegroundColor Green
    }
}

Write-Progress -Activity "Optimizando imágenes" -Completed
Write-Host "`n✨ Optimización completada!" -ForegroundColor Green
