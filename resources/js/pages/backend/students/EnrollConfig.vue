<script setup>
import axios from 'axios'
import { Head } from '@inertiajs/vue3'
import { onMounted, ref, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { Pagination } from '../../../components/ui/pagination'
import { PageHero } from '../../../components/ui/page-hero'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import { useConfirm } from '../../../composables/useConfirm'
import { useI18n } from '@/i18n'

const { t } = useI18n()
const toast = useToast()
const { confirm } = useConfirm()

const courses = ref([])
const search = ref('')
const isLoading = ref(false)
const hasLoaded = ref(false)
const savingId = ref(null)
const bulkStartDate = ref('')
const isBulkSaving = ref(false)
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
})

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Enroll Config', current: true },
]

onMounted(() => {
  fetchCourses()
})

async function fetchCourses(pageNumber = 1) {
  isLoading.value = true

  try {
    const response = await axios.get('/dashboard/enroll/config/data', {
      params: {
        page: pageNumber,
        per_page: pagination.value.per_page,
        search: search.value,
      },
    })

    courses.value = response.data.data ?? []
    pagination.value = {
      current_page: response.data.current_page ?? 1,
      last_page: response.data.last_page ?? 1,
      per_page: response.data.per_page ?? 10,
      total: response.data.total ?? 0,
    }
  } catch (error) {
    console.error('Failed to fetch course enroll configs', error)
  } finally {
    hasLoaded.value = true
    isLoading.value = false
  }
}

// Optimistic row update: apply the change immediately, roll back on failure.
async function saveConfig(course, changes) {
  const previous = { enroll_status: course.enroll_status, start_date: course.start_date, price: course.price, document_price: course.document_price }
  Object.assign(course, changes)
  savingId.value = course.id

  try {
    const response = await axios.put(`/dashboard/enroll/config/${course.id}`, {
      status: course.enroll_status,
      start_date: course.start_date,
      price: course.price,
      document_price: course.document_price,
    })
    course.enroll_status = response.data.status
    course.start_date = response.data.start_date
    course.price = response.data.price
    course.document_price = response.data.document_price
  } catch (error) {
    console.error('Failed to save course enroll config', error)
    Object.assign(course, previous)
    toast.error(t('Failed to save. Please try again.'))
  } finally {
    savingId.value = null
  }
}

function toggleStatus(course) {
  saveConfig(course, { enroll_status: course.enroll_status === 'open' ? 'closed' : 'open' })
}

function updateStartDate(course, value) {
  saveConfig(course, { start_date: value || null })
}

function updatePrice(course, value) {
  saveConfig(course, { price: value === '' ? 0 : Number(value) })
}

function updateDocumentPrice(course, value) {
  saveConfig(course, { document_price: value === '' ? 0 : Number(value) })
}

