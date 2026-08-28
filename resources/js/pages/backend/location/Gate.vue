<script setup>
import { onMounted, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import axios from 'axios'
import { LoaderCircle, MapPin, RotateCcw, ShieldAlert } from '@lucide/vue'

const props = defineProps({
  intended: { type: String, default: '/dashboard' },
  ttlSeconds: { type: Number, default: 900 },
})

// idle | locating | verifying | denied | error
const state = ref('idle')
const message = ref('')

function check() {
  if (!('geolocation' in navigator)) {
    state.value = 'error'
    message.value = 'This browser cannot report its location.'
    return
  }

  state.value = 'locating'
  message.value = ''

  navigator.geolocation.getCurrentPosition(
    async ({ coords }) => {
      state.value = 'verifying'
      try {
        const { data } = await axios.post('/dashboard/location/verify', {
          latitude: coords.latitude,
          longitude: coords.longitude,
          accuracy: coords.accuracy,
        })

        if (data?.matched) {
          // Full reload so the middleware re-evaluates with the fresh session stamp.
          window.location.assign(data.redirect || props.intended)
          return
        }

        state.value = 'denied'
        message.value = data?.message || 'You are not inside an approved location.'
      } catch (err) {
        if (err.response?.status === 422) {
          state.value = 'denied'
          message.value = err.response.data?.message || 'You are not inside an approved location.'
        } else if (err.response?.status === 429) {
          state.value = 'error'
          message.value = 'Too many attempts. Wait a minute and try again.'
        } else {
          state.value = 'error'
          message.value = 'Could not verify your location. Please try again.'
        }
      }
    },
    (err) => {
      state.value = 'error'
      message.value =
        err.code === err.PERMISSION_DENIED
          ? 'Location permission was denied. Enable it for this site in your browser settings, then try again.'
          : 'Could not read your location. Make sure GPS / location services are on.'
    },
    { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
  )
}

onMounted(check)
</script>

<template>
  <Head :title="$t('Location check')" />

  <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-12 dark:bg-gray-950">
    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm dark:border-gray-800 dark:bg-gray-900">
      <span
        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl"
        :class="state === 'denied' || state === 'error'
          ? 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400'
          : 'bg-blue-50 text-blue-900 dark:bg-blue-500/10 dark:text-blue-400'"
      >
        <component
          :is="state === 'locating' || state === 'verifying' ? LoaderCircle : (state === 'denied' || state === 'error' ? ShieldAlert : MapPin)"
          class="h-7 w-7"
          :class="{ 'animate-spin': state === 'locating' || state === 'verifying' }"
        />
      </span>

      <h1 class="mt-5 text-lg font-bold text-slate-900 dark:text-gray-100">
        {{ $t('This section needs your location') }}
      </h1>

      <p v-if="state === 'locating'" class="mt-2 text-sm text-slate-500 dark:text-gray-400">
        {{ $t('Reading your location...') }}
      </p>
      <p v-else-if="state === 'verifying'" class="mt-2 text-sm text-slate-500 dark:text-gray-400">
        {{ $t('Checking you are inside an approved area...') }}
      </p>
      <p v-else-if="message" class="mt-2 text-sm font-medium text-amber-600 dark:text-amber-400">
        {{ message }}
      </p>
      <p v-else class="mt-2 text-sm text-slate-500 dark:text-gray-400">
        {{ $t('Allow location access so we can confirm you are on site.') }}
      </p>

      <div class="mt-6 flex flex-col gap-2.5">
        <button
          type="button"
          :disabled="state === 'locating' || state === 'verifying'"
          class="flex items-center justify-center gap-2 rounded-xl bg-blue-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500"
          @click="check"
        >
          <RotateCcw class="h-4 w-4" />
          {{ state === 'idle' ? $t('Share my location') : $t('Try again') }}
        </button>
        <Link
          href="/dashboard"
          class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
        >
          {{ $t('Back to dashboard') }}
        </Link>
      </div>

      <p class="mt-5 text-[11px] text-slate-400 dark:text-gray-600">
        {{ $t('Location access requires a secure (https) connection.') }}
      </p>
    </div>
  </div>
</template>
