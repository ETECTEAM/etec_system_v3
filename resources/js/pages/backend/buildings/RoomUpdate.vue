<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'

const page = usePage()
const room = page.props.room ?? {}

const form = useForm({
  floor_id: room.floor_id ?? '',
  room_number: room.room_number ?? '',
  capacity: room.capacity ?? '',
  status: room.status ?? 'available',
})

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Rooms', href: '/dashboard/rooms' },
  { label: 'Update', current: true },
]

function submit() {
  form.put(`/dashboard/rooms/${room.id}`)
}
</script>

<template>
  <Head :title="`Update Room - ${room.room_number ?? 'Room'}`" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Building Management" title="Update Room" description="Edit room details and save changes." />

      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
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
              placeholder="Example: A101"
            >
            <span v-if="form.errors.room_number" class="mt-1 block text-xs text-red-600">{{ form.errors.room_number }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Capacity</span>
            <input
              v-model="form.capacity"
              type="number"
              min="1"
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
              {{ form.processing ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </form>
      </div>
    </section>
  </DashboardLayout>
</template>
