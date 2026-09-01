<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch, watchEffect } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import axios from 'axios'
import {
    Award,
    Bookmark,
    BookOpen,
    CheckCircle2,
    ChevronLeft,
    CalendarDays,
    Loader2,
    Printer,
    Save,
    Trash2,
    User,
    Users,
    X,
} from '@lucide/vue'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import RealCertificatePreview from './CertificatePreview.vue'
import FreeCertificatePreview from './FreeCertificatePreview.vue'

const props = defineProps({
    type: { type: String, default: 'free' },
    freeCertificates: { type: Object, default: () => ({ data: [], meta: {}, course_filter: '' }) },
    freeCourses: { type: Array, default: () => [] },
    normalCourses: { type: Array, default: () => [] },
    certificateRequests: { type: Array, default: () => [] },
    generatedIds: { type: Object, default: () => ({ free: '', normal: '' }) },
})

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.withCredentials = true

const certificateType = computed(() => props.type === 'meal' ? 'meal' : props.type)
const isNormal = computed(() => certificateType.value === 'normal')
const isFree = computed(() => certificateType.value === 'free')
const isClassCertificate = computed(() => !isFree.value)

const pageTitle = computed(() => ({
    free: 'សញ្ញាបត្រថ្នាក់ Free',
    normal: 'តារាង សញ្ញាបត្រធម្មតា',
    scholarship: 'សញ្ញាបត្រអាហាររូបករណ៍',
    meal: 'សញ្ញាបត្រអាហាររូបករណ៍',
}[certificateType.value] ?? 'សញ្ញាបត្រ'))

const classRows = ref([])
const classLoading = ref(false)
const selectedCategory = ref('all')
const selectedCourse = ref('all')
const categoryPages = reactive({})
const selectedClass = ref(null)
const selectedStudent = ref(null)
const students = ref([])
const studentsLoading = ref(false)
const modalMode = ref(null)
const isPrintAllMode = ref(false)
const printSaving = ref(false)
const normalCertificateId = ref(props.generatedIds.normal)
const savedCourses = ref([...props.normalCourses])
const studentDrafts = ref([])

const freeForm = reactive({
    student_name: '',
    course: '',
    end_date: new Date().toISOString().slice(0, 10),
})
const freeErrors = ref({})
const freeSaving = ref(false)
const freeCertificateId = ref(props.generatedIds.free)

const printForm = reactive({
    student_name: '',
    course: '',
    granted_date: new Date().toISOString().slice(0, 10),
    certificate_id: props.generatedIds.normal,
    director: 'Mr. HENG PHEAKNA',
})

const perPage = 9

const categories = computed(() => {
    const values = classRows.value.map((item) => item.category || 'General')
    return ['all', ...new Set(values)]
})

const courseOptions = computed(() => {
    const values = classRows.value
        .filter((item) => selectedCategory.value === 'all' || item.category === selectedCategory.value)
        .map((item) => item.course || 'Untitled Course')

    return ['all', ...new Set(values)]
})

const filteredClasses = computed(() => classRows.value.filter((item) => {
    const matchesCategory = selectedCategory.value === 'all' || item.category === selectedCategory.value
    const matchesCourse = selectedCourse.value === 'all' || item.course === selectedCourse.value

    return matchesCategory && matchesCourse
}))

const groupedClasses = computed(() => filteredClasses.value.reduce((groups, item) => {
    const key = item.category || 'General'
    groups[key] ??= []
    groups[key].push(item)
    return groups
}, {}))

const pagedGroups = computed(() => Object.entries(groupedClasses.value).reduce((groups, [category, items]) => {
    const lastPage = Math.max(Math.ceil(items.length / perPage), 1)
    const page = Math.min(categoryPages[category] || 1, lastPage)
    const start = (page - 1) * perPage

    groups[category] = {
        items: items.slice(start, start + perPage),
        page,
        lastPage,
    }

    return groups
}, {}))

const totalRequested = computed(() => filteredClasses.value.reduce((sum, item) => sum + Number(item.total_students || 0), 0))
const totalPrinted = computed(() => filteredClasses.value.reduce((sum, item) => sum + Number(item.printed_students || 0), 0))
const totalFinishedCourses = computed(() => filteredClasses.value.length)
const hasCertificateRequests = computed(() => props.certificateRequests.length > 0)

const currentCertificate = computed(() => ({
    student_name: printForm.student_name || 'STUDENT NAME',
    course: printForm.course || selectedClass.value?.course || 'COURSE NAME',
    granted_date: formatReadableDate(printForm.granted_date),
    certificate_id: printForm.certificate_id || normalCertificateId.value || '0000000 ETEC',
    director: printForm.director || 'Mr. HENG PHEAKNA',
}))

watch(() => props.type, () => {
    closeModal()
    selectedClass.value = null
    if (isClassCertificate.value) loadClasses()
})

watch(selectedCategory, () => {
    selectedCourse.value = 'all'
})

onMounted(() => {
    if (isClassCertificate.value) loadClasses()
})

let freePrintStyleElement = null

watchEffect(() => {
    if (typeof document === 'undefined') return

    document.body.classList.toggle('free-certificate-print', isFree.value)

    if (isFree.value && !freePrintStyleElement) {
        freePrintStyleElement = document.createElement('style')
        freePrintStyleElement.dataset.freeCertificatePrint = 'true'
        freePrintStyleElement.textContent = `
            @media print {
                @page { size: A4 landscape; margin: 0; }
                html, body {
                    width: 297mm !important;
                    height: 210mm !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    overflow: hidden !important;
                    background: #fff !important;
                }
                body.free-certificate-print > *:not(#class-free-cert-print) { display: none !important; }
                body.free-certificate-print #app { display: none !important; }
                body.free-certificate-print #class-free-cert-print,
                body.free-certificate-print #class-free-cert-print * { visibility: visible !important; }
                body.free-certificate-print #class-free-cert-print {
                    position: fixed !important;
                    inset: 0 !important;
                    z-index: 999999 !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    width: 297mm !important;
                    height: 210mm !important;
                    min-height: 210mm !important;
                    margin: 0 !important;
                    border-radius: 0 !important;
                    background: #fff !important;
                    padding: 0 !important;
                    box-shadow: none !important;
                    overflow: hidden !important;
                }
                body.free-certificate-print #class-free-cert-print .certificate-free-wrap {
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    width: 297mm !important;
                    height: 210mm !important;
                }
                body.free-certificate-print #class-free-cert-print .certificate-free {
                    box-sizing: border-box !important;
                    width: 297mm !important;
                    height: 210mm !important;
                    min-height: 210mm !important;
                    padding: 5mm !important;
                }
                body.free-certificate-print #class-free-cert-print .cert-free-logo-box {
                    width: 110px !important;
                    height: 110px !important;
                }
                body.free-certificate-print #class-free-cert-print .cert-free-motto { margin-top: 4px !important; font-size: 19px !important; }
                body.free-certificate-print #class-free-cert-print .cert-free-kingdom { font-size: 19px !important; line-height: 1.5 !important; }
                body.free-certificate-print #class-free-cert-print .cert-free-kingdom img { max-width: 150px !important; margin-top: 4px !important; }
                body.free-certificate-print #class-free-cert-print .cert-free-title { margin-top: 18px !important; font-size: 56px !important; line-height: 1 !important; }
                body.free-certificate-print #class-free-cert-print .cert-free-certify { margin-top: 20px !important; font-size: 29px !important; }
                body.free-certificate-print #class-free-cert-print .cert-free-student-name { margin: 18px 0 16px !important; font-size: 34px !important; -webkit-text-stroke: 1px #000 !important; }
                body.free-certificate-print #class-free-cert-print .cert-free-desc { font-size: 24px !important; line-height: 1.45 !important; }
                body.free-certificate-print #class-free-cert-print .cert-free-course { width: 500px !important; min-height: 40px !important; margin: 10px auto !important; font-size: 24px !important; }
                body.free-certificate-print #class-free-cert-print .cert-free-granted { margin-bottom: 20px !important; font-size: 18px !important; }
                body.free-certificate-print #class-free-cert-print .cert-free-bottom { margin-top: 5px !important; }
                body.free-certificate-print #class-free-cert-print .cert-free-id-bottom { font-size: 16px !important; }
                body.free-certificate-print #class-free-cert-print .cert-free-sig-line { width: 200px !important; }
                body.free-certificate-print #class-free-cert-print .cert-free-sig-name,
                body.free-certificate-print #class-free-cert-print .cert-free-sig-role { font-size: 18px !important; }
            }
        `
        document.head.appendChild(freePrintStyleElement)
    }

    if (!isFree.value && freePrintStyleElement) {
        freePrintStyleElement.remove()
        freePrintStyleElement = null
    }
})

