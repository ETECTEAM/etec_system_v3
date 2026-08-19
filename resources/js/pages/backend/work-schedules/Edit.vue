<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { onMounted, ref, computed } from 'vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'
import { SelectSearch } from '@/components/ui/select-search'

const props = defineProps({
  schedule: { type: Object, required: true },
  times: { type: Array, default: () => [] },
})

const selectButtonClass = 'flex w-full items-center justify-between rounded-xl border border-slate-300 bg-white px-4 py-3 text-left text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20'

const activeOptions = [
  { value: '1', label: 'Yes' },
  { value: '0', label: 'No' },
]

const days = [
  { id: 1, short: 'Mon', full: 'Monday' },
  { id: 2, short: 'Tue', full: 'Tuesday' },
  { id: 3, short: 'Wed', full: 'Wednesday' },
  { id: 4, short: 'Thu', full: 'Thursday' },
  { id: 5, short: 'Fri', full: 'Friday' },
  { id: 6, short: 'Sat', full: 'Saturday' },
  { id: 7, short: 'Sun', full: 'Sunday' },
]

const selected = ref({})

days.forEach((d) => { selected.value[d.id] = [] })

onMounted(() => {
  for (const entry of (props.schedule.times ?? [])) {
    if (selected.value[entry.day_of_week]) {
      selected.value[entry.day_of_week].push(entry.time_id)
    }
  }
})

function toggleTime(dayId, timeId) {
  const arr = selected.value[dayId]
  const idx = arr.indexOf(timeId)
  if (idx === -1) {
    arr.push(timeId)
  } else {
    arr.splice(idx, 1)
  }
}

function isTimeSelected(dayId, timeId) {
  return selected.value[dayId]?.includes(timeId) ?? false
}

const form = useForm({
  name: props.schedule.name ?? '',
  code: props.schedule.code ?? '',
  description: props.schedule.description ?? '',
  is_active: props.schedule.is_active ?? true,
})

function formatTimeRange(timeName) {
  const match = timeName?.match(/\(([^)]+)\)/)
  if (match) return match[1]
  return timeName ?? ''
}

const totalSelected = computed(() => {
  return Object.values(selected.value).reduce((sum, arr) => sum + arr.length, 0)
})

function submit() {
  const entries = []
  for (const [dayId, timeIds] of Object.entries(selected.value)) {
    for (const timeId of timeIds) {
      entries.push({ day_of_week: parseInt(dayId), time_id: timeId })
    }
  }

  const payload = {
    name: form.name,
    code: form.code,
    description: form.description,
    is_active: form.is_active,
    entries,
  }

  form.transform(() => payload).put(`/dashboard/work-schedules/${props.schedule.id}`, {
    preserveScroll: true,
  })
}

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Work Schedules', href: '/dashboard/work-schedules' },
  { label: 'Edit', current: true },
]
</script>

<template>
  <Head :title="$t('Edit Work Schedule')" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Schedule Management" :title="$t('Edit Work Schedule')" :description="$t('Update work schedule :name', { name: schedule.name })" />

      <div v-if="$page.props.flash?.success" class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400">
        {{ $page.props.flash.success }}
      </div>

      <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-gray-800 dark:bg-gray-900">
        <form @submit.prevent="submit" class="space-y-6">
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Name') }} <span class="text-red-500">*</span></label>
              <input v-model="form.name" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20" :placeholder="$t('e.g. Morning & Evening')" />
              <p v-if="form.errors.name" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
            </div>
            <div>
              <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Code') }} <span class="text-red-500">*</span></label>
              <input v-model="form.code" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20" :placeholder="$t('e.g. morning_evening')" />
              <p v-if="form.errors.code" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ form.errors.code }}</p>
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
              <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-gray-100">{{ $t('Time Slots') }} <span class="text-red-500 dark:text-red-400">*</span></h3>
                <p class="mt-0.5 text-xs text-slate-400 dark:text-gray-500">{{ $t(':count slot(s) selected', { count: totalSelected }) }}</p>
              </div>
            </div>

            <div class="space-y-4">
              <div v-for="day in days" :key="day.id" class="rounded-xl bg-slate-50 border border-slate-200 p-4 dark:bg-gray-800 dark:border-gray-700">
                <h4 class="mb-3 text-sm font-bold text-slate-700 dark:text-gray-300">{{ day.full }}</h4>
                <div v-if="props.times.length > 0" class="flex flex-wrap gap-2">
                  <button
                    v-for="time in props.times"
                    :key="time.id"
                    type="button"
                    @click="toggleTime(day.id, time.id)"
                    class="inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-medium transition"
                    :class="isTimeSelected(day.id, time.id)
                      ? 'bg-blue-900 text-white dark:bg-blue-600 dark:text-white'
                      : 'bg-white text-slate-600 border border-slate-300 hover:bg-slate-100 dark:bg-gray-900 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700'"
                    :title="time.time_name"
                  >
                    {{ formatTimeRange(time.time_name) }}
                  </button>
                </div>
                <p v-else class="text-xs text-slate-400 dark:text-gray-500">{{ $t('No time slots available.') }}</p>
              </div>
            </div>

            <p v-if="form.errors.entries" class="mt-2 text-xs text-red-600 dark:text-red-400">{{ form.errors.entries }}</p>
          </div>

          <div class="flex justify-end gap-3 border-t border-slate-200 pt-6 dark:border-gray-800">
            <Link href="/dashboard/work-schedules" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
              {{ $t('Cancel') }}
            </Link>
            <button type="submit" :disabled="form.processing" class="rounded-xl bg-blue-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500">
              {{ form.processing ? $t('Saving...') : $t('Update Schedule') }}
            </button>
          </div>
        </form>
      </div>
    </section>
  </DashboardLayout>
</template>
