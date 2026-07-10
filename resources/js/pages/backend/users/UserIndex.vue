<script setup>
import axios from 'axios'
import { onMounted, ref, watch } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import UserTableSection from './components/UserTableSection.vue'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import { useConfirm } from '../../../composables/useConfirm'

const toast = useToast()
const { confirm } = useConfirm()
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

async function deleteUser(id) {
  const confirmed = await confirm({
    title: 'Delete this user?',
    message: 'This will permanently remove the account. This action cannot be undone.',
    confirmText: 'Delete',
    danger: true,
  })

  if (!confirmed) {
    return
  }

  router.delete(`/dashboard/users/${id}`, {
    preserveScroll: true,
    // The table's data comes from a separate axios fetch, not Inertia props,
    // so the redirect this triggers won't refresh it on its own — pull the
    // current page again once the delete has actually gone through.
    onSuccess: () => refetchAfterDelete(),
  })
}

async function refetchAfterDelete() {
  await fetchUsers(pagination.value.current_page)

  // Deleting the last row on a page (other than page 1) leaves that page
  // empty even though earlier pages still have data — step back one page.
  if (users.value.length === 0 && pagination.value.current_page > 1) {
    await fetchUsers(pagination.value.current_page - 1)
  }
}

watch(search, () => {
  fetchUsers(1)
})

watch(selectedRole, () => {
  fetchUsers(1)
})

watch(() => page.props.flash, (flash) => {
  if (flash?.success) {
    toast.success(flash.success)
  } else if (flash?.error) {
    toast.error(flash.error)
  } else if (flash?.warning) {
    toast.warning(flash.warning)
  } else if (flash?.info) {
    toast.info(flash.info)
  }
}, { deep: true, immediate: true })
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
