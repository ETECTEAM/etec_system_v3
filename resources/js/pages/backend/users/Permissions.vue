<script setup>
import { Head } from '@inertiajs/vue3'
import { PageHero } from '../../../components/ui/page-hero'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
</script>

<template>
  <Head title="Permission" />

  <DashboardLayout>
    <section class="space-y-6 p-4 sm:p-6">
      <PageHero eyebrow="User Management" title="Permission" description="Configure access rules and permission mapping for each role." />

      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-sm text-slate-700">
          Permission management page is ready. You can now add permission table, create permission form, and role-permission assignment UI here.
        </p>
      </div>
    </section>
  </DashboardLayout>
</template>
