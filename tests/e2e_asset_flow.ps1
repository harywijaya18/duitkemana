$ErrorActionPreference='Stop'
$base='http://localhost/budget'
$mysql='C:/xampp/mysql/bin/mysql.exe'

function QueryScalar([string]$sql){
  $raw = & $mysql -u root -N -B duitkemana -e $sql
  if ($null -eq $raw) { return '' }
  return ($raw | Out-String).Trim()
}

function GetCsrf([string]$h, [string]$source){
  $m=[regex]::Match($h,'name="csrf_token"[^>]*value="([^"]+)"')
  if(!$m.Success){ throw ("csrf missing at " + $source) }
  $m.Groups[1].Value
}

try {
  & $mysql -u root duitkemana --execute="source database/seed_demo.sql" | Out-Null
  & $mysql -u root duitkemana --execute="source database/migrate_assets.sql" | Out-Null
  & $mysql -u root duitkemana --execute="source database/migrate_asset_advanced.sql" | Out-Null
  & $mysql -u root duitkemana -e 'UPDATE users SET password=''$2y$10$6BFQuL.sQaGrd.kWA8njT.r84VINdlMOrarEMvzSBnPJVIvk0S5.C'', status=''active'' WHERE email=''demo@duitkemana.com'';' | Out-Null

  $uid=QueryScalar "SELECT id FROM users WHERE email='demo@duitkemana.com' LIMIT 1;"
  if([string]::IsNullOrWhiteSpace($uid)){ throw 'demo user missing' }

  $s=New-Object Microsoft.PowerShell.Commands.WebRequestSession
  $lp=Invoke-WebRequest -UseBasicParsing -Uri "$base/login" -WebSession $s
  $c=GetCsrf $lp.Content 'login'
  $lr=Invoke-WebRequest -UseBasicParsing -Uri "$base/login" -Method POST -WebSession $s -Body @{csrf_token=$c; email='demo@duitkemana.com'; password='secret123'} -MaximumRedirection 0 -ErrorAction SilentlyContinue
  if(!($lr.StatusCode -in 302,303)){ throw 'login fail' }

  $ap=Invoke-WebRequest -UseBasicParsing -Uri "$base/assets/add" -WebSession $s
  $ac=GetCsrf $ap.Content 'assets_add'
  $n='E2E Asset '+[DateTime]::Now.ToString('yyyyMMddHHmmss')
  $d=(Get-Date).ToString('yyyy-MM-dd')
  $cr=Invoke-WebRequest -UseBasicParsing -Uri "$base/assets/store" -Method POST -WebSession $s -Body @{csrf_token=$ac; name=$n; type='cash'; quantity='1'; unit_price='10000000'; total_value='10000000'; valuation_date=$d; notes='e2e'} -MaximumRedirection 0 -ErrorAction SilentlyContinue
  if(!($cr.StatusCode -in 302,303)){ throw 'asset create fail' }
  $aid=QueryScalar "SELECT id FROM assets WHERE user_id=$uid AND name='$n' ORDER BY id DESC LIMIT 1;"
  if([string]::IsNullOrWhiteSpace($aid)){ throw 'asset id missing' }

  $det=Invoke-WebRequest -UseBasicParsing -Uri "$base/assets/detail?id=$aid" -WebSession $s
  if($det.Content -notmatch 'Asset Transaction History'){ throw 'asset detail missing history' }
  $dc=GetCsrf $det.Content 'asset_detail_tx'

  $tx=Invoke-WebRequest -UseBasicParsing -Uri "$base/assets/transactions/store" -Method POST -WebSession $s -Body @{csrf_token=$dc; asset_id=$aid; transaction_type='top_up'; quantity='1'; unit_price='2000000'; total_amount='2000000'; transaction_date=$d; notes='tx'} -MaximumRedirection 0 -ErrorAction SilentlyContinue
  if(!($tx.StatusCode -in 302,303)){ throw 'asset transaction fail' }
  $txc=QueryScalar "SELECT COUNT(*) FROM asset_transactions WHERE asset_id=$aid;"
  if([int]$txc -lt 1){ throw 'asset transaction row missing' }

  $det2=Invoke-WebRequest -UseBasicParsing -Uri "$base/assets/detail?id=$aid" -WebSession $s
  $vc=GetCsrf $det2.Content 'asset_detail_val'
  $val=Invoke-WebRequest -UseBasicParsing -Uri "$base/assets/valuations/store" -Method POST -WebSession $s -Body @{csrf_token=$vc; asset_id=$aid; valuation_date=$d; total_value='13000000'; notes='val'} -MaximumRedirection 0 -ErrorAction SilentlyContinue
  if(!($val.StatusCode -in 302,303)){ throw 'asset valuation fail' }
  $valc=QueryScalar "SELECT COUNT(*) FROM asset_valuations WHERE asset_id=$aid;"
  if([int]$valc -lt 1){ throw 'asset valuation row missing' }

  $lp2=Invoke-WebRequest -UseBasicParsing -Uri "$base/liabilities" -WebSession $s
  $lc=GetCsrf $lp2.Content 'liabilities_add'
  $ln='E2E Liability '+[DateTime]::Now.ToString('yyyyMMddHHmmss')
  $la=Invoke-WebRequest -UseBasicParsing -Uri "$base/liabilities/store" -Method POST -WebSession $s -Body @{csrf_token=$lc; name=$ln; type='loan'; principal_amount='5000000'; outstanding_amount='3000000'; due_date=(Get-Date).AddMonths(6).ToString('yyyy-MM-dd'); notes='liab'} -MaximumRedirection 0 -ErrorAction SilentlyContinue
  if(!($la.StatusCode -in 302,303)){ throw 'liability add fail' }
  $lid=QueryScalar "SELECT id FROM liabilities WHERE user_id=$uid AND name='$ln' ORDER BY id DESC LIMIT 1;"
  if([string]::IsNullOrWhiteSpace($lid)){ throw 'liability id missing' }

  $lr2=Invoke-WebRequest -UseBasicParsing -Uri "$base/liabilities" -WebSession $s
  $lc2=GetCsrf $lr2.Content 'liabilities_update'
  $lu=Invoke-WebRequest -UseBasicParsing -Uri "$base/liabilities/update" -Method POST -WebSession $s -Body @{csrf_token=$lc2; id=$lid; name=$ln; type='loan'; principal_amount='5000000'; outstanding_amount='2500000'; due_date=(Get-Date).AddMonths(6).ToString('yyyy-MM-dd'); notes='upd'} -MaximumRedirection 0 -ErrorAction SilentlyContinue
  if(!($lu.StatusCode -in 302,303)){ throw 'liability update fail' }

  $lr3=Invoke-WebRequest -UseBasicParsing -Uri "$base/liabilities" -WebSession $s
  $lc3=GetCsrf $lr3.Content 'liabilities_delete'
  $ld=Invoke-WebRequest -UseBasicParsing -Uri "$base/liabilities/delete" -Method POST -WebSession $s -Body @{csrf_token=$lc3; id=$lid} -MaximumRedirection 0 -ErrorAction SilentlyContinue
  if(!($ld.StatusCode -in 302,303)){ throw 'liability delete fail' }
  $active=QueryScalar "SELECT is_active FROM liabilities WHERE id=$lid LIMIT 1;"
  if($active -ne '0'){ throw 'liability soft delete fail' }

  $dash=Invoke-WebRequest -UseBasicParsing -Uri "$base/" -WebSession $s
  if($dash.Content -notmatch 'Net Worth Snapshot'){ throw 'dashboard net worth missing' }
  $proj=Invoke-WebRequest -UseBasicParsing -Uri "$base/projection" -WebSession $s
  if($proj.Content -notmatch 'Net Worth Snapshot'){ throw 'projection snapshot missing' }
  if($proj.Content -notmatch 'Net Worth'){ throw 'projection net worth column missing' }

  Write-Host "PASS|asset_id=$aid|tx_count=$txc|val_count=$valc|liability_id=$lid"
}
catch {
  Write-Host ("FAIL|" + $_.Exception.Message)
  Write-Host ("FAIL_TYPE|" + $_.Exception.GetType().FullName)
  if ($_.InvocationInfo -and $_.InvocationInfo.PositionMessage) {
    Write-Host ("FAIL_POS|" + $_.InvocationInfo.PositionMessage)
  }
  exit 1
}
