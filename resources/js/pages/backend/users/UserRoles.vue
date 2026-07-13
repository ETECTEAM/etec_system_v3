<script setup>
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import AssignUsersPanel from './components/AssignUsersPanel.vue'
import PermissionsMatrixPanel from './components/PermissionsMatrixPanel.vue'
import RolesPanel from './components/RolesPanel.vue'
import { useUserRoles } from './composables/useUserRoles'

// UI state only: role/search selection, matrix pagination, and create-role modal visibility.
const selectedRoleId = ref('')
const userSearch = ref('')
const permissionSearch = ref('')
const matrixPage = ref(1)
const showCreateModal = ref(false)
const showOnlyChecked = ref(false)

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
  checkedResourcesCount,
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
  saveAssignedUsers,
  changeMatrixPage,
} = useUserRoles({ selectedRoleId, userSearch, permissionSearch, matrixPage, showCreateModal, showOnlyChecked })
</script>

<template>
  <Head title="Role & Permission" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />

      <!-- Top summary: title on the left, compact quick stats on the right. -->
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <PageHero eyebrow="User Management" title="Role & Permission" description="Manage role defaults and set permission access across modules." />

        <div class="grid grid-cols-4 gap-3">
          <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-[10px] font-semibold uppercase tracking-[0.15em] text-slate-500 dark:text-gray-400">Total Users</p>
            <p class="mt-1 text-xl font-bold text-slate-900 dark:text-gray-100">{{ totalUsers }}</p>
            <p class="mt-0.5 text-[11px] text-emerald-600 dark:text-emerald-400">Across all active roles</p>
          </div>
          <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-[10px] font-semibold uppercase tracking-[0.15em] text-slate-500 dark:text-gray-400">Active Roles</p>
            <p class="mt-1 text-xl font-bold text-slate-900 dark:text-gray-100">{{ activeRoles }}</p>
            <p class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400">Available for assignment</p>
          </div>
          <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-[10px] font-semibold uppercase tracking-[0.15em] text-slate-500 dark:text-gray-400">Permissions</p>
            <p class="mt-1 text-xl font-bold text-slate-900 dark:text-gray-100">{{ permissions.length }}</p>
            <p class="mt-0.5 text-[11px] text-blue-700 dark:text-blue-400">Registered access rules</p>
          </div>
          <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-[10px] font-semibold uppercase tracking-[0.15em] text-slate-500 dark:text-gray-400">Restricted Modules</p>
            <p class="mt-1 text-xl font-bold text-slate-900 dark:text-gray-100">{{ restrictedModules }}</p>
            <p class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400">For selected role</p>
          </div>
        </div>
      </div>

      <div class="grid gap-6 2xl:grid-cols-[280px_1fr_340px]">
        <RolesPanel
          v-model:selected-role-id="selectedRoleId"
          :role-summaries="roleSummaries"
          :delete-disabled-reason="deleteDisabledReason"
          :open-create-modal="openCreateModal"
          :delete-role="deleteRole"
        />

        <PermissionsMatrixPanel
          v-model:permission-search="permissionSearch"
          v-model:show-only-checked="showOnlyChecked"
          :selected-role="selectedRole"
          :form="form"
          :actions="actions"
          :paginated-resources="paginatedResources"
          :filtered-resources="filteredResources"
          :checked-resources-count="checkedResourcesCount"
          :matrix-page="matrixPage"
          :matrix-last-page="matrixLastPage"
          :matrix-start="matrixStart"
          :matrix-end="matrixEnd"
          :all-permissions-selected="allPermissionsSelected"
          :permission-name="permissionName"
          :is-checked="isChecked"
          :is-resource-fully-checked="isResourceFullyChecked"
          :is-resource-partially-checked="isResourcePartiallyChecked"
          :toggle-resource-permissions="toggleResourcePermissions"
          :toggle-permission="togglePermission"
          :toggle-all-permissions="toggleAllPermissions"
          :clear-all-permissions="clearAllPermissions"
          :save-permissions="savePermissions"
          :change-matrix-page="changeMatrixPage"
        />

        <AssignUsersPanel
          v-model:user-search="userSearch"
          :selected-role="selectedRole"
          :assign-users-form="assignUsersForm"
          :filtered-users="filteredUsers"
          :selected-user-count="selectedUserCount"
          :is-user-selected="isUserSelected"
          :toggle-user="toggleUser"
          :save-assigned-users="saveAssignedUsers"
        />
      </div>
    </section>

    <!-- ── Create Role Modal ── -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="create-role-title" tabindex="-1" @keydown.esc.prevent="closeCreateModal">
          <!-- Backdrop -->
          <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="closeCreateModal" />

          <!-- Panel: max-h + overflow-y-auto prevents bottom clipping on short viewports -->
          <div class="modal-panel relative z-10 flex w-full max-w-md flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-gray-800 dark:bg-gray-900" style="max-height: calc(100vh - 2rem);">
            <!-- Header -->
            <div class="flex shrink-0 items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-gray-800">
              <h2 id="create-role-title" class="text-base font-bold text-slate-900 dark:text-gray-100">Create New Role</h2>
              <button type="button" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-300 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300" aria-label="Close modal" @click="closeCreateModal">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M18 6 6 18M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Body (scrollable if content overflows) -->
            <form id="create-role-form" class="flex min-h-0 flex-1 flex-col" @submit.prevent="submitCreateRole">
              <div class="flex-1 overflow-y-auto px-6 py-5">
                <!-- Role Name: server converts spaces to underscores, see helper text below the field -->
                <div>
                  <label for="create-role-name" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">Role Name <span class="text-red-500 dark:text-red-400">*</span></label>
                  <input id="create-role-name" v-model="createRoleForm.name" type="text" placeholder="e.g. editor" autocomplete="off" :disabled="createRoleForm.processing" class="w-full rounded-xl border px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:ring-2 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-gray-800 dark:text-gray-200" :class="createRoleForm.errors.name ? 'border-red-400 focus:border-red-400 focus:ring-red-100 dark:border-red-500/60 dark:focus:border-red-500 dark:focus:ring-red-500/20' : 'border-slate-300 focus:border-blue-600 focus:ring-blue-100 dark:border-gray-600 dark:focus:border-blue-500 dark:focus:ring-blue-500/20'">
                  <p v-if="createRoleForm.errors.name" class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">{{ createRoleForm.errors.name }}</p>
                  <p class="mt-1.5 text-xs text-slate-400 dark:text-gray-500">Spaces are converted to underscores automatically, e.g. <code class="rounded bg-slate-100 px-1 py-0.5 font-mono dark:bg-gray-800">Super Editor</code> → <code class="rounded bg-slate-100 px-1 py-0.5 font-mono dark:bg-gray-800">super_editor</code></p>
                </div>
              </div>

              <!-- Footer: shrink-0 keeps it always visible -->
              <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 dark:border-gray-800 dark:bg-gray-800/40">
                <button type="button" :disabled="createRoleForm.processing" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800" @click="closeCreateModal">Cancel</button>
                <button type="submit" form="create-role-form" :disabled="createRoleForm.processing || !createRoleForm.name.trim()" class="inline-flex items-center gap-2 rounded-xl bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70">
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
