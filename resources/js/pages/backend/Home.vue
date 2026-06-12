<script setup>
import { computed } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import DashboardLayout from '../../layouts/DashboardLayout.vue'

const page = usePage()

const user = computed(() => page.props.auth?.user ?? null)
const roles = computed(() => page.props.auth?.roles ?? [])

const primaryRole = computed(() => {
  return user.value?.role ?? roles.value[0] ?? 'guest'
})
</script>

<template>
  <Head title="Dashboard" />

  <DashboardLayout>
    <section class="space-y-6 p-4 sm:p-6">
      <div class="rounded-3xl bg-gradient-to-r from-blue-950 via-blue-900 to-sky-800 p-6 text-white shadow-xl">
        <p class="text-sm uppercase tracking-[0.3em] text-blue-100">ETEC Dashboard</p>

        <h1 class="mt-3 text-3xl font-black">
          {{ user ? `Welcome back, ${user.name}` : 'Welcome to ETEC System' }}
        </h1>

        <p class="mt-2 max-w-2xl text-sm text-blue-100/90">
          {{ user ? 'You are now logged in and inside the dashboard area.' : 'Please sign in to access your dashboard.' }}
        </p>
      </div>

      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Account</p>
          <p class="mt-3 text-lg font-bold text-slate-900">
            {{ user?.email ?? 'Guest session' }}
          </p>
          <p class="mt-2 text-sm text-slate-600">
            Authenticated email for the current dashboard session.
          </p>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Primary Role</p>
          <p class="mt-3 text-lg font-bold capitalize text-slate-900">
            {{ primaryRole.replace('_', ' ') }}
          </p>
          <p class="mt-2 text-sm text-slate-600">
            Permissions and navigation can be tailored from this role.
          </p>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Status</p>
          <p class="mt-3 text-lg font-bold text-emerald-600">
            {{ user ? 'Logged in' : 'Signed out' }}
          </p>
          <p class="mt-2 text-sm text-slate-600">
            You are inside the dashboard area.
          </p>
        </article>
      </div>
    </section>
  </DashboardLayout>
</template>