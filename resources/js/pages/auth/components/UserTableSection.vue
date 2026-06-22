<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
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

const searchError = computed(() => {
  if (!props.search) {
    return ''
  }

  return props.search.length < 2 ? 'Type at least 2 characters to search.' : ''
})

function actionItemsFor(user) {
  const items = [
    { key: 'view', label: 'View' },
  ]

  if (canEdit()) {
    items.push({ key: 'edit', label: 'Edit' })
  }

  items.push({
    key: 'delete',
    label: 'Delete',
    disabled: !canDelete(user),
    hint: !canDelete(user) ? 'Unavailable' : '',
  })

  return items
}

function handleAction(action, user) {
  if (action.key === 'view') {
    emit('view-user', user.id)
  }

  if (action.key === 'edit') {
    emit('edit-user', user.id)
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
          <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">User Directory</p>
          <h2 class="mt-1 text-xl font-semibold text-slate-900">Users</h2>
          <p class="mt-1 text-sm text-slate-500">Manage accounts, roles, and access levels.</p>
        </div>

        <Link
          v-if="canCreateUser"
          href="/dashboard/users/create"
          class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
        >
          Create User
        </Link>
      </div>

      <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="md:col-span-2">
          <label class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Search</label>
          <input
            :value="search"
            type="text"
            placeholder="Search email, profile name, code, or phone"
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
            <TableHead>Avatar</TableHead>
            <TableHead>Name</TableHead>
            <TableHead>Email</TableHead>
            <TableHead>Roles</TableHead>
            <TableHead>Status</TableHead>
            <TableHead>Created</TableHead>
            <TableHead class="text-right">Actions</TableHead>
          </TableRow>
        </TableHeader>

        <TableBody>
          <TableRow v-for="(user, index) in users" :key="user.id" class="transition-opacity duration-200">
            <TableCell class="text-slate-500">{{ rowNumber(index) }}</TableCell>
            <TableCell><img :src="user.avatar ? `/storage/${user.avatar}` : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.name)" class="h-9 w-9 rounded-full object-cover" alt="Avatar"></TableCell>
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
            <TableCell><span :class="user.status ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'" class="rounded-full px-2.5 py-1 text-xs font-semibold">{{ user.status ? 'Active' : 'Inactive' }}</span></TableCell>
            <TableCell class="text-sm text-slate-600">{{ user.created_at ?? '—' }}</TableCell>
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
          <span class="text-sm text-slate-600">Loading users...</span>
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
