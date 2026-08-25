<script setup>
import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import axios from 'axios'
import { AlertTriangle, CalendarDays, CheckCircle2, Clock, Send } from '@lucide/vue'
import { useI18n } from '../../i18n'

// Public phone-facing form behind the office's signed QR. No dashboard chrome —
// the student scans, fills the range + reason, and submits back to the same URL.
const props = defineProps({
  state: {
    type: String,
    default: 'not_found',
  },
  student: {
    type: Object,
    default: null,
  },
  expiresAt: {
    type: String,
    default: null,
  },
})

const { t } = useI18n()

const form = ref({
  start_date: new Date().toISOString().slice(0, 10),
  end_date: new Date().toISOString().slice(0, 10),
  reason: '',
})
const fieldErrors = ref({})
const submitting = ref(false)
const submitted = ref(false)

const days = computed(() => {
  const start = new Date(`${form.value.start_date}T00:00:00`)
  const end = new Date(`${form.value.end_date}T00:00:00`)

  if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) return null

  return Math.floor((end - start) / 86400000) + 1
})

const submitUrl = computed(() => {
  // POST back to the exact signed URL (path + query) the student opened.
  return window.location.pathname + window.location.search
})

async function submit() {
  submitting.value = true
  fieldErrors.value = {}

  try {
    await axios.post(submitUrl.value, form.value)

    submitted.value = true
  } catch (error) {
    if (error.response?.status === 422 && error.response?.data?.errors) {
      const errors = error.response.data.errors

      fieldErrors.value = Object.fromEntries(
        Object.entries(errors).map(([key, messages]) => [key, messages[0]]),
      )
    } else {
      fieldErrors.value = { token: error.response?.data?.message ?? t('Failed to submit the request. Please try again.') }
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <Head :title="$t('Official Leave Request')" />

  <div class="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10 dark:bg-gray-950">
    <div class="w-full max-w-md">
      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-gray-800 dark:bg-gray-900 sm:p-8">
        <div class="text-center">
          <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-lg">
            <CalendarDays class="h-7 w-7" />
          </div>
          <h1 class="mt-4 text-2xl font-black tracking-tight text-slate-950 dark:text-gray-100">{{ $t('Official Leave Request') }}</h1>
          <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-gray-400">{{ $t('ETEC Center · School Office') }}</p>
        </div>

        <div v-if="state === 'not_found'" class="mt-8 space-y-3 text-center">
          <AlertTriangle class="mx-auto h-12 w-12 text-rose-500" />
          <p class="text-base font-bold text-slate-900 dark:text-gray-100">{{ $t('This leave request link is invalid.') }}</p>
          <p class="text-sm font-semibold text-slate-500 dark:text-gray-400">{{ $t('Please ask the school office for a new QR code.') }}</p>
        </div>

        <div v-else-if="state === 'expired'" class="mt-8 space-y-3 text-center">
          <Clock class="mx-auto h-12 w-12 text-amber-500" />
          <p class="text-base font-bold text-slate-900 dark:text-gray-100">{{ $t('This QR code has expired.') }}</p>
          <p class="text-sm font-semibold text-slate-500 dark:text-gray-400">{{ $t('Please ask the school office for a new QR code.') }}</p>
        </div>

        <!-- Submitted now, or revisited after submitting -->
        <div v-else-if="submitted || state === 'already_used'" class="mt-8 space-y-3 text-center">
          <CheckCircle2 class="mx-auto h-12 w-12 text-emerald-500" />
          <p class="text-base font-bold text-slate-900 dark:text-gray-100">
            {{ submitted ? $t('Request submitted!') : $t('Your request was already submitted.') }}
          </p>
          <p class="text-sm font-semibold text-slate-500 dark:text-gray-400">{{ $t('The office will review your leave shortly.') }}</p>
        </div>

        <!-- The form -->
        <form v-else class="mt-8 space-y-5" novalidate @submit.prevent="submit">
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-center dark:border-gray-700 dark:bg-gray-800/60">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">{{ $t('Student') }}</p>
            <p class="mt-1 text-lg font-black text-slate-950 dark:text-gray-100">{{ student?.full_name }}</p>
            <p class="text-xs font-semibold text-slate-500 dark:text-gray-400">
              #{{ student?.id }} · {{ student?.classes?.length ? student.classes.join(', ') : (student?.course ?? '-') }}
            </p>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label for="leave-start" class="text-xs font-black uppercase tracking-[0.1em] text-slate-500 dark:text-gray-400">{{ $t('From') }}</label>
              <input
                id="leave-start"
                v-model="form.start_date"
                type="date"
                required
                class="mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-bold outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
              />
              <p v-if="fieldErrors.start_date" class="mt-1 text-xs font-bold text-rose-600">{{ fieldErrors.start_date }}</p>
            </div>

            <div>
              <label for="leave-end" class="text-xs font-black uppercase tracking-[0.1em] text-slate-500 dark:text-gray-400">{{ $t('To') }}</label>
              <input
                id="leave-end"
                v-model="form.end_date"
                type="date"
                required
                class="mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-bold outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
              />
              <p v-if="fieldErrors.end_date" class="mt-1 text-xs font-bold text-rose-600">{{ fieldErrors.end_date }}</p>
            </div>
          </div>

          <p class="-mt-2 text-xs font-semibold text-slate-400">
            {{ days ? $t(':day day(s)', { day: days }) : '' }}
          </p>

          <div>
            <label for="leave-reason" class="text-xs font-black uppercase tracking-[0.1em] text-slate-500 dark:text-gray-400">{{ $t('Reason') }}</label>
            <textarea
              id="leave-reason"
              v-model="form.reason"
              rows="3"
              required
              :placeholder="$t('Why do you need this leave?')"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
            />
            <p v-if="fieldErrors.reason" class="mt-1 text-xs font-bold text-rose-600">{{ fieldErrors.reason }}</p>
          </div>

          <button
            type="submit"
            :disabled="submitting"
            class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-blue-600 text-sm font-black text-white shadow-lg transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70"
          >
            <Send class="h-4 w-4" />
            {{ submitting ? $t('Submitting...') : $t('Submit request') }}
          </button>

          <p class="text-center text-xs font-semibold text-slate-400">
            {{ expiresAt ? $t('Valid until :time.', { time: new Date(expiresAt).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' }) }) : '' }}
          </p>
        </form>
      </div>

      <p class="mt-4 text-center text-xs font-semibold text-slate-400">ETEC Center</p>
    </div>
  </div>
</template>
