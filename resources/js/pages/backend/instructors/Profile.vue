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
const profilePhotoProp = page.props.profilePhoto ?? null
const cvFileProp = page.props.cvFile ?? null

const employmentTypeOptions = [
  { label: 'Full Time', value: 'full_time' },
  { label: 'Part Time', value: 'part_time' },
]

const genderOptions = [
  { label: 'Male', value: 'male' },
  { label: 'Female', value: 'female' },
  { label: 'Other', value: 'other' },
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
  headline: instructorData?.headline ?? '',
  bio: instructorData?.bio ?? '',
  date_of_birth: instructorData?.date_of_birth ?? '',
  gender: instructorData?.gender ?? '',
  address: instructorData?.address ?? '',
  telegram: instructorData?.telegram ?? '',
  linkedin: instructorData?.linkedin ?? '',
  github: instructorData?.github ?? '',
  portfolio_url: instructorData?.portfolio_url ?? '',
}

const form = useForm({
  email: user?.email ?? '',
  full_name: instructorData?.full_name ?? user?.name ?? '',
  instructor_code: instructorData?.instructor_code ?? '',
  phone: instructorData?.phone ?? '',
  employment_type: instructorData?.employment_type ?? '',
  shift_template_id: instructorData?.shift_template_id ? String(instructorData.shift_template_id) : '',
  headline: instructorData?.headline ?? '',
  bio: instructorData?.bio ?? '',
  date_of_birth: instructorData?.date_of_birth ?? '',
  gender: instructorData?.gender ?? '',
  address: instructorData?.address ?? '',
  telegram: instructorData?.telegram ?? '',
  linkedin: instructorData?.linkedin ?? '',
  github: instructorData?.github ?? '',
  portfolio_url: instructorData?.portfolio_url ?? '',
  password: '',
  password_confirmation: '',
  profile_photo: null,
  cv_file: null,
})

const profilePhotoPreview = ref(profilePhotoProp?.url ?? null)
const profilePhotoFile = ref(null)
const cvFileName = ref(cvFileProp?.file_name ?? null)
const cvFileObj = ref(null)

