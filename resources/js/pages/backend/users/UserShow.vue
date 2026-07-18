<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import { formatRole, roleBadgeClass } from '../../../lib/roleBadge'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'

const page = usePage()
// Falls back to an empty roles array so the role badges v-for below doesn't error if user is missing.
const user = page.props.user ?? { roles: [] }

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Users', href: '/dashboard/users' },
  { label: 'View User', current: true },
]
</script>

<template>
  <Head title="View User" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />

      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <PageHero
          eyebrow="User Management"
          title="View User"
          description="Review account details and assigned role information."
        />

        <div class="flex gap-3">
          <Link :href="`/dashboard/users/edit/${user.id}`" class="rounded-xl bg-blue-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500">Edit User</Link>
          <Link href="/dashboard/users" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">Back to List</Link>
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <!-- Full Name: shows an italic placeholder when the user hasn't set a name (e.g. instructor/student not yet logged in) -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-gray-400">Full Name</p>
          <p class="mt-3 text-lg font-bold text-slate-900 dark:text-gray-100" :class="{ 'text-slate-400 italic dark:text-gray-500': !user.name }">{{ user.name || 'Not provided' }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-gray-400">Email</p>
          <p class="mt-3 text-lg font-bold text-slate-900 dark:text-gray-100">{{ user.email }}</p>
        </div>

        <!-- Role: a user can hold multiple roles, each rendered as its own badge -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-gray-400">Role</p>
          <div class="mt-3 flex flex-wrap gap-2">
            <span v-for="role in user.roles" :key="role" :class="['inline-flex rounded-full px-3 py-1 text-xs font-semibold', roleBadgeClass(role)]">{{ formatRole(role) }}</span>
          </div>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>