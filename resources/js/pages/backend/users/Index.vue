<script setup>
import axios from 'axios'
import { onMounted, ref, watch } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import UserTableSection from './components/UserTableSection.vue'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'

const page = usePage()
const canCreateUser = page.props.canCreateUser ?? false
const currentUserRole = page.props.auth?.roles?.[0] ?? null
const currentUserId = page.props.auth?.user?.id ?? null
const users = ref([])
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 5,
  total: 0,
})
const search = ref('')
const selectedRole = ref('')
const roles = ref([])
const isLoadingUsers = ref(false)
const hasLoadedUsers = ref(false)

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Users', current: true },
]

onMounted(async () => {
  await Promise.all([
    fetchRoles(),
    fetchUsers(),
  ])
})

async function fetchRoles() {
  try {
    const response = await axios.get('/roles')
    roles.value = Array.isArray(response.data) ? response.data : []
  } catch (error) {
    console.error('Failed to fetch roles', error)
    roles.value = []
  }
}

async function fetchUsers(pageNumber = 1) {
  isLoadingUsers.value = true

  try {
    const params = { page: pageNumber, per_page: 5 }
    if (search.value) params.search = search.value
    if (selectedRole.value) params.role = selectedRole.value

    const response = await axios.get('/dashboard/users/data', { params })

    users.value = response.data.data ?? []
    pagination.value = {
      current_page: response.data.current_page ?? 1,
      last_page: response.data.last_page ?? 1,
      per_page: response.data.per_page ?? 5,
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
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Users Management" title="Users" description="View existing users and manage account roles." />

      <UserTableSection
        :users="users"
        :roles="roles"
        :pagination="pagination"
        :is-loading="isLoadingUsers"
        :has-loaded="hasLoadedUsers"
        :can-create-user="canCreateUser"
        :current-user-role="currentUserRole"
        :current-user-id="currentUserId"
        :search="search"
        :selected-role="selectedRole"
        @update:search="search = $event"
        @update:selectedRole="selectedRole = $event"
        @view-user="viewUser"
        @edit-user="editUser"
        @delete-user="deleteUser"
        @page-change="fetchUsers"
      />
    </section>
  </DashboardLayout>
</template>
