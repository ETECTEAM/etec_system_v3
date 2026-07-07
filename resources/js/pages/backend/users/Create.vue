<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { Eye, EyeOff } from '@lucide/vue'
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

const showPassword = ref(false)
const showPasswordConfirmation = ref(false)

const roleSelectOptions = computed(() => roleOptions.map((role) => ({
  label: formatRole(role),
  value: role,
})))

const statusSelectOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
]

const nameLocked = computed(() => form.role === 'instructor' || form.role === 'student')

watch(() => form.role, (role) => {
  if (role === 'instructor' || role === 'student') {
    form.name = ''
  }
})

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
              :disabled="nameLocked"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400"
              :placeholder="nameLocked ? 'Set by the instructor/student in their own profile' : 'User full name'"
            >
            <span v-if="nameLocked" class="mt-1 block text-xs text-slate-500">Instructors and students set their name themselves after logging in.</span>
            <span v-if="form.errors.name" class="mt-1 block text-xs text-red-600">{{ form.errors.name }}</span>
          </label>

          <label class="block sm:col-span-1">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Email</span>
            <input v-model="form.email" type="email" autocomplete="email" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100" placeholder="user@etec.com">
            <span v-if="form.errors.email" class="mt-1 block text-xs text-red-600">{{ form.errors.email }}</span>
          </label>

          <label class="block sm:col-span-1">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Password</span>
            <div class="relative">
              <input v-model="form.password" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 pr-11 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100" placeholder="Minimum 8 characters">
              <button type="button" tabindex="-1" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600" @click="showPassword = !showPassword">
                <EyeOff v-if="showPassword" class="h-4 w-4" />
                <Eye v-else class="h-4 w-4" />
              </button>
            </div>
            <span v-if="form.errors.password" class="mt-1 block text-xs text-red-600">{{ form.errors.password }}</span>
          </label>

          <label class="block sm:col-span-1">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Confirm Password</span>
            <div class="relative">
              <input v-model="form.password_confirmation" :type="showPasswordConfirmation ? 'text' : 'password'" autocomplete="new-password" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 pr-11 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100" placeholder="Repeat password">
              <button type="button" tabindex="-1" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600" @click="showPasswordConfirmation = !showPasswordConfirmation">
                <EyeOff v-if="showPasswordConfirmation" class="h-4 w-4" />
                <Eye v-else class="h-4 w-4" />
              </button>
            </div>
          </label>

          <div class="grid gap-4 sm:col-span-2 sm:grid-cols-2">
            <label class="block">
              <span class="mb-2 block text-sm font-semibold text-slate-700">Role</span>
              <SelectSearch v-model="form.role" :options="roleSelectOptions" placeholder="Select role" />
              <span v-if="form.errors.role" class="mt-1 block text-xs text-red-600">{{ form.errors.role }}</span>
            </label>

            <label class="block">
              <span class="mb-2 block text-sm font-semibold text-slate-700">Status</span>
              <SelectSearch v-model="form.account_status" :options="statusSelectOptions" placeholder="Select status" />
              <span v-if="form.errors.account_status" class="mt-1 block text-xs text-red-600">{{ form.errors.account_status }}</span>
            </label>
          </div>

          <div class="flex justify-end gap-3 sm:col-span-2">
            <Link href="/dashboard/users" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Cancel</Link>

            <button type="submit" :disabled="form.processing" class="rounded-xl bg-blue-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70">{{ form.processing ? 'Creating user...' : 'Create User' }}</button>
          </div>
        </form>
      </div>
    </section>
  </DashboardLayout>
</template>
