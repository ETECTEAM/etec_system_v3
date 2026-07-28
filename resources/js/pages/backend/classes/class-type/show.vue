<script setup>
import { Head, Link } from '@inertiajs/vue3'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { Breadcrumbs } from '@/components/ui/breadcrumbs'
import { PageHero } from '@/components/ui/page-hero'    
import { Card } from '@/components/ui/card'
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table'

const props = defineProps({
  classType: Object
});

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Class Management', href: '/dashboard/class-types' },
  { label: 'View Class Type', current: true },
]

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleDateString('en-US', {
    year: 'numeric', month: 'short', day: 'numeric'
  });
};
</script>

<template>
  <Head :title="$t('View Class Type')" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      
      <PageHero
        eyebrow="Class Management"
        :title="$t('View Class Type')"
        description="Review class type definitions and linked categories."
      />

      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 space-y-8 dark:border-gray-800 dark:bg-gray-900">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <div class="space-y-5">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-gray-400">{{ $t('Type Name') }}</p>
              <p class="mt-2 text-base font-semibold text-slate-900 dark:text-gray-100">{{ classType?.type_name }}</p>
            </div>
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-gray-400">{{ $t('Description') }}</p>
              <p class="mt-2 text-sm text-slate-600 bg-slate-50 p-4 rounded-lg dark:text-gray-300 dark:bg-gray-800">{{ classType?.description || 'No description provided.' }}</p>
            </div>
          </div>

          <div class="space-y-5">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-gray-400">{{ $t('Status') }}</p>
              <div class="mt-2">
                <span :class="classType?.is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-300'"
                      class="inline-flex rounded-full px-3 py-1 text-xs font-bold">
                  {{ classType?.is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <p class="font-semibold text-slate-400 dark:text-gray-500">{{ $t('Created At') }}</p>
                <p class="text-slate-700 dark:text-gray-300">{{ formatDate(classType?.created_at) }}</p>
              </div>
              <div>
                <p class="font-semibold text-slate-400 dark:text-gray-500">{{ $t('Updated At') }}</p>
                <p class="text-slate-700 dark:text-gray-300">{{ formatDate(classType?.updated_at) }}</p>
              </div>
            </div>
          </div>
        </div>
        <div class="flex gap-3 pt-4 border-t border-slate-100 dark:border-gray-800">
          <Link href="/dashboard/class-types"
                class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
              {{ $t('Back to List') }}
          </Link>

          <Link :href="`/dashboard/class-types/${classType.class_type_id}/edit`"
                class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500">
              Edit Class Type
          </Link>
      </div>
      </div>
    </section>
  </DashboardLayout>
</template>