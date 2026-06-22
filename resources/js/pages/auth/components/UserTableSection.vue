<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { formatRole, roleBadgeClass } from '@/lib/roleBadge.js'
import { Pagination } from '@/components/ui/pagination'
import { Card } from '@/components/ui/card'
import { ActionMenu } from '@/components/ui/menu'
import { SelectSearch } from '../../../components/ui/select-search'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../components/ui/table'

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

const searchError = computed(() => {
  if (!props.search) {
    return ''
  }

  return props.search.length < 2 ? 'Type at least 2 characters to search.' : ''
})

function actionItemsFor(user) {
  return [
    {
      key: 'view',
      label: 'View',
    },
    {
      key: 'edit',
      label: 'Edit',
    },
    {
      key: 'delete',
      label: 'Delete',
      danger: true,
    },
  ]
}
const actions = [
    {
        key: 'view',
        label: 'View',
    },
    {
        key: 'edit',
        label: 'Edit',
    },
    {
        key: 'delete',
        label: 'Delete',
    },
]

function handleAction(action, user) {
  if (action.key === 'view') {
    router.visit(`/dashboard/users/${user.id}`)
    return
  }

  if (action.key === 'edit') {
    router.visit(`/dashboard/users/${user.id}/edit`)
    return
  }

  if (action.key === 'delete') {
    emit('delete-user', user.id)
  }
}
</script>

<template>
  <Card padding="p-0">
    <div class="border-b border-slate-200 px-6 py-5">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Instructor Directory</p>
          <h2 class="mt-1 text-xl font-semibold text-slate-900">Instructors</h2>
          <p class="mt-1 text-sm text-slate-500">Manage registered instructors and their access.</p>
        </div>

        
      </div>

      <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="md:col-span-2">
          <label class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Search</label>
          <input
            :value="search"
            type="text"
            placeholder="Search by name or email"
            class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
            @input="emit('update:search', $event.target.value)"
          >
          <p v-if="searchError" class="mt-1 text-xs text-red-500">{{ searchError }}</p>
        </div>

        <div>
          <label class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Role</label>
          <div class="mt-2">
            <SelectSearch
              :model-value="selectedRole"
              :options="roleOptions"
              placeholder="All Roles"
              @update:model-value="emit('update:selectedRole', $event)"
            />
          </div>
        </div>
      </div>
    </div>

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
              <div class="flex justify-end">
                <ActionMenu
                  :items="actionItemsFor(user)"
                  @select="handleAction($event, user)"
                />
              </div>
            </TableCell>
          </TableRow>

          <TableRow v-if="hasLoaded && !isLoading && users.length === 0">
            <TableCell colspan="8" class="py-10 text-center text-slate-500">
              {{ roles.length === 0 ? 'No roles available or roles could not be loaded.' : 'No users found.' }}
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>

      <div
        v-if="isLoading"
        class="absolute inset-0 flex items-center justify-center bg-white/70"
      >
        <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-2 shadow-sm">
          <div class="h-5 w-5 animate-spin rounded-full border-2 border-blue-500 border-t-transparent"></div>
          <span class="text-sm text-slate-600">Loading instructors...</span>
        </div>
      </div>
    </div>

    <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
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
  </Card>
</template>
