<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import { Pagination } from '../../../components/ui/pagination'
import { formatRole, roleBadgeClass } from '../../../lib/roleBadge'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'

const page = usePage()
const users = computed(() => page.props.users ?? [])
const permissions = computed(() => page.props.permissions ?? [])
const selectedUserId = ref(users.value[0]?.id ? String(users.value[0].id) : '')
const matrixPage = ref(1)
const matrixPerPage = 10

const form = useForm({
  permissions: [],
})

const preferredActions = ['view', 'create', 'update', 'delete', 'manage', 'approve', 'export', 'track']

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Users', href: '/dashboard/users' },
  { label: 'Permissions', current: true },
]

const selectedUser = computed(() => users.value.find((user) => String(user.id) === selectedUserId.value) ?? null)
const totalUsers = computed(() => users.value.length)
const directPermissionUsers = computed(() => users.value.filter((user) => (user.direct_permissions?.length ?? 0) > 0).length)
const selectedDirectCount = computed(() => form.permissions.length)
const selectedRoleCount = computed(() => selectedUser.value?.role_permissions?.length ?? 0)
const selectedTotalCount = computed(() => {
  const combined = new Set([
    ...(selectedUser.value?.role_permissions ?? []),
    ...form.permissions,
  ])

  return combined.size
})

const actions = computed(() => {
  const discovered = permissions.value
    .map((permission) => permission.split('.')[1])
    .filter(Boolean)

  return [...preferredActions, ...discovered.filter((action) => !preferredActions.includes(action))]
    .filter((action, index, list) => list.indexOf(action) === index)
})

const resources = computed(() => {
  return permissions.value
    .map((permission) => permission.split('.')[0])
    .filter((resource, index, list) => list.indexOf(resource) === index)
})

const matrixLastPage = computed(() => Math.max(1, Math.ceil(resources.value.length / matrixPerPage)))
const paginatedResources = computed(() => {
  const start = (matrixPage.value - 1) * matrixPerPage

  return resources.value.slice(start, start + matrixPerPage)
})
const matrixStart = computed(() => {
  if (resources.value.length === 0) {
    return 0
  }

  return ((matrixPage.value - 1) * matrixPerPage) + 1
})
const matrixEnd = computed(() => {
  if (resources.value.length === 0) {
    return 0
  }

  return Math.min(matrixPage.value * matrixPerPage, resources.value.length)
})

const userSummaries = computed(() => users.value.map((user) => ({
  ...user,
  selected: String(user.id) === selectedUserId.value,
})))

function permissionName(resource, action) {
  const name = `${resource}.${action}`

  return permissions.value.includes(name) ? name : null
}

function syncFormFromSelectedUser() {
  form.permissions = [...(selectedUser.value?.direct_permissions ?? [])]
  form.clearErrors()
}

function togglePermission(permission) {
  if (!permission) {
    return
  }

  if (form.permissions.includes(permission)) {
    form.permissions = form.permissions.filter((item) => item !== permission)
    return
  }

  form.permissions = [...form.permissions, permission]
}

function isDirectPermission(permission) {
  return permission ? form.permissions.includes(permission) : false
}

function isRolePermission(permission) {
  return permission ? (selectedUser.value?.role_permissions ?? []).includes(permission) : false
}

function savePermissions() {
  if (!selectedUser.value) {
    return
  }

  form.put(`/dashboard/users/${selectedUser.value.id}/permissions`, {
    preserveScroll: true,
  })
}

function changeMatrixPage(page) {
  matrixPage.value = page
}

watch(selectedUserId, syncFormFromSelectedUser, { immediate: true })

watch(resources, () => {
  matrixPage.value = 1
})
</script>

<template>
  <Head title="User & Permission" />

  <DashboardLayout>
    <section class="space-y-6">
<<<<<<< HEAD
      <Breadcrumbs :items="breadcrumbItems" />
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <PageHero eyebrow="User Management" title="User & Permission" description="Give selected users extra permissions without changing their role." />
=======
      <Breadcrumbs :items="breadcrumbItems"/>
      <PageHero eyebrow="User Management" title="Permission" description="Configure access rules and permission mapping for each role." />
