<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { watch } from 'vue'
import { KeyRound, Save } from '@lucide/vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'

const props = defineProps({
  isEnabled: Boolean,
})

const form = useForm({
  is_enabled: true,
})

watch(() => props.isEnabled, (value) => {
  form.is_enabled = value ?? true
}, { immediate: true })

function submit() {
  form.put('/dashboard/otp-settings', { preserveScroll: true })
}

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'OTP Verification', current: true },
]
</script>

<template>
  <Head :title="$t('OTP Verification Settings')" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero
        eyebrow="Security"
        :title="$t('OTP Verification')"
        :description="$t('Controls whether instructor self-registration requires a Telegram OTP code before the account activates.')"
      />

      <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-gray-800 dark:bg-gray-900">
        <form @submit.prevent="submit">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-3">
              <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-900 dark:bg-blue-500/10 dark:text-blue-400">
                <KeyRound class="h-5 w-5" />
              </span>
              <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-gray-100">{{ $t('Require OTP on registration') }}</h3>
                <p class="mt-1 max-w-md text-sm text-slate-500 italic dark:text-gray-400">
                  {{ $t('When off, new instructor accounts are created and activated immediately - no OTP code needed. Registration is still recorded and tracked either way; a captcha challenge on the form keeps blocking automated signups regardless of this setting.') }}
                </p>
              </div>
            </div>

            <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-slate-200 py-2 pr-3.5 pl-3 dark:border-gray-800">
              <span class="text-sm font-semibold whitespace-nowrap text-slate-600 dark:text-gray-300">
                {{ form.is_enabled ? $t('Required') : $t('Skipped') }}
              </span>
              <span class="relative inline-flex items-center">
                <input v-model="form.is_enabled" type="checkbox" class="peer sr-only">
                <span class="h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-blue-900 dark:bg-gray-600 dark:peer-checked:bg-blue-600"></span>
                <span class="absolute left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
              </span>
            </label>
          </div>
          <p v-if="form.errors.is_enabled" class="mt-2 text-xs text-red-600 dark:text-red-400">{{ form.errors.is_enabled }}</p>

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
