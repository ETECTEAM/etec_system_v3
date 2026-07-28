<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Eye, EyeOff } from '@lucide/vue'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import { SelectSearch } from '../../../components/ui/select-search'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import { useUserCreate } from './composables/useUserCreate'

// UI-only state: password/confirm-password visibility toggles.
const showPassword = ref(false)
const showPasswordConfirmation = ref(false)

// breadcrumbItems => Dashboard / Users / Create
const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Users', href: '/dashboard/users' },
  { label: 'Create', current: true },
]

// Server-side validation errors land on form.errors and are rendered inline per field below.
const { form, roleSelectOptions, statusSelectOptions, nameLocked, submit } = useUserCreate()
</script>

<template>
  <Head :title="$t('Create User')" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="User Management" :title="$t('Create New User')" :description="$t('Create a new account and assign a role based on your access level.')" />

      <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-gray-800 dark:bg-gray-900">
        <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
          <!-- Name: disabled and auto-cleared when role is instructor/student, see nameLocked -->
          <label class="block sm:col-span-1">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Full Name') }}</span>
            <input v-model="form.name" type="text" autocomplete="name" :disabled="nameLocked" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20 dark:disabled:bg-gray-700 dark:disabled:text-gray-500" :placeholder="nameLocked ? $t('Set by the instructor/student in their own profile') : $t('User full name')">
            <span v-if="nameLocked" class="mt-1 block text-xs text-slate-500 dark:text-gray-400">{{ $t('Instructors and students set their name themselves after logging in.') }}</span>
            <span v-if="form.errors.name" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.name }}</span>
          </label>

          <!-- Email: used as the login identifier -->
          <label class="block sm:col-span-1">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Email') }}</span>
            <input v-model="form.email" type="email" autocomplete="email" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20" :placeholder="$t('user@etec.com')">
            <span v-if="form.errors.email" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.email }}</span>
          </label>

          <!-- Password: eye icon toggles the input type to reveal/hide the typed value -->
          <label class="block sm:col-span-1">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Password') }}</span>
            <div class="relative">
              <input v-model="form.password" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 pr-11 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20" :placeholder="$t('Minimum 8 characters')">
              <button type="button" tabindex="-1" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300" @click="showPassword = !showPassword">
                <EyeOff v-if="showPassword" class="h-4 w-4" />
                <Eye v-else class="h-4 w-4" />
              </button>
            </div>
            <span v-if="form.errors.password" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.password }}</span>
          </label>

          <!-- Confirm password: mirrors the password field's show/hide toggle -->
          <label class="block sm:col-span-1">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Confirm Password') }}</span>
            <div class="relative">
              <input v-model="form.password_confirmation" :type="showPasswordConfirmation ? 'text' : 'password'" autocomplete="new-password" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 pr-11 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20" :placeholder="$t('Repeat password')">
              <button type="button" tabindex="-1" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300" @click="showPasswordConfirmation = !showPasswordConfirmation">
                <EyeOff v-if="showPasswordConfirmation" class="h-4 w-4" />
                <Eye v-else class="h-4 w-4" />
              </button>
            </div>
          </label>

          <!-- Role/status: full-width row, driven by roleSelectOptions and statusSelectOptions -->
          <div class="grid gap-4 sm:col-span-2 sm:grid-cols-2">
            <label class="block">
              <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Role') }}</span>
              <SelectSearch v-model="form.role" :options="roleSelectOptions" :placeholder="$t('Select role')" />
              <span v-if="form.errors.role" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.role }}</span>
            </label>

            <label class="block">
              <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Status') }}</span>
              <SelectSearch v-model="form.account_status" :options="statusSelectOptions" :placeholder="$t('Select status')" />
              <span v-if="form.errors.account_status" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.account_status }}</span>
            </label>
          </div>

          <!-- Actions: submit is disabled while the request is in flight to prevent double-submits -->
          <div class="flex justify-end gap-3 sm:col-span-2">
            <Link href="/dashboard/users" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">{{ $t('Cancel') }}</Link>

            <button type="submit" :disabled="form.processing" class="rounded-xl bg-blue-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70">{{ form.processing ? $t('Creating user...') : $t('Create User') }}</button>
          </div>
        </form>
      </div>
    </section>
  </DashboardLayout>
</template>
