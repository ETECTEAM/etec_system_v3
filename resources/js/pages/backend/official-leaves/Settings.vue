<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { useForm } from '@inertiajs/vue3'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'
import { useI18n } from '../../../i18n'

const props = defineProps({ settings: Array })
const { t } = useI18n()

const form = useForm({})
props.settings.forEach(s => { form[s.key] = s.value })

function submit() { form.put('/dashboard/official-leaves/settings', { preserveScroll: true }) }

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Leave Settings', current: true },
]
</script>

<template>
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Super Admin" :title="$t('Leave Settings')" :description="$t('Configure system-wide leave and permission policies.')" />

      <div class="bg-white rounded-xl border border-slate-200 shadow-sm dark:bg-gray-900 dark:border-gray-800 p-6">
        <form @submit.prevent="submit" class="space-y-6 max-w-2xl">
          <div v-for="setting in settings" :key="setting.key">
            <label class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">{{ setting.label }}</label>
            <p class="text-xs text-slate-500 dark:text-gray-400 mb-2">{{ setting.description }}</p>
            <input v-model="form[setting.key]" type="number" :min="setting.min" :max="setting.max" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" />
            <p v-if="form.errors[setting.key]" class="text-red-500 text-xs mt-1">{{ form.errors[setting.key] }}</p>
          </div>
          <div class="flex items-center gap-4">
            <button type="submit" :disabled="form.processing" class="px-6 py-2.5 rounded-xl bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50 transition">
              {{ form.processing ? $t('Saving...') : $t('Save Settings') }}
            </button>
            <p v-if="form.recentlySuccessful" class="text-sm text-green-600 dark:text-green-400">{{ $t('Settings saved successfully.') }}</p>
          </div>
        </form>
      </div>
    </section>
  </DashboardLayout>
</template>