onUnmounted(() => {
    document.body.classList.remove('free-certificate-print')
    freePrintStyleElement?.remove()
    freePrintStyleElement = null
})

function remainingStudents(item) {
    return Math.max(Number(item.total_students || 0) - Number(item.printed_students || 0), 0)
}

function formatReadableDate(value) {
    const date = value instanceof Date ? value : new Date(`${value || new Date().toISOString().slice(0, 10)}T00:00:00`)

    if (Number.isNaN(date.getTime())) return value

    return new Intl.DateTimeFormat('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    }).format(date)
}

function certificateDateFromEndDate(value) {
    if (!value) return formatReadableDate(new Date())
    const date = new Date(`${value}T00:00:00`)
    if (Number.isNaN(date.getTime())) return value

    if (date.getDate() <= 10) {
        date.setMonth(date.getMonth() - 1)
    }

    date.setDate(15)
    return formatReadableDate(date)
}

async function loadClasses() {
    classLoading.value = true
    try {
        const { data } = await axios.get('/dashboard/certificates/classes', {
            params: { type: certificateType.value },
        })
        classRows.value = data.data || []
    } finally {
        classLoading.value = false
    }
}

async function openStudents(studyClass, openBulkModal = false) {
    selectedClass.value = studyClass
    students.value = []
    studentsLoading.value = true

    if (openBulkModal) {
        openBlankCertificateModal(studyClass)
    }

    try {
        const { data } = await axios.get(`/dashboard/certificates/classes/${studyClass.id}/students`, {
            params: { type: certificateType.value },
        })
        students.value = data.data || []

        if (openBulkModal) {
            if (students.value.length) {
                await openPrintModal(students.value[0], true)
            }
        }
    } catch (error) {
        if (!openBulkModal) {
            openBlankCertificateModal(studyClass)
        }
    } finally {
        studentsLoading.value = false
    }
}

async function openFirstClassForCreate() {
    const firstClass = filteredClasses.value[0]
    if (!firstClass) {
        openBlankCertificateModal()
        return
    }

    await openStudents(firstClass, true)
}

function openBlankCertificateModal(studyClass = null) {
    selectedClass.value = studyClass
    selectedStudent.value = null
    students.value = []
    studentDrafts.value = []
    isPrintAllMode.value = false
    printForm.student_name = 'STUDENT NAME'
    printForm.course = studyClass?.course || 'COURSE NAME'
    printForm.granted_date = new Date().toISOString().slice(0, 10)
    printForm.director = 'Mr. HENG PHEAKNA'
    refreshId('normal')
    modalMode.value = 'print'
}

async function openClassCertificateModal(studyClass) {
    await openStudents(studyClass, true)
}

function backToClasses() {
    selectedClass.value = null
    students.value = []
    closeModal()
}

function openCreateModal() {
    if (!selectedClass.value) return

    studentDrafts.value = students.value.map((student) => ({
        ...student,
        draft_name: student.name,
    }))
    printForm.course = selectedClass.value.course || ''
    printForm.granted_date = new Date().toISOString().slice(0, 10)
    modalMode.value = 'create'
}

async function openPrintModal(student, printAll = false) {
    selectedStudent.value = student
    isPrintAllMode.value = printAll
    studentDrafts.value = printAll
        ? students.value.map((item) => ({
            ...item,
            draft_name: item.id === student.id ? printForm.student_name || item.name : item.name,
        }))
        : []
    printForm.student_name = student.name
    printForm.course = selectedClass.value?.course || ''
    printForm.granted_date = new Date().toISOString().slice(0, 10)
    printForm.director = 'Mr. HENG PHEAKNA'
    await refreshId('normal')
    modalMode.value = 'print'
}

function closeModal() {
    modalMode.value = null
    selectedStudent.value = null
    isPrintAllMode.value = false
    studentDrafts.value = []
}

function applySavedCourse(courseName) {
    if (courseName) {
        printForm.course = courseName
    }
}

async function saveCourse() {
    if (!printForm.course.trim()) return

    await axios.post('/dashboard/certificates/courses', {
        course_name: printForm.course.trim(),
        scope: 'normal',
    })

    if (!savedCourses.value.some((item) => item.course_name === printForm.course.trim())) {
        savedCourses.value.push({ course_name: printForm.course.trim() })
    }
}

async function deleteSavedCourse() {
    if (!printForm.course.trim()) return

    await axios.delete('/dashboard/certificates/courses', {
        data: {
            course_name: printForm.course.trim(),
            scope: 'normal',
        },
    })

    savedCourses.value = savedCourses.value.filter((item) => item.course_name !== printForm.course.trim())
}

async function savePrintedStudent(student, studentName = student.name) {
    if (!selectedClass.value) return

    const certificateId = student.certificate_id || await nextCertificateId()

    await axios.post('/dashboard/certificates/printed', {
        student_id: student.id,
        study_class_id: selectedClass.value.id,
        certificate_type: certificateType.value,
        student_name: studentName,
        course: printForm.course,
        granted_date: formatReadableDate(printForm.granted_date),
        certificate_id: certificateId,
    })

    student.is_printed = true
    normalCertificateId.value = certificateId
    printForm.certificate_id = certificateId
}

async function saveDraftStudent(student) {
    await savePrintedStudent(student, student.draft_name)
}

async function printSingle() {
    const student = selectedStudent.value
    if (!printForm.course.trim()) return

    window.print()
    if (!window.confirm('Printed successfully?')) return

    if (!student) return

    printSaving.value = true
    try {
        await savePrintedStudent(student, printForm.student_name)
        await loadClasses()
    } finally {
        printSaving.value = false
    }
}

async function printAllDrafts() {
    if (!studentDrafts.value.length || !printForm.course.trim()) return

    studentDrafts.value = studentDrafts.value.map((student) => ({
        ...student,
        draft_name: selectedStudent.value?.id === student.id ? printForm.student_name : student.draft_name,
    }))
    await assignDraftCertificateIds()

    window.print()
    if (!window.confirm('Printed successfully?')) return

    printSaving.value = true
    try {
        for (const student of studentDrafts.value) {
            await savePrintedStudent(student, student.draft_name)
        }
        await loadClasses()
    } finally {
        printSaving.value = false
    }
}

async function refreshId(scope = 'normal') {
    const { data } = await axios.get('/dashboard/certificates/generate-id', { params: { scope } })
    if (scope === 'free') freeCertificateId.value = data.id
    else {
        normalCertificateId.value = data.id
        printForm.certificate_id = data.id
    }
}

async function nextCertificateId() {
    const { data } = await axios.get('/dashboard/certificates/generate-id', {
        params: { scope: 'normal' },
    })

    return data.id
}

async function assignDraftCertificateIds() {
    const firstId = await nextCertificateId()
    const ids = buildCertificateIds(firstId, studentDrafts.value.length)

    studentDrafts.value = studentDrafts.value.map((student, index) => ({
        ...student,
        certificate_id: ids[index],
    }))
}

function buildCertificateIds(firstId, count) {
    const match = String(firstId).trim().match(/^(\d{4})(\d{3,})\s*(.*)$/)
    if (!match) return Array.from({ length: count }, () => firstId)

    const [, prefix, sequence, suffix] = match
    const width = sequence.length
    const start = Number(sequence)

    return Array.from({ length: count }, (_, index) => {
        return `${prefix}${String(start + index).padStart(width, '0')} ${suffix || 'ETEC'}`.trim()
    })
}

function clearFreeError(field) {
    if (!freeErrors.value[field]) return

    freeErrors.value = {
        ...freeErrors.value,
        [field]: '',
    }
}

function updateFreeStudentName(value) {
    freeForm.student_name = value.toUpperCase()
    clearFreeError('student_name')
}

function updateFreeCourse(value) {
    freeForm.course = value.toUpperCase()
    clearFreeError('course')
}

function saveFreeAfterPrint() {
    freeErrors.value = {}
    if (!freeForm.student_name.trim()) freeErrors.value.student_name = 'Full Name is required!'
    if (!freeForm.course.trim()) freeErrors.value.course = 'Course is required.'
    if (!freeForm.end_date) freeErrors.value.end_date = 'End date is required.'
    if (Object.keys(freeErrors.value).length) return

    window.print()
    if (!window.confirm('Printed successfully?')) return

    freeSaving.value = true
    router.post('/dashboard/certificates/free', {
        student_name: freeForm.student_name,
        course: freeForm.course,
        end_date: freeForm.end_date,
    }, {
        preserveScroll: true,
        onFinish: () => {
            freeSaving.value = false
            refreshId('free')
        },
    })
}
</script>

<template>
    <Head :title="pageTitle" />

    <DashboardLayout>
        <section v-if="isClassCertificate" class="normal-certificate-page">
            <template v-if="!selectedClass">
                <header class="normal-toolbar no-print">
                    <h1>{{ pageTitle }}</h1>

                    <div class="normal-actions">
                        <select v-model="selectedCategory" class="filter-select">
                            <option v-for="category in categories" :key="category" :value="category">
                                {{ category === 'all' ? 'ទាំងអស់' : category }}
                            </option>
                        </select>

                        <select v-model="selectedCourse" class="filter-select">
                            <option v-for="course in courseOptions" :key="course" :value="course">
                                {{ course === 'all' ? 'ជ្រើសរើស' : course }}
                            </option>
                        </select>

                        <button class="blue-action" type="button" @click="openFirstClassForCreate">
                            <Award class="h-5 w-5" />
                            បង្កើតវិញ្ញាបនបត្រ
                        </button>
                    </div>
                </header>

                <div v-if="hasCertificateRequests" class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm no-print dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4 dark:border-gray-800">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-amber-500 dark:text-amber-400">Certificate Requests</p>
                            <h2 class="mt-1 text-lg font-black text-slate-950 dark:text-gray-100">Requests waiting in super admin</h2>
                        </div>
                        <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-black text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
                            {{ certificateRequests.length }} request{{ certificateRequests.length === 1 ? '' : 's' }}
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-[860px] w-full border-collapse text-sm">
                            <thead>
                                <tr class="bg-slate-50 text-xs font-black uppercase tracking-[0.08em] text-slate-500 dark:bg-gray-950 dark:text-gray-400">
                                    <th class="border-b border-slate-200 px-4 py-3 text-left dark:border-gray-800">Class</th>
                                    <th class="border-b border-slate-200 px-4 py-3 text-left dark:border-gray-800">Teacher</th>
                                    <th class="border-b border-slate-200 px-4 py-3 text-center dark:border-gray-800">Students</th>
                                    <th class="border-b border-slate-200 px-4 py-3 text-center dark:border-gray-800">Status</th>
                                    <th class="border-b border-slate-200 px-4 py-3 text-left dark:border-gray-800">Requested By</th>
                                    <th class="border-b border-slate-200 px-4 py-3 text-left dark:border-gray-800">Requested At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="request in certificateRequests" :key="request.id" class="transition hover:bg-slate-50/80 dark:hover:bg-gray-800/50">
                                    <td class="border-b border-slate-100 px-4 py-3 font-semibold text-slate-900 dark:border-gray-800 dark:text-gray-100">
                                        <div class="flex items-start gap-2">
                                            <Bookmark class="mt-0.5 h-4 w-4 shrink-0 text-amber-500 dark:text-amber-400" />
                                            <div>
                                                <p class="font-black">{{ request.class_title }}</p>
                                                <p class="text-xs text-slate-500 dark:text-gray-400">{{ request.course }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="border-b border-slate-100 px-4 py-3 text-slate-700 dark:border-gray-800 dark:text-gray-300">
                                        {{ request.teacher_name }}
                                    </td>
                                    <td class="border-b border-slate-100 px-4 py-3 text-center font-black text-slate-900 dark:border-gray-800 dark:text-gray-100">
                                        {{ request.student_count }}
                                    </td>
                                    <td class="border-b border-slate-100 px-4 py-3 text-center dark:border-gray-800">
                                        <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-black text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
                                            {{ request.status_label }}
                                        </span>
                                    </td>
                                    <td class="border-b border-slate-100 px-4 py-3 text-slate-700 dark:border-gray-800 dark:text-gray-300">
                                        {{ request.requested_by }}
                                    </td>
                                    <td class="border-b border-slate-100 px-4 py-3 text-slate-700 dark:border-gray-800 dark:text-gray-300">
                                        {{ request.requested_at ?? '-' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="normal-summary no-print">
                    <article>
                        <span><Users class="h-5 w-5" /></span>
                        <div>
                            <p>Total Student Request Certificate</p>
                            <strong>{{ totalRequested }}</strong>
                        </div>
                    </article>
                    <article>
                        <span><BookOpen class="h-5 w-5" /></span>
                        <div>
                            <p>Total Course Finish</p>
                            <strong>{{ totalFinishedCourses }}</strong>
                        </div>
                    </article>
                    <article>
                        <span><Award class="h-5 w-5" /></span>
                        <div>
                            <p>Total Certificate Done</p>
                            <strong>{{ totalPrinted }}</strong>
                        </div>
                    </article>
                </div>

                <div v-if="classLoading" class="loading-card no-print">
                    <Loader2 class="h-6 w-6 animate-spin" />
                    កំពុងទាញយកទិន្នន័យ...
                </div>

                <div v-else-if="!filteredClasses.length" class="loading-card no-print">
                    មិនមានវគ្គសិក្សាសម្រាប់សញ្ញាបត្រធម្មតាទេ
                </div>

                <div v-else class="category-stack no-print">
                    <article v-for="(group, category) in pagedGroups" :key="category" class="category-card">
                        <h2>ប្រភេទវគ្គសិក្សា {{ category }}</h2>

                        <div class="table-wrap">
                            <table class="class-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>គ្រូបង្រៀន</th>
                                        <th>មុខវិជ្ជា</th>
                                        <th>ម៉ោង</th>
                                        <th>ចំនួនសិស្សដែលនៅសល់</th>
                                        <th>សិស្ស</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in group.items" :key="item.id">
                                        <td>{{ item.id }}</td>
                                        <td>{{ item.teacher_name }}</td>
                                        <td>{{ item.course }}</td>
                                        <td>{{ item.time }}</td>
                                        <td>
                                            <span :class="['count-badge', remainingStudents(item) === 0 ? 'done' : 'pending']">
                                                {{ remainingStudents(item) }}
                                                <CheckCircle2 v-if="remainingStudents(item) === 0" class="h-3 w-3" />
                                            </span>
                                        </td>
                                        <td>
                                            <div class="row-actions">
                                                <button class="view-students" type="button" @click="openStudents(item)">
                                                    <Users class="h-4 w-4" />
                                                    មើលសិស្ស
                                                </button>
                                                <button class="view-students make-cert" type="button" @click="openClassCertificateModal(item)">
                                                    <Award class="h-4 w-4" />
                                                    បង្កើត
                                                </button>
                                                <span v-if="remainingStudents(item) === 0" class="complete-mark">
                                                    <CheckCircle2 class="h-4 w-4" />
                                                    <CheckCircle2 class="h-4 w-4" />
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-if="group.lastPage > 1" class="pagination-row">
                            <button
                                v-for="page in group.lastPage"
                                :key="page"
                                type="button"
                                :class="{ active: group.page === page }"
                                @click="categoryPages[category] = page"
                            >
                                {{ page }}
                            </button>
                            <button
                                type="button"
                                :disabled="group.page >= group.lastPage"
                                @click="categoryPages[category] = group.page + 1"
                            >
                                បន្ទាប់
                            </button>
                        </div>
                    </article>
                </div>
            </template>

            <template v-else>
                <div class="detail-toolbar no-print">
                    <button class="back-button" type="button" @click="backToClasses">
                        <ChevronLeft class="h-5 w-5" />
                        ត្រឡប់ក្រោយ
                    </button>

                    <div class="detail-buttons">
                        <button class="green-action" type="button" :disabled="studentsLoading || !students.length" @click="openPrintModal(students[0], true)">
                            <Printer class="h-5 w-5" />
                            បោះពុម្ពទាំងអស់
                        </button>
                        <button class="purple-action" type="button" :disabled="studentsLoading || !students.length" @click="openPrintModal(students[0], true)">
                            <Award class="h-5 w-5" />
                            បង្កើតសញ្ញាបត្រ
                        </button>
                    </div>
                </div>

                <article class="info-card no-print">
                    <header>
                        <span><BookOpen class="h-7 w-7" /></span>
                        <div>
                            <h2>ព័ត៌មានវគ្គសិក្សា</h2>
                            <p>Class Information</p>
                        </div>
                    </header>
                    <div class="info-grid">
                        <div>
                            <p>មុខវិជ្ជា</p>
                            <strong>{{ selectedClass.course }}</strong>
                        </div>
                        <div>
                            <p>គ្រូបង្រៀន</p>
                            <strong>{{ selectedClass.teacher_name }}</strong>
                        </div>
                        <div>
                            <p>ម៉ោងរៀន</p>
                            <strong>{{ selectedClass.time }}</strong>
                        </div>
                    </div>
                </article>

                <article class="students-card no-print">
                    <header>
                        <div class="section-title">
                            <span><Users class="h-7 w-7" /></span>
                            <div>
                                <h2>បញ្ជីឈ្មោះសិស្ស</h2>
                                <p>Student List</p>
                            </div>
                        </div>
                        <strong>{{ students.length }} នាក់</strong>
                    </header>

                    <div v-if="studentsLoading" class="loading-card">
                        <Loader2 class="h-6 w-6 animate-spin" />
                        កំពុងទាញយកសិស្ស...
                    </div>

                    <div v-else class="table-wrap">
                        <table class="student-table">
                            <thead>
                                <tr>
                                    <th>ល.រ</th>
                                    <th>ឈ្មោះសិស្ស</th>
                                    <th>ភេទ</th>
                                    <th>លេខទូរស័ព្ទ</th>
                                    <th>មុខវិជ្ជា</th>
                                    <th>សកម្មភាព</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="student in students" :key="student.id">
                                    <td><span class="student-id">{{ student.id }}</span></td>
                                    <td>
                                        <div class="student-name">
                                            <span><User class="h-4 w-4" /></span>
                                            <strong>{{ student.name }}</strong>
                                        </div>
                                    </td>
                                    <td><span class="gender-pill">{{ student.gender }}</span></td>
                                    <td>{{ student.tel }}</td>
                                    <td>{{ selectedClass.course }}</td>
                                    <td>
                                        <button class="print-button" type="button" @click="openPrintModal(student)">
                                            <Printer class="h-4 w-4" />
                                            Print
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </article>
            </template>

            <div v-if="modalMode === 'create'" class="modal-shell no-print">
                <div class="certificate-modal create-modal">
                    <header class="modal-header">
                        <h2><Printer class="h-6 w-6" /> បោះពុម្ពសញ្ញាបត្រ</h2>
                        <button type="button" @click="closeModal"><X class="h-7 w-7" /></button>
                    </header>

                    <div class="modal-body create-grid">
                        <aside class="modal-editor">
                            <h3><Bookmark class="h-5 w-5" /> កែប្រែព័ត៌មាន</h3>
                            <label>
                                មុខវិជ្ជា / COURSE
                                <textarea v-model="printForm.course" rows="4" />
                            </label>
                            <label>
                                <span class="saved-course-title">
                                    COURSE រក្សាទុក
                                    <span class="saved-course-count">{{ savedCourses.length }}</span>
                                </span>
                                <div class="saved-course-row">
                                    <select :value="printForm.course" @change="applySavedCourse($event.target.value)">
                                        <option value="">-- ជ្រើសរើស Course --</option>
                                        <option v-for="course in savedCourses" :key="course.course_name" :value="course.course_name">
                                            {{ course.course_name }}
                                        </option>
                                    </select>
                                    <button type="button" @click="deleteSavedCourse"><Trash2 class="h-5 w-5" /></button>
                                </div>
                            </label>
                            <label>
                                ថ្ងៃខែឆ្នាំ / GRANTED DATE
                                <input v-model="printForm.granted_date" type="date" />
                            </label>
                        </aside>

                        <section class="draft-table-wrap">
                            <p class="draft-title"><Users class="h-5 w-5" /> សិស្សទាំងអស់ដែលត្រូវបោះពុម្ពសញ្ញាបត្រ</p>
                            <table class="draft-table">
                                <thead>
                                    <tr>
                                        <th>លេខរៀង</th>
                                        <th>ឈ្មោះសិស្ស</th>
                                        <th>ភេទ</th>
                                        <th>សកម្មភាព</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="student in studentDrafts" :key="student.id">
                                        <td>{{ student.id }}</td>
                                        <td><input v-model="student.draft_name" /></td>
                                        <td>{{ student.gender }}</td>
                                        <td>
                                            <button type="button" class="save-row" @click="saveDraftStudent(student)">Save</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </section>
                    </div>

                    <div class="print-batch">
                        <RealCertificatePreview
                            v-for="student in studentDrafts"
                            :key="student.id"
                            :certificate="{
                                student_name: student.draft_name || student.name,
                                course: printForm.course || selectedClass?.course || 'COURSE NAME',
                                granted_date: formatReadableDate(printForm.granted_date),
                                certificate_id: student.certificate_id || normalCertificateId || '0000000 ETEC',
                                director: printForm.director,
                            }"
                        />
                    </div>

                    <footer class="modal-footer">
                        <button class="light-action" type="button" @click="closeModal"><X class="h-4 w-4" /> បិទ</button>
                        <button class="outline-action" type="button" @click="saveCourse"><Bookmark class="h-5 w-5" /> រក្សាទុក Course</button>
                        <button class="green-action" type="button" :disabled="printSaving" @click="printAllDrafts">
                            <Loader2 v-if="printSaving" class="h-5 w-5 animate-spin" />
                            <Printer v-else class="h-5 w-5" />
                            បោះពុម្ពទាំងអស់
                        </button>
                    </footer>
                </div>
            </div>

            <div v-if="modalMode === 'print'" class="modal-shell">
                <div class="certificate-modal print-modal certificate-studio">
                    <header class="modal-header no-print">
                        <h2><Printer class="h-6 w-6" /> បោះពុម្ពសញ្ញាបត្រ</h2>
                        <button type="button" @click="closeModal"><X class="h-7 w-7" /></button>
                    </header>

                    <div class="modal-body print-grid">
                        <aside class="modal-editor no-print">
                            <div class="editor-heading">
                                <span><Award class="h-6 w-6" /></span>
                                <div>
                                    <h3>កែប្រែព័ត៌មាន</h3>
                                    <p>Change only name, course and granted date.</p>
                                </div>
                            </div>
                            <label>ឈ្មោះសិស្ស / STUDENT NAME<input v-model="printForm.student_name" /></label>
                            <label>មុខវិជ្ជា / COURSE<textarea v-model="printForm.course" rows="4" /></label>
                            <label>
                                <span class="saved-course-title">
                                    COURSE រក្សាទុក
                                    <span class="saved-course-count">{{ savedCourses.length }}</span>
                                </span>
                                <div class="saved-course-row">
                                    <select :value="printForm.course" @change="applySavedCourse($event.target.value)">
                                        <option value="">-- ជ្រើសរើស Course --</option>
                                        <option v-for="course in savedCourses" :key="course.course_name" :value="course.course_name">
                                            {{ course.course_name }}
                                        </option>
                                    </select>
                                    <button type="button" @click="deleteSavedCourse"><Trash2 class="h-5 w-5" /></button>
                                </div>
                            </label>
                            <label>ថ្ងៃខែឆ្នាំ / GRANTED DATE<input v-model="printForm.granted_date" type="date" /></label>
                        </aside>

                        <section class="preview-zone">
                            <div class="preview-head no-print">
                                <span>PREVIEW</span>
                                <strong>{{ isPrintAllMode ? `${studentDrafts.length || 1} certificates ready` : 'Single certificate' }}</strong>
                            </div>
                            <RealCertificatePreview :certificate="currentCertificate" :class="{ 'screen-preview-only': isPrintAllMode }" />
                        </section>
                    </div>

                    <div v-if="isPrintAllMode" class="print-batch">
                        <RealCertificatePreview
                            v-for="student in studentDrafts"
                            :key="student.id"
                            :certificate="{
                                student_name: selectedStudent?.id === student.id ? printForm.student_name : student.draft_name || student.name,
                                course: printForm.course || selectedClass?.course || 'COURSE NAME',
                                granted_date: formatReadableDate(printForm.granted_date),
                                certificate_id: student.certificate_id || normalCertificateId || '0000000 ETEC',
                                director: printForm.director,
                            }"
                        />
                    </div>

                    <footer class="modal-footer no-print">
                        <button class="light-action" type="button" @click="closeModal"><X class="h-4 w-4" /> បិទ</button>
                        <button class="green-action" type="button" :disabled="printSaving" @click="isPrintAllMode ? printAllDrafts() : printSingle()">
                            <Loader2 v-if="printSaving" class="h-5 w-5 animate-spin" />
                            <Printer v-else class="h-5 w-5" />
                            {{ isPrintAllMode ? 'Start Print All' : 'Start Print' }}
                        </button>
                        <button class="outline-action" type="button" @click="saveCourse"><Save class="h-5 w-5" /> រក្សាទុក Course</button>
                        <button class="purple-action" type="button" :disabled="printSaving" @click="isPrintAllMode ? printAllDrafts() : printSingle()">
                            <Printer class="h-5 w-5" />
                            បោះពុម្ព
                        </button>
                    </footer>
                </div>
            </div>
        </section>

        <section v-else class="legacy-certificate-page">
            <form v-if="isFree" class="free-form free-form-card" @submit.prevent="saveFreeAfterPrint">
                <div class="free-form-grid">
                    <label class="free-field" :class="{ 'has-error': freeErrors.student_name }">
                        <span><User class="h-4 w-4" /> ឈ្មោះសិស្សជាភាសាអង់គ្លេស <b>*</b></span>
                        <span class="free-input-wrap">
                            <User class="free-input-icon h-5 w-5" />
                            <input
                                v-model="freeForm.student_name"
                                placeholder="Ex. PHEAROM RATHA"
                                @input="updateFreeStudentName($event.target.value)"
                            >
                        </span>
                        <small v-if="freeErrors.student_name" class="free-error-text">
                            <span>!</span>
                            {{ freeErrors.student_name }}
                        </small>
                    </label>

                    <label class="free-field" :class="{ 'has-error': freeErrors.course }">
                        <span><BookOpen class="h-4 w-4" /> វគ្គសិក្សា <b>*</b></span>
                        <span class="free-input-wrap">
                            <BookOpen class="free-input-icon h-5 w-5" />
                            <input
                                v-model="freeForm.course"
                                placeholder="Ex. PHP/Laravel"
                                @input="updateFreeCourse($event.target.value)"
                            >
                        </span>
                        <small v-if="freeErrors.course" class="free-error-text">
                            <span>!</span>
                            {{ freeErrors.course }}
                        </small>
                    </label>

                    <label class="free-field">
                        <span><Bookmark class="h-4 w-4" /> ជ្រើសរើសវគ្គសិក្សា</span>
                        <span class="free-input-wrap">
                            <Bookmark class="free-input-icon h-5 w-5" />
                            <select
                                v-model="freeForm.course"
                                @change="clearFreeError('course')"
                            >
                                <option value="">-- ជ្រើសរើសវគ្គសិក្សា --</option>
                                <option v-for="course in freeCourses" :key="course.course_name" :value="course.course_name">
                                    {{ course.course_name }}
                                </option>
                            </select>
                        </span>
                    </label>

                    <label class="free-field" :class="{ 'has-error': freeErrors.end_date }">
                        <span><CalendarDays class="h-4 w-4" /> ថ្ងៃបញ្ចប់វគ្គសិក្សា <b>*</b></span>
                        <span class="free-input-wrap">
                            <CalendarDays class="free-input-icon h-5 w-5" />
                            <input
                                v-model="freeForm.end_date"
                                type="date"
                                @input="clearFreeError('end_date')"
                            >
                        </span>
                        <small v-if="freeErrors.end_date" class="free-error-text">
                            <span>!</span>
                            {{ freeErrors.end_date }}
                        </small>
                    </label>

                    <div class="free-print-cell">
                        <button class="btn-cert-free-print" type="submit" :disabled="freeSaving">
                            <Printer class="h-5 w-5" />
                            បោះពុម្ព
                        </button>
                    </div>
                </div>
            </form>

            <FreeCertificatePreview
                v-if="isFree"
                :certificate="{
                    student_name: freeForm.student_name || 'STUDENT NAME',
                    course: (freeForm.course || 'COURSE NAME').toUpperCase(),
                    granted_date: formatReadableDate(freeForm.end_date),
                    certificate_id: freeCertificateId || '0000000 ETEC',
                    director: 'Mr. HENG PHEAKNA',
                }"
            />

            <div v-if="!isFree" class="loading-card">
                Please open សញ្ញាបត្រថ្នាក់ធម្មតា for the completed dynamic page.
            </div>
        </section>
    </DashboardLayout>
</template>

<script>
const LegacyCertificatePreview = {
    props: {
        certificate: { type: Object, required: true },
    },
    template: `
        <article class="certificate-preview printable-certificate">
            <div class="certificate-wrap">
                <div class="certificate">
                    <div class="cert-outer-border">
                        <div class="cert-inner-border">
                            <div class="cert-kingdom">
                                <div>KINGDOM OF CAMBODIA</div>
                                <div>NATION&nbsp; RELIGION &nbsp;KING</div>
                                <img src="/assets/Images/border.png" alt="">
                            </div>

                            <div class="cert-logo-area">
                                <img src="/assets/Images/logo2.png" alt="Logo" class="cert-logo-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                <div class="cert-logo-fallback">ETEC</div>
                            </div>

                            <div class="cert-school-kh">មជ្ឈមណ្ឌលវិស្វកម្មបច្ចេកវិទ្យា និង<span>អេឡិចត្រូនិក</span></div>
                            <div class="cert-school-en"><span>Engineering</span> of Technology and Electronic Center</div>
                            <div class="cert-title">Certificate of Completion</div>
                            <div class="cert-certify">This is to certify that</div>
                            <h1 class="cert-student-name">{{ certificate.student_name }}</h1>
                            <div class="cert-desc">
                                has successfully completed all requirements for completion<br>
                                of the I.T Training Courses in
                            </div>
                            <h4 class="cert-course">{{ certificate.course }}</h4>
                            <div class="cert-granted">Granted: {{ certificate.granted_date }}</div>

                            <div class="cert-footer">
                                <div class="cert-id"><span class="id_text">ID:</span> {{ certificate.certificate_id }}</div>
                                <div class="cert-signature">
                                    <div class="cert-sig-line"></div>
                                    <div class="cert-sig-name">Mr.<span>HENG PHEAKNA</span></div>
                                    <div class="cert-sig-role">Director</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </article>
    `,
}
</script>

<style scoped>
@font-face {
    font-family: "oldeng";
    src: url("/assets/fonts/oldenglishtextmt.ttf") format("truetype");
}

@font-face {
    font-family: "Simp";
    src: url("/assets/fonts/simpfxo.ttf") format("truetype");
}

@font-face {
    font-family: "KhmerCert";
    src: url("/assets/fonts/KhmerUIb.ttf") format("truetype");
}

.normal-certificate-page {
    min-height: 100vh;
    padding: 30px 36px 64px;
    background: #f5f6fa;
    color: #101126;
}

.normal-toolbar,
.detail-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 24px;
}

