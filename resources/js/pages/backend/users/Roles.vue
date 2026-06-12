<script setup>
<<<<<<< HEAD
import { onMounted, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
=======
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import { Pagination } from '../../../components/ui/pagination'
import { formatRole, roleBadgeClass } from '../../../lib/roleBadge'
>>>>>>> origin/dev
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import axios from 'axios'

<<<<<<< HEAD
const roles = ref([])
const roleName = ref('')
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')

async function fetchRoles() {
    loading.value = true
    error.value = ''

    try {
        const response = await axios.get('/dashboard/permissions/roles')
        roles.value = response.data
    } catch (e) {
        error.value = 'Cannot fetch roles.'
        console.error(e)
    } finally {
        loading.value = false
    }
}

async function createRole() {
    error.value = ''
    success.value = ''

    if (!roleName.value.trim()) {
        error.value = 'Role name is required.'
        return
    }

    saving.value = true

    try {
        await axios.post('/dashboard/permissions/roles', {
            name: roleName.value.trim(),
            guard_name: 'web',
        })

        success.value = 'Role created successfully.'
        roleName.value = ''

        await fetchRoles()
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Cannot create role.'
        console.error(e)
    } finally {
        saving.value = false
    }
}

onMounted(() => {
    fetchRoles()
=======
const page = usePage()
const roles = computed(() => page.props.roles ?? [])
const permissions = computed(() => page.props.permissions ?? [])
const users = computed(() => page.props.users ?? [])
const selectedRoleId = ref(roles.value[0]?.id ? String(roles.value[0].id) : '')
const userSearch = ref('')
const permissionSearch = ref('')
const matrixPage = ref(1)
const matrixPerPage = 10

const form = useForm({
  permissions: [],
})

const assignUsersForm = useForm({
  users: [],
})

const preferredActions = ['view', 'create', 'update', 'delete', 'manage', 'approve', 'export', 'track']

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Users', href: '/dashboard/users' },
  { label: 'Roles & Permission', current: true },
]

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

  return [...preferredActions, ...discovered.filter((action) => !preferredActions.includes(action))]
    .filter((action, index, list) => list.indexOf(action) === index)
})

// Build the unique module list from all permission names.
const resources = computed(() => {
  return permissions.value
    .map((permission) => permission.split('.')[0])
    .filter((resource, index, list) => list.indexOf(resource) === index)
})

// Filter modules by the search box before paginating them.
const filteredResources = computed(() => {
  const keyword = permissionSearch.value.trim().toLowerCase()

  if (keyword === '') {
    return resources.value
  }

  return resources.value.filter((resource) => {
    return resource.replaceAll('_', ' ').toLowerCase().includes(keyword)
      || actions.value.some((action) => permissionName(resource, action)?.toLowerCase().includes(keyword))
  })
})

const matrixLastPage = computed(() => Math.max(1, Math.ceil(filteredResources.value.length / matrixPerPage)))
const paginatedResources = computed(() => {
  const start = (matrixPage.value - 1) * matrixPerPage

  return filteredResources.value.slice(start, start + matrixPerPage)
})
const matrixStart = computed(() => {
  if (filteredResources.value.length === 0) {
    return 0
  }

  return ((matrixPage.value - 1) * matrixPerPage) + 1
})
const matrixEnd = computed(() => {
  if (filteredResources.value.length === 0) {
    return 0
  }

  return Math.min(matrixPage.value * matrixPerPage, filteredResources.value.length)
})

