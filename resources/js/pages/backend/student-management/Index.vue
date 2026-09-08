<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import {
    CalendarDays,
    Clock3,
    Pencil,
    Search,
    Shuffle,
    Trash2,
    UserCheck,
    X,
} from '@lucide/vue'

import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import { useConfirm } from '../../../composables/useConfirm'
import { useI18n } from '../../../i18n'

const props = defineProps({
    enrollments: Object,
    filters: Object,
    courses: Array,
    times: Array,
    classes: Array,
})

const search = ref(props.filters?.search || '')
const courseId = ref(props.filters?.course_id || '')
const timeId = ref(props.filters?.time_id || '')
let searchTimer

const dialog = ref(null)
const selected = ref(null)

const editForm = useForm({
    full_name: '',
    gender: '',
    phone: '',
})

const permissionForm = useForm({
    start_date: '',
    end_date: '',
    reason: '',
})

const transferForm = useForm({
    study_class_id: '',
    force: false,
})

const availableClasses = computed(() => props.classes || [])
const { confirm } = useConfirm()
const { t } = useI18n()

function emptyValue(value) {
    return value === '-' ? '' : (value ?? '')
}

function classOptionLabel(item) {
    const pieces = [item.title]

    if (item.course?.title && item.course.title !== item.title) {
        pieces.push(item.course.title)
    }

    if (item.time?.time_name) {
        pieces.push(item.time.time_name)
    }

    return pieces.filter(Boolean).join(' - ')
}

const transferClasses = computed(() => {
    return availableClasses.value.filter((item) => item.id !== selected.value?.study_class_id)
})

// =========================
// Filters
// =========================

function filter() {
    // Dashboard pages scroll inside the layout's <main>, not window. Reset it
    // when the result set changes so the new list starts at its header.
    document.querySelector('main.overflow-y-auto')?.scrollTo({
        top: 0,
        behavior: 'smooth',
    })

    router.get(
        '/dashboard/student-management',
        {
            search: search.value,
            course_id: courseId.value,
            time_id: timeId.value,
        },
        {
            preserveState: true,
            preserveScroll: false,
            replace: true,
        },
    )
}

watch(search, () => {
    window.clearTimeout(searchTimer)
    searchTimer = window.setTimeout(filter, 400)
})

onBeforeUnmount(() => window.clearTimeout(searchTimer))

// =========================
// Dialog
// =========================

function open(type, row) {
    selected.value = row
    dialog.value = type

    editForm.clearErrors()
    permissionForm.clearErrors()
    transferForm.clearErrors()

    if (type === 'edit') {
        editForm.reset()
        editForm.defaults({
            full_name: row.name,
            gender: emptyValue(row.gender),
            phone: emptyValue(row.phone),
        })
        editForm.full_name = row.name
        editForm.gender = emptyValue(row.gender)
        editForm.phone = emptyValue(row.phone)
    }

    if (type === 'permission') {
        permissionForm.defaults({
            start_date: '',
            end_date: '',
            reason: '',
        })
        permissionForm.reset()
    }

    if (type === 'transfer') {
        transferForm.defaults({
            study_class_id: '',
            force: false,
        })
        transferForm.reset()
    }
}

function close() {
    dialog.value = null
    selected.value = null
    editForm.reset()
    editForm.clearErrors()
    permissionForm.reset()
    permissionForm.clearErrors()
    transferForm.reset()
    transferForm.clearErrors()
}

// =========================
// Student Actions
// =========================

function submitEdit() {
    editForm.put(
        `/dashboard/student-management/students/${selected.value.student_id}`,
        {
            onSuccess: close,
        },
    )
}

function submitPermission() {
    permissionForm.post(
        `/dashboard/student-management/students/${selected.value.student_id}/permission`,
        {
            onSuccess: close,
        },
    )
}

function submitTransfer() {
    transferForm.put(
        `/dashboard/student-management/enrollments/${selected.value.enrollment_id}/transfer`,
        {
            onSuccess: close,
        },
    )
}

