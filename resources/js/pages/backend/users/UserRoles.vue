<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import { Pagination } from '../../../components/ui/pagination'
import { formatRole, roleBadgeClass } from '../../../lib/roleBadge'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import { useUserRoles } from './composables/useUserRoles'

// UI state only: role/search selection, matrix pagination, and create-role modal visibility.
const selectedRoleId = ref('')
const userSearch = ref('')
const permissionSearch = ref('')
const matrixPage = ref(1)
const showCreateModal = ref(false)

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Users', href: '/dashboard/users' },
  { label: 'Roles & Permission', current: true },
]

const {
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
  matrixLastPage,
  paginatedResources,
  matrixStart,
  matrixEnd,
  roleSummaries,
  filteredUsers,
  selectedUserCount,
  allPermissionsSelected,
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
  initials,
  saveAssignedUsers,
  changeMatrixPage,
} = useUserRoles({ selectedRoleId, userSearch, permissionSearch, matrixPage, showCreateModal })
</script>

<template>
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
            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
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
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-gray-400">Total Users</p>
          <p class="mt-3 text-3xl font-bold text-slate-900 dark:text-gray-100">{{ totalUsers }}</p>
          <p class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">Across all active roles</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-gray-400">Active Roles</p>
          <p class="mt-3 text-3xl font-bold text-slate-900 dark:text-gray-100">{{ activeRoles }}</p>
          <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">Available for assignment</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-gray-400">Permissions</p>
          <p class="mt-3 text-3xl font-bold text-slate-900 dark:text-gray-100">{{ permissions.length }}</p>
          <p class="mt-1 text-xs text-blue-700 dark:text-blue-400">Registered access rules</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-gray-400">Restricted Modules</p>
          <p class="mt-3 text-3xl font-bold text-slate-900 dark:text-gray-100">{{ restrictedModules }}</p>
          <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">For selected role</p>
        </div>
      </div>

      <div class="grid gap-6 2xl:grid-cols-[280px_1fr_340px]">
        <!-- Left panel: pick the role to edit. -->
        <aside class="self-start rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <div class="flex items-center justify-between pb-3">
            <h2 class="text-base font-bold text-slate-900 dark:text-gray-100">Roles</h2>
            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg bg-blue-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-300"
              @click="openCreateModal"
            >
              <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 5v14M5 12h14" />
              </svg>
              Create Role
            </button>
          </div>

          <div class="space-y-2">
            <div
              v-for="role in roleSummaries"
              :key="role.id"
              class="group flex w-full items-center gap-2 rounded-xl px-4 py-3 transition"
              :class="role.selected ? 'bg-blue-50 text-blue-900 dark:bg-blue-500/10 dark:text-blue-400' : 'text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-800'"
            >
              <button
                type="button"
                class="flex flex-1 items-center justify-between text-left"
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
                  <span class="mt-2 block text-xs text-slate-500 dark:text-gray-400">{{ role.users_count }} users</span>
                </span>
                <span class="text-sm font-semibold text-slate-400 dark:text-gray-500">{{ role.permissions.length }}</span>
              </button>
              <button
                type="button"
                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg transition"
                :class="role.deletable ? 'text-slate-400 hover:bg-red-50 hover:text-red-600 dark:text-gray-500 dark:hover:bg-red-500/10 dark:hover:text-red-400' : 'cursor-not-allowed text-slate-200 dark:text-gray-700'"
                :disabled="!role.deletable"
                :title="deleteDisabledReason(role)"
                :aria-label="`Delete ${formatRole(role.name)} role`"
                @click="deleteRole(role)"
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
        </aside>

        <!-- Middle panel: permission matrix for the selected role. -->
        <form class="self-start overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900" @submit.prevent="savePermissions">
          <div class="flex flex-col gap-4 border-b border-slate-200 p-5 dark:border-gray-800">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-gray-100">Permissions Matrix</h2>
                <p class="mt-1 text-sm text-slate-600 dark:text-gray-400">
                  {{ selectedRole ? formatRole(selectedRole.name) : 'Select a role' }} role access by module.
                </p>
              </div>
              <div class="flex flex-wrap items-center gap-2">
                <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
                  <input
                    type="checkbox"
                    class="h-4 w-4 rounded border-slate-300 text-blue-700 accent-blue-700 focus:ring-blue-200 dark:border-gray-600 dark:bg-gray-800 dark:accent-blue-500 dark:focus:ring-blue-500/20"
                    :checked="allPermissionsSelected"
                    @change="toggleAllPermissions"
                  >
                  <span>Select All</span>
                </label>
                <button
                  type="button"
                  class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:hover:border-red-500/30 dark:hover:bg-red-500/10 dark:hover:text-red-400"
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
                class="w-full rounded-xl border border-slate-300 px-4 py-2.5 pr-10 text-sm text-slate-700 outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
              >
              <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7" />
                <path d="m20 20-3.5-3.5" />
              </svg>
            </label>
          </div>

          <p v-if="form.errors.permissions" class="m-5 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400">
            {{ form.errors.permissions }}
          </p>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-gray-800">
              <thead class="bg-slate-50 dark:bg-gray-800">
                <tr>
                  <th class="sticky left-0 z-10 whitespace-nowrap bg-slate-50 px-5 py-4 text-left font-semibold text-slate-700 min-w-[240px] w-[260px] dark:bg-gray-800 dark:text-gray-300">Module</th>
                  <th
                    v-for="action in actions"
                    :key="action"
                    class="w-24 px-4 py-4 text-center font-semibold capitalize text-slate-700 dark:text-gray-300"
                  >
                    {{ action }}
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 bg-white dark:divide-gray-800 dark:bg-gray-900">
                <tr v-for="resource in paginatedResources" :key="resource" class="hover:bg-slate-50 dark:hover:bg-gray-800/60">
                  <td class="sticky left-0 z-10 whitespace-nowrap bg-white px-5 py-4 font-semibold capitalize text-slate-800 min-w-[240px] w-[260px] dark:bg-gray-900 dark:text-gray-200">
                    <div class="flex items-center gap-3">
                      <button
                        type="button"
                        class="flex h-5 w-5 items-center justify-center rounded border transition"
                        :class="isResourceFullyChecked(resource) || isResourcePartiallyChecked(resource)
                          ? 'border-emerald-600 bg-emerald-600 text-white dark:border-emerald-500 dark:bg-emerald-500'
                          : 'border-slate-300 bg-white text-transparent hover:border-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:hover:border-emerald-500'"
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
                    class="w-24 px-4 py-4 text-center"
                  >
                    <button
                      v-if="permissionName(resource, action)"
                      type="button"
                      class="mx-auto flex h-5 w-5 items-center justify-center rounded border transition"
                      :class="isChecked(permissionName(resource, action))
                        ? 'border-blue-700 bg-blue-700 text-white dark:border-blue-500 dark:bg-blue-500'
                        : 'border-slate-300 bg-white text-transparent hover:border-blue-400 dark:border-gray-600 dark:bg-gray-800 dark:hover:border-blue-500'"
                      @click="togglePermission(permissionName(resource, action))"
                    >
                      <span class="text-xs font-bold">✓</span>
                    </button>
                    <span v-else class="text-slate-300 dark:text-gray-600">-</span>
                  </td>
                </tr>
              </tbody>
            </table>

            <div v-if="filteredResources.length === 0" class="px-5 py-10 text-center text-sm text-slate-500 dark:text-gray-400">
              No permissions found.
            </div>
          </div>

          <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/40">
            <div class="text-sm text-slate-500 dark:text-gray-400">
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
        <form class="self-start rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900" @submit.prevent="saveAssignedUsers">
          <div class="border-b border-slate-200 p-5 dark:border-gray-800">
            <h2 class="text-base font-bold text-slate-900 dark:text-gray-100">Assign Users to Role</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-gray-400">
              {{ selectedRole ? formatRole(selectedRole.name) : 'Select a role' }}
            </p>

            <label class="mt-4 block">
              <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400 dark:text-gray-500">Search</span>
              <input
                v-model="userSearch"
                type="text"
                placeholder="Search users..."
                class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
              >
            </label>
          </div>

          <p v-if="assignUsersForm.errors.users" class="mx-5 mt-4 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400">
            {{ assignUsersForm.errors.users }}
          </p>

          <div class="max-h-[560px] divide-y divide-slate-100 overflow-y-auto dark:divide-gray-800">
            <label
              v-for="user in filteredUsers"
              :key="user.id"
              class="flex cursor-pointer items-center gap-3 px-5 py-4 transition hover:bg-slate-50 dark:hover:bg-gray-800/60"
            >
              <input
                type="checkbox"
                class="h-4 w-4 rounded border-slate-300 text-blue-700 accent-blue-700 focus:ring-blue-200 dark:border-gray-600 dark:bg-gray-800 dark:accent-blue-500 dark:focus:ring-blue-500/20"
                :checked="isUserSelected(user.id)"
                @change="toggleUser(user.id)"
              >

              <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-500/10 dark:text-blue-400">
                {{ initials(user.name) }}
              </span>

              <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-bold text-slate-900 dark:text-gray-100">{{ user.name }}</span>
                <span class="block truncate text-xs text-slate-500 dark:text-gray-400">{{ user.email }}</span>
              </span>

                  <div class="shrink-0 text-right">
                    <span class="block text-xs font-semibold text-blue-700 dark:text-blue-400">{{ formatRole(user.roles?.[0] ?? 'No Role') }}</span>
                    <span class="block text-[11px] text-slate-400 dark:text-gray-500">{{ user.total_permissions_count }} permissions</span>
                  </div>
            </label>

            <div v-if="filteredUsers.length === 0" class="px-5 py-10 text-center text-sm text-slate-500 dark:text-gray-400">
              No users found.
            </div>
          </div>

          <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-5 py-4 dark:border-gray-800 dark:bg-gray-800/40">
            <span class="text-sm text-slate-500 dark:text-gray-400">Selected {{ selectedUserCount }} users</span>
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

    <!-- ── Create Role Modal ── -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div
          v-if="showCreateModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4"
          role="dialog"
          aria-modal="true"
          aria-labelledby="create-role-title"
          tabindex="-1"
          @keydown.esc.prevent="closeCreateModal"
        >
          <!-- Backdrop -->
          <div
            class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"
            @click="closeCreateModal"
          />

          <!-- Panel: max-h + overflow-y-auto prevents bottom clipping on short viewports -->
          <div class="modal-panel relative z-10 flex w-full max-w-md flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-gray-800 dark:bg-gray-900" style="max-height: calc(100vh - 2rem);">
            <!-- Header -->
            <div class="flex shrink-0 items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-gray-800">
              <h2 id="create-role-title" class="text-base font-bold text-slate-900 dark:text-gray-100">Create New Role</h2>
              <button
                type="button"
                class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-300 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                aria-label="Close modal"
                @click="closeCreateModal"
              >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M18 6 6 18M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Body (scrollable if content overflows) -->
            <form
              id="create-role-form"
              class="flex min-h-0 flex-1 flex-col"
              @submit.prevent="submitCreateRole"
            >
              <div class="flex-1 overflow-y-auto px-6 py-5">
                <!-- Role Name -->
                <div>
                  <label for="create-role-name" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">
                    Role Name <span class="text-red-500 dark:text-red-400">*</span>
                  </label>
                  <input
                    id="create-role-name"
                    v-model="createRoleForm.name"
                    type="text"
                    placeholder="e.g. editor"
                    autocomplete="off"
                    :disabled="createRoleForm.processing"
                    class="w-full rounded-xl border px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:ring-2 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-gray-800 dark:text-gray-200"
                    :class="createRoleForm.errors.name
                      ? 'border-red-400 focus:border-red-400 focus:ring-red-100 dark:border-red-500/60 dark:focus:border-red-500 dark:focus:ring-red-500/20'
                      : 'border-slate-300 focus:border-blue-600 focus:ring-blue-100 dark:border-gray-600 dark:focus:border-blue-500 dark:focus:ring-blue-500/20'"
                  >
                  <p v-if="createRoleForm.errors.name" class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">
                    {{ createRoleForm.errors.name }}
                  </p>
                  <p class="mt-1.5 text-xs text-slate-400 dark:text-gray-500">
                    Spaces are converted to underscores automatically, e.g. <code class="rounded bg-slate-100 px-1 py-0.5 font-mono dark:bg-gray-800">Super Editor</code> → <code class="rounded bg-slate-100 px-1 py-0.5 font-mono dark:bg-gray-800">super_editor</code>
                  </p>
                </div>
              </div>

              <!-- Footer: shrink-0 keeps it always visible -->
              <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 dark:border-gray-800 dark:bg-gray-800/40">
                <button
                  type="button"
                  :disabled="createRoleForm.processing"
                  class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
                  @click="closeCreateModal"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  form="create-role-form"
                  :disabled="createRoleForm.processing || !createRoleForm.name.trim()"
                  class="inline-flex items-center gap-2 rounded-xl bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70"
                >
                  <svg v-if="createRoleForm.processing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  {{ createRoleForm.processing ? 'Creating...' : 'Create Role' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>
  </DashboardLayout>
</template>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.18s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
/* Animate the panel itself, not generic .relative elements */
.modal-fade-enter-active .modal-panel,
.modal-fade-leave-active .modal-panel {
  transition: transform 0.18s ease, opacity 0.18s ease;
}
.modal-fade-enter-from .modal-panel,
.modal-fade-leave-to .modal-panel {
  transform: scale(0.96) translateY(-8px);
  opacity: 0;
}
</style>
