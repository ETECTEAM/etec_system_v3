<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import { SelectSearch } from '../../../components/ui/select-search'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'

const page = usePage()
const instructorData = page.props.instructorData ?? null

const employmentTypeOptions = [
  { label: 'Full Time', value: 'full_time' },
  { label: 'Part Time', value: 'part_time' },
]

const shiftGroupOptions = [
  { label: 'Morning & Afternoon (Mon-Fri)', value: 'morning_afternoon' },
  { label: 'Morning & Evening (Mon-Fri)', value: 'morning_evening' },
  { label: 'Weekend Morning', value: 'weekend_morning' },
  { label: 'Weekend Afternoon', value: 'weekend_afternoon' },
  { label: 'Custom', value: 'custom' },
]

const form = useForm({
  full_name: instructorData?.full_name ?? '',
  instructor_code: instructorData?.instructor_code ?? '',
  phone: instructorData?.phone ?? '',
  employment_type: instructorData?.employment_type ?? '',
  shift_group: instructorData?.shift_group ?? '',
  available_for_class: instructorData?.available_for_class ?? true,
})

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'My Instructor Profile', current: true },
]

function submit() {
  form.put('/dashboard/instructor/profile')
}
</script>

<template>
  <Head title="My Instructor Profile" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Instructor" title="My Instructor Profile" description="Complete or update your instructor profile information." />

      <div v-if="$page.props.flash?.success" class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-medium text-emerald-800">
        {{ $page.props.flash.success }}
      </div>

      <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
          <label class="block sm:col-span-1">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Full Name <span class="text-red-500">*</span></span>
            <input
              v-model="form.full_name"
              type="text"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
              placeholder="Your full name"
            >
            <span v-if="form.errors.full_name" class="mt-1 block text-xs text-red-600">{{ form.errors.full_name }}</span>
          </label>

          <label class="block sm:col-span-1">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Instructor Code <span class="text-red-500">*</span></span>
            <input
              v-model="form.instructor_code"
              type="text"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
              placeholder="e.g. INS001"
            >
            <span v-if="form.errors.instructor_code" class="mt-1 block text-xs text-red-600">{{ form.errors.instructor_code }}</span>
          </label>

          <label class="block sm:col-span-1">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Phone</span>
            <input
              v-model="form.phone"
              type="text"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
              placeholder="Phone number"
            >
            <span v-if="form.errors.phone" class="mt-1 block text-xs text-red-600">{{ form.errors.phone }}</span>
          </label>

          <label class="block sm:col-span-1">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Employment Type <span class="text-red-500">*</span></span>
            <SelectSearch
              v-model="form.employment_type"
              :options="employmentTypeOptions"
              placeholder="Select employment type"
            />
            <span v-if="form.errors.employment_type" class="mt-1 block text-xs text-red-600">{{ form.errors.employment_type }}</span>
          </label>

          <label class="block sm:col-span-2">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Shift Group <span class="text-red-500">*</span></span>
            <SelectSearch
              v-model="form.shift_group"
              :options="shiftGroupOptions"
              placeholder="Select shift group"
            />
            <p class="mt-1.5 text-xs text-slate-400">Availabilities will be auto-generated based on the selected shift group. Choose "Custom" to manage manually.</p>
            <span v-if="form.errors.shift_group" class="mt-1 block text-xs text-red-600">{{ form.errors.shift_group }}</span>
          </label>

          <label class="flex items-center gap-3 sm:col-span-2">
            <input
              v-model="form.available_for_class"
              type="checkbox"
              class="h-4 w-4 rounded border-slate-300 text-blue-700 focus:ring-blue-200"
            >
            <span class="text-sm text-slate-700">Available for class</span>
          </label>

          <div class="flex justify-end gap-3 sm:col-span-2">
            <button
              type="submit"
              :disabled="form.processing"
              class="rounded-xl bg-blue-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70"
            >
              {{ form.processing ? 'Saving...' : 'Save Profile' }}
            </button>
          </div>
        </form>
      </div>
    </section>
  </DashboardLayout>
</template>