const isDirty = computed(() => {
  if (form.email !== initialValues.email) return true
  if (form.full_name !== initialValues.full_name) return true
  if (form.phone !== initialValues.phone) return true
  if (form.employment_type !== initialValues.employment_type) return true
  if (form.shift_template_id !== initialValues.shift_template_id) return true
  if (form.headline !== initialValues.headline) return true
  if (form.bio !== initialValues.bio) return true
  if (form.date_of_birth !== initialValues.date_of_birth) return true
  if (form.gender !== initialValues.gender) return true
  if (form.address !== initialValues.address) return true
  if (form.telegram !== initialValues.telegram) return true
  if (form.linkedin !== initialValues.linkedin) return true
  if (form.github !== initialValues.github) return true
  if (form.portfolio_url !== initialValues.portfolio_url) return true
  if (form.password !== '' || form.password_confirmation !== '') return true
  if (profilePhotoFile.value !== null) return true
  if (cvFileObj.value !== null) return true
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

function onProfilePhotoChange(e) {
  const file = e.target.files[0]
  if (!file) return
  profilePhotoFile.value = file
  form.profile_photo = file
  profilePhotoPreview.value = URL.createObjectURL(file)
}

function onCvFileChange(e) {
  const file = e.target.files[0]
  if (!file) return
  cvFileObj.value = file
  form.cv_file = file
  cvFileName.value = file.name
}

function formatFileSize(bytes) {
  if (!bytes) return ''
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / 1048576).toFixed(1) + ' MB'
}

function submit() {
  if (!isDirty.value || form.processing) return

  form.put('/dashboard/instructor/profile', {
    forceFormData: true,
    onSuccess: () => {
      initialValues.email = form.email
      initialValues.full_name = form.full_name
      initialValues.phone = form.phone
      initialValues.employment_type = form.employment_type
      initialValues.shift_template_id = form.shift_template_id
      initialValues.headline = form.headline
      initialValues.bio = form.bio
      initialValues.date_of_birth = form.date_of_birth
      initialValues.gender = form.gender
      initialValues.address = form.address
      initialValues.telegram = form.telegram
      initialValues.linkedin = form.linkedin
      initialValues.github = form.github
      initialValues.portfolio_url = form.portfolio_url
      form.password = ''
      form.password_confirmation = ''
      profilePhotoFile.value = null
      cvFileObj.value = null
      form.profile_photo = null
      form.cv_file = null
      page.props.flash = {}
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
        <form @submit.prevent="submit">

          <!-- Basic Information -->
          <div class="mb-8">
            <h2 class="mb-1 text-lg font-semibold text-slate-900">Basic Information</h2>
            <p class="mb-5 text-sm text-slate-500">Your core account and employment details.</p>
            <div class="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
              <div class="md:col-span-2 md:grid md:grid-cols-3 md:gap-x-6">
                <label class="block md:col-span-1">
                  <span class="mb-1.5 block text-sm font-semibold text-slate-700">Email</span>
                  <input
                    v-model="form.email"
                    type="email"
                    class="w-full h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
                    placeholder="your@email.com"
                  >
                  <span v-if="form.errors.email" class="mt-1 block text-xs text-red-600">{{ form.errors.email }}</span>
                </label>
                <label class="block md:col-span-1">
                  <span class="mb-1.5 block text-sm font-semibold text-slate-700">Full Name <span class="text-red-500">*</span></span>
                  <input
                    v-model="form.full_name"
                    type="text"
                    class="w-full h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
                    placeholder="Your full name"
                  >
                  <span v-if="form.errors.full_name" class="mt-1 block text-xs text-red-600">{{ form.errors.full_name }}</span>
                </label>
                <label class="block md:col-span-1">
                  <span class="mb-1.5 block text-sm font-semibold text-slate-700">Instructor Code</span>
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
            </div>
          </div>

          <hr class="mb-8 border-slate-200">

          <!-- Profile Details -->
          <div class="mb-8">
            <h2 class="mb-1 text-lg font-semibold text-slate-900">Profile Details</h2>
            <p class="mb-5 text-sm text-slate-500">Optional CV-style information about yourself.</p>
            <div class="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
              <div class="md:col-span-2">
                <label class="block">
                  <span class="mb-1.5 block text-sm font-semibold text-slate-700">Headline</span>
                  <input
                    v-model="form.headline"
                    type="text"
                    class="w-full h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
                    placeholder="e.g. Senior Math Instructor | 10+ Years Experience"
                  >
                  <span v-if="form.errors.headline" class="mt-1 block text-xs text-red-600">{{ form.errors.headline }}</span>
                </label>
              </div>

              <div class="md:col-span-2">
                <label class="block">
                  <span class="mb-1.5 block text-sm font-semibold text-slate-700">Bio</span>
                  <textarea
                    v-model="form.bio"
                    rows="4"
                    class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
                    placeholder="A short professional summary about yourself..."
                  />
                  <span v-if="form.errors.bio" class="mt-1 block text-xs text-red-600">{{ form.errors.bio }}</span>
                </label>
              </div>

              <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-slate-700">Date of Birth</span>
                <input
                  v-model="form.date_of_birth"
                  type="date"
                  class="w-full h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
                >
                <span v-if="form.errors.date_of_birth" class="mt-1 block text-xs text-red-600">{{ form.errors.date_of_birth }}</span>
              </label>

              <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-slate-700">Gender</span>
                <SelectSearch
                  v-model="form.gender"
                  :options="genderOptions"
                  placeholder="Select gender"
                  button-class="flex w-full h-11 items-center justify-between rounded-lg border border-slate-300 bg-white px-4 text-sm transition focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400"
                />
                <span v-if="form.errors.gender" class="mt-1 block text-xs text-red-600">{{ form.errors.gender }}</span>
              </label>

              <div class="md:col-span-2">
                <label class="block">
                  <span class="mb-1.5 block text-sm font-semibold text-slate-700">Address</span>
                  <input
                    v-model="form.address"
                    type="text"
                    class="w-full h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
                    placeholder="Your address"
                  >
                  <span v-if="form.errors.address" class="mt-1 block text-xs text-red-600">{{ form.errors.address }}</span>
                </label>
              </div>
            </div>
          </div>

          <hr class="mb-8 border-slate-200">

          <!-- Social Links -->
          <div class="mb-8">
            <h2 class="mb-1 text-lg font-semibold text-slate-900">Social Links</h2>
            <p class="mb-5 text-sm text-slate-500">Connect your professional online profiles.</p>
            <div class="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
              <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-slate-700">Telegram</span>
                <input
                  v-model="form.telegram"
                  type="text"
                  class="w-full h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
                  placeholder="Telegram username or link"
                >
                <span v-if="form.errors.telegram" class="mt-1 block text-xs text-red-600">{{ form.errors.telegram }}</span>
              </label>

              <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-slate-700">LinkedIn</span>
                <input
                  v-model="form.linkedin"
                  type="url"
                  class="w-full h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
                  placeholder="https://linkedin.com/in/..."
                >
                <span v-if="form.errors.linkedin" class="mt-1 block text-xs text-red-600">{{ form.errors.linkedin }}</span>
              </label>

              <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-slate-700">GitHub</span>
                <input
                  v-model="form.github"
                  type="url"
                  class="w-full h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
                  placeholder="https://github.com/..."
                >
                <span v-if="form.errors.github" class="mt-1 block text-xs text-red-600">{{ form.errors.github }}</span>
              </label>

              <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-slate-700">Portfolio URL</span>
                <input
                  v-model="form.portfolio_url"
                  type="url"
                  class="w-full h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
                  placeholder="https://your-portfolio.com"
                >
                <span v-if="form.errors.portfolio_url" class="mt-1 block text-xs text-red-600">{{ form.errors.portfolio_url }}</span>
              </label>
            </div>
          </div>

          <hr class="mb-8 border-slate-200">

          <!-- Attachments -->
          <div class="mb-8">
            <h2 class="mb-1 text-lg font-semibold text-slate-900">Attachments</h2>
            <p class="mb-5 text-sm text-slate-500">Upload your profile photo, CV, and other documents.</p>
            <div class="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">

              <!-- Profile Photo -->
              <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-slate-700">Profile Photo</span>
                <div class="flex items-center gap-4">
                  <div class="h-16 w-16 shrink-0 overflow-hidden rounded-full border border-slate-200 bg-slate-50">
                    <img
                      v-if="profilePhotoPreview"
                      :src="profilePhotoPreview"
                      class="h-full w-full object-cover"
                      alt="Profile photo"
                    >
                    <div v-else class="flex h-full w-full items-center justify-center text-slate-400">
                      <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                  </div>
                  <div class="flex-1">
                    <input
                      type="file"
                      accept="image/*"
                      class="block w-full text-sm text-slate-500 file:mr-3 file:h-9 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:text-sm file:font-semibold file:text-blue-900 hover:file:bg-blue-100"
                      @change="onProfilePhotoChange"
                    >
                    <p class="mt-1 text-xs text-slate-400">JPEG, PNG, GIF, WebP. Max 2MB.</p>
                    <span v-if="form.errors.profile_photo" class="mt-1 block text-xs text-red-600">{{ form.errors.profile_photo }}</span>
                  </div>
                </div>
              </label>

              <!-- CV -->
              <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-slate-700">CV / Resume</span>
                <div class="space-y-2">
                  <div v-if="cvFileName" class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                    <svg class="h-4 w-4 shrink-0 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="truncate">{{ cvFileName }}</span>
                    <a
                      v-if="cvFileProp?.url"
                      :href="cvFileProp.url"
                      target="_blank"
                      class="ml-auto shrink-0 text-blue-900 underline hover:text-blue-700"
                    >View</a>
                  </div>
                  <input
                    type="file"
                    accept=".pdf,.doc,.docx"
                    class="block w-full text-sm text-slate-500 file:mr-3 file:h-9 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:text-sm file:font-semibold file:text-blue-900 hover:file:bg-blue-100"
                    @change="onCvFileChange"
                  >
                  <p class="mt-1 text-xs text-slate-400">PDF, DOC, DOCX. Max 5MB.</p>
                  <span v-if="form.errors.cv_file" class="mt-1 block text-xs text-red-600">{{ form.errors.cv_file }}</span>
                </div>
              </label>


            </div>
          </div>

          <hr class="mb-8 border-slate-200">

          <!-- Change Password -->
          <div class="mb-8">
            <h2 class="mb-1 text-lg font-semibold text-slate-900">Change Password</h2>
            <p class="mb-5 text-sm text-slate-500">Leave blank to keep your current password.</p>
            <div class="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
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
            </div>
          </div>

          <!-- Submit -->
          <div class="flex justify-end border-t border-slate-200 pt-6">
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
