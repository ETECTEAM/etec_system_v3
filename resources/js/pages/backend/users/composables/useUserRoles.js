import { computed, nextTick, ref, watch } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'
import { formatRole } from '../../../../lib/roleBadge'
import { useConfirm } from '../../../../composables/useConfirm'
import { useSaveForm } from '../../../../composables/useSaveForm'
import { useI18n } from '../../../../i18n'

const PREFERRED_ACTIONS = ['view', 'create', 'update', 'delete', 'manage', 'approve', 'export', 'track']
const MATRIX_PER_PAGE = 10
// The Super Admin role can't be removed so nobody can lock out admin access.
const UNDELETABLE_ROLE_NAMES = ['super_admin']

// Order-independent comparison, since toggling permissions off then back on
// can leave the array in a different order than what the server returned.
function sameItems(a, b) {
  if (a.length !== b.length) {
    return false
  }

  const setB = new Set(b)

  return a.every((item) => setB.has(item))
}

// Owns role/permission matrix data, user-assignment data, role create/delete, and their save forms.
// selectedRoleId/userSearch/permissionSearch/matrixPage/showCreateModal/showOnlyChecked are UI state owned by the component, passed in by ref.
export function useUserRoles({ selectedRoleId, userSearch, permissionSearch, matrixPage, showCreateModal, showOnlyChecked }) {
  const page = usePage()
  const toast = useToast()
  const { confirm } = useConfirm()
  const { t } = useI18n()

  watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
      toast.success(flash.success)
    } else if (flash?.error) {
      toast.error(flash.error)
    }
  }, { deep: true })

  const isSuperAdmin = computed(() => (page.props.auth?.roles ?? []).includes('super_admin'))
  const roles = computed(() => page.props.roles ?? [])
  const permissions = computed(() => page.props.permissions ?? [])
  const users = computed(() => page.props.users ?? [])

  // Default to the first role in the list so the matrix isn't empty on load.
  if (!selectedRoleId.value && roles.value[0]?.id) {
    selectedRoleId.value = String(roles.value[0].id)
  }

  const { form, save: savePermissionsForm } = useSaveForm({ permissions: [] })
  const { form: assignUsersForm, save: saveAssignUsersForm } = useSaveForm({ users: [] })
  // Kept as a plain useForm (not routed through useSaveForm): the original create-role
  // request doesn't set preserveScroll, unlike the other two saves on this page.
  const createRoleForm = useForm({ name: '' })

  function openCreateModal() {
    createRoleForm.reset()
    createRoleForm.clearErrors()
    showCreateModal.value = true
  }

  function closeCreateModal() {
    showCreateModal.value = false
    createRoleForm.reset()
    createRoleForm.clearErrors()
  }

  function submitCreateRole() {
    createRoleForm.post('/dashboard/users/roles', {
      onSuccess: () => {
        closeCreateModal()
        // Auto-select the newly added role (last in list after redirect).
        nextTick(() => {
          const last = roles.value[roles.value.length - 1]
          if (last) {
            selectedRoleId.value = String(last.id)
          }
        })
      },
    })
  }

  function isRoleDeletable(role) {
    return isSuperAdmin.value && !UNDELETABLE_ROLE_NAMES.includes(role.name) && Number(role.users_count ?? 0) === 0
  }

  function deleteDisabledReason(role) {
    if (isRoleDeletable(role)) {
      return 'Delete role'
    }

    if (!isSuperAdmin.value) {
      return 'Only Super Admin can delete roles'
    }

    if (role.name === 'super_admin') {
      return 'The Super Admin role cannot be deleted'
    }

    return 'Reassign users before deleting'
  }

  async function deleteRole(role) {
    if (!isRoleDeletable(role)) {
      return
    }

    const confirmed = await confirm({
      title: t('Delete this role?'),
      message: t('Delete the ":role" role? This cannot be undone.', { role: formatRole(role.name) }),
      confirmText: t('Delete'),
      danger: true,
    })

    if (!confirmed) {
      return
    }

    router.delete(`/dashboard/users/roles/${role.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        if (selectedRoleId.value === String(role.id)) {
          selectedRoleId.value = roles.value[0]?.id ? String(roles.value[0].id) : ''
        }
      },
    })
  }

  // The selected role drives both the permission matrix and the user assignment list.
  const selectedRole = computed(() => roles.value.find((role) => String(role.id) === selectedRoleId.value) ?? null)
  const totalUsers = computed(() => roles.value.reduce((total, role) => total + Number(role.users_count ?? 0), 0))
  const activeRoles = computed(() => roles.value.length)
  // Count modules where the current role does not have every available action.
  const restrictedModules = computed(() => resources.value.filter((resource) => {
    return actions.value.some((action) => permissionName(resource, action) && !form.permissions.includes(permissionName(resource, action)))
  }).length)

  // Build the action columns shown in the matrix.
  const actions = computed(() => {
    const discovered = permissions.value
      .map((permission) => permission.split('.')[1])
      .filter(Boolean)

    return [...PREFERRED_ACTIONS, ...discovered.filter((action) => !PREFERRED_ACTIONS.includes(action))]
      .filter((action, index, list) => list.indexOf(action) === index)
  })

  // Build the unique module list from all permission names.
  const resources = computed(() => {
    return permissions.value
      .map((permission) => permission.split('.')[0])
      .filter((resource, index, list) => list.indexOf(resource) === index)
  })

  // Snapshot of which modules had a checked permission at the moment the role was selected.
  // Used only for sort order, so rows don't jump around underneath the user while they edit.
  const checkedResourcesSnapshot = ref(new Set())

  // Modules with any checked permission (live, reflects in-progress edits) - used by the
  // "Show only checked" filter and the checked-count display.
  function isResourceChecked(resource) {
    return resourcePermissions(resource).some((permission) => form.permissions.includes(permission))
  }

  // Checked-first order, frozen per role selection to avoid re-sorting while the user edits.
  const sortedResources = computed(() => {
    return [...resources.value].sort((a, b) => {
      const aChecked = checkedResourcesSnapshot.value.has(a) ? 0 : 1
      const bChecked = checkedResourcesSnapshot.value.has(b) ? 0 : 1

      return aChecked - bChecked
    })
  })

  const checkedResourcesCount = computed(() => resources.value.filter(isResourceChecked).length)

  // Filter modules by the search box and the "show only checked" toggle before paginating them.
  const filteredResources = computed(() => {
    const keyword = permissionSearch.value.trim().toLowerCase()

    let list = sortedResources.value

    if (keyword !== '') {
      list = list.filter((resource) => {
        return resource.replaceAll('_', ' ').toLowerCase().includes(keyword)
          || actions.value.some((action) => permissionName(resource, action)?.toLowerCase().includes(keyword))
      })
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

  const roleSummaries = computed(() => roles.value.map((role) => ({
    ...role,
    selected: String(role.id) === selectedRoleId.value,
    deletable: isRoleDeletable(role),
  })))

  // Narrow the user list by name, email, or assigned role.
  const filteredUsers = computed(() => {
    const keyword = userSearch.value.trim().toLowerCase()

    if (keyword === '') {
      return users.value
    }

    return users.value.filter((user) => {
      return user.name.toLowerCase().includes(keyword)
        || user.email.toLowerCase().includes(keyword)
        || (user.roles ?? []).some((role) => formatRole(role).toLowerCase().includes(keyword))
    })
  })

  const selectedUserCount = computed(() => assignUsersForm.users.length)
  const allPermissionsSelected = computed(() => {
    return permissions.value.length > 0 && form.permissions.length === permissions.value.length
  })

  // Drives the "you have unsaved changes" nudge on the Save Changes button.
  const hasUnsavedPermissionChanges = computed(() => {
    return !sameItems(form.permissions, selectedRole.value?.permissions ?? [])
  })

  // Return the full permission key only when it exists in the backend list.
  function permissionName(resource, action) {
    const name = `${resource}.${action}`

    return permissions.value.includes(name) ? name : null
  }

  // Reset both forms whenever the selected role changes.
  function syncFormFromSelectedRole() {
    form.permissions = [...(selectedRole.value?.permissions ?? [])]
    checkedResourcesSnapshot.value = new Set(form.permissions.map((permission) => permission.split('.')[0]))
    assignUsersForm.users = users.value
      .filter((user) => (user.roles ?? []).includes(selectedRole.value?.name))
      .map((user) => user.id)
    form.clearErrors()
    assignUsersForm.clearErrors()
    // New role means a new checked-first order, so land back on page 1.
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

  function isChecked(permission) {
    return permission ? form.permissions.includes(permission) : false
  }

  function resourcePermissions(resource) {
    return actions.value
      .map((action) => permissionName(resource, action))
      .filter(Boolean)
  }

  function isResourceFullyChecked(resource) {
    const currentPermissions = resourcePermissions(resource)

    return currentPermissions.length > 0 && currentPermissions.every((permission) => form.permissions.includes(permission))
  }

  function isResourcePartiallyChecked(resource) {
    const currentPermissions = resourcePermissions(resource)

    return currentPermissions.some((permission) => form.permissions.includes(permission)) && !isResourceFullyChecked(resource)
  }

  function toggleResourcePermissions(resource) {
    const currentPermissions = resourcePermissions(resource)

    if (isResourceFullyChecked(resource)) {
      form.permissions = form.permissions.filter((permission) => !currentPermissions.includes(permission))
      return
    }

    form.permissions = [...new Set([...form.permissions, ...currentPermissions])]
  }

  function selectAllPermissions() {
    form.permissions = [...permissions.value]
  }

  // Keep the checkbox handler separate so the template stays simple.
  function toggleAllPermissions(event) {
    if (event.target.checked) {
      selectAllPermissions()
      return
    }

    clearAllPermissions()
  }

  function clearAllPermissions() {
    form.permissions = []
  }

  // Save the selected permissions for the active role.
  function savePermissions() {
    if (!selectedRole.value) {
      return
    }

    savePermissionsForm(`/dashboard/users/roles/${selectedRole.value.id}/permissions`)
  }

  function toggleUser(userId) {
    if (assignUsersForm.users.includes(userId)) {
      assignUsersForm.users = assignUsersForm.users.filter((id) => id !== userId)
      return
    }

    assignUsersForm.users = [...assignUsersForm.users, userId]
  }

  function isUserSelected(userId) {
    return assignUsersForm.users.includes(userId)
  }

  // Save the user-role assignments for the active role.
  function saveAssignedUsers() {
    if (!selectedRole.value) {
      return
    }

    saveAssignUsersForm(`/dashboard/users/roles/${selectedRole.value.id}/users`)
  }

  function changeMatrixPage(pageNumber) {
    matrixPage.value = pageNumber
  }

  watch(selectedRoleId, syncFormFromSelectedRole, { immediate: true })

  watch(resources, () => {
    matrixPage.value = 1
  })

  watch(permissionSearch, () => {
    matrixPage.value = 1
  })

  watch(showOnlyChecked, () => {
    matrixPage.value = 1
  })

  return {
    permissions,
    form,
    assignUsersForm,
    createRoleForm,
    openCreateModal,
    closeCreateModal,
    submitCreateRole,
    deleteDisabledReason,
    deleteRole,
    selectedRole,
    totalUsers,
    activeRoles,
    restrictedModules,
    actions,
    filteredResources,
    checkedResourcesCount,
    matrixLastPage,
    paginatedResources,
    matrixStart,
    matrixEnd,
    roleSummaries,
    filteredUsers,
    selectedUserCount,
    allPermissionsSelected,
    hasUnsavedPermissionChanges,
    permissionName,
    togglePermission,
    isChecked,
    isResourceFullyChecked,
    isResourcePartiallyChecked,
    toggleResourcePermissions,
    toggleAllPermissions,
    clearAllPermissions,
    savePermissions,
    toggleUser,
    isUserSelected,
    saveAssignedUsers,
    changeMatrixPage,
  }
}
