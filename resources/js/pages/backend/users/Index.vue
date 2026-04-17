<script setup>
import axios from 'axios'
import { onMounted, ref, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import PageHero from '../../../components/PageHero.vue'
import Pagination from '../../../components/Pagination.vue'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../components/ui/table'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'

const page = usePage()
const canCreateUser = page.props.canCreateUser ?? false
const currentUserRole = page.props.auth?.roles?.[0] ?? null
const currentUserId = page.props.auth?.user?.id ?? null
const users = ref([])
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
})
const search = ref('')
const selectedRole = ref('')
const roles = ref([])
const isLoadingUsers = ref(false)
const hasLoadedUsers = ref(false)

onMounted(async () => {
  await Promise.all([
    fetchRoles(),
    fetchUsers(),
  ])
})

async function fetchRoles() {
  try {
    const response = await axios.get('/api/roles')
    roles.value = Array.isArray(response.data) ? response.data : []
  } catch (error) {
    console.error('Failed to fetch roles', error)
    roles.value = []
  }
}

async function fetchUsers(pageNumber = 1) {
  isLoadingUsers.value = true

  try {
    const response = await axios.get('/dashboard/users/data', {
      params: {
        page: pageNumber,
        search: search.value,
        role: selectedRole.value,
      },
    })

    users.value = response.data.data ?? []
    pagination.value = {
      current_page: response.data.current_page ?? 1,
      last_page: response.data.last_page ?? 1,
      per_page: response.data.per_page ?? 10,
      total: response.data.total ?? 0,
    }
  } catch (error) {
    console.error('Failed to fetch users', error)
  } finally {
    hasLoadedUsers.value = true
    isLoadingUsers.value = false
  }
}

function viewUser(id) {
  router.visit(`/dashboard/users/${id}`)
}

function editUser(id) {
  router.visit(`/dashboard/users/edit/${id}`)
}

function deleteUser(id) {
  if (!window.confirm('Are you sure?')) {
    return
  }

  router.delete(`/dashboard/users/${id}`)
}

function canEdit(user) {
  return currentUserRole === 'super_admin'
}

function canDelete(user) {
  return currentUserRole === 'super_admin' && user.id !== currentUserId
}

function formatRole(role) {
  return role.replace(/_/g, ' ').replace(/\b\w/g, (character) => character.toUpperCase())
}

function paginationStart() {
  if (pagination.value.total === 0 || users.value.length === 0) {
    return 0
  }

  return ((pagination.value.current_page - 1) * pagination.value.per_page) + 1
}

function paginationEnd() {
  if (pagination.value.total === 0 || users.value.length === 0) {
    return 0
  }

  return ((pagination.value.current_page - 1) * pagination.value.per_page) + users.value.length
}

function rowNumber(index) {
  return ((pagination.value.current_page - 1) * pagination.value.per_page) + index + 1
}

watch(search, () => {
  fetchUsers(1)
})

watch(selectedRole, () => {
  fetchUsers(1)
})
</script>

<template>
  <Head title="User" />

  <DashboardLayout>
    <section class="space-y-6 p-4 sm:p-6">
      <PageHero eyebrow="User Management" title="User" description="View existing users and manage account roles." />

      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div class="w-full lg:w-1/3">
            <input
              v-model="search"
              type="text"
              placeholder="Search users..."
              class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
            >
          </div>

          <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <select
              v-model="selectedRole"
              class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
            >
              <option value="">All Roles</option>
              <option v-for="role in roles" :key="role.id" :value="role.name">
                {{ formatRole(role.name) }}
              </option>
            </select>

            <Link v-if="canCreateUser" href="/dashboard/users/create" class="rounded-lg bg-blue-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
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
                      class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold capitalize text-slate-700 ring-1 ring-slate-200"
                    >
                      {{ role.replace('_', ' ') }}
                    </span>
                  </div>
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end gap-2">
                    <button
                      type="button"
                      class="rounded-md bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700 transition hover:bg-slate-200"
                      @click="viewUser(user.id)"
                    >
                      View
                    </button>

                    <button
                      v-if="canEdit(user)"
                      type="button"
                      class="rounded-md bg-blue-500 px-3 py-1 text-sm font-medium text-white transition hover:bg-blue-600"
                      @click="editUser(user.id)"
                    >
                      Edit
                    </button>

                    <button
                      v-if="canDelete(user)"
                      type="button"
                      class="rounded-md bg-red-500 px-3 py-1 text-sm font-medium text-white transition hover:bg-red-600"
                      @click="deleteUser(user.id)"
                    >
                      Delete
                    </button>
                  </div>
                </TableCell>
              </TableRow>

              <TableRow v-if="hasLoadedUsers && !isLoadingUsers && users.length === 0">
                <TableCell colspan="5" class="py-10 text-center text-slate-500">
                  {{ roles.length === 0 ? 'No roles available or roles could not be loaded.' : 'No users found.' }}
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>

          <div
            v-if="isLoadingUsers"
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
              :disabled="isLoadingUsers"
              @page-change="fetchUsers"
            />
          </div>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
