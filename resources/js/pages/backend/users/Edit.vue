<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { computed, watch } from 'vue'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import { SelectSearch } from '../../../components/ui/select-search'
import { formatRole } from '../../../lib/roleBadge'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import UserAccountForm from './components/UserAccountForm.vue'
const page = usePage(), user = page.props.user, roleOptions = page.props.roleOptions ?? [], s = user.student ?? {}, i = user.instructor_data ?? {}
const form = useForm({ name: user.name ?? '', email: user.email ?? '', password: '', password_confirmation: '', role: user.role ?? (roleOptions[0] ?? 'admin'), account_status: user.status ?? 'active', avatar: null, student_code: s.student_code ?? '', student_full_name: s.full_name ?? '', student_first_name: s.first_name ?? '', student_last_name: s.last_name ?? '', student_full_name_kh: s.full_name_kh ?? '', student_gender: s.gender ?? '', student_date_of_birth: s.date_of_birth ?? '', student_phone: s.phone ?? '', student_email: s.email ?? '', student_class_id: s.class_id ?? '', parent_name: s.parent_name ?? '', parent_phone: s.parent_phone ?? '', student_address: s.address ?? '', student_status: s.status ?? true, instructor_code: i.instructor_code ?? '', instructor_full_name: i.full_name ?? '', instructor_first_name: i.first_name ?? '', instructor_last_name: i.last_name ?? '', instructor_full_name_kh: i.full_name_kh ?? '', instructor_gender: i.gender ?? '', instructor_date_of_birth: i.date_of_birth ?? '', instructor_phone: i.phone ?? '', instructor_email: i.email ?? '', specialization: i.specialization ?? '', employment_type: i.employment_type ?? 'full_time', shift_preference: i.shift_preference ?? 'morning_evening', available_for_class: i.available_for_class ?? true, hire_date: i.hire_date ?? '', instructor_address: i.address ?? '', instructor_status: i.status ?? true })

const roleSelectOptions = computed(() => roleOptions.map((role) => ({ label: formatRole(role), value: role })))

const nameLocked = computed(() => form.role === 'instructor' || form.role === 'student')

watch(() => form.role, (role) => {
  if (role === 'instructor' || role === 'student') {
    form.name = ''
  }
})

const statusSelectOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
]

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Users', href: '/dashboard/users' },
  { label: 'Edit', current: true },
]

function submit() {
  form.put(`/dashboard/users/edit/${user.id}`)
}
</script>

<template>
  <Head :title="`Edit User - ${user.name ?? 'User'}`" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
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
              :disabled="nameLocked"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400"
              :placeholder="nameLocked ? 'Set by the instructor/student in their own profile' : 'User full name'"
            >
            <span v-if="nameLocked" class="mt-1 block text-xs text-slate-500">Instructors and students set their name themselves after logging in.</span>
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
              {{ form.processing ? 'Saving changes...' : 'Save Changes' }}
            </button>
          </div>
        </form>
      </div>
    </section>
  </DashboardLayout>
</template>
