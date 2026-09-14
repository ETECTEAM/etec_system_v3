<script setup>
import axios from 'axios'
import { Head } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { useToast } from '@/composables/useToast'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import { SelectSearch } from '../../../components/ui/select-search'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import { ChevronRight, Search } from '@lucide/vue'
import { useConfirm } from '../../../composables/useConfirm'
import { useI18n } from '@/i18n'

const { t } = useI18n()
const toast = useToast()
const { confirm } = useConfirm()

const props = defineProps({
  // { categories: [...], filters: {...} } from CourseEnrollConfigController@index.
  initial: { type: Object, default: () => ({ categories: [] }) },
})

// Category -> subCategories -> tracks -> courses, as returned already grouped
// by GetCourseEnrollConfigs. Each course carries its default pricing config
// plus class_schedules (Class Type -> Term -> Time, from Schedule Management).
const categories = ref(props.initial.categories ?? [])
const search = ref('')
const isLoading = ref(false)
const hasLoaded = ref(true)
const savingId = ref(null)
const savingOrderId = ref(null)
const bulkStartDate = ref('')
const isBulkSaving = ref(false)
const selectedCategory = ref('')
const selectedSubCategory = ref('')
const selectedTrack = ref('')
const selectedCourse = ref('')

// New matrix filters.
const selectedClassType = ref('')
const selectedStatus = ref('')

// "courseId:scheduleId:timeId" of the badge currently mid-request.
const pendingKey = ref(null)

const breadcrumbItems = [
  { label: t('Dashboard'), href: '/dashboard' },
  { label: t('Enroll Config'), current: true },
]

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
    selectedCourse.value !== '' ||
    selectedStatus.value !== '',
)

const categoryOptions = computed(() =>
  categories.value.map((category) => ({ value: String(category.id), label: category.name })),
)

const subCategoryOptions = computed(() =>
  categories.value.flatMap((category) =>
    category.subCategories.map((subCategory) => ({ value: String(subCategory.id), label: subCategory.name })),
  ),
)

const trackOptions = computed(() =>
  categories.value.flatMap((category) =>
    category.subCategories.flatMap((subCategory) =>
      subCategory.tracks.map((track) => ({ value: String(track.id), label: track.name })),
    ),
  ),
)

const courseOptions = computed(() =>
  categories.value.flatMap((category) =>
    category.subCategories.flatMap((subCategory) =>
      subCategory.tracks.flatMap((track) =>
        track.courses.map((course) => ({ value: String(course.id), label: course.title })),
      ),
    ),
  ),
)

// Every course, flat — for building the class-type / term / time option lists.
const allCourses = computed(() =>
  categories.value.flatMap((c) => c.subCategories.flatMap((s) => s.tracks.flatMap((t2) => t2.courses))),
)

const classTypeOptions = computed(() => {
  const map = new Map()
  allCourses.value.forEach((course) => {
    ;(course.class_schedules ?? []).forEach((ct) => {
      if (!map.has(ct.class_type_id)) {
        map.set(ct.class_type_id, { value: String(ct.class_type_id), label: ct.class_type_name })
      }
    })
  })
  return [...map.values()]
})

const TERM_ORDER = ['Mon & Thu', 'Sat & Sun', 'Sunday']
function termSort(a, b) {
  const ia = TERM_ORDER.indexOf(a)
  const ib = TERM_ORDER.indexOf(b)
  if (ia !== -1 || ib !== -1) return (ia === -1 ? 99 : ia) - (ib === -1 ? 99 : ib)
  return String(a).localeCompare(String(b))
}

const statusOptions = [
  { value: 'open', label: t('Open') },
  { value: 'closed', label: t('Closed') },
]

const selectedClassTypeLabel = computed(
  () => classTypeOptions.value.find((o) => o.value === selectedClassType.value)?.label ?? '',
)

// The start date currently shared by the selected class type across every
// course (blank when they disagree or none is set).
const selectedTypeStartDate = computed(() => {
  const dates = new Set()
  allCourses.value.forEach((course) => {
    const sd = (course.class_schedules ?? []).find(
      (ct) => String(ct.class_type_id) === selectedClassType.value,
    )?.start_date
    if (sd) dates.add(sd)
  })
  return dates.size === 1 ? [...dates][0] : ''
})

// Auto-pick the first class type once data is available; keep a valid one.
watch(
  classTypeOptions,
  (opts) => {
    if (selectedClassType.value === '' || !opts.some((o) => o.value === selectedClassType.value)) {
      selectedClassType.value = opts[0]?.value ?? ''
    }
  },
  { immediate: true },
)