.normal-toolbar h1 {
    margin: 0;
    font-family: "Khmer OS Muol Light", "Noto Serif Khmer", serif;
    font-size: clamp(24px, 2.2vw, 32px);
    font-weight: 800;
    color: #050505;
}

.normal-actions,
.detail-buttons,
.row-actions,
.modal-footer,
.saved-course-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-select {
    min-width: 190px;
    height: 43px;
    border: 1px solid #d7dbe7;
    border-radius: 6px;
    background: #fff;
    padding: 0 14px;
    font-size: 16px;
    outline: none;
}

.filter-select:focus,
.modal-editor input:focus,
.modal-editor textarea:focus,
.modal-editor select:focus,
.draft-table input:focus {
    border-color: #2c2d86;
    box-shadow: 0 0 0 3px rgba(44, 45, 134, .12);
}

button {
    border: 0;
    cursor: pointer;
}

button:disabled {
    cursor: not-allowed;
    opacity: .65;
}

.blue-action,
.purple-action,
.green-action,
.view-students,
.print-button,
.save-row,
.outline-action,
.light-action,
.back-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 6px;
    font-weight: 800;
    transition: transform .15s ease, box-shadow .15s ease, opacity .15s ease;
}

.blue-action:hover,
.purple-action:hover,
.green-action:hover,
.view-students:hover,
.print-button:hover,
.save-row:hover {
    transform: translateY(-1px);
}

