<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'
import { useI18n } from '../../../i18n'

const props = defineProps({
  rules: { type: Array, default: () => [] },
  canManage: { type: Boolean, default: false },
})

const { t } = useI18n()

const showModal = ref(false)
const editingId = ref(null)
const deleteTarget = ref(null)

const form = useForm({
  rule_type: 'absence',
  limit_count: 3,
  period_type: 'both',
  start_date: '2026-04-01',
  is_active: true,
})

function openCreate() {
  editingId.value = null
  form.defaults({ rule_type: 'absence', limit_count: 3, period_type: 'both', start_date: '2026-04-01', is_active: true })
  form.reset()
  form.clearErrors()
  showModal.value = true
}

function openEdit(rule) {
  editingId.value = rule.id
  form.rule_type = rule.rule_type
  form.limit_count = rule.limit_count
  form.period_type = rule.period_type
  form.start_date = String(rule.start_date).slice(0, 10)
  form.is_active = !!rule.is_active
  form.clearErrors()
  showModal.value = true
}

function save() {
  const opts = { preserveScroll: true, onSuccess: () => { showModal.value = false } }
  if (editingId.value) {
    form.put(`/dashboard/absence-blocks/rules/${editingId.value}`, opts)
  } else {
    form.post('/dashboard/absence-blocks/rules', opts)
  }
}

function toggle(rule) {
  router.patch(`/dashboard/absence-blocks/rules/${rule.id}/toggle`, {}, { preserveScroll: true })
}

function confirmDelete() {
  router.delete(`/dashboard/absence-blocks/rules/${deleteTarget.value.id}`, {
    preserveScroll: true,
    onSuccess: () => { deleteTarget.value = null },
  })
}

const periodLabels = {
  both: 'All classes',
  week: 'Weekday classes',
  month: 'Weekend classes',
}

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Attendance Rules', current: true },
]
</script>

<template>
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <div class="flex flex-wrap items-start justify-between gap-3">
        <PageHero eyebrow="Attendance" :title="$t('Attendance Rules')" :description="$t('Numeric thresholds that drive the absence-block workflow. Newest active rule wins.')" />
        <button v-if="canManage" @click="openCreate" class="mt-1 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
          + {{ $t('New rule') }}
        </button>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-800">
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Type') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Limit') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Applies to') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Start date') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Active') }}</th>
                <th class="px-6 py-3 text-right text-slate-600 dark:text-gray-300">{{ $t('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="rule in rules" :key="rule.id" class="border-t border-slate-200 hover:bg-slate-50 dark:border-gray-800 dark:hover:bg-gray-800">
                <td class="px-6 py-4">
                  <span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold"
                    :class="rule.rule_type === 'absence' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-400'">
                    {{ $t(rule.rule_type) }}
                  </span>
                </td>
                <td class="px-6 py-4 font-medium text-slate-900 dark:text-gray-100">{{ rule.limit_count }}</td>
                <td class="px-6 py-4 text-slate-600 dark:text-gray-400">{{ $t(periodLabels[rule.period_type]) }}</td>
                <td class="px-6 py-4 text-slate-600 dark:text-gray-400">{{ String(rule.start_date).slice(0, 10) }}</td>
                <td class="px-6 py-4">
                  <button
                    @click="canManage && toggle(rule)"
                    :disabled="!canManage"
                    class="relative inline-flex h-6 w-11 items-center rounded-full transition"
                    :class="rule.is_active ? 'bg-green-500' : 'bg-slate-300 dark:bg-gray-600'"
                  >
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition" :class="rule.is_active ? 'translate-x-6' : 'translate-x-1'" />
                  </button>
                </td>
                <td class="px-6 py-4 space-x-2 text-right">
                  <template v-if="canManage">
                    <button @click="openEdit(rule)" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">{{ $t('Edit') }}</button>
                    <button @click="deleteTarget = rule" class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs text-red-700 hover:bg-red-100 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-400">{{ $t('Delete') }}</button>
                  </template>
                  <span v-else class="text-xs text-slate-400">—</span>
                </td>
              </tr>
              <tr v-if="!rules.length">
                <td colspan="6" class="py-10 text-center text-slate-500 dark:text-gray-400">{{ $t('No rules yet. Defaults from Settings apply.') }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- Create / Edit -->
    <transition name="fade">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" @click.self="showModal = false">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-lg dark:bg-gray-900">
          <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-gray-100">{{ editingId ? $t('Edit rule') : $t('New rule') }}</h3>
          <form @submit.prevent="save" class="space-y-4">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-gray-300">{{ $t('Rule type') }}</label>
              <select v-model="form.rule_type" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                <option value="absence">{{ $t('absence') }}</option>
                <option value="permission">{{ $t('permission') }}</option>
              </select>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-gray-300">{{ $t('Limit count') }}</label>
              <input v-model.number="form.limit_count" type="number" min="1" max="100" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
              <p v-if="form.errors.limit_count" class="mt-1 text-xs text-red-600">{{ form.errors.limit_count }}</p>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-gray-300">{{ $t('Applies to') }}</label>
              <select v-model="form.period_type" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                <option value="both">{{ $t('All classes') }}</option>
                <option value="week">{{ $t('Weekday classes') }}</option>
                <option value="month">{{ $t('Weekend classes') }}</option>
              </select>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-gray-300">{{ $t('Start date') }}</label>
              <input v-model="form.start_date" type="date" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-gray-300">
              <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300">
              {{ $t('Active') }}
            </label>
            <div class="flex justify-end gap-3 pt-2">
              <button type="button" @click="showModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm dark:border-gray-600 dark:text-gray-300">{{ $t('Cancel') }}</button>
              <button type="submit" :disabled="form.processing" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">{{ $t('Save') }}</button>
            </div>
          </form>
        </div>
      </div>
    </transition>

    <!-- Delete -->
    <transition name="fade">
      <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" @click.self="deleteTarget = null">
        <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-lg dark:bg-gray-900">
          <h3 class="mb-2 text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Delete rule?') }}</h3>
          <p class="mb-6 text-sm text-slate-600 dark:text-gray-400">{{ $t('Existing blocks are not affected.') }}</p>
          <div class="flex justify-end gap-3">
            <button @click="deleteTarget = null" class="rounded-lg border border-slate-300 px-4 py-2 text-sm dark:border-gray-600 dark:text-gray-300">{{ $t('Cancel') }}</button>
            <button @click="confirmDelete" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">{{ $t('Delete') }}</button>
          </div>
        </div>
      </div>
    </transition>
  </DashboardLayout>
</template>
