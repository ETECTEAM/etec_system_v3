<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import { formatRole, roleBadgeClass } from '../../../lib/roleBadge'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'

const page = usePage()
const user = page.props.user ?? { roles: [] }

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Users', href: '/dashboard/users' },
  { label: 'View User', current: true },
]
</script>

<template>
  <Head :title="`View User - ${user.name ?? 'User'}`" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero
        eyebrow="User Management"
        title="View User"
        description="Review account details and assigned role information."
        
      />

      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
          <div class="space-y-5">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-gray-400">Full Name</p>
              <p class="mt-2 text-base font-semibold text-slate-900 dark:text-gray-100" :class="{ 'text-slate-400 italic dark:text-gray-500': !user.name }">
                {{ user.name || 'Not provided' }}
              </p>
            </div>

            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-gray-400">Email</p>
              <p class="mt-2 text-base text-slate-800 dark:text-gray-200">{{ user.email }}</p>
            </div>

            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-gray-400">Roles</p>
              <div class="mt-3 flex flex-wrap gap-2">
                <span
                  v-for="role in user.roles"
                  :key="role"
                  :class="[
                    'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
                    roleBadgeClass(role),
                  ]"
                >
                  {{ formatRole(role) }}
                </span>
              </div>
            </div>
          </div>

          <div class="flex gap-3">
            <Link
              :href="`/dashboard/users/edit/${user.id}`"
              class="rounded-xl bg-blue-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500"
            >
              Edit User
            </Link>
            <Link
              href="/dashboard/users"
              class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
            >
              Back to List
            </Link>
          </div>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>