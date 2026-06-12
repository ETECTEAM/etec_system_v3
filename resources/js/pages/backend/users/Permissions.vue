<script setup>
import { Head } from '@inertiajs/vue3'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Permissions', current: true },
]
</script>

<template>
  <Head title="Permission" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Instructor Management" title="Permission" description="Configure access rules and permission mapping for each role." />

      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-sm text-slate-700">
          Permission management page is ready. You can now add permission table, create permission form, and role-permission assignment UI here.
        </p>
      </div>
    </section>
  </DashboardLayout>
</template>
