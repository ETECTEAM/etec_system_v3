<script setup>
import { computed, nextTick, onMounted, onUnmounted, reactive, ref, watch, watchEffect } from 'vue'
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
import { useTheme } from '../../../composables/useTheme'
import { useI18n } from '@/i18n'
import RealCertificatePreview from './CertificatePreview.vue'
import FreeCertificatePreview from './FreeCertificatePreview.vue'

const props = defineProps({
    type: { type: String, default: 'free' },
    freeCertificates: { type: Object, default: () => ({ data: [], meta: {}, course_filter: '' }) },
    freeCourses: { type: Array, default: () => [] },
    normalCourses: { type: Array, default: () => [] },
    generatedIds: { type: Object, default: () => ({ free: '', normal: '' }) },
})

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.withCredentials = true

const certificateType = computed(() => props.type)
const isNormal = computed(() => certificateType.value === 'normal')
const isFree = computed(() => certificateType.value === 'free')
const isClassCertificate = computed(() => ['free', 'normal', 'scholarship', 'meal', 'internship'].includes(certificateType.value))
const isReport = computed(() => certificateType.value === 'report')
const isClassListPage = computed(() => isClassCertificate.value || isReport.value)
const { resolvedTheme } = useTheme()
const { t } = useI18n()
const isDarkTheme = computed(() => resolvedTheme.value === 'dark')

const pageTitle = computed(() => ({
    free: t('certificatePage.titles.free'),
    normal: t('certificatePage.titles.normal'),
    scholarship: t('certificatePage.titles.scholarship'),
    internship: t('certificatePage.titles.internship'),
    meal: t('certificatePage.titles.meal'),
    report: t('certificatePage.titles.report'),
}[certificateType.value] ?? t('navigation.certificate')))

const classRows = ref([])
const classLoading = ref(false)
const currentClassFilterDate = new Date()
const selectedTrack = ref('all')
const selectedMonth = ref('all')
const selectedYear = ref(currentClassFilterDate.getFullYear())
const selectedReportType = ref('all')
const selectedReportStatus = ref('all')
const trackOptions = ref([])
const categoryPages = reactive({})
const selectedClass = ref(null)
const activeCertificateType = computed(() => isReport.value ? selectedClass.value?.certificate_type : certificateType.value)
const classCertificatePreview = computed(() => activeCertificateType.value === 'free' ? FreeCertificatePreview : RealCertificatePreview)
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

const perPage = 10

const categories = computed(() => {
    const values = trackOptions.value.length
        ? trackOptions.value
        : classRows.value.map((item) => item.category || 'General')

    return ['all', ...new Set(values)]
})

const monthOptions = computed(() => [
    { value: 'all', label: t('certificatePage.filters.allMonth') },
    ...Array.from({ length: 12 }, (_, index) => {
        const value = index + 1
        return {
            value,
            label: t(`certificatePage.months.${value}`),
        }
    }),
])

const yearOptions = computed(() => {
    const currentYear = currentClassFilterDate.getFullYear()
    return Array.from({ length: currentYear - 2018 + 1 }, (_, index) => 2018 + index)
})

const certificateTypeOptions = computed(() => [
    { value: 'all', label: t('certificatePage.filters.allTypes') },
    { value: 'free', label: t('certificatePage.types.free') },
    { value: 'normal', label: t('certificatePage.types.normal') },
    { value: 'scholarship', label: t('certificatePage.types.scholarship') },
    { value: 'internship', label: t('certificatePage.types.internship') },
    { value: 'meal', label: t('certificatePage.types.meal') },
])

const reportStatusOptions = computed(() => [
    { value: 'all', label: t('certificatePage.filters.allStatus') },
    { value: 'not_printed', label: t('certificatePage.status.notPrinted') },
    { value: 'printed', label: t('certificatePage.status.printed') },
])

const filteredClasses = computed(() => classRows.value)

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

const currentCertificate = computed(() => ({
    student_name: printForm.student_name || 'STUDENT NAME',
    course: printForm.course || selectedClass.value?.course || 'COURSE NAME',
    granted_date: formatReadableDate(printForm.granted_date),
    certificate_id: printForm.certificate_id || normalCertificateId.value || '0000000 ETEC',
    director: printForm.director || 'Mr. HENG PHEAKNA',
}))

const normalPrintCertificates = computed(() => {
    if (isPrintAllMode.value && studentDrafts.value.length) {
        return studentDrafts.value.map((student) => ({
            student_name: selectedStudent.value?.id === student.id
                ? printForm.student_name
                : student.draft_name || student.name,
            course: printForm.course || selectedClass.value?.course || 'COURSE NAME',
            granted_date: formatReadableDate(printForm.granted_date),
            certificate_id: student.certificate_id || normalCertificateId.value || '0000000 ETEC',
            director: printForm.director,
        }))
    }

    return [currentCertificate.value]
})

watch(() => props.type, () => {
    closeModal()
    selectedClass.value = null
    if (isClassListPage.value) loadClasses()
})

watch([selectedTrack, selectedMonth, selectedYear, selectedReportType, selectedReportStatus], () => {
    if (isClassListPage.value) loadClasses()
})

onMounted(() => {
    if (isClassListPage.value) loadClasses()
})

let freePrintStyleElement = null
let normalPrintStyleElement = null

