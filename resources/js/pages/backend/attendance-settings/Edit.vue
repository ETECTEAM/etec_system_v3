<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { computed, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { BellRing, Bot, PencilLine, Save, ShieldAlert, Timer, UserCheck, Zap } from '@lucide/vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'
import { SelectSearch } from '../../../components/ui/select-search'
import { useConfirm } from '@/composables/useConfirm'
import { useI18n } from '@/i18n'

const props = defineProps({
  settings: Object,
  shortestClassDurationMinutes: {
    type: Number,
    default: null,
  },
})

const toast = useToast()
const page = usePage()
const { confirm } = useConfirm()
const { t } = useI18n()

watch(() => page.props.flash, (flash) => {
  if (flash?.success) toast.success(flash.success)
  else if (flash?.error) toast.error(flash.error)
}, { deep: true })

const form = useForm({
  auto_record_enabled: props.settings.enabled,
  auto_record_grace_minutes: props.settings.graceMinutes,
  auto_record_default_status: props.settings.defaultStatus,
  auto_record_notify_instructor: props.settings.notifyInstructor,
  auto_record_allow_override: props.settings.allowOverride,
  auto_record_override_hours: props.settings.overrideHours,
})

const statusOptions = [
  { value: 'present', label: 'Present' },
  { value: 'pending', label: 'Pending' },
]

// Matches this page's number-input styling so the searchable select sits in the same visual row.
const selectClass = 'flex w-full items-center justify-between rounded-lg border border-slate-300 bg-slate-50 px-3 py-2.5 text-left text-sm transition focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20 dark:disabled:bg-gray-700 dark:disabled:text-gray-500'

// "A class starting 09:00 will auto-record at 09:15" - one illustrative example, not
// tied to any real class, so the effect of the number is obvious before saving.
const previewTime = computed(() => {
  const grace = Number(form.auto_record_grace_minutes) || 0
  const total = 9 * 60 + grace
  const hh = String(Math.floor(total / 60) % 24).padStart(2, '0')
  const mm = String(total % 60).padStart(2, '0')

  return `${hh}:${mm}`
})

async function toggleEnabled(event) {
  const turningOff = form.auto_record_enabled && !event.target.checked

  if (!turningOff) {
    form.auto_record_enabled = event.target.checked
    return
  }

  // Reverts the checkbox instead of leaving it visually toggled while the confirm
  // is still open, then re-applies it only if the admin actually confirms.
  event.target.checked = true

  const ok = await confirm({
    title: t('Turn off auto-record attendance?'),
    message: t('Once off, a class the instructor forgets to submit will have no attendance data at all - nothing records it on their behalf.'),
    confirmText: t('Turn Off'),
    danger: true,
  })

  if (ok) {
    form.auto_record_enabled = false
  }
}

function submit() {
  form.put('/dashboard/attendance-settings', { preserveScroll: true })
}

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Attendance Settings', current: true },
]
</script>

