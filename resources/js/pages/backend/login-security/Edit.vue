<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { watch } from 'vue'
import { useToast } from 'vue-toastification'
import { Clock, KeyRound, Plus, RotateCcw, Save, ShieldCheck, X } from '@lucide/vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'

const props = defineProps({
  tiers: Array,
  resetAfterHours: Number,
  isEnabled: Boolean,
  freeAttempts: Number,
})

const toast = useToast()
const page = usePage()

watch(() => page.props.flash, (flash) => {
  if (flash?.success) {
    toast.success(flash.success)
  } else if (flash?.error) {
    toast.error(flash.error)
  }
}, { deep: true })

const form = useForm({
  tiers: [],
  reset_after_hours: 24,
  reset_unit: 'hours',
  is_enabled: true,
  free_attempts: 5,
})

// Picks a display unit that shows the stored value as a whole number
// (24 hours reads as "1 day", not "0.04 days").
function unitFor(value, small, large) {
  return value >= 60 && value % 60 === 0 ? large : small
}

function initFromProps() {
  form.tiers = (props.tiers ?? []).map((tier) => ({
    duration_minutes: tier.duration_minutes,
    unit: unitFor(tier.duration_minutes, 'minutes', 'hours'),
  }))
  form.reset_after_hours = props.resetAfterHours ?? 24
  form.reset_unit = unitFor(props.resetAfterHours ?? 24, 'hours', 'days')
  form.free_attempts = props.freeAttempts ?? 5
}

watch(() => [props.tiers, props.resetAfterHours, props.isEnabled, props.freeAttempts], initFromProps, { immediate: true })

function addTier() {
  const lastDuration = form.tiers.at(-1)?.duration_minutes ?? 1
  form.tiers.push({ duration_minutes: lastDuration, unit: unitFor(lastDuration, 'minutes', 'hours') })
}

function removeTier(index) {
  if (form.tiers.length > 1) {
    form.tiers.splice(index, 1)
  }
}

function ordinal(n) {
  const suffixes = { 1: 'st', 2: 'nd', 3: 'rd' }

  return `${n}${suffixes[n] ?? 'th'}`
}

// duration_minutes is the only value actually sent to the server - unit is
// purely a display choice, converted back on every edit.
function tierValue(tier) {
  return tier.unit === 'hours' ? tier.duration_minutes / 60 : tier.duration_minutes
}

function setTierValue(tier, value) {
  const n = Math.max(0, Number(value) || 0)
  tier.duration_minutes = tier.unit === 'hours' ? n * 60 : n
}

function setTierUnit(tier, unit) {
  tier.unit = unit
}

function resetValue() {
  return form.reset_unit === 'days' ? form.reset_after_hours / 24 : form.reset_after_hours
}

function setResetValue(value) {
  const n = Math.max(0, Number(value) || 0)
  form.reset_after_hours = form.reset_unit === 'days' ? n * 24 : n
}

function setResetUnit(unit) {
  form.reset_unit = unit
}

function setFreeAttempts(value) {
  form.free_attempts = Math.max(1, Number(value) || 1)
}

function submit() {
  form.put('/dashboard/login-security', { preserveScroll: true })
}

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Login Security', current: true },
]
</script>

