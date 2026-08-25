import { onMounted, ref, watch } from 'vue'
import axios from 'axios'
import { usePage } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'
import { useConfirm } from '../../../../composables/useConfirm'
import { useI18n } from '../../../../i18n'

// Owns the history table: filter state, paginated fetch, row decisions
// (approve/reject/revoke/delete) and the CSV export download.
export function useLeaveHistory({ canDelete }) {
  const { confirm } = useConfirm()
  const { t } = useI18n()
  const toast = useToast()
  const page = usePage()

  const search = ref('')
  const statusFilter = ref('')
  const classFilter = ref('')
  const dateFrom = ref('')
  const dateTo = ref('')

  const leaves = ref([])
  const pagination = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 })
  const isLoading = ref(false)
  const hasLoaded = ref(false)
  const busyId = ref(null)

  let searchTimer = null

  function filterParams(extra = {}) {
    const params = {}

    if (search.value.trim()) params.search = search.value.trim()
    if (statusFilter.value) params.status = statusFilter.value
    if (classFilter.value) params.study_class_id = classFilter.value
    if (dateFrom.value) params.date_from = dateFrom.value
    if (dateTo.value) params.date_to = dateTo.value

    return { ...params, ...extra }
  }

  async function fetchLeaves(pageNumber = 1) {
    isLoading.value = true

    try {
      const response = await axios.get('/dashboard/official-leaves/history/data', {
        params: filterParams({ page: pageNumber, per_page: 10 }),
      })

      leaves.value = response.data.data ?? []
      pagination.value = {
        current_page: response.data.current_page ?? 1,
        last_page: response.data.last_page ?? 1,
        per_page: response.data.per_page ?? 10,
        total: response.data.total ?? 0,
      }
    } catch (error) {
      console.error('Failed to fetch leave history', error)
      toast.error(t('Failed to load leave history. Please try again.'))
    } finally {
      hasLoaded.value = true
      isLoading.value = false
    }
  }

  function exportCsv() {
    const query = new URLSearchParams(filterParams()).toString()

    window.open(`/dashboard/official-leaves/history/export${query ? `?${query}` : ''}`, '_blank')
  }

  async function approve(leave) {
    const confirmed = await confirm({
      title: t('Approve this official leave?'),
      message: t(':name will be excused for :start to :end.', {
        name: leave.student.full_name,
        start: leave.start_date,
        end: leave.end_date,
      }),
      confirmText: t('Approve'),
      cancelText: t('Cancel'),
    })

    if (!confirmed) return

    busyId.value = leave.id

    try {
      const response = await axios.post(`/dashboard/official-leaves/leaves/${leave.id}/approve`)

      toast.success(response.data.message ?? t('Official leave approved.'))
      await fetchLeaves(pagination.value.current_page)
    } catch (error) {
      handleError(error, t('Failed to approve the leave.'))
    } finally {
      busyId.value = null
    }
  }

  async function reject(leave, note) {
    if (!note?.trim()) {
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

    busyId.value = leave.id

    try {
      const response = await axios.post(`/dashboard/official-leaves/leaves/${leave.id}/reject`, {
        note: note.trim(),
      })

      toast.success(response.data.message ?? t('Official leave rejected.'))
      await fetchLeaves(pagination.value.current_page)
    } catch (error) {
      handleError(error, t('Failed to reject the leave.'))
    } finally {
      busyId.value = null
    }
  }

  async function revoke(leave) {
    const confirmed = await confirm({
      title: t('Revoke this approved leave?'),
      message: t(':name becomes unexcused again for :start to :end, and attendance is editable for those dates.', {
        name: leave.student.full_name,
        start: leave.start_date,
        end: leave.end_date,
      }),
      confirmText: t('Revoke'),
      cancelText: t('Cancel'),
      danger: true,
    })

    if (!confirmed) return

    busyId.value = leave.id

    try {
      const response = await axios.post(`/dashboard/official-leaves/leaves/${leave.id}/revoke`, {})

      toast.success(response.data.message ?? t('Official leave revoked.'))
      await fetchLeaves(pagination.value.current_page)
    } catch (error) {
      handleError(error, t('Failed to revoke the leave.'))
    } finally {
      busyId.value = null
    }
  }

  async function remove(leave) {
    const confirmed = await confirm({
      title: t('Delete this leave record?'),
      message: t('This removes the record for :name (:start to :end). The audit trail is kept.', {
        name: leave.student.full_name,
        start: leave.start_date,
        end: leave.end_date,
      }),
      confirmText: t('Delete'),
      cancelText: t('Cancel'),
      danger: true,
    })

    if (!confirmed) return

    busyId.value = leave.id

    try {
      const response = await axios.delete(`/dashboard/official-leaves/leaves/${leave.id}`)

      toast.success(response.data.message ?? t('Leave deleted.'))
      await refetchAfterDelete()
    } catch (error) {
      handleError(error, t('Failed to delete the leave.'))
    } finally {
      busyId.value = null
    }
  }

  async function refetchAfterDelete() {
    await fetchLeaves(pagination.value.current_page)

    if (leaves.value.length === 0 && pagination.value.current_page > 1) {
      await fetchLeaves(pagination.value.current_page - 1)
    }
  }

  // Admin may only revoke while the leave hasn't started; super_admin anytime.
  const currentUserRole = page.props.auth?.roles?.[0] ?? null

  function canRevoke(leave) {
    if (leave.status !== 'approved') return false

    if (currentUserRole === 'super_admin') return true

    return new Date(`${leave.start_date}T00:00:00`) > new Date()
  }

  function canDecide(leave) {
    return leave.status === 'pending' && !leave.deleted
  }

  function handleError(error, fallback) {
    const errors = error.response?.data?.errors
    const message = errors ? Object.values(errors)[0]?.[0] : error.response?.data?.message

    toast.error(message ?? fallback)

    // A stale table (someone decided first elsewhere) refreshes silently.
    if (error.response?.status === 422 || error.response?.status === 403) {
      fetchLeaves(pagination.value.current_page)
    }
  }

  watch(search, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => fetchLeaves(1), 350)
  })

  watch([statusFilter, classFilter, dateFrom, dateTo], () => fetchLeaves(1))

  onMounted(() => fetchLeaves())

  return {
    search,
    statusFilter,
    classFilter,
    dateFrom,
    dateTo,
    leaves,
    pagination,
    isLoading,
    hasLoaded,
    busyId,
    canDelete,
    fetchLeaves,
    exportCsv,
    approve,
    reject,
    revoke,
    remove,
    canDecide,
    canRevoke,
  }
}
