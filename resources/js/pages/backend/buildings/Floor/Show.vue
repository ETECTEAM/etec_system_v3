<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import { Breadcrumbs } from '../../../../components/ui/breadcrumbs'
import { PageHero } from '../../../../components/ui/page-hero'
import DashboardLayout from '../../../../layouts/DashboardLayout.vue'

const page = usePage()
const floor = page.props.floor ?? {}

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Floors', href: '/dashboard/floors' },
  { label: 'View Floor', current: true },
]
</script>

<template>
  <Head :title="`View Floor - ${floor.name ?? 'Floor'}`" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Building Management" title="View Floor" description="Review floor details and level information." />

      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
          <div class="grid gap-5 sm:grid-cols-2">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Floor Name</p>
              <p class="mt-2 text-base font-semibold text-slate-900">{{ floor.name }}</p>
            </div>

            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Level</p>
              <p class="mt-2 text-base text-slate-800">{{ floor.level ?? '-' }}</p>
            </div>
          </div>

          <div class="flex gap-3">
            <Link
              :href="`/dashboard/floors/edit/${floor.id}`"
              class="rounded-xl bg-blue-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-800"
            >
              Edit Floor
            </Link>
            <Link
              href="/dashboard/floors"
              class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
              Back to List
            </Link>
          </div>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