const roleSummaries = computed(() => roles.value.map((role) => ({
  ...role,
  selected: String(role.id) === selectedRoleId.value,
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

// Return the full permission key only when it exists in the backend list.
function permissionName(resource, action) {
  const name = `${resource}.${action}`

  return permissions.value.includes(name) ? name : null
}

// Reset both forms whenever the selected role changes.
function syncFormFromSelectedRole() {
  form.permissions = [...(selectedRole.value?.permissions ?? [])]
  assignUsersForm.users = users.value
    .filter((user) => (user.roles ?? []).includes(selectedRole.value?.name))
    .map((user) => user.id)
  form.clearErrors()
  assignUsersForm.clearErrors()
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

  form.put(`/dashboard/users/roles/${selectedRole.value.id}/permissions`, {
    preserveScroll: true,
  })
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

function initials(name) {
  return name
    .split(' ')
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
}

// Save the user-role assignments for the active role.
function saveAssignedUsers() {
  if (!selectedRole.value) {
    return
  }

  assignUsersForm.put(`/dashboard/users/roles/${selectedRole.value.id}/users`, {
    preserveScroll: true,
  })
}

function changeMatrixPage(page) {
  matrixPage.value = page
}

watch(selectedRoleId, syncFormFromSelectedRole, { immediate: true })

watch(resources, () => {
  matrixPage.value = 1
})

watch(permissionSearch, () => {
  matrixPage.value = 1
>>>>>>> origin/dev
})
</script>

<template>
<<<<<<< HEAD
    <Head title="User Roles" />

    <DashboardLayout>
        <section class="space-y-6 p-4 sm:p-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">User Roles</h1>
                <p class="mt-1 text-sm text-slate-600">
                    View and create system roles.
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Create Role</h2>

                <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                    <input
                        v-model="roleName"
                        type="text"
                        placeholder="admin, instructor, super_admin"
                        class="w-full rounded-xl border border-slate-300 px-4 py-2 outline-none focus:border-blue-500"
                    />

                    <button
                        type="button"
                        class="rounded-xl bg-blue-600 px-5 py-2 font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60"
                        :disabled="saving"
                        @click="createRole"
                    >
                        {{ saving ? 'Saving...' : 'Save' }}
                    </button>
                </div>

                <p v-if="error" class="mt-3 text-sm font-medium text-red-600">
                    {{ error }}
                </p>

                <p v-if="success" class="mt-3 text-sm font-medium text-emerald-600">
                    {{ success }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-slate-900">Role List</h2>

                    <button
                        type="button"
                        class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        @click="fetchRoles"
                    >
                        Refresh
                    </button>
                </div>

                <p v-if="loading" class="mt-4 text-sm text-slate-500">
                    Loading...
                </p>

                <div v-else class="mt-4 overflow-x-auto">
                    <table class="w-full border border-slate-200 text-sm">
                        <thead>
                            <tr class="bg-slate-50">
                                <th class="border border-slate-200 p-3 text-left">ID</th>
                                <th class="border border-slate-200 p-3 text-left">Name</th>
                                <th class="border border-slate-200 p-3 text-left">Guard</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="role in roles" :key="role.id">
                                <td class="border border-slate-200 p-3">
                                    {{ role.id }}
                                </td>

                                <td class="border border-slate-200 p-3 font-semibold capitalize">
                                    {{ role.name.replace('_', ' ') }}
                                </td>

                                <td class="border border-slate-200 p-3">
                                    {{ role.guard_name }}
                                </td>
                            </tr>

                            <tr v-if="roles.length === 0">
                                <td colspan="3" class="p-4 text-center text-slate-500">
                                    No roles found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </DashboardLayout>
</template>
=======
  <Head title="Role & Permission" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <!-- Top summary and actions for the role management page. -->
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <PageHero eyebrow="User Management" title="Role & Permission" description="Manage role defaults and set permission access across modules." />

        <div class="flex gap-3">
          <Link
            href="/dashboard/users/create"
            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
          >
            Create User
          </Link>
          <button
            type="button"
            :disabled="!selectedRole || form.processing"
            class="inline-flex items-center justify-center rounded-xl bg-blue-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70"
            @click="savePermissions"
          >
            {{ form.processing ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <!-- Quick stats used to understand the current access setup at a glance. -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Total Users</p>
          <p class="mt-3 text-3xl font-bold text-slate-900">{{ totalUsers }}</p>
          <p class="mt-1 text-xs text-emerald-600">Across all active roles</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Active Roles</p>
          <p class="mt-3 text-3xl font-bold text-slate-900">{{ activeRoles }}</p>
          <p class="mt-1 text-xs text-slate-500">Available for assignment</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Permissions</p>
          <p class="mt-3 text-3xl font-bold text-slate-900">{{ permissions.length }}</p>
          <p class="mt-1 text-xs text-blue-700">Registered access rules</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Restricted Modules</p>
          <p class="mt-3 text-3xl font-bold text-slate-900">{{ restrictedModules }}</p>
          <p class="mt-1 text-xs text-slate-500">For selected role</p>
        </div>
      </div>

      <div class="grid gap-6 2xl:grid-cols-[280px_1fr_340px]">
        <!-- Left panel: pick the role to edit. -->
        <aside class="self-start rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="flex items-center justify-between px-2 pb-3">
            <h2 class="text-base font-bold text-slate-900">Roles</h2>
            <span class="text-sm font-semibold text-slate-400">+</span>
          </div>

          <div class="space-y-2">
            <button
              v-for="role in roleSummaries"
              :key="role.id"
              type="button"
              class="flex w-full items-center justify-between rounded-xl px-4 py-3 text-left transition"
              :class="role.selected ? 'bg-blue-50 text-blue-900' : 'text-slate-700 hover:bg-slate-50'"
              @click="selectedRoleId = String(role.id)"
            >
              <span>
                <span
                  :class="[
                    'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
                    roleBadgeClass(role.name),
                  ]"
                >
                  {{ formatRole(role.name) }}
                </span>
                <span class="mt-2 block text-xs text-slate-500">{{ role.users_count }} users</span>
              </span>
              <span class="text-sm font-semibold text-slate-400">{{ role.permissions.length }}</span>
            </button>
          </div>
        </aside>

        <!-- Middle panel: permission matrix for the selected role. -->
        <form class="self-start overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" @submit.prevent="savePermissions">
          <div class="flex flex-col gap-4 border-b border-slate-200 p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <h2 class="text-base font-bold text-slate-900">Permissions Matrix</h2>
                <p class="mt-1 text-sm text-slate-600">
                  {{ selectedRole ? formatRole(selectedRole.name) : 'Select a role' }} role access by module.
                </p>
              </div>
              <div class="flex flex-wrap items-center gap-2">
                <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                  <input
                    type="checkbox"
                    class="h-4 w-4 rounded border-slate-300 text-blue-700 focus:ring-blue-200"
                    :checked="allPermissionsSelected"
                    @change="toggleAllPermissions"
                  >
                  <span>Select All</span>
                </label>
                <button
                  type="button"
                  class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600"
                  title="Clear all permissions"
                  aria-label="Clear all permissions"
                  @click="clearAllPermissions"
                >
                  <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 6h18" />
                    <path d="M8 6V4h8v2" />
                    <path d="M19 6l-1 14H6L5 6" />
                    <path d="M10 11v5" />
                    <path d="M14 11v5" />
                  </svg>
                </button>
              </div>
            </div>

            <label class="relative block">
              <span class="sr-only">Search permissions</span>
              <input
                v-model="permissionSearch"
                type="text"
                placeholder="Search modules or permissions..."
                class="w-full rounded-xl border border-slate-300 px-4 py-2.5 pr-10 text-sm text-slate-700 outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
              >
              <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7" />
                <path d="m20 20-3.5-3.5" />
              </svg>
            </label>
          </div>

          <p v-if="form.errors.permissions" class="m-5 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            {{ form.errors.permissions }}
          </p>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
              <thead class="bg-slate-50">
                <tr>
                  <th class="sticky left-0 z-10 bg-slate-50 px-5 py-4 text-left font-semibold text-slate-700">Module</th>
                  <th
                    v-for="action in actions"
                    :key="action"
                    class="px-4 py-4 text-center font-semibold capitalize text-slate-700"
                  >
                    {{ action }}
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 bg-white">
                <tr v-for="resource in paginatedResources" :key="resource" class="hover:bg-slate-50">
                  <td class="sticky left-0 z-10 bg-white px-5 py-4 font-semibold capitalize text-slate-800">
                    <div class="flex items-center gap-3">
                      <button
                        type="button"
                        class="flex h-5 w-5 items-center justify-center rounded border transition"
                        :class="isResourceFullyChecked(resource) || isResourcePartiallyChecked(resource)
                          ? 'border-emerald-600 bg-emerald-600 text-white'
                          : 'border-slate-300 bg-white text-transparent hover:border-emerald-500'"
                        :aria-pressed="isResourceFullyChecked(resource)"
                        :aria-label="`Select all ${resource.replaceAll('_', ' ')} permissions`"
                        @click="toggleResourcePermissions(resource)"
                      >
                        <span class="text-xs font-bold">{{ isResourcePartiallyChecked(resource) ? '-' : '✓' }}</span>
                      </button>
                      <span>{{ resource.replaceAll('_', ' ') }}</span>
                    </div>
                  </td>
                  <td
                    v-for="action in actions"
                    :key="`${resource}-${action}`"
                    class="px-4 py-4 text-center"
                  >
                    <button
                      v-if="permissionName(resource, action)"
                      type="button"
                      class="mx-auto flex h-5 w-5 items-center justify-center rounded border transition"
                      :class="isChecked(permissionName(resource, action))
                        ? 'border-blue-700 bg-blue-700 text-white'
                        : 'border-slate-300 bg-white text-transparent hover:border-blue-400'"
                      @click="togglePermission(permissionName(resource, action))"
                    >
                      <span class="text-xs font-bold">✓</span>
                    </button>
                    <span v-else class="text-slate-300">-</span>
                  </td>
                </tr>
              </tbody>
            </table>

            <div v-if="filteredResources.length === 0" class="px-5 py-10 text-center text-sm text-slate-500">
              No permissions found.
            </div>
          </div>

          <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-slate-500">
              Showing {{ matrixStart }} to {{ matrixEnd }} of {{ filteredResources.length }} modules
            </div>

            <Pagination
              :current-page="matrixPage"
              :last-page="matrixLastPage"
              :disabled="form.processing"
              @page-change="changeMatrixPage"
            />
          </div>
        </form>

        <!-- Right panel: assign users to the selected role. -->
        <form class="self-start rounded-2xl border border-slate-200 bg-white shadow-sm" @submit.prevent="saveAssignedUsers">
          <div class="border-b border-slate-200 p-5">
            <h2 class="text-base font-bold text-slate-900">Assign Users to Role</h2>
            <p class="mt-1 text-sm text-slate-600">
              {{ selectedRole ? formatRole(selectedRole.name) : 'Select a role' }}
            </p>

            <label class="mt-4 block">
              <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Search</span>
              <input
                v-model="userSearch"
                type="text"
                placeholder="Search users..."
                class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
              >
            </label>
          </div>

          <p v-if="assignUsersForm.errors.users" class="mx-5 mt-4 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            {{ assignUsersForm.errors.users }}
          </p>

          <div class="max-h-[560px] divide-y divide-slate-100 overflow-y-auto">
            <label
              v-for="user in filteredUsers"
              :key="user.id"
              class="flex cursor-pointer items-center gap-3 px-5 py-4 transition hover:bg-slate-50"
            >
              <input
                type="checkbox"
                class="h-4 w-4 rounded border-slate-300 text-blue-700 focus:ring-blue-200"
                :checked="isUserSelected(user.id)"
                @change="toggleUser(user.id)"
              >

              <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">
                {{ initials(user.name) }}
              </span>

              <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-bold text-slate-900">{{ user.name }}</span>
                <span class="block truncate text-xs text-slate-500">{{ user.email }}</span>
              </span>

              <span class="shrink-0 text-xs font-semibold text-blue-700">
                {{ formatRole(user.roles?.[0] ?? 'No Role') }}
              </span>
            </label>

            <div v-if="filteredUsers.length === 0" class="px-5 py-10 text-center text-sm text-slate-500">
              No users found.
            </div>
          </div>

          <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-5 py-4">
            <span class="text-sm text-slate-500">Selected {{ selectedUserCount }} users</span>
            <button
              type="submit"
              :disabled="!selectedRole || assignUsersForm.processing"
              class="rounded-xl bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70"
            >
              {{ assignUsersForm.processing ? 'Assigning...' : 'Assign User' }}
            </button>
          </div>
        </form>
      </div>
    </section>
  </DashboardLayout>
</template>
>>>>>>> origin/dev
