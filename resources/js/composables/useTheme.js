import { computed, ref } from 'vue'

const STORAGE_KEY = 'theme'
const VALID_THEMES = ['light', 'dark', 'system']

// Module-scoped state (outside the composable function) so every component
// that calls useTheme() shares one reactive source of truth instead of each
// getting its own isolated ref.
const theme = ref(readStoredTheme())
const media = typeof window !== 'undefined' ? window.matchMedia('(prefers-color-scheme: dark)') : null
const prefersDark = ref(media?.matches ?? false)

function readStoredTheme() {
  if (typeof window === 'undefined') {
    return 'system'
  }

  const stored = window.localStorage.getItem(STORAGE_KEY)
  return VALID_THEMES.includes(stored) ? stored : 'system'
}

// The actually-applied theme: resolves 'system' down to 'light' or 'dark'.
const resolvedTheme = computed(() => (theme.value === 'system' ? (prefersDark.value ? 'dark' : 'light') : theme.value))

function applyTheme() {
  document.documentElement.classList.toggle('dark', resolvedTheme.value === 'dark')
}

function setTheme(value) {
  if (!VALID_THEMES.includes(value)) {
    return
  }

  theme.value = value
  window.localStorage.setItem(STORAGE_KEY, value)
  applyTheme()
}

function cycleTheme() {
  const order = ['light', 'dark', 'system']
  const next = order[(order.indexOf(theme.value) + 1) % order.length]
  setTheme(next)
}

// Keep 'system' mode in sync if the OS-level preference changes while open.
media?.addEventListener('change', (event) => {
  prefersDark.value = event.matches

  if (theme.value === 'system') {
    applyTheme()
  }
})

// Apply once on module load (covers client-side navigations after the
// initial page load's inline FOUC-prevention script has already run).
applyTheme()

export function useTheme() {
  return {
    theme,
    resolvedTheme,
    setTheme,
    cycleTheme,
  }
}
