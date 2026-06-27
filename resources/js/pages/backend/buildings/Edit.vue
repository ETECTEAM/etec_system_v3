<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { PageHero } from '../../../components/ui/page-hero'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'

const page = usePage()
const building = page.props.building ?? {}

const form = useForm({
  name: building.name ?? '',
  code: building.code ?? '',
  description: building.description ?? '',
})

function submit() {
  form.put(`/dashboard/buildings/${building.id}`)
}
</script>

<template>
  <Head :title="`Edit Building - ${building.name ?? 'Building'}`" />

  <DashboardLayout>
    <section class="space-y-6">
      <PageHero eyebrow="Building Management" title="Edit Building" description="Update the building details shown in the hierarchy page." />

      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <form class="grid gap-5 sm:grid-cols-2" @submit.prevent="submit">
          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Building Name</span>
            <input v-model="form.name" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100">
            <span v-if="form.errors.name" class="mt-1 block text-xs text-red-600">{{ form.errors.name }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Code</span>
            <input v-model="form.code" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100">
            <span v-if="form.errors.code" class="mt-1 block text-xs text-red-600">{{ form.errors.code }}</span>
          </label>

          <label class="block sm:col-span-2">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Description</span>
            <textarea v-model="form.description" rows="5" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100"></textarea>
            <span v-if="form.errors.description" class="mt-1 block text-xs text-red-600">{{ form.errors.description }}</span>
          </label>

          <div class="flex justify-end gap-3 sm:col-span-2">
            <Link href="/dashboard/buildings" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
              Cancel
            </Link>
            <button type="submit" :disabled="form.processing" class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70">
              {{ form.processing ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </form>
      </div>
    </section>
  </DashboardLayout>
</template>
