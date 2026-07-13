<script setup>
import axios from 'axios'
import { Head, Link, router } from '@inertiajs/vue3'
import { onMounted, ref, watch } from 'vue'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'

const rooms = ref([])
const search = ref('')
const isLoading = ref(false)
const hasLoaded = ref(false)
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 5,
  total: 0,
})

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Rooms', current: true },
]

onMounted(() => {
  fetchRooms()
})

async function fetchRooms(pageNumber = 1) {
  isLoading.value = true

  try {
    const response = await axios.get('/dashboard/rooms/data', {
      params: {
        page: pageNumber,
        search: search.value,
      },
    })

    rooms.value = response.data.data ?? []
    pagination.value = {
      current_page: response.data.current_page ?? 1,
      last_page: response.data.last_page ?? 1,
      per_page: response.data.per_page ?? 5,
      total: response.data.total ?? 0,
    }
  } catch (error) {
    console.error('Failed to fetch rooms', error)
  } finally {
    hasLoaded.value = true
    isLoading.value = false
  }
}

function editRoom(room) {
  router.visit(`/dashboard/rooms/edit/${room.id}`)
}

function deleteRoom(room) {
  if (!window.confirm(`Delete room ${room.room_number}?`)) {
    return
  }

  router.delete(`/dashboard/rooms/${room.id}`, {
    preserveScroll: true,
    onSuccess: () => fetchRooms(pagination.value.current_page),
  })
}

watch(search, () => {
  fetchRooms(1)
})

function statusClass(status) {
  if (status === 'occupied')
    return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20'
  if (status === 'maintenance')
    return 'bg-rose-50 text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-500/20'

  return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20'
}
</script>

<template>
  <Head title="Rooms" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Building Management" title="Rooms" description="Read, create, update, and delete room records." />

      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <input
            v-model="search"
            type="search"
            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 sm:max-w-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
            placeholder="Search room number or status"
          >

          <Link
            href="/dashboard/rooms/create"
            class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 whitespace-nowrap dark:bg-blue-600 dark:hover:bg-blue-500"
          >
            Create Room
          </Link>
        </div>

        <div class="mt-5 overflow-x-auto">
          <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3">ID</th>
                <th class="px-4 py-3">Floor ID</th>
                <th class="px-4 py-3">Room Number</th>
                <th class="px-4 py-3">Capacity</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-gray-800">
              <tr v-if="isLoading">
                <td colspan="6" class="px-4 py-8 text-center text-slate-500 dark:text-gray-400">Loading rooms...</td>
              </tr>

              <tr v-else-if="hasLoaded && rooms.length === 0">
                <td colspan="6" class="px-4 py-8 text-center text-slate-500 dark:text-gray-400">No rooms found.</td>
              </tr>

              <tr v-for="room in rooms" v-else :key="room.id" class="transition hover:bg-slate-50 dark:hover:bg-gray-800">
                <td class="px-4 py-3 font-medium text-slate-700 dark:text-gray-300">{{ room.id }}</td>
                <td class="px-4 py-3 text-slate-600 dark:text-gray-300">{{ room.floor_id ?? '-' }}</td>
                <td class="px-4 py-3 font-semibold text-slate-900 dark:text-gray-100">{{ room.room_number }}</td>
                <td class="px-4 py-3 text-slate-600 dark:text-gray-300">{{ room.capacity ?? '-' }}</td>
                <td class="px-4 py-3">
                  <span :class="['rounded-full px-3 py-1 text-xs font-semibold capitalize', statusClass(room.status)]">
                    {{ room.status }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex justify-end gap-2">
                    <button
                      type="button"
                      class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                      @click="editRoom(room)"
                    >
                      Update
                    </button>
                    <button
                      type="button"
                      class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-500/20"
                      @click="deleteRoom(room)"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-5 flex flex-col gap-3 border-t border-slate-100 pt-4 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:text-gray-300">
          <span>Page {{ pagination.current_page }} of {{ pagination.last_page }} - {{ pagination.total }} rooms</span>

          <div class="flex gap-2">
            <button
              type="button"
              :disabled="pagination.current_page <= 1 || isLoading"
              class="rounded-lg border border-slate-300 px-3 py-2 font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
              @click="fetchRooms(pagination.current_page - 1)"
            >
              Previous
            </button>
            <button
              type="button"
              :disabled="pagination.current_page >= pagination.last_page || isLoading"
              class="rounded-lg border border-slate-300 px-3 py-2 font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
              @click="fetchRooms(pagination.current_page + 1)"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
