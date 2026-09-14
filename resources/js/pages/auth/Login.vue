<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import AuthCard from './components/AuthCard.vue'
import AuthLayout from '../../layouts/AuthLayout.vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { useToast } from '@/composables/useToast'
defineOptions({
  layout: AuthLayout,
})

const page = usePage()
const toast = useToast()

const form = useForm({
  login: '',
  password: '',
})

const showPassword = ref(false)

const formValid = computed(() => form.login.trim().length > 0 && form.password.length > 0)

// While locked out, the lockout alert above the form covers messaging, so
// suppress the field-level error to avoid showing the same thing twice.
const loginFieldError = computed(() => {
  if (lockoutSecondsLeft.value > 0) return null

  return form.errors.login
})

// The alert and toast read in minutes (or hours, once the wait is an hour or
// more - a hard block can run up to 24h, and "1440 minutes" doesn't read well).
// Under a minute, show the exact seconds instead of rounding up to "1 minute" -
// otherwise the last 59 seconds of any ban all read identically.
function lockoutWaitLabel(seconds) {
  if (seconds < 60) {
    const s = Math.max(1, Math.ceil(seconds))

    return `${s} ${s === 1 ? 'second' : 'seconds'}`
  }

  const minutes = Math.ceil(seconds / 60)

  if (minutes >= 60) {
    const hours = Math.ceil(minutes / 60)

    return `${hours} ${hours === 1 ? 'hour' : 'hours'}`
  }

  return `${minutes} ${minutes === 1 ? 'minute' : 'minutes'}`
}

const lockoutWaitText = computed(() => lockoutWaitLabel(lockoutSecondsLeft.value))

// Counts down a throttle block locally once retryAfter arrives, so the submit
// button stays disabled without needing to keep polling the server.
const lockoutSecondsLeft = ref(0)
// True when the block is an account-wide hard block (past every configured
// lockout tier) rather than a regular timed one - only an admin unblocking
// it, or the full reset window, lifts this one.
const lockoutIsHardBlock = ref(false)
let lockoutTimer = null

// The ban itself is enforced server-side (DB-backed), but the countdown UI is
// only in-memory - without this, refreshing the page during an active block
// would reset lockoutSecondsLeft to 0 and the form would look usable again,
// even though the next submit would just be rejected the same way. Persist
// the absolute expiry so a refresh can restore the same countdown instead of
// waiting for another failed submit to rediscover it.
const LOCKOUT_STORAGE_KEY = 'login-lockout'

function persistLockout(seconds, isHardBlock) {
  sessionStorage.setItem(LOCKOUT_STORAGE_KEY, JSON.stringify({
    expiresAt: Date.now() + seconds * 1000,
    isHardBlock,
  }))
}

function clearPersistedLockout() {
  sessionStorage.removeItem(LOCKOUT_STORAGE_KEY)
}

function startLockoutCountdown(seconds, isHardBlock = false) {
  window.clearInterval(lockoutTimer)
  lockoutSecondsLeft.value = seconds
  lockoutIsHardBlock.value = isHardBlock
  persistLockout(seconds, isHardBlock)

  lockoutTimer = window.setInterval(() => {
    if (lockoutSecondsLeft.value > 0) {
      lockoutSecondsLeft.value -= 1
    } else {
      window.clearInterval(lockoutTimer)
      lockoutIsHardBlock.value = false
      clearPersistedLockout()
      // The block has actually lifted now - clear every field error so nothing
      // lingers from either the throttle message or the attempt that caused it.
      form.clearErrors()
    }
  }, 1000)
}

watch(() => page.props.flash, (flash) => {
  // A throttle lockout carries its own retryAfter-based message so the toast
  // reads in minutes/hours, instead of the backend's raw "...in N seconds." string.
  if (flash?.retryAfter) {
    const wait = lockoutWaitLabel(flash.retryAfter)

    toast.error(flash.isHardBlock
      ? `Your account has been blocked. Contact an administrator, or wait ${wait} for it to reset automatically.`
      : `Too many attempts. Please try again in ${wait}.`)
    startLockoutCountdown(flash.retryAfter, !!flash.isHardBlock)
  } else if (flash?.error) {
    toast.error(flash.error)
  }
}, { deep: true })

