import { computed, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'
import { formatRole } from '../../../../lib/roleBadge'
import { useSaveForm } from '../../../../composables/useSaveForm'

const PREFERRED_ACTIONS = ['view', 'create', 'update', 'delete', 'manage', 'approve', 'export', 'track']
const MATRIX_PER_PAGE = 10

// Order-independent comparison, since toggling permissions off then back on
// can leave the array in a different order than what the server returned.
function sameItems(a, b) {
  if (a.length !== b.length) {
    return false
  }

  const setB = new Set(b)

  return a.every((item) => setB.has(item))
}

// Owns the permission-matrix domain data and the direct-permission save form.
// selectedUserId/userSearchQuery/moduleSearchQuery/matrixPage/showOnlyChecked are UI state owned by the component and passed in by ref.
export function useUserPermissions({ selectedUserId, userSearchQuery, moduleSearchQuery, matrixPage, showOnlyChecked }) {
  const page = usePage()
  const toast = useToast()
  const users = computed(() => page.props.users ?? [])
  const permissions = computed(() => page.props.permissions ?? [])

  watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
      toast.success(flash.success)
    } else if (flash?.error) {
      toast.error(flash.error)
    }
  }, { deep: true })

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

  // Snapshot of which modules had any checked permission (direct or role-inherited) at
  // the moment the user was selected. Used only for sort order, so rows don't jump
  // around underneath the user while they edit direct permissions.
  const checkedResourcesSnapshot = ref(new Set())

  function resourcePermissions(resource) {
    return actions.value
      .map((action) => permissionName(resource, action))
      .filter(Boolean)
  }

  // A module reads as "checked" if any of its actions show a filled box - direct
  // (blue) or role-inherited (pale) - since that's what the user visually sees.
  function isResourceChecked(resource) {
    return resourcePermissions(resource).some((permission) => isDirectPermission(permission) || isRolePermission(permission))
  }

  const checkedResourcesCount = computed(() => resources.value.filter(isResourceChecked).length)

  // Checked-first order, frozen per user selection to avoid re-sorting while the user edits.
  const sortedResources = computed(() => {
    return [...resources.value].sort((a, b) => {
      const aChecked = checkedResourcesSnapshot.value.has(a) ? 0 : 1
      const bChecked = checkedResourcesSnapshot.value.has(b) ? 0 : 1

      return aChecked - bChecked
    })
  })

  // Filter against the same "Attendance"/"Building" display text the table renders,
  // not the raw underscored resource key, so the search matches what's on screen.
  const filteredResources = computed(() => {
    const query = moduleSearchQuery.value.trim().toLowerCase()

    let list = sortedResources.value

    if (query) {
      list = list.filter((resource) => resource.replaceAll('_', ' ').toLowerCase().includes(query))
    }

    if (showOnlyChecked.value) {
      list = list.filter((resource) => isResourceChecked(resource))
    }

    return list
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
    checkedResourcesSnapshot.value = new Set(
      [...form.permissions, ...(selectedUser.value?.role_permissions ?? [])].map((permission) => permission.split('.')[0]),
    )
    form.clearErrors()
    // New user means a new checked-first order, so land back on page 1.
    matrixPage.value = 1
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

  const allPermissionsSelected = computed(() => {
    return permissions.value.length > 0 && form.permissions.length === permissions.value.length
  })

  // Drives the "you have unsaved changes" nudge on the Save Changes button.
  const hasUnsavedPermissionChanges = computed(() => {
    return !sameItems(form.permissions, selectedUser.value?.direct_permissions ?? [])
  })

  function selectAllPermissions() {
    form.permissions = [...permissions.value]
  }

  function clearAllPermissions() {
    form.permissions = []
  }

  // Keep the checkbox handler separate so the template stays simple.
  function toggleAllPermissions(event) {
    if (event.target.checked) {
      selectAllPermissions()
      return
    }

    clearAllPermissions()
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
    checkedResourcesCount,
    matrixLastPage,
    paginatedResources,
    matrixStart,
    matrixEnd,
    filteredUserSummaries,
    permissionName,
    togglePermission,
    isDirectPermission,
    isRolePermission,
    allPermissionsSelected,
    hasUnsavedPermissionChanges,
    toggleAllPermissions,
    clearAllPermissions,
    savePermissions,
  }
}