function markLate(row) {
    router.post(
        `/dashboard/student-management/enrollments/${row.enrollment_id}/late`,
    )
}

async function remove(row) {
    const confirmed = await confirm({
        title: t('Remove student from class?'),
        message: t('This will remove :name from :class.', { name: row.name, class: row.class }),
        confirmText: t('Remove'),
        cancelText: t('Cancel'),
        danger: true,
    })

    if (confirmed) {
        router.delete(`/dashboard/student-management/enrollments/${row.enrollment_id}`)
    }
}

function openAttendance(row) {
    router.visit(`/dashboard/student-management/enrollments/${row.enrollment_id}/attendance`)
}
</script>

<template>
    <DashboardLayout>
        <div class="space-y-5">

            <!-- ========================= -->
            <!-- Page Header -->
            <!-- ========================= -->

            <div
                class="flex items-center gap-3 border-b border-slate-200 pb-4
                       dark:border-gray-800"
            >
                <div
                    class="grid h-10 w-10 place-items-center rounded-lg
                           bg-indigo-50 text-indigo-700
                           dark:bg-indigo-500/10 dark:text-indigo-300"
                >
                    <UserCheck class="h-5 w-5" />
                </div>

                <div>
                    <h1
                        class="text-xl font-bold text-slate-900
                               dark:text-gray-100"
                    >
                        {{ $t('Student Management') }}
                    </h1>

                    <p class="text-sm text-slate-500 dark:text-gray-400">
                        {{ $t('Manage enrolled students and class attendance actions.') }}
                    </p>
                </div>
            </div>

            <!-- ========================= -->
            <!-- Filters -->
            <!-- ========================= -->

            <div
                class="grid gap-3 rounded-lg bg-slate-50 p-4
                       dark:bg-gray-900
                       sm:grid-cols-[minmax(220px,1fr)_200px_200px_auto]"
            >
                <!-- Search -->
                <label class="relative">
                    <Search
                        class="pointer-events-none absolute left-3 top-1/2
                               h-4 w-4 -translate-y-1/2 text-slate-400"
                    />

                    <input
                        v-model="search"
                        type="text"
                        :placeholder="$t('Search by student name...')"
                        class="h-10 w-full rounded-md border border-slate-200
                               bg-white pl-9 pr-3 text-sm
                               dark:border-gray-700 dark:bg-gray-800
                               dark:text-gray-100"
                    />
                </label>

                <!-- Course Filter -->
                <select
                    v-model="courseId"
                    class="h-10 rounded-md border border-slate-200
                           bg-white px-3 text-sm
                           dark:border-gray-700 dark:bg-gray-800
                           dark:text-gray-100"
                    @change="filter"
                >
                    <option value="">
                        {{ $t('All Courses') }}
                    </option>

                    <option
                        v-for="course in courses"
                        :key="course.id"
                        :value="course.id"
                    >
                        {{ course.title }}
                    </option>
                </select>

                <!-- Time Filter -->
                <select
                    v-model="timeId"
                    class="h-10 rounded-md border border-slate-200
                           bg-white px-3 text-sm
                           dark:border-gray-700 dark:bg-gray-800
                           dark:text-gray-100"
                    @change="filter"
                >
                    <option value="">
                        {{ $t('All Times') }}
                    </option>

                    <option
                        v-for="time in times"
                        :key="time.id"
                        :value="time.id"
                    >
                        {{ time.time_name }}
                    </option>
                </select>
            </div>

            <!-- ========================= -->
            <!-- Student Table -->
            <!-- ========================= -->

            <div
                class="overflow-hidden rounded-lg border border-slate-200
                       bg-white dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1120px] text-sm">
                        <thead
                            class="bg-slate-100 text-left text-[11px]
                                   font-bold uppercase tracking-wide
                                   text-slate-500
                                   dark:bg-gray-800 dark:text-gray-400"
                        >
                            <tr>
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">{{ $t('Name') }}</th>
                                <th class="px-4 py-3">{{ $t('Gender') }}</th>
                                <th class="px-4 py-3">{{ $t('Phone') }}</th>
                                <th class="px-4 py-3">{{ $t('Course') }}</th>
                                <th class="px-4 py-3">{{ $t('Instructor') }}</th>
                                <th class="px-4 py-3">{{ $t('Time') }}</th>
                                <th class="px-4 py-3">{{ $t('Created') }}</th>
                                <th class="px-4 py-3">{{ $t('Permission') }}</th>
                                <th class="px-4 py-3">{{ $t('Action') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="(row, index) in enrollments.data"
                                :key="row.enrollment_id"
                                class="cursor-pointer border-t border-slate-100
                                       transition hover:bg-slate-50
                                       dark:border-gray-800 dark:hover:bg-gray-800/50"
                                tabindex="0"
                                @click="openAttendance(row)"
                                @keydown.enter="openAttendance(row)"
                            >
                                <!-- Number -->
                                <td class="px-4 py-4 text-slate-400">
                                    {{
                                        (enrollments.current_page - 1) *
                                            enrollments.per_page +
                                        index +
                                        1
                                    }}
                                </td>

                                <!-- Student -->
                                <td
                                    class="px-4 py-4 font-semibold
                                           text-slate-800 dark:text-gray-100"
                                >
                                    {{ row.name }}

                                    <span
                                        class="mt-1 block text-xs font-normal
                                               text-slate-400"
                                    >
                                        {{ row.class }}
                                    </span>
                                </td>

                                <!-- Gender -->
                                <td class="px-4 py-4">
                                    <span
                                        class="rounded-full bg-blue-50
                                               px-2 py-1 text-xs text-blue-700
                                               dark:bg-blue-500/10
                                               dark:text-blue-300"
                                    >
                                        {{ row.gender }}
                                    </span>
                                </td>

                                <!-- Phone -->
                                <td
                                    class="px-4 py-4 text-slate-600
                                           dark:text-gray-300"
                                >
                                    {{ row.phone }}
                                </td>

                                <!-- Course -->
                                <td
                                    class="max-w-[190px] px-4 py-4
                                           text-slate-600 dark:text-gray-300"
                                >
                                    {{ row.course }}
                                </td>

                                <!-- Instructor -->
                                <td
                                    class="px-4 py-4 text-slate-600
                                           dark:text-gray-300"
                                >
                                    {{ row.instructor }}
                                </td>

                                <!-- Time -->
                                <td
                                    class="px-4 py-4 text-slate-600
                                           dark:text-gray-300"
                                >
                                    {{ row.time }}
                                </td>

                                <!-- Created -->
                                <td class="px-4 py-4 text-slate-500">
                                    {{ row.created }}
                                </td>

                                <!-- Permission -->
                                <td class="px-4 py-4">
                                    <button
                                        :title="$t('Grant permission')"
                                        class="inline-flex items-center gap-1.5
                                               rounded-md bg-indigo-800
                                               px-3 py-2 text-xs font-semibold
                                               text-white hover:bg-indigo-900"
                                        @click.stop="open('permission', row)"
                                    >
                                        <CalendarDays class="h-3.5 w-3.5" />
                                        {{ $t('Permission') }}
                                    </button>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-1">
                                        <!-- Delete -->
                                        <button
                                            :title="$t('Remove from class')"
                                            class="grid h-8 w-8 place-items-center
                                                   rounded-md text-red-500
                                                   hover:bg-red-50"
                                            @click.stop="remove(row)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                        <!-- Edit -->
                                        <button
                                            :title="$t('Edit student')"
                                            class="grid h-8 w-8 place-items-center
                                                   rounded-md text-slate-500
                                                   hover:bg-slate-100"
                                            @click.stop="open('edit', row)"
                                        >
                                            <Pencil class="h-4 w-4" />
                                        </button>

                                        <!-- Transfer -->
                                        <button
                                            :title="$t('Transfer class')"
                                            class="grid h-8 w-8 place-items-center
                                                   rounded-md text-slate-500
                                                   hover:bg-slate-100"
                                            @click.stop="open('transfer', row)"
                                        >
                                            <Shuffle class="h-4 w-4" />
                                        </button>

                                        <!-- Late -->
                                        <button
                                            :title="$t('Mark this student as late')"
                                            :aria-label="$t('Mark this student as late')"
                                            class="inline-flex items-center
                                                   gap-1.5 rounded-lg
                                                   bg-amber-100 px-3 py-2
                                                   text-xs font-semibold
                                                   text-amber-800 transition
                                                   hover:bg-amber-200
                                                   dark:bg-amber-500/10
                                                   dark:text-amber-300
                                                   dark:hover:bg-amber-500/20"
                                            @click.stop="markLate(row)"
                                        >
                                            <Clock3 class="h-3.5 w-3.5" />
                                            {{ $t('Late') }}
                                        </button>

                                      
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!enrollments.data?.length">
                                <td
                                    colspan="10"
                                    class="px-4 py-12 text-center
                                           text-sm text-slate-500"
                                >
                                    {{ $t('No students found.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ========================= -->
                <!-- Pagination -->
                <!-- ========================= -->

                <div
                    v-if="enrollments.last_page > 1"
                    class="flex flex-col gap-3 border-t border-slate-200
                           px-4 py-4 sm:flex-row sm:items-center
                           sm:justify-between dark:border-gray-800"
                >
                    <p class="text-sm text-slate-500 dark:text-gray-400">
                        Showing {{ enrollments.from ?? 0 }}
                        to {{ enrollments.to ?? 0 }}
                        of {{ enrollments.total ?? 0 }}
                    </p>

                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            v-for="link in enrollments.links"
                            :key="link.label"
                            type="button"
                            :disabled="!link.url"
                            class="inline-flex min-w-9 items-center
                                   justify-center rounded-lg border
                                   px-3 py-2 text-sm font-semibold
                                   transition disabled:cursor-not-allowed
                                   disabled:opacity-50"
                            :class="
                                link.active
                                    ? 'border-blue-900 bg-blue-900 text-white dark:border-blue-500 dark:bg-blue-600'
                                    : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800'
                            "
                            @click="
                                link.url &&
                                router.visit(link.url, {
                                    preserveState: true,
                                })
                            "
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================= -->
        <!-- Dialog -->
        <!-- ========================= -->

        <div
            v-if="dialog"
            class="fixed inset-0 z-50 grid place-items-center
                   bg-slate-950/40 p-4"
            @click.self="close"
        >
            <div
                class="w-full max-w-lg rounded-lg bg-white p-6
                       shadow-2xl dark:bg-gray-900"
            >
                <!-- Dialog Header -->
                <div class="flex items-center justify-between">
                    <h2
                        class="text-lg font-bold text-slate-900
                               dark:text-gray-100"
                    >
                        {{
                            dialog === 'permission'
                                ? $t('Grant Permission')
                                : dialog === 'transfer'
                                  ? $t('Transfer Student')
                                  : $t('Edit Student')
                        }}
                    </h2>

                    <button
                        type="button"
                        class="grid h-8 w-8 place-items-center
                               rounded-md hover:bg-slate-100
                               dark:hover:bg-gray-800"
                        @click="close"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <!-- ========================= -->
                <!-- Permission Form -->
                <!-- ========================= -->

                <form
                    v-if="dialog === 'permission'"
                    class="mt-5 space-y-4"
                    @submit.prevent="submitPermission"
                >
                    <div>
                        <label class="label">
                            {{ $t('Reason') }}
                        </label>

                        <textarea
                            v-model="permissionForm.reason"
                            required
                            rows="3"
                            class="input"
                            :placeholder="$t('Reason for permission')"
                        />
                        <p v-if="permissionForm.errors.reason" class="error">
                            {{ permissionForm.errors.reason }}
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">
                                {{ $t('Start date') }}
                            </label>

                            <input
                                v-model="permissionForm.start_date"
                                required
                                type="date"
                                class="input"
                            />
                            <p v-if="permissionForm.errors.start_date" class="error">
                                {{ permissionForm.errors.start_date }}
                            </p>
                        </div>

                        <div>
                            <label class="label">
                                {{ $t('End date') }}
                            </label>

                            <input
                                v-model="permissionForm.end_date"
                                required
                                type="date"
                                class="input"
                            />
                            <p v-if="permissionForm.errors.end_date" class="error">
                                {{ permissionForm.errors.end_date }}
                            </p>
                        </div>
                    </div>

                    <button
                        class="primary"
                        :disabled="permissionForm.processing"
                    >
                        {{ $t('Save Permission') }}
                    </button>
                </form>

                <!-- ========================= -->
                <!-- Transfer Form -->
                <!-- ========================= -->

                <form
                    v-else-if="dialog === 'transfer'"
                    class="mt-5 space-y-4"
                    @submit.prevent="submitTransfer"
                >
                    <div>
                        <label class="label">
                            {{ $t('New class') }}
                        </label>

                        <select
                            v-model="transferForm.study_class_id"
                            required
                            class="input truncate"
                        >
                            <option value="">
                                {{ $t('Select a class') }}
                            </option>

                            <option
                                v-for="item in transferClasses"
                                :key="item.id"
                                :value="item.id"
                                :title="classOptionLabel(item)"
                            >
                                {{ classOptionLabel(item) }}
                            </option>
                        </select>
                        <p v-if="transferForm.errors.study_class_id" class="error">
                            {{ transferForm.errors.study_class_id }}
                        </p>
                    </div>

                    <label
                        class="flex items-center gap-2
                               text-sm text-slate-600"
                    >
                        <input
                            v-model="transferForm.force"
                            type="checkbox"
                        />

                        {{ $t('Allow transfer into a full class') }}
                    </label>

                    <button
                        class="primary"
                        :disabled="transferForm.processing"
                    >
                        {{ $t('Transfer Student') }}
                    </button>
                </form>

                <!-- ========================= -->
                <!-- Edit Form -->
                <!-- ========================= -->

                <form
                    v-else
                    class="mt-5 space-y-4"
                    @submit.prevent="submitEdit"
                >
                    <div>
                        <label class="label">
                            {{ $t('Full name') }}
                        </label>

                        <input
                            v-model="editForm.full_name"
                            required
                            class="input"
                        />
                        <p v-if="editForm.errors.full_name" class="error">
                            {{ editForm.errors.full_name }}
                        </p>
                    </div>

                    <div>
                        <label class="label">
                            Gender
                        </label>

                        <input
                            v-model="editForm.gender"
                            class="input"
                        />
                        <p v-if="editForm.errors.gender" class="error">
                            {{ editForm.errors.gender }}
                        </p>
                    </div>

                    <div>
                        <label class="label">
                            Phone
                        </label>

                        <input
                            v-model="editForm.phone"
                            class="input"
                        />
                        <p v-if="editForm.errors.phone" class="error">
                            {{ editForm.errors.phone }}
                        </p>
                    </div>

                    <button
                        class="primary"
                        :disabled="editForm.processing"
                    >
                        Save Changes
                    </button>
                </form>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.label {
    display: block;
    margin-bottom: 0.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #334155;
}

.dark .label {
    color: #d1d5db;
}

.input {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 0.375rem;
    background: #fff;
    padding: 0.625rem 0.75rem;
    font-size: 0.875rem;
    outline: none;
}

.dark .input {
    border-color: #374151;
    background: #1f2937;
    color: #f3f4f6;
}

.error {
    margin-top: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #dc2626;
}

.primary {
    width: 100%;
    border: 0;
    border-radius: 0.375rem;
    background: #4338ca;
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #fff;
}

.primary:disabled {
    opacity: 0.5;
}

</style>
