param(
    [string]$BaseUrl = 'http://127.0.0.1:8765',
    [Parameter(Mandatory = $true)][string]$Username,
    [Parameter(Mandatory = $true)][string]$Password,
    [int[]]$Widths = @(320, 360, 375, 390, 430)
)

$ErrorActionPreference = 'Stop'
$edgePath = 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
if (-not (Test-Path -LiteralPath $edgePath)) {
    throw "Microsoft Edge tidak ditemukan di $edgePath"
}

$port = Get-Random -Minimum 9300 -Maximum 9900
$tempRoot = [System.IO.Path]::GetFullPath([System.IO.Path]::GetTempPath())
$profilePath = Join-Path $tempRoot ('serviceplan-mobile-audit-' + [Guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $profilePath | Out-Null

$edgeProcess = $null
$socket = $null
$nextId = 0

function Receive-CdpMessage {
    param([System.Net.WebSockets.ClientWebSocket]$WebSocket)
    $buffer = New-Object byte[] 1048576
    $segment = [ArraySegment[byte]]::new($buffer)
    $stream = New-Object System.IO.MemoryStream
    do {
        $result = $WebSocket.ReceiveAsync($segment, [Threading.CancellationToken]::None).GetAwaiter().GetResult()
        if ($result.MessageType -eq [System.Net.WebSockets.WebSocketMessageType]::Close) {
            throw 'Koneksi DevTools ditutup sebelum audit selesai.'
        }
        $stream.Write($buffer, 0, $result.Count)
    } while (-not $result.EndOfMessage)
    $json = [Text.Encoding]::UTF8.GetString($stream.ToArray())
    $stream.Dispose()
    return ($json | ConvertFrom-Json)
}

function Invoke-Cdp {
    param(
        [string]$Method,
        [hashtable]$Params = @{}
    )
    $script:nextId++
    $requestId = $script:nextId
    $payload = @{ id = $requestId; method = $Method; params = $Params } | ConvertTo-Json -Depth 20 -Compress
    $bytes = [Text.Encoding]::UTF8.GetBytes($payload)
    $segment = [ArraySegment[byte]]::new($bytes)
    $null = $socket.SendAsync($segment, [System.Net.WebSockets.WebSocketMessageType]::Text, $true, [Threading.CancellationToken]::None).GetAwaiter().GetResult()
    do {
        $message = Receive-CdpMessage -WebSocket $socket
    } while ($message.id -ne $requestId)
    if ($message.error) { throw "$Method gagal: $($message.error.message)" }
    return $message.result
}

function Invoke-JavaScript {
    param([string]$Expression, [switch]$AwaitPromise)
    $params = @{
        expression = $Expression
        returnByValue = $true
        awaitPromise = [bool]$AwaitPromise
    }
    $result = Invoke-Cdp -Method 'Runtime.evaluate' -Params $params
    if ($result.exceptionDetails) { throw "JavaScript audit gagal: $($result.exceptionDetails.text)" }
    return $result.result.value
}

function Wait-JavaScript {
    param([string]$Expression, [int]$TimeoutSeconds = 20)
    $deadline = [DateTime]::UtcNow.AddSeconds($TimeoutSeconds)
    do {
        if (Invoke-JavaScript -Expression $Expression) { return }
        Start-Sleep -Milliseconds 150
    } while ([DateTime]::UtcNow -lt $deadline)
    throw "Timeout menunggu kondisi browser: $Expression"
}

$auditExpression = @'
(() => {
    const viewportWidth = document.documentElement.clientWidth;
    const activeView = document.querySelector('.view-section.active');
    const localScroller = '.responsive-scroll-region, .mobile-table-scroll, .table-responsive, .table-container, .logistics-data-table-wrap, .cm-kpi-table-wrap, .cm-table-wrap, .pm-table-wrap, .pk-table-wrap, .hse-table-wrap, .prod-table-wrap, .p2h-table-wrap, .sl-table-wrap, .kanban-board, .settings-sidebar-nav, .asset-tabs, .report-view-tabs, .logistics-ledger-tabs, .cm-tabs, .pm-tabs, .pk-nav-tabs, .hse-nav-tabs, .prod-nav-tabs, .p2h-nav-tabs';
    const isVisible = element => {
        const style = getComputedStyle(element);
        const rect = element.getBoundingClientRect();
        return style.display !== 'none' && style.visibility !== 'hidden' && Number(style.opacity) !== 0 && rect.width > 0 && rect.height > 0;
    };
    const selectorFor = element => {
        if (element.id) return '#' + element.id;
        const classes = Array.from(element.classList).slice(0, 3).join('.');
        return element.tagName.toLowerCase() + (classes ? '.' + classes : '');
    };
    const offenders = [];
    document.querySelectorAll('body *').forEach(element => {
        if (!isVisible(element)) return;
        if (element.closest('.sidebar') || element.classList.contains('mobile-nav-backdrop')) return;
        const scrollParent = element.closest(localScroller);
        if (scrollParent && scrollParent !== element) return;
        const rect = element.getBoundingClientRect();
        if (rect.right > viewportWidth + 1 || rect.left < -1 || rect.width > viewportWidth + 1) {
            offenders.push({ selector: selectorFor(element), left: Math.round(rect.left), right: Math.round(rect.right), width: Math.round(rect.width) });
        }
    });
    const content = document.querySelector('.content');
    const bodyOverflow = Math.max(document.documentElement.scrollWidth, document.body.scrollWidth) - viewportWidth;
    const contentOverflow = content ? content.scrollWidth - content.clientWidth : 0;
    const activeOverflow = activeView ? activeView.scrollWidth - activeView.clientWidth : 0;
    return {
        viewportWidth,
        view: activeView ? activeView.id : null,
        bodyOverflow,
        contentOverflow,
        activeOverflow,
        offenders: offenders.slice(0, 15)
    };
})()
'@

try {
    $edgeArguments = @(
        '--headless',
        '--disable-gpu',
        '--disable-software-rasterizer',
        '--disable-dev-shm-usage',
        '--no-sandbox',
        '--no-first-run',
        '--no-default-browser-check',
        "--remote-debugging-port=$port",
        "--user-data-dir=$profilePath",
        '--window-size=430,950',
        'about:blank'
    )
    $edgeProcess = Start-Process -FilePath $edgePath -ArgumentList $edgeArguments -WindowStyle Hidden -PassThru

    $versionUrl = "http://127.0.0.1:$port/json/version"
    $deadline = [DateTime]::UtcNow.AddSeconds(15)
    do {
        try { $null = Invoke-RestMethod -Uri $versionUrl -TimeoutSec 1; break } catch { Start-Sleep -Milliseconds 150 }
    } while ([DateTime]::UtcNow -lt $deadline)

    $targets = Invoke-RestMethod -Uri "http://127.0.0.1:$port/json/list" -TimeoutSec 3
    $target = $targets | Where-Object { $_.type -eq 'page' } | Select-Object -First 1
    if (-not $target) { throw 'Target halaman Edge DevTools tidak ditemukan.' }

    $socket = [System.Net.WebSockets.ClientWebSocket]::new()
    $null = $socket.ConnectAsync([Uri]$target.webSocketDebuggerUrl, [Threading.CancellationToken]::None).GetAwaiter().GetResult()
    $null = Invoke-Cdp -Method 'Page.enable'
    $null = Invoke-Cdp -Method 'Runtime.enable'

    $null = Invoke-Cdp -Method 'Page.navigate' -Params @{ url = ($BaseUrl.TrimEnd('/') + '/index.html') }
    Wait-JavaScript -Expression "document.readyState === 'complete'"

    $loginResults = New-Object System.Collections.Generic.List[object]
    foreach ($width in $Widths) {
        $null = Invoke-Cdp -Method 'Emulation.setDeviceMetricsOverride' -Params @{
            width = $width; height = 900; deviceScaleFactor = 1; mobile = $true; screenWidth = $width; screenHeight = 900
        }
        $loginResults.Add([pscustomobject]@{ width = $width; state = 'login'; audit = (Invoke-JavaScript -Expression $auditExpression) })
    }

    $usernameJson = $Username | ConvertTo-Json -Compress
    $passwordJson = $Password | ConvertTo-Json -Compress
    $loginExpression = @"
(async () => {
    const sessionResponse = await fetch('api/auth.php?action=session', { credentials: 'same-origin', cache: 'no-store' });
    const session = await sessionResponse.json();
    const response = await fetch('api/auth.php', {
        method: 'POST', credentials: 'same-origin', cache: 'no-store',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-Token': session.csrf_token },
        body: JSON.stringify({ action: 'login', username: $usernameJson, password: $passwordJson })
    });
    return { status: response.status, payload: await response.json() };
})()
"@
    $login = Invoke-JavaScript -Expression $loginExpression -AwaitPromise
    if ($login.status -ne 200 -or $login.payload.status -ne 'success') {
        throw "Login audit gagal: HTTP $($login.status) $($login.payload.message)"
    }

    $null = Invoke-Cdp -Method 'Page.navigate' -Params @{ url = ($BaseUrl.TrimEnd('/') + '/dashboard.php') }
    Wait-JavaScript -Expression "document.readyState === 'complete' && typeof window.showView === 'function' && document.querySelectorAll('.view-section').length >= 19" -TimeoutSeconds 30

    $views = @('dashboard','monitoring','asset','wo','pm','reports','people','hse','productivity','inspection','uc','logistics','condition','fuel','biaya','approval','settings','archive','unit-properties')
    $tabButtonSelector = '.asset-tabs button, .asset-tabs .asset-tab, .report-view-tabs button, .logistics-ledger-tabs button, .cm-tabs button, .pm-tabs button, .pk-nav-tabs button, .hse-nav-tabs button, .prod-nav-tabs button, .p2h-nav-tabs button, .settings-sidebar-nav .settings-nav-item'
    $results = New-Object System.Collections.Generic.List[object]

    foreach ($width in $Widths) {
        $null = Invoke-Cdp -Method 'Emulation.setDeviceMetricsOverride' -Params @{
            width = $width; height = 900; deviceScaleFactor = 1; mobile = $true; screenWidth = $width; screenHeight = 900
        }
        foreach ($view in $views) {
            $viewJson = $view | ConvertTo-Json -Compress
            $null = Invoke-JavaScript -Expression "window.showView($viewJson); true"
            Start-Sleep -Milliseconds 180
            $results.Add([pscustomobject]@{ width = $width; state = $view; audit = (Invoke-JavaScript -Expression $auditExpression) })

            $tabCount = [int](Invoke-JavaScript -Expression "(() => { const root=document.getElementById('view-' + $viewJson); return root ? root.querySelectorAll('$tabButtonSelector').length : 0; })()")
            for ($tabIndex = 0; $tabIndex -lt $tabCount; $tabIndex++) {
                $tabLabel = Invoke-JavaScript -Expression "(() => { const root=document.getElementById('view-' + $viewJson); const b=root ? root.querySelectorAll('$tabButtonSelector')[$tabIndex] : null; if(!b)return ''; b.click(); return (b.textContent||'tab').trim().replace(/\s+/g,' '); })()"
                Start-Sleep -Milliseconds 120
                $results.Add([pscustomobject]@{ width = $width; state = "$view/tab:$tabLabel"; audit = (Invoke-JavaScript -Expression $auditExpression) })
            }
        }
    }

    $modalResults = New-Object System.Collections.Generic.List[object]
    $modalCount = [int](Invoke-JavaScript -Expression "document.querySelectorAll('.modal-overlay, .hse-modal-overlay, .sl-modal-overlay, .guide-modal').length")
    foreach ($width in $Widths) {
        $null = Invoke-Cdp -Method 'Emulation.setDeviceMetricsOverride' -Params @{
            width = $width; height = 900; deviceScaleFactor = 1; mobile = $true; screenWidth = $width; screenHeight = 900
        }
        $null = Invoke-JavaScript -Expression "window.showView('dashboard'); true"
        for ($modalIndex = 0; $modalIndex -lt $modalCount; $modalIndex++) {
            $openModal = @"
(() => {
    const all = Array.from(document.querySelectorAll('.modal-overlay, .hse-modal-overlay, .sl-modal-overlay, .guide-modal'));
    all.forEach(el => { el.classList.remove('active'); el.style.removeProperty('display'); });
    const modal = all[$modalIndex];
    if (!modal) return '';
    modal.classList.add('active');
    modal.style.setProperty('display', 'flex', 'important');
    return modal.id || Array.from(modal.classList).join('.');
})()
"@
            $modalLabel = Invoke-JavaScript -Expression $openModal
            Start-Sleep -Milliseconds 80
            $modalResults.Add([pscustomobject]@{ width = $width; state = $modalLabel; audit = (Invoke-JavaScript -Expression $auditExpression) })
        }
    }
    $null = Invoke-JavaScript -Expression "document.querySelectorAll('.modal-overlay, .hse-modal-overlay, .sl-modal-overlay, .guide-modal').forEach(el => { el.classList.remove('active'); el.style.removeProperty('display'); }); true"

    $allPageResults = @($loginResults | ForEach-Object { $_ }) + @($results | ForEach-Object { $_ })
    $failures = @($allPageResults | Where-Object { $_.audit.bodyOverflow -gt 1 -or $_.audit.contentOverflow -gt 1 -or $_.audit.activeOverflow -gt 1 -or $_.audit.offenders.Count -gt 0 })
    $modalFailures = @($modalResults | Where-Object { $_.audit.bodyOverflow -gt 1 -or $_.audit.contentOverflow -gt 1 -or $_.audit.offenders.Count -gt 0 })
    [pscustomobject]@{
        summary = [pscustomobject]@{
            checkedStates = $allPageResults.Count
            checkedModals = $modalResults.Count
            failures = $failures.Count
            modalFailures = $modalFailures.Count
            widths = $Widths
        }
        failures = @($failures | ForEach-Object {
            [pscustomobject]@{
                width = $_.width
                state = $_.state
                bodyOverflow = $_.audit.bodyOverflow
                contentOverflow = $_.audit.contentOverflow
                activeOverflow = $_.audit.activeOverflow
                offenders = @($_.audit.offenders | ForEach-Object { $_.selector } | Select-Object -Unique)
            }
        })
        modalFailures = @($modalFailures | ForEach-Object {
            [pscustomobject]@{
                width = $_.width
                state = $_.state
                bodyOverflow = $_.audit.bodyOverflow
                contentOverflow = $_.audit.contentOverflow
                activeOverflow = $_.audit.activeOverflow
                offenders = @($_.audit.offenders | ForEach-Object { $_.selector } | Select-Object -Unique)
            }
        })
    } | ConvertTo-Json -Depth 12

    if ($failures.Count -gt 0 -or $modalFailures.Count -gt 0) { exit 2 }
} finally {
    if ($socket) { $socket.Dispose() }
    if ($edgeProcess -and -not $edgeProcess.HasExited) { Stop-Process -Id $edgeProcess.Id -Force }
    $resolvedProfile = [System.IO.Path]::GetFullPath($profilePath)
    if ($resolvedProfile.StartsWith($tempRoot, [StringComparison]::OrdinalIgnoreCase) -and (Test-Path -LiteralPath $resolvedProfile)) {
        Remove-Item -LiteralPath $resolvedProfile -Recurse -Force -ErrorAction SilentlyContinue
    }
}
