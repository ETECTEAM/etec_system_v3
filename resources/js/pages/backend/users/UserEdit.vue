<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import { SelectSearch } from '../../../components/ui/select-search'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import UserAccountForm from './components/UserAccountForm.vue'
import { useUserEdit } from './composables/useUserEdit'

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Users', href: '/dashboard/users' },
  { label: 'Edit', current: true },
]

const { form, user, roleSelectOptions, statusSelectOptions, nameLocked, submit } = useUserEdit()
</script>

<template>
  <Head :title="`Edit User - ${user.name ?? 'User'}`" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="User Management" title="Edit User" description="Update account details, reset the password if needed, and change the assigned role." />

      <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-gray-800 dark:bg-gray-900">
        <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
          <!-- Name: disabled and auto-cleared when role is instructor/student, see nameLocked -->
          <label class="block sm:col-span-1">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">Full Name</span>
            <input v-model="form.name" type="text" autocomplete="name" :disabled="nameLocked" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20 dark:disabled:bg-gray-700 dark:disabled:text-gray-500" :placeholder="nameLocked ? 'Set by the instructor/student in their own profile' : 'User full name'">
            <span v-if="nameLocked" class="mt-1 block text-xs text-slate-500 dark:text-gray-400">Instructors and students set their name themselves after logging in.</span>
            <span v-if="form.errors.name" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.name }}</span>
          </label>

          <!-- Email: used as the login identifier -->
          <label class="block sm:col-span-1">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">Email</span>
            <input v-model="form.email" type="email" autocomplete="email" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20" placeholder="user@etec.com">
            <span v-if="form.errors.email" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.email }}</span>
          </label>

          <!-- Password: blank means "keep current password", see submit() -->
          <label class="block sm:col-span-1">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">New Password</span>
            <input v-model="form.password" type="password" autocomplete="new-password" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20" placeholder="Leave blank to keep current password">
            <span v-if="form.errors.password" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.password }}</span>
          </label>

          <!-- Confirm password: only relevant when a new password is entered above -->
          <label class="block sm:col-span-1">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">Confirm Password</span>
            <input v-model="form.password_confirmation" type="password" autocomplete="new-password" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20" placeholder="Repeat new password">
          </label>

          <!-- Role/status: full-width row, driven by roleSelectOptions and statusSelectOptions -->
          <div class="grid gap-4 sm:col-span-2 sm:grid-cols-2">
            <label class="block">
              <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">Role</span>
              <SelectSearch v-model="form.role" :options="roleSelectOptions" placeholder="Select role" />
              <span v-if="form.errors.role" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.role }}</span>
            </label>

            <label class="block">
              <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">Status</span>
              <SelectSearch v-model="form.account_status" :options="statusSelectOptions" placeholder="Select status" />
              <span v-if="form.errors.account_status" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.account_status }}</span>
            </label>
          </div>

          <!-- Actions: submit is disabled while the request is in flight to prevent double-submits -->
          <div class="flex justify-end gap-3 sm:col-span-2">
            <Link href="/dashboard/users" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Cancel</Link>

            <button type="submit" :disabled="form.processing" class="rounded-xl bg-blue-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70">{{ form.processing ? 'Saving changes...' : 'Save Changes' }}</button>
          </div>
        </form>
      </div>
    </section>
  </DashboardLayout>
</template>