// Preload the bulk field with the class type's current date when the tab
// changes — never mid-edit from an unrelated filter change.
watch(selectedClassType, () => {
  bulkStartDate.value = selectedTypeStartDate.value
}, { immediate: true })

// The classType node of a course for the currently selected class type.
function getCourseClassType(course) {
  const list = course.class_schedules ?? []
  if (selectedClassType.value !== '') {
    return list.find((ct) => String(ct.class_type_id) === selectedClassType.value) ?? null
  }
  return list[0] ?? null
}

function courseHasClassType(course) {
  if (selectedClassType.value === '') return true
  return (course.class_schedules ?? []).some((ct) => String(ct.class_type_id) === selectedClassType.value)
}

function courseMatchesStatus(course) {
  if (selectedStatus.value === '') return true
  return course.config?.enroll_status === selectedStatus.value
}

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
        subCategories: category.subCategories.filter((s) => String(s.id) === selectedSubCategory.value),
      }))
      .filter((category) => category.subCategories.length > 0)
  }

  if (selectedTrack.value !== '') {
    list = list
      .map((category) => ({
        ...category,
        subCategories: category.subCategories
          .map((s) => ({ ...s, tracks: s.tracks.filter((tr) => String(tr.id) === selectedTrack.value) }))
          .filter((s) => s.tracks.length > 0),
      }))
      .filter((category) => category.subCategories.length > 0)
  }

  if (selectedCourse.value !== '') {
    list = list
      .map((category) => ({
        ...category,
        subCategories: category.subCategories
          .map((s) => ({
            ...s,
            tracks: s.tracks
              .map((tr) => ({ ...tr, courses: tr.courses.filter((c) => String(c.id) === selectedCourse.value) }))
              .filter((tr) => tr.courses.length > 0),
          }))
          .filter((s) => s.tracks.length > 0),
      }))
      .filter((category) => category.subCategories.length > 0)
  }

  // Search across course + category + sub-category + tech-stack names.
  const keyword = search.value.trim().toLowerCase()
  if (keyword !== '') {
    list = list
      .map((category) => {
        const catMatch = category.name.toLowerCase().includes(keyword)
        return {
          ...category,
          subCategories: category.subCategories
            .map((s) => {
              const subMatch = catMatch || s.name.toLowerCase().includes(keyword)
              return {
                ...s,
                tracks: s.tracks
                  .map((tr) => {
                    const trackMatch = subMatch || tr.name.toLowerCase().includes(keyword)
                    return {
                      ...tr,
                      courses: tr.courses.filter((c) => trackMatch || c.title.toLowerCase().includes(keyword)),
                    }
                  })
                  .filter((tr) => tr.courses.length > 0),
              }
            })
            .filter((s) => s.tracks.length > 0),
        }
      })
      .filter((category) => category.subCategories.length > 0)
  }

  // Class type / status.
  list = list
    .map((category) => ({
      ...category,
      subCategories: category.subCategories
        .map((s) => ({
          ...s,
          tracks: s.tracks
            .map((tr) => ({
              ...tr,
              courses: tr.courses.filter((c) => courseHasClassType(c) && courseMatchesStatus(c)),
            }))
            .filter((tr) => tr.courses.length > 0),
        }))
        .filter((s) => s.tracks.length > 0),
    }))
    .filter((category) => category.subCategories.length > 0)

  return list
})

const visibleCourses = computed(() =>
  filteredCategories.value.flatMap((c) => c.subCategories.flatMap((s) => s.tracks.flatMap((tr) => tr.courses))),
)

// Term columns for the matrix: the union of terms across the visible courses
// for the selected class type.
const matrixTerms = computed(() => {
  const set = new Set()
  visibleCourses.value.forEach((course) => {
    getCourseClassType(course)?.terms?.forEach((term) => set.add(term.term_name))
  })
  return [...set].sort(termSort)
})

// The (schedule) term node + its time slots for a course/column.
function termSlots(course, termName) {
  const ct = getCourseClassType(course)
  const term = ct?.terms?.find((tm) => tm.term_name === termName)
  if (!term) return { term: null, times: [] }
  return { term, times: term.times ?? [] }
}

function resetFilters() {
  search.value = ''
  selectedCategory.value = ''
  selectedSubCategory.value = ''
  selectedTrack.value = ''
  selectedCourse.value = ''
  selectedStatus.value = ''
}

