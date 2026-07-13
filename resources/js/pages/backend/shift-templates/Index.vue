<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'

const props = defineProps({
  templates: Object,
  filters: Object,
})

const search = ref(props.filters.search ?? '')
let timeout = null

watch(search, (value) => {
  clearTimeout(timeout)
  timeout = setTimeout(() => {
    router.get('/dashboard/shift-templates', { search: value, page: 1 }, { preserveState: true, replace: true })
  }, 400)
})

function deleteTemplate(id) {
  if (confirm('Are you sure you want to delete this shift template?')) {
    router.delete(`/dashboard/shift-templates/${id}`, { preserveScroll: true })
  }
}

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Shift Templates', current: true },
]

function employmentLabel(type) {
  if (!type) return '—'
  return type === 'full_time' ? 'Full Time' : 'Part Time'
}
</script>

<template>
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Schedule Management" title="Shift Templates" description="Create and manage instructor shift templates." />

      <div class="bg-white rounded-xl border border-slate-200 shadow-sm dark:bg-gray-900 dark:border-gray-800">
        <div class="border-b border-slate-200 px-6 py-5 dark:border-gray-800">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="w-[24%]">
              <input v-model="search" type="text" placeholder="Search templates..." class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20" />
            </div>
            <Link href="/dashboard/shift-templates/create" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-650 dark:bg-blue-600 dark:hover:bg-blue-500">
              Create Template
            </Link>
          </div>
        </div>

        <div class="relative overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-200 dark:bg-gray-800 dark:border-gray-800">
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">Name</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">Code</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">Type</th>
                <th class="px-6 py-3 text-center text-slate-600 dark:text-gray-300">Blocks</th>
                <th class="px-6 py-3 text-center text-slate-600 dark:text-gray-300">Active</th>
                <th class="px-6 py-3 text-right text-slate-600 dark:text-gray-300">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="template in templates.data" :key="template.id" class="border-t border-slate-200 hover:bg-slate-50 transition dark:border-gray-800 dark:hover:bg-gray-800">
                <td class="px-6 py-4 font-medium text-slate-900 dark:text-gray-100">{{ template.name }}</td>
                <td class="px-6 py-4 text-slate-600 font-mono text-xs dark:text-gray-300">{{ template.code }}</td>
                <td class="px-6 py-4 text-slate-600 dark:text-gray-300">{{ employmentLabel(template.employment_type) }}</td>
                <td class="px-6 py-4 text-center text-slate-600 dark:text-gray-300">{{ template.blocks_count }}</td>
                <td class="px-6 py-4 text-center">
                  <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="template.is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-slate-100 text-slate-500 dark:bg-gray-800 dark:text-gray-400'">
                    {{ template.is_active ? 'Yes' : 'No' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right space-x-2">
                  <Link :href="`/dashboard/shift-templates/${template.id}/edit`" class="px-5 py-2 text-sm rounded-lg border border-blue-200 bg-blue-50 font-semibold text-blue-700 transition hover:bg-blue-100 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20">
                    Edit
                  </Link>
                  <button @click="deleteTemplate(template.id)" class="px-5 py-2 text-sm rounded-lg border border-rose-200 bg-rose-50 font-semibold text-rose-700 transition hover:bg-rose-100 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-500/20">
                    Delete
                  </button>
                </td>
              </tr>
              <tr v-if="!templates?.data?.length">
                <td colspan="6" class="py-10 text-center text-slate-500 dark:text-gray-400">{{ search ? `No results for "${search}"` : 'No shift templates found.' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/40">
          <p class="text-sm text-slate-500 dark:text-gray-400">Showing {{ templates.from }}–{{ templates.to }} of {{ templates.total }} templates</p>
          <div class="flex flex-wrap gap-2 text-sm">
            <Link v-for="link in templates.links" :key="link.label" :href="link.url || '#'" v-html="link.label" class="px-3 py-2 rounded-lg border text-sm transition dark:border-gray-700 dark:text-gray-300"
              :class="{ 'bg-blue-600 text-white border-blue-600': link.active, 'hover:bg-gray-100 dark:hover:bg-gray-800': !link.active, 'opacity-40 pointer-events-none': !link.url }" />
          </div>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
