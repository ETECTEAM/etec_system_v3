import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

// Module-level (not per-call) state: every component that calls this composable
// shares the same refs, so DashboardLayout can react to navigations kicked off
// from anywhere (sidebar links, redirects, etc.), not just from itself.
const isNavigating = ref(false)
const isNavigationPending = ref(false)
const targetUrl = ref('')

let listenersBound = false
let showTimer = null

const SHOW_DELAY_MS = 180

function clearShowTimer() {
  if (showTimer !== null) {
    clearTimeout(showTimer)
    showTimer = null
  }
}

function bindListenersOnce() {
  if (listenersBound) {
    return
  }

  listenersBound = true

  router.on('start', (event) => {
    isNavigationPending.value = true
    isNavigating.value = false
    targetUrl.value = event.detail.visit.url.pathname

    clearShowTimer()
    showTimer = setTimeout(() => {
      if (isNavigationPending.value) {
        isNavigating.value = true
      }
    }, SHOW_DELAY_MS)
  })

  router.on('finish', () => {
    isNavigationPending.value = false
    isNavigating.value = false
    clearShowTimer()
  })
}

// Tracks in-flight Inertia navigations so layouts can show a route-specific
// skeleton for the page being navigated to, before its component has mounted.
export function useRouteLoading() {
  bindListenersOnce()

  return { isNavigating, targetUrl }
}