.blue-action {
    min-height: 44px;
    background: dodgerblue;
    color: #fff;
    padding: 0 22px;
    box-shadow: 0 9px 18px rgba(30, 144, 255, .24);
}

.purple-action {
    min-height: 44px;
    background: #2d2e83;
    color: #fff;
    padding: 0 22px;
    box-shadow: 0 9px 18px rgba(45, 46, 131, .22);
}

.green-action {
    min-height: 44px;
    background: #0f9650;
    color: #fff;
    padding: 0 22px;
    box-shadow: 0 9px 18px rgba(15, 150, 80, .18);
}

.normal-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.normal-summary article {
    display: flex;
    align-items: center;
    gap: 16px;
    min-height: 86px;
    border: 1px solid #dde4f2;
    border-radius: 10px;
    background: #fff;
    padding: 18px 22px;
    box-shadow: 0 3px 10px rgba(20, 24, 60, .08);
}

.normal-summary span,
.info-card header span,
.students-card header .section-title span,
.student-name span {
    display: grid;
    place-items: center;
    border-radius: 9px;
    background: #eef2ff;
    color: #2d2e83;
}

.normal-summary span {
    width: 46px;
    height: 46px;
}

.normal-summary p {
    margin: 0 0 2px;
    color: #6e738a;
    font-size: 14px;
}

