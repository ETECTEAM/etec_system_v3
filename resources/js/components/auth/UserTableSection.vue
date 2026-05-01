<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { formatRole, roleBadgeClass } from '../../lib/roleBadge'
import { Pagination } from '../ui/pagination'
import { SelectSearch } from '../ui/select-search'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../ui/table'

const props = defineProps({
  users: {
    type: Array,
    default: () => [],
  },
  roles: {
    type: Array,
    default: () => [],
  },
  pagination: {
    type: Object,
    required: true,
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
  hasLoaded: {
    type: Boolean,
    default: false,
  },
  canCreateUser: {
    type: Boolean,
    default: false,
  },
  currentUserRole: {
    type: String,
    default: null,
  },
  currentUserId: {
    type: Number,
    default: null,
  },
  search: {
    type: String,
    default: '',
  },
  selectedRole: {
    type: String,
    default: '',
  },
})

const emit = defineEmits([
  'update:search',
  'update:selectedRole',
  'view-user',
  'edit-user',
  'delete-user',
  'page-change',
])

// Only super admins can edit users from this table.
function canEdit() {
  return props.currentUserRole === 'super_admin'
}

// Prevent super admins from deleting their own account from the list.
function canDelete(user) {
  return props.currentUserRole === 'super_admin' && user.id !== props.currentUserId
}

function paginationStart() {
  if (props.pagination.total === 0 || props.users.length === 0) {
    return 0
  }

  return ((props.pagination.current_page - 1) * props.pagination.per_page) + 1
}

function paginationEnd() {
  if (props.pagination.total === 0 || props.users.length === 0) {
    return 0
  }

  return ((props.pagination.current_page - 1) * props.pagination.per_page) + props.users.length
}

// Keep row numbering continuous across paginated pages.
function rowNumber(index) {
  return ((props.pagination.current_page - 1) * props.pagination.per_page) + index + 1
}

// Build the role filter options from the backend roles list.
const roleOptions = computed(() => [
  { label: 'All Roles', value: '' },
  ...props.roles.map((role) => ({
    label: formatRole(role.name),
    value: role.name,
  })),
])
</script>

<template>
  <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
      <div class="w-full lg:w-1/3">
        <input
          :value="search"
          type="text"
          placeholder="Search users..."
          class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
          @input="emit('update:search', $event.target.value)"
        >
      </div>

      <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="w-full sm:w-56">
          <SelectSearch
            :model-value="selectedRole"
            :options="roleOptions"
            placeholder="All Roles"
            @update:model-value="emit('update:selectedRole', $event)"
          />
        </div>

        <Link
          v-if="canCreateUser"
          href="/dashboard/users/create"
          class="rounded-lg bg-blue-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800"
        >
          Create User
        </Link>
      </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-all duration-200">
      <div class="relative">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead class="w-16">No</TableHead>
              <TableHead>Name</TableHead>
              <TableHead>Email</TableHead>
              <TableHead>Roles</TableHead>
              <TableHead class="text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>

          <TableBody>
            <TableRow v-for="(user, index) in users" :key="user.id" class="transition-opacity duration-200">
              <TableCell class="text-slate-500">{{ rowNumber(index) }}</TableCell>
              <TableCell class="font-medium text-slate-900">{{ user.name }}</TableCell>
              <TableCell class="text-slate-600">{{ user.email }}</TableCell>
              <TableCell>
                <div class="flex flex-wrap gap-2">
                  <span
                    v-for="role in user.roles"
                    :key="`${user.id}-${role}`"
                    :class="[
                      'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                      roleBadgeClass(role),
                    ]"
                  >
                    {{ formatRole(role) }}
                  </span>
                </div>
              </TableCell>
              <TableCell class="text-right">
                <div class="flex justify-end gap-2">
                  <button
                    type="button"
                    class="rounded-md bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700 transition hover:bg-slate-200"
                    @click="emit('view-user', user.id)"
                  >
                    View
                  </button>

                  <button
                    v-if="canEdit()"
                    type="button"
                    class="rounded-md bg-blue-500 px-3 py-1 text-sm font-medium text-white transition hover:bg-blue-600"
                    @click="emit('edit-user', user.id)"
                  >
                    Edit
                  </button>

                  <button
                    v-if="canDelete(user)"
                    type="button"
                    class="rounded-md bg-red-500 px-3 py-1 text-sm font-medium text-white transition hover:bg-red-600"
                    @click="emit('delete-user', user.id)"
                  >
                    Delete
                  </button>
                </div>
              </TableCell>
            </TableRow>

            <TableRow v-if="hasLoaded && !isLoading && users.length === 0">
              <TableCell colspan="5" class="py-10 text-center text-slate-500">
                {{ roles.length === 0 ? 'No roles available or roles could not be loaded.' : 'No users found.' }}
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>

        <div
          v-if="isLoading"
          class="absolute inset-0 flex items-center justify-center bg-white/60 backdrop-blur-[1px]"
        >
          <div class="h-8 w-8 animate-spin rounded-full border-2 border-blue-500 border-t-transparent"></div>
        </div>
      </div>

      <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="text-sm text-slate-500">
          Showing {{ paginationStart() }} to {{ paginationEnd() }} of {{ pagination.total }} users
        </div>

        <Pagination
          :current-page="pagination.current_page"
          :last-page="pagination.last_page"
          :disabled="isLoading"
          @page-change="emit('page-change', $event)"
        />
      </div>
    </div>
  </div>
</template>
