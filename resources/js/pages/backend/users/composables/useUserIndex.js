import { onMounted, ref, watch } from 'vue'
import axios from 'axios'
import { router, usePage } from '@inertiajs/vue3'
import { useConfirm } from '../../../../composables/useConfirm'
import { useI18n } from '../../../../i18n'

// Owns user-list data fetching (axios, not Inertia props), delete confirmation.
// Flash-message toasts are handled globally by FlashToasts.vue.
// search/selectedRole are UI state owned by the component and passed in by ref, since typing in either refetches page 1.
export function useUserIndex({ search, selectedRole }) {
  const { confirm } = useConfirm()
  const { t } = useI18n()
  const page = usePage()

  const canCreateUser = page.props.canCreateUser ?? false
  const currentUserRole = page.props.auth?.roles?.[0] ?? null
  const currentUserId = page.props.auth?.user?.id ?? null

  const users = ref([])
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 5,
    total: 0,
  })
  const roles = ref([])
  const isLoadingUsers = ref(false)
  const hasLoadedUsers = ref(false)

  onMounted(async () => {
    await Promise.all([
      fetchRoles(),
      fetchUsers(),
    ])
  })

  async function fetchRoles() {
    try {
      const response = await axios.get('/roles')
      roles.value = Array.isArray(response.data) ? response.data : []
    } catch (error) {
      console.error('Failed to fetch roles', error)
      roles.value = []
    }
  }

  async function fetchUsers(pageNumber = 1) {
    isLoadingUsers.value = true

    try {
      const params = { page: pageNumber, per_page: 5 }
      if (search.value) params.search = search.value
      if (selectedRole.value) params.role = selectedRole.value

      const response = await axios.get('/dashboard/users/data', { params })

      users.value = response.data.data ?? []
      pagination.value = {
        current_page: response.data.current_page ?? 1,
        last_page: response.data.last_page ?? 1,
        per_page: response.data.per_page ?? 5,
        total: response.data.total ?? 0,
      }
    } catch (error) {
      console.error('Failed to fetch users', error)
    } finally {
      hasLoadedUsers.value = true
      isLoadingUsers.value = false
    }
  }

  function viewUser(id) {
    router.visit(`/dashboard/users/${id}`)
  }

  function editUser(id) {
    router.visit(`/dashboard/users/edit/${id}`)
  }

  async function deleteUser(id) {
    const confirmed = await confirm({
      title: t('Delete this user?'),
      message: t('This will permanently remove the account. This action cannot be undone.'),
      confirmText: t('Delete'),
      danger: true,
    })

    if (!confirmed) {
      return
    }

    router.delete(`/dashboard/users/${id}`, {
      preserveScroll: true,
      // The table's data comes from a separate axios fetch, not Inertia props,
      // so the redirect this triggers won't refresh it on its own — pull the
      // current page again once the delete has actually gone through.
      onSuccess: () => refetchAfterDelete(),
    })
  }

  async function refetchAfterDelete() {
    await fetchUsers(pagination.value.current_page)

    // Deleting the last row on a page (other than page 1) leaves that page
    // empty even though earlier pages still have data — step back one page.
    if (users.value.length === 0 && pagination.value.current_page > 1) {
      await fetchUsers(pagination.value.current_page - 1)
    }
  }

  watch(search, () => {
    fetchUsers(1)
  })

  watch(selectedRole, () => {
    fetchUsers(1)
  })

  return {
    canCreateUser,
    currentUserRole,
    currentUserId,
    users,
    pagination,
    roles,
    isLoadingUsers,
    hasLoadedUsers,
    fetchUsers,
    viewUser,
    editUser,
    deleteUser,
  }
}