.normal-summary strong {
    color: #2d2e83;
    font-size: 27px;
    line-height: 1;
}

.category-stack {
    display: grid;
    gap: 24px;
}

.category-card,
.info-card,
.students-card {
    overflow: hidden;
    border: 1px solid #d9deeb;
    border-radius: 5px;
    background: #fff;
    box-shadow: 0 3px 9px rgba(18, 18, 60, .12);
}

.category-card h2,
.info-card header,
.students-card header {
    margin: 0;
    background: #2d2e83;
    color: #fff;
}

.category-card h2 {
    padding: 11px 18px;
    font-size: 23px;
    font-weight: 900;
}

.table-wrap {
    overflow-x: auto;
    padding: 16px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

.class-table th,
.class-table td,
.student-table th,
.student-table td,
.draft-table th,
.draft-table td {
    border: 1px solid #d6dce7;
    padding: 10px 12px;
    text-align: center;
    vertical-align: middle;
}

.class-table th,
.student-table th {
    background: #cfe2fb;
    color: #030714;
    font-size: 18px;
    font-weight: 900;
}

.class-table td {
    font-size: 16px;
}

.count-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    min-width: 25px;
    min-height: 22px;
    border-radius: 7px;
    padding: 2px 8px;
    font-size: 14px;
    font-weight: 900;
}

