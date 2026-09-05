import { computed, ref } from 'vue'

// Module-scoped singleton state so every component shares one reactive source
// of truth instead of each attaching its own browser listeners.
//
// The beforeinstallprompt/appinstalled listeners are attached here as a
// MODULE-LEVEL side effect the moment this file is first imported — not inside
// a Vue component's setup(). Chrome/Edge can fire beforeinstallprompt as soon
// as the manifest + service worker are evaluated, which may happen before an
// Inertia page component (code-split per route) has even mounted. Hoisting the
// capture to module import time guarantees the event can never be lost.
const deferredPrompt = ref(null)
const installed = ref(false)

// True when the browser has told us a native install prompt is available.
const ready = computed(() => deferredPrompt.value != null)

function isStandaloneMode() {
  if (typeof window === 'undefined') return false
  if (navigator.standalone === true) return true
  return window.matchMedia('(display-mode: standalone)').matches
}

// If the app is already being run as an installed PWA (standalone launch),
// treat it as installed from the very first import so the UI never shows the
// add-to-screen affordances.
if (isStandaloneMode()) {
  installed.value = true
}

if (typeof window !== 'undefined') {
  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault()
    deferredPrompt.value = event
  })

  window.addEventListener('appinstalled', () => {
    installed.value = true
    deferredPrompt.value = null
  })
}

async function promptInstall() {
  const prompt = deferredPrompt.value
  if (!prompt) return

  prompt.prompt()
  const choice = await prompt.userChoice
  deferredPrompt.value = null

  if (choice?.outcome === 'accepted') {
    installed.value = true
  }

  return choice?.outcome
}

export function usePwaInstall() {
  return {
    deferredPrompt,
    installed,
    ready,
    promptInstall,
  }
}
