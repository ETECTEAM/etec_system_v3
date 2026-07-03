<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import DashboardLayout from '../../layouts/DashboardLayout.vue'

const page = usePage()
const instructorData = page.props.instructorData ?? null
</script>

<template>
  <Head title="Dashboard" />

  <DashboardLayout>
    <section class="space-y-6">

      <!-- Fallback when instructor_data is missing -->
      <div v-if="!instructorData" class="rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
          <svg class="h-8 w-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </div>
        <h2 class="text-lg font-semibold text-slate-900">Instructor profile not completed yet.</h2>
        <p class="mt-1 text-sm text-slate-500">Please complete your profile to view your dashboard.</p>
        <Link
          href="/dashboard/instructor/profile"
          class="mt-5 inline-flex h-10 items-center gap-2 rounded-lg bg-blue-900 px-5 text-sm font-semibold text-white transition hover:bg-blue-800"
        >
          Complete Profile
        </Link>
      </div>

      <!-- Summary Cards -->
      <div v-if="instructorData" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50">
              <svg class="h-5 w-5 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z" />
                <path d="M6 6h10M6 10h10" />
              </svg>
            </div>
            <div>
              <p class="text-xs font-medium text-slate-500">Total Classes</p>
              <p class="text-xl font-bold text-slate-900">--</p>
            </div>
          </div>
          <p class="mt-2 text-xs text-slate-400">Across all terms</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50">
              <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z" />
                <path d="M8 7h8" />
                <path d="M8 11h6" />
                <path d="M8 15h4" />
              </svg>
            </div>
            <div>
              <p class="text-xs font-medium text-slate-500">Active Courses</p>
              <p class="text-xl font-bold text-slate-900">--</p>
            </div>
          </div>
          <p class="mt-2 text-xs text-slate-400">Currently teaching</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50">
              <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
                <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
              </svg>
            </div>
            <div>
              <p class="text-xs font-medium text-slate-500">Students</p>
              <p class="text-xl font-bold text-slate-900">--</p>
            </div>
          </div>
          <p class="mt-2 text-xs text-slate-400">Enrolled this term</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-50">
              <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                <line x1="16" y1="2" x2="16" y2="6" />
                <line x1="8" y1="2" x2="8" y2="6" />
                <line x1="3" y1="10" x2="21" y2="10" />
              </svg>
            </div>
            <div>
              <p class="text-xs font-medium text-slate-500">Upcoming Sessions</p>
              <p class="text-xl font-bold text-slate-900">--</p>
            </div>
          </div>
          <p class="mt-2 text-xs text-slate-400">Next 7 days</p>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