.count-badge.pending {
    background: #ffc107;
    color: #111827;
}

.count-badge.done {
    background: #199661;
    color: #fff;
}

.view-students {
    min-height: 31px;
    background: #1832a3;
    color: #fff;
    padding: 0 12px;
    font-size: 15px;
}

.view-students.make-cert {
    background: #2d2e83;
}

.complete-mark {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    border-radius: 5px;
    background: #66b995;
    color: #eafff4;
    padding: 7px 10px;
}

.pagination-row {
    display: flex;
    justify-content: center;
    gap: 8px;
    padding: 0 16px 16px;
}

.pagination-row button {
    min-width: 38px;
    height: 38px;
    border: 1px solid #0d6efd;
    border-radius: 7px;
    background: #fff;
    color: #0d6efd;
    font-size: 17px;
}

.pagination-row button.active {
    background: #1e2b9a;
    color: #fff;
}

.back-button {
    min-height: 48px;
    border: 1px solid #dce1ef;
    background: #fff;
    color: #2d2e83;
    padding: 0 24px;
    font-size: 17px;
}

.info-card,
.students-card {
    margin-bottom: 30px;
}

.info-card header,
.students-card header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 30px;
}

.info-card header {
    justify-content: flex-start;
    gap: 18px;
}

.info-card header span,
.students-card header .section-title span {
    width: 54px;
    height: 54px;
    background: rgba(255, 255, 255, .13);
    color: #dce4ff;
}

.info-card h2,
.students-card h2 {
    margin: 0;
    font-size: 24px;
    font-weight: 900;
}

.info-card p,
.students-card p {
    margin: 0;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    padding: 18px 30px;
}

.info-grid div {
    min-height: 70px;
    display: grid;
    place-items: center;
    border-right: 1px solid #dfe4f0;
    text-align: center;
}

.info-grid div:last-child {
    border-right: 0;
}

.info-grid p {
    color: #74798f;
    font-weight: 800;
}

.info-grid strong {
    color: #02030b;
    font-size: 20px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 18px;
}

.students-card header > strong {
    border-radius: 7px;
    background: #1e246e;
    padding: 9px 28px;
    font-size: 20px;
}

.student-table th {
    background: #efeff9;
}

.student-table td {
    height: 76px;
    font-size: 17px;
}

.student-id {
    display: inline-flex;
    align-items: center;
    min-height: 38px;
    border-radius: 8px;
    background: #2d2e83;
    color: #fff;
    padding: 0 3px;
    font-weight: 900;
}

.student-name {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;
}

.student-name span {
    width: 43px;
    height: 43px;
}

.gender-pill {
    display: inline-flex;
    border-radius: 7px;
    background: #eeecff;
    color: #172179;
    padding: 7px 15px;
    font-size: 13px;
    font-weight: 900;
}

.print-button {
    min-height: 39px;
    background: #0ca34f;
    color: #fff;
    padding: 0 17px;
    font-size: 16px;
}

.loading-card {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-height: 180px;
    border: 1px solid #dce1ef;
    border-radius: 8px;
    background: #fff;
    color: #586074;
    font-weight: 800;
}

.modal-shell {
    position: fixed;
    inset: 0;
    z-index: 80;
    display: grid;
    place-items: center;
    overflow: auto;
    background:
        radial-gradient(circle at 70% 20%, rgba(91, 124, 255, .22), transparent 30%),
        rgba(0, 0, 0, .62);
    padding: 32px;
    backdrop-filter: blur(4px);
}

.certificate-modal {
    overflow: hidden;
    width: min(1425px, 96vw);
    max-height: 92vh;
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 30px 70px rgba(0, 0, 0, .35);
}

