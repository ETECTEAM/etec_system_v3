<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { watch } from 'vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'
import { SelectSearch } from '@/components/ui/select-search'

const selectButtonClass = 'flex w-full items-center justify-between rounded-xl border border-slate-300 bg-white px-4 py-3 text-left text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20'
const blockSelectButtonClass = 'flex w-full items-center justify-between rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-left text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20'

const employmentTypeOptions = [
  { value: 'full_time', label: 'Full Time' },
  { value: 'part_time', label: 'Part Time' },
]

const activeOptions = [
  { value: '1', label: 'Yes' },
  { value: '0', label: 'No option' },
]

const dayOptions = [
  { value: '1', label: 'Monday' },
  { value: '2', label: 'Tuesday' },
  { value: '3', label: 'Wednesday' },
  { value: '4', label: 'Thursday' },
  { value: '5', label: 'Friday' },
  { value: '6', label: 'Saturday' },
  { value: '7', label: 'Sunday' },
]

const props = defineProps({
  template: Object,
})

const form = useForm({
  name: '',
  code: '',
  employment_type: '',
  description: '',
  is_active: true,
  blocks: [],
})

function initFromTemplate(tpl) {
  if (!tpl) return
  form.name = tpl.name
  form.code = tpl.code
  form.employment_type = tpl.employment_type ?? ''
  form.description = tpl.description ?? ''
  form.is_active = tpl.is_active
  form.blocks = (tpl.blocks ?? []).map((b) => ({
    day_of_week: b.day_of_week,
    period: b.period ?? '',
    start_time: b.start_time.slice(0, 5),
    end_time: b.end_time.slice(0, 5),
  }))
}

watch(() => props.template, initFromTemplate, { immediate: true })

function addBlock() {
  form.blocks.push({ day_of_week: 1, period: 'daytime', start_time: '08:00', end_time: '17:00' })
}

function removeBlock(index) {
  if (form.blocks.length > 1) {
    form.blocks.splice(index, 1)
  }
}

function submit() {
  form.put(`/dashboard/shift-templates/${props.template.id}`, {
    preserveScroll: true,
  })
}

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Shift Templates', href: '/dashboard/shift-templates' },
  { label: 'Edit', current: true },
]
</script>

<template>
  <Head :title="$t('Edit Shift Template')" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Schedule Management" :title="$t('Edit Shift Template')" description="Update shift template information and blocks." />

      <div v-if="$page.props.flash?.success" class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400">
        {{ $page.props.flash.success }}
      </div>

      <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-gray-800 dark:bg-gray-900">
        <form @submit.prevent="submit" class="space-y-6">
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Name') }} <span class="text-red-500">*</span></label>
              <input v-model="form.name" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20" :placeholder="$t('e.g. Morning & Afternoon')" />
              <p v-if="form.errors.name" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
            </div>
            <div>
              <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Code') }} <span class="text-red-500">*</span></label>
              <input v-model="form.code" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20" :placeholder="$t('e.g. morning_afternoon')" />
              <p v-if="form.errors.code" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ form.errors.code }}</p>
            </div>
            <div>
              <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Employment Type') }}</label>
              <SelectSearch
                v-model="form.employment_type"
                :options="employmentTypeOptions"
                placeholder="—"
                :button-class="selectButtonClass"
              />
            </div>
            <div>
              <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Active') }}</label>
              <SelectSearch
                :model-value="form.is_active ? '1' : '0'"
                :options="activeOptions"
                :button-class="selectButtonClass"
                @update:model-value="value => form.is_active = value === '1'"
              />
            </div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Description') }}</label>
            <textarea v-model="form.description" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20" rows="2" :placeholder="$t('Optional description')"></textarea>
          </div>

          <div class="border-t border-slate-200 pt-6 dark:border-gray-800">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-base font-bold text-slate-900 dark:text-gray-100">{{ $t('Time Blocks') }} <span class="text-red-500 dark:text-red-400">*</span></h3>
              <button type="button" @click="addBlock" class="text-sm font-semibold text-blue-700 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">{{ $t('+ Add Block') }}</button>
            </div>

            <div v-for="(block, i) in form.blocks" :key="i" class="flex flex-wrap items-end gap-3 mb-3 p-4 rounded-xl bg-slate-50 border border-slate-200 dark:bg-gray-800 dark:border-gray-700">
              <div class="flex-1 min-w-[130px]">
                <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-gray-400">{{ $t('Day') }}</label>
                <SelectSearch
                  :model-value="String(block.day_of_week)"
                  :options="dayOptions"
                  :button-class="blockSelectButtonClass"
                  @update:model-value="value => block.day_of_week = Number(value)"
                />
              </div>
              <div class="flex-1 min-w-[110px]">
                <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-gray-400">{{ $t('Period') }}</label>
                <input v-model="block.period" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20" :placeholder="$t('e.g. morning')" />
              </div>
              <div class="w-[120px]">
                <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-gray-400">{{ $t('Start') }}</label>
                <input v-model="block.start_time" type="time" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20" />
              </div>
              <div class="w-[120px]">
                <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-gray-400">{{ $t('End') }}</label>
                <input v-model="block.end_time" type="time" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20" />
              </div>
              <button type="button" @click="removeBlock(i)" :disabled="form.blocks.length <= 1" class="px-3 py-2.5 text-sm rounded-lg border border-rose-200 bg-rose-50 font-semibold text-rose-700 hover:bg-rose-100 disabled:opacity-30 disabled:cursor-not-allowed dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-500/20">
                ✕
              </button>
            </div>
            <p v-if="form.errors.blocks" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ form.errors.blocks }}</p>
          </div>

          <div class="flex justify-end gap-3 border-t border-slate-200 pt-6 dark:border-gray-800">
            <Link href="/dashboard/shift-templates" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
              {{ $t('Cancel') }}
            </Link>
            <button type="submit" :disabled="form.processing" class="rounded-xl bg-blue-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500">
              {{ form.processing ? $t('Updating...') : $t('Update Template') }}
            </button>
          </div>
        </form>
      </div>
    </section>
  </DashboardLayout>
</template>
