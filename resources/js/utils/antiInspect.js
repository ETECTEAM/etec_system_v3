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

    const restrictedPage = document.createElement('main')
    restrictedPage.id = 'devtools-restricted'
    restrictedPage.setAttribute('role', 'alert')
    restrictedPage.setAttribute('aria-live', 'assertive')
    restrictedPage.innerHTML = `
        <section>
            <h1>Ke Bit Inspect Hx Khorm Berk Tab Tmey Inspect Tuk TT</h1>
            <p>Hot Mong Hah</p>
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
        fontFamily: 'Arial, sans-serif',
        textAlign: 'center',
        background: '#111',
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
