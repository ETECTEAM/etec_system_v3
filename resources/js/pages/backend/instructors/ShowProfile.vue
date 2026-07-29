<script setup>
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'

const page = usePage()
const instructorData = page.props.instructorData ?? null
const profilePhoto = page.props.profilePhoto ?? null
const cvFile = page.props.cvFile ?? null
const otherAttachments = page.props.otherAttachments ?? []
const shiftTemplate = page.props.shiftTemplate ?? null

const user = computed(() => page.props.auth?.user ?? {})
const userEmail = computed(() => user.value?.email ?? 'Not provided')

const initials = computed(() => {
  const name = instructorData?.full_name ?? ''
  const parts = name.trim().split(/\s+/)
  if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  return name[0]?.toUpperCase() ?? '?'
})

function formatFileSize(bytes) {
  if (!bytes) return ''
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / 1048576).toFixed(1) + ' MB'
}
</script>

<template>
  <Head :title="$t('My Profile')" />

  <DashboardLayout>
    <section class="space-y-6">

      <!-- Profile Header Card -->
      <div v-if="instructorData" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="relative bg-gradient-to-r from-blue-950 via-blue-900 to-sky-800 px-6 pb-14 pt-8 sm:px-8 sm:pb-20">
          <p class="text-sm uppercase tracking-[0.3em] text-blue-200">{{ $t('Instructor') }}</p>
          <h1 class="mt-2 text-2xl font-bold text-white">{{ $t('My Profile') }}</h1>
          <div class="mx-auto mt-6 h-24 w-24 sm:absolute sm:-bottom-11 sm:left-8 sm:mx-0 sm:mt-0">
            <div class="h-full w-full overflow-hidden rounded-xl border-4 border-white bg-slate-100 shadow-lg">
              <img
                v-if="profilePhoto?.url"
                :src="profilePhoto.url"
                class="h-full w-full object-cover"
                :alt="instructorData.full_name"
              >
              <div v-else class="flex h-full w-full items-center justify-center bg-blue-900 text-3xl font-bold text-white">
                {{ initials }}
              </div>
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-4 px-6 pb-6 pt-4 sm:flex-row sm:items-start sm:justify-between sm:pl-[9.5rem] sm:pt-5">
          <div class="min-w-0">
            <h2 class="text-xl font-bold text-slate-900 dark:text-gray-100">{{ instructorData.full_name }}</h2>
            <p v-if="instructorData.headline" class="mt-0.5 text-sm text-slate-500 dark:text-gray-400">{{ instructorData.headline }}</p>
            <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-gray-400">
              <span class="flex items-center gap-1">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                {{ userEmail }}
              </span>
              <span class="flex items-center gap-1">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                {{ instructorData.phone ?? 'Not provided' }}
              </span>
              <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                {{ instructorData.status ? 'Active' : 'Inactive' }}
              </span>
              <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-500/10 dark:text-blue-400">{{ instructorData.instructor_code }}</span>
            </div>
          </div>

          <Link
            href="/dashboard/instructor/profile"
            class="inline-flex h-10 shrink-0 items-center gap-2 self-start rounded-lg bg-blue-900 px-5 text-sm font-semibold text-white transition hover:bg-blue-800 sm:self-auto dark:bg-blue-600 dark:hover:bg-blue-500"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Edit Profile
          </Link>
        </div>
      </div>

      <div v-else class="rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 dark:bg-gray-800">
          <svg class="h-8 w-8 text-slate-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Instructor profile not completed yet.') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">{{ $t('Please complete your profile to get started.') }}</p>
        <Link
          href="/dashboard/instructor/profile"
          class="mt-5 inline-flex h-10 items-center gap-2 rounded-lg bg-blue-900 px-5 text-sm font-semibold text-white transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500"
        >
          Complete Profile
        </Link>
      </div>

      <!-- Main Grid -->
      <div v-if="instructorData" class="grid gap-6 lg:grid-cols-2">

        <!-- Left Column -->
        <div class="space-y-6">

          <!-- Personal Information -->
          <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between">
              <h3 class="text-base font-semibold text-slate-900 dark:text-gray-100">{{ $t('Personal Information') }}</h3>
              <Link href="/dashboard/instructor/profile" class="text-blue-900 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              </Link>
            </div>
            <div class="mt-4 space-y-3">
              <div class="flex justify-between border-b border-slate-100 pb-2 text-sm dark:border-gray-800">
                <span class="text-slate-500 dark:text-gray-400">{{ $t('Full Name') }}</span>
                <span class="font-medium text-slate-900 dark:text-gray-100">{{ instructorData.full_name ?? 'Not provided' }}</span>
              </div>
              <div class="flex justify-between border-b border-slate-100 pb-2 text-sm dark:border-gray-800">
                <span class="text-slate-500 dark:text-gray-400">{{ $t('Email') }}</span>
                <span class="font-medium text-slate-900 dark:text-gray-100">{{ userEmail }}</span>
              </div>
              <div class="flex justify-between border-b border-slate-100 pb-2 text-sm dark:border-gray-800">
                <span class="text-slate-500 dark:text-gray-400">{{ $t('Phone') }}</span>
                <span class="font-medium text-slate-900 dark:text-gray-100">{{ instructorData.phone ?? 'Not provided' }}</span>
              </div>
              <div class="flex justify-between border-b border-slate-100 pb-2 text-sm dark:border-gray-800">
                <span class="text-slate-500 dark:text-gray-400">{{ $t('Date of Birth') }}</span>
                <span class="font-medium text-slate-900 dark:text-gray-100">{{ instructorData.date_of_birth ?? 'Not provided' }}</span>
              </div>
              <div class="flex justify-between border-b border-slate-100 pb-2 text-sm dark:border-gray-800">
                <span class="text-slate-500 dark:text-gray-400">{{ $t('Gender') }}</span>
                <span class="font-medium capitalize text-slate-900 dark:text-gray-100">{{ instructorData.gender ?? 'Not provided' }}</span>
              </div>
              <div class="flex justify-between pb-2 text-sm">
                <span class="text-slate-500 dark:text-gray-400">{{ $t('Address') }}</span>
                <span class="max-w-[200px] text-right font-medium text-slate-900 dark:text-gray-100">{{ instructorData.address ?? 'Not provided' }}</span>
              </div>
            </div>
          </div>

          <!-- Social Links -->
          <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between">
              <h3 class="text-base font-semibold text-slate-900 dark:text-gray-100">{{ $t('Social Links') }}</h3>
              <Link href="/dashboard/instructor/profile" class="text-blue-900 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              </Link>
            </div>
            <div class="mt-4 space-y-3">
              <div class="flex justify-between border-b border-slate-100 pb-2 text-sm dark:border-gray-800">
                <span class="text-slate-500 dark:text-gray-400">{{ $t('Telegram') }}</span>
                <span v-if="instructorData.telegram" class="font-medium text-blue-900 dark:text-blue-400">
                  <a :href="instructorData.telegram.startsWith('http') ? instructorData.telegram : 'https://t.me/' + instructorData.telegram" target="_blank" class="hover:underline">{{ instructorData.telegram }}</a>
                </span>
                <span v-else class="text-slate-400 dark:text-gray-500">{{ $t('Not provided') }}</span>
              </div>
              <div class="flex justify-between border-b border-slate-100 pb-2 text-sm dark:border-gray-800">
                <span class="text-slate-500 dark:text-gray-400">{{ $t('LinkedIn') }}</span>
                <span v-if="instructorData.linkedin" class="font-medium text-blue-900 dark:text-blue-400">
                  <a :href="instructorData.linkedin" target="_blank" class="hover:underline">{{ instructorData.linkedin }}</a>
                </span>
                <span v-else class="text-slate-400 dark:text-gray-500">{{ $t('Not provided') }}</span>
              </div>
              <div class="flex justify-between border-b border-slate-100 pb-2 text-sm dark:border-gray-800">
                <span class="text-slate-500 dark:text-gray-400">{{ $t('GitHub') }}</span>
                <span v-if="instructorData.github" class="font-medium text-blue-900 dark:text-blue-400">
                  <a :href="instructorData.github" target="_blank" class="hover:underline">{{ instructorData.github }}</a>
                </span>
                <span v-else class="text-slate-400 dark:text-gray-500">{{ $t('Not provided') }}</span>
              </div>
              <div class="flex justify-between pb-2 text-sm">
                <span class="text-slate-500 dark:text-gray-400">{{ $t('Portfolio') }}</span>
                <span v-if="instructorData.portfolio_url" class="font-medium text-blue-900 dark:text-blue-400">
                  <a :href="instructorData.portfolio_url" target="_blank" class="hover:underline">{{ instructorData.portfolio_url }}</a>
                </span>
                <span v-else class="text-slate-400 dark:text-gray-500">{{ $t('Not provided') }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column -->
        <div class="space-y-6">

          <!-- Employment Information -->
          <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between">
              <h3 class="text-base font-semibold text-slate-900 dark:text-gray-100">{{ $t('Employment Information') }}</h3>
              <Link href="/dashboard/instructor/profile" class="text-blue-900 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              </Link>
            </div>
            <div class="mt-4 space-y-3">
              <div class="flex justify-between border-b border-slate-100 pb-2 text-sm dark:border-gray-800">
                <span class="text-slate-500 dark:text-gray-400">{{ $t('Instructor Code') }}</span>
                <span class="font-mono font-medium text-slate-900 dark:text-gray-100">{{ instructorData.instructor_code ?? 'Not provided' }}</span>
              </div>
              <div class="flex justify-between border-b border-slate-100 pb-2 text-sm dark:border-gray-800">
                <span class="text-slate-500 dark:text-gray-400">{{ $t('Employment Type') }}</span>
                <span class="font-medium capitalize text-slate-900 dark:text-gray-100">{{ instructorData.employment_type?.replace('_', ' ') ?? 'Not provided' }}</span>
              </div>
              <div class="flex justify-between border-b border-slate-100 pb-2 text-sm dark:border-gray-800">
                <span class="text-slate-500 dark:text-gray-400">{{ $t('Shift Template') }}</span>
                <span class="font-medium text-slate-900 dark:text-gray-100">{{ shiftTemplate?.name ?? 'Not assigned' }}</span>
              </div>
              <div class="flex justify-between border-b border-slate-100 pb-2 text-sm dark:border-gray-800">
                <span class="text-slate-500 dark:text-gray-400">{{ $t('Available for Class') }}</span>
                <span :class="instructorData.available_for_class ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500 dark:text-red-400'" class="font-medium">
                  {{ instructorData.available_for_class ? 'Yes' : 'No' }}
                </span>
              </div>
              <div class="flex justify-between pb-2 text-sm">
                <span class="text-slate-500 dark:text-gray-400">{{ $t('Status') }}</span>
                <span :class="instructorData.status ? 'rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-500/10 dark:text-red-400'">
                  {{ instructorData.status ? 'Active' : 'Inactive' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Profile Details -->
          <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between">
              <h3 class="text-base font-semibold text-slate-900 dark:text-gray-100">{{ $t('Profile Details') }}</h3>
              <Link href="/dashboard/instructor/profile" class="text-blue-900 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              </Link>
            </div>
            <div class="mt-4 space-y-3">
              <div class="border-b border-slate-100 pb-2 text-sm dark:border-gray-800">
                <span class="block text-slate-500 dark:text-gray-400">{{ $t('Headline') }}</span>
                <p class="mt-0.5 font-medium text-slate-900 dark:text-gray-100">{{ instructorData.headline ?? 'Not provided' }}</p>
              </div>
              <div class="pb-2 text-sm">
                <span class="block text-slate-500 dark:text-gray-400">{{ $t('Bio') }}</span>
                <p class="mt-0.5 whitespace-pre-wrap font-medium text-slate-900 dark:text-gray-100">{{ instructorData.bio ?? 'Not provided' }}</p>
              </div>
            </div>
          </div>

          <!-- Attachments -->
          <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-base font-semibold text-slate-900 dark:text-gray-100">{{ $t('Attachments') }}</h3>
            <div class="mt-4 space-y-3">
              <div class="flex items-center gap-3 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                <svg class="h-5 w-5 shrink-0 text-blue-900 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span class="text-slate-600 dark:text-gray-300">{{ $t('Profile Photo') }}</span>
                <span v-if="profilePhoto?.url" class="ml-auto text-xs text-emerald-600 dark:text-emerald-400">{{ $t('Uploaded') }}</span>
                <span v-else class="ml-auto text-xs text-slate-400 dark:text-gray-500">{{ $t('Not uploaded') }}</span>
              </div>

              <div class="flex items-center gap-3 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                <svg class="h-5 w-5 shrink-0 text-blue-900 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="text-slate-600 dark:text-gray-300">{{ $t('CV / Resume') }}</span>
                <a
                  v-if="cvFile?.url"
                  :href="cvFile.url"
                  target="_blank"
                  class="ml-auto text-xs font-medium text-blue-900 underline hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                >{{ $t('View CV') }}</a>
                <span v-else class="ml-auto text-xs text-slate-400 dark:text-gray-500">{{ $t('Not uploaded') }}</span>
              </div>

              <div v-for="att in otherAttachments" :key="att.id" class="flex items-center gap-3 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                <svg class="h-5 w-5 shrink-0 text-blue-900 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                <span class="truncate text-slate-600 dark:text-gray-300">{{ att.file_name }}</span>
                <span class="shrink-0 text-xs text-slate-400 dark:text-gray-500">{{ formatFileSize(att.file_size) }}</span>
                <a
                  :href="att.url"
                  target="_blank"
                  class="ml-auto shrink-0 text-xs font-medium text-blue-900 underline hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                >{{ $t('View') }}</a>
              </div>

              <p v-if="!profilePhoto && !cvFile && otherAttachments.length === 0" class="text-sm text-slate-400 dark:text-gray-500">{{ $t('No attachments uploaded yet.') }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>