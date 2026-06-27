<script setup>
import { computed, ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'

const mode = ref('single')

const form = useForm({
  floor_id: '',
  room_number: '',
  capacity: '',
  status: 'available',
})

const autoForm = useForm({
  floor_id: '',
  start_room_number: '',
  total_rooms: 3,
  capacity: '',
  status: 'available',
})

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Rooms', href: '/dashboard/rooms' },
  { label: 'Create', current: true },
]

const generatedRooms = computed(() => {
  const start = String(autoForm.start_room_number ?? '').trim()
  const total = Number(autoForm.total_rooms ?? 0)

  if (!start || !Number.isInteger(total) || total <= 0) {
    return []
  }

  const match = start.match(/^(.*?)(\d+)$/)
  if (!match) {
    return []
  }

  const prefix = match[1] ?? ''
  const base = Number(match[2] ?? 0)
  const width = String(match[2] ?? '').length

  return Array.from({ length: total }, (_, index) => {
    const current = base + index
    return `${prefix}${String(current).padStart(width, '0')}`
  })
})

function submit() {
  form.post('/dashboard/rooms')
}

function submitAuto() {
  autoForm.post('/dashboard/rooms/auto-generate')
}
</script>

<template>
  <Head title="Create Room" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Building Management" title="Create Room" description="Create one room manually or generate a sequence automatically." />

      <div class="flex flex-wrap gap-3">
        <button
          type="button"
          :class="mode === 'single' ? 'bg-blue-600 text-white' : 'border border-slate-300 bg-white text-slate-700'"
          class="rounded-xl px-5 py-3 text-sm font-semibold transition"
          @click="mode = 'single'"
        >
          Single Room
        </button>
        <button
          type="button"
          :class="mode === 'auto' ? 'bg-blue-600 text-white' : 'border border-slate-300 bg-white text-slate-700'"
          class="rounded-xl px-5 py-3 text-sm font-semibold transition"
          @click="mode = 'auto'"
        >
          Auto Room
        </button>
      </div>

      <div v-if="mode === 'single'" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Floor ID</span>
            <input
              v-model="form.floor_id"
              type="number"
              min="1"
              class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
              placeholder="Optional"
            >
            <span v-if="form.errors.floor_id" class="mt-1 block text-xs text-red-600">{{ form.errors.floor_id }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Room Number</span>
            <input
              v-model="form.room_number"
              type="text"
              class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
              placeholder="Example: A-101"
            >
            <span v-if="form.errors.room_number" class="mt-1 block text-xs text-red-600">{{ form.errors.room_number }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Capacity</span>
            <input
              v-model="form.capacity"
              type="number"
              min="1"
              @keydown="['-', 'e', 'E', '+'].includes($event.key) && $event.preventDefault()"
              class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
              placeholder="Optional"
            >
            <span v-if="form.errors.capacity" class="mt-1 block text-xs text-red-600">{{ form.errors.capacity }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Status</span>
            <select
              v-model="form.status"
              class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
            >
              <option value="available">Available</option>
              <option value="occupied">Occupied</option>
              <option value="maintenance">Maintenance</option>
            </select>
            <span v-if="form.errors.status" class="mt-1 block text-xs text-red-600">{{ form.errors.status }}</span>
          </label>

          <div class="flex justify-end gap-3 sm:col-span-2">
            <Link
              href="/dashboard/rooms"
              class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
              Cancel
            </Link>

            <button
              type="submit"
              :disabled="form.processing"
              class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70"
            >
              {{ form.processing ? 'Creating...' : 'Create Room' }}
            </button>
          </div>
        </form>
      </div>

      <div v-else class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submitAuto">
          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Floor ID</span>
            <input
              v-model="autoForm.floor_id"
              type="number"
              min="1"
              class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
              placeholder="Optional"
            >
            <span v-if="autoForm.errors.floor_id" class="mt-1 block text-xs text-red-600">{{ autoForm.errors.floor_id }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Start Room</span>
            <input
              v-model="autoForm.start_room_number"
              type="text"
              class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
              placeholder="Example: A-101"
            >
            <span v-if="autoForm.errors.start_room_number" class="mt-1 block text-xs text-red-600">{{ autoForm.errors.start_room_number }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700">How Many Rooms</span>
            <input
              v-model="autoForm.total_rooms"
              type="number"
              min="1"
              max="200"
              class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
            >
            <span v-if="autoForm.errors.total_rooms" class="mt-1 block text-xs text-red-600">{{ autoForm.errors.total_rooms }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Capacity</span>
            <input
              v-model="autoForm.capacity"
              type="number"
              min="1"
              @keydown="['-', 'e', 'E', '+'].includes($event.key) && $event.preventDefault()"
              class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
              placeholder="Optional"
            >
            <span v-if="autoForm.errors.capacity" class="mt-1 block text-xs text-red-600">{{ autoForm.capacity }}</span>
          </label>

          <label class="block sm:col-span-2">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Status</span>
            <select
              v-model="autoForm.status"
              class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
            >
              <option value="available">Available</option>
              <option value="occupied">Occupied</option>
              <option value="maintenance">Maintenance</option>
            </select>
            <span v-if="autoForm.errors.status" class="mt-1 block text-xs text-red-600">{{ autoForm.errors.status }}</span>
          </label>

          <div class="sm:col-span-2 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
            <div class="flex items-center justify-between gap-3">
              <div>
                <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Preview</h3>
                <p class="mt-1 text-sm text-slate-600">Generated room numbers will auto increment from your starting room.</p>
              </div>
              <span class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-slate-700 ring-1 ring-slate-200">
                {{ generatedRooms.length }} rooms
              </span>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
              <span
                v-for="room in generatedRooms"
                :key="room"
                class="rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700 ring-1 ring-blue-200"
              >
                {{ room }}
              </span>
              <span v-if="generatedRooms.length === 0" class="text-sm text-slate-500">
                Enter a start room like `A-101` and the number of rooms to preview the sequence.
              </span>
            </div>
          </div>

          <div class="flex justify-end gap-3 sm:col-span-2">
            <Link
              href="/dashboard/rooms"
              class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
              Cancel
            </Link>

            <button
              type="submit"
              :disabled="autoForm.processing || generatedRooms.length === 0"
              class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70"
            >
              {{ autoForm.processing ? 'Generating...' : 'Create Auto Rooms' }}
            </button>
          </div>
        </form>
      </div>
    </section>
  </DashboardLayout>
</template>