// ── Business logic (unchanged) ─────────────────────────────────────────────

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
      document_price: course.config.document_price,
    }

    const response = await axios.put(`/dashboard/enroll/config/${course.config.id}`, payload)

    const saved = response.data
    course.config.enroll_status = saved.enroll_status
    course.config.start_date = saved.start_date
    course.config.unit_price = saved.unit_price
    course.config.course_price = saved.course_price
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

function updateDocumentPrice(course, value) {
  saveConfig(course, { document_price: value === '' ? 0 : Number(value) })
}

// Course-level display order for the public student-register list.
async function updateCourseOrder(course, value) {
  const enrollOrder = value === '' ? null : Number(value)

  if ((course.enroll_order ?? null) === enrollOrder) {
    return
  }

  const previous = course.enroll_order ?? null
  course.enroll_order = enrollOrder
  savingOrderId.value = course.id

  try {
    await axios.put(`/dashboard/enroll/config/course/${course.id}/order`, { enroll_order: enrollOrder })
    toast.success(t('Course order saved.'))
  } catch (error) {
    console.error('Failed to save course order', error)
    course.enroll_order = previous
    toast.error(t(error.response?.data?.message ?? 'Failed to save. Please try again.'))
  } finally {
    savingOrderId.value = null
  }
}

// One accent per class type so the tabs are easy to tell apart — keyed by name.
const CLASS_TYPE_ACCENTS = {
  'Physical Class': 'bg-blue-500',
  'Scholarship Class': 'bg-amber-500',
  'Online Class': 'bg-violet-500',
}
function accentDot(name) {
  return CLASS_TYPE_ACCENTS[name] ?? 'bg-slate-400'
}

function recomputeEnabled(classType) {
  classType.is_enabled = classType.terms.some((term) => term.times.some((time) => time.is_open))
}

// Toggle a single (schedule, time) slot for this course.
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

// Per-slot class cap.
async function updateTimeMaxClasses(course, term, time, rawValue) {
  const n = Math.trunc(Number(String(rawValue ?? '').trim()))
  const max = Number.isFinite(n) && n >= 1 ? n : null
  const previous = time.max_classes ?? null

  if (max === previous) {
    return
  }

  time.max_classes = max
  const key = `max:${course.id}:${term.schedule_id}:${time.time_id}`
  pendingKey.value = key

  try {
    const { data } = await axios.post(`/dashboard/course/courses/${course.id}/schedules/max-classes`, {
      schedule_id: term.schedule_id,
      time_id: time.time_id,
      max_classes: max,
    })
    time.max_classes = data.max_classes ?? null
  } catch (error) {
    console.error('Failed to set slot class limit', error)
    time.max_classes = previous
    toast.error(error.response?.data?.message ?? t('Failed to save. Please try again.'))
  } finally {
    pendingKey.value = null
  }
}

// Bulk open/close every time under one class type for a course.
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

function onToggleTime(course, term, time) {
  const ct = getCourseClassType(course)
  if (ct) toggleTime(course, ct, term, time)
}

function setAllForCourse(course, open) {
  const ct = getCourseClassType(course)
  if (ct) setClassTypeAvailability(course, ct, open)
}

