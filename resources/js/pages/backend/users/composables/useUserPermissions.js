import { computed, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { formatRole } from '../../../../lib/roleBadge'
import { useSaveForm } from '../../../../composables/useSaveForm'

const PREFERRED_ACTIONS = ['view', 'create', 'update', 'delete', 'manage', 'approve', 'export', 'track']
const MATRIX_PER_PAGE = 10

// Owns the permission-matrix domain data and the direct-permission save form.
// selectedUserId/userSearchQuery/moduleSearchQuery/matrixPage are UI state owned by the component and passed in by ref.
export function useUserPermissions({ selectedUserId, userSearchQuery, moduleSearchQuery, matrixPage }) {
  const page = usePage()
  const users = computed(() => page.props.users ?? [])
  const permissions = computed(() => page.props.permissions ?? [])

  const { form, save } = useSaveForm({ permissions: [] })

  // Default to the first user in the list so the matrix isn't empty on load.
  if (!selectedUserId.value && users.value[0]?.id) {
    selectedUserId.value = String(users.value[0].id)
  }

  const selectedUser = computed(() => users.value.find((user) => String(user.id) === selectedUserId.value) ?? null)
  const totalUsers = computed(() => users.value.length)
  const directPermissionUsers = computed(() => users.value.filter((user) => (user.direct_permissions?.length ?? 0) > 0).length)
  const selectedDirectCount = computed(() => form.permissions.length)
  const selectedRoleCount = computed(() => selectedUser.value?.role_permissions?.length ?? 0)
  const selectedTotalCount = computed(() => {
    const combined = new Set([
      ...(selectedUser.value?.role_permissions ?? []),
      ...form.permissions,
    ])

    return combined.size
  })

  const actions = computed(() => {
    const discovered = permissions.value
      .map((permission) => permission.split('.')[1])
      .filter(Boolean)

    return [...PREFERRED_ACTIONS, ...discovered.filter((action) => !PREFERRED_ACTIONS.includes(action))]
      .filter((action, index, list) => list.indexOf(action) === index)
  })

  const resources = computed(() => {
    return permissions.value
      .map((permission) => permission.split('.')[0])
      .filter((resource, index, list) => list.indexOf(resource) === index)
  })

  // Filter against the same "Attendance"/"Building" display text the table renders,
  // not the raw underscored resource key, so the search matches what's on screen.
  const filteredResources = computed(() => {
    const query = moduleSearchQuery.value.trim().toLowerCase()

    if (!query) {
      return resources.value
    }

    return resources.value.filter((resource) => resource.replaceAll('_', ' ').toLowerCase().includes(query))
  })

  const matrixLastPage = computed(() => Math.max(1, Math.ceil(filteredResources.value.length / MATRIX_PER_PAGE)))
  const paginatedResources = computed(() => {
    const start = (matrixPage.value - 1) * MATRIX_PER_PAGE

    return filteredResources.value.slice(start, start + MATRIX_PER_PAGE)
  })
  const matrixStart = computed(() => {
    if (filteredResources.value.length === 0) {
      return 0
    }

    return ((matrixPage.value - 1) * MATRIX_PER_PAGE) + 1
  })
  const matrixEnd = computed(() => {
    if (filteredResources.value.length === 0) {
      return 0
    }

    return Math.min(matrixPage.value * MATRIX_PER_PAGE, filteredResources.value.length)
  })

  const userSummaries = computed(() => users.value.map((user) => ({
    ...user,
    selected: String(user.id) === selectedUserId.value,
  })))

  // Match against name, email, and formatted role labels (e.g. "Instructor") so
  // typing a role name filters the list the same way typing a name does.
  const filteredUserSummaries = computed(() => {
    const query = userSearchQuery.value.trim().toLowerCase()

    if (!query) {
      return userSummaries.value
    }

    return userSummaries.value.filter((user) => {
      const roleLabels = (user.roles ?? []).map((role) => formatRole(role).toLowerCase())

      return (user.name ?? '').toLowerCase().includes(query)
        || (user.email ?? '').toLowerCase().includes(query)
        || roleLabels.some((label) => label.includes(query))
    })
  })

  // Returns null when a resource/action combo isn't a real permission, so the matrix cell renders "-" instead of a checkbox.
  function permissionName(resource, action) {
    const name = `${resource}.${action}`

    return permissions.value.includes(name) ? name : null
  }

  function syncFormFromSelectedUser() {
    form.permissions = [...(selectedUser.value?.direct_permissions ?? [])]
    form.clearErrors()
  }

  function togglePermission(permission) {
    if (!permission) {
      return
    }

    if (form.permissions.includes(permission)) {
      form.permissions = form.permissions.filter((item) => item !== permission)
      return
    }

    form.permissions = [...form.permissions, permission]
  }

  function isDirectPermission(permission) {
    return permission ? form.permissions.includes(permission) : false
  }

  function isRolePermission(permission) {
    return permission ? (selectedUser.value?.role_permissions ?? []).includes(permission) : false
  }

  function savePermissions() {
    if (!selectedUser.value) {
      return
    }

    save(`/dashboard/users/${selectedUser.value.id}/permissions`)
  }

  watch(selectedUserId, syncFormFromSelectedUser, { immediate: true })

  // Reset to page 1 whenever the underlying data or the module search changes,
  // otherwise a filter could leave the view stuck on a now out-of-range page.
  watch(filteredResources, () => {
    matrixPage.value = 1
  })

  return {
    form,
    users,
    selectedUser,
    totalUsers,
    directPermissionUsers,
    selectedDirectCount,
    selectedRoleCount,
    selectedTotalCount,
    actions,
    filteredResources,
    matrixLastPage,
    paginatedResources,
    matrixStart,
    matrixEnd,
    filteredUserSummaries,
    permissionName,
    togglePermission,
    isDirectPermission,
    isRolePermission,
    savePermissions,
  }
}
