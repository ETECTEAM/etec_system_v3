<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Breadcrumbs } from '../../../../components/ui/breadcrumbs'
import DashboardLayout from '../../../../layouts/DashboardLayout.vue'

const form = useForm({
  name: '',
  level: '',
})

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Floors', href: '/dashboard/floors' },
  { label: 'Create', current: true },
]

function submit() {
  form.post('/dashboard/floors')
}
</script>

<template>
  <Head title="Create Floor" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />

      <!-- Card full width -->
      <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">

        <!-- Card Header -->
        <div class="border-b border-slate-200 bg-slate-50 px-8 py-6 dark:border-gray-800 dark:bg-gray-800/40">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-lg font-semibold text-slate-900 dark:text-gray-100">Create Floor</h1>
              <p class="mt-0.5 text-sm text-slate-500 dark:text-gray-400">Add a new floor record for building organization.</p>
            </div>
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-white dark:bg-blue-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Form -->
        <form class="px-8 py-6" @submit.prevent="submit">
          <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">

            <!-- Floor Name -->
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">
                Floor Name
                <span class="text-red-500 dark:text-red-400">*</span>
              </label>
              <input
                v-model="form.name"
                type="text"
                placeholder="e.g. Ground Floor"
                :class="[
                  'w-full rounded-xl border px-4 py-2.5 text-sm text-slate-700 outline-none transition dark:text-gray-200',
                  form.errors.name
                    ? 'border-red-400 bg-red-50 focus:border-red-500 focus:ring-2 focus:ring-red-100 dark:border-red-500/40 dark:bg-red-500/10 dark:focus:border-red-500 dark:focus:ring-red-500/20'
                    : 'border-slate-300 bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:focus:border-blue-500 dark:focus:ring-blue-500/20'
                ]"
              >
              <p v-if="form.errors.name" class="mt-1.5 flex items-center gap-1 text-xs text-red-600 dark:text-red-400">
                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                {{ form.errors.name }}
              </p>
            </div>



            <!-- Level -->
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">Level</label>
              <input
                v-model="form.level"
                type="number"
                placeholder="e.g. 1"
                :class="[
                  'w-full rounded-xl border px-4 py-2.5 text-sm text-slate-700 outline-none transition dark:text-gray-200',
                  form.errors.level
                    ? 'border-red-400 bg-red-50 focus:border-red-500 focus:ring-2 focus:ring-red-100 dark:border-red-500/40 dark:bg-red-500/10 dark:focus:border-red-500 dark:focus:ring-red-500/20'
                    : 'border-slate-300 bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:focus:border-blue-500 dark:focus:ring-blue-500/20'
                ]"
              >
              <p v-if="form.errors.level" class="mt-1.5 flex items-center gap-1 text-xs text-red-600 dark:text-red-400">
                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                {{ form.errors.level }}
              </p>
            </div>

          </div>

          <!-- Divider -->
          <div class="my-6 border-t border-slate-200 dark:border-gray-800" />

          <!-- Actions -->
          <div class="flex items-center justify-end gap-3">
            <Link
              href="/dashboard/floors"
              class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
            >
              Cancel
            </Link>

            <button
              type="submit"
              :disabled="form.processing"
              class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500"
            >
              <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
              </svg>
              {{ form.processing ? 'Creating...' : 'Create Floor' }}
            </button>
          </div>
        </form>

      </div>
    </section>
  </DashboardLayout>
</template>