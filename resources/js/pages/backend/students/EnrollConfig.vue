<script setup>
import axios from 'axios'
import { Head } from '@inertiajs/vue3'
import { computed, onMounted, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import { SelectSearch } from '../../../components/ui/select-search'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import { Clock, Search, Trash2, X } from '@lucide/vue'
import { useConfirm } from '../../../composables/useConfirm'
import { useI18n } from '@/i18n'

const { t } = useI18n()
const toast = useToast()
const { confirm } = useConfirm()

// Category -> subCategories -> tracks -> courses, as returned already grouped
// by GetCourseEnrollConfigs (see that class for why the grouping happens
// server-side rather than here). Each course carries a configs array - one
// enrollment schedule per time slot, always with a real id (schedules are
// only ever created via the "Manage Time Slots" modal, which POSTs with the
// chosen time_id immediately - there's no unsaved draft-row state to track).
// times lists every slot the admin can toggle on/off in that modal.
const categories = ref([])
const times = ref([])
const search = ref('')
const isLoading = ref(false)
const hasLoaded = ref(false)
const savingId = ref(null)
const deletingId = ref(null)
const savingOrderId = ref(null)
const bulkStartDate = ref('')
const isBulkSaving = ref(false)
const selectedCategory = ref('')
const selectedSubCategory = ref('')
const selectedTrack = ref('')
const selectedCourse = ref('')

// The course currently open in the "Manage Time Slots" modal, or null.
const manageModalCourse = ref(null)
// Which chip (a time_id, or 'default') is mid-toggle, so only that one
// chip disables while its own request is in flight.
const manageModalPendingKey = ref(null)

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Enroll Config', current: true },
]

onMounted(() => {
  fetchCategories()
})

async function fetchCategories() {
  isLoading.value = true

  try {
    const response = await axios.get('/dashboard/enroll/config/data')

    categories.value = response.data.categories ?? []
    times.value = response.data.times ?? []
  } catch (error) {
    console.error('Failed to fetch course enroll configs', error)
  } finally {
    hasLoaded.value = true
    isLoading.value = false
  }
}

const hasActiveFilters = computed(
  () =>
    search.value !== '' ||
    selectedCategory.value !== '' ||
    selectedSubCategory.value !== '' ||
    selectedTrack.value !== '' ||
    selectedCourse.value !== '',
)

const categoryOptions = computed(() =>
  categories.value.map((category) => ({
    value: String(category.id),
    label: category.name,
  })),
)

const subCategoryOptions = computed(() =>
  categories.value.flatMap((category) =>
    category.subCategories.map((subCategory) => ({
      value: String(subCategory.id),
      label: subCategory.name,
    })),
  ),
)

const trackOptions = computed(() =>
  categories.value.flatMap((category) =>
    category.subCategories.flatMap((subCategory) =>
      subCategory.tracks.map((track) => ({
        value: String(track.id),
        label: track.name,
      })),
    ),
  ),
)

const courseOptions = computed(() =>
  categories.value.flatMap((category) =>
    category.subCategories.flatMap((subCategory) =>
      subCategory.tracks.flatMap((track) =>
        track.courses.map((course) => ({
          value: String(course.id),
          label: course.title,
        })),
      ),
    ),
  ),
)

