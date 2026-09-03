<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { useForm } from '@inertiajs/vue3'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'
import { useI18n } from '../../../i18n'

const props = defineProps({
  settings: { type: Array, default: () => [] },
})

const { t } = useI18n()

const initial = {}
for (const row of props.settings) {
  initial[row.key] = row.type === 'number' ? Number(row.value) : row.value
}
const form = useForm(initial)

function save() {
  form.put('/dashboard/absence-blocks/settings', { preserveScroll: true })
}

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Attendance Rule Settings', current: true },
]
</script>

<template>
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Attendance" :title="$t('Attendance Rule Settings')" :description="$t('Fallback limits for auto-locking a student who misses too many sessions — used when no Attendance Rule matches.')" />

      <form @submit.prevent="save" class="max-w-2xl space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div v-for="row in settings" :key="row.key">
          <label class="block text-sm font-semibold text-slate-800 dark:text-gray-200">{{ $t(row.label) }}</label>
          <p class="mb-2 text-xs text-slate-500 dark:text-gray-400">{{ $t(row.description) }}</p>
          <input
            v-if="row.type === 'number'"
            v-model.number="form[row.key]"
            type="number"
            :min="row.min ?? undefined"
            :max="row.max ?? undefined"
            class="w-40 rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
          >
          <input
            v-else
            v-model="form[row.key]"
            type="date"
            class="w-52 rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
          >
          <p v-if="form.errors[row.key]" class="mt-1 text-xs text-red-600">{{ form.errors[row.key] }}</p>
        </div>

        <div class="flex justify-end border-t border-slate-200 pt-4 dark:border-gray-800">
          <button type="submit" :disabled="form.processing" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
            {{ form.processing ? $t('Saving...') : $t('Save settings') }}
          </button>
        </div>
      </form>
    </section>
  </DashboardLayout>
</template>
