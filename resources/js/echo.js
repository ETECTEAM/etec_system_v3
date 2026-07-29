import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// Lazily created so pages that never touch notifications (most of the app -
// students, instructors) never open a websocket connection at all.
let echoInstance = null

export function getEcho() {
  if (echoInstance) return echoInstance

  if (!import.meta.env.VITE_REVERB_APP_KEY) {
    return null
  }

  window.Pusher = Pusher

  echoInstance = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
  })

  return echoInstance
}
