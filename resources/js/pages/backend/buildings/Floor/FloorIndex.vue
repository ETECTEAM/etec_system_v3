<script setup>
import axios from 'axios'
import { onMounted, ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Breadcrumbs } from '../../../../components/ui/breadcrumbs'
import { PageHero } from '../../../../components/ui/page-hero'
import { Pagination } from '../../../../components/ui/pagination'
import { Card } from '../../../../components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../../components/ui/table'
import DashboardLayout from '../../../../layouts/DashboardLayout.vue'
import { useI18n } from '@/i18n'
import { useToast } from '@/composables/useToast'

const { t } = useI18n()
const toast = useToast()

const floors = ref([])
const savingId = ref(null)
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

async function saveFloorField(floor, field, value) {
  const normalized = field === 'level' ? (value === '' ? null : Number(value)) : value

  const previous = floor[field]
  if (normalized === previous || savingId.value !== null) return
  savingId.value = floor.id

  try {
    const response = await axios.put(`/dashboard/floors/${floor.id}`, {
      name: field === 'name' ? value : floor.name,
      level: field === 'level' ? normalized : floor.level,
    })

    Object.assign(floor, response.data.data ?? { [field]: normalized })
    toast.success(t('Floor updated.'))
  } catch (error) {
    floor[field] = previous
    console.error('Failed to update floor', error)
    toast.error(t(error.response?.data?.message ?? 'Failed to update floor. Please try again.'))
  } finally {
    savingId.value = null
  }
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
                <TableCell class="font-medium text-slate-900 dark:text-gray-100">
                  <input type="text" :value="floor.name" :disabled="savingId === floor.id" :class="['w-32 rounded-lg border border-transparent bg-transparent px-2 py-1 font-medium text-slate-900 transition hover:border-slate-300 focus:border-blue-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:hover:border-gray-500 dark:focus:border-blue-500 dark:focus:bg-gray-800 dark:focus:ring-blue-500/20']" @change="saveFloorField(floor, 'name', $event.target.value.trim())">
                </TableCell>
                <TableCell class="text-slate-600 dark:text-gray-300">
                  <input type="number" min="-50" max="300" :value="floor.level" :disabled="savingId === floor.id" placeholder="-" :class="['w-20 rounded-lg border border-transparent bg-transparent px-2 py-1 text-slate-600 transition hover:border-slate-300 focus:border-blue-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-gray-500 dark:focus:border-blue-500 dark:focus:bg-gray-800 dark:focus:ring-blue-500/20']" @change="saveFloorField(floor, 'level', $event.target.value)">
                </TableCell>
                <TableCell class="text-right">
                  <button
                    type="button"
                    class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-500/20"
                    @click="deleteFloor(floor.id)"
                  >
                    {{ $t('Delete') }}
                  </button>
                </TableCell>
              </TableRow>

              <TableRow v-if="floors.length === 0">
                <TableCell colspan="5" class="py-10 text-center text-slate-500 dark:text-gray-400">
                  {{ $t('No floors found.') }}
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
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