>>>>>>> 614892dc5d6c1829f122fdebb7f43b88424d43c4

        <div class="flex gap-3">
          <Link
            href="/dashboard/users/roles"
            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
          >
            Role Matrix
          </Link>
          <button
            type="button"
            :disabled="!selectedUser || form.processing"
            class="inline-flex items-center justify-center rounded-xl bg-blue-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70"
            @click="savePermissions"
          >
            {{ form.processing ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Total Users</p>
          <p class="mt-3 text-3xl font-bold text-slate-900">{{ totalUsers }}</p>
          <p class="mt-1 text-xs text-emerald-600">Available for assignment</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Direct Users</p>
          <p class="mt-3 text-3xl font-bold text-slate-900">{{ directPermissionUsers }}</p>
          <p class="mt-1 text-xs text-blue-700">Have custom permissions</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Direct Selected</p>
          <p class="mt-3 text-3xl font-bold text-slate-900">{{ selectedDirectCount }}</p>
          <p class="mt-1 text-xs text-slate-500">User-specific access</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Total Selected</p>
          <p class="mt-3 text-3xl font-bold text-slate-900">{{ selectedTotalCount }}</p>
          <p class="mt-1 text-xs text-slate-500">Role plus direct access</p>
        </div>
      </div>

      <div class="grid gap-6 xl:grid-cols-[320px_1fr]">
        <aside class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="flex items-center justify-between px-2 pb-3">
            <h2 class="text-base font-bold text-slate-900">Users</h2>
            <span class="text-sm font-semibold text-slate-400">{{ users.length }}</span>
          </div>

          <div class="max-h-[640px] space-y-2 overflow-y-auto pr-1">
            <button
              v-for="user in userSummaries"
              :key="user.id"
              type="button"
              class="flex w-full items-start justify-between gap-3 rounded-xl px-4 py-3 text-left transition"
              :class="user.selected ? 'bg-blue-50 text-blue-900' : 'text-slate-700 hover:bg-slate-50'"
              @click="selectedUserId = String(user.id)"
            >
              <span class="min-w-0">
                <span class="block truncate text-sm font-bold">{{ user.name }}</span>
                <span class="mt-1 block truncate text-xs text-slate-500">{{ user.email }}</span>
                <span class="mt-2 flex flex-wrap gap-1">
                  <span
                    v-for="role in user.roles"
                    :key="role"
                    :class="[
                      'inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold',
                      roleBadgeClass(role),
                    ]"
                  >
                    {{ formatRole(role) }}
                  </span>
                </span>
              </span>
              <span class="shrink-0 rounded-full bg-white px-2 py-1 text-xs font-bold text-blue-700">
                {{ user.direct_permissions.length }}
              </span>
            </button>
          </div>
        </aside>

        <form class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" @submit.prevent="savePermissions">
          <div class="flex flex-col gap-3 border-b border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h2 class="text-base font-bold text-slate-900">User Permissions Matrix</h2>
              <p class="mt-1 text-sm text-slate-600">
                Blue checks are direct permissions. Pale checks already come from the user's role.
              </p>
            </div>
            <div class="flex gap-3 text-sm font-semibold">
              <span class="text-slate-500">Role {{ selectedRoleCount }}</span>
              <span class="text-blue-700">Direct {{ selectedDirectCount }}</span>
            </div>
          </div>

          <p v-if="form.errors.permissions" class="m-5 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            {{ form.errors.permissions }}
          </p>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
              <thead class="bg-slate-50">
                <tr>
                  <th class="sticky left-0 z-10 bg-slate-50 px-5 py-4 text-left font-semibold text-slate-700">Module</th>
                  <th
                    v-for="action in actions"
                    :key="action"
                    class="px-4 py-4 text-center font-semibold capitalize text-slate-700"
                  >
                    {{ action }}
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 bg-white">
                <tr v-for="resource in paginatedResources" :key="resource" class="hover:bg-slate-50">
                  <td class="sticky left-0 z-10 bg-white px-5 py-4 font-semibold capitalize text-slate-800">
                    {{ resource.replaceAll('_', ' ') }}
                  </td>
                  <td
                    v-for="action in actions"
                    :key="`${resource}-${action}`"
                    class="px-4 py-4 text-center"
                  >
                    <button
                      v-if="permissionName(resource, action)"
                      type="button"
                      class="mx-auto flex h-5 w-5 items-center justify-center rounded border transition"
                      :class="isDirectPermission(permissionName(resource, action))
                        ? 'border-blue-700 bg-blue-700 text-white'
                        : isRolePermission(permissionName(resource, action))
                          ? 'border-blue-200 bg-blue-50 text-blue-600'
                          : 'border-slate-300 bg-white text-transparent hover:border-blue-400'"
                      @click="togglePermission(permissionName(resource, action))"
                    >
                      <span class="text-xs font-bold">✓</span>
                    </button>
                    <span v-else class="text-slate-300">-</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-slate-500">
              Showing {{ matrixStart }} to {{ matrixEnd }} of {{ resources.length }} modules
            </div>

            <Pagination
              :current-page="matrixPage"
              :last-page="matrixLastPage"
              :disabled="form.processing"
              @page-change="changeMatrixPage"
            />
          </div>
        </form>
      </div>
    </section>
  </DashboardLayout>
</template>