// AND-combined filters on the already-loaded tree; empty groups are pruned.
const filteredCategories = computed(() => {
  let list = categories.value

  if (selectedCategory.value !== '') {
    list = list.filter((category) => String(category.id) === selectedCategory.value)
  }

  if (selectedSubCategory.value !== '') {
    list = list
      .map((category) => ({
        ...category,
        subCategories: category.subCategories.filter((subCategory) => String(subCategory.id) === selectedSubCategory.value),
      }))
      .filter((category) => category.subCategories.length > 0)
  }

  if (selectedTrack.value !== '') {
    list = list
      .map((category) => ({
        ...category,
        subCategories: category.subCategories
          .map((subCategory) => ({
            ...subCategory,
            tracks: subCategory.tracks.filter((track) => String(track.id) === selectedTrack.value),
          }))
          .filter((subCategory) => subCategory.tracks.length > 0),
      }))
      .filter((category) => category.subCategories.length > 0)
  }

  if (selectedCourse.value !== '') {
    list = list
      .map((category) => ({
        ...category,
        subCategories: category.subCategories
          .map((subCategory) => ({
            ...subCategory,
            tracks: subCategory.tracks
              .map((track) => ({
                ...track,
                courses: track.courses.filter((course) => String(course.id) === selectedCourse.value),
              }))
              .filter((track) => track.courses.length > 0),
          }))
          .filter((subCategory) => subCategory.tracks.length > 0),
      }))
      .filter((category) => category.subCategories.length > 0)
  }

  const keyword = search.value.trim().toLowerCase()
  if (keyword !== '') {
    list = list
      .map((category) => ({
        ...category,
        subCategories: category.subCategories
          .map((subCategory) => ({
            ...subCategory,
            tracks: subCategory.tracks
              .map((track) => ({
                ...track,
                courses: track.courses.filter((course) => course.title.toLowerCase().includes(keyword)),
              }))
              .filter((track) => track.courses.length > 0),
          }))
          .filter((subCategory) => subCategory.tracks.length > 0),
      }))
      .filter((category) => category.subCategories.length > 0)
  }

  return list
})

function resetFilters() {
  search.value = ''
  selectedCategory.value = ''
  selectedSubCategory.value = ''
  selectedTrack.value = ''
  selectedCourse.value = ''
}

function sortConfigs(course) {
  course.configs.sort((a, b) => (a.time_name ?? '').localeCompare(b.time_name ?? ''))
}

// Optimistic schedule save: apply the change immediately, roll back on failure.
// Every row reaching this already has a real id (see manageModalCourse above).
async function saveConfig(course, config, changes) {
  const previous = { ...config }
  Object.assign(config, changes)
  savingId.value = config.id

  try {
    const payload = {
      time_id: config.time_id ?? null,
      status: config.enroll_status,
      start_date: config.start_date ?? null,
      unit_price: config.unit_price,
      course_price: config.course_price,
      selected_price_type: config.selected_price_type,
      document_price: config.document_price,
    }

    const response = await axios.put(`/dashboard/enroll/config/${config.id}`, payload)

    const saved = response.data
    config.id = saved.id
    config.time_id = saved.time_id
    config.time_name = saved.time_name
    config.enroll_status = saved.enroll_status
    config.start_date = saved.start_date
    config.unit_price = saved.unit_price
    config.course_price = saved.course_price
    config.selected_price_type = saved.selected_price_type
    config.resolved_price = saved.resolved_price
    config.document_price = saved.document_price

    sortConfigs(course)
  } catch (error) {
    console.error('Failed to save course enroll config', error)
    Object.assign(config, previous)
    toast.error(t(error.response?.data?.message ?? 'Failed to save. Please try again.'))
  } finally {
    savingId.value = null
  }
}

function toggleStatus(course, config) {
  saveConfig(course, config, { enroll_status: config.enroll_status === 'open' ? 'closed' : 'open' })
}

function updateStartDate(course, config, value) {
  saveConfig(course, config, { start_date: value || null })
}

function updateUnitPrice(course, config, value) {
  saveConfig(course, config, { unit_price: value === '' ? 0 : Number(value) })
}

function updateCoursePrice(course, config, value) {
  saveConfig(course, config, { course_price: value === '' ? 0 : Number(value) })
}

function updateSelectedPriceType(course, config, value) {
  saveConfig(course, config, { selected_price_type: value })
}

function priceTypeOptions(config) {
  return [
    { value: 'unit', label: `${t('Unit Price')} ($${Number(config.unit_price ?? 0).toFixed(2)})` },
    { value: 'course', label: `${t('Course Price')} ($${Number(config.course_price ?? 0).toFixed(2)})` },
  ]
}

