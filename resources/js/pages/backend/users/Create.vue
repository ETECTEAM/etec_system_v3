<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3'
import PageHero from '../../../components/PageHero.vue'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'

const page = usePage()

const roleOptions = page.props.roleOptions ?? []

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: roleOptions[0] ?? 'admin',
})

function submit() {
  form.post('/dashboard/users')
}
</script>

<template>
  <Head title="Create User" />

  <DashboardLayout>
    <section class="space-y-6 p-4 sm:p-6">
      <PageHero eyebrow="User Management" title="Create New User" description="Super admin can add a new account and assign a role from this page." gradient-class="from-slate-900 via-blue-900 to-sky-800" />

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

          <label class="block sm:col-span-2">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Role</span>
            <select
              v-model="form.role"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
            >
              <option v-for="role in roleOptions" :key="role" :value="role">{{ role }}</option>
            </select>
            <span v-if="form.errors.role" class="mt-1 block text-xs text-red-600">{{ form.errors.role }}</span>
          </label>

          <div class="sm:col-span-2">
            <button
              type="submit"
              :disabled="form.processing"
              class="w-full rounded-xl bg-blue-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70"
            >
              {{ form.processing ? 'Creating user...' : 'Create User' }}
            </button>
          </div>
        </form>
      </div>
    </section>
  </DashboardLayout>
</template>