<template>
  <Head :title="$t('Login Security Settings')" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero
        eyebrow="Security"
        :title="$t('Login Security')"
        :description="$t('Configure how long an account is locked out after repeated failed login attempts, and how long the offense history is remembered.')"
      />

      <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-gray-800 dark:bg-gray-900">
        <form @submit.prevent="submit">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-3">
              <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-900 dark:bg-blue-500/10 dark:text-blue-400">
                <ShieldCheck class="h-5 w-5" />
              </span>
              <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-gray-100">{{ $t('Failed login settings') }}</h3>
                <p class="mt-1 max-w-md text-sm text-slate-500 italic dark:text-gray-400">
                  {{ $t('Controls how repeated failed logins are throttled and, past a point, blocked outright.') }}
                </p>
              </div>
            </div>

            <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-slate-200 py-2 pr-3.5 pl-3 dark:border-gray-800">
              <span class="text-sm font-semibold whitespace-nowrap text-slate-600 dark:text-gray-300">
                {{ $t('Lockout') }} {{ form.is_enabled ? $t('on') : $t('off') }}
              </span>
              <span class="relative inline-flex items-center">
                <input v-model="form.is_enabled" type="checkbox" class="peer sr-only">
                <span class="h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-blue-900 dark:bg-gray-600 dark:peer-checked:bg-blue-600"></span>
                <span class="absolute left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
              </span>
            </label>
          </div>

          <div class="my-6 border-t border-slate-200 dark:border-gray-800"></div>

          <div class="space-y-6 transition" :class="{ 'pointer-events-none opacity-40': !form.is_enabled }">
            <div>
              <h4 class="mb-4 text-xs font-bold tracking-wide text-slate-400 uppercase dark:text-gray-500">{{ $t('First lockout trigger') }}</h4>

              <div class="flex flex-wrap items-center gap-4">
                <div class="flex w-full items-center gap-3 sm:w-48 sm:shrink-0">
                  <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-gray-800 dark:text-gray-400">
                    <KeyRound class="h-3.5 w-3.5" />
                  </span>
                  <label class="text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Free wrong attempts') }}</label>
                </div>
                <div class="max-w-[140px] flex-1">
                  <input
                    :value="form.free_attempts"
                    type="number"
                    min="1"
                    class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                    @input="setFreeAttempts($event.target.value)"
                  >
                </div>
                <p class="w-full text-xs text-slate-500 italic sm:ml-52 sm:w-auto sm:flex-1 dark:text-gray-400">
                  {{ $t('Wrong passwords allowed before the first offense trips. After that, every single wrong attempt escalates straight to the next tier below - no more batching.') }}
                </p>
              </div>
              <p v-if="form.errors.free_attempts" class="mt-2 text-xs text-red-600 sm:ml-52 dark:text-red-400">{{ form.errors.free_attempts }}</p>
            </div>

            <div class="border-t border-slate-200 pt-6 dark:border-gray-800">
              <div class="mb-4 flex items-center justify-between gap-4">
                <h4 class="text-xs font-bold tracking-wide text-slate-400 uppercase dark:text-gray-500">{{ $t('Offense escalation') }}</h4>
                <button
                  type="button"
                  class="flex shrink-0 items-center gap-1.5 rounded-lg border border-dashed border-slate-300 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:border-blue-400 hover:bg-blue-50/60 dark:border-gray-700 dark:text-blue-400 dark:hover:border-blue-500/50 dark:hover:bg-blue-500/5"
                  @click="addTier"
                >
                  <Plus class="h-3.5 w-3.5" />
                  {{ $t('Add another offense tier') }}
                </button>
              </div>

              <div class="space-y-3">
                <div
                  v-for="(tier, i) in form.tiers"
                  :key="i"
                  class="-mx-3 flex flex-wrap items-center gap-4 rounded-xl px-3 py-1.5 transition hover:bg-slate-50 dark:hover:bg-gray-800/40"
                >
                  <div class="flex w-full items-center gap-3 sm:w-48 sm:shrink-0">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-50 text-xs font-bold text-blue-900 dark:bg-blue-500/10 dark:text-blue-400">
                      {{ i + 1 }}
                    </span>
                    <label class="text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t(':number offense', { number: i + 1 }) }}</label>
                  </div>
                  <div class="flex max-w-md flex-1 items-stretch">
                    <input
                      :value="tierValue(tier)"
                      type="number"
                      min="0"
                      class="w-full rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 px-3 py-2.5 text-sm outline-none focus:z-10 focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                      @input="setTierValue(tier, $event.target.value)"
                    >
                    <select
                      :value="tier.unit"
                      class="shrink-0 rounded-r-lg border border-slate-300 bg-slate-100 px-3 py-2.5 text-sm font-medium text-slate-600 outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"
                      @change="setTierUnit(tier, $event.target.value)"
                    >
                      <option value="minutes">{{ $t('Minutes') }}</option>
                      <option value="hours">{{ $t('Hours') }}</option>
                    </select>
                  </div>
                  <button
                    type="button"
                    :disabled="form.tiers.length <= 1"
                    class="shrink-0 rounded-lg p-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-transparent dark:text-gray-500 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                    @click="removeTier(i)"
                  >
                    <X class="h-4 w-4" />
                  </button>
                </div>
              </div>
              <p v-if="form.errors.tiers" class="mt-2 text-xs text-red-600 sm:ml-52 dark:text-red-400">{{ form.errors.tiers }}</p>
            </div>

            <div class="border-t border-slate-200 pt-6 dark:border-gray-800">
              <h4 class="mb-4 text-xs font-bold tracking-wide text-slate-400 uppercase dark:text-gray-500">{{ $t('Offense history') }}</h4>

              <div class="flex flex-wrap items-center gap-4">
                <div class="flex w-full items-center gap-3 sm:w-48 sm:shrink-0">
                  <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-gray-800 dark:text-gray-400">
                    <RotateCcw class="h-3.5 w-3.5" />
                  </span>
                  <label class="text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Reset history after') }}</label>
                </div>
                <div class="flex max-w-md flex-1 items-stretch">
                  <input
                    :value="resetValue()"
                    type="number"
                    min="0"
                    class="w-full rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 px-3 py-2.5 text-sm outline-none focus:z-10 focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                    @input="setResetValue($event.target.value)"
                  >
                  <select
                    :value="form.reset_unit"
                    class="shrink-0 rounded-r-lg border border-slate-300 bg-slate-100 px-3 py-2.5 text-sm font-medium text-slate-600 outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"
                    @change="setResetUnit($event.target.value)"
                  >
                    <option value="hours">{{ $t('Hours') }}</option>
                    <option value="days">{{ $t('Days') }}</option>
                  </select>
                </div>
              </div>
              <p v-if="form.errors.reset_after_hours" class="mt-2 text-xs text-red-600 sm:ml-52 dark:text-red-400">{{ form.errors.reset_after_hours }}</p>
            </div>
          </div>

          <div class="mt-8 flex items-center justify-end gap-3 border-t border-slate-200 pt-6 dark:border-gray-800">
            <Clock v-if="form.processing" class="h-4 w-4 animate-spin text-slate-400 dark:text-gray-500" />
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