function updateDocumentPrice(course, config, value) {
  saveConfig(course, config, { document_price: value === '' ? 0 : Number(value) })
}

// Course-level display order for the public student-register list - 1 shows
// first (Basic IT = 1, Office Word Excel = 2, ...). Clearing the input drops
// the course back to its old alphabetical position.
async function updateCourseOrder(course, value) {
  const enrollOrder = value === '' ? null : Number(value)

  if ((course.enroll_order ?? null) === enrollOrder) {
    return
  }

  const previous = course.enroll_order ?? null
  course.enroll_order = enrollOrder
  savingOrderId.value = course.id

  try {
    await axios.put(`/dashboard/enroll/config/course/${course.id}/order`, {
      enroll_order: enrollOrder,
    })
    toast.success(t('Course order saved.'))
  } catch (error) {
    console.error('Failed to save course order', error)
    course.enroll_order = previous
    toast.error(t(error.response?.data?.message ?? 'Failed to save. Please try again.'))
  } finally {
    savingOrderId.value = null
  }
}

// Trash icon in the table - same delete as the modal's toggle-off, but with
// a confirm step since it's a single-purpose destructive action here rather
// than a quick toggle the admin is actively managing in the modal.
async function deleteSchedule(course, config) {
  const ok = await confirm({
    title: t('Remove this schedule?'),
    message: config.time_name
      ? t('This removes the :time schedule for :course.', { time: config.time_name, course: course.title })
      : t('This removes the default schedule for :course.', { course: course.title }),
    confirmText: t('Remove'),
    danger: true,
  })

  if (!ok) {
    return
  }

  deletingId.value = config.id

  try {
    await axios.delete(`/dashboard/enroll/config/${config.id}`)
    course.configs.splice(course.configs.indexOf(config), 1)
    toast.success(t('Schedule removed.'))
  } catch (error) {
    console.error('Failed to remove schedule', error)
    toast.error(t('Failed to remove schedule. Please try again.'))
  } finally {
    deletingId.value = null
  }
}

function openManageModal(course) {
  manageModalCourse.value = course
}

function closeManageModal() {
  manageModalCourse.value = null
}

// The modal's own list: "Default" (time_id null) plus every real time slot.
function manageModalSlots() {
  return [{ key: 'default', time_id: null, label: t('Default') }, ...times.value.map((slot) => ({
    key: slot.value,
    time_id: Number(slot.value),
    label: slot.label,
  }))]
}

function configForTimeId(course, timeId) {
  return course.configs.find((config) => (config.time_id ?? null) === timeId)
}

// Toggle a slot on/off for the course open in the modal. Off -> on creates a
// fresh schedule (default price/status, same as a brand-new row always
// started); on -> off deletes it immediately - no confirm step, since this
// is the admin actively curating the set of open slots, not a one-off
// destructive action (that's what the table's trash icon is for).
async function toggleModalTimeSlot(course, timeId) {
  const key = timeId ?? 'default'
  const existing = configForTimeId(course, timeId)
  manageModalPendingKey.value = key

  try {
    if (existing) {
      await axios.delete(`/dashboard/enroll/config/${existing.id}`)
      course.configs.splice(course.configs.indexOf(existing), 1)
    } else {
      const response = await axios.post(`/dashboard/enroll/config/${course.id}/schedules`, {
        time_id: timeId,
        status: 'open',
        start_date: null,
        unit_price: 0,
        course_price: 0,
        selected_price_type: 'course',
        document_price: 5,
      })
      course.configs.push(response.data)
      sortConfigs(course)
    }
  } catch (error) {
    console.error('Failed to toggle schedule', error)
    toast.error(t(error.response?.data?.message ?? 'Failed to save. Please try again.'))
  } finally {
    manageModalPendingKey.value = null
  }
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
    await fetchCategories()
  } catch (error) {
    console.error('Failed to bulk-set course start dates', error)
    toast.error(t('Failed to save. Please try again.'))
  } finally {
    isBulkSaving.value = false
  }
}
</script>

