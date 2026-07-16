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
  is_enabled: true,
})

function initFromProps() {
  form.tiers = (props.tiers ?? []).map((tier) => ({ duration_minutes: tier.duration_minutes }))
  form.reset_after_hours = props.resetAfterHours ?? 24
  form.is_enabled = props.isEnabled ?? true
}

watch(() => [props.tiers, props.resetAfterHours, props.isEnabled], initFromProps, { immediate: true })

function addTier() {
  const lastDuration = form.tiers.at(-1)?.duration_minutes ?? 1
  form.tiers.push({ duration_minutes: lastDuration })
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

// duration_minutes is the only value actually sent to the server - these
// just split it into readable Hours/Minutes fields (no seconds: nothing in
// the backend tracks lockout durations more precisely than a minute).
function hoursPart(tier) {
  return Math.floor(tier.duration_minutes / 60)
}

function minutesPart(tier) {
  return tier.duration_minutes % 60
}

function setHoursPart(tier, value) {
  const hours = Math.max(0, Math.min(168, Number(value) || 0))
  tier.duration_minutes = hours * 60 + minutesPart(tier)
}

function setMinutesPart(tier, value) {
  const minutes = Math.max(0, Math.min(59, Number(value) || 0))
  tier.duration_minutes = hoursPart(tier) * 60 + minutes
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
        <form class="space-y-6" @submit.prevent="submit">
          <div class="flex items-center justify-between gap-4 border-b border-slate-200 pb-6 dark:border-gray-800">
            <div>
              <h3 class="text-base font-bold text-slate-900 dark:text-gray-100">Account Lockout</h3>
              <p class="mt-1 text-sm text-slate-600 dark:text-gray-400">
                Turn off to stop banning accounts after repeated failed logins. The short per-IP
                rate limit below the login form still applies either way.
              </p>
            </div>
            <label class="relative inline-flex shrink-0 cursor-pointer items-center">
              <input v-model="form.is_enabled" type="checkbox" class="peer sr-only">
              <div class="h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-blue-900 dark:bg-gray-600 dark:peer-checked:bg-blue-600"></div>
              <div class="absolute left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></div>
            </label>
          </div>

          <div
            class="flex flex-col gap-6 border-b border-slate-200 pb-6 transition lg:flex-row dark:border-gray-800"
            :class="{ 'pointer-events-none opacity-40': !form.is_enabled }"
          >
            <div class="lg:w-1/2">
              <div class="mb-4 flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                  <h3 class="text-base font-bold text-slate-900 dark:text-gray-100">Lockout Tiers</h3>
                  <p class="mt-1 text-sm text-slate-600 dark:text-gray-400">
                    How long an account is banned each successive time it's locked out. The last tier's
                    duration applies to every offense beyond it.
                  </p>
                </div>
                <button type="button" class="shrink-0 text-sm font-semibold whitespace-nowrap text-blue-700 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300" @click="addTier">
                  + Add Tier
                </button>
              </div>

              <div v-for="(tier, i) in form.tiers" :key="i" class="mb-3 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                <div class="w-28">
                  <span class="mb-1 block text-xs font-semibold text-slate-600 dark:text-gray-400">Offense</span>
                  <p class="rounded-lg border border-transparent px-3 py-2.5 text-sm font-semibold text-slate-700 dark:text-gray-200">{{ ordinal(i + 1) }}</p>
                </div>
                <div class="min-w-[200px] flex-1">
                  <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-gray-400">Duration</label>
                  <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                      <input
                        :value="hoursPart(tier)"
                        type="number"
                        min="0"
                        max="168"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 pr-8 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                        @input="setHoursPart(tier, $event.target.value)"
                      >
                      <span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-xs font-semibold text-slate-400 dark:text-gray-500">h</span>
                    </div>
                    <div class="relative flex-1">
                      <input
                        :value="minutesPart(tier)"
                        type="number"
                        min="0"
                        max="59"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 pr-8 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                        @input="setMinutesPart(tier, $event.target.value)"
                      >
                      <span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-xs font-semibold text-slate-400 dark:text-gray-500">m</span>
                    </div>
                  </div>
                </div>
                <button
                  type="button"
                  :disabled="form.tiers.length <= 1"
                  class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-30 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-500/20"
                  @click="removeTier(i)"
                >
                  ✕
                </button>
              </div>
              <p v-if="form.errors.tiers" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ form.errors.tiers }}</p>
            </div>

            <div class="lg:w-1/2">
              <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">Reset After (hours)</label>
              <p class="mb-2 text-sm text-slate-600 dark:text-gray-400">
                If an account has no lockouts for this many hours, its offense count drops back to the first tier.
              </p>
              <input
                v-model.number="form.reset_after_hours"
                type="number"
                min="1"
                max="720"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
              >
              <p v-if="form.errors.reset_after_hours" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ form.errors.reset_after_hours }}</p>
            </div>
          </div>

          <div class="flex justify-end border-t border-slate-200 pt-6 dark:border-gray-800">
            <button type="submit" :disabled="form.processing" class="rounded-xl bg-blue-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500">
              {{ form.processing ? 'Saving...' : 'Save Settings' }}
            </button>
          </div>
        </form>
      </div>
    </section>
  </DashboardLayout>
</template>
