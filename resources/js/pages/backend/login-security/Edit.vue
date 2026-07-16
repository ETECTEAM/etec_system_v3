<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { watch } from 'vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'

const props = defineProps({
  tiers: Array,
  resetAfterHours: Number,
  isEnabled: Boolean,
})

const form = useForm({
  tiers: [],
  reset_after_hours: 24,
  reset_unit: 'hours',
  is_enabled: true,
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
}

watch(() => [props.tiers, props.resetAfterHours, props.isEnabled], initFromProps, { immediate: true })

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

function submit() {
  form.put('/dashboard/login-security', { preserveScroll: true })
}

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Login Security', current: true },
]
</script>

<template>
  <Head title="Login Security Settings" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero
        eyebrow="Security"
        title="Login Security"
        description="Configure how long an account is locked out after repeated failed login attempts, and how long the offense history is remembered."
      />

      <div v-if="$page.props.flash?.success" class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400">
        {{ $page.props.flash.success }}
      </div>

      <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-gray-800 dark:bg-gray-900">
        <form @submit.prevent="submit">
          <h3 class="text-base font-bold text-slate-900 dark:text-gray-100">Failed login settings</h3>
          <p class="mt-1 mb-6 text-sm text-slate-500 italic dark:text-gray-400">
            Controls how repeated failed logins are throttled and, past a point, blocked outright.
          </p>
          <div class="border-t border-slate-200 dark:border-gray-800"></div>

          <div class="mt-6 space-y-5">
            <div class="flex flex-wrap items-center gap-4">
              <label class="w-full text-sm font-semibold text-slate-700 sm:w-48 sm:shrink-0 dark:text-gray-200">Account lockout</label>
              <label class="relative inline-flex cursor-pointer items-center">
                <input v-model="form.is_enabled" type="checkbox" class="peer sr-only">
                <div class="h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-blue-900 dark:bg-gray-600 dark:peer-checked:bg-blue-600"></div>
                <div class="absolute left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></div>
              </label>
            </div>

            <div class="space-y-5 transition" :class="{ 'pointer-events-none opacity-40': !form.is_enabled }">
              <div v-for="(tier, i) in form.tiers" :key="i" class="flex flex-wrap items-center gap-4">
                <label class="w-full text-sm font-semibold text-slate-700 sm:w-48 sm:shrink-0 dark:text-gray-200">{{ ordinal(i + 1) }} offense duration</label>
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
                    <option value="minutes">Minutes</option>
                    <option value="hours">Hours</option>
                  </select>
                </div>
                <button
                  type="button"
                  :disabled="form.tiers.length <= 1"
                  class="shrink-0 rounded-lg px-2 py-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-transparent dark:text-gray-500 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                  @click="removeTier(i)"
                >
                  ✕
                </button>
              </div>
              <p v-if="form.errors.tiers" class="text-xs text-red-600 sm:ml-52 dark:text-red-400">{{ form.errors.tiers }}</p>

              <div class="flex flex-wrap items-center gap-4">
                <div class="w-full sm:w-48 sm:shrink-0"></div>
                <button type="button" class="text-sm font-semibold text-blue-700 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300" @click="addTier">
                  + Add another offense tier
                </button>
              </div>

              <div class="flex flex-wrap items-center gap-4">
                <label class="w-full text-sm font-semibold text-slate-700 sm:w-48 sm:shrink-0 dark:text-gray-200">Reset offense history after</label>
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
                    <option value="hours">Hours</option>
                    <option value="days">Days</option>
                  </select>
                </div>
              </div>
              <p v-if="form.errors.reset_after_hours" class="text-xs text-red-600 sm:ml-52 dark:text-red-400">{{ form.errors.reset_after_hours }}</p>
            </div>
          </div>

          <div class="mt-8 flex justify-end border-t border-slate-200 pt-6 dark:border-gray-800">
            <button type="submit" :disabled="form.processing" class="rounded-xl bg-blue-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500">
              {{ form.processing ? 'Saving...' : 'Save Settings' }}
            </button>
          </div>
        </form>
      </div>
    </section>
  </DashboardLayout>
</template>
