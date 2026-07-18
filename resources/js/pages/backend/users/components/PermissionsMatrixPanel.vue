<script setup>
import { Pagination } from '../../../../components/ui/pagination'
import { formatRole } from '../../../../lib/roleBadge'

defineProps({
  selectedRole: { type: Object, default: null },
  form: { type: Object, required: true },
  actions: { type: Array, required: true },
  paginatedResources: { type: Array, required: true },
  filteredResources: { type: Array, required: true },
  checkedResourcesCount: { type: Number, required: true },
  matrixPage: { type: Number, required: true },
  matrixLastPage: { type: Number, required: true },
  matrixStart: { type: Number, required: true },
  matrixEnd: { type: Number, required: true },
  allPermissionsSelected: { type: Boolean, required: true },
  hasUnsavedPermissionChanges: { type: Boolean, required: true },
  permissionName: { type: Function, required: true },
  isChecked: { type: Function, required: true },
  isResourceFullyChecked: { type: Function, required: true },
  isResourcePartiallyChecked: { type: Function, required: true },
  toggleResourcePermissions: { type: Function, required: true },
  togglePermission: { type: Function, required: true },
  toggleAllPermissions: { type: Function, required: true },
  clearAllPermissions: { type: Function, required: true },
  savePermissions: { type: Function, required: true },
  changeMatrixPage: { type: Function, required: true },
})

const permissionSearch = defineModel('permissionSearch', { type: String, required: true })
const showOnlyChecked = defineModel('showOnlyChecked', { type: Boolean, required: true })
</script>

<template>
  <form class="self-start overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900" @submit.prevent="savePermissions">
    <div class="flex flex-col gap-4 border-b border-slate-200 p-5 dark:border-gray-800">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h2 class="text-base font-bold text-slate-900 dark:text-gray-100">Permissions Matrix</h2>
          <p class="mt-1 text-sm text-slate-600 dark:text-gray-400">{{ selectedRole ? formatRole(selectedRole.name) : 'Select a role' }} role access by module.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <!-- :checked/@change instead of v-model: allPermissionsSelected is derived, not a standalone boolean to bind to -->
          <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
            <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-700 accent-blue-700 focus:ring-blue-200 dark:border-gray-600 dark:bg-gray-800 dark:accent-blue-500 dark:focus:ring-blue-500/20" :checked="allPermissionsSelected" @change="toggleAllPermissions">
            <span>Select All</span>
          </label>
          <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:hover:border-red-500/30 dark:hover:bg-red-500/10 dark:hover:text-red-400" title="Clear all permissions" aria-label="Clear all permissions" @click="clearAllPermissions">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M3 6h18" />
              <path d="M8 6V4h8v2" />
              <path d="M19 6l-1 14H6L5 6" />
              <path d="M10 11v5" />
              <path d="M14 11v5" />
            </svg>
          </button>
          <button type="button" :disabled="!selectedRole || form.processing" class="relative inline-flex items-center justify-center rounded-lg bg-blue-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70" @click="savePermissions">
            <!-- Nudges the user to save once they've checked a box: a quiet ping until they click, not a blocking modal. -->
            <span v-if="hasUnsavedPermissionChanges && !form.processing" class="absolute -right-1 -top-1 flex h-3 w-3" aria-hidden="true">
              <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75" />
              <span class="relative inline-flex h-3 w-3 rounded-full bg-amber-500" />
            </span>
            {{ form.processing ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </div>

      <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <!-- Typing here also resets the matrix to page 1, see watch(permissionSearch) in useUserRoles -->
        <label class="relative block flex-1">
          <span class="sr-only">Search permissions</span>
          <input v-model="permissionSearch" type="text" placeholder="Search modules or permissions..." class="w-full rounded-xl border border-slate-300 px-4 py-2.5 pr-10 text-sm text-slate-700 outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20">
          <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-3.5-3.5" />
          </svg>
        </label>

        <!-- Checked modules sort to the top of the matrix automatically; this toggle hides the rest so they're findable without paging through. -->
        <label class="inline-flex w-fit shrink-0 items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-800/80">
          <input v-model="showOnlyChecked" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-700 accent-blue-700 focus:ring-blue-200 dark:border-gray-600 dark:bg-gray-800 dark:accent-blue-500 dark:focus:ring-blue-500/20">
          <span>Show only checked ({{ checkedResourcesCount }})</span>
        </label>
      </div>
    </div>

    <p v-if="form.errors.permissions" class="m-5 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400">{{ form.errors.permissions }}</p>

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
            <td class="sticky left-0 z-10 whitespace-nowrap bg-white px-5 py-4 font-semibold capitalize text-slate-800 min-w-[240px] w-[260px] dark:bg-gray-900 dark:text-gray-200">
              <div class="flex items-center gap-3">
                <!-- Dash means some but not all actions in this row are granted; click toggles the whole row -->
                <button type="button" class="flex h-5 w-5 items-center justify-center rounded border transition" :class="isResourceFullyChecked(resource) || isResourcePartiallyChecked(resource) ? 'border-emerald-600 bg-emerald-600 text-white dark:border-emerald-500 dark:bg-emerald-500' : 'border-slate-300 bg-white text-transparent hover:border-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:hover:border-emerald-500'" :aria-pressed="isResourceFullyChecked(resource)" :aria-label="`Select all ${resource.replaceAll('_', ' ')} permissions`" @click="toggleResourcePermissions(resource)">
                  <span class="text-xs font-bold">{{ isResourcePartiallyChecked(resource) ? '-' : '✓' }}</span>
                </button>
                <span>{{ resource.replaceAll('_', ' ') }}</span>
              </div>
            </td>
            <td v-for="action in actions" :key="`${resource}-${action}`" class="w-24 px-4 py-4 text-center">
              <!-- Renders "-" instead of a checkbox when this resource/action combo isn't a real permission -->
              <button v-if="permissionName(resource, action)" type="button" class="mx-auto flex h-5 w-5 items-center justify-center rounded border transition" :class="isChecked(permissionName(resource, action)) ? 'border-blue-700 bg-blue-700 text-white dark:border-blue-500 dark:bg-blue-500' : 'border-slate-300 bg-white text-transparent hover:border-blue-400 dark:border-gray-600 dark:bg-gray-800 dark:hover:border-blue-500'" @click="togglePermission(permissionName(resource, action))"><span class="text-xs font-bold">✓</span></button>
              <span v-else class="text-slate-300 dark:text-gray-600">-</span>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="filteredResources.length === 0" class="px-5 py-10 text-center text-sm text-slate-500 dark:text-gray-400">No permissions found.</div>
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
