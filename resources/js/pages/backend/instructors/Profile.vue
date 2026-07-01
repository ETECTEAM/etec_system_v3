<script setup>
import { computed, watch, ref } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import { SelectSearch } from '../../../components/ui/select-search'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'

const toast = useToast()
const page = usePage()
const user = page.props.user ?? {}
const instructorData = page.props.instructorData ?? null
const shiftTemplates = computed(() => page.props.shiftTemplates ?? [])

const employmentTypeOptions = [
  { label: 'Full Time', value: 'full_time' },
  { label: 'Part Time', value: 'part_time' },
]

const filteredShiftTemplateOptions = computed(() =>
  shiftTemplates.value
    .filter((t) => !form.employment_type || t.employment_type === form.employment_type)
    .map((t) => ({ label: t.name, value: String(t.id) }))
)

const initialValues = {
  email: user?.email ?? '',
  full_name: instructorData?.full_name ?? user?.name ?? '',
  phone: instructorData?.phone ?? '',
  employment_type: instructorData?.employment_type ?? '',
  shift_template_id: instructorData?.shift_template_id ? String(instructorData.shift_template_id) : '',
}

const form = useForm({
  email: user?.email ?? '',
  full_name: instructorData?.full_name ?? user?.name ?? '',
  instructor_code: instructorData?.instructor_code ?? '',
  phone: instructorData?.phone ?? '',
  employment_type: instructorData?.employment_type ?? '',
  shift_template_id: instructorData?.shift_template_id ? String(instructorData.shift_template_id) : '',
  password: '',
  password_confirmation: '',
})

const isDirty = computed(() => {
  if (form.email !== initialValues.email) return true
  if (form.full_name !== initialValues.full_name) return true
  if (form.phone !== initialValues.phone) return true
  if (form.employment_type !== initialValues.employment_type) return true
  if (form.shift_template_id !== initialValues.shift_template_id) return true
  if (form.password !== '' || form.password_confirmation !== '') return true
  return false
})

watch(() => form.employment_type, () => {
  form.shift_template_id = ''
})

watch(() => page.props.flash, (flash) => {
  if (flash?.success) {
    toast.success(flash.success)
  } else if (flash?.error) {
    toast.error(flash.error)
  } else if (flash?.warning) {
    toast.warning(flash.warning)
  } else if (flash?.info) {
    toast.info(flash.info)
  }
}, { deep: true })

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'My Instructor Profile', current: true },
]

function submit() {
  if (!isDirty.value || form.processing) return

  form.put('/dashboard/instructor/profile', {
    onSuccess: () => {
      initialValues.email = form.email
      initialValues.full_name = form.full_name
      initialValues.phone = form.phone
      initialValues.employment_type = form.employment_type
      initialValues.shift_template_id = form.shift_template_id
      form.password = ''
      form.password_confirmation = ''
    },
    onError: (errors) => {
      const firstError = Object.values(errors)[0]
      if (firstError) {
        toast.error(firstError)
      } else {
        toast.error('Failed to update profile.')
      }
    },
  })
}
</script>