<template>
  <Head :title="$t('Course Enroll Config')" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Enrollment Management" :title="$t('Course Enroll Config')" :description="$t('Set when each course opens for enrollment.')" />

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
              {{ $t('Apply to All') }}
            </button>
          </div>
        </div>

        <div class="flex flex-col gap-4">
          <div class="grid w-full gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            <!-- Search courses by name -->
            <div class="space-y-1.5 text-left">
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">{{ $t('Search') }}</label>
              <div class="relative">
                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500" />
                <input
                  v-model="search"
                  type="search"
                  class="w-full rounded-xl border border-slate-300 py-3 pl-9 pr-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                  :placeholder="$t('Search courses...')"
                >
              </div>
            </div>

            <!-- Filter by category -->
            <div class="space-y-1.5 text-left">
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">{{ $t('Category') }}</label>
              <SelectSearch
                v-model="selectedCategory"
                :options="categoryOptions"
                :placeholder="t('All Categories')"
                button-class="flex w-full items-center justify-between rounded-xl border border-slate-300 bg-white px-4 py-3 text-left text-sm transition focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:border-gray-600 dark:bg-gray-800 dark:focus:border-blue-500 dark:focus:ring-blue-500/20 dark:disabled:bg-gray-700 dark:disabled:text-gray-500"
              />
            </div>

            <!-- Filter by sub-category -->
            <div class="space-y-1.5 text-left">
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">{{ $t('Sub-category') }}</label>
              <SelectSearch
                v-model="selectedSubCategory"
                :options="subCategoryOptions"
                :placeholder="t('All Sub Categories')"
                button-class="flex w-full items-center justify-between rounded-xl border border-slate-300 bg-white px-4 py-3 text-left text-sm transition focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
              />
            </div>

            <!-- Filter by tech stack -->
            <div class="space-y-1.5 text-left">
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">{{ $t('Tech Stack') }}</label>
              <SelectSearch
                v-model="selectedTrack"
                :options="trackOptions"
                :placeholder="t('All Tech Stacks')"
                button-class="flex w-full items-center justify-between rounded-xl border border-slate-300 bg-white px-4 py-3 text-left text-sm transition focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
              />
            </div>

            <!-- Filter by course -->
            <div class="space-y-1.5 text-left">
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">{{ $t('Course') }}</label>
              <SelectSearch
                v-model="selectedCourse"
                :options="courseOptions"
                :placeholder="t('All Courses')"
                button-class="flex w-full items-center justify-between rounded-xl border border-slate-300 bg-white px-4 py-3 text-left text-sm transition focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
              />
            </div>
          </div>

          <div v-if="hasActiveFilters" class="flex justify-end">
            <button
              type="button"
              @click="resetFilters"
              class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
            >
              {{ $t('Reset Filters') }}
            </button>
          </div>
        </div>

        <div class="mt-5">
          <p v-if="hasLoaded && filteredCategories.length === 0" class="rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 dark:border-gray-700 dark:text-gray-400">
            {{ $t('No courses found.') }}
          </p>

          <div v-for="(category, catIndex) in filteredCategories" :key="category.id ?? 'uncategorized'" :class="catIndex > 0 ? 'mt-10' : ''">
            <div class="border-b-1 border-gray-300 pb-2 dark:border-blue-500">
              <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-blue-900/60 dark:text-blue-400/70">{{ $t('Category') }}</p>
              <h2 class="text-xl font-bold text-slate-900 dark:text-gray-100">{{ category.name }}</h2>
            </div>

            <div v-for="(subCategory, subIndex) in category.subCategories" :key="subCategory.id ?? 'uncategorized'" :class="subIndex > 0 ? 'mt-8' : 'mt-5'">
              <div class="border-l-4 border-slate-300 pl-3 dark:border-gray-600">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400 dark:text-gray-500">{{ $t('Sub-category') }}</p>
                <h3 class="text-base font-semibold text-slate-700 dark:text-gray-200">{{ subCategory.name }}</h3>
              </div>

              <div v-for="(track, trackIndex) in subCategory.tracks" :key="track.id ?? 'uncategorized'" :class="trackIndex > 0 ? 'mt-6' : 'mt-4'">
                <div class="mb-2 pl-3">
                  <p class="text-[10px] uppercase tracking-[0.18em] text-slate-400 dark:text-gray-500">{{ $t('Tech Stack') }}</p>
                  <h4 class="text-sm font-medium text-slate-600 dark:text-gray-300">{{ track.name }}</h4>
                </div>

                <div v-for="(course, courseIndex) in track.courses" :key="course.id ?? 'uncategorized'" :class="courseIndex > 0 ? 'mt-6' : 'mt-4'">
                  <div class="mb-2 flex items-center justify-between gap-3 pl-3">
                    <div class="flex items-center gap-3">
                      <h5 class="text-sm font-semibold text-slate-900 dark:text-gray-100">{{ course.title }}</h5>
                      <div class="flex items-center gap-1.5" :title="$t('Lower numbers show first on the registration page.')">
                        <label class="text-[10px] font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500">{{ $t('Order') }}</label>
                        <input
                          type="number"
                          min="1"
                          max="9999"
                          :value="course.enroll_order ?? ''"
                          :disabled="savingOrderId === course.id"
                          class="w-16 rounded-lg border border-slate-300 px-2 py-1 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                          @change="updateCourseOrder(course, $event.target.value)"
                        >
                      </div>
                    </div>
                    <button
                      type="button"
                      class="inline-flex items-center gap-1 rounded-lg border border-dashed border-blue-300 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-50 dark:border-blue-500/40 dark:text-blue-400 dark:hover:bg-blue-500/10"
                      @click="openManageModal(course)"
                    >
                      <Clock class="h-3.5 w-3.5" />
                      {{ $t('Manage Time Slots') }}
                    </button>
                  </div>

                  <p v-if="course.configs.length === 0" class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500 dark:border-gray-700 dark:text-gray-400">
                    {{ $t('No schedules yet. Use "Manage Time Slots" to open one.') }}
                  </p>

                  <!-- overflow-y explicit (not left implicit) - CSS computes an unset axis to
                       auto whenever the other axis is non-visible, which would silently clip
                       the SelectSearch dropdown (price-to-use) at this container's edge
                       instead of letting it float above the content below. -->
                  <div v-else class="overflow-x-auto overflow-y-visible rounded-xl border border-slate-200 dark:border-gray-800">
                    <table class="w-full min-w-[1020px] text-left text-sm">
                      <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-400">
                        <tr>
                          <th class="px-4 py-3">{{ $t('Time Slot') }}</th>
                          <th class="px-4 py-3">{{ $t('Start Date') }}</th>
                          <th class="px-4 py-3">{{ $t('Unit Price') }}</th>
                          <th class="px-4 py-3">{{ $t('Course Price') }}</th>
                          <th class="px-4 py-3">{{ $t('Document Price') }}</th>
                          <th class="px-4 py-3">{{ $t('Price to Use') }}</th>
                          <th class="px-4 py-3">{{ $t('Status') }}</th>
                          <th class="px-4 py-3"></th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-slate-100 bg-white dark:divide-gray-800 dark:bg-gray-900">
                        <tr v-for="config in course.configs" :key="config.id" class="transition hover:bg-slate-50 dark:hover:bg-gray-800">
                          <td class="px-4 py-3 font-medium text-slate-700 dark:text-gray-300">
                            {{ config.time_name ?? $t('Default') }}
                          </td>
                          <td class="px-4 py-3">
                            <input
                              type="date"
                              :value="config.start_date ?? ''"
                              :disabled="savingId === config.id || deletingId === config.id"
                              class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                              @change="updateStartDate(course, config, $event.target.value)"
                            >
                          </td>
                          <td class="px-4 py-3">
                            <div class="relative w-28">
                              <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-gray-500">$</span>
                              <input
                                type="number"
                                min="0"
                                step="0.01"
                                :value="config.unit_price"
                                :disabled="savingId === config.id || deletingId === config.id"
                                class="w-full rounded-lg border border-slate-300 py-1.5 pl-6 pr-2 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                                @change="updateUnitPrice(course, config, $event.target.value)"
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
                                :value="config.course_price"
                                :disabled="savingId === config.id || deletingId === config.id"
                                class="w-full rounded-lg border border-slate-300 py-1.5 pl-6 pr-2 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                                @change="updateCoursePrice(course, config, $event.target.value)"
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
                                :value="config.document_price"
                                :disabled="savingId === config.id || deletingId === config.id"
                                class="w-full rounded-lg border border-slate-300 py-1.5 pl-6 pr-2 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                                @change="updateDocumentPrice(course, config, $event.target.value)"
                              >
                            </div>
                          </td>
                          <td class="px-4 py-3">
                            <div class="w-48">
                              <SelectSearch
                                :model-value="config.selected_price_type"
                                :options="priceTypeOptions(config)"
                                :disabled="savingId === config.id || deletingId === config.id"
                                :clearable="false"
                                :searchable="false"
                                button-class="flex w-full items-center justify-between rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-left text-sm transition focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                                @update:model-value="(value) => updateSelectedPriceType(course, config, value)"
                              />
                            </div>
                          </td>
                          <td class="px-4 py-3">
                            <button
                              type="button"
                              :disabled="savingId === config.id || deletingId === config.id"
                              class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold transition disabled:opacity-50"
                              :class="config.enroll_status === 'open' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400'"
                              @click="toggleStatus(course, config)"
                            >
                              {{ config.enroll_status === 'open' ? $t('Open') : $t('Closed') }}
                            </button>
                          </td>
                          <td class="px-4 py-3">
                            <button
                              type="button"
                              :title="$t('Remove')"
                              :disabled="savingId === config.id || deletingId === config.id"
                              class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600 disabled:opacity-50 dark:hover:bg-red-500/10 dark:hover:text-red-400"
                              @click="deleteSchedule(course, config)"
                            >
                              <Trash2 class="h-4 w-4" />
                            </button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <div
      v-if="manageModalCourse"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4"
      @click.self="closeManageModal"
    >
      <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Manage Time Slots') }}</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">{{ manageModalCourse.title }}</p>
          </div>
          <button
            type="button"
            :title="$t('Close')"
            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-gray-800 dark:hover:text-gray-300"
            @click="closeManageModal"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <p class="mt-4 text-xs text-slate-500 dark:text-gray-400">
          {{ $t('Click a time slot to open it for enrollment. Click it again to remove that schedule.') }}
        </p>

        <div class="mt-3 flex max-h-80 flex-wrap gap-2 overflow-y-auto py-1">
          <button
            v-for="slot in manageModalSlots()"
            :key="slot.key"
            type="button"
            :disabled="manageModalPendingKey === slot.key"
            class="rounded-full border px-3 py-1.5 text-xs font-medium transition disabled:cursor-not-allowed disabled:opacity-50"
            :class="configForTimeId(manageModalCourse, slot.time_id)
              ? 'border-blue-600 bg-blue-600 text-white dark:border-blue-500 dark:bg-blue-500'
              : 'border-slate-300 text-slate-600 hover:border-blue-400 hover:text-blue-600 dark:border-gray-600 dark:text-gray-300 dark:hover:border-blue-500 dark:hover:text-blue-400'"
            @click="toggleModalTimeSlot(manageModalCourse, slot.time_id)"
          >
            {{ slot.label }}
          </button>
        </div>

        <div class="mt-6 flex justify-end">
          <button
            type="button"
            class="rounded-xl bg-blue-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500"
            @click="closeManageModal"
          >
            {{ $t('Done') }}
          </button>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
