<script setup>
import axios from 'axios'
import { onMounted, ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Breadcrumbs } from '../../../../components/ui/breadcrumbs'
import { PageHero } from '../../../../components/ui/page-hero'
import { Pagination } from '../../../../components/ui/pagination'
import { Card } from '../../../../components/ui/card'
import { ActionMenu } from '../../../../components/ui/menu'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../../components/ui/table'
import DashboardLayout from '../../../../layouts/DashboardLayout.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

const floors = ref([])
const search = ref('')
const perPage = ref(10)
const perPageOptions = [10, 25, 50, 100, 'all']
const isLoading = ref(false)
const hasLoaded = ref(false)
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
})

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Floors', current: true },
]

onMounted(() => {
  fetchFloors()
})

async function fetchFloors(pageNumber = 1) {
  isLoading.value = true

  try {
    const response = await axios.get('/dashboard/floors/data', {
      params: {
        page: pageNumber,
        per_page: perPage.value,
        search: search.value,
      },
    })

    floors.value = response.data.data ?? []
    pagination.value = {
      current_page: response.data.current_page ?? 1,
      last_page: response.data.last_page ?? 1,
      per_page: response.data.per_page ?? 10,
      total: response.data.total ?? 0,
    }
  } catch (error) {
    console.error('Failed to fetch floors', error)
  } finally {
    hasLoaded.value = true
    isLoading.value = false
  }
}

function viewFloor(id) {
  router.visit(`/dashboard/floors/${id}`)
}

function editFloor(id) {
  router.visit(`/dashboard/floors/edit/${id}`)
}

function deleteFloor(id) {
  if (!window.confirm(t('Are you sure?'))) {
    return
  }

  router.delete(`/dashboard/floors/${id}`, {
    onSuccess: () => fetchFloors(pagination.value.current_page),
  })
}

function rowNumber(index) {
  return ((pagination.value.current_page - 1) * pagination.value.per_page) + index + 1
}

function paginationStart() {
  if (pagination.value.total === 0 || floors.value.length === 0) return 0
  return ((pagination.value.current_page - 1) * pagination.value.per_page) + 1
}

function paginationEnd() {
  if (pagination.value.total === 0 || floors.value.length === 0) return 0
  return ((pagination.value.current_page - 1) * pagination.value.per_page) + floors.value.length
}

function actionItems() {
  return [
    { key: 'view', label: 'View' },
    { key: 'edit', label: 'Edit' },
    { key: 'delete', label: 'Delete', danger: true },
  ]
}

function handleAction(action, floor) {
  if (action.key === 'view') viewFloor(floor.id)
  if (action.key === 'edit') editFloor(floor.id)
  if (action.key === 'delete') deleteFloor(floor.id)
}

watch(search, () => fetchFloors(1))
watch(perPage, () => fetchFloors(1))
</script>

<template>
  <Head :title="$t('Floors')" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Building Management" :title="$t('Floors')" :description="$t('Read, create, update, and delete floor records.')" />

      <Card padding="p-0">
        <!-- Header -->
        <div class="border-b border-slate-200 px-6 py-5 dark:border-gray-800 flex justify-between item-center">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
            <input
              v-model="search"
              type="text"
              :placeholder="$t('Search by name or level...')"
              class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 lg:max-w-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
            >

            <!-- <select
              v-model="perPage"
              class="w-40 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
            >
              <option v-for="option in perPageOptions" :key="option" :value="option">
                {{ option === 'all' ? $t('All floors') : $t(':count per page', { count: option }) }}
              </option>
            </select> -->
          </div>

          <div>
            <Link
              href="/dashboard/floors/create"
              class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500"
            >
              {{ $t('Create Floor') }}
            </Link>
          </div>
        </div>

        <!-- Table -->
        <div class="relative">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="w-16">{{ $t('No') }}</TableHead>
                <TableHead>{{ $t('Name') }}</TableHead>
                <TableHead>{{ $t('Level') }}</TableHead>
                <TableHead class="text-right">{{ $t('Actions') }}</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              <TableRow v-for="(floor, index) in floors" :key="floor.id">
                <TableCell class="text-slate-500 dark:text-gray-400">{{ rowNumber(index) }}</TableCell>
                <TableCell class="font-medium text-slate-900 dark:text-gray-100">{{ floor.name }}</TableCell>
                <TableCell class="text-slate-600 dark:text-gray-300">{{ floor.level ?? '-' }}</TableCell>
                <TableCell class="text-right">
                  <ActionMenu :items="actionItems()" @select="handleAction($event, floor)" />
                </TableCell>
              </TableRow>

              <TableRow v-if="hasLoaded && !isLoading && floors.length === 0">
                <TableCell colspan="5" class="py-10 text-center text-slate-500 dark:text-gray-400">
                  {{ $t('No floors found.') }}
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>

          <div v-if="isLoading" class="absolute inset-0 flex items-center justify-center bg-white/70 dark:bg-gray-900/70">
            <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-2 shadow-sm dark:border-gray-800 dark:bg-gray-900">
              <div class="h-5 w-5 animate-spin rounded-full border-2 border-blue-500 border-t-transparent"></div>
              <span class="text-sm text-slate-600 dark:text-gray-300">{{ $t('Loading floors...') }}</span>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/40">
          <p class="text-sm text-slate-500 dark:text-gray-400">
            {{ $t('Showing :from-:to of :total floors', { from: paginationStart(), to: paginationEnd(), total: pagination.total }) }}
          </p>

          <Pagination
            :current-page="pagination.current_page"
            :last-page="pagination.last_page"
            :disabled="isLoading"
            @page-change="fetchFloors"
          />
        </div>
      </Card>
    </section>
  </DashboardLayout>
</template>
