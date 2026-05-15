<script setup>
import { Head, usePage } from '@inertiajs/vue3'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'

const page = usePage()
const roles = page.props.roles ?? []

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'User Roles', current: true },
]
</script>

<template>
  <Head title="User Role" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="User Management" title="User Role" description="View available roles for user assignment." />

      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <li
            v-for="role in roles"
            :key="role"
            class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold capitalize text-slate-800"
          >
            {{ role.replace('_', ' ') }}
          </li>
        </ul>
      </div>
    </section>
  </DashboardLayout>
</template>
