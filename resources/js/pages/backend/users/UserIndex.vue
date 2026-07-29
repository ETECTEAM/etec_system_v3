<script setup>
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import UserTableSection from './components/UserTableSection.vue'
import UserIndexSkeleton from './components/UserIndexSkeleton.vue'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import { useUserIndex } from './composables/useUserIndex'

// UI state: UserTableSection binds directly to these via v-model-style events.
const search = ref('')
const selectedRole = ref('')

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Users', current: true },
]

const {
  canCreateUser,
  currentUserRole,
  currentUserId,
  users,
  pagination,
  roles,
  isLoadingUsers,
  hasLoadedUsers,
  fetchUsers,
  viewUser,
  editUser,
  deleteUser,
} = useUserIndex({ search, selectedRole })
</script>

<template>
  <Head :title="$t('User')" />

  <DashboardLayout>
    <!-- Shown until the very first fetch resolves, so a hard refresh sees the same
         skeleton as an in-app navigation, not just the inner table's row placeholders. -->
    <UserIndexSkeleton v-if="!hasLoadedUsers" />

    <section v-else class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Users Management" :title="$t('Users')" :description="$t('View existing users and manage account roles.')" />

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
