<script setup>
import axios from 'axios'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { ArrowRight, Check, GraduationCap, Loader2, LogOut, Mail, RotateCcw, ShieldCheck, Sparkles } from '@lucide/vue'
import { SelectSearch } from '../../../components/ui/select-search'

const props = defineProps({
  user: { type: Object, default: () => ({}) },
  instructorData: { type: Object, default: null },
  justCompleted: { type: Boolean, default: false },
  recoveryEmail: { type: String, default: null },
  recoveryVerified: { type: Boolean, default: false },
  workSchedules: { type: Array, default: () => [] },
  subCategories: { type: Array, default: () => [] },
})

const STEPS = [
  { key: 'teaching', label: 'Teaching setup' },
  { key: 'recovery', label: 'Recovery email' },
  { key: 'done', label: 'All set' },
]

function resolveInitialStep() {
  // Arrived here straight from finishing the last step - show the "all set" screen.
  if (props.justCompleted) return 2

  const t = props.instructorData
  const teachingDone = Boolean(
    t && t.employment_type && t.work_schedule_id && (t.specialization?.length ?? 0) > 0,
  )
  if (!teachingDone) return 0
  if (!props.recoveryVerified) return 1
  return 2
}

const step = ref(resolveInitialStep())

const employmentTypeOptions = [
  { label: 'Full Time', value: 'full_time' },
  { label: 'Part Time', value: 'part_time' },
]

// New instructors start with these areas pre-selected (only the ones that
// actually exist as options); they can add or remove any of them.
const DEFAULT_SPECIALIZATIONS = ['Basic IT', 'Programming Fundamentals']

const initialSpecialization = props.instructorData?.specialization?.length
  ? [...props.instructorData.specialization]
  : DEFAULT_SPECIALIZATIONS.filter((name) => props.subCategories.includes(name))

const teachingForm = useForm({
  employment_type: props.instructorData?.employment_type ?? '',
  work_schedule_id: props.instructorData?.work_schedule_id ? String(props.instructorData.work_schedule_id) : '',
  specialization: initialSpecialization,
})

const workScheduleOptions = computed(() =>
  props.workSchedules
    .filter((ws) => ws.code?.startsWith(`${teachingForm.employment_type}_`))
    .map((ws) => ({ label: ws.name, value: String(ws.id) })),
)

watch(
  () => teachingForm.employment_type,
  () => {
    if (!workScheduleOptions.value.some((option) => option.value === teachingForm.work_schedule_id)) {
      teachingForm.work_schedule_id = ''
    }
  },
)

const teachingValid = computed(
  () =>
    Boolean(teachingForm.employment_type) &&
    Boolean(teachingForm.work_schedule_id) &&
    teachingForm.specialization.length > 0,
)

function toggleSpecialization(name) {
  const index = teachingForm.specialization.indexOf(name)
  if (index === -1) {
    teachingForm.specialization.push(name)
  } else {
    teachingForm.specialization.splice(index, 1)
  }
}

function saveTeaching() {
  if (!teachingValid.value || teachingForm.processing) return

  teachingForm.put('/dashboard/instructor/onboarding/teaching', {
    preserveScroll: true,
    onSuccess: () => {
      step.value = props.recoveryVerified ? 2 : 1
    },
  })
}

// --- Recovery email step ------------------------------------------------------

const recoveryForm = useForm({ recovery_email: '' })
const resending = ref(false)
const checkingStatus = ref(false)
let pollTimer = null

function sendRecoveryEmail() {
  if (recoveryForm.processing) return

  recoveryForm
    .transform((data) => ({ recovery_email: data.recovery_email.trim().toLowerCase() }))
    .post('/dashboard/instructor/onboarding/recovery-email', {
      preserveScroll: true,
      onSuccess: () => {
        recoveryForm.reset()
        startPolling()
      },
    })
}

function resendRecoveryEmail() {
  if (resending.value) return
  resending.value = true
  router.post(
    '/dashboard/instructor/onboarding/recovery-email/resend',
    {},
    { preserveScroll: true, onFinish: () => (resending.value = false) },
  )
}

