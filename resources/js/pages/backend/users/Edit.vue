<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import PageHero from '../../../components/PageHero.vue'
import SelectSearch from '../../../components/ui/SelectSearch.vue'
import { formatRole } from '../../../lib/roleBadge'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'

const page = usePage()
const user = page.props.user ?? { roles: [] }
const roleOptions = page.props.roleOptions ?? []

const form = useForm({
  name: user.name ?? '',
  email: user.email ?? '',
  password: '',
  password_confirmation: '',
  role: user.roles?.[0] ?? roleOptions[0] ?? 'student',
})

const roleSelectOptions = computed(() => roleOptions.map((role) => ({
  label: formatRole(role),
  value: role,
})))

function submit() {
  form.put(`/dashboard/users/${user.id}`)
}
</script>

<template>
  <Head :title="`Edit User - ${user.name ?? 'User'}`" />

  <DashboardLayout>
    <section class="space-y-6 p-4 sm:p-6">
      <PageHero
        eyebrow="User Management"
        title="Edit User"
        description="Update account details, reset the password if needed, and change the assigned role."
      />

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
              placeholder="user@example.com"
            >
            <span v-if="form.errors.email" class="mt-1 block text-xs text-red-600">{{ form.errors.email }}</span>
          </label>

          <label class="block sm:col-span-1">
            <span class="mb-2 block text-sm font-semibold text-slate-700">New Password</span>
            <input
              v-model="form.password"
              type="password"
              autocomplete="new-password"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
              placeholder="Leave blank to keep current password"
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
              placeholder="Repeat new password"
            >
          </label>

          <label class="block sm:col-span-2">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Role</span>
            <SelectSearch
              v-model="form.role"
              :options="roleSelectOptions"
              placeholder="Select role"
            />
            <span v-if="form.errors.role" class="mt-1 block text-xs text-red-600">{{ form.errors.role }}</span>
          </label>

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
              {{ form.processing ? 'Saving changes...' : 'Save Changes' }}
            </button>
          </div>
        </form>
      </div>
    </section>
  </DashboardLayout>
</template>
