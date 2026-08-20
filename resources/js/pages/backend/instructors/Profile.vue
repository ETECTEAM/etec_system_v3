<script setup>
import { computed, watch, ref } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import { SelectSearch } from '../../../components/ui/select-search'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'

const page = usePage()
const user = page.props.user ?? {}
const instructorData = page.props.instructorData ?? null
const workSchedules = computed(() => page.props.workSchedules ?? [])
const subCategories = computed(() => page.props.subCategories ?? [])
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

const workScheduleOptions = computed(() =>
  workSchedules.value
    .filter((ws) => ws.code?.startsWith(`${form.employment_type}_`))
    .map((ws) => ({ label: ws.name, value: String(ws.id) }))
)

// Older instructor rows may still contain specialization as a scalar string.
// Always submit an array of currently selectable values; spreading a string
// would otherwise turn it into invalid one-character entries (e.g. "B").
function normalizeSpecialization(value) {
  let values = value

  if (typeof value === 'string') {
    try {
      const parsed = JSON.parse(value)
      values = Array.isArray(parsed) ? parsed : [value]
    } catch {
      values = [value]
    }
  }

  if (!Array.isArray(values)) return []

  return values.filter((name) =>
    typeof name === 'string' && subCategories.value.includes(name)
  )
}

const initialValues = {
  email: user?.email ?? '',
  full_name: instructorData?.full_name ?? user?.name ?? '',
  phone: instructorData?.phone ?? '',
  specialization: normalizeSpecialization(instructorData?.specialization),
  employment_type: instructorData?.employment_type ?? '',
  work_schedule_id: instructorData?.work_schedule_id ? String(instructorData.work_schedule_id) : '',
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
  // Multipart requests must be sent as POST so PHP parses every FormData field.
  // Laravel turns this into the existing PUT route before it reaches the controller.
  _method: 'put',
  email: user?.email ?? '',
  full_name: instructorData?.full_name ?? user?.name ?? '',
  instructor_code: instructorData?.instructor_code ?? '',
  phone: instructorData?.phone ?? '',
  specialization: normalizeSpecialization(instructorData?.specialization),
  employment_type: instructorData?.employment_type ?? '',
  work_schedule_id: instructorData?.work_schedule_id ? String(instructorData.work_schedule_id) : '',
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
  if (JSON.stringify([...form.specialization].sort()) !== JSON.stringify([...initialValues.specialization].sort())) return true
  if (form.employment_type !== initialValues.employment_type) return true
  if (form.work_schedule_id !== initialValues.work_schedule_id) return true
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
  if (!workScheduleOptions.value.some((option) => option.value === form.work_schedule_id)) {
    form.work_schedule_id = ''
  }
})

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'My Instructor Profile', current: true },
]

function toggleSpecialization(name) {
  const index = form.specialization.indexOf(name)

  if (index === -1) {
    form.specialization.push(name)
  } else {
    form.specialization.splice(index, 1)
  }
}

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

  form.post('/dashboard/instructor/profile', {
    forceFormData: true,
    onSuccess: () => {
      initialValues.email = form.email
      initialValues.full_name = form.full_name
      initialValues.phone = form.phone
      initialValues.specialization = [...form.specialization]
      initialValues.employment_type = form.employment_type
      initialValues.work_schedule_id = form.work_schedule_id
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
    },
  })
}
</script>

