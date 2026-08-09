import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

// Module-level (not per-call) state: every component that calls this composable
// shares the same refs, so DashboardLayout can react to navigations kicked off
// from anywhere (sidebar links, redirects, etc.), not just from itself.
const isNavigating = ref(false)
const targetUrl = ref('')

let listenersBound = false

function bindListenersOnce() {
  if (listenersBound) {
    return
  }

  listenersBound = true

  router.on('start', (event) => {
    isNavigating.value = true
    targetUrl.value = event.detail.visit.url.pathname
  })

  router.on('finish', () => {
    isNavigating.value = false
  })
}

// Tracks in-flight Inertia navigations so layouts can show a route-specific
// skeleton for the page being navigated to, before its component has mounted.
export function useRouteLoading() {
  bindListenersOnce()

  return { isNavigating, targetUrl }
}