function niceDate(value) {
  if (!value) return ''
  return new Date(`${value}T00:00:00`).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

// Scoped to the selected class type only — the backend enforces the same scope.
async function applyClassTypeStartDate() {
  if (!selectedClassType.value || isBulkSaving.value) return

  const type = selectedClassTypeLabel.value

  const ok = await confirm({
    title: t('Set start date for :type?', { type }),
    message: bulkStartDate.value
      ? t('This will apply :date to all applicable :type course enrollment configurations. It will NOT change other class types.', { date: niceDate(bulkStartDate.value), type })
      : t('This will clear the start date on all :type course enrollment configurations. It will NOT change other class types.', { type }),
    confirmText: t('Apply to :type', { type }),
  })

  if (!ok) return

  isBulkSaving.value = true

  try {
    await axios.post('/dashboard/enroll/config/bulk-start-date', {
      class_type_id: Number(selectedClassType.value),
      start_date: bulkStartDate.value || null,
    })
    toast.success(t('Start date applied to :type.', { type }))
    await fetchCategories()
  } catch (error) {
    console.error('Failed to bulk-set class type start dates', error)
    toast.error(t(error.response?.data?.message ?? 'Failed to save. Please try again.'))
  } finally {
    isBulkSaving.value = false
  }
}

const filterBtn =
  'flex w-full items-center justify-between rounded-xl border border-slate-300 bg-white px-3 py-2 text-left text-sm transition focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:focus:border-blue-500 dark:focus:ring-blue-500/20'
const numCell =
  'rounded-md border border-slate-300 px-1.5 py-1 text-xs outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200'
</script>

<template>
  <Head :title="$t('Course Enroll Config')" />

  <DashboardLayout>
    <section class="space-y-5">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero :eyebrow="$t('Enrollment Management')" :title="$t('Course Enroll Config')" :description="$t('Set when each course opens for enrollment.')" />

      <!-- Toolbar -->
      <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="relative w-full lg:max-w-xs">
          <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500" />
          <input
            v-model="search"
            type="search"
            class="w-full rounded-xl border border-slate-300 py-2 pl-9 pr-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
            :placeholder="$t('Search course, category, tech stack...')"
          >
        </div>

        <!-- Filters -->
        <div class="mt-3 grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
          <SelectSearch v-model="selectedCategory" :options="categoryOptions" :placeholder="t('All Categories')" :button-class="filterBtn" />
          <SelectSearch v-model="selectedSubCategory" :options="subCategoryOptions" :placeholder="t('All Sub Categories')" :button-class="filterBtn" />
          <SelectSearch v-model="selectedTrack" :options="trackOptions" :placeholder="t('All Tech Stacks')" :button-class="filterBtn" />
          <SelectSearch v-model="selectedCourse" :options="courseOptions" :placeholder="t('All Courses')" :button-class="filterBtn" />
          <SelectSearch v-model="selectedStatus" :options="statusOptions" :placeholder="t('All Status')" :button-class="filterBtn" />
          <button
            v-if="hasActiveFilters"
            type="button"
            class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
            @click="resetFilters"
          >
            {{ $t('Reset Filters') }}
          </button>
        </div>

        <!-- Class type selector + scoped bulk start date -->
        <div v-if="classTypeOptions.length" class="mt-3 flex flex-col gap-3 border-t border-slate-100 pt-3 dark:border-gray-800 xl:flex-row xl:items-center xl:justify-between">
          <div class="flex items-center gap-2 overflow-x-auto">
            <span class="shrink-0 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500">{{ $t('Class Type') }}</span>
            <div class="inline-flex shrink-0 gap-1 rounded-lg border border-slate-200 bg-slate-100 p-1 dark:border-gray-800 dark:bg-gray-800/60">
              <button
                v-for="ct in classTypeOptions"
                :key="ct.value"
                type="button"
                @click="selectedClassType = ct.value"
                :class="[
                  'inline-flex items-center gap-1.5 whitespace-nowrap rounded-md px-3 py-1.5 text-xs font-semibold transition',
                  selectedClassType === ct.value
                    ? 'bg-white text-slate-900 shadow-sm dark:bg-gray-900 dark:text-gray-100'
                    : 'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-100',
                ]"
              >
                <span class="h-2 w-2 rounded-full" :class="accentDot(ct.label)" />
                {{ ct.label }}
              </button>
            </div>
          </div>

          <!-- Bulk start date — scoped to the selected class type only -->
          <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-gray-400">
              {{ $t('Bulk Start Date') }} —
              <span class="text-slate-800 dark:text-gray-200">{{ selectedClassTypeLabel }}</span>
            </span>
            <input
              v-model="bulkStartDate"
              type="date"
              :disabled="isBulkSaving"
              class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
            >
            <button
              type="button"
              :disabled="isBulkSaving || !selectedClassType"
              class="inline-flex items-center justify-center rounded-xl bg-blue-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-blue-600 dark:hover:bg-blue-500"
              @click="applyClassTypeStartDate"
            >
              {{ $t('Apply to :type', { type: selectedClassTypeLabel }) }}
            </button>
          </div>
        </div>
      </div>

      <!-- Empty states -->
      <p v-if="hasLoaded && visibleCourses.length === 0" class="rounded-xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500 dark:border-gray-700 dark:text-gray-400">
        {{ $t('No courses found.') }}
      </p>
      <p v-else-if="visibleCourses.length > 0 && matrixTerms.length === 0" class="rounded-xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500 dark:border-gray-700 dark:text-gray-400">
        {{ $t('No schedules for this class type yet.') }}
      </p>

      <!-- Matrices -->
      <div v-else class="space-y-8">
        <template v-for="category in filteredCategories" :key="category.id ?? 'uncategorized'">
          <div
            v-for="subCategory in category.subCategories"
            :key="subCategory.id ?? 'uncategorized'"
          >
            <template v-for="track in subCategory.tracks" :key="track.id ?? 'uncategorized'">
              <!-- Compact breadcrumb hierarchy -->
              <div class="mb-2 flex flex-wrap items-center gap-1.5 text-sm">
                <span class="font-bold text-slate-900 dark:text-gray-100">{{ category.name }}</span>
                <ChevronRight class="h-3.5 w-3.5 text-slate-300 dark:text-gray-600" />
                <span class="text-slate-600 dark:text-gray-300">{{ subCategory.name }}</span>
                <template v-if="track.name">
                  <ChevronRight class="h-3.5 w-3.5 text-slate-300 dark:text-gray-600" />
                  <span class="text-slate-500 dark:text-gray-400">{{ track.name }}</span>
                </template>
              </div>

              <div class="mb-6 max-h-[75vh] overflow-auto rounded-xl border border-slate-200 dark:border-gray-800">
                <table class="w-full min-w-max border-collapse text-sm">
                  <thead>
                    <tr>
                      <th class="sticky left-0 top-0 z-30 w-[320px] min-w-[320px] max-w-[320px] border-b border-r border-slate-200 bg-slate-50 px-3 py-2 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-400">
                        {{ $t('Course') }}
                      </th>
                      <th
                        v-for="term in matrixTerms"
                        :key="term"
                        class="sticky top-0 z-20 min-w-[210px] border-b border-r border-slate-200 bg-slate-50 px-3 py-2 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500 last:border-r-0 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-400"
                      >
                        {{ term }}
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="course in track.courses"
                      :key="course.id ?? 'uncategorized'"
                      class="border-b border-slate-200 last:border-b-0 dark:border-gray-800"
                    >
                      <!-- Sticky course column -->
                      <td class="sticky left-0 z-10 w-[320px] min-w-[320px] max-w-[320px] border-r border-slate-200 bg-white p-0 align-top dark:border-gray-800 dark:bg-gray-900">
                        <div class="w-full space-y-2 p-3">
                          <p class="text-sm font-bold text-slate-900 dark:text-gray-100">{{ course.title }}</p>

                          <div class="space-y-1.5 text-xs">
                            <div class="flex items-center justify-between gap-2">
                              <span class="text-slate-400 dark:text-gray-500">{{ $t('Order') }}</span>
                              <input
                                type="number" min="1" max="9999"
                                :value="course.enroll_order ?? ''"
                                :disabled="savingOrderId === course.id"
                                :title="$t('Lower numbers show first on the registration page.')"
                                :class="[numCell, 'w-14 text-right']"
                                @change="updateCourseOrder(course, $event.target.value)"
                              >
                            </div>
                            <div class="flex items-center justify-between gap-2">
                              <span class="text-slate-400 dark:text-gray-500">{{ $t('Start Date') }}</span>
                              <input
                                type="date"
                                :value="course.config.start_date ?? ''"
                                :disabled="savingId === course.config.id"
                                :class="[numCell, 'w-[132px]']"
                                @change="updateStartDate(course, $event.target.value)"
                              >
                            </div>
                            <div class="flex items-center justify-between gap-2">
                              <span class="text-slate-400 dark:text-gray-500">{{ $t('Unit Price') }}</span>
                              <span class="relative">
                                <span class="pointer-events-none absolute left-1.5 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                <input
                                  type="number" min="0" step="0.01"
                                  :value="course.config.unit_price"
                                  :disabled="savingId === course.config.id"
                                  :class="[numCell, 'w-20 pl-4 text-right']"
                                  @change="updateUnitPrice(course, $event.target.value)"
                                >
                              </span>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                              <span class="text-slate-400 dark:text-gray-500">{{ $t('Course Price') }}</span>
                              <span class="relative">
                                <span class="pointer-events-none absolute left-1.5 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                <input
                                  type="number" min="0" step="0.01"
                                  :value="course.config.course_price"
                                  :disabled="savingId === course.config.id"
                                  :class="[numCell, 'w-20 pl-4 text-right']"
                                  @change="updateCoursePrice(course, $event.target.value)"
                                >
                              </span>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                              <span class="text-slate-400 dark:text-gray-500">{{ $t('Document Price') }}</span>
                              <span class="relative">
                                <span class="pointer-events-none absolute left-1.5 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                <input
                                  type="number" min="0" step="0.01"
                                  :value="course.config.document_price"
                                  :disabled="savingId === course.config.id"
                                  :class="[numCell, 'w-20 pl-4 text-right']"
                                  @change="updateDocumentPrice(course, $event.target.value)"
                                >
                              </span>
                            </div>
                          </div>

                          <div class="flex items-center gap-2 pt-0.5">
                            <button
                              type="button"
                              :disabled="savingId === course.config.id"
                              class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold transition disabled:opacity-50"
                              :class="course.config.enroll_status === 'open'
                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400'
                                : 'bg-slate-100 text-slate-600 dark:bg-gray-700 dark:text-gray-300'"
                              @click="toggleStatus(course)"
                            >
                              {{ course.config.enroll_status === 'open' ? $t('Open') : $t('Closed') }}
                            </button>
                            <span class="ml-auto flex items-center gap-1 text-[11px] text-slate-400 dark:text-gray-500">
                              <button
                                type="button"
                                :disabled="pendingKey === `classtype:${course.id}:${getCourseClassType(course)?.class_type_id}`"
                                class="hover:text-emerald-600 hover:underline disabled:opacity-50 dark:hover:text-emerald-400"
                                @click="setAllForCourse(course, true)"
                              >{{ $t('All on') }}</button>
                              <span>·</span>
                              <button
                                type="button"
                                :disabled="pendingKey === `classtype:${course.id}:${getCourseClassType(course)?.class_type_id}`"
                                class="hover:text-slate-700 hover:underline disabled:opacity-50 dark:hover:text-gray-200"
                                @click="setAllForCourse(course, false)"
                              >{{ $t('off') }}</button>
                            </span>
                          </div>
                        </div>
                      </td>

                      <!-- Term columns -->
                      <td
                        v-for="term in matrixTerms"
                        :key="term"
                        class="border-r border-slate-200 p-2 align-top last:border-r-0 dark:border-gray-800"
                      >
                        <div v-if="termSlots(course, term).times.length" class="space-y-1.5">
                          <div
                            v-for="time in termSlots(course, term).times"
                            :key="time.time_id"
                            role="button"
                            tabindex="0"
                            @click="pendingKey === `${course.id}:${termSlots(course, term).term.schedule_id}:${time.time_id}` ? null : onToggleTime(course, termSlots(course, term).term, time)"
                            :class="[
                              'group rounded-lg border p-2 transition',
                              pendingKey === `${course.id}:${termSlots(course, term).term.schedule_id}:${time.time_id}` ? 'opacity-50' : 'cursor-pointer',
                              time.is_open
                                ? 'border-emerald-300 bg-emerald-50 hover:bg-emerald-100 dark:border-emerald-500/40 dark:bg-emerald-500/10'
                                : 'border-slate-200 bg-white hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800',
                            ]"
                          >
                            <div class="flex items-center justify-between gap-2">
                              <span
                                class="text-xs font-semibold tabular-nums"
                                :class="time.is_open ? 'text-emerald-800 dark:text-emerald-300' : 'text-slate-500 dark:text-gray-400'"
                              >{{ time.time_name }}</span>
                              <span
                                class="h-2.5 w-2.5 shrink-0 rounded-full"
                                :class="time.is_open ? 'bg-emerald-500' : 'border border-slate-300 dark:border-gray-600'"
                              />
                            </div>

                            <div class="mt-1 flex items-center gap-1.5 text-[11px]" @click.stop>
                              <template v-if="time.is_open">
                                <span class="text-emerald-700/80 dark:text-emerald-400/70">{{ $t('Max Classes') }}</span>
                                <input
                                  type="number" min="0" inputmode="numeric"
                                  :value="time.max_classes ?? 0"
                                  :disabled="pendingKey === `max:${course.id}:${termSlots(course, term).term.schedule_id}:${time.time_id}`"
                                  class="w-11 rounded-md border border-emerald-300 bg-white px-1 py-0.5 text-center text-[11px] text-emerald-800 outline-none focus:border-emerald-500 disabled:opacity-50 dark:border-emerald-500/40 dark:bg-gray-900 dark:text-emerald-300"
                                  @change="updateTimeMaxClasses(course, termSlots(course, term).term, time, $event.target.value)"
                                >
                                <span class="text-emerald-600/70 dark:text-emerald-400/60">{{ time.max_classes ? '' : $t('∞') }}</span>
                              </template>
                              <span v-else class="text-slate-400 dark:text-gray-500">{{ $t('Closed') }}</span>
                            </div>
                          </div>
                        </div>
                        <span v-else class="block px-1 py-2 text-xs text-slate-300 dark:text-gray-600">—</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </template>
          </div>
        </template>
      </div>
    </section>
  </DashboardLayout>
</template>
