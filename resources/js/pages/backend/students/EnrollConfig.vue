<script setup>
import axios from 'axios'
import { Head } from '@inertiajs/vue3'
import { computed, onMounted, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import { SelectSearch } from '../../../components/ui/select-search'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import { ChevronDown, ChevronUp, Search } from '@lucide/vue'
import { useConfirm } from '../../../composables/useConfirm'
import { useI18n } from '@/i18n'

const { t } = useI18n()
const toast = useToast()
const { confirm } = useConfirm()

// Category -> subCategories -> tracks -> courses, as returned already grouped
// by GetCourseEnrollConfigs. Each course carries its default pricing config
// plus class_schedules (Class Type -> Term -> Time, from Schedule Management).
const categories = ref([])
const search = ref('')
const isLoading = ref(false)
const hasLoaded = ref(false)
const savingId = ref(null)
const savingOrderId = ref(null)
const bulkStartDate = ref('')
const isBulkSaving = ref(false)
const selectedCategory = ref('')
const selectedSubCategory = ref('')
const selectedTrack = ref('')
const selectedCourse = ref('')

// "courseId:classTypeId" pairs the admin has collapsed (expanded by default).
const collapsed = ref(new Set())
// "courseId:scheduleId:timeId" of the badge currently mid-request.
const pendingKey = ref(null)

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

// Optimistic pricing save: apply the change immediately, roll back on failure.
async function saveConfig(course, changes) {
  const previous = { ...course.config }
  Object.assign(course.config, changes)
  savingId.value = course.config.id

  try {
    const payload = {
      status: course.config.enroll_status,
      start_date: course.config.start_date ?? null,
      unit_price: course.config.unit_price,
      course_price: course.config.course_price,
      selected_price_type: course.config.selected_price_type,
      document_price: course.config.document_price,
    }

    const response = await axios.put(`/dashboard/enroll/config/${course.config.id}`, payload)

    const saved = response.data
    course.config.enroll_status = saved.enroll_status
    course.config.start_date = saved.start_date
    course.config.unit_price = saved.unit_price
    course.config.course_price = saved.course_price
    course.config.selected_price_type = saved.selected_price_type
    course.config.resolved_price = saved.resolved_price
    course.config.document_price = saved.document_price
  } catch (error) {
    console.error('Failed to save course enroll config', error)
    Object.assign(course.config, previous)
    toast.error(t(error.response?.data?.message ?? 'Failed to save. Please try again.'))
  } finally {
    savingId.value = null
  }
}

function toggleStatus(course) {
  saveConfig(course, { enroll_status: course.config.enroll_status === 'open' ? 'closed' : 'open' })
}

function updateStartDate(course, value) {
  saveConfig(course, { start_date: value || null })
}

function updateUnitPrice(course, value) {
  saveConfig(course, { unit_price: value === '' ? 0 : Number(value) })
}

function updateCoursePrice(course, value) {
  saveConfig(course, { course_price: value === '' ? 0 : Number(value) })
}

function updateSelectedPriceType(course, value) {
  saveConfig(course, { selected_price_type: value })
}

function priceTypeOptions(config) {
  return [
    { value: 'unit', label: `${t('Unit Price')} ($${Number(config.unit_price ?? 0).toFixed(2)})` },
    { value: 'course', label: `${t('Course Price')} ($${Number(config.course_price ?? 0).toFixed(2)})` },
  ]
}

function updateDocumentPrice(course, value) {
  saveConfig(course, { document_price: value === '' ? 0 : Number(value) })
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

// One accent per class type so the three sections are easy to tell apart at
// a glance - keyed by name since that's stable across environments, unlike class_type_id.
const CLASS_TYPE_ACCENTS = {
  'Physical Class': {
    header: 'bg-blue-50 hover:bg-blue-100 dark:bg-blue-500/10 dark:hover:bg-blue-500/15',
    border: 'border-blue-200 dark:border-blue-500/30',
    text: 'text-blue-800 dark:text-blue-300',
    dot: 'bg-blue-500',
  },
  'Scholarship Class': {
    header: 'bg-amber-50 hover:bg-amber-100 dark:bg-amber-500/10 dark:hover:bg-amber-500/15',
    border: 'border-amber-200 dark:border-amber-500/30',
    text: 'text-amber-800 dark:text-amber-300',
    dot: 'bg-amber-500',
  },
  'Online Class': {
    header: 'bg-violet-50 hover:bg-violet-100 dark:bg-violet-500/10 dark:hover:bg-violet-500/15',
    border: 'border-violet-200 dark:border-violet-500/30',
    text: 'text-violet-800 dark:text-violet-300',
    dot: 'bg-violet-500',
  },
}
const DEFAULT_ACCENT = {
  header: 'bg-slate-50 hover:bg-slate-100 dark:bg-gray-800/60 dark:hover:bg-gray-800',
  border: 'border-slate-200 dark:border-gray-800',
  text: 'text-slate-800 dark:text-gray-200',
  dot: 'bg-slate-400',
}

function classTypeAccent(classType) {
  return CLASS_TYPE_ACCENTS[classType.class_type_name] ?? DEFAULT_ACCENT
}

function isCollapsed(course, classType) {
  return collapsed.value.has(`${course.id}:${classType.class_type_id}`)
}

function toggleCollapsed(course, classType) {
  const key = `${course.id}:${classType.class_type_id}`

  if (collapsed.value.has(key)) {
    collapsed.value.delete(key)
  } else {
    collapsed.value.add(key)
  }
  // Reassign so the template's reactivity picks up the Set mutation.
  collapsed.value = new Set(collapsed.value)
}

function recomputeEnabled(classType) {
  classType.is_enabled = classType.terms.some((term) => term.times.some((time) => time.is_open))
}

// Toggle a single (schedule, time) slot for this course - same endpoint the
// Course create/edit page's Class Schedules picker uses.
async function toggleTime(course, classType, term, time) {
  const key = `${course.id}:${term.schedule_id}:${time.time_id}`
  const previous = time.is_open
  time.is_open = !previous
  recomputeEnabled(classType)
  pendingKey.value = key

  try {
    await axios.post(`/dashboard/course/courses/${course.id}/schedules/toggle`, {
      schedule_id: term.schedule_id,
      time_id: time.time_id,
    })
  } catch (error) {
    console.error('Failed to toggle schedule availability', error)
    time.is_open = previous
    recomputeEnabled(classType)
    toast.error(error.response?.data?.message ?? t('Failed to save. Please try again.'))
  } finally {
    pendingKey.value = null
  }
}

// Bulk counterpart to toggleTime() - opens or closes every time under one
// class type in a single request instead of clicking each badge.
async function setClassTypeAvailability(course, classType, open) {
  const previous = classType.terms.map((term) => term.times.map((time) => time.is_open))
  classType.terms.forEach((term) => term.times.forEach((time) => { time.is_open = open }))
  recomputeEnabled(classType)
  const key = `classtype:${course.id}:${classType.class_type_id}`
  pendingKey.value = key

  try {
    await axios.post(`/dashboard/course/courses/${course.id}/schedules/class-type`, {
      class_type_id: classType.class_type_id,
      open,
    })
  } catch (error) {
    console.error('Failed to bulk-toggle class type availability', error)
    classType.terms.forEach((term, i) => term.times.forEach((time, j) => { time.is_open = previous[i][j] }))
    recomputeEnabled(classType)
    toast.error(error.response?.data?.message ?? t('Failed to save. Please try again.'))
  } finally {
    pendingKey.value = null
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
                  <div class="mb-3 flex items-center gap-3 pl-3">
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

                  <!-- Enrollment & pricing: the course's single default config (schedule_id/time_id both null). -->
                  <div class="rounded-xl border border-slate-200 p-4 dark:border-gray-800">
                    <div class="mb-3 flex items-center justify-between">
                      <p class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-gray-400">{{ $t('Enrollment & Pricing') }}</p>
                      <button
                        type="button"
                        :disabled="savingId === course.config.id"
                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold transition disabled:opacity-50"
                        :class="course.config.enroll_status === 'open' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400'"
                        @click="toggleStatus(course)"
                      >
                        {{ course.config.enroll_status === 'open' ? $t('Open') : $t('Closed') }}
                      </button>
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                      <div>
                        <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500">{{ $t('Start Date') }}</label>
                        <input
                          type="date"
                          :value="course.config.start_date ?? ''"
                          :disabled="savingId === course.config.id"
                          class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                          @change="updateStartDate(course, $event.target.value)"
                        >
                      </div>
                      <div>
                        <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500">{{ $t('Unit Price') }}</label>
                        <div class="relative">
                          <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-gray-500">$</span>
                          <input
                            type="number"
                            min="0"
                            step="0.01"
                            :value="course.config.unit_price"
                            :disabled="savingId === course.config.id"
                            class="w-full rounded-lg border border-slate-300 py-1.5 pl-6 pr-2 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                            @change="updateUnitPrice(course, $event.target.value)"
                          >
                        </div>
                      </div>
                      <div>
                        <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500">{{ $t('Course Price') }}</label>
                        <div class="relative">
                          <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-gray-500">$</span>
                          <input
                            type="number"
                            min="0"
                            step="0.01"
                            :value="course.config.course_price"
                            :disabled="savingId === course.config.id"
                            class="w-full rounded-lg border border-slate-300 py-1.5 pl-6 pr-2 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                            @change="updateCoursePrice(course, $event.target.value)"
                          >
                        </div>
                      </div>
                      <div>
                        <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500">{{ $t('Document Price') }}</label>
                        <div class="relative">
                          <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-gray-500">$</span>
                          <input
                            type="number"
                            min="0"
                            step="0.01"
                            :value="course.config.document_price"
                            :disabled="savingId === course.config.id"
                            class="w-full rounded-lg border border-slate-300 py-1.5 pl-6 pr-2 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                            @change="updateDocumentPrice(course, $event.target.value)"
                          >
                        </div>
                      </div>
                      <div>
                        <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500">{{ $t('Price to Use') }}</label>
                        <SelectSearch
                          :model-value="course.config.selected_price_type"
                          :options="priceTypeOptions(course.config)"
                          :disabled="savingId === course.config.id"
                          :clearable="false"
                          button-class="flex w-full items-center justify-between rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-left text-sm transition focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                          @update:model-value="(value) => updateSelectedPriceType(course, value)"
                        />
                      </div>
                    </div>
                  </div>

                  <!-- Class schedules: Class Type -> Term -> Time, sourced from Schedule Management. -->
                  <div class="mt-3 space-y-3">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-gray-400">{{ $t('Class Schedules') }}</p>

                    <div v-for="classType in course.class_schedules" :key="classType.class_type_id"
                      class="rounded-xl border overflow-hidden" :class="classTypeAccent(classType).border">
                      <div class="w-full flex items-center justify-between gap-3 px-4 py-3 transition" :class="classTypeAccent(classType).header">
                        <button type="button" class="flex items-center gap-2.5 min-w-0" @click="toggleCollapsed(course, classType)">
                          <span class="h-2.5 w-2.5 shrink-0 rounded-full" :class="classTypeAccent(classType).dot" />
                          <span class="font-semibold" :class="classTypeAccent(classType).text">{{ classType.class_type_name }}</span>
                          <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                            :class="classType.is_enabled ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-slate-200 text-slate-600 dark:bg-gray-700 dark:text-gray-400'">
                            {{ classType.is_enabled ? $t('ON') : $t('OFF') }}
                          </span>
                        </button>
                        <div class="flex items-center gap-3 shrink-0">
                          <button type="button"
                            :disabled="pendingKey === `classtype:${course.id}:${classType.class_type_id}`"
                            class="text-xs font-semibold text-slate-500 hover:text-slate-800 hover:underline disabled:opacity-50 dark:text-gray-400 dark:hover:text-gray-100"
                            @click="setClassTypeAvailability(course, classType, true)">
                            {{ $t('Turn on all') }}
                          </button>
                          <span class="text-slate-300 dark:text-gray-600">|</span>
                          <button type="button"
                            :disabled="pendingKey === `classtype:${course.id}:${classType.class_type_id}`"
                            class="text-xs font-semibold text-slate-500 hover:text-slate-800 hover:underline disabled:opacity-50 dark:text-gray-400 dark:hover:text-gray-100"
                            @click="setClassTypeAvailability(course, classType, false)">
                            {{ $t('Turn off all') }}
                          </button>
                          <button type="button" @click="toggleCollapsed(course, classType)">
                            <ChevronUp v-if="!isCollapsed(course, classType)" class="h-4 w-4 text-slate-400 dark:text-gray-500" />
                            <ChevronDown v-else class="h-4 w-4 text-slate-400 dark:text-gray-500" />
                          </button>
                        </div>
                      </div>

                      <div v-if="!isCollapsed(course, classType)" class="px-4 py-4 space-y-4">
                        <p v-if="classType.terms.length === 0" class="text-sm text-slate-500 dark:text-gray-400">
                          {{ $t('No schedules configured for this class type yet.') }}
                        </p>
                        <div v-for="term in classType.terms" :key="term.schedule_id">
                          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2 dark:text-gray-400">{{ term.term_name }}</p>
                          <div class="flex flex-wrap gap-2">
                            <button v-for="time in term.times" :key="time.time_id" type="button"
                              :disabled="pendingKey === `${course.id}:${term.schedule_id}:${time.time_id}`"
                              @click="toggleTime(course, classType, term, time)"
                              class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-medium transition disabled:cursor-not-allowed disabled:opacity-50"
                              :class="time.is_open
                                ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-400'
                                : 'border-slate-300 text-slate-500 hover:border-slate-400 dark:border-gray-600 dark:text-gray-400 dark:hover:border-gray-500'">
                              <span class="h-2 w-2 rounded-full" :class="time.is_open ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-gray-600'" />
                              {{ time.time_name }}
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
