<script setup>
import axios from 'axios'
import { onMounted, ref, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import PageHero from '../../../components/PageHero.vue'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'

const page = usePage()
const canCreateUser = page.props.canCreateUser ?? false
const currentUserRole = page.props.auth?.roles?.[0] ?? null
const currentUserId = page.props.auth?.user?.id ?? null
const users = ref([])
const pagination = ref({
  current_page: 1,
  last_page: 1,
})
const search = ref('')
const selectedRole = ref('')
const roles = ref([])
const isLoadingUsers = ref(false)

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
    }
  } catch (error) {
    console.error('Failed to fetch users', error)
    users.value = []
    pagination.value = {
      current_page: 1,
      last_page: 1,
    }
  } finally {
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

        <div class="overflow-x-auto">
          <table class="min-w-full border-collapse text-left text-sm">
            <thead>
              <tr class="border-b border-slate-200 text-slate-600">
                <th class="px-3 py-2 font-semibold">Name</th>
                <th class="px-3 py-2 font-semibold">Email</th>
                <th class="px-3 py-2 font-semibold">Roles</th>
                <th class="px-3 py-2 text-right font-semibold">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in users" :key="user.id" class="border-b border-slate-100 text-slate-800">
                <td class="px-3 py-2">{{ user.name }}</td>
                <td class="px-3 py-2">{{ user.email }}</td>
                <td class="px-3 py-2">
                  <span
                    v-for="role in user.roles"
                    :key="`${user.id}-${role}`"
                    class="mr-2 inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold capitalize text-slate-700"
                  >
                    {{ role.replace('_', ' ') }}
                  </span>
                </td>
                <td class="px-3 py-2 text-right">
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
                </td>
              </tr>
              <tr v-if="isLoadingUsers">
                <td colspan="4" class="px-3 py-6 text-center text-slate-500">Loading users...</td>
              </tr>
              <tr v-else-if="users.length === 0">
                <td colspan="4" class="px-3 py-6 text-center text-slate-500">
                  {{ roles.length === 0 ? 'No roles available or roles could not be loaded.' : 'No users found.' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="pagination.last_page > 1" class="mt-6 flex flex-wrap justify-center gap-2">
          <button
            v-for="pageNumber in pagination.last_page"
            :key="pageNumber"
            type="button"
            class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
            :class="pageNumber === pagination.current_page
              ? 'bg-blue-600 text-white'
              : 'bg-slate-200 text-slate-700 hover:bg-slate-300'"
            @click="fetchUsers(pageNumber)"
          >
            {{ pageNumber }}
          </button>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