.print-modal {
    width: min(1240px, 96vw);
}

.certificate-studio {
    border: 1px solid rgba(255, 255, 255, .22);
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: linear-gradient(135deg, #1e206d 0%, #323493 58%, #23246f 100%);
    color: #fff;
    padding: 16px 28px;
}

.modal-header h2 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    font-size: 20px;
    font-weight: 600;
}

.modal-header button {
    background: transparent;
    color: rgba(255, 255, 255, .72);
}

.modal-body {
    overflow: auto;
    max-height: calc(92vh - 138px);
}

.create-grid {
    display: grid;
    grid-template-columns: 350px 1fr;
}

.print-grid {
    display: grid;
    grid-template-columns: 310px minmax(0, 1fr);
    background:
        linear-gradient(90deg, #f8f9ff 0 310px, transparent 310px),
        radial-gradient(circle at 50% 8%, rgba(255, 255, 255, .92), rgba(222, 224, 232, .94) 48%, #d7d8de 100%);
}

.modal-editor {
    display: grid;
    align-content: start;
    gap: 19px;
    min-height: 625px;
    border-right: 1px solid #dbe1ef;
    background:
        radial-gradient(circle at 0 0, rgba(45, 46, 131, .08), transparent 34%),
        linear-gradient(180deg, #fbfcff 0%, #f6f8fe 58%, #f3f5fb 100%);
    padding: 26px 26px 28px;
}

.modal-editor h3,
.draft-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    color: #2d2e83;
    font-size: 15px;
    font-weight: 600;
}

.editor-heading {
    display: flex;
    align-items: center;
    gap: 14px;
    border-bottom: 1px solid #dce3f2;
    padding-bottom: 18px;
}

.editor-heading span {
    display: grid;
    place-items: center;
    width: 44px;
    height: 44px;
    border-radius: 13px;
    background:
        linear-gradient(180deg, rgba(255, 255, 255, .7), rgba(255, 255, 255, 0)),
        #eef2ff;
    color: #2d2e83;
    box-shadow: inset 0 0 0 1px rgba(45, 46, 131, .04);
}

.editor-heading h3 {
    margin: 0;
    color: #20227d;
    font-size: 15px;
    font-weight: 600;
    line-height: 1.05;
}

.editor-heading p {
    max-width: 205px;
    margin: 6px 0 0;
    color: #747b97;
    font-size: 10.5px;
    line-height: 1.45;
}

.modal-editor label {
    display: grid;
    gap: 8px;
    color: #707692;
    font-size: 11px;
    font-weight: 650;
    letter-spacing: .025em;
    line-height: 1.25;
}

.modal-editor input,
.modal-editor textarea,
.modal-editor select,
.draft-table input {
    width: 100%;
    border: 1px solid #d2daea;
    border-radius: 9px;
    background: #fff;
    padding: 9px 13px;
    color: #111827;
    font-size: 13px;
    font-weight: 400;
    outline: none;
    box-shadow: 0 1px 0 rgba(15, 23, 42, .02);
    transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
}

.modal-editor input {
    min-height: 40px;
}

.modal-editor textarea {
    min-height: 96px;
    resize: vertical;
}

.modal-editor select {
    min-height: 42px;
    cursor: pointer;
}

.saved-course-title {
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.saved-course-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 25px;
    height: 18px;
    border-radius: 999px;
    background: #0d6efd;
    color: #fff;
    padding: 0 8px;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0;
    line-height: 1;
}

.saved-course-row {
    align-items: stretch;
    gap: 10px;
}

.saved-course-row select {
    flex: 1;
    height: 42px;
    min-height: 42px;
    border-radius: 9px;
}

.saved-course-row button {
    display: grid;
    place-items: center;
    width: 42px;
    height: 42px;
    min-width: 42px;
    border-radius: 10px;
    background: linear-gradient(180deg, #f43f5e, #e83248);
    color: #fff;
    box-shadow: 0 10px 20px rgba(232, 50, 72, .24);
    transition: transform .15s ease, box-shadow .15s ease;
}

.saved-course-row button:hover {
    transform: translateY(-1px);
    box-shadow: 0 14px 24px rgba(232, 50, 72, .3);
}

.draft-table-wrap {
    padding: 24px 20px;
}

.draft-title {
    margin-bottom: 14px;
    color: #111827;
}

.draft-table th {
    background: #f8f8f8;
    font-size: 19px;
    font-weight: 900;
}

.draft-table td {
    font-size: 18px;
}

.save-row {
    min-height: 38px;
    background: #1832a3;
    color: #fff;
    padding: 0 16px;
    font-size: 16px;
}

.modal-footer {
    justify-content: flex-end;
    border-top: 1px solid #dfe4f0;
    background: #fff;
    gap: 12px;
    padding: 14px 32px;
}

.modal-footer .light-action,
.modal-footer .green-action,
.modal-footer .outline-action,
.modal-footer .purple-action {
    min-height: 38px;
    border-radius: 7px;
    padding: 0 18px;
    font-size: 14px;
    font-weight: 800;
}

.modal-footer .light-action {
    min-width: 86px;
}

.modal-footer .green-action,
.modal-footer .outline-action,
.modal-footer .purple-action {
    min-width: 142px;
}

.modal-footer svg {
    width: 18px;
    height: 18px;
}

.light-action,
.outline-action {
    min-height: 46px;
    border: 1px solid #d9dfec;
    background: #fff;
    color: #6d748d;
    padding: 0 26px;
    font-size: 16px;
}

.outline-action {
    border-color: #2d2e83;
    color: #2d2e83;
}

.preview-zone {
    display: grid;
    justify-items: center;
    align-content: start;
    min-height: 760px;
    padding: 18px 32px 30px;
}

.preview-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: min(560px, 100%);
    margin: 0 0 13px;
    color: #838386;
}

.preview-head span {
    font-size: 13px;
    font-weight: 900;
    letter-spacing: .08em;
}

.preview-head strong {
    border-radius: 999px;
    background: rgba(45, 46, 131, .1);
    color: #2d2e83;
    padding: 6px 12px;
    font-size: 12px;
}

.print-batch {
    display: none;
}

.certificate-preview {
    display: grid;
    place-items: center;
    overflow: auto;
    max-width: 100%;
    border-radius: 8px;
}

.certificate-wrap {
    display: flex;
    justify-content: center;
    background: #e0e0e0;
    padding: 5px;
}

.certificate {
    width: 520px;
    background: #fff;
    padding: 10px;
    box-shadow: 0 22px 45px rgba(20, 20, 45, .2);
}

.cert-outer-border {
    border: 15px solid #2d2e81;
    border-radius: 10px;
    padding: 4px;
}

.cert-inner-border {
    position: relative;
    min-height: 624px;
    border: 8px solid #a6a6a6;
    border-radius: 8px;
    background: #fff;
    padding: 15px 15px 3px;
}

.cert-kingdom {
    margin-bottom: 20px;
    color: #2d2e81;
    font-family: "Simp", "Courier New", monospace;
    font-size: .7rem;
    font-weight: 900;
    line-height: 1.5;
    letter-spacing: .02em;
    text-align: center;
    text-shadow: 0 0 .3px #2d2e81;
    text-transform: uppercase;
    -webkit-text-stroke: .3px #2d2e81;
}

.cert-kingdom img {
    display: block;
    max-width: 120px;
    height: auto;
    margin: 4px auto 0;
    border: none;
    object-fit: contain;
}

.cert-logo-area {
    margin-bottom: 12px;
    text-align: center;
}

.cert-logo-img {
    display: inline-block;
    width: 98px;
    height: 98px;
    border-radius: 13px;
    object-fit: contain;
}

.cert-logo-fallback {
    display: none;
    align-items: center;
    justify-content: center;
    width: 88px;
    height: 88px;
    margin: 0 auto;
    border-radius: 12px;
    background: linear-gradient(135deg, #2d2e81, #1f2060);
    color: #fff;
    font-weight: 900;
}

.cert-school-kh {
    margin-bottom: 4px;
    color: #2d2e81;
    font-family: "KhmerCert", "Moul", serif;
    font-size: .9rem;
    font-weight: 700;
    text-align: center;
}

.cert-school-kh span {
    color: #000;
}

.cert-school-en {
    margin-bottom: 14px;
    color: #2d2e81;
    font-family: Arial, sans-serif;
    font-size: 13px;
    font-weight: 550;
    text-align: center;
}

.cert-school-en span {
    color: #000;
}

.cert-title {
    margin: 2px 0 7px;
    color: #111;
    font-family: "oldeng", "Times New Roman", serif;
    font-size: 1.8rem;
    font-weight: 700;
    text-align: center;
}

.cert-certify {
    margin-bottom: 16px;
    color: #2d2e81;
    font-family: "Courier New", monospace;
    font-size: 16px;
    font-weight: 700;
    letter-spacing: .08em;
    text-align: center;
}

.cert-student-name {
    margin: 0 0 14px;
    color: #111;
    font-size: 1.4rem;
    font-weight: 900;
    text-align: center;
    text-transform: uppercase;
}

.cert-desc {
    margin-bottom: 14px;
    color: #2d2e81;
    font-family: "Times New Roman", serif;
    font-size: 13px;
    letter-spacing: .01em;
    text-align: center;
}

.cert-course {
    max-width: 100%;
    margin: 0 0 10px;
    color: #000;
    font-family: "Simp", "Courier New", monospace;
    font-size: 18px;
    font-weight: 900;
    line-height: 28px;
    text-align: center;
    text-shadow: 0 0 2px #000;
    white-space: pre-line;
    word-break: break-word;
    -webkit-text-stroke: .5px #000;
}

.cert-granted {
    margin-bottom: 36px;
    color: #2d2e81;
    font-family: Arial, sans-serif;
    font-size: .9rem;
    font-weight: 700;
    letter-spacing: .05em;
    text-align: center;
}

.cert-footer {
    position: relative;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    padding-top: 20px;
}

.cert-id {
    position: absolute;
    bottom: 0;
    left: 50%;
    color: #c00;
    font-family: Arial, sans-serif;
    font-size: 7px;
    font-weight: 700;
    letter-spacing: .08em;
    white-space: nowrap;
    transform: translateX(-50%);
}

.id_text {
    color: #2d2e81;
    font-family: "Simp", "Courier New", monospace;
    font-size: 8px;
}

.cert-signature {
    margin-left: auto;
    text-align: right;
}

.cert-sig-line {
    width: 150px;
    height: 1px;
    margin-left: auto;
    margin-bottom: 2px;
    background: #222;
}

.cert-sig-name,
.cert-sig-role {
    color: #111;
    font-family: "Simp", "Courier New", monospace;
    font-size: .8rem;
    font-weight: 700;
    text-align: center;
    text-shadow: 0 0 .4px #000;
    -webkit-text-stroke: .5px #000;
}

.cert-sig-role {
    font-size: .78rem;
}

.legacy-certificate-page {
    min-height: 100vh;
    padding: 12px 30px 56px;
    background: #f5f6fa;
}

.legacy-header h1 {
    margin: 0 0 20px;
    font-size: 30px;
    font-weight: 900;
}

.free-form {
    margin: 0 auto 40px;
}

.free-form-card {
    overflow: hidden;
    width: 100%;
    border: 1px solid #e5e7f2;
    border-radius: 18px;
    background: #fff;
    padding: 30px 50px 36px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
}

.free-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 34px 40px;
    align-items: end;
}

.free-field {
    display: grid;
    gap: 12px;
    margin: 0;
}

.free-field > span:first-child {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #2d2e81;
    font-size: 16px;
    font-weight: 700;
}

.free-field b {
    color: #dc3545;
    font-weight: 700;
}

.free-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.free-input-icon {
    position: absolute;
    left: 18px;
    z-index: 1;
    color: #7b82a3;
    pointer-events: none;
}

.free-field input,
.free-field select {
    width: 100%;
    min-height: 62px;
    border: 1.5px solid #e0e5f2;
    border-radius: 12px;
    background: #f9f9fb;
    color: #111827;
    padding: 0 18px 0 58px;
    font-family: inherit;
    font-size: 18px;
    outline: none;
    transition: border-color .25s ease, box-shadow .25s ease, background .25s ease, transform .25s ease;
}

.free-field input:focus,
.free-field select:focus {
    border-color: #2d2e81;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(45, 46, 129, .1);
    transform: translateY(-1px);
}

.free-field input::placeholder {
    color: #aeb5c8;
    font-weight: 600;
}

.free-field.has-error input,
.free-field.has-error select {
    border-color: #f05265;
    background: #fff8f9;
    box-shadow: 0 0 0 3px rgba(240, 82, 101, .06);
}

.free-field.has-error .free-input-icon {
    color: #7c839f;
}

.free-error-text {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: #ef4056;
    font-size: 13px;
    font-weight: 500;
    line-height: 1.2;
}

.free-error-text span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 13px;
    height: 13px;
    border-radius: 999px;
    background: #ef4056;
    color: #fff;
    font-family: Arial, sans-serif;
    font-size: 9px;
    font-weight: 700;
}

.free-print-cell {
    display: flex;
    align-items: end;
    justify-content: flex-start;
}

.btn-cert-free-print {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-width: 152px;
    min-height: 64px;
    border: 0;
    border-radius: 12px;
    background: #16a34a;
    color: #fff;
    padding: 0 28px;
    font-size: 18px;
    font-weight: 700;
    white-space: nowrap;
    box-shadow: 0 12px 22px rgba(22, 163, 74, .24);
    transition: background .25s ease, box-shadow .25s ease, transform .25s ease;
}

.btn-cert-free-print:hover {
    background: #15803d;
    box-shadow: 0 16px 28px rgba(22, 163, 74, .32);
    transform: translateY(-2px);
}

.btn-cert-free-print:disabled {
    cursor: not-allowed;
    opacity: .65;
}

@media (max-width: 1200px) {
    .normal-toolbar,
    .detail-toolbar,
    .normal-actions,
    .detail-buttons {
        align-items: stretch;
        flex-direction: column;
    }

    .normal-summary,
    .info-grid,
    .create-grid,
    .print-grid {
        grid-template-columns: 1fr;
    }

    .modal-editor {
        min-height: auto;
        border-right: 0;
        border-bottom: 1px solid #dbe1ef;
    }

    .free-form-grid {
        grid-template-columns: 1fr 1fr;
    }

    .free-print-cell {
        grid-column: 1 / -1;
    }

    .btn-cert-free-print {
        width: 100%;
    }
}

@media (max-width: 768px) {
    .normal-certificate-page {
        padding: 18px 14px 48px;
    }

    .table-wrap {
        padding: 10px;
    }

    .modal-shell {
        padding: 12px;
    }

    .modal-footer {
        flex-direction: column;
        align-items: stretch;
    }

    .legacy-certificate-page {
        padding: 12px 14px 40px;
    }

    .free-form-card {
        padding: 22px;
    }

    .free-form-grid {
        grid-template-columns: 1fr;
    }
}

@page {
    size: A4 portrait;
    margin: 0;
}

@media print {
    body * {
        visibility: hidden !important;
    }

    .print-batch {
        display: block !important;
    }

    .printable-certificate,
    .printable-certificate * {
        visibility: visible !important;
    }

    .printable-certificate {
        display: grid !important;
        place-items: center !important;
        overflow: hidden !important;
        width: 100vw !important;
        min-height: 100vh !important;
        background: #fff !important;
        page-break-after: always !important;
        break-after: page !important;
    }

    .screen-preview-only {
        display: none !important;
    }

    .cert-paper {
        width: 148mm !important;
        min-height: 210mm !important;
        border: 0 !important;
        padding: 8mm !important;
        print-color-adjust: exact !important;
        -webkit-print-color-adjust: exact !important;
    }

    .certificate-free-wrapper {
        visibility: visible !important;
    }

    * {
        print-color-adjust: exact !important;
        -webkit-print-color-adjust: exact !important;
    }
}
</style>