watchEffect(() => {
    if (typeof document === 'undefined') return

    if (!isClassListPage.value && isFree.value && !freePrintStyleElement) {
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

    if ((isClassListPage.value || !isFree.value) && freePrintStyleElement) {
        document.body.classList.remove('free-certificate-print')
        freePrintStyleElement.remove()
        freePrintStyleElement = null
    }
})

onUnmounted(() => {
    document.body.classList.remove('free-certificate-print')
    document.body.classList.remove('normal-certificate-print')
    freePrintStyleElement?.remove()
    freePrintStyleElement = null
    normalPrintStyleElement?.remove()
    normalPrintStyleElement = null
})

function remainingStudents(item) {
    if (item.remaining_students !== undefined) return Number(item.remaining_students || 0)

    return Math.max(Number(item.total_students || 0) - Number(item.printed_students || 0), 0)
}

function certificateTypeLabel(type) {
    return t(`certificatePage.types.${type || 'normal'}`)
}

function reportStatusLabel(status) {
    return t(`certificatePage.status.${status === 'printed' ? 'printed' : 'notPrinted'}`)
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
        const { data } = await axios.get(isReport.value ? '/dashboard/certificates/report/classes' : '/dashboard/certificates/classes', {
            params: isReport.value ? {
                certificate_type: selectedReportType.value,
                status: selectedReportStatus.value,
                track: selectedTrack.value,
                month: selectedMonth.value,
                year: selectedYear.value,
            } : {
                type: certificateType.value,
                track: selectedTrack.value,
                month: selectedMonth.value,
                year: selectedYear.value,
            },
        })
        classRows.value = data.data || []
        trackOptions.value = data.tracks || []
        Object.keys(categoryPages).forEach((key) => delete categoryPages[key])
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
            params: { type: studyClass.certificate_type || certificateType.value },
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
        certificate_type: selectedClass.value.certificate_type || certificateType.value,
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

function beginNormalPrint() {
    document.body.classList.add('normal-certificate-print')

    if (normalPrintStyleElement) {
        normalPrintStyleElement.remove()
    }

    const page = activeCertificateType.value === 'free'
        ? { orientation: 'landscape', width: '297mm', height: '210mm' }
        : { orientation: 'portrait', width: '210mm', height: '297mm' }

    normalPrintStyleElement = document.createElement('style')
    normalPrintStyleElement.dataset.normalCertificatePrint = 'true'
    normalPrintStyleElement.textContent = `
        @media print {
            @page { size: A4 ${page.orientation}; margin: 0; }
            html,
            body {
                width: ${page.width} !important;
                min-width: ${page.width} !important;
                max-width: ${page.width} !important;
                height: ${page.height} !important;
                min-height: ${page.height} !important;
                max-height: ${page.height} !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: hidden !important;
                background: #fff !important;
            }
            body.normal-certificate-print > *:not(#normal-cert-print) {
                display: none !important;
            }
            body.normal-certificate-print #app {
                display: none !important;
            }
            body.normal-certificate-print #normal-cert-print,
            body.normal-certificate-print #normal-cert-print * {
                visibility: visible !important;
            }
            body.normal-certificate-print #normal-cert-print {
                position: fixed !important;
                inset: 0 auto auto 0 !important;
                z-index: 999999 !important;
                display: block !important;
                width: ${page.width} !important;
                min-width: ${page.width} !important;
                max-width: ${page.width} !important;
                height: ${page.height} !important;
                min-height: ${page.height} !important;
                max-height: ${page.height} !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: hidden !important;
                background: #fff !important;
            }
            body.normal-certificate-print #normal-cert-print .printable-certificate {
                box-sizing: border-box !important;
                display: flex !important;
                align-items: stretch !important;
                justify-content: stretch !important;
                width: ${page.width} !important;
                min-width: ${page.width} !important;
                max-width: ${page.width} !important;
                height: ${page.height} !important;
                min-height: ${page.height} !important;
                max-height: ${page.height} !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: hidden !important;
                background: #fff !important;
                page-break-after: always !important;
                break-after: page !important;
                page-break-before: auto !important;
                break-before: auto !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            body.normal-certificate-print #normal-cert-print .printable-certificate:last-child {
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
            body.normal-certificate-print #normal-cert-print .certificate-wrap,
            body.normal-certificate-print #normal-cert-print .certificate {
                box-sizing: border-box !important;
                display: flex !important;
                flex: 1 1 auto !important;
                width: ${page.width} !important;
                min-width: ${page.width} !important;
                max-width: ${page.width} !important;
                height: ${page.height} !important;
                min-height: ${page.height} !important;
                max-height: ${page.height} !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: hidden !important;
                background: #fff !important;
                box-shadow: none !important;
                transform: none !important;
            }
            body.normal-certificate-print #normal-cert-print .certificate {
                padding: 4mm !important;
            }
            body.normal-certificate-print #normal-cert-print .cert-outer-border {
                box-sizing: border-box !important;
                display: flex !important;
                flex: 1 1 auto !important;
                min-height: 0 !important;
                border-width: 8mm !important;
            }
            body.normal-certificate-print #normal-cert-print .cert-inner-border {
                box-sizing: border-box !important;
                display: flex !important;
                flex: 1 1 auto !important;
                min-height: 0 !important;
                border-width: 5mm !important;
            }
            body.normal-certificate-print #normal-cert-print .certificate-free-wrapper {
                box-sizing: border-box !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                width: ${page.width} !important;
                min-width: ${page.width} !important;
                max-width: ${page.width} !important;
                height: ${page.height} !important;
                min-height: ${page.height} !important;
                max-height: ${page.height} !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: hidden !important;
                border-radius: 0 !important;
                background: #fff !important;
                box-shadow: none !important;
                page-break-after: always !important;
                break-after: page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            body.normal-certificate-print #normal-cert-print .certificate-free-wrapper:last-child {
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
            body.normal-certificate-print #normal-cert-print .certificate-free-wrap {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                width: ${page.width} !important;
                height: ${page.height} !important;
            }
            body.normal-certificate-print #normal-cert-print .certificate-free {
                box-sizing: border-box !important;
                width: ${page.width} !important;
                height: ${page.height} !important;
                min-height: ${page.height} !important;
                padding: 5mm !important;
                box-shadow: none !important;
            }
            body.normal-certificate-print #normal-cert-print .cert-free-outer-border {
                display: flex !important;
                flex-direction: column !important;
                box-sizing: border-box !important;
                width: 100% !important;
                height: 100% !important;
            }
            body.normal-certificate-print #normal-cert-print .cert-free-inner-border {
                display: flex !important;
                flex: 1 !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                box-sizing: border-box !important;
                min-height: 0 !important;
                height: auto !important;
            }
            body.normal-certificate-print #normal-cert-print .cert-free-logo-box {
                width: 110px !important;
                height: 110px !important;
            }
            body.normal-certificate-print #normal-cert-print .cert-free-motto {
                margin-top: 4px !important;
                font-size: 19px !important;
            }
            body.normal-certificate-print #normal-cert-print .cert-free-kingdom {
                font-size: 19px !important;
                line-height: 1.5 !important;
            }
            body.normal-certificate-print #normal-cert-print .cert-free-kingdom img {
                max-width: 150px !important;
                margin-top: 4px !important;
            }
            body.normal-certificate-print #normal-cert-print .cert-free-title {
                margin-top: 18px !important;
                font-size: 56px !important;
                line-height: 1 !important;
            }
            body.normal-certificate-print #normal-cert-print .cert-free-certify {
                margin-top: 20px !important;
                font-size: 29px !important;
            }
            body.normal-certificate-print #normal-cert-print .cert-free-student-name {
                margin: 18px 0 16px !important;
                font-size: 34px !important;
                -webkit-text-stroke: 1px #000 !important;
            }
            body.normal-certificate-print #normal-cert-print .cert-free-desc {
                font-size: 24px !important;
                line-height: 1.45 !important;
            }
            body.normal-certificate-print #normal-cert-print .cert-free-course {
                width: 500px !important;
                min-height: 40px !important;
                margin: 10px auto !important;
                font-size: 24px !important;
            }
            body.normal-certificate-print #normal-cert-print .cert-free-granted {
                margin-bottom: 20px !important;
                font-size: 18px !important;
            }
            body.normal-certificate-print #normal-cert-print .cert-free-bottom {
                margin-top: 5px !important;
            }
            body.normal-certificate-print #normal-cert-print .cert-free-id-bottom {
                font-size: 16px !important;
            }
            body.normal-certificate-print #normal-cert-print .cert-free-sig-line {
                width: 200px !important;
            }
            body.normal-certificate-print #normal-cert-print .cert-free-sig-name,
            body.normal-certificate-print #normal-cert-print .cert-free-sig-role {
                font-size: 18px !important;
            }
            * {
                print-color-adjust: exact !important;
                -webkit-print-color-adjust: exact !important;
            }
        }
    `
    document.head.appendChild(normalPrintStyleElement)
}

function endNormalPrint() {
    document.body.classList.remove('normal-certificate-print')
    normalPrintStyleElement?.remove()
    normalPrintStyleElement = null
}

function scheduleNormalPrintCleanup() {
    let cleaned = false
    const cleanup = () => {
        if (cleaned) return
        cleaned = true
        window.removeEventListener('afterprint', cleanup)
        endNormalPrint()
    }

    window.addEventListener('afterprint', cleanup)
    return cleanup
}

async function waitForPrintStyles() {
    await nextTick()
    await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)))
}

