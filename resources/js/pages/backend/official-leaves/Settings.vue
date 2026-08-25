<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import { useI18n } from '@/i18n'

const props = defineProps({
  settings: {
    type: Array,
    default: () => [],
  },
})

const { t } = useI18n()
const toast = useToast()

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Official Leave', href: '/dashboard/official-leaves' },
  { label: 'Settings', current: true },
]

// One Inertia form keyed by setting key; values are numbers from the backend.
const form = useForm(Object.fromEntries(props.settings.map((s) => [s.key, s.value])))

function submit() {
  form.put('/dashboard/official-leaves/settings', {
    preserveScroll: true,
    onSuccess: () => toast.success(t('Leave settings updated. Changes apply system-wide.')),
    onError: () => toast.error(t('Failed to update settings. Please review the values.')),
  })
}
</script>

<template>
  <Head :title="$t('Leave Settings')" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero
        eyebrow="Official Leave"
        :title="$t('Leave Settings')"
        :description="$t('System-wide rules for permissions, blocks, and QR lifetime.')"
      />

      <form class="max-w-2xl space-y-4" novalidate @submit.prevent="submit">
        <div
          v-for="setting in settings"
          :key="setting.key"
          class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900"
        >
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
              <p class="font-black text-slate-950 dark:text-gray-100">{{ $t(setting.label) }}</p>
              <p class="mt-1 text-xs font-semibold leading-relaxed text-slate-500 dark:text-gray-400">{{ $t(setting.description ?? '') }}</p>
              <p class="mt-1 font-mono text-[11px] font-medium text-slate-400">{{ setting.key }}</p>
            </div>

            <div class="shrink-0">
              <input
                v-model.number="form[setting.key]"
                type="number"
                :min="setting.min ?? 0"
                :max="setting.max ?? undefined"
                class="h-11 w-28 rounded-xl border border-slate-200 bg-white px-3 text-center text-sm font-black tabular-nums outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
              />
              <p v-if="form.errors[setting.key]" class="mt-1 text-xs font-bold text-rose-600">{{ form.errors[setting.key] }}</p>
              <p class="mt-1 text-center text-[11px] font-semibold text-slate-400">min {{ setting.min ?? 0 }} · max {{ setting.max ?? '∞' }}</p>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3">
          <button
            type="button"
            class="h-10 rounded-lg bg-slate-100 px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-200"
            @click="form.reset()"
          >
            {{ $t('Reset') }}
          </button>

          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex h-10 items-center gap-2 rounded-lg bg-blue-600 px-5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70"
          >
            {{ form.processing ? $t('Saving...') : $t('Save changes') }}
          </button>
        </div>
      </form>
    </section>
  </DashboardLayout>
</template>
