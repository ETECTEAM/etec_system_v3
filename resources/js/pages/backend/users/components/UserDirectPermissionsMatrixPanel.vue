<script setup>
import { Search } from '@lucide/vue'
import { Pagination } from '../../../../components/ui/pagination'

defineProps({
  selectedUser: { type: Object, default: null },
  form: { type: Object, required: true },
  actions: { type: Array, required: true },
  paginatedResources: { type: Array, required: true },
  filteredResources: { type: Array, required: true },
  checkedResourcesCount: { type: Number, required: true },
  matrixPage: { type: Number, required: true },
  matrixLastPage: { type: Number, required: true },
  matrixStart: { type: Number, required: true },
  matrixEnd: { type: Number, required: true },
  selectedRoleCount: { type: Number, required: true },
  selectedDirectCount: { type: Number, required: true },
  allPermissionsSelected: { type: Boolean, required: true },
  hasUnsavedPermissionChanges: { type: Boolean, required: true },
  permissionName: { type: Function, required: true },
  isDirectPermission: { type: Function, required: true },
  isRolePermission: { type: Function, required: true },
  togglePermission: { type: Function, required: true },
  toggleAllPermissions: { type: Function, required: true },
  clearAllPermissions: { type: Function, required: true },
  savePermissions: { type: Function, required: true },
  changeMatrixPage: { type: Function, required: true },
})

const moduleSearch = defineModel('moduleSearch', { type: String, required: true })
const showOnlyChecked = defineModel('showOnlyChecked', { type: Boolean, required: true })
</script>

<template>
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
        <input v-model="moduleSearch" type="text" placeholder="Search..." class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20">
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
</template>