function safePrintFileName(value, fallback = 'Certificate') {
    const name = String(value || fallback)
        .trim()
        .replace(/[\\/:*?"<>|]+/g, ' ')
        .replace(/\s+/g, ' ')

    return name || fallback
}

function printWithTitle(title) {
    const originalTitle = document.title
    document.title = safePrintFileName(title)

    try {
        window.print()
    } finally {
        document.title = originalTitle
    }
}

async function printSingle() {
    const student = selectedStudent.value
    if (!printForm.course.trim()) return

    beginNormalPrint()
    const cleanupPrint = scheduleNormalPrintCleanup()
    await waitForPrintStyles()
    printWithTitle(printForm.student_name || student?.name)
    const printed = window.confirm('Printed successfully?')
    cleanupPrint()
    if (!printed) return

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

    beginNormalPrint()
    const cleanupPrint = scheduleNormalPrintCleanup()
    await waitForPrintStyles()
    printWithTitle(studentDrafts.value[0]?.draft_name || printForm.student_name)
    const printed = window.confirm('Printed successfully?')
    cleanupPrint()
    if (!printed) return

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
    if (!freeForm.student_name.trim()) freeErrors.value.student_name = t('certificatePage.validation.fullNameRequired')
    if (!freeForm.course.trim()) freeErrors.value.course = t('certificatePage.validation.courseRequired')
    if (!freeForm.end_date) freeErrors.value.end_date = t('certificatePage.validation.endDateRequired')
    if (Object.keys(freeErrors.value).length) return

    document.body.classList.add('free-certificate-print')
    printWithTitle(freeForm.student_name)
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
        <section
            v-if="isClassListPage"
            class="normal-certificate-page"
            :class="{ 'is-dark-theme': isDarkTheme }"
        >
            <template v-if="!selectedClass">
                <header class="normal-toolbar no-print">
                    <h1>{{ pageTitle }}</h1>

                    <div class="normal-actions" :class="{ 'report-actions': isReport }">
                        <select v-if="isReport" v-model="selectedReportType" class="filter-select report-filter-select">
                            <option v-for="type in certificateTypeOptions" :key="type.value" :value="type.value">
                                {{ type.label }}
                            </option>
                        </select>

                        <select v-model="selectedTrack" class="filter-select">
                            <option v-for="category in categories" :key="category" :value="category">
                                {{ category === 'all' ? t('certificatePage.filters.all') : category }}
                            </option>
                        </select>

                        <select v-model="selectedMonth" class="filter-select filter-select-small">
                            <option v-for="month in monthOptions" :key="month.value" :value="month.value">
                                {{ month.label }}
                            </option>
                        </select>

                        <select v-model.number="selectedYear" class="filter-select filter-select-small">
                            <option v-for="year in yearOptions" :key="year" :value="year">
                                {{ year }}
                            </option>
                        </select>

                        <select v-if="isReport" v-model="selectedReportStatus" class="filter-select report-filter-select">
                            <option v-for="status in reportStatusOptions" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>

                        <button v-if="!isReport" class="blue-action" type="button" @click="openFirstClassForCreate">
                            <Award class="h-5 w-5" />
                            {{ t('certificatePage.actions.createCertificate') }}
                        </button>
                    </div>
                </header>

                <div v-if="classLoading" class="loading-card no-print">
                    <Loader2 class="h-6 w-6 animate-spin" />
                    {{ t('certificatePage.states.loadingData') }}
                </div>

                <div v-else-if="!filteredClasses.length" class="loading-card no-print">
                    {{ t('certificatePage.states.noClasses') }}
                </div>

                <div v-else class="category-stack no-print">
                    <article v-for="(group, category) in pagedGroups" :key="category" class="category-card">
                        <h2>{{ t('certificatePage.sections.courseType', { category }) }}</h2>

                        <div class="table-wrap">
                            <table class="class-table" :class="{ 'report-table': isReport }">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th v-if="isReport">{{ t('certificatePage.table.type') }}</th>
                                        <th>{{ t('certificatePage.table.teacher') }}</th>
                                        <th>{{ t('certificatePage.table.course') }}</th>
                                        <th>{{ t('certificatePage.table.time') }}</th>
                                        <th v-if="isReport">{{ t('certificatePage.table.requestedAt') }}</th>
                                        <th v-if="isReport">{{ t('certificatePage.table.printedStudents') }}</th>
                                        <th>{{ t('certificatePage.table.remainingStudents') }}</th>
                                        <th v-if="isReport">{{ t('certificatePage.table.status') }}</th>
                                        <th>{{ t('certificatePage.table.students') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in group.items" :key="`${item.id}-${item.certificate_type || certificateType}`">
                                        <td>{{ item.id }}</td>
                                        <td v-if="isReport">{{ certificateTypeLabel(item.certificate_type) }}</td>
                                        <td>{{ item.teacher_name }}</td>
                                        <td>{{ item.course }}</td>
                                        <td>{{ item.time }}</td>
                                        <td v-if="isReport">{{ item.requested_at }}</td>
                                        <td v-if="isReport">
                                            <span class="count-badge done">{{ item.printed_students }}</span>
                                        </td>
                                        <td>
                                            <span :class="['count-badge', remainingStudents(item) === 0 ? 'done' : 'pending']">
                                                {{ remainingStudents(item) }}
                                                <CheckCircle2 v-if="remainingStudents(item) === 0" class="h-3 w-3" />
                                            </span>
                                        </td>
                                        <td v-if="isReport">
                                            <span :class="['report-status', item.print_status === 'printed' ? 'printed' : 'not-printed']">
                                                {{ reportStatusLabel(item.print_status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="row-actions">
                                                <button class="view-students" type="button" @click="openStudents(item)">
                                                    <Users class="h-4 w-4" />
                                                    {{ t('certificatePage.actions.viewStudents') }}
                                                </button>
                                                <button v-if="!isReport" class="view-students make-cert" type="button" @click="openClassCertificateModal(item)">
                                                    <Award class="h-4 w-4" />
                                                    {{ t('certificatePage.actions.create') }}
                                                </button>
                                                <span v-if="!isReport && remainingStudents(item) === 0" class="complete-mark">
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
                                {{ t('certificatePage.actions.next') }}
                            </button>
                        </div>
                    </article>
                </div>
            </template>

            <template v-else>
                <div class="detail-toolbar no-print">
                    <button class="back-button" type="button" @click="backToClasses">
                        <ChevronLeft class="h-5 w-5" />
                        {{ t('certificatePage.actions.back') }}
                    </button>

                    <div v-if="!isReport" class="detail-buttons">
                        <button class="green-action" type="button" :disabled="studentsLoading || !students.length" @click="openPrintModal(students[0], true)">
                            <Printer class="h-5 w-5" />
                            {{ t('certificatePage.actions.printAll') }}
                        </button>
                        <button class="purple-action" type="button" :disabled="studentsLoading || !students.length" @click="openPrintModal(students[0], true)">
                            <Award class="h-5 w-5" />
                            {{ t('certificatePage.actions.createCertificate') }}
                        </button>
                    </div>
                </div>

                <article class="info-card no-print">
                    <header>
                        <span><BookOpen class="h-7 w-7" /></span>
                        <div>
                            <h2>{{ t('certificatePage.sections.classInfoKh') }}</h2>
                        </div>
                    </header>
                    <div class="info-grid">
                        <div>
                            <p>{{ t('certificatePage.table.course') }}</p>
                            <strong>{{ selectedClass.course }}</strong>
                        </div>
                        <div>
                            <p>{{ t('certificatePage.table.teacher') }}</p>
                            <strong>{{ selectedClass.teacher_name }}</strong>
                        </div>
                        <div>
                            <p>{{ t('certificatePage.table.time') }}</p>
                            <strong>{{ selectedClass.time }}</strong>
                        </div>
                    </div>
                </article>

                <article class="students-card no-print">
                    <header>
                        <div class="section-title">
                            <span><Users class="h-7 w-7" /></span>
                            <div>
                                <h2>{{ t('certificatePage.sections.studentListKh') }}</h2>
                            </div>
                        </div>
                        <strong>{{ t('certificatePage.studentsCount', { count: students.length }) }}</strong>
                    </header>

                    <div v-if="studentsLoading" class="loading-card">
                        <Loader2 class="h-6 w-6 animate-spin" />
                        {{ t('certificatePage.states.loadingStudents') }}
                    </div>

                    <div v-else class="table-wrap">
                        <table class="student-table">
                            <thead>
                                <tr>
                                    <th>{{ t('certificatePage.table.no') }}</th>
                                    <th>{{ t('certificatePage.table.studentName') }}</th>
                                    <th>{{ t('certificatePage.table.gender') }}</th>
                                    <th>{{ t('certificatePage.table.phone') }}</th>
                                    <th>{{ t('certificatePage.table.course') }}</th>
                                    <th>{{ t('certificatePage.table.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="student in students"
                                    :key="student.id"
                                    :class="{ 'printed-student-row': student.is_printed }"
                                >
                                    <td><span class="student-id">{{ student.id }}</span></td>
                                    <td>
                                        <div class="student-name">
                                            <strong>{{ student.name }}</strong>
                                        </div>
                                    </td>
                                    <td><span class="gender-pill">{{ student.gender }}</span></td>
                                    <td>{{ student.tel }}</td>
                                    <td>{{ selectedClass.course }}</td>
                                    <td>
                                        <div class="student-print-actions">
                                            <span v-if="student.is_printed" class="printed-status">
                                                <CheckCircle2 class="h-4 w-4" />
                                                Printed
                                            </span>
                                            <button
                                                class="print-button"
                                                :class="{ 'reprint-button': student.is_printed }"
                                                type="button"
                                                @click="openPrintModal(student)"
                                            >
                                            <Printer class="h-4 w-4" />
                                                {{ student.is_printed ? 'Re-print' : 'Print' }}
                                            </button>
                                        </div>
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
                        <h2><Printer class="h-6 w-6" /> {{ t('certificatePage.modal.printCertificate') }}</h2>
                        <button type="button" @click="closeModal"><X class="h-7 w-7" /></button>
                    </header>

                    <div class="modal-body create-grid">
                        <aside class="modal-editor">
                            <h3><Bookmark class="h-5 w-5" /> {{ t('certificatePage.modal.editInfo') }}</h3>
                            <label>
                                {{ t('certificatePage.form.course') }}
                                <textarea v-model="printForm.course" rows="4" />
                            </label>
                            <label>
                                <span class="saved-course-title">
                                    {{ t('certificatePage.form.savedCourses') }}
                                    <span class="saved-course-count">{{ savedCourses.length }}</span>
                                </span>
                                <div class="saved-course-row">
                                    <select :value="printForm.course" @change="applySavedCourse($event.target.value)">
                                        <option value="">-- {{ t('certificatePage.form.selectCourse') }} --</option>
                                        <option v-for="course in savedCourses" :key="course.course_name" :value="course.course_name">
                                            {{ course.course_name }}
                                        </option>
                                    </select>
                                    <button type="button" @click="deleteSavedCourse"><Trash2 class="h-5 w-5" /></button>
                                </div>
                            </label>
                            <label>
                                {{ t('certificatePage.form.grantedDate') }}
                                <input v-model="printForm.granted_date" type="date" />
                            </label>
                        </aside>

                        <section class="draft-table-wrap">
                            <p class="draft-title"><Users class="h-5 w-5" /> {{ t('certificatePage.modal.studentsReady') }}</p>
                            <table class="draft-table">
                                <thead>
                                    <tr>
                                        <th>{{ t('certificatePage.table.no') }}</th>
                                        <th>{{ t('certificatePage.table.studentName') }}</th>
                                        <th>{{ t('certificatePage.table.gender') }}</th>
                                        <th>{{ t('certificatePage.table.actions') }}</th>
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
                        <component
                            :is="classCertificatePreview"
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
                        <button class="light-action" type="button" @click="closeModal"><X class="h-4 w-4" /> {{ t('certificatePage.actions.close') }}</button>
                        <button class="outline-action" type="button" @click="saveCourse"><Bookmark class="h-5 w-5" /> {{ t('certificatePage.actions.saveCourse') }}</button>
                        <button class="green-action" type="button" :disabled="printSaving" @click="printAllDrafts">
                            <Loader2 v-if="printSaving" class="h-5 w-5 animate-spin" />
                            <Printer v-else class="h-5 w-5" />
                            {{ t('certificatePage.actions.printAll') }}
                        </button>
                    </footer>
                </div>
            </div>

            <div v-if="modalMode === 'print'" class="modal-shell">
                <div class="certificate-modal print-modal certificate-studio">
                    <header class="modal-header no-print">
                        <h2><Printer class="h-6 w-6" /> {{ t('certificatePage.modal.printCertificate') }}</h2>
                        <button type="button" @click="closeModal"><X class="h-7 w-7" /></button>
                    </header>

                    <div class="modal-body print-grid">
                        <aside class="modal-editor no-print">
                            <div class="editor-heading">
                                <span><Award class="h-6 w-6" /></span>
                                <div>
                                    <h3>{{ t('certificatePage.modal.editInfo') }}</h3>
                                    <p>{{ t('certificatePage.modal.editHint') }}</p>
                                </div>
                            </div>
                            <label>{{ t('certificatePage.form.studentName') }}<input v-model="printForm.student_name" /></label>
                            <label>{{ t('certificatePage.form.course') }}<textarea v-model="printForm.course" rows="4" /></label>
                            <label>
                                <span class="saved-course-title">
                                    {{ t('certificatePage.form.savedCourses') }}
                                    <span class="saved-course-count">{{ savedCourses.length }}</span>
                                </span>
                                <div class="saved-course-row">
                                    <select :value="printForm.course" @change="applySavedCourse($event.target.value)">
                                        <option value="">-- {{ t('certificatePage.form.selectCourse') }} --</option>
                                        <option v-for="course in savedCourses" :key="course.course_name" :value="course.course_name">
                                            {{ course.course_name }}
                                        </option>
                                    </select>
                                    <button type="button" @click="deleteSavedCourse"><Trash2 class="h-5 w-5" /></button>
                                </div>
                            </label>
                            <label>{{ t('certificatePage.form.grantedDate') }}<input v-model="printForm.granted_date" type="date" /></label>
                        </aside>

                        <section class="preview-zone">
                            <div class="preview-head no-print">
                                <span>{{ t('certificatePage.modal.preview') }}</span>
                                <strong>{{ isPrintAllMode ? t('certificatePage.modal.certificatesReady', { count: studentDrafts.length || 1 }) : t('certificatePage.modal.singleCertificate') }}</strong>
                            </div>
                            <component :is="classCertificatePreview" :certificate="currentCertificate" />
                        </section>
                    </div>

                    <div v-if="isPrintAllMode" class="print-batch">
                        <component
                            :is="classCertificatePreview"
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
                        <button class="light-action" type="button" @click="closeModal"><X class="h-4 w-4" /> {{ t('certificatePage.actions.close') }}</button>
                        <button class="green-action" type="button" :disabled="printSaving" @click="isPrintAllMode ? printAllDrafts() : printSingle()">
                            <Loader2 v-if="printSaving" class="h-5 w-5 animate-spin" />
                            <Printer v-else class="h-5 w-5" />
                            {{ isPrintAllMode ? t('certificatePage.actions.startPrintAll') : t('certificatePage.actions.startPrint') }}
                        </button>
                        <button class="outline-action" type="button" @click="saveCourse"><Save class="h-5 w-5" /> {{ t('certificatePage.actions.saveCourse') }}</button>
                        <button class="purple-action" type="button" :disabled="printSaving" @click="isPrintAllMode ? printAllDrafts() : printSingle()">
                            <Printer class="h-5 w-5" />
                            {{ t('certificatePage.actions.print') }}
                        </button>
                    </footer>
                </div>
            </div>
        </section>

        <section
            v-else
            class="legacy-certificate-page"
            :class="{ 'is-dark-theme': isDarkTheme }"
        >
            <form v-if="isFree" class="free-form free-form-card" @submit.prevent="saveFreeAfterPrint">
                <div class="free-form-grid">
                    <label class="free-field" :class="{ 'has-error': freeErrors.student_name }">
                        <span><User class="h-4 w-4" /> {{ t('certificatePage.free.studentNameEnglish') }} <b>*</b></span>
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
                        <span><BookOpen class="h-4 w-4" /> {{ t('certificatePage.free.course') }} <b>*</b></span>
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
                        <span><Bookmark class="h-4 w-4" /> {{ t('certificatePage.free.selectCourse') }}</span>
                        <span class="free-input-wrap">
                            <Bookmark class="free-input-icon h-5 w-5" />
                            <select
                                v-model="freeForm.course"
                                @change="clearFreeError('course')"
                            >
                                <option value="">-- {{ t('certificatePage.free.selectCourse') }} --</option>
                                <option v-for="course in freeCourses" :key="course.course_name" :value="course.course_name">
                                    {{ course.course_name }}
                                </option>
                            </select>
                        </span>
                    </label>

                    <label class="free-field" :class="{ 'has-error': freeErrors.end_date }">
                        <span><CalendarDays class="h-4 w-4" /> {{ t('certificatePage.free.endDate') }} <b>*</b></span>
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
                            {{ t('certificatePage.actions.print') }}
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
                {{ t('certificatePage.states.openRegularPage') }}
            </div>
        </section>

        <Teleport to="body">
            <div
                v-if="isClassListPage && modalMode"
                id="normal-cert-print"
                class="normal-print-root"
            >
                <component
                    :is="classCertificatePreview"
                    v-for="(certificate, index) in normalPrintCertificates"
                    :key="`${certificate.certificate_id}-${index}`"
                    :certificate="certificate"
                />
            </div>
        </Teleport>
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

:global(.dark) .normal-certificate-page {
    background:
        radial-gradient(circle at 18% 0%, rgba(37, 99, 235, .14), transparent 30%),
        linear-gradient(180deg, #0b1120 0%, #111827 100%);
    color: #e5e7eb;
}

.normal-certificate-page.is-dark-theme,
.legacy-certificate-page.is-dark-theme {
    background:
        radial-gradient(circle at 18% 0%, rgba(37, 99, 235, .16), transparent 30%),
        linear-gradient(180deg, #0b1120 0%, #111827 100%) !important;
    color: #e5e7eb !important;
}

.certificate-dark-ui.normal-certificate-page,
.certificate-dark-ui.legacy-certificate-page {
    background:
        radial-gradient(circle at 18% 0%, rgba(37, 99, 235, .16), transparent 30%),
        linear-gradient(180deg, #0b1120 0%, #111827 100%) !important;
    color: #e5e7eb !important;
}

.normal-toolbar,
.detail-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 18px;
}

.normal-toolbar h1 {
    margin: 0;
    font-family: var(--font-khmer), "Khmer OS Muol Light", "Noto Serif Khmer", "Poppins", "Segoe UI", Arial, sans-serif;
    font-size: clamp(22px, 2vw, 28px);
    font-weight: 800;
    color: #050505;
}

:global(.dark) .normal-toolbar h1 {
    color: #f8fafc;
}

.is-dark-theme .normal-toolbar h1,
.is-dark-theme .legacy-header h1 {
    color: #f8fafc !important;
}

.normal-actions,
.detail-buttons,
.row-actions,
.modal-footer,
.saved-course-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.normal-actions {
    flex: 0 0 auto;
    justify-content: flex-end;
    min-width: 760px;
}

.report-actions {
    min-width: 1008px;
}

.filter-select {
    flex: 0 0 250px;
    width: 250px;
    height: 38px;
    border: 1px solid #d7dbe7;
    border-radius: 6px;
    background: #fff;
    padding: 0 12px;
    font-size: 14px;
    outline: none;
}

.filter-select-small {
    flex-basis: 124px;
    width: 124px;
}

.report-filter-select {
    flex-basis: 178px;
    width: 178px;
}

:global(.dark) .filter-select {
    border-color: #374151;
    background: #111827;
    color: #e5e7eb;
}

.is-dark-theme .filter-select {
    border-color: #374151 !important;
    background: #111827 !important;
    color: #e5e7eb !important;
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
    flex: 0 0 190px;
    width: 190px;
    min-height: 38px;
    background: dodgerblue;
    color: #fff;
    padding: 0 16px;
    font-size: 14px;
    box-shadow: 0 7px 14px rgba(30, 144, 255, .2);
}

.purple-action {
    min-height: 38px;
    background: #2d2e83;
    color: #fff;
    padding: 0 16px;
    font-size: 14px;
    box-shadow: 0 7px 14px rgba(45, 46, 131, .2);
}

.green-action {
    min-height: 38px;
    background: #0f9650;
    color: #fff;
    padding: 0 16px;
    font-size: 14px;
    box-shadow: 0 7px 14px rgba(15, 150, 80, .16);
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

:global(.dark) .normal-summary article {
    border-color: #263244;
    background: linear-gradient(180deg, #172033 0%, #111827 100%);
    box-shadow: 0 18px 40px rgba(0, 0, 0, .28);
}

.is-dark-theme .normal-summary article,
.is-dark-theme .category-card,
.is-dark-theme .info-card,
.is-dark-theme .students-card,
.is-dark-theme .loading-card {
    border-color: #263244 !important;
    background: linear-gradient(180deg, #172033 0%, #111827 100%) !important;
    box-shadow: 0 18px 42px rgba(0, 0, 0, .3) !important;
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

:global(.dark) .normal-summary span,
:global(.dark) .info-card header span,
:global(.dark) .students-card header .section-title span,
:global(.dark) .student-name span {
    background: rgba(96, 165, 250, .12);
    color: #93c5fd;
}

.is-dark-theme .normal-summary span,
.is-dark-theme .info-card header span,
.is-dark-theme .students-card header .section-title span,
.is-dark-theme .student-name span {
    background: rgba(96, 165, 250, .12) !important;
    color: #93c5fd !important;
}

.normal-summary p {
    margin: 0 0 2px;
    color: #6e738a;
    font-size: 14px;
}

:global(.dark) .normal-summary p {
    color: #9ca3af;
}

.is-dark-theme .normal-summary p,
.is-dark-theme .info-grid p {
    color: #9ca3af !important;
}

.normal-summary strong {
    color: #2d2e83;
    font-size: 27px;
    line-height: 1;
}

:global(.dark) .normal-summary strong {
    color: #93c5fd;
}

.is-dark-theme .normal-summary strong {
    color: #93c5fd !important;
}

.category-stack {
    display: grid;
    gap: 16px;
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

:global(.dark) .category-card,
:global(.dark) .info-card,
:global(.dark) .students-card {
    border-color: #263244;
    background: #111827;
    box-shadow: 0 18px 42px rgba(0, 0, 0, .3);
}

.category-card h2,
.info-card header,
.students-card header {
    margin: 0;
    background: #2d2e83;
    color: #fff;
}

:global(.dark) .category-card h2,
:global(.dark) .info-card header,
:global(.dark) .students-card header {
    background: linear-gradient(135deg, #1e3a8a, #312e81);
}

.is-dark-theme .category-card h2,
.is-dark-theme .info-card header,
.is-dark-theme .students-card header {
    background: linear-gradient(135deg, #1e3a8a, #312e81) !important;
}

.category-card h2 {
    padding: 9px 16px;
    font-size: 18px;
    font-weight: 900;
}

.table-wrap {
    overflow-x: auto;
    padding: 12px 14px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

.class-table {
    min-width: 960px;
    table-layout: fixed;
}

.report-table {
    min-width: 1180px;
}

.class-table th:nth-child(1),
.class-table td:nth-child(1) {
    width: 56px;
}

.class-table th:nth-child(2),
.class-table td:nth-child(2) {
    width: 26%;
}

.class-table th:nth-child(3),
.class-table td:nth-child(3) {
    width: 28%;
}

.class-table th:nth-child(4),
.class-table td:nth-child(4) {
    width: 15%;
}

.class-table th:nth-child(5),
.class-table td:nth-child(5) {
    width: 13%;
}

.class-table th:nth-child(6),
.class-table td:nth-child(6) {
    width: 18%;
}

.report-table th:nth-child(1),
.report-table td:nth-child(1) {
    width: 52px;
}

.report-table th:nth-child(2),
.report-table td:nth-child(2) {
    width: 95px;
}

.report-table th:nth-child(3),
.report-table td:nth-child(3) {
    width: 18%;
}

.report-table th:nth-child(4),
.report-table td:nth-child(4) {
    width: 22%;
}

.report-table th:nth-child(5),
.report-table td:nth-child(5) {
    width: 130px;
}

.report-table th:nth-child(6),
.report-table td:nth-child(6) {
    width: 138px;
}

.report-table th:nth-child(7),
.report-table td:nth-child(7),
.report-table th:nth-child(8),
.report-table td:nth-child(8) {
    width: 92px;
}

.report-table th:nth-child(9),
.report-table td:nth-child(9) {
    width: 116px;
}

.report-table th:nth-child(10),
.report-table td:nth-child(10) {
    width: 128px;
}

.class-table th,
.class-table td,
.student-table th,
.student-table td,
.draft-table th,
.draft-table td {
    border: 1px solid #d6dce7;
    padding: 8px 10px;
    text-align: center;
    vertical-align: middle;
}

:global(.dark) .class-table th,
:global(.dark) .class-table td,
:global(.dark) .student-table th,
:global(.dark) .student-table td,
:global(.dark) .draft-table th,
:global(.dark) .draft-table td {
    border-color: #263244;
}

.is-dark-theme .class-table th,
.is-dark-theme .class-table td,
.is-dark-theme .student-table th,
.is-dark-theme .student-table td,
.is-dark-theme .draft-table th,
.is-dark-theme .draft-table td {
    border-color: #263244 !important;
}

.class-table th,
.student-table th {
    background: #cfe2fb;
    color: #030714;
    font-size: 14px;
    font-weight: 900;
    line-height: 1.25;
}

:global(.dark) .class-table th,
:global(.dark) .student-table th {
    background: #1f2a44;
    color: #f8fafc;
}

.is-dark-theme .class-table th,
.is-dark-theme .student-table th,
.is-dark-theme .draft-table th {
    background: #1f2a44 !important;
    color: #f8fafc !important;
}

:global(.dark) .class-table td,
:global(.dark) .student-table td,
:global(.dark) .draft-table td {
    color: #d1d5db;
}

.is-dark-theme .class-table td,
.is-dark-theme .student-table td,
.is-dark-theme .draft-table td {
    color: #d1d5db !important;
}

.class-table td {
    font-size: 14px;
    line-height: 1.35;
}

.count-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    min-width: 22px;
    min-height: 20px;
    border-radius: 6px;
    padding: 1px 7px;
    font-size: 12px;
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

.report-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 24px;
    border-radius: 6px;
    padding: 2px 9px;
    font-size: 12px;
    font-weight: 900;
    white-space: nowrap;
}

.report-status.printed {
    background: #16a765;
    color: #fff;
}

.report-status.not-printed {
    background: #ffc107;
    color: #111827;
}

.view-students {
    min-height: 30px;
    background: #1832a3;
    color: #fff;
    padding: 0 10px;
    font-size: 13px;
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

:global(.dark) .pagination-row button {
    border-color: #3b82f6;
    background: #111827;
    color: #bfdbfe;
}

.pagination-row button.active {
    background: #1e2b9a;
    color: #fff;
}

.back-button {
    min-height: 38px;
    border: 1px solid #dce1ef;
    background: #fff;
    color: #2d2e83;
    padding: 0 16px;
    font-size: 14px;
}

:global(.dark) .back-button {
    border-color: #374151;
    background: #111827;
    color: #bfdbfe;
}

.info-card,
.students-card {
    margin-bottom: 16px;
}

.info-card header,
.students-card header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #d9deeb;
    background: #f8fafc;
    color: #111827;
    padding: 14px 18px;
}

.info-card header {
    justify-content: flex-start;
    gap: 10px;
}

.info-card header span,
.students-card header .section-title span {
    width: 36px;
    height: 36px;
    background: #e8eefc;
    color: #2d2e83;
}

:global(.dark) .info-card header,
:global(.dark) .students-card header,
.is-dark-theme .info-card header,
.is-dark-theme .students-card header {
    border-bottom-color: #263244;
    background: #172033 !important;
    color: #f8fafc;
}

:global(.dark) .info-card header span,
:global(.dark) .students-card header .section-title span,
.is-dark-theme .info-card header span,
.is-dark-theme .students-card header .section-title span {
    background: rgba(96, 165, 250, .14);
    color: #bfdbfe;
}

.info-card h2,
.students-card h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 900;
}

.info-card p,
.students-card p {
    margin: 0;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    padding: 0;
}

.info-grid div {
    min-height: 62px;
    display: grid;
    align-content: center;
    gap: 5px;
    border-right: 1px solid #dfe4f0;
    padding: 12px 18px;
    text-align: left;
}

:global(.dark) .info-grid div {
    border-right-color: #263244;
}

.info-grid div:last-child {
    border-right: 0;
}

.info-grid p {
    color: #74798f;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
}

:global(.dark) .info-grid p {
    color: #9ca3af;
}

.info-grid strong {
    color: #111827;
    font-size: 15px;
    line-height: 1.35;
}

:global(.dark) .info-grid strong {
    color: #f8fafc;
}

.is-dark-theme .info-grid strong {
    color: #f8fafc !important;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 10px;
}

.students-card header > strong {
    border-radius: 999px;
    background: #eef2ff;
    color: #2d2e83;
    padding: 6px 13px;
    font-size: 13px;
}

:global(.dark) .students-card header > strong,
.is-dark-theme .students-card header > strong {
    background: rgba(96, 165, 250, .14);
    color: #bfdbfe;
}

.student-table {
    min-width: 940px;
    table-layout: fixed;
}

.student-table th:nth-child(1),
.student-table td:nth-child(1) {
    width: 70px;
}

.student-table th:nth-child(2),
.student-table td:nth-child(2) {
    width: 20%;
    text-align: left;
}

.student-table th:nth-child(3),
.student-table td:nth-child(3) {
    width: 110px;
}

.student-table th:nth-child(4),
.student-table td:nth-child(4) {
    width: 150px;
}

.student-table th:nth-child(5),
.student-table td:nth-child(5) {
    text-align: left;
}

.student-table th:nth-child(6),
.student-table td:nth-child(6) {
    width: 260px;
}

.student-table th {
    background: #efeff9;
}

:global(.dark) .student-table th {
    background: #1f2a44;
}

.student-table td {
    height: 54px;
    font-size: 14px;
}

.printed-student-row {
    background: #f7f7fb;
    color: #4b5563;
}

:global(.dark) .printed-student-row,
.is-dark-theme .printed-student-row {
    background: rgba(30, 41, 59, .58);
    color: #9ca3af;
}

.printed-student-row td:nth-child(n + 2):nth-child(-n + 5) {
    color: #4b5563;
    text-decoration: line-through;
    text-decoration-thickness: 1px;
}

:global(.dark) .printed-student-row td:nth-child(n + 2):nth-child(-n + 5),
.is-dark-theme .printed-student-row td:nth-child(n + 2):nth-child(-n + 5) {
    color: #9ca3af !important;
}

.student-id {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    min-height: 28px;
    border-radius: 6px;
    background: #2d2e83;
    color: #fff;
    padding: 0 6px;
    font-weight: 900;
}

.student-name {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    min-width: 0;
}

.gender-pill {
    display: inline-flex;
    border-radius: 7px;
    background: #eeecff;
    color: #172179;
    padding: 5px 11px;
    font-size: 12px;
    font-weight: 900;
}

:global(.dark) .gender-pill {
    background: rgba(96, 165, 250, .14);
    color: #bfdbfe;
}

.print-button {
    min-height: 32px;
    background: #0ca34f;
    color: #fff;
    padding: 0 12px;
    font-size: 13px;
}

.student-print-actions {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.printed-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    min-height: 32px;
    border-radius: 6px;
    background: #56aa7e;
    color: #fff;
    padding: 0 12px;
    font-size: 13px;
    font-weight: 800;
}

.print-button.reprint-button {
    background: #3b82f6;
    box-shadow: 0 7px 14px rgba(59, 130, 246, .18);
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

:global(.dark) .loading-card {
    border-color: #263244;
    background: #111827;
    color: #aeb8cc;
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

:global(.dark) .certificate-modal {
    border: 1px solid #263244;
    background: #111827;
    color: #e5e7eb;
}

.is-dark-theme .certificate-modal {
    border-color: #263244 !important;
    background: #111827 !important;
    color: #e5e7eb !important;
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

:global(.dark) .print-grid {
    background:
        linear-gradient(90deg, #111827 0 310px, transparent 310px),
        radial-gradient(circle at 50% 8%, rgba(51, 65, 85, .8), rgba(30, 41, 59, .94) 48%, #0f172a 100%);
}

.is-dark-theme .print-grid,
.is-dark-theme .create-grid {
    background:
        linear-gradient(90deg, #111827 0 310px, transparent 310px),
        radial-gradient(circle at 50% 8%, rgba(51, 65, 85, .8), rgba(30, 41, 59, .94) 48%, #0f172a 100%) !important;
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

:global(.dark) .modal-editor {
    border-right-color: #263244;
    background:
        radial-gradient(circle at 0 0, rgba(59, 130, 246, .13), transparent 34%),
        linear-gradient(180deg, #111827 0%, #0f172a 100%);
}

.is-dark-theme .modal-editor {
    border-right-color: #263244 !important;
    background:
        radial-gradient(circle at 0 0, rgba(59, 130, 246, .13), transparent 34%),
        linear-gradient(180deg, #111827 0%, #0f172a 100%) !important;
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

:global(.dark) .modal-editor h3,
:global(.dark) .draft-title {
    color: #bfdbfe;
}

.is-dark-theme .modal-editor h3,
.is-dark-theme .draft-title {
    color: #bfdbfe !important;
}

.editor-heading {
    display: flex;
    align-items: center;
    gap: 14px;
    border-bottom: 1px solid #dce3f2;
    padding-bottom: 18px;
}

:global(.dark) .editor-heading {
    border-bottom-color: #263244;
}

.is-dark-theme .editor-heading {
    border-bottom-color: #263244 !important;
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

:global(.dark) .editor-heading span {
    background:
        linear-gradient(180deg, rgba(255, 255, 255, .08), rgba(255, 255, 255, 0)),
        rgba(96, 165, 250, .12);
    color: #bfdbfe;
}

.is-dark-theme .editor-heading span {
    background:
        linear-gradient(180deg, rgba(255, 255, 255, .08), rgba(255, 255, 255, 0)),
        rgba(96, 165, 250, .12) !important;
    color: #bfdbfe !important;
}

.editor-heading h3 {
    margin: 0;
    color: #20227d;
    font-size: 15px;
    font-weight: 600;
    line-height: 1.05;
}

:global(.dark) .editor-heading h3 {
    color: #e0e7ff;
}

.is-dark-theme .editor-heading h3 {
    color: #e0e7ff !important;
}

.editor-heading p {
    max-width: 205px;
    margin: 6px 0 0;
    color: #747b97;
    font-size: 10.5px;
    line-height: 1.45;
}

:global(.dark) .editor-heading p,
:global(.dark) .modal-editor label {
    color: #9ca3af;
}

.is-dark-theme .editor-heading p,
.is-dark-theme .modal-editor label {
    color: #9ca3af !important;
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

:global(.dark) .modal-editor input,
:global(.dark) .modal-editor textarea,
:global(.dark) .modal-editor select,
:global(.dark) .draft-table input {
    border-color: #374151;
    background: #0f172a;
    color: #e5e7eb;
}

.is-dark-theme .modal-editor input,
.is-dark-theme .modal-editor textarea,
.is-dark-theme .modal-editor select,
.is-dark-theme .draft-table input {
    border-color: #374151 !important;
    background: #0f172a !important;
    color: #e5e7eb !important;
}

:global(.dark) .modal-editor input::placeholder,
:global(.dark) .modal-editor textarea::placeholder {
    color: #6b7280;
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

:global(.dark) .draft-table th {
    background: #1f2937;
    color: #f8fafc;
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

:global(.dark) .modal-footer {
    border-top-color: #263244;
    background: #0f172a;
}

.is-dark-theme .modal-footer {
    border-top-color: #263244 !important;
    background: #0f172a !important;
}

.certificate-dark-ui .certificate-modal {
    border: 1px solid #263244 !important;
    background: #111827 !important;
    color: #e5e7eb !important;
}

.certificate-dark-ui .print-grid,
.certificate-dark-ui .create-grid {
    background:
        linear-gradient(90deg, #111827 0 310px, transparent 310px),
        radial-gradient(circle at 50% 8%, rgba(51, 65, 85, .8), rgba(30, 41, 59, .94) 48%, #0f172a 100%) !important;
}

.certificate-dark-ui .modal-editor {
    border-right-color: #263244 !important;
    background:
        radial-gradient(circle at 0 0, rgba(59, 130, 246, .13), transparent 34%),
        linear-gradient(180deg, #111827 0%, #0f172a 100%) !important;
}

.certificate-dark-ui .modal-footer {
    border-top-color: #263244 !important;
    background: #0f172a !important;
}

.certificate-dark-ui .preview-zone {
    background:
        radial-gradient(circle at 50% 0%, rgba(96, 165, 250, .12), transparent 38%),
        #111827 !important;
}

.certificate-dark-ui .modal-editor h3,
.certificate-dark-ui .draft-title,
.certificate-dark-ui .editor-heading h3 {
    color: #e0e7ff !important;
}

.certificate-dark-ui .editor-heading,
.certificate-dark-ui .draft-table-wrap {
    border-color: #263244 !important;
}

.certificate-dark-ui .editor-heading p,
.certificate-dark-ui .modal-editor label {
    color: #9ca3af !important;
}

.certificate-dark-ui .modal-editor input,
.certificate-dark-ui .modal-editor textarea,
.certificate-dark-ui .modal-editor select,
.certificate-dark-ui .draft-table input {
    border-color: #374151 !important;
    background: #0f172a !important;
    color: #e5e7eb !important;
}

.certificate-dark-ui .modal-editor input::placeholder,
.certificate-dark-ui .modal-editor textarea::placeholder {
    color: #64748b !important;
}

.certificate-dark-ui .normal-summary article,
.certificate-dark-ui .category-card,
.certificate-dark-ui .info-card,
.certificate-dark-ui .students-card,
.certificate-dark-ui .loading-card,
.certificate-dark-ui .free-form-card {
    border-color: #263244 !important;
    background:
        radial-gradient(circle at 12% 0%, rgba(96, 165, 250, .10), transparent 32%),
        linear-gradient(180deg, #172033 0%, #111827 100%) !important;
    color: #e5e7eb !important;
    box-shadow: 0 20px 48px rgba(0, 0, 0, .34) !important;
}

.certificate-dark-ui .filter-select,
.certificate-dark-ui .free-field input,
.certificate-dark-ui .free-field select {
    border-color: #374151 !important;
    background: #0f172a !important;
    color: #e5e7eb !important;
}

.certificate-dark-ui .free-field input::placeholder {
    color: #7b8498 !important;
}

.certificate-dark-ui .normal-summary p,
.certificate-dark-ui .free-field > span:first-child,
.certificate-dark-ui .preview-head span,
.certificate-dark-ui .info-grid p,
.certificate-dark-ui .student-table td,
.certificate-dark-ui .class-table td,
.certificate-dark-ui .draft-table td {
    color: #9ca3af !important;
}

.certificate-dark-ui .normal-summary strong,
.certificate-dark-ui .legacy-header h1,
.certificate-dark-ui .normal-toolbar h1,
.certificate-dark-ui .free-field > span:first-child b {
    color: #e5e7eb !important;
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

:global(.dark) .light-action,
:global(.dark) .outline-action {
    border-color: #374151;
    background: #111827;
    color: #d1d5db;
}

.outline-action {
    border-color: #2d2e83;
    color: #2d2e83;
}

:global(.dark) .outline-action {
    border-color: #60a5fa;
    color: #bfdbfe;
}

.preview-zone {
    display: grid;
    justify-items: center;
    align-content: start;
    min-height: 760px;
    padding: 18px 32px 30px;
}

:global(.dark) .preview-zone {
    background:
        radial-gradient(circle at 50% 0%, rgba(96, 165, 250, .12), transparent 38%),
        #111827;
}

.preview-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: min(560px, 100%);
    margin: 0 0 13px;
    color: #838386;
}

:global(.dark) .preview-head {
    color: #a3a3a3;
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

:global(.dark) .preview-head strong {
    background: rgba(96, 165, 250, .14);
    color: #bfdbfe;
}

.print-batch {
    display: none;
}

.normal-print-root {
    display: none;
}

.certificate-preview {
    display: grid;
    place-items: center;
    overflow: auto;
    max-width: 100%;
    border-radius: 8px;
}

:global(.dark) .certificate-preview {
    background: transparent;
}

.certificate-wrap {
    display: flex;
    justify-content: center;
    background: #e0e0e0;
    padding: 5px;
}

:global(.dark) .certificate-wrap {
    background: #e0e0e0;
}

.certificate {
    width: 520px;
    background: #fff;
    padding: 10px;
    box-shadow: 0 22px 45px rgba(20, 20, 45, .2);
}

:global(.dark) .certificate,
:global(.dark) .cert-inner-border {
    background: #fff;
}

.is-dark-theme :deep(.certificate-preview),
.is-dark-theme :deep(.certificate-free-wrapper) {
    background:
        radial-gradient(circle at 50% 0%, rgba(96, 165, 250, .10), transparent 34%),
        #111827 !important;
    box-shadow: 0 22px 50px rgba(0, 0, 0, .34) !important;
}

.is-dark-theme :deep(.certificate-wrap) {
    background: #e0e0e0 !important;
}

.is-dark-theme :deep(.certificate),
.is-dark-theme :deep(.certificate-free),
.is-dark-theme :deep(.cert-inner-border),
.is-dark-theme :deep(.cert-free-inner-border) {
    background: #fff !important;
    color-scheme: light !important;
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

:global(.dark) .legacy-certificate-page {
    background:
        radial-gradient(circle at 12% 0%, rgba(37, 99, 235, .13), transparent 34%),
        linear-gradient(180deg, #0b1120 0%, #111827 100%);
    color: #e5e7eb;
}

:global(html.dark) .legacy-certificate-page {
    background:
        radial-gradient(circle at 16% 0%, rgba(37, 99, 235, .16), transparent 32%),
        linear-gradient(180deg, #0b1120 0%, #111827 100%) !important;
    color: #e5e7eb !important;
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

:global(.dark) .free-form-card {
    border-color: #263244;
    background: linear-gradient(180deg, #172033 0%, #111827 100%);
    box-shadow: 0 20px 48px rgba(0, 0, 0, .32);
}

:global(html.dark) .free-form-card {
    border-color: #263244 !important;
    background:
        radial-gradient(circle at 12% 0%, rgba(96, 165, 250, .10), transparent 32%),
        linear-gradient(180deg, #172033 0%, #111827 100%) !important;
    box-shadow: 0 20px 48px rgba(0, 0, 0, .34) !important;
}

.is-dark-theme .free-form-card {
    border-color: #263244 !important;
    background:
        radial-gradient(circle at 12% 0%, rgba(96, 165, 250, .10), transparent 32%),
        linear-gradient(180deg, #172033 0%, #111827 100%) !important;
    box-shadow: 0 20px 48px rgba(0, 0, 0, .34) !important;
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

:global(.dark) .free-field > span:first-child {
    color: #bfdbfe;
}

:global(html.dark) .free-field > span:first-child {
    color: #bfdbfe !important;
}

.is-dark-theme .free-field > span:first-child {
    color: #bfdbfe !important;
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

:global(.dark) .free-input-icon {
    color: #94a3b8;
}

:global(html.dark) .free-input-icon {
    color: #94a3b8 !important;
}

.is-dark-theme .free-input-icon {
    color: #94a3b8 !important;
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

:global(.dark) .free-field input,
:global(.dark) .free-field select {
    border-color: #374151;
    background: #0f172a;
    color: #f8fafc;
}

:global(html.dark) .free-field input,
:global(html.dark) .free-field select {
    border-color: #374151 !important;
    background: #0f172a !important;
    color: #f8fafc !important;
}

.is-dark-theme .free-field input,
.is-dark-theme .free-field select {
    border-color: #374151 !important;
    background: #0f172a !important;
    color: #f8fafc !important;
}

.free-field input:focus,
.free-field select:focus {
    border-color: #2d2e81;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(45, 46, 129, .1);
    transform: translateY(-1px);
}

:global(.dark) .free-field input:focus,
:global(.dark) .free-field select:focus {
    border-color: #60a5fa;
    background: #111827;
    box-shadow: 0 0 0 4px rgba(96, 165, 250, .16);
}

.free-field input::placeholder {
    color: #aeb5c8;
    font-weight: 600;
}

:global(.dark) .free-field input::placeholder {
    color: #6b7280;
}

:global(html.dark) .free-field input::placeholder {
    color: #64748b !important;
}

.is-dark-theme .free-field input::placeholder {
    color: #64748b !important;
}

.free-field.has-error input,
.free-field.has-error select {
    border-color: #f05265;
    background: #fff8f9;
    box-shadow: 0 0 0 3px rgba(240, 82, 101, .06);
}

:global(.dark) .free-field.has-error input,
:global(.dark) .free-field.has-error select {
    border-color: #fb7185;
    background: rgba(127, 29, 29, .16);
    box-shadow: 0 0 0 3px rgba(251, 113, 133, .11);
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

    .normal-actions {
        min-width: 0;
        width: 100%;
    }

    .filter-select,
    .filter-select-small,
    .blue-action {
        flex: 0 0 auto;
        width: 100%;
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
    :global(body.normal-certificate-print) {
        width: 210mm !important;
        height: 297mm !important;
        min-height: 297mm !important;
        max-height: 297mm !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: hidden !important;
        background: #fff !important;
    }

    :global(body.normal-certificate-print > *:not(#normal-cert-print)) {
        display: none !important;
    }

    :global(body.normal-certificate-print #app) {
        display: none !important;
    }

    :global(body.normal-certificate-print #normal-cert-print),
    :global(body.normal-certificate-print #normal-cert-print *) {
        visibility: visible !important;
    }

    :global(body.normal-certificate-print #normal-cert-print) {
        position: fixed !important;
        inset: 0 auto auto 0 !important;
        display: block !important;
        width: 210mm !important;
        height: 297mm !important;
        min-height: 297mm !important;
        max-height: 297mm !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: hidden !important;
        background: #fff !important;
    }

    :global(body.normal-certificate-print #normal-cert-print .printable-certificate) {
        display: flex !important;
        align-items: stretch !important;
        justify-content: stretch !important;
        width: 210mm !important;
        height: 297mm !important;
        min-height: 297mm !important;
        max-height: 297mm !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: hidden !important;
        background: #fff !important;
        page-break-after: always !important;
        break-after: page !important;
        page-break-before: auto !important;
        break-before: auto !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    :global(body.normal-certificate-print #normal-cert-print .printable-certificate:last-child) {
        page-break-after: avoid !important;
        break-after: avoid !important;
    }

    :global(body.normal-certificate-print #normal-cert-print .certificate-wrap) {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 100% !important;
        height: 100% !important;
        min-height: 297mm !important;
        max-height: 297mm !important;
        margin: 0 auto !important;
        padding: 0 !important;
        overflow: hidden !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    :global(body.normal-certificate-print #normal-cert-print .certificate) {
        box-sizing: border-box !important;
        width: 210mm !important;
        height: 297mm !important;
        min-height: 297mm !important;
        max-height: 297mm !important;
        margin: 0 !important;
        padding: 5mm !important;
        overflow: hidden !important;
        background: #fff !important;
        box-shadow: none !important;
        transform: none !important;
    }

    * {
        print-color-adjust: exact !important;
        -webkit-print-color-adjust: exact !important;
    }
}
</style>