async function applyStartDateToAll() {
  const ok = await confirm({
    title: t('Set start date for all courses?'),
    message: bulkStartDate.value
      ? t('This overwrites the start date on every course with :date.', { date: bulkStartDate.value })
      : t('This clears the start date on every course.'),
    confirmText: t('Apply to All'),
  })

  if (!ok) {
    return
  }

  isBulkSaving.value = true

  try {
    await axios.post('/dashboard/enroll/config/bulk-start-date', {
      start_date: bulkStartDate.value || null,
    })
    toast.success(t('Start date applied to every course.'))
    await fetchCourses(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to bulk-set course start dates', error)
    toast.error(t('Failed to save. Please try again.'))
  } finally {
    isBulkSaving.value = false
  }
}

watch(search, () => {
  fetchCourses(1)
})

function paginationStart() {
  if (pagination.value.total === 0 || courses.value.length === 0) return 0
  return ((pagination.value.current_page - 1) * pagination.value.per_page) + 1
}

function paginationEnd() {
  if (pagination.value.total === 0 || courses.value.length === 0) return 0
  return ((pagination.value.current_page - 1) * pagination.value.per_page) + courses.value.length
}
</script>

<template>
  <Head :title="$t('Student Enroll Config')" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Enrollment Management" :title="$t('Student Enroll Config')" :description="$t('Set when each course opens for enrollment.')" />

      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="mb-5 flex flex-col gap-3 rounded-xl border border-dashed border-slate-300 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
          <div>
            <p class="text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Set start date for all courses') }}</p>
            <p class="text-xs text-slate-500 dark:text-gray-400">{{ $t('Applies to every course at once, so you do not have to set each one individually.') }}</p>
          </div>

          <div class="flex items-center gap-2">
            <input
              v-model="bulkStartDate"
              type="date"
              :disabled="isBulkSaving"
              class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
            >
            <button
              type="button"
              :disabled="isBulkSaving"
              class="inline-flex items-center justify-center rounded-xl bg-blue-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-blue-600 dark:hover:bg-blue-500"
              @click="applyStartDateToAll"
            >
              {{ isBulkSaving ? $t('Applying...') : $t('Apply to All') }}
            </button>
          </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <input
            v-model="search"
            type="search"
            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 sm:max-w-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
            :placeholder="$t('Search courses...')"
          >
        </div>

        <div class="relative mt-5 overflow-x-auto">
          <table class="w-full min-w-[640px] text-left text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3">{{ $t('Course') }}</th>
                <th class="px-4 py-3">{{ $t('Start Date') }}</th>
                <th class="px-4 py-3">{{ $t('Price') }}</th>
                <th class="px-4 py-3">{{ $t('Document Price') }}</th>
                <th class="px-4 py-3">{{ $t('Status') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-gray-800">
              <tr v-if="hasLoaded && !isLoading && courses.length === 0">
                <td colspan="5" class="px-4 py-8 text-center text-slate-500 dark:text-gray-400">{{ $t('No courses found.') }}</td>
              </tr>

              <tr v-for="course in courses" :key="course.id" class="transition hover:bg-slate-50 dark:hover:bg-gray-800">
                <td class="px-4 py-3 font-medium text-slate-900 dark:text-gray-100">{{ course.title }}</td>
                <td class="px-4 py-3">
                  <input
                    type="date"
                    :value="course.start_date ?? ''"
                    :disabled="savingId === course.id"
                    class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                    @change="updateStartDate(course, $event.target.value)"
                  >
                </td>
                <td class="px-4 py-3">
                  <div class="relative w-28">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-gray-500">$</span>
                    <input
                      type="number"
                      min="0"
                      step="0.01"
                      :value="course.price"
                      :disabled="savingId === course.id"
                      class="w-full rounded-lg border border-slate-300 py-1.5 pl-6 pr-2 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                      @change="updatePrice(course, $event.target.value)"
                    >
                  </div>
                </td>
                <td class="px-4 py-3">
                  <div class="relative w-28">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-gray-500">$</span>
                    <input
                      type="number"
                      min="0"
                      step="0.01"
                      :value="course.document_price"
                      :disabled="savingId === course.id"
                      class="w-full rounded-lg border border-slate-300 py-1.5 pl-6 pr-2 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                      @change="updateDocumentPrice(course, $event.target.value)"
                    >
                  </div>
                </td>
                <td class="px-4 py-3">
                  <button
                    type="button"
                    :disabled="savingId === course.id"
                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold transition disabled:opacity-50"
                    :class="course.enroll_status === 'open' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400'"
                    @click="toggleStatus(course)"
                  >
                    {{ course.enroll_status === 'open' ? $t('Open') : $t('Closed') }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-5 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
          <p class="text-sm text-slate-500 dark:text-gray-400">
            {{ $t('Showing :from-:to of :total courses', { from: paginationStart(), to: paginationEnd(), total: pagination.total }) }}
          </p>

          <Pagination
            :current-page="pagination.current_page"
            :last-page="pagination.last_page"
            :disabled="isLoading"
            @page-change="fetchCourses"
          />
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
