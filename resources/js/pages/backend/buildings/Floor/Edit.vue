<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { Breadcrumbs } from '../../../../components/ui/breadcrumbs'
import { PageHero } from '../../../../components/ui/page-hero'
import DashboardLayout from '../../../../layouts/DashboardLayout.vue'

const page = usePage()
const floor = page.props.floor ?? {}

const form = useForm({
  name: floor.name ?? '',
  code: floor.code ?? '',
  level: floor.level ?? '',
})

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Floors', href: '/dashboard/floors' },
  { label: 'Edit', current: true },
]

function submit() {
  form.put(`/dashboard/floors/${floor.id}`)
}
</script>

<template>
  <Head :title="`Edit Floor - ${floor.name ?? 'Floor'}`" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Building Management" title="Edit Floor" description="Update the floor name, code, or level number." />

      <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
          <label class="block sm:col-span-1">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Floor Name</span>
            <input
              v-model="form.name"
              type="text"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
            >
            <span v-if="form.errors.name" class="mt-1 block text-xs text-red-600">{{ form.errors.name }}</span>
          </label>

          <label class="block sm:col-span-1">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Code</span>
            <input
              v-model="form.code"
              type="text"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
            >
            <span v-if="form.errors.code" class="mt-1 block text-xs text-red-600">{{ form.errors.code }}</span>
          </label>

          <label class="block sm:col-span-2">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Level</span>
            <input
              v-model="form.level"
              type="number"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
            >
            <span v-if="form.errors.level" class="mt-1 block text-xs text-red-600">{{ form.errors.level }}</span>
          </label>

          <div class="flex justify-end gap-3 sm:col-span-2">
            <Link href="/dashboard/floors" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
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
