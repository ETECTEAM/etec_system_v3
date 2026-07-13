<script setup>
import { Head } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { Search } from '@lucide/vue'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import { Pagination } from '../../../components/ui/pagination'
import { formatRole, roleBadgeClass } from '../../../lib/roleBadge'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import { useUserPermissions } from './composables/useUserPermissions'

// Debounces a fast-changing ref (raw keystrokes) into a settled value that
// computed filters can key off, so large lists don't re-filter on every key.
function useDebouncedValue(sourceRef, delay = 250) {
  const debounced = ref(sourceRef.value)
  let timer = null

  watch(sourceRef, (value) => {
    if (timer) {
      clearTimeout(timer)
    }

    timer = window.setTimeout(() => {
      debounced.value = value
    }, delay)
  })

  return debounced
}

// UI state only (selection, pagination, search text) - domain data and save logic live in useUserPermissions.
const selectedUserId = ref('')
const matrixPage = ref(1)
const showOnlyChecked = ref(false)

const userSearchInput = ref('')
const userSearchQuery = useDebouncedValue(userSearchInput)

const moduleSearchInput = ref('')
const moduleSearchQuery = useDebouncedValue(moduleSearchInput)

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Users', href: '/dashboard/users' },
  { label: 'Permissions', current: true },
]

const {
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
} = useUserPermissions({ selectedUserId, userSearchQuery, moduleSearchQuery, matrixPage, showOnlyChecked })

function changeMatrixPage(page) {
  matrixPage.value = page
}
</script>

<template>
  <Head title="User & Permission" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />

      <!-- Top summary: title on the left, compact quick stats on the right. -->
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <PageHero eyebrow="User Management" title="User & Permission" description="Give selected users extra permissions without changing their role." />

        <!-- Summary tiles: derived from users/permissions props and the current selection, not fetched separately -->
        <div class="grid grid-cols-4 gap-3">
          <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-[10px] font-semibold uppercase tracking-[0.15em] text-slate-500 dark:text-gray-400">Total Users</p>
            <p class="mt-1 text-xl font-bold text-slate-900 dark:text-gray-100">{{ totalUsers }}</p>
            <p class="mt-0.5 text-[11px] text-emerald-600 dark:text-emerald-400">Available for assignment</p>
          </div>
          <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-[10px] font-semibold uppercase tracking-[0.15em] text-slate-500 dark:text-gray-400">Direct Users</p>
            <p class="mt-1 text-xl font-bold text-slate-900 dark:text-gray-100">{{ directPermissionUsers }}</p>
            <p class="mt-0.5 text-[11px] text-blue-700 dark:text-blue-400">Have custom permissions</p>
          </div>
          <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-[10px] font-semibold uppercase tracking-[0.15em] text-slate-500 dark:text-gray-400">Direct Selected</p>
            <p class="mt-1 text-xl font-bold text-slate-900 dark:text-gray-100">{{ selectedDirectCount }}</p>
            <p class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400">User-specific access</p>
          </div>
          <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-[10px] font-semibold uppercase tracking-[0.15em] text-slate-500 dark:text-gray-400">Total Selected</p>
            <p class="mt-1 text-xl font-bold text-slate-900 dark:text-gray-100">{{ selectedTotalCount }}</p>
            <p class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400">Role plus direct access</p>
          </div>
        </div>
      </div>

      <div class="grid gap-6 xl:grid-cols-[320px_1fr]">
        <!-- User list: click a row to select it, which loads its permissions into the matrix via syncFormFromSelectedUser -->
        <aside class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <div class="flex items-center justify-between px-2 pb-3">
            <h2 class="text-base font-bold text-slate-900 dark:text-gray-100">Users</h2>
            <span class="text-sm font-semibold text-slate-400 dark:text-gray-500">{{ users.length }}</span>
          </div>

          <div class="px-2 pb-3">
            <div class="relative">
              <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500" />
              <input v-model="userSearchInput" type="text" placeholder="Search..." class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20">
            </div>
          </div>

          <div class="max-h-[640px] space-y-2 overflow-y-auto pr-1">
            <p v-if="filteredUserSummaries.length === 0" class="px-2 py-6 text-center text-sm text-slate-500 dark:text-gray-400">No users found.</p>
            <button v-for="user in filteredUserSummaries" :key="user.id" type="button" class="flex w-full items-start justify-between gap-3 rounded-xl px-4 py-3 text-left transition" :class="user.selected ? 'bg-blue-50 text-blue-900 dark:bg-blue-500/10 dark:text-blue-400' : 'text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-800'" @click="selectedUserId = String(user.id)">
              <span class="min-w-0">
                <span class="block truncate text-sm font-bold">{{ user.name }}</span>
                <span class="mt-1 block truncate text-xs text-slate-500 dark:text-gray-400">{{ user.email }}</span>
                <span class="mt-2 flex flex-wrap gap-1">
                  <span v-for="role in user.roles" :key="role" :class="['inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold', roleBadgeClass(role)]">{{ formatRole(role) }}</span>
                </span>
              </span>
              <span class="shrink-0 rounded-full bg-white px-2 py-1 text-xs font-bold text-blue-700 dark:bg-gray-800 dark:text-blue-400">{{ user.total_permissions_count }}</span>
            </button>
          </div>
        </aside>

        <!-- Permission matrix: form submit is a fallback, the Save Changes button above triggers the same savePermissions -->
        <form class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900" @submit.prevent="savePermissions">
          <div class="flex flex-col gap-3 border-b border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
            <div>
              <h2 class="text-base font-bold text-slate-900 dark:text-gray-100">User Permissions Matrix</h2>
              <p class="mt-1 text-sm text-slate-600 dark:text-gray-400">Blue checks are direct permissions. Pale checks already come from the user's role.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
              <div class="flex gap-3 text-sm font-semibold">
                <span class="text-slate-500 dark:text-gray-400">Role {{ selectedRoleCount }}</span>
                <span class="text-blue-700 dark:text-blue-400">Direct {{ selectedDirectCount }}</span>
              </div>
              <!-- :checked/@change instead of v-model: allPermissionsSelected is derived, not a standalone boolean to bind to -->
              <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
                <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-700 accent-blue-700 focus:ring-blue-200 dark:border-gray-600 dark:bg-gray-800 dark:accent-blue-500 dark:focus:ring-blue-500/20" :checked="allPermissionsSelected" @change="toggleAllPermissions">
                <span>Select All</span>
              </label>
              <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:hover:border-red-500/30 dark:hover:bg-red-500/10 dark:hover:text-red-400" title="Clear all direct permissions" aria-label="Clear all direct permissions" @click="clearAllPermissions">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M3 6h18" />
                  <path d="M8 6V4h8v2" />
                  <path d="M19 6l-1 14H6L5 6" />
                  <path d="M10 11v5" />
                  <path d="M14 11v5" />
                </svg>
              </button>
              <button type="button" :disabled="!selectedUser || form.processing" class="relative inline-flex items-center justify-center rounded-lg bg-blue-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70" @click="savePermissions">
                <!-- Nudges the user to save once they've checked a box: a quiet ping until they click, not a blocking modal. -->
                <span v-if="hasUnsavedPermissionChanges && !form.processing" class="absolute -right-1 -top-1 flex h-3 w-3" aria-hidden="true">
                  <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75" />
                  <span class="relative inline-flex h-3 w-3 rounded-full bg-amber-500" />
                </span>
                {{ form.processing ? 'Saving...' : 'Save Changes' }}
              </button>
            </div>
          </div>

          <p v-if="form.errors.permissions" class="m-5 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400">{{ form.errors.permissions }}</p>

          <!-- Module search filters the table rows client-side via filteredResources, no request is made -->
          <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-3 sm:flex-row sm:items-center sm:justify-end dark:border-gray-800">
            <!-- Checked modules sort to the top of the matrix automatically; this toggle hides the rest so they're findable without paging through. -->
            <label class="inline-flex w-fit shrink-0 items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-800/80">
              <input v-model="showOnlyChecked" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-700 accent-blue-700 focus:ring-blue-200 dark:border-gray-600 dark:bg-gray-800 dark:accent-blue-500 dark:focus:ring-blue-500/20">
              <span>Show only checked ({{ checkedResourcesCount }})</span>
            </label>

            <div class="relative sm:max-w-xs">
              <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500" />
              <input v-model="moduleSearchInput" type="text" placeholder="Search..." class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20">
            </div>
          </div>

          <!-- Cell colors: solid blue = direct permission, pale blue = inherited from role, "-" = not a real permission for this resource -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-gray-800">
              <thead class="bg-slate-50 dark:bg-gray-800">
                <tr>
                  <th class="sticky left-0 z-10 whitespace-nowrap bg-slate-50 px-5 py-4 text-left font-semibold text-slate-700 min-w-[240px] w-[260px] dark:bg-gray-800 dark:text-gray-300">Module</th>
                  <th v-for="action in actions" :key="action" class="w-24 px-4 py-4 text-center font-semibold capitalize text-slate-700 dark:text-gray-300">{{ action }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 bg-white dark:divide-gray-800 dark:bg-gray-900">
                <tr v-for="resource in paginatedResources" :key="resource" class="hover:bg-slate-50 dark:hover:bg-gray-800/60">
                  <td class="sticky left-0 z-10 whitespace-nowrap bg-white px-5 py-4 font-semibold capitalize text-slate-800 min-w-[240px] w-[260px] dark:bg-gray-900 dark:text-gray-200">{{ resource.replaceAll('_', ' ') }}</td>
                  <td v-for="action in actions" :key="`${resource}-${action}`" class="w-24 px-4 py-4 text-center">
                    <button v-if="permissionName(resource, action)" type="button" class="mx-auto flex h-5 w-5 items-center justify-center rounded border transition" :class="isDirectPermission(permissionName(resource, action)) ? 'border-blue-700 bg-blue-700 text-white dark:border-blue-500 dark:bg-blue-500' : isRolePermission(permissionName(resource, action)) ? 'border-blue-200 bg-blue-50 text-blue-600 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-400' : 'border-slate-300 bg-white text-transparent hover:border-blue-400 dark:border-gray-600 dark:bg-gray-800 dark:hover:border-blue-500'" @click="togglePermission(permissionName(resource, action))"><span class="text-xs font-bold">✓</span></button>
                    <span v-else class="text-slate-300 dark:text-gray-600">-</span>
                  </td>
                </tr>

                <tr v-if="paginatedResources.length === 0">
                  <td :colspan="actions.length + 1" class="px-5 py-10 text-center text-slate-500 dark:text-gray-400">No modules found.</td>
                </tr>
              </tbody>
            </table>
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
      </div>
    </section>
  </DashboardLayout>
</template>