<template>
  <Head title="My Instructor Profile" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :breadcrumbItems="breadcrumbItems" />
      <PageHero eyebrow="Instructor" title="My Instructor Profile" description="Complete or update your instructor profile information." />

      <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <form class="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2" @submit.prevent="submit">
          <div class="grid grid-cols-1 gap-x-6 md:col-span-2 md:grid-cols-3">
            <label class="block">
              <span class="mb-1.5 block text-sm font-semibold text-slate-700">Email</span>
            <input
              v-model="form.email"
              type="email"
              class="w-full h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
              placeholder="your@email.com"
            >
            <span v-if="form.errors.email" class="mt-1 block text-xs text-red-600">{{ form.errors.email }}</span>
            </label>

            <label class="block">
              <span class="mb-1.5 block text-sm font-semibold text-slate-700">Full Name <span class="text-red-500">*</span></span>
              <input
                v-model="form.full_name"
                type="text"
                class="w-full h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
                placeholder="Your full name"
              >
              <span v-if="form.errors.full_name" class="mt-1 block text-xs text-red-600">{{ form.errors.full_name }}</span>
            </label>

            <label class="block">
              <span class="mb-1.5 block text-sm font-semibold text-slate-700">Instructor Code <span class="text-red-500">*</span></span>
              <input
                v-model="form.instructor_code"
                type="text"
                disabled
                class="w-full h-11 rounded-lg border border-slate-300 bg-slate-100 px-4 text-sm text-slate-500 outline-none transition"
              >
              <span v-if="form.errors.instructor_code" class="mt-1 block text-xs text-red-600">{{ form.errors.instructor_code }}</span>
            </label>
          </div>

          <label class="block">
            <span class="mb-1.5 block text-sm font-semibold text-slate-700">Employment Type <span class="text-red-500">*</span></span>
            <SelectSearch
              v-model="form.employment_type"
              :options="employmentTypeOptions"
              placeholder="Select employment type"
              button-class="flex w-full h-11 items-center justify-between rounded-lg border border-slate-300 bg-white px-4 text-sm transition focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400"
            />
            <span v-if="form.errors.employment_type" class="mt-1 block text-xs text-red-600">{{ form.errors.employment_type }}</span>
          </label>

          <label class="block">
            <span class="mb-1.5 block text-sm font-semibold text-slate-700">Phone</span>
            <input
              v-model="form.phone"
              type="text"
              class="w-full h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
              placeholder="Phone number"
            >
            <span v-if="form.errors.phone" class="mt-1 block text-xs text-red-600">{{ form.errors.phone }}</span>
          </label>

          <label class="block">
            <span class="mb-1.5 block text-sm font-semibold text-slate-700">Shift Template</span>
            <SelectSearch
              v-model="form.shift_template_id"
              :options="filteredShiftTemplateOptions"
              :disabled="!form.employment_type"
              placeholder="Select a shift template"
              button-class="flex w-full h-11 items-center justify-between rounded-lg border border-slate-300 bg-white px-4 text-sm transition focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400"
            />
            <p class="mt-1 text-xs text-slate-400">Availabilities will be auto-generated from the selected template.</p>
            <span v-if="form.errors.shift_template_id" class="mt-1 block text-xs text-red-600">{{ form.errors.shift_template_id }}</span>
          </label>

          <div class="border-t border-slate-200 md:col-span-2" />

          <div class="md:col-span-2">
            <h3 class="text-sm font-semibold text-slate-700">Change Password</h3>
            <p class="mt-1 text-xs text-slate-400">Leave blank to keep your current password.</p>
          </div>

          <label class="block">
            <span class="mb-1.5 block text-sm font-semibold text-slate-700">New Password</span>
            <input
              v-model="form.password"
              type="password"
              class="w-full h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
              placeholder="New password"
              autocomplete="new-password"
            >
            <span v-if="form.errors.password" class="mt-1 block text-xs text-red-600">{{ form.errors.password }}</span>
          </label>

          <label class="block">
            <span class="mb-1.5 block text-sm font-semibold text-slate-700">Confirm New Password</span>
            <input
              v-model="form.password_confirmation"
              type="password"
              class="w-full h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
              placeholder="Confirm new password"
              autocomplete="new-password"
            >
            <span v-if="form.errors.password_confirmation" class="mt-1 block text-xs text-red-600">{{ form.errors.password_confirmation }}</span>
          </label>

          <div class="flex justify-end md:col-span-2">
            <button
              type="submit"
              :disabled="!isDirty || form.processing"
              class="h-11 rounded-lg bg-blue-900 px-5 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70"
            >
              {{ form.processing ? 'Saving...' : 'Save Profile' }}
            </button>
          </div>
        </form>
      </div>
    </section>
  </DashboardLayout>
</template>