<template>
  <Head :title="$t('Attendance Settings')" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero
        eyebrow="Attendance"
        :title="$t('Auto-Record Attendance')"
        :description="$t('Configure whether attendance is recorded automatically when an instructor forgets to submit it.')"
      />

      <div class="grid gap-6 lg:grid-cols-3">
        <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7 lg:col-span-2 dark:border-gray-800 dark:bg-gray-900">
          <div class="flex flex-wrap items-start justify-between gap-5">
            <div class="flex items-start gap-4">
              <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-900 dark:bg-blue-500/10 dark:text-blue-400">
                <Bot class="h-5 w-5" />
              </span>
              <div>
                <div class="flex flex-wrap items-center gap-2.5">
                  <h3 class="text-base font-bold text-slate-900 dark:text-gray-100">{{ $t('Auto-record attendance') }}</h3>
                  <span
                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-bold tracking-wide uppercase"
                    :class="form.auto_record_enabled
                      ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400'
                      : 'bg-slate-100 text-slate-500 dark:bg-gray-800 dark:text-gray-400'"
                  >
                    <span class="h-1.5 w-1.5 rounded-full" :class="form.auto_record_enabled ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                    {{ form.auto_record_enabled ? $t('on') : $t('off') }}
                  </span>
                </div>
                <p class="mt-1.5 max-w-md text-sm text-slate-500 dark:text-gray-400">
                  {{ $t('When on, a class with no attendance submitted by the instructor is recorded automatically after the grace period below.') }}
                </p>
              </div>
            </div>

            <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-slate-200 py-2 pr-3.5 pl-3 dark:border-gray-800">
              <span class="text-sm font-semibold whitespace-nowrap text-slate-600 dark:text-gray-300">
                {{ form.auto_record_enabled ? $t('on') : $t('off') }}
              </span>
              <span class="relative inline-flex items-center">
                <input :checked="form.auto_record_enabled" type="checkbox" class="peer sr-only" @change="toggleEnabled">
                <span class="h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-blue-900 dark:bg-gray-600 dark:peer-checked:bg-blue-600"></span>
                <span class="absolute left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
              </span>
            </label>
          </div>
        </div>

        <div
          class="relative w-full overflow-hidden rounded-2xl bg-gradient-to-br from-blue-900 via-blue-800 to-blue-700 p-6 shadow-lg sm:p-7 dark:from-blue-950 dark:via-blue-900 dark:to-blue-800"
          :class="{ 'opacity-50': !form.auto_record_enabled }"
        >
          <div class="absolute -top-6 -right-6 h-24 w-24 rounded-full bg-white/10"></div>
          <div class="relative">
            <div class="flex items-center gap-2 text-blue-100">
              <Zap class="h-4 w-4" />
              <p class="text-[11px] font-black tracking-[0.18em] uppercase">{{ $t('Auto-record preview') }}</p>
            </div>
            <p class="mt-4 text-5xl font-black tracking-tight text-white">{{ previewTime }}</p>
            <p class="mt-2 text-sm font-medium text-blue-100/90">
              {{ $t('A class starting 09:00 will auto-record at') }} <span class="font-bold text-white">{{ previewTime }}</span>.
            </p>
          </div>
        </div>
      </div>

      <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-gray-800 dark:bg-gray-900">
        <form @submit.prevent="submit">
          <div class="space-y-8 transition" :class="{ 'pointer-events-none opacity-40': !form.auto_record_enabled }">
            <div>
              <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-300">
                  <Timer class="h-4 w-4" />
                </span>
                <div>
                  <h4 class="text-sm font-bold text-slate-900 dark:text-gray-100">{{ $t('Grace period') }}</h4>
                  <p class="text-xs text-slate-400 dark:text-gray-500">{{ $t('How long the instructor has before the system records attendance for them.') }}</p>
                </div>
              </div>

              <div class="mt-4 flex flex-wrap items-center gap-3 pl-0 sm:pl-12">
                <div class="flex w-full items-center gap-3 sm:w-48 sm:shrink-0">
                  <label class="text-sm font-semibold whitespace-nowrap text-slate-700 dark:text-gray-200">{{ $t('Grace minutes') }}</label>
                </div>
                <div class="max-w-[140px] flex-1">
                  <input
                    v-model.number="form.auto_record_grace_minutes"
                    type="number"
                    min="1"
                    class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                  >
                </div>
                <p v-if="form.errors.auto_record_grace_minutes" class="text-xs font-semibold text-red-600 dark:text-red-400">{{ form.errors.auto_record_grace_minutes }}</p>
                <p v-else-if="shortestClassDurationMinutes" class="text-xs text-slate-400 dark:text-gray-500">
                  {{ $t('Shortest configured class runs') }} {{ shortestClassDurationMinutes }} {{ $t('minutes') }}.
                </p>
              </div>
            </div>

            <div class="border-t border-slate-200 pt-8 dark:border-gray-800">
              <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                  <UserCheck class="h-4 w-4" />
                </span>
                <div>
                  <h4 class="text-sm font-bold text-slate-900 dark:text-gray-100">{{ $t('Auto-recorded status') }}</h4>
                  <p class="text-xs text-slate-400 dark:text-gray-500">{{ $t('The status a student gets when the system records their attendance.') }}</p>
                </div>
              </div>

              <div class="mt-4 flex flex-wrap items-center gap-3 pl-0 sm:pl-12">
                <div class="w-full sm:w-64">
                  <SelectSearch
                    v-model="form.auto_record_default_status"
                    :options="statusOptions"
                    :placeholder="$t('Select status')"
                    :button-class="selectClass"
                    :disabled="!form.auto_record_enabled"
                  />
                </div>
                <p class="text-xs text-slate-400 italic dark:text-gray-500">
                  {{ $t('Never "absent" - a student must not fail because an instructor forgot to submit.') }}
                </p>
              </div>
            </div>

            <div class="border-t border-slate-200 pt-8 dark:border-gray-800">
              <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                  <PencilLine class="h-4 w-4" />
                </span>
                <div>
                  <h4 class="text-sm font-bold text-slate-900 dark:text-gray-100">{{ $t('Instructor correction') }}</h4>
                  <p class="text-xs text-slate-400 dark:text-gray-500">{{ $t('Controls whether an instructor can fix an auto-recorded session.') }}</p>
                </div>
              </div>

              <div class="mt-4 space-y-3 pl-0 sm:pl-12">
                <label class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3.5 transition hover:border-slate-300 dark:border-gray-800 dark:bg-gray-950/40 dark:hover:border-gray-700">
                  <span class="text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Allow instructor override') }}</span>
                  <span class="relative inline-flex items-center">
                    <input v-model="form.auto_record_allow_override" type="checkbox" class="peer sr-only">
                    <span class="h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-blue-900 dark:bg-gray-600 dark:peer-checked:bg-blue-600"></span>
                    <span class="absolute left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                  </span>
                </label>

                <div class="flex flex-wrap items-center gap-3" :class="{ 'pointer-events-none opacity-40': !form.auto_record_allow_override }">
                  <div class="w-full sm:w-48">
                    <label class="text-sm font-semibold whitespace-nowrap text-slate-700 dark:text-gray-200">{{ $t('Override window (hours)') }}</label>
                  </div>
                  <div class="max-w-[140px] flex-1">
                    <input
                      v-model.number="form.auto_record_override_hours"
                      type="number"
                      min="1"
                      class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                    >
                  </div>
                  <p v-if="form.errors.auto_record_override_hours" class="text-xs font-semibold text-red-600 dark:text-red-400">{{ form.errors.auto_record_override_hours }}</p>
                </div>

                <label class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3.5 transition hover:border-slate-300 dark:border-gray-800 dark:bg-gray-950/40 dark:hover:border-gray-700">
                  <span class="flex items-center gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400">
                      <BellRing class="h-4 w-4" />
                    </span>
                    <span class="text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Notify instructor') }}</span>
                  </span>
                  <span class="relative inline-flex items-center">
                    <input v-model="form.auto_record_notify_instructor" type="checkbox" class="peer sr-only">
                    <span class="h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-blue-900 dark:bg-gray-600 dark:peer-checked:bg-blue-600"></span>
                    <span class="absolute left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                  </span>
                </label>
              </div>
            </div>

            <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-semibold text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
              <ShieldAlert class="h-4 w-4 shrink-0" />
              {{ $t('A class already over is never auto-recorded after the fact - it is marked missed instead, for an admin to review.') }}
            </div>
          </div>

          <div class="mt-8 flex items-center justify-end gap-3 border-t border-slate-200 pt-6 dark:border-gray-800">
            <button
              type="submit"
              :disabled="form.processing"
              class="flex items-center gap-2 rounded-xl bg-blue-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500"
            >
              <Save class="h-4 w-4" />
              {{ form.processing ? $t('Saving...') : $t('Save Settings') }}
            </button>
          </div>
        </form>
      </div>
    </section>
  </DashboardLayout>
</template>
