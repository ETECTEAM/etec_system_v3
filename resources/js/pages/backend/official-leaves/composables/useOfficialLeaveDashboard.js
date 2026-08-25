import { onBeforeUnmount, ref } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import { useConfirm } from '../../../../composables/useConfirm'
import { useI18n } from '../../../../i18n'

// Owns the desk flow: debounced student search, the QR session lifecycle with its
// 3s poll + 1s countdown, and approve/reject confirmations for the review card.
export function useOfficialLeaveDashboard() {
  const { confirm } = useConfirm()
  const { t } = useI18n()
  const toast = useToast()

  const search = ref('')
  const isSearching = ref(false)
  const hasSearched = ref(false)
  const results = ref([])

  const activeStudent = ref(null)
  const qrSession = ref(null)
  const modalOpen = ref(false)
  const sessionState = ref('idle') // idle | waiting | submitted | expired
  const reviewLeave = ref(null)
  const remainingSeconds = ref(0)
  const deciding = ref(false)
  const rejectNote = ref('')

  let searchTimer = null
  let pollTimer = null
  let tickTimer = null

  function debounceSearch() {
    clearTimeout(searchTimer)

    if (!search.value.trim()) {
      results.value = []
      hasSearched.value = false

      return
    }

    searchTimer = setTimeout(fetchStudents, 350)
  }

  async function fetchStudents() {
    isSearching.value = true

    try {
      const response = await axios.get('/dashboard/official-leaves/students/search', {
        params: { search: search.value.trim() },
      })

      results.value = response.data.data ?? []
      hasSearched.value = true
    } catch (error) {
      console.error('Failed to search students', error)
      toast.error(t('Failed to search students. Please try again.'))
    } finally {
      isSearching.value = false
    }
  }

  async function openRequestModal(student) {
    activeStudent.value = student
    reviewLeave.value = null
    rejectNote.value = ''
    modalOpen.value = true

    await generateQr()
  }

  async function generateQr() {
    sessionState.value = 'waiting'
    stopTimers()

    try {
      const response = await axios.post('/dashboard/official-leaves/qr', {
        student_id: activeStudent.value.id,
      })

      qrSession.value = response.data.data
      remainingSeconds.value = qrSession.value.ttl_seconds
      startPolling()
      startCountdown()
    } catch (error) {
      sessionState.value = 'idle'
      toast.error(error.response?.data?.message ?? t('Failed to generate QR code. Please try again.'))
    }
  }

  function startPolling() {
    pollTimer = setInterval(pollOnce, 3000)
  }

  async function pollOnce() {
    if (!qrSession.value) return

    try {
      const response = await axios.get(
        `/dashboard/official-leaves/sessions/${qrSession.value.session_id}/poll`,
      )

      applyPollState(response.data)
    } catch (error) {
      console.error('Failed to poll session', error)
    }
  }

  function applyPollState(payload) {
    if (payload.state === 'submitted' && payload.leave) {
      sessionState.value = 'submitted'
      reviewLeave.value = payload.leave
      stopTimers()
    } else if (payload.state === 'expired') {
      sessionState.value = 'expired'
      stopTimers()
    }
  }

  function startCountdown() {
    tickTimer = setInterval(() => {
      remainingSeconds.value = Math.max(0, remainingSeconds.value - 1)
    }, 1000)
  }

  function stopTimers() {
    clearInterval(pollTimer)
    clearInterval(tickTimer)
    pollTimer = null
    tickTimer = null
  }

  function closeModal() {
    modalOpen.value = false
    sessionState.value = 'idle'
    qrSession.value = null
    reviewLeave.value = null
    rejectNote.value = ''
    stopTimers()
    fetchStudents()
  }

  async function approveReview() {
    if (!reviewLeave.value) return

    const confirmed = await confirm({
      title: t('Approve this official leave?'),
      message: t(':name will be excused for :start to :end.', {
        name: reviewLeave.value.student.full_name,
        start: reviewLeave.value.start_date,
        end: reviewLeave.value.end_date,
      }),
      confirmText: t('Approve'),
      cancelText: t('Cancel'),
    })

    if (!confirmed) return

    deciding.value = true

    try {
      const response = await axios.post(
        `/dashboard/official-leaves/leaves/${reviewLeave.value.id}/approve`,
      )

      toast.success(response.data.message ?? t('Official leave approved.'))
      closeModal()
    } catch (error) {
      handleError(error, t('Failed to approve the leave. Please try again.'))
    } finally {
      deciding.value = false
    }
  }

  async function rejectReview() {
    if (!reviewLeave.value) return

    if (!rejectNote.value.trim()) {
      toast.warning(t('A short rejection note is required.'))

      return
    }

    const confirmed = await confirm({
      title: t('Reject this official leave?'),
      message: t('The student will see that the request was rejected.'),
      confirmText: t('Reject'),
      cancelText: t('Cancel'),
      danger: true,
    })

    if (!confirmed) return

    deciding.value = true

    try {
      const response = await axios.post(
        `/dashboard/official-leaves/leaves/${reviewLeave.value.id}/reject`,
        { note: rejectNote.value.trim() },
      )

      toast.success(response.data.message ?? t('Official leave rejected.'))
      closeModal()
    } catch (error) {
      handleError(error, t('Failed to reject the leave. Please try again.'))
    } finally {
      deciding.value = false
    }
  }

  function handleError(error, fallback) {
    const errors = error.response?.data?.errors
    const message = errors ? Object.values(errors)[0]?.[0] : error.response?.data?.message

    toast.error(message ?? fallback)
  }

  onBeforeUnmount(stopTimers)

  return {
    search,
    isSearching,
    hasSearched,
    results,
    fetchStudents,
    debounceSearch,
    activeStudent,
    modalOpen,
    sessionState,
    qrSession,
    reviewLeave,
    remainingSeconds,
    deciding,
    rejectNote,
    openRequestModal,
    generateQr,
    closeModal,
    approveReview,
    rejectReview,
  }
}
