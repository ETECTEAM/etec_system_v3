import DisableDevtool from 'disable-devtool'

let initialized = false

function shouldEnableAntiInspect() {
    return import.meta.env.PROD || import.meta.env.VITE_ANTI_INSPECT === 'true'
}

function showRestrictedPage() {
    if (document.getElementById('devtools-restricted')) {
        return
    }

    document.body.innerHTML = ''

    const style = document.createElement('style')
    style.textContent = `
        @keyframes dt-pop {
            0% { transform: scale(0.85); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        @keyframes dt-bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        #devtools-restricted * {
            box-sizing: border-box;
        }
        #devtools-restricted .dt-card {
            animation: dt-pop 0.35s ease-out;
            max-width: 480px;
            width: 100%;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 20px;
            padding: 40px 32px;
            backdrop-filter: blur(6px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        #devtools-restricted .dt-emoji {
            font-size: 56px;
            display: block;
            margin-bottom: 16px;
            animation: dt-bounce 1.6s ease-in-out infinite;
        }
        #devtools-restricted h1 {
            font-size: 22px;
            margin: 0 0 12px;
            line-height: 1.4;
        }
        #devtools-restricted p {
            font-size: 15px;
            opacity: 0.75;
            margin: 0;
            line-height: 1.6;
        }
        #devtools-restricted .dt-sub {
            margin-top: 18px;
            font-size: 12px;
            opacity: 0.4;
            letter-spacing: 0.5px;
        }
    `
    document.head.appendChild(style)

    const restrictedPage = document.createElement('main')
    restrictedPage.id = 'devtools-restricted'
    restrictedPage.setAttribute('role', 'alert')
    restrictedPage.setAttribute('aria-live', 'assertive')
    restrictedPage.innerHTML = `
        <section class="dt-card">
            <span class="dt-emoji">🕵️‍♂️</span>
            <h1>ចាប់បានហើយ! កុំបើក Inspect ទៀតណា 😅</h1>
            <p>បិទ DevTools ចោល ហើយ Refresh ម្តងទៀត អ្វីៗនឹងវិលមកដូចដើម។</p>
            <div class="dt-sub">nice try though 👀</div>
        </section>
    `

    Object.assign(restrictedPage.style, {
        position: 'fixed',
        inset: '0',
        zIndex: '2147483647',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        padding: '30px',
        boxSizing: 'border-box',
        fontFamily: '"Segoe UI", Arial, sans-serif',
        textAlign: 'center',
        background: 'radial-gradient(circle at top, #1f1f26, #0b0b0f 70%)',
        color: '#fff',
    })

    document.body.appendChild(restrictedPage)
}

function blockDevToolShortcuts(event) {
    const key = event.key.toLowerCase()
    const ctrlOrMeta = event.ctrlKey || event.metaKey
    const macInspectShortcut = event.metaKey && event.altKey && ['i', 'j', 'c'].includes(key)
    const blocked =
        event.key === 'F12' ||
        (ctrlOrMeta && event.shiftKey && ['i', 'j', 'c'].includes(key)) ||
        (ctrlOrMeta && key === 'u') ||
        macInspectShortcut

    if (blocked) {
        event.preventDefault()
        event.stopPropagation()
    }
}

function blockContextMenu(event) {
    event.preventDefault()
}

function isLikelyDevtoolsDocked() {
    const widthGap = window.outerWidth - window.innerWidth
    const heightGap = window.outerHeight - window.innerHeight

    return widthGap > 160 || heightGap > 160
}

function runProtectionCheck() {
    const devtoolOpened =
        typeof DisableDevtool.isDevToolOpened === 'function' && DisableDevtool.isDevToolOpened()

    if (devtoolOpened || isLikelyDevtoolsDocked()) {
        showRestrictedPage()
    }
}

function initializeDisableDevtool() {
    if (DisableDevtool.isRunning) {
        runProtectionCheck()
        return
    }

    DisableDevtool({
        disableMenu: true,
        disableSelect: false,
        disableCopy: false,
        disableCut: false,
        disablePaste: false,
        clearLog: true,
        interval: 1000,
        clearIntervalWhenDevOpenTrigger: true,
        ondevtoolopen: showRestrictedPage,
        rewriteHTML: '',
    })
}

export function initializeAntiInspect() {
    if (!shouldEnableAntiInspect() || initialized) {
        return
    }

    initialized = true

    document.addEventListener('keydown', blockDevToolShortcuts, true)
    document.addEventListener('contextmenu', blockContextMenu, true)

    initializeDisableDevtool()

    window.addEventListener('load', runProtectionCheck, { passive: true })
    window.addEventListener('focus', runProtectionCheck, { passive: true })
    window.addEventListener('pageshow', runProtectionCheck, { passive: true })
    window.addEventListener('resize', runProtectionCheck, { passive: true })
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            runProtectionCheck()
        }
    })
}