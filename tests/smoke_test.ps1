<#
.SYNOPSIS
    DuitKemana API Smoke Tests — PowerShell-based test runner.
    Alternative to PHPUnit for PHP 8.0 environments where PHPUnit is blocked.

.DESCRIPTION
    Runs a suite of HTTP tests against the running local server and reports pass/fail.
    Usage:
        .\smoke_test.ps1
        .\smoke_test.ps1 -BaseUrl "http://localhost/budget/public"
        .\smoke_test.ps1 -Verbose

.PARAMETER BaseUrl
    Base URL of the application. Default: http://localhost/budget/public
#>

param(
    [string] $BaseUrl = "http://localhost/budget/public"
)

$ErrorActionPreference = "Continue"
$passed = 0
$failed = 0
$results = @()

function Assert-Response {
    param(
        [string]       $TestName,
        [string]       $Method     = "GET",
        [string]       $Path,
        [hashtable]    $Body       = @{},
        [hashtable]    $Headers    = @{},
        [int]          $ExpectCode = 200,
        [string]       $ExpectBody = $null,
        [string]       $NotBody    = $null
    )

    $url = "$BaseUrl$Path"
    try {
        $splat = @{ Uri = $url; Method = $Method; UseBasicParsing = $true; ErrorAction = "Stop" }

        if ($Headers.Count -gt 0) { $splat["Headers"] = $Headers }

        if ($Method -in @("POST","PUT","PATCH") -and $Body.Count -gt 0) {
            $bodyJson = $Body | ConvertTo-Json -Compress
            $splat["Body"]        = $bodyJson
            $splat["ContentType"] = "application/json"
        }

        $resp     = Invoke-WebRequest @splat
        $code     = [int] $resp.StatusCode
        $bodyText = [string] $resp.Content
    }
    catch {
        if ($null -ne $_.Exception.Response) {
            $code = [int] $_.Exception.Response.StatusCode
        } else {
            $code = 0
        }
        $bodyText = [string] $_.Exception.Message
    }

    $ok = $code -eq $ExpectCode
    if ($ExpectBody -and $ok) { $ok = $bodyText -match [regex]::Escape($ExpectBody) }
    if ($NotBody   -and $ok) { $ok = -not ($bodyText -match [regex]::Escape($NotBody)) }

    if ($ok) {
        $script:passed++
        $status = "PASS"
        $color  = "Green"
    } else {
        $script:failed++
        $status = "FAIL"
        $color  = "Red"
    }

    $script:results += [PSCustomObject]@{
        Status   = $status
        Test     = $TestName
        URL      = $url
        Got      = $code
        Expected = $ExpectCode
    }

    Write-Host "  [$status] $TestName  (HTTP $code)" -ForegroundColor $color
    if ($status -eq "FAIL") {
        Write-Host "         → Expected HTTP $ExpectCode · Body snippet: $(($bodyText)[0..200] -join '')" -ForegroundColor DarkRed
    }
}

# ──────────────────────────────────────────────────────────────────
#   Setup — obtain auth token
# ──────────────────────────────────────────────────────────────────
Write-Host "`n=== DuitKemana Smoke Tests ===" -ForegroundColor Cyan
Write-Host "  Target: $BaseUrl" -ForegroundColor Cyan
Write-Host ""

# Register a temp test user (may fail if already exists — that's OK)
$testEmail    = "smoketest_$(Get-Date -Format 'yyMMddHHmm')@test.local"
$testPassword = "SmokeTest123!"

try {
    $regBody = @{ name = "Smoke Test"; email = $testEmail; password = $testPassword; password_confirmation = $testPassword } | ConvertTo-Json -Compress
    $regResp = Invoke-WebRequest -Uri "$BaseUrl/api/v1/auth/register" -Method POST -Body $regBody -ContentType "application/json" -UseBasicParsing -ErrorAction Stop
    $regData = $regResp.Content | ConvertFrom-Json
        # PS5.1 fallback for null-coalescing
        if (-not $token) { $token = $regData.data.access_token }
        if (-not $token) { $token = "" }
} catch {
    $token = ""
}

# Login if registration returned no token
if (-not $token) {
    try {
        $loginBody = @{ email = $testEmail; password = $testPassword } | ConvertTo-Json -Compress
        $loginResp = Invoke-WebRequest -Uri "$BaseUrl/api/v1/auth/login" -Method POST -Body $loginBody -ContentType "application/json" -UseBasicParsing -ErrorAction Stop
        $loginData = $loginResp.Content | ConvertFrom-Json
        $token     = $loginData.data.token
        if (-not $token) { $token = $loginData.data.access_token }
        if (-not $token) { $token = "" }
    } catch {
        $token = ""
    }
}