async function checkStatus({ manual = false } = {}) {
  if (manual) checkingStatus.value = true
  try {
    const { data } = await axios.get('/dashboard/instructor/onboarding/status')
    if (data.recoveryVerified || data.complete) {
      stopPolling()
      step.value = 2
    }
  } catch {
    // Transient failure - the next poll (or manual retry) tries again.
  } finally {
    if (manual) checkingStatus.value = false
  }
}

function startPolling() {
  stopPolling()
  pollTimer = window.setInterval(checkStatus, 5000)
}

function stopPolling() {
  if (pollTimer) {
    window.clearInterval(pollTimer)
    pollTimer = null
  }
}

watch(step, (value) => {
  if (value === 1 && props.recoveryEmail && !props.recoveryVerified) {
    startPolling()
  } else {
    stopPolling()
  }
})

onMounted(() => {
  if (step.value === 1 && props.recoveryEmail && !props.recoveryVerified) {
    startPolling()
  }
})

onBeforeUnmount(stopPolling)

function goToDashboard() {
  router.visit('/dashboard')
}

function logout() {
  router.post('/logout')
}
</script>

<template>
  <Head :title="$t('Finish your setup')" />

  <main class="min-h-screen bg-slate-100 px-4 py-10 text-slate-900 sm:py-16 dark:bg-gray-950 dark:text-gray-100">
    <div class="mx-auto w-full max-w-2xl">
      <div class="mb-8 flex items-center justify-between">
        <div>
          <p class="text-xs font-semibold tracking-wide text-blue-800 uppercase dark:text-blue-400">{{ $t('Welcome') }}</p>
          <h1 class="mt-1 text-2xl font-black sm:text-3xl">{{ $t('Finish your setup') }}</h1>
          <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
            {{ $t('A couple of quick steps and your dashboard is ready') }}{{ props.user?.name ? `, ${props.user.name}` : '' }}.
          </p>
        </div>
        <button
          type="button"
          class="flex shrink-0 items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-semibold text-slate-500 transition hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-100"
          @click="logout"
        >
          <LogOut class="h-3.5 w-3.5" />
          {{ $t('Log out') }}
        </button>
      </div>

      <!-- Stepper -->
      <ol class="mb-8 flex items-center gap-2">
        <li v-for="(s, index) in STEPS" :key="s.key" class="flex flex-1 items-center gap-2">
          <span
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border text-xs font-bold transition"
            :class="index < step
              ? 'border-emerald-500 bg-emerald-500 text-white'
              : index === step
                ? 'border-blue-900 bg-blue-900 text-white dark:border-blue-500 dark:bg-blue-500'
                : 'border-slate-300 bg-white text-slate-400 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-500'"
          >
            <Check v-if="index < step" class="h-4 w-4" />
            <span v-else>{{ index + 1 }}</span>
          </span>
          <span
            class="hidden text-xs font-semibold sm:block"
            :class="index <= step ? 'text-slate-800 dark:text-gray-100' : 'text-slate-400 dark:text-gray-500'"
          >
            {{ $t(s.label) }}
          </span>
          <span
            v-if="index < STEPS.length - 1"
            class="h-px flex-1"
            :class="index < step ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-gray-700'"
          />
        </li>
      </ol>

      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-gray-800 dark:bg-gray-900">
        <!-- Step 1: Teaching setup -->
        <section v-if="step === 0">
          <div class="mb-6 flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-900 dark:bg-blue-500/10 dark:text-blue-400">
              <GraduationCap class="h-5 w-5" />
            </span>
            <div>
              <h2 class="text-base font-bold">{{ $t('Teaching setup') }}</h2>
              <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                {{ $t('Tell us how you work so we can build your class schedule.') }}
              </p>
            </div>
          </div>

          <form class="space-y-5" @submit.prevent="saveTeaching">
            <label class="block">
              <span class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">
                {{ $t('Employment Type') }} <span class="text-red-500">*</span>
              </span>
              <SelectSearch
                v-model="teachingForm.employment_type"
                :options="employmentTypeOptions"
                :placeholder="$t('Select employment type')"
                button-class="flex w-full h-11 items-center justify-between rounded-lg border border-slate-300 bg-white px-4 text-sm transition focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
              />
              <span v-if="teachingForm.errors.employment_type" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ teachingForm.errors.employment_type }}</span>
            </label>

            <label class="block">
              <span class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">
                {{ $t('Work Schedule') }} <span class="text-red-500">*</span>
              </span>
              <SelectSearch
                v-model="teachingForm.work_schedule_id"
                :options="workScheduleOptions"
                :placeholder="teachingForm.employment_type ? $t('Select a work schedule') : $t('Choose an employment type first')"
                button-class="flex w-full h-11 items-center justify-between rounded-lg border border-slate-300 bg-white px-4 text-sm transition focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
              />
              <p class="mt-1 text-xs text-slate-400 dark:text-gray-500">{{ $t('Your weekly availability is derived from this schedule.') }}</p>
              <span v-if="teachingForm.errors.work_schedule_id" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ teachingForm.errors.work_schedule_id }}</span>
            </label>

            <div class="block">
              <span class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">
                {{ $t('Specialization') }} <span class="text-red-500">*</span>
              </span>
              <p class="mb-2 text-xs text-slate-400 dark:text-gray-500">{{ $t('Select every area you can teach - click a skill to add or remove it.') }}</p>
              <div v-if="props.subCategories.length" class="flex flex-wrap gap-2">
                <button
                  v-for="name in props.subCategories"
                  :key="name"
                  type="button"
                  class="rounded-full border px-3 py-1.5 text-xs font-medium transition"
                  :class="teachingForm.specialization.includes(name)
                    ? 'border-blue-600 bg-blue-600 text-white dark:border-blue-500 dark:bg-blue-500'
                    : 'border-slate-300 text-slate-600 hover:border-blue-400 hover:text-blue-600 dark:border-gray-600 dark:text-gray-300 dark:hover:border-blue-500 dark:hover:text-blue-400'"
                  @click="toggleSpecialization(name)"
                >
                  {{ name }}
                </button>
              </div>
              <p v-else class="text-xs text-amber-600 dark:text-amber-400">{{ $t('No specialization options are available yet. Contact an administrator.') }}</p>
              <span v-if="teachingForm.errors.specialization" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ teachingForm.errors.specialization }}</span>
            </div>

            <div class="flex justify-end border-t border-slate-200 pt-5 dark:border-gray-800">
              <button
                type="submit"
                :disabled="!teachingValid || teachingForm.processing"
                class="flex h-11 items-center gap-2 rounded-lg bg-blue-900 px-5 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-blue-600 dark:hover:bg-blue-500"
              >
                {{ teachingForm.processing ? $t('Saving...') : $t('Continue') }}
                <ArrowRight v-if="!teachingForm.processing" class="h-4 w-4" />
              </button>
            </div>
          </form>
        </section>

        <!-- Step 2: Recovery email -->
        <section v-else-if="step === 1">
          <div class="mb-6 flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-900 dark:bg-blue-500/10 dark:text-blue-400">
              <Mail class="h-5 w-5" />
            </span>
            <div>
              <h2 class="text-base font-bold">{{ $t('Recovery email') }}</h2>
              <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                {{ $t('Add a personal email, separate from') }} <span class="font-semibold">{{ props.user?.email }}</span>, {{ $t('so password-reset links reach only you.') }}
              </p>
            </div>
          </div>

          <!-- Awaiting verification -->
          <div v-if="props.recoveryEmail && !props.recoveryVerified" class="space-y-4">
            <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/20 dark:bg-amber-500/10">
              <Loader2 class="mt-0.5 h-4 w-4 shrink-0 animate-spin text-amber-600 dark:text-amber-400" />
              <div class="text-sm">
                <p class="font-semibold text-amber-900 dark:text-amber-300">{{ $t('Verification link sent') }}</p>
                <p class="mt-0.5 text-amber-800 dark:text-amber-200">
                  {{ $t('Open the link we emailed to') }} <span class="font-semibold">{{ props.recoveryEmail }}</span>. {{ $t('This page updates automatically once you do.') }}
                </p>
              </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
              <button
                type="button"
                :disabled="checkingStatus"
                class="flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:opacity-60 dark:bg-blue-600 dark:hover:bg-blue-500"
                @click="checkStatus({ manual: true })"
              >
                <Loader2 v-if="checkingStatus" class="h-4 w-4 animate-spin" />
                <Check v-else class="h-4 w-4" />
                {{ $t("I've clicked the link") }}
              </button>
              <button
                type="button"
                :disabled="resending"
                class="flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-blue-400 hover:text-blue-700 disabled:opacity-60 dark:border-gray-700 dark:text-gray-300 dark:hover:border-blue-500/50 dark:hover:text-blue-400"
                @click="resendRecoveryEmail"
              >
                <RotateCcw class="h-3.5 w-3.5" />
                {{ $t('Resend link') }}
              </button>
            </div>

            <p class="pt-1 text-xs font-semibold text-slate-500 dark:text-gray-400">{{ $t('Wrong address? Enter a new one:') }}</p>
            <form class="flex flex-col gap-3 sm:flex-row" @submit.prevent="sendRecoveryEmail">
              <input
                v-model="recoveryForm.recovery_email"
                type="email"
                :placeholder="$t('you@personal-email.com')"
                class="h-11 w-full rounded-lg border border-slate-300 bg-slate-50 px-4 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
              >
              <button
                type="submit"
                :disabled="recoveryForm.processing || !recoveryForm.recovery_email"
                class="h-11 shrink-0 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 transition hover:border-blue-400 hover:text-blue-700 disabled:opacity-60 dark:border-gray-600 dark:text-gray-200 dark:hover:border-blue-500/50 dark:hover:text-blue-400"
              >
                {{ recoveryForm.processing ? $t('Saving...') : $t('Update') }}
              </button>
            </form>
            <span v-if="recoveryForm.errors.recovery_email" class="block text-xs text-red-600 dark:text-red-400">{{ recoveryForm.errors.recovery_email }}</span>
          </div>

          <!-- First-time entry -->
          <form v-else class="space-y-4" @submit.prevent="sendRecoveryEmail">
            <label class="block">
              <span class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Recovery email address') }}</span>
              <input
                v-model="recoveryForm.recovery_email"
                type="email"
                :placeholder="$t('you@personal-email.com')"
                class="h-11 w-full rounded-lg border border-slate-300 bg-slate-50 px-4 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
              >
              <span v-if="recoveryForm.errors.recovery_email" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ recoveryForm.errors.recovery_email }}</span>
            </label>
            <div class="flex justify-end border-t border-slate-200 pt-5 dark:border-gray-800">
              <button
                type="submit"
                :disabled="recoveryForm.processing || !recoveryForm.recovery_email"
                class="flex h-11 items-center gap-2 rounded-lg bg-blue-900 px-5 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-blue-600 dark:hover:bg-blue-500"
              >
                {{ recoveryForm.processing ? $t('Sending...') : $t('Send verification link') }}
                <ArrowRight v-if="!recoveryForm.processing" class="h-4 w-4" />
              </button>
            </div>
          </form>
        </section>

        <!-- Step 3: Done -->
        <section v-else class="py-4 text-center">
          <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
            <Sparkles class="h-7 w-7" />
          </span>
          <h2 class="mt-4 text-lg font-bold">{{ $t("You're all set") }}</h2>
          <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500 dark:text-gray-400">
            {{ $t('Your teaching setup is saved and your recovery email is verified. You can add a bio, photo, CV and social links anytime from your profile.') }}
          </p>
          <div class="mt-6 flex items-center justify-center gap-2 text-sm font-semibold text-emerald-700 dark:text-emerald-400">
            <ShieldCheck class="h-4 w-4" />
            {{ $t('Recovery email verified') }}
          </div>
          <button
            type="button"
            class="mx-auto mt-6 flex h-11 items-center gap-2 rounded-lg bg-blue-900 px-6 text-sm font-semibold text-white transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500"
            @click="goToDashboard"
          >
            {{ $t('Go to dashboard') }}
            <ArrowRight class="h-4 w-4" />
          </button>
        </section>
      </div>
    </div>
  </main>
</template>