onMounted(() => {
  const stored = sessionStorage.getItem(LOCKOUT_STORAGE_KEY)
  if (!stored) return

  const { expiresAt, isHardBlock } = JSON.parse(stored)
  const secondsLeft = Math.ceil((expiresAt - Date.now()) / 1000)

  if (secondsLeft > 0) {
    startLockoutCountdown(secondsLeft, isHardBlock)
  } else {
    clearPersistedLockout()
  }
})

onBeforeUnmount(() => {
  window.clearInterval(lockoutTimer)
})

function submit() {
  if (!formValid.value || lockoutSecondsLeft.value > 0) return
  form.post('/login')
}
</script>

<template>
  <AuthCard>
    <div class="mb-8">
      <h2 class="text-3xl font-black text-slate-900 dark:text-gray-100">Sign In</h2>
      <p class="mt-2 text-sm text-slate-600 dark:text-gray-400">Use your email or username to continue.</p>
    </div>

    <div
      v-if="lockoutSecondsLeft > 0"
      class="mb-6 flex items-start gap-2.5 rounded-lg border border-red-100 bg-[#FEECEC] px-4 py-3 dark:border-red-500/20 dark:bg-red-500/10"
    >
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="mt-0.5 h-4 w-4 shrink-0 text-[#D14343] dark:text-red-400">
        <circle cx="12" cy="12" r="9" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5v5.5" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.25h.01" />
      </svg>
      <div class="text-[13px] leading-snug text-[#D14343] dark:text-red-400">
        <template v-if="lockoutIsHardBlock">
          <p>Your account has been blocked due to repeated failed login attempts.</p>
          <p>Contact an administrator, or wait {{ lockoutWaitText }} for it to reset automatically.</p>
        </template>
        <template v-else>
          <p>Too many failed login attempts.</p>
          <p>Please try again in {{ lockoutWaitText }}.</p>
        </template>
      </div>
    </div>

    <form class="space-y-4" @submit.prevent="submit">
      <label class="block">
        <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">Email or Username</span>
        <input
          v-model="form.login"
          type="text"
          autocomplete="username"
          class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
          placeholder="you@example.com or username"
        >
        <span v-if="loginFieldError" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ loginFieldError }}</span>
      </label>

      <label class="block">
        <span class="mb-2 flex items-center justify-between gap-2">
          <span class="text-sm font-semibold text-slate-700 dark:text-gray-300">Password</span>
          <Link href="/forgot-password" class="text-xs font-semibold text-blue-900 hover:text-blue-950 dark:text-blue-400 dark:hover:text-blue-300">Forgot password?</Link>
        </span>
        <div class="relative">
          <input
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            autocomplete="current-password"
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 pr-12 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
            placeholder="Enter your password"
          >

          <button
            type="button"
            class="absolute top-1/2 right-3 -translate-y-1/2 text-slate-500 transition hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200"
            @click="showPassword = !showPassword"
          >
            <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M10.58 10.58A2 2 0 0012 16a2 2 0 001.42-.58" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 4.24A10.94 10.94 0 0112 4c5 0 9.27 3.11 11 8-1.01 2.85-2.94 5.11-5.41 6.44" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M6.61 6.61C4.62 8 3.1 9.86 2 12c.69 1.94 1.79 3.62 3.17 4.94" />
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-8 10-8 10 8 10 8-3.5 8-10 8S2 12 2 12z" />
              <circle cx="12" cy="12" r="3" />
            </svg>
          </button>
        </div>
        <span v-if="form.errors.password" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.password }}</span>
      </label>

      <p v-if="form.hasErrors && !form.errors.login && !form.errors.password" class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400">
        Invalid login credentials.
      </p>

      <button
        type="submit"
        :disabled="form.processing || !formValid || lockoutSecondsLeft > 0"
        class="w-full rounded-xl bg-blue-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500"
      >
        {{ lockoutSecondsLeft > 0 ? (lockoutIsHardBlock ? `Blocked - ${lockoutWaitText}` : `Try again in ${lockoutWaitText}`) : (form.processing ? 'Signing in...' : 'Sign In') }}
      </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600 dark:text-gray-400">
      New here?
      <Link href="/instructor-register" class="font-semibold text-blue-900 hover:text-blue-950 dark:text-blue-400 dark:hover:text-blue-300">Create account</Link>
    </p>
  </AuthCard>
</template>
