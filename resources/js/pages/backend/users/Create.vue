<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import { SelectSearch } from '../../../components/ui/select-search'
import { formatRole } from '../../../lib/roleBadge'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'

const page = usePage()

const roleOptions = page.props.roleOptions ?? []

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: roleOptions[0] ?? 'admin',
  account_status: 'active',
})

const roleSelectOptions = computed(() => roleOptions.map((role) => ({
  label: formatRole(role),
  value: role,
})))

const statusSelectOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
]

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Users', href: '/dashboard/users' },
  { label: 'Create', current: true },
]

function submit() {
  form.post('/dashboard/users')
}
</script>

<template>
  <Head title="Create User" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="User Management" title="Create New User" description="Create a new account and assign a role based on your access level." />

      <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
          <label class="block sm:col-span-1">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Full Name</span>
            <input
              v-model="form.name"
              type="text"
              autocomplete="name"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
              placeholder="User full name"
            >
            <span v-if="form.errors.name" class="mt-1 block text-xs text-red-600">{{ form.errors.name }}</span>
          </label>

          <label class="block sm:col-span-1">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Email</span>
            <input
              v-model="form.email"
              type="email"
              autocomplete="email"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
              placeholder="user@etec.com"
            >
            <span v-if="form.errors.email" class="mt-1 block text-xs text-red-600">{{ form.errors.email }}</span>
          </label>

          <label class="block sm:col-span-1">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Password</span>
            <input
              v-model="form.password"
              type="password"
              autocomplete="new-password"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
              placeholder="Minimum 8 characters"
            >
            <span v-if="form.errors.password" class="mt-1 block text-xs text-red-600">{{ form.errors.password }}</span>
          </label>

          <label class="block sm:col-span-1">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Confirm Password</span>
            <input
              v-model="form.password_confirmation"
              type="password"
              autocomplete="new-password"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
              placeholder="Repeat password"
            >
          </label>

          <div class="grid gap-4 sm:col-span-2 sm:grid-cols-2">
            <label class="block">
              <span class="mb-2 block text-sm font-semibold text-slate-700">Role</span>
              <SelectSearch
                v-model="form.role"
                :options="roleSelectOptions"
                placeholder="Select role"
              />
              <span v-if="form.errors.role" class="mt-1 block text-xs text-red-600">{{ form.errors.role }}</span>
            </label>

            <label class="block">
              <span class="mb-2 block text-sm font-semibold text-slate-700">Status</span>
              <SelectSearch
                v-model="form.account_status"
                :options="statusSelectOptions"
                placeholder="Select status"
              />
              <span v-if="form.errors.account_status" class="mt-1 block text-xs text-red-600">{{ form.errors.account_status }}</span>
            </label>
          </div>

          <div class="flex justify-end gap-3 sm:col-span-2">
            <Link
              href="/dashboard/users"
              class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
              Cancel
            </Link>

            <button
              type="submit"
              :disabled="form.processing"
              class="rounded-xl bg-blue-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70"
            >
              {{ form.processing ? 'Creating user...' : 'Create User' }}
            </button>
          </div>
        </form>
      </div>
    </section>
  </DashboardLayout>
</template>
