<script setup>
import { computed, reactive, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { Search } from '@lucide/vue'
import { formatRole, roleBadgeClass } from '@/lib/roleBadge.js'
import { Pagination } from '@/components/ui/pagination'
import { Card } from '@/components/ui/card'
import { ActionMenu } from '@/components/ui/menu'
import RightClick from '@/components/ui/rightclick/RightClick.vue'
import { SelectSearch } from '@/components/ui/select-search'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { TableSkeleton } from '@/components/ui/skeleton'

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
  canCreateUser: {
    type: Boolean,
    default: false,
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

const contextMenu = reactive({
  show: false,
  x: 0,
  y: 0,
  row: null,
})

function openRowContextMenu(event, user) {
  contextMenu.show = true
  contextMenu.x = event.clientX
  contextMenu.y = event.clientY
  contextMenu.row = user
}

function closeContextMenu() {
  contextMenu.show = false
  contextMenu.row = null
}

watch(() => props.users, () => {
  closeContextMenu()
})

function viewUser(id) {
  router.visit(`/dashboard/users/${id}`)
}

function editUser(id) {
  router.visit(`/dashboard/users/edit/${id}`)
}

function deleteUser(id) {
  emit('delete-user', id)
}

function handleContextMenuSelect(actionKey) {
  if (!contextMenu.row) return
  if (actionKey === 'view') viewUser(contextMenu.row.id)
  else if (actionKey === 'edit') editUser(contextMenu.row.id)
  else if (actionKey === 'delete') deleteUser(contextMenu.row.id)
}

const contextMenuActions = [
  { key: 'view', label: 'View' },
  { key: 'edit', label: 'Edit' },
  { key: 'delete', label: 'Delete', danger: true },
]

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
  if (action.key === 'view') viewUser(user.id)
  else if (action.key === 'edit') editUser(user.id)
  else if (action.key === 'delete') deleteUser(user.id)
}
</script>

<template>
  <Card padding="p-0">
    <div class="border-b border-slate-200 px-6 py-5 dark:border-gray-800">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="min-w-0 shrink-0">
          <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-gray-500">User Directory</p>
          <!-- <h2 class="mt-1 text-xl font-semibold text-slate-900">Users</h2> -->
          <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">Manage registered users and their access.</p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-3">
          <div class="relative">
            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500" />
            <input
              :value="search"
              type="text"
              placeholder="Search..."
              class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 sm:w-56 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
              @input="emit('update:search', $event.target.value)"
            >
          </div>

          <div class="sm:w-40">
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
            class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-blue-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Create User
          </Link>
        </div>
      </div>

      <p v-if="searchError && search" class="mt-2 text-xs text-red-500 dark:text-red-400">{{ searchError }}</p>
    </div>

    <div class="relative">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead class="w-16">No</TableHead>
            <TableHead>Name</TableHead>
            <TableHead>Email</TableHead>
            <TableHead>Roles</TableHead>
            <TableHead>Status</TableHead>
            <TableHead class="text-right">Actions</TableHead>
          </TableRow>
        </TableHeader>

        <TableBody>
          <TableSkeleton
            v-if="isLoading"
            :rows="5"
            :columns="[
              { width: '1.5rem' },
              { width: '70%' },
              { width: '85%' },
              { width: '5rem', rounded: 'rounded-full' },
              { width: '4rem', rounded: 'rounded-full' },
              { width: '2rem', align: 'right' },
            ]"
          />

          <template v-else>
            <TableRow v-for="(user, index) in users" :key="user.id" class="transition-opacity duration-200" @contextmenu.prevent="openRowContextMenu($event, user)">
              <TableCell class="text-slate-500 dark:text-gray-400">{{ rowNumber(index) }}</TableCell>
              <TableCell class="font-medium text-slate-900 dark:text-gray-100">
                <span v-if="user.name">{{ user.name }}</span>
                <span v-else class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">No Name</span>
              </TableCell>
              <TableCell class="text-slate-600 dark:text-gray-300">{{ user.email }}</TableCell>
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
              <TableCell>
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="user.status === 'active' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400'">
                  {{ user.status === 'active' ? 'Active' : 'Inactive' }}
                </span>
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

            <TableRow v-if="hasLoaded && users.length === 0">
              <TableCell colspan="9" class="py-10 text-center text-slate-500 dark:text-gray-400">
                {{ roles.length === 0 ? 'No roles available or roles could not be loaded.' : 'No users found.' }}
              </TableCell>
            </TableRow>
          </template>
        </TableBody>
      </Table>
    </div>

    <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/40">
      <div class="text-sm text-slate-500 dark:text-gray-400">
        Showing {{ paginationStart() }} to {{ paginationEnd() }} of {{ pagination.total }} users
      </div>

      <Pagination
        :current-page="pagination.current_page"
        :last-page="pagination.last_page"
        :disabled="isLoading"
        @page-change="emit('page-change', $event)"
      />
    </div>

    <RightClick
      :show="contextMenu.show"
      :x="contextMenu.x"
      :y="contextMenu.y"
      :actions="contextMenuActions"
      @select="handleContextMenuSelect"
      @close="closeContextMenu"
    />
  </Card>
</template>
