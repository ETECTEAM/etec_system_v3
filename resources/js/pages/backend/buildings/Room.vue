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
</script>

<template>
  <Head title="Rooms" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Building Management" title="Rooms" description="Read, create, update, and delete room records." />

      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <input
            v-model="search"
            type="search"
            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 sm:max-w-sm"
            placeholder="Search room number or status"
          >

          <Link
            href="/dashboard/rooms/create"
            class="inline-flex items-center justify-center rounded-xl bg-blue-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-800"
          >
            Create Room
          </Link>
        </div>

        <div class="mt-5 overflow-x-auto">
          <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
              <tr>
                <th class="px-4 py-3">ID</th>
                <th class="px-4 py-3">Floor ID</th>
                <th class="px-4 py-3">Room Number</th>
                <th class="px-4 py-3">Capacity</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="isLoading">
                <td colspan="6" class="px-4 py-8 text-center text-slate-500">Loading rooms...</td>
              </tr>

              <tr v-else-if="hasLoaded && rooms.length === 0">
                <td colspan="6" class="px-4 py-8 text-center text-slate-500">No rooms found.</td>
              </tr>

              <tr v-for="room in rooms" v-else :key="room.id" class="transition hover:bg-slate-50">
                <td class="px-4 py-3 font-medium text-slate-700">{{ room.id }}</td>
                <td class="px-4 py-3 text-slate-600">{{ room.floor_id ?? '-' }}</td>
                <td class="px-4 py-3 font-semibold text-slate-900">{{ room.room_number }}</td>
                <td class="px-4 py-3 text-slate-600">{{ room.capacity ?? '-' }}</td>
                <td class="px-4 py-3">
                  <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold capitalize text-slate-700">
                    {{ room.status }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex justify-end gap-2">
                    <button
                      type="button"
                      class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                      @click="editRoom(room)"
                    >
                      Update
                    </button>
                    <button
                      type="button"
                      class="rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-red-700"
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

        <div class="mt-5 flex flex-col gap-3 border-t border-slate-100 pt-4 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between">
          <span>Page {{ pagination.current_page }} of {{ pagination.last_page }} - {{ pagination.total }} rooms</span>

          <div class="flex gap-2">
            <button
              type="button"
              :disabled="pagination.current_page <= 1 || isLoading"
              class="rounded-lg border border-slate-300 px-3 py-2 font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
              @click="fetchRooms(pagination.current_page - 1)"
            >
              Previous
            </button>
            <button
              type="button"
              :disabled="pagination.current_page >= pagination.last_page || isLoading"
              class="rounded-lg border border-slate-300 px-3 py-2 font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
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
