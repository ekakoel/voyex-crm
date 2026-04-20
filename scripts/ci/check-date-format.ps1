$ErrorActionPreference = "Stop"

$root = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
Set-Location $root

if (-not (Get-Command rg -ErrorAction SilentlyContinue)) {
    Write-Error "ripgrep (rg) is required for date format guard."
}

Write-Host "Running date format guard..."

$failed = $false

function Run-Check {
    param(
        [string]$Label,
        [string]$Pattern,
        [string[]]$Paths
    )

    $args = @(
        "-n",
        "-S",
        "--glob", "!vendor/**",
        "--glob", "!node_modules/**",
        "--glob", "!storage/**",
        "--glob", "!public/build/**",
        "--",
        $Pattern
    ) + $Paths

    $result = & rg @args
    if ($LASTEXITCODE -eq 0) {
        Write-Host $result
        Write-Host ""
        Write-Host "ERROR: $Label"
        Write-Host ""
        $script:failed = $true
    } elseif ($LASTEXITCODE -gt 1) {
        throw "ripgrep execution failed for pattern: $Pattern"
    }
}

Run-Check `
    -Label "Use absolute format (YYYY-MM-DD / YYYY-MM-DD (HH:ii)), do not use diffForHumans()." `
    -Pattern "diffForHumans\s*\(" `
    -Paths @("resources/views", "app/Http/Controllers")

Run-Check `
    -Label "Found non-standard PHP date format (example: d M Y, M Y, l, j F Y)." `
    -Pattern "->format\('(?:d\s*M(?:,\s*Y|\s*Y)?|M\s*Y|l,\s*j\s*F\s*Y)'\)" `
    -Paths @("resources/views", "resources/views/pdf", "app/Http/Controllers")

Run-Check `
    -Label "Found locale-dependent JS datetime rendering with hardcoded locale (toLocaleString / toLocaleDateString)." `
    -Pattern '(toLocaleString\s*\(\s*[''"]|toLocaleDateString\s*\(\s*[''"])' `
    -Paths @("resources/views")

Run-Check `
    -Label "Found Intl.DateTimeFormat(undefined, ...) for datetime rendering. Use en-CA parts mapping to YYYY-MM-DD (HH:ii)." `
    -Pattern "Intl\.DateTimeFormat\(undefined\s*," `
    -Paths @("resources/views")

if ($failed) {
    Write-Error "Date format guard FAILED."
}

Write-Host "Date format guard PASSED."
