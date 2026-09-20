<script setup>
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Breadcrumbs } from '../../../../components/ui/breadcrumbs'
import { PageHero } from '../../../../components/ui/page-hero'
import DashboardLayout from '../../../../layouts/DashboardLayout.vue'

const props = defineProps({
  classTypes: { type: Array, default: () => [] },
  classTypeId: { type: Number, default: null },
  overview: { type: Object, default: () => ({ terms: [], unassigned: [], roomCount: 0 }) },
})

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Room Availability', current: true },
]

// Slots whose room list is open, keyed "termId:timeId".
const expanded = ref({})

const hasSlots = computed(() => props.overview.terms.some((term) => term.times.length > 0))

function slotKey(term, time) {
  return `${term.term_id}:${time.time_id}`
}

function toggle(term, time) {
  const key = slotKey(term, time)
  expanded.value[key] = !expanded.value[key]
}

function selectClassType(id) {
  if (id === props.classTypeId) return

  expanded.value = {}
  router.get('/dashboard/room-availability', { class_type_id: id }, { preserveScroll: true, preserveState: true, replace: true })
}

// Green while most rooms are free, amber when few remain, red when none.
function slotTone(time) {
  if (time.free === 0) return 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300'
  if (time.free <= Math.ceil((time.free + time.busy) * 0.25)) return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300'

  return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300'
}
</script>

<template>
  <Head :title="$t('Room Availability')" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Building Management" :title="$t('Room Availability')" :description="$t('See which rooms are free or taken at each class time, following the class schedule.')" />

      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex flex-wrap gap-2">
            <button v-for="type in classTypes" :key="type.id" type="button" :class="['rounded-xl border px-4 py-2 text-sm font-semibold transition', type.id === classTypeId ? 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-400' : 'border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800']" @click="selectClassType(type.id)">{{ type.name }}</button>
          </div>
          <p class="text-sm text-slate-500 dark:text-gray-400">{{ $t(':count rooms can be booked', { count: overview.roomCount }) }}</p>
        </div>

        <div class="mt-4 flex flex-wrap gap-4 text-xs text-slate-500 dark:text-gray-400">
          <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>{{ $t('Free') }}</span>
          <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>{{ $t('Taken by a class') }}</span>
          <span>{{ $t('Rooms under maintenance or closed are not counted.') }}</span>
        </div>
      </div>

      <div v-if="!hasSlots" class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-sm text-slate-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
        {{ $t('No schedule is set up for this class type yet. Add terms and times under Schedule Management.') }}
      </div>

      <div v-for="term in overview.terms" :key="term.term_id" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <h2 class="text-base font-semibold text-slate-900 dark:text-gray-100">{{ term.term_name }}</h2>

        <ul class="mt-4 space-y-2">
          <li v-for="time in term.times" :key="time.time_id" class="rounded-xl border border-slate-200 dark:border-gray-700">
            <button type="button" class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left" :aria-expanded="Boolean(expanded[slotKey(term, time)])" @click="toggle(term, time)">
              <span class="text-sm font-semibold text-slate-800 dark:text-gray-100">{{ time.time_name }}</span>
              <span :class="['rounded-full border px-3 py-1 text-xs font-semibold', slotTone(time)]">{{ $t(':free free · :busy taken', { free: time.free, busy: time.busy }) }}</span>
            </button>

            <div v-if="expanded[slotKey(term, time)]" class="grid gap-2 border-t border-slate-200 p-4 sm:grid-cols-2 lg:grid-cols-4 dark:border-gray-700">
              <div v-for="room in time.rooms" :key="room.id" :class="['rounded-lg border px-3 py-2 text-xs', room.busy ? 'border-red-200 bg-red-50 text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200' : 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200']">
                <p class="font-semibold">{{ room.room_number }}<span v-if="room.capacity" class="ml-1 font-normal opacity-70">· {{ room.capacity }}</span></p>
                <p v-if="room.location" class="opacity-70">{{ room.location }}</p>
                <p v-if="room.busy" class="mt-1 font-medium">{{ room.class_title }}<span v-if="room.teacher"> · {{ room.teacher }}</span></p>
              </div>
              <p v-if="!time.rooms.length" class="text-sm text-slate-500 dark:text-gray-400">{{ $t('No rooms are set up yet.') }}</p>
            </div>
          </li>
        </ul>
      </div>

      <div v-if="overview.unassigned.length" class="rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-500/30 dark:bg-amber-500/10">
        <h2 class="text-base font-semibold text-amber-900 dark:text-amber-200">{{ $t('Classes without a room yet') }}</h2>
        <p class="mt-1 text-sm text-amber-800 dark:text-amber-300">{{ $t('These in-person classes have no room, so they are not counted above. The instructor still has to pick one.') }}</p>
        <ul class="mt-3 space-y-1 text-sm text-amber-900 dark:text-amber-200">
          <li v-for="item in overview.unassigned" :key="item.id">{{ item.title }} · {{ item.term_name }} · {{ item.time_name }}<span v-if="item.teacher"> · {{ item.teacher }}</span></li>
        </ul>
      </div>
    </section>
  </DashboardLayout>
</template>