<template>
  <Head :title="$t('My Instructor Profile')" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :breadcrumbItems="breadcrumbItems" />
      <PageHero eyebrow="Instructor" :title="$t('My Instructor Profile')" description="Complete or update your instructor profile information." />

      <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-gray-800 dark:bg-gray-900">
        <form @submit.prevent="submit">

          <!-- Basic Information -->
          <div class="mb-8">
            <h2 class="mb-1 text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Basic Information') }}</h2>
            <p class="mb-5 text-sm text-slate-500 dark:text-gray-400">{{ $t('Your core account and employment details.') }}</p>
            <div class="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
              <div class="md:col-span-2 md:grid md:grid-cols-3 md:gap-x-6">
                <label class="block md:col-span-1">
                  <span class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Email') }}</span>
                  <input
                    v-model="form.email"
                    type="email"
                    class="w-full h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                    :placeholder="$t('your@email.com')"
                  >
                  <span v-if="form.errors.email" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.email }}</span>
                </label>
                <label class="block md:col-span-1">
                  <span class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Full Name') }} <span class="text-red-500">*</span></span>
                  <input
                    v-model="form.full_name"
                    type="text"
                    class="w-full h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                    :placeholder="$t('Your full name')"
                  >
                  <span v-if="form.errors.full_name" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.full_name }}</span>
                </label>
                <label class="block md:col-span-1">
                  <span class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Instructor Code') }}</span>
                  <input
                    v-model="form.instructor_code"
                    type="text"
                    disabled
                    class="w-full h-11 rounded-lg border border-slate-300 bg-slate-100 px-4 text-sm text-slate-500 outline-none transition dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400"
                  >
                  <span v-if="form.errors.instructor_code" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.instructor_code }}</span>
                </label>
              </div>

              <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Employment Type') }} <span class="text-red-500">*</span></span>
                <SelectSearch
                  v-model="form.employment_type"
                  :options="employmentTypeOptions"
                  :placeholder="$t('Select employment type')"
                  button-class="flex w-full h-11 items-center justify-between rounded-lg border border-slate-300 bg-white px-4 text-sm transition focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20 dark:disabled:bg-gray-900 dark:disabled:text-gray-500"
                />
                <span v-if="form.errors.employment_type" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.employment_type }}</span>
              </label>

              <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Phone') }}</span>
                <input
                  v-model="form.phone"
                  type="text"
                  class="w-full h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                  :placeholder="$t('Phone number')"
                >
                <span v-if="form.errors.phone" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.phone }}</span>
              </label>

              <div class="block md:col-span-2">
                <span class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Specialization') }}</span>
                <p class="mb-2 text-xs text-slate-400 dark:text-gray-500">{{ $t('Select every area you can teach - click a skill to add or remove it.') }}</p>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="name in subCategories"
                    :key="name"
                    type="button"
                    class="rounded-full border px-3 py-1.5 text-xs font-medium transition"
                    :class="form.specialization.includes(name)
                      ? 'border-blue-600 bg-blue-600 text-white dark:border-blue-500 dark:bg-blue-500'
                      : 'border-slate-300 text-slate-600 hover:border-blue-400 hover:text-blue-600 dark:border-gray-600 dark:text-gray-300 dark:hover:border-blue-500 dark:hover:text-blue-400'"
                    @click="toggleSpecialization(name)"
                  >
                    {{ name }}
                  </button>
                </div>
                <span v-if="form.errors.specialization" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.specialization }}</span>
              </div>

              <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Work Schedule') }}</span>
                <SelectSearch
                  v-model="form.work_schedule_id"
                  :options="workScheduleOptions"
                  :placeholder="$t('Select a work schedule')"
                  button-class="flex w-full h-11 items-center justify-between rounded-lg border border-slate-300 bg-white px-4 text-sm transition focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20 dark:disabled:bg-gray-900 dark:disabled:text-gray-500"
                />
                <p class="mt-1 text-xs text-slate-400 dark:text-gray-500">{{ $t('Your working availability will be derived from the selected schedule.') }}</p>
                <span v-if="form.errors.work_schedule_id" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.work_schedule_id }}</span>
              </label>
            </div>
          </div>

          <hr class="mb-8 border-slate-200 dark:border-gray-800">

          <!-- Profile Details -->
          <div class="mb-8">
            <h2 class="mb-1 text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Profile Details') }}</h2>
            <p class="mb-5 text-sm text-slate-500 dark:text-gray-400">{{ $t('Optional CV-style information about yourself.') }}</p>
            <div class="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
              <div class="md:col-span-2">
                <label class="block">
                  <span class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Headline') }}</span>
                  <input
                    v-model="form.headline"
                    type="text"
                    class="w-full h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                    :placeholder="$t('e.g. Senior Math Instructor | 10+ Years Experience')"
                  >
                  <span v-if="form.errors.headline" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.headline }}</span>
                </label>
              </div>

              <div class="md:col-span-2">
                <label class="block">
                  <span class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Bio') }}</span>
                  <textarea
                    v-model="form.bio"
                    rows="4"
                    class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                    :placeholder="$t('A short professional summary about yourself...')"
                  />
                  <span v-if="form.errors.bio" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.bio }}</span>
                </label>
              </div>

              <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Date of Birth') }}</span>
                <input
                  v-model="form.date_of_birth"
                  type="date"
                  class="w-full h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                >
                <span v-if="form.errors.date_of_birth" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.date_of_birth }}</span>
              </label>

              <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Gender') }}</span>
                <SelectSearch
                  v-model="form.gender"
                  :options="genderOptions"
                  :placeholder="$t('Select gender')"
                  button-class="flex w-full h-11 items-center justify-between rounded-lg border border-slate-300 bg-white px-4 text-sm transition focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20 dark:disabled:bg-gray-900 dark:disabled:text-gray-500"
                />
                <span v-if="form.errors.gender" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.gender }}</span>
              </label>

              <div class="md:col-span-2">
                <label class="block">
                  <span class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Address') }}</span>
                  <input
                    v-model="form.address"
                    type="text"
                    class="w-full h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                    :placeholder="$t('Your address')"
                  >
                  <span v-if="form.errors.address" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.address }}</span>
                </label>
              </div>
            </div>
          </div>

          <hr class="mb-8 border-slate-200 dark:border-gray-800">

          <!-- Social Links -->
          <div class="mb-8">
            <h2 class="mb-1 text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Social Links') }}</h2>
            <p class="mb-5 text-sm text-slate-500 dark:text-gray-400">{{ $t('Connect your professional online profiles.') }}</p>
            <div class="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
              <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Telegram') }}</span>
                <input
                  v-model="form.telegram"
                  type="text"
                  class="w-full h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                  :placeholder="$t('Telegram username or link')"
                >
                <span v-if="form.errors.telegram" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.telegram }}</span>
              </label>

              <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('LinkedIn') }}</span>
                <input
                  v-model="form.linkedin"
                  type="url"
                  class="w-full h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                  :placeholder="$t('https://linkedin.com/in/...')"
                >
                <span v-if="form.errors.linkedin" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.linkedin }}</span>
              </label>

              <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('GitHub') }}</span>
                <input
                  v-model="form.github"
                  type="url"
                  class="w-full h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                  :placeholder="$t('https://github.com/...')"
                >
                <span v-if="form.errors.github" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.github }}</span>
              </label>

              <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Portfolio URL') }}</span>
                <input
                  v-model="form.portfolio_url"
                  type="url"
                  class="w-full h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                  :placeholder="$t('https://your-portfolio.com')"
                >
                <span v-if="form.errors.portfolio_url" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.portfolio_url }}</span>
              </label>
            </div>
          </div>

          <hr class="mb-8 border-slate-200 dark:border-gray-800">

          <!-- Attachments -->
          <div class="mb-8">
            <h2 class="mb-1 text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Attachments') }}</h2>
            <p class="mb-5 text-sm text-slate-500 dark:text-gray-400">{{ $t('Upload your profile photo, CV, and other documents.') }}</p>
            <div class="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">

              <!-- Profile Photo -->
              <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Profile Photo') }}</span>
                <div class="flex items-center gap-4">
                  <div class="h-16 w-16 shrink-0 overflow-hidden rounded-full border border-slate-200 bg-slate-50 dark:border-gray-700 dark:bg-gray-800">
                    <img
                      v-if="profilePhotoPreview"
                      :src="profilePhotoPreview"
                      class="h-full w-full object-cover"
                      alt="Profile photo"
                    >
                    <div v-else class="flex h-full w-full items-center justify-center text-slate-400 dark:text-gray-500">
                      <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                  </div>
                  <div class="flex-1">
                    <input
                      type="file"
                      accept="image/*"
                      class="block w-full text-sm text-slate-500 file:mr-3 file:h-9 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:text-sm file:font-semibold file:text-blue-900 hover:file:bg-blue-100 dark:text-gray-400 dark:file:bg-blue-500/10 dark:file:text-blue-400 dark:hover:file:bg-blue-500/20"
                      @change="onProfilePhotoChange"
                    >
                    <p class="mt-1 text-xs text-slate-400 dark:text-gray-500">{{ $t('JPEG, PNG, GIF, WebP. Max 2MB.') }}</p>
                    <span v-if="form.errors.profile_photo" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.profile_photo }}</span>
                  </div>
                </div>
              </label>

              <!-- CV -->
              <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('CV / Resume') }}</span>
                <div class="space-y-2">
                  <div v-if="cvFileName" class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    <svg class="h-4 w-4 shrink-0 text-blue-900 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="truncate">{{ cvFileName }}</span>
                    <a
                      v-if="cvFileProp?.url"
                      :href="cvFileProp.url"
                      target="_blank"
                      class="ml-auto shrink-0 text-blue-900 underline hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                    >{{ $t('View') }}</a>
                  </div>
                  <input
                    type="file"
                    accept=".pdf,.doc,.docx"
                    class="block w-full text-sm text-slate-500 file:mr-3 file:h-9 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:text-sm file:font-semibold file:text-blue-900 hover:file:bg-blue-100 dark:text-gray-400 dark:file:bg-blue-500/10 dark:file:text-blue-400 dark:hover:file:bg-blue-500/20"
                    @change="onCvFileChange"
                  >
                  <p class="mt-1 text-xs text-slate-400 dark:text-gray-500">{{ $t('PDF, DOC, DOCX. Max 5MB.') }}</p>
                  <span v-if="form.errors.cv_file" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.cv_file }}</span>
                </div>
              </label>


            </div>
          </div>

          <hr class="mb-8 border-slate-200 dark:border-gray-800">

          <!-- Change Password -->
          <div class="mb-8">
            <h2 class="mb-1 text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Change Password') }}</h2>
            <p class="mb-5 text-sm text-slate-500 dark:text-gray-400">{{ $t('Leave blank to keep your current password.') }}</p>
            <div class="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
              <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('New Password') }}</span>
                <input
                  v-model="form.password"
                  type="password"
                  class="w-full h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                  :placeholder="$t('New password')"
                  autocomplete="new-password"
                >
                <span v-if="form.errors.password" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.password }}</span>
              </label>

              <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Confirm New Password') }}</span>
                <input
                  v-model="form.password_confirmation"
                  type="password"
                  class="w-full h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                  :placeholder="$t('Confirm new password')"
                  autocomplete="new-password"
                >
                <span v-if="form.errors.password_confirmation" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.password_confirmation }}</span>
              </label>
            </div>
          </div>

          <!-- Submit -->
          <div class="flex justify-end border-t border-slate-200 pt-6 dark:border-gray-800">
            <button
              type="submit"
              :disabled="!isDirty || form.processing"
              class="h-11 rounded-lg bg-blue-900 px-5 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500"
            >
              {{ form.processing ? $t('Saving...') : $t('Save Profile') }}
            </button>
          </div>
        </form>
      </div>
    </section>
  </DashboardLayout>
</template>
