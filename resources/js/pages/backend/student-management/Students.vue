<script setup>
import { computed, reactive, ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { ArrowLeftRight, Clock3, Eye, PencilLine, Search, Trash2 } from '@lucide/vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { Pagination } from '@/components/ui/pagination'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'

const props = defineProps({
  students: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  courses: {
    type: Array,
    default: () => [],
  },
  times: {
    type: Array,
    default: () => [],
  },
  transferClasses: {
    type: Array,
    default: () => [],
  },
})

const todayIso = new Date().toLocaleDateString('en-CA')

const form = reactive({
  search: props.filters.search ?? '',
  course_id: props.filters.course_id ?? '',
  time_id: props.filters.time_id ?? '',
})

const permissionForm = useForm({
  reason: '',
  start_date: todayIso,
  end_date: todayIso,
  note: '',
})

const transferForm = useForm({
  study_class_id: '',
  effective_date: todayIso,
  reason: '',
})

const lateForm = useForm({
  reason: '',
})

const showPermissionModal = ref(false)
const showTransferModal = ref(false)
const showLateModal = ref(false)
const activeStudent = ref(null)

const rows = computed(() => props.students?.data ?? [])

const availableTransferClasses = computed(() => {
  if (!activeStudent.value) {
    return []
  }

  const currentCourseId = Number(activeStudent.value.current_course_id ?? 0)
  const currentClassId = Number(activeStudent.value.current_class_id ?? 0)

  return props.transferClasses.filter((item) => {
    const sameCourse = !currentCourseId || Number(item.course_id ?? 0) === currentCourseId
    const notCurrentClass = Number(item.id) !== currentClassId

    return sameCourse && notCurrentClass && !item.is_full
  })
})

function submit(page = 1) {
  router.get('/dashboard/student-management/students', { ...form, page }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

function resetFilters() {
  form.search = ''
  form.course_id = ''
  form.time_id = ''
  submit(1)
}

function rowNumber(index) {
  return (((props.students.current_page ?? 1) - 1) * (props.students.per_page ?? 15)) + index + 1
}

function formatDate(value) {
  if (!value) return '—'
  return new Date(value).toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

function formatAttendanceStatus(status) {
  if (!status) return '—'

  return String(status).replace(/_/g, ' ').replace(/^\w/, (letter) => letter.toUpperCase())
}

function openPermissionModal(student) {
  activeStudent.value = student
  permissionForm.clearErrors()
  permissionForm.reset()
  permissionForm.start_date = todayIso
  permissionForm.end_date = todayIso
  showPermissionModal.value = true
}

function closePermissionModal() {
  showPermissionModal.value = false
  activeStudent.value = null
  permissionForm.clearErrors()
  permissionForm.reset()
  permissionForm.start_date = todayIso
  permissionForm.end_date = todayIso
}

function openTransferModal(student) {
  activeStudent.value = student
  transferForm.clearErrors()
  transferForm.reset()
  transferForm.effective_date = todayIso
  showTransferModal.value = true
}

function closeTransferModal() {
  showTransferModal.value = false
  activeStudent.value = null
  transferForm.clearErrors()
  transferForm.reset()
  transferForm.effective_date = todayIso
}

function openLateModal(student) {
  activeStudent.value = student
  lateForm.clearErrors()
  lateForm.reset()
  showLateModal.value = true
}

function closeLateModal() {
  showLateModal.value = false
  activeStudent.value = null
  lateForm.clearErrors()
  lateForm.reset()
}

function submitPermission() {
  if (!activeStudent.value) return

  permissionForm.post(`/dashboard/student-management/students/${activeStudent.value.id}/permission`, {
    preserveScroll: true,
    onSuccess: () => {
      closePermissionModal()
    },
  })
}

function submitTransfer() {
  if (!activeStudent.value || !transferForm.study_class_id) return

  transferForm.post(`/dashboard/student-management/students/${activeStudent.value.id}/transfer`, {
    preserveScroll: true,
    onSuccess: () => {
      closeTransferModal()
    },
  })
}

function submitLate() {
  if (!activeStudent.value) return

  lateForm.post(`/dashboard/student-management/students/${activeStudent.value.id}/late`, {
    preserveScroll: true,
    onSuccess: () => {
      closeLateModal()
    },
  })
}

function transferClassLabel(item) {
  const parts = [
    item.title,
    item.course,
    item.term,
    item.time,
    item.teacher,
  ].filter(Boolean)

  return parts.join(' · ')
}
</script>

<template>
  <Head :title="$t('Student Management')" />

  <DashboardLayout>
    <section class="space-y-6">
      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center gap-3 border-b border-slate-200 pb-4 dark:border-gray-800">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-900 dark:bg-blue-500/10 dark:text-blue-400">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
              <path d="M12 12c2.761 0 5-2.239 5-5s-2.239-5-5-5-5 2.239-5 5 2.239 5 5 5Zm0 2c-4.418 0-8 2.239-8 5v1h16v-1c0-2.761-3.582-5-8-5Zm7.5-1h2.5v2h-2.5V17h-2v-2h-2.5v-2H18V10h2v2.5Z" />
            </svg>
          </div>
          <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-gray-100">Student Management</h1>
          </div>
        </div>

        <div class="mt-5 rounded-xl bg-slate-50 p-4 dark:bg-gray-950/40">
          <form class="grid gap-3 lg:grid-cols-[1.3fr_0.8fr_0.6fr_auto]" @submit.prevent="submit(1)">
            <div class="relative">
              <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500" />
              <input
                v-model="form.search"
                type="text"
                placeholder="Search by name, course, or instructor..."
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 pl-9 text-sm text-slate-700 outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
              >
            </div>

            <select v-model="form.course_id" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20">
              <option value="">All Courses</option>
              <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.title }}</option>
            </select>

            <select v-model="form.time_id" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20">
              <option value="">All Times</option>
              <option v-for="time in times" :key="time.id" :value="time.id">{{ time.time_name }}</option>
            </select>

            <div class="flex gap-2">
              <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500">
                Filter
              </button>
              <button type="button" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800" @click="resetFilters">
                Reset
              </button>
            </div>
          </form>
        </div>

        <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 dark:border-gray-800">
          <Table>
            <TableHeader>
              <TableRow class="bg-slate-50 dark:bg-gray-950/40">
                <TableHead class="w-16">#</TableHead>
                <TableHead>Name</TableHead>
                <TableHead>Gender</TableHead>
                <TableHead>Phone</TableHead>
                <TableHead>Course</TableHead>
                <TableHead>Instructor</TableHead>
                <TableHead>Created</TableHead>
                <TableHead>Permission</TableHead>
                <TableHead class="text-right">Action</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              <TableRow v-if="!rows.length">
                <TableCell colspan="9" class="py-10 text-center text-sm text-slate-500 dark:text-gray-400">
                  No students found.
                </TableCell>
              </TableRow>

              <TableRow v-for="(student, index) in rows" :key="student.id" class="hover:bg-slate-50 dark:hover:bg-gray-800/50">
                <TableCell class="text-slate-500 dark:text-gray-400">{{ rowNumber(index) }}</TableCell>
                <TableCell>
                  <div class="space-y-1">
                    <p class="font-semibold text-slate-900 dark:text-gray-100">{{ student.full_name }}</p>
                    <p class="text-xs text-slate-500 dark:text-gray-400">
                      {{ student.current_class_title || 'No active class' }}
                    </p>
                  </div>
                </TableCell>
                <TableCell>
                  <span
                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                    :class="student.gender === 'female'
                      ? 'bg-pink-100 text-pink-700 dark:bg-pink-500/10 dark:text-pink-300'
                      : 'bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300'"
                  >
                    {{ student.gender === 'female' ? 'Female' : 'Male' }}
                  </span>
                </TableCell>
                <TableCell class="text-slate-600 dark:text-gray-300">{{ student.phone ?? '—' }}</TableCell>
                <TableCell class="text-slate-600 dark:text-gray-300">{{ student.course ?? '—' }}</TableCell>
                <TableCell class="text-slate-600 dark:text-gray-300">{{ student.instructor ?? '—' }}</TableCell>
                <TableCell class="text-slate-600 dark:text-gray-300">{{ formatDate(student.created_at) }}</TableCell>
                <TableCell>
                  <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-800 dark:bg-indigo-600 dark:hover:bg-indigo-500"
                    :disabled="!student.current_class_id"
                    @click="openPermissionModal(student)"
                  >
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-md bg-white/10 text-xs">✉</span>
                    Permission
                  </button>
                </TableCell>
                <TableCell>
                  <div class="flex items-center justify-end gap-2">
                    <button type="button" class="rounded-md bg-red-500 p-2 text-white transition hover:bg-red-600" title="Delete">
                      <Trash2 class="h-3.5 w-3.5" />
                    </button>
                    <button type="button" class="rounded-md bg-slate-100 p-2 text-slate-700 transition hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700" title="View">
                      <Eye class="h-3.5 w-3.5" />
                    </button>
                    <button
                      type="button"
                      class="rounded-md bg-slate-100 p-2 text-slate-700 transition hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                      title="Transfer"
                      :disabled="!student.current_class_id"
                      @click="openTransferModal(student)"
                    >
                      <ArrowLeftRight class="h-3.5 w-3.5" />
                    </button>
                    <button type="button" class="rounded-md bg-slate-100 p-2 text-slate-700 transition hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700" title="Edit">
                      <PencilLine class="h-3.5 w-3.5" />
                    </button>
                    <button
                      type="button"
                      class="inline-flex items-center gap-1 rounded-md bg-amber-400 px-2.5 py-1 text-[11px] font-black tracking-wide text-amber-950 transition hover:bg-amber-300"
                      title="Mark as Late"
                      :disabled="!student.latest_attendance"
                      @click="openLateModal(student)"
                    >
                      <Clock3 class="h-3.5 w-3.5" />
                      LATE
                    </button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <div class="mt-5">
          <Pagination
            :current-page="students.current_page"
            :last-page="students.last_page"
            :disabled="students.last_page <= 1"
            @page-change="submit"
          />
        </div>
      </div>
    </section>

    <Teleport to="body">
      <div v-if="showPermissionModal && activeStudent" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 px-4" @click.self="closePermissionModal">
        <div class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-gray-800 dark:bg-gray-900">
          <div class="flex items-start justify-between gap-4 border-b border-slate-200 p-6 dark:border-gray-800">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.2em] text-indigo-500">Permission Request</p>
              <h3 class="mt-1 text-xl font-bold text-slate-900 dark:text-gray-100">{{ activeStudent.full_name }}</h3>
              <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                {{ activeStudent.course || 'No course' }} · {{ activeStudent.current_class_title || 'No active class' }}
              </p>
            </div>
            <button type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-500 transition hover:bg-slate-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800" @click="closePermissionModal">
              Close
            </button>
          </div>

          <form class="space-y-5 p-6" @submit.prevent="submitPermission">
            <div class="grid gap-4 sm:grid-cols-2">
              <label class="space-y-2">
                <span class="block text-sm font-semibold text-slate-700 dark:text-gray-300">Permission Start Date</span>
                <input
                  v-model="permissionForm.start_date"
                  type="date"
                  class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                >
                <p v-if="permissionForm.errors.start_date" class="text-xs font-semibold text-red-600 dark:text-red-400">{{ permissionForm.errors.start_date }}</p>
              </label>

              <label class="space-y-2">
                <span class="block text-sm font-semibold text-slate-700 dark:text-gray-300">Permission End Date</span>
                <input
                  v-model="permissionForm.end_date"
                  type="date"
                  class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                >
                <p v-if="permissionForm.errors.end_date" class="text-xs font-semibold text-red-600 dark:text-red-400">{{ permissionForm.errors.end_date }}</p>
              </label>
            </div>

            <label class="space-y-2">
              <span class="block text-sm font-semibold text-slate-700 dark:text-gray-300">Reason</span>
              <input
                v-model="permissionForm.reason"
                type="text"
                placeholder="Enter permission reason"
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
              >
              <p v-if="permissionForm.errors.reason" class="text-xs font-semibold text-red-600 dark:text-red-400">{{ permissionForm.errors.reason }}</p>
            </label>

            <label class="space-y-2">
              <span class="block text-sm font-semibold text-slate-700 dark:text-gray-300">Note</span>
              <textarea
                v-model="permissionForm.note"
                rows="3"
                placeholder="Optional note"
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
              ></textarea>
              <p v-if="permissionForm.errors.note" class="text-xs font-semibold text-red-600 dark:text-red-400">{{ permissionForm.errors.note }}</p>
            </label>

            <div class="flex justify-end gap-3 border-t border-slate-200 pt-5 dark:border-gray-800">
              <button
                type="button"
                class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                @click="closePermissionModal"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="permissionForm.processing"
                class="rounded-xl bg-indigo-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-indigo-600 dark:hover:bg-indigo-500"
              >
                {{ permissionForm.processing ? 'Saving...' : 'Save Permission' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div v-if="showTransferModal && activeStudent" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 px-4" @click.self="closeTransferModal">
        <div class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-gray-800 dark:bg-gray-900">
          <div class="flex items-start justify-between gap-4 border-b border-slate-200 p-6 dark:border-gray-800">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-500">Transfer Student</p>
              <h3 class="mt-1 text-xl font-bold text-slate-900 dark:text-gray-100">{{ activeStudent.full_name }}</h3>
              <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                Current class: {{ activeStudent.current_class_title || 'No active class' }}
              </p>
            </div>
            <button type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-500 transition hover:bg-slate-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800" @click="closeTransferModal">
              Close
            </button>
          </div>

          <form class="space-y-5 p-6" @submit.prevent="submitTransfer">
            <div class="grid gap-4 sm:grid-cols-2">
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400 dark:text-gray-500">Current Course</p>
                <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-gray-100">{{ activeStudent.course || '—' }}</p>
              </div>
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400 dark:text-gray-500">Current Class</p>
                <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-gray-100">{{ activeStudent.current_class_title || '—' }}</p>
              </div>
            </div>

            <label class="space-y-2 block">
              <span class="block text-sm font-semibold text-slate-700 dark:text-gray-300">Transfer To Class</span>
              <select
                v-model="transferForm.study_class_id"
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
              >
                <option value="">Select destination class</option>
                <option v-for="item in availableTransferClasses" :key="item.id" :value="item.id">
                  {{ transferClassLabel(item) }}{{ item.is_full ? ' (Full)' : '' }}
                </option>
              </select>
              <p v-if="transferForm.errors.study_class_id" class="text-xs font-semibold text-red-600 dark:text-red-400">{{ transferForm.errors.study_class_id }}</p>
            </label>

            <div class="grid gap-4 sm:grid-cols-2">
              <label class="space-y-2">
                <span class="block text-sm font-semibold text-slate-700 dark:text-gray-300">Effective Date</span>
                <input
                  v-model="transferForm.effective_date"
                  type="date"
                  class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                >
                <p v-if="transferForm.errors.effective_date" class="text-xs font-semibold text-red-600 dark:text-red-400">{{ transferForm.errors.effective_date }}</p>
              </label>

              <label class="space-y-2">
                <span class="block text-sm font-semibold text-slate-700 dark:text-gray-300">Reason</span>
                <input
                  v-model="transferForm.reason"
                  type="text"
                  placeholder="Optional transfer reason"
                  class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                >
                <p v-if="transferForm.errors.reason" class="text-xs font-semibold text-red-600 dark:text-red-400">{{ transferForm.errors.reason }}</p>
              </label>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-200 pt-5 dark:border-gray-800">
              <button
                type="button"
                class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                @click="closeTransferModal"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="transferForm.processing || !transferForm.study_class_id"
                class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-70"
              >
                {{ transferForm.processing ? 'Transferring...' : 'Transfer Student' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div v-if="showLateModal && activeStudent" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 px-4" @click.self="closeLateModal">
        <div class="w-full max-w-xl rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-gray-800 dark:bg-gray-900">
          <div class="flex items-start justify-between gap-4 border-b border-slate-200 p-6 dark:border-gray-800">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.2em] text-amber-500">Mark Late</p>
              <h3 class="mt-1 text-xl font-bold text-slate-900 dark:text-gray-100">{{ activeStudent.full_name }}</h3>
              <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                {{ formatAttendanceStatus(activeStudent.latest_attendance?.status) }} · {{ formatDate(activeStudent.latest_attendance?.attendance_date) }}
              </p>
            </div>
            <button type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-500 transition hover:bg-slate-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800" @click="closeLateModal">
              Close
            </button>
          </div>

          <form class="space-y-5 p-6" @submit.prevent="submitLate">
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-200">
              <p class="font-semibold">Current status: {{ formatAttendanceStatus(activeStudent.latest_attendance?.status) }}</p>
              <p class="mt-1">Late can only convert an absent finalized attendance. Permission stays permission.</p>
            </div>

            <label class="space-y-2 block">
              <span class="block text-sm font-semibold text-slate-700 dark:text-gray-300">Reason</span>
              <textarea
                v-model="lateForm.reason"
                rows="3"
                placeholder="Why should this attendance be corrected to late?"
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
              ></textarea>
              <p v-if="lateForm.errors.reason" class="text-xs font-semibold text-red-600 dark:text-red-400">{{ lateForm.errors.reason }}</p>
            </label>

            <div class="flex justify-end gap-3 border-t border-slate-200 pt-5 dark:border-gray-800">
              <button
                type="button"
                class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                @click="closeLateModal"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="lateForm.processing"
                class="rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-semibold text-amber-950 transition hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-70"
              >
                {{ lateForm.processing ? 'Saving...' : 'Mark as Late' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </DashboardLayout>
</template>