$authHeaders = @{ Authorization = "Bearer $token" }

# ──────────────────────────────────────────────────────────────────
#   Web page checks (HTML)
# ──────────────────────────────────────────────────────────────────
Write-Host "--- Web Pages ---"
Assert-Response -TestName "Login page"       -Path "/login"
Assert-Response -TestName "Register page"    -Path "/register"

# ──────────────────────────────────────────────────────────────────
#   API Auth
# ──────────────────────────────────────────────────────────────────
Write-Host "`n--- API Auth ---"
Assert-Response -TestName "Login valid"        -Method POST -Path "/api/v1/auth/login"    -Body @{email=$testEmail; password=$testPassword} -ExpectCode 200 -ExpectBody '"success":true'
Assert-Response -TestName "Login bad creds"    -Method POST -Path "/api/v1/auth/login"    -Body @{email=$testEmail; password="WRONGPASS"}     -ExpectCode 401
Assert-Response -TestName "Register duplicate" -Method POST -Path "/api/v1/auth/register" -Body @{name="X";email=$testEmail;password=$testPassword;password_confirmation=$testPassword} -ExpectCode 409

# ──────────────────────────────────────────────────────────────────
#   API Authenticated
# ──────────────────────────────────────────────────────────────────
Write-Host "`n--- API Authenticated Endpoints ---"
Assert-Response -TestName "GET profile"       -Path "/api/v1/profile/me"      -Headers $authHeaders -ExpectCode 200 -ExpectBody '"success":true'
Assert-Response -TestName "GET transactions"  -Path "/api/v1/transactions"    -Headers $authHeaders -ExpectCode 200
Assert-Response -TestName "GET categories"    -Path "/api/v1/categories"      -Headers $authHeaders -ExpectCode 200
Assert-Response -TestName "GET budget"        -Path "/api/v1/budget"          -Headers $authHeaders -ExpectCode 200
Assert-Response -TestName "GET reports"       -Path "/api/v1/reports/summary" -Headers $authHeaders -ExpectCode 200
Assert-Response -TestName "GET pay-methods"   -Path "/api/v1/payment-methods" -Headers $authHeaders -ExpectCode 200

# ──────────────────────────────────────────────────────────────────
#   API Unauthenticated (expect 401)
# ──────────────────────────────────────────────────────────────────
Write-Host "`n--- API Unauthenticated (expect 401) ---"
Assert-Response -TestName "GET transactions unauth"  -Path "/api/v1/transactions"    -ExpectCode 401
Assert-Response -TestName "GET profile unauth"       -Path "/api/v1/profile/me"      -ExpectCode 401

# ──────────────────────────────────────────────────────────────────
#   Rate limit header presence
# ──────────────────────────────────────────────────────────────────
Write-Host "`n--- Rate Limit Headers ---"
try {
    $rlResp = Invoke-WebRequest -Uri "$BaseUrl/api/v1/profile/me" -Headers $authHeaders -UseBasicParsing -ErrorAction Stop
    $hasRateHeaders = $rlResp.Headers.ContainsKey("X-RateLimit-Limit")
    if ($hasRateHeaders) {
        Write-Host "  [PASS] X-RateLimit-* headers present" -ForegroundColor Green; $script:passed++
    } else {
        Write-Host "  [FAIL] X-RateLimit-* headers missing" -ForegroundColor Red;   $script:failed++
    }
} catch {
    Write-Host "  [SKIP] Could not check rate-limit headers" -ForegroundColor Yellow
}

# ──────────────────────────────────────────────────────────────────
#   Health check
# ──────────────────────────────────────────────────────────────────
Write-Host "`n--- Health Check ---"
Assert-Response -TestName "Recurring health endpoint" -Path "/health/recurring" -ExpectCode 200

# ──────────────────────────────────────────────────────────────────
#   Results
# ──────────────────────────────────────────────────────────────────
$total = $passed + $failed
Write-Host "`n=====================================" -ForegroundColor Cyan
Write-Host "  Results : $passed / $total passed"    -ForegroundColor $(if ($failed -eq 0) {"Green"} else {"Yellow"})
if ($failed -gt 0) {
    Write-Host "  FAILURES: $failed"                -ForegroundColor Red
    $results | Where-Object { $_.Status -eq "FAIL" } | Format-Table -AutoSize
}
Write-Host "=====================================" -ForegroundColor Cyan

if ($failed -gt 0) { exit 1 } else { exit 0 }
