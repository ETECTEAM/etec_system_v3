<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { PageHero } from '../../../components/ui/page-hero'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'

const props = defineProps({
  building: {
    type: Object,
    default: null,
  },
  initialStep: {
    type: Number,
    default: 1,
  },
})

const step = ref(props.initialStep)
const floorAddMode = ref('manual') // 'manual' | 'auto'
const roomAddMode = ref('manual') // 'manual' | 'auto'

watch(() => props.initialStep, (newVal) => {
  step.value = newVal
})

// Step 1: Building Details form
const form = useForm({
  name: props.building?.name ?? '',
  code: props.building?.code ?? '',
  description: props.building?.description ?? '',
  redirect_to: 'wizard',
})

watch(() => props.building, (newBuilding) => {
  if (newBuilding) {
    form.name = newBuilding.name ?? ''
    form.code = newBuilding.code ?? ''
    form.description = newBuilding.description ?? ''
  }
}, { deep: true })

function submitBuilding() {
  if (props.building?.id) {
    form.put(`/dashboard/buildings/${props.building.id}`, {
      onSuccess: () => {
        step.value = 2
      }
    })
  } else {
    form.post('/dashboard/buildings', {
      onSuccess: () => {
        // Redirection to Step 2 URL is handled by the backend store method
      }
    })
  }
}

// Step 2: Floor forms & actions
const floorForm = useForm({
  name: '',
  level: '',
})

const autoFloorForm = useForm({
  start_name: '',
  total_floors: 3,
  start_level: null,
})

function submitFloor() {
  if (!props.building?.id) return
  floorForm.post(`/dashboard/buildings/${props.building.id}/floors`, {
    preserveScroll: true,
    onSuccess: () => {
      floorForm.reset()
    }
  })
}

function submitAutoFloor() {
  if (!props.building?.id) return
  autoFloorForm.post(`/dashboard/buildings/${props.building.id}/floors/auto-generate`, {
    preserveScroll: true,
    onSuccess: () => {
      autoFloorForm.reset()
      autoFloorForm.total_floors = 3
    }
  })
}

function deleteFloor(floor) {
  if (!props.building?.id) return
  if (!window.confirm(`Delete floor "${floor.name}" and all rooms inside it?`)) return
  router.delete(`/dashboard/buildings/${props.building.id}/floors/${floor.id}`, {
    preserveScroll: true,
  })
}

const generatedFloors = computed(() => {
  const start = String(autoFloorForm.start_name ?? '').trim()
  const total = Number(autoFloorForm.total_floors ?? 0)

  if (!start || !Number.isInteger(total) || total <= 0) {
    return []
  }

  if (/^[A-Za-z]+$/.test(start)) {
    const base = lettersToNumber(start.toUpperCase())
    return Array.from({ length: total }, (_, index) => ({
      name: numberToLetters(base + index),
      level: null,
    }))
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
    return {
      name: `${prefix}${String(current).padStart(width, '0')}`,
      level: null,
    }
  })
})

function lettersToNumber(letters) {
  return letters.split('').reduce((total, character) => total * 26 + (character.charCodeAt(0) - 64), 0)
}

function numberToLetters(number) {
  let value = number
  let result = ''
  while (value > 0) {
    value -= 1
    result = String.fromCharCode((value % 26) + 65) + result
    value = Math.floor(value / 26)
  }
  return result
}

// Step 3: Room forms & actions
const selectedFloorId = ref(null)

watch(() => props.building?.floors, (floors) => {
  if (floors && floors.length > 0) {
    if (!selectedFloorId.value || !floors.some(f => f.id === selectedFloorId.value)) {
      selectedFloorId.value = floors[0].id
    }
  } else {
    selectedFloorId.value = null
  }
}, { immediate: true })

const selectedFloor = computed(() => {
  return props.building?.floors?.find(f => f.id === selectedFloorId.value) || null
})

const roomForm = useForm({
  floor_id: '',
  room_number: '',
  capacity: '',
  status: 'available',
})

const autoRoomForm = useForm({
  floor_id: '',
  start_room_number: '',
  total_rooms: 3,
  capacity: '',
  status: 'available',
  redirect_back: true,
})

watch(selectedFloorId, (newFloorId) => {
  roomForm.floor_id = newFloorId ?? ''
  autoRoomForm.floor_id = newFloorId ?? ''
}, { immediate: true })

function submitRoom() {
  if (!props.building?.id || !selectedFloorId.value) return
  roomForm.post(`/dashboard/buildings/${props.building.id}/floors/${selectedFloorId.value}/rooms`, {
    preserveScroll: true,
    onSuccess: () => {
      roomForm.reset()
      roomForm.floor_id = selectedFloorId.value
      roomForm.status = 'available'
    }
  })
}

function submitAutoRoom() {
  if (!selectedFloorId.value) return
  autoRoomForm.post('/dashboard/rooms/auto-generate', {
    preserveScroll: true,
    onSuccess: () => {
      autoRoomForm.reset()
      autoRoomForm.floor_id = selectedFloorId.value
      autoRoomForm.status = 'available'
      autoRoomForm.total_rooms = 3
      autoRoomForm.redirect_back = true
    }
  })
}

function deleteRoom(room) {
  if (!props.building?.id || !selectedFloorId.value) return
  if (!window.confirm(`Delete room "${room.room_number}"?`)) return
  router.delete(`/dashboard/buildings/${props.building.id}/floors/${selectedFloorId.value}/rooms/${room.id}`, {
    preserveScroll: true,
  })
}

const generatedRooms = computed(() => {
  const start = String(autoRoomForm.start_room_number ?? '').trim()
  const total = Number(autoRoomForm.total_rooms ?? 0)

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

function statusClass(status) {
  if (status === 'occupied') return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20'
  if (status === 'maintenance') return 'bg-rose-50 text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-500/20'
  return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20'
}

function goToStep(targetStep) {
  if (!props.building?.id) return
  router.visit(`/dashboard/buildings/create?building_id=${props.building.id}&step=${targetStep}`)
}
</script>

<template>
  <Head title="Building Wizard" />

  <DashboardLayout>
    <section class="space-y-6">
      <PageHero 
        eyebrow="Building Creator Wizard" 
        :title="step === 1 ? 'Building Details' : step === 2 ? 'Configure Floors' : 'Configure Rooms'" 
        :description="step === 1 ? 'Configure the basic settings and properties of the building.' : step === 2 ? `Manage or auto-generate floors for '${props.building?.name}'.` : `Manage or auto-generate rooms for '${props.building?.name}'.`" 
      />

      <!-- Stepper Progress Bar -->
      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 max-w-4xl mx-auto">

          <!-- Step 1 Indicator -->
          <button type="button" @click="goToStep(1)" :disabled="!props.building?.id" class="flex items-center gap-3 group text-left outline-none disabled:cursor-default">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 transition duration-200" :class="[
              step === 1 ? 'border-blue-600 bg-blue-600 text-white font-semibold' :
              props.building?.id ? 'border-blue-600 bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white dark:bg-blue-500/10 dark:text-blue-400' :
              'border-slate-300 text-slate-400 dark:border-gray-700 dark:text-gray-500'
            ]">
              <svg v-if="props.building?.id && step !== 1" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
              <span v-else>1</span>
            </div>
            <div>
              <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500">Step 1</p>
              <p class="text-sm font-bold transition" :class="step === 1 ? 'text-blue-600 dark:text-blue-400' : 'text-slate-700 dark:text-gray-300'">Building Details</p>
            </div>
          </button>

          <!-- Line Divider 1 -->
          <div class="hidden md:block h-0.5 flex-1 bg-slate-200 relative dark:bg-gray-800">
            <div class="absolute inset-0 bg-blue-600 transition-all duration-300" :style="{ width: props.building?.id ? '100%' : '0%' }"></div>
          </div>

          <!-- Step 2 Indicator -->
          <button type="button" @click="goToStep(2)" :disabled="!props.building?.id" class="flex items-center gap-3 group text-left outline-none disabled:cursor-default">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 transition duration-200" :class="[
              step === 2 ? 'border-blue-600 bg-blue-600 text-white font-semibold' :
              props.building?.floors?.length > 0 && props.building?.id ? 'border-blue-600 bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white dark:bg-blue-500/10 dark:text-blue-400' :
              'border-slate-300 text-slate-400 dark:border-gray-700 dark:text-gray-500'
            ]">
              <svg v-if="props.building?.floors?.length > 0 && step !== 2" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
              <span v-else>2</span>
            </div>
            <div>
              <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500">Step 2</p>
              <p class="text-sm font-bold transition" :class="step === 2 ? 'text-blue-600 dark:text-blue-400' : 'text-slate-700 dark:text-gray-300'">Floor Configuration</p>
            </div>
          </button>

          <!-- Line Divider 2 -->
          <div class="hidden md:block h-0.5 flex-1 bg-slate-200 relative dark:bg-gray-800">
            <div class="absolute inset-0 bg-blue-600 transition-all duration-300" :style="{ width: props.building?.floors?.length > 0 ? '100%' : '0%' }"></div>
          </div>

          <!-- Step 3 Indicator -->
          <button type="button" @click="goToStep(3)" :disabled="!props.building?.id || !props.building?.floors?.length" class="flex items-center gap-3 group text-left outline-none disabled:cursor-default">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 transition duration-200" :class="[
              step === 3 ? 'border-blue-600 bg-blue-600 text-white font-semibold' :
              'border-slate-300 text-slate-400 dark:border-gray-700 dark:text-gray-500'
            ]">
              <span>3</span>
            </div>
            <div>
              <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500">Step 3</p>
              <p class="text-sm font-bold transition" :class="step === 3 ? 'text-blue-600 dark:text-blue-400' : 'text-slate-700 dark:text-gray-300'">Room Configuration</p>
            </div>
          </button>

        </div>
      </div>

      <!-- Step 1: Building Details -->
      <div v-if="step === 1" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-gray-800 dark:bg-gray-900">
        <form class="max-w-xl mx-auto space-y-5" @submit.prevent="submitBuilding">
          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700 font-sans dark:text-gray-300">Building Name</span>
            <input v-model="form.name" type="text" required placeholder="e.g. Science Block" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20">
            <span v-if="form.errors.name" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.name }}</span>
          </label>

          <div class="flex justify-between items-center mt-6">
            <Link href="/dashboard/buildings" class="rounded-xl border border-slate-300 px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
              Cancel
            </Link>
            <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500">
              {{ form.processing ? 'Processing...' : props.building?.id ? 'Save & Continue' : 'Create & Continue' }}
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>
        </form>
      </div>

      <!-- Step 2: Floor Management -->
      <div v-if="step === 2" class="grid lg:grid-cols-3 gap-6">
        
        <!-- Floors List Column -->
        <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <h3 class="text-lg font-bold text-slate-900 mb-4 dark:text-gray-100">Floors of {{ props.building?.name }}</h3>

          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider dark:border-gray-800 dark:text-gray-400">
                  <th class="py-3 px-4">Floor Name</th>
                  <th class="py-3 px-4">Level</th>
                  <th class="py-3 px-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-sm dark:divide-gray-800">
                <tr v-for="floor in props.building?.floors" :key="floor.id" class="hover:bg-slate-50 transition dark:hover:bg-gray-800">
                  <td class="py-3 px-4 font-semibold text-slate-900 dark:text-gray-100">{{ floor.name }}</td>
                  <td class="py-3 px-4">
                    <span class="rounded bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-gray-800 dark:text-gray-300">
                      Lvl {{ floor.level !== null ? floor.level : '-' }}
                    </span>
                  </td>
                  <td class="py-3 px-4 text-right">
                    <button type="button" @click="deleteFloor(floor)" class="text-rose-600 hover:text-rose-900 font-semibold transition text-xs dark:text-rose-400 dark:hover:text-rose-300">
                      Delete
                    </button>
                  </td>
                </tr>
                <tr v-if="!props.building?.floors?.length">
                  <td colspan="4" class="py-8 text-center text-slate-400 dark:text-gray-500">
                    No floors created yet. Use the configuration form to generate floors.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="flex justify-between items-center mt-8 pt-4 border-t border-slate-100 dark:border-gray-800">
            <button type="button" @click="goToStep(1)" class="rounded-xl border border-slate-300 px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 flex items-center gap-2 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
              </svg>
              Back to Building Details
            </button>

            <button type="button" @click="goToStep(3)" :disabled="!props.building?.floors?.length" class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50 flex items-center gap-2 dark:bg-blue-600 dark:hover:bg-blue-500">
              Continue to Rooms
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Floor Creation Form Column -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden flex flex-col dark:border-gray-800 dark:bg-gray-900">
          <div class="flex border-b border-slate-200 bg-slate-50 dark:border-gray-800 dark:bg-gray-800/40">
            <button type="button" @click="floorAddMode = 'manual'" class="flex-1 py-4 text-center text-sm font-semibold transition" :class="floorAddMode === 'manual' ? 'border-b-2 border-blue-600 text-blue-600 bg-white dark:bg-gray-900 dark:text-blue-400' : 'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200'">
              Manual Floor
            </button>
            <button type="button" @click="floorAddMode = 'auto'" class="flex-1 py-4 text-center text-sm font-semibold transition" :class="floorAddMode === 'auto' ? 'border-b-2 border-blue-600 text-blue-600 bg-white dark:bg-gray-900 dark:text-blue-400' : 'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200'">
              Auto Floor Sequence
            </button>
          </div>

          <div class="p-6 flex-1 flex flex-col justify-between">
            <!-- Manual Form -->
            <form v-if="floorAddMode === 'manual'" @submit.prevent="submitFloor" class="space-y-4">
              <label class="block">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Floor Name</span>
                <input v-model="floorForm.name" type="text" placeholder="e.g. Ground Floor" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500">
                <span v-if="floorForm.errors.name" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ floorForm.errors.name }}</span>
              </label>

              <label class="block">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Level (Numerical)</span>
                <input v-model="floorForm.level" type="number" placeholder="e.g. 0" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500">
                <span v-if="floorForm.errors.level" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ floorForm.errors.level }}</span>
              </label>

              <button type="submit" :disabled="floorForm.processing" class="w-full rounded-xl bg-blue-600 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60 dark:bg-blue-600 dark:hover:bg-blue-500">
                {{ floorForm.processing ? 'Creating...' : 'Create Floor' }}
              </button>
            </form>

            <!-- Auto Form -->
            <form v-else @submit.prevent="submitAutoFloor" class="space-y-4">
              <label class="block">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Start Floor Name</span>
                <input v-model="autoFloorForm.start_name" type="text" placeholder="e.g. Floor 01 or A" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500">
                <span v-if="autoFloorForm.errors.start_name" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ autoFloorForm.errors.start_name }}</span>
              </label>

              <label class="block">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">How Many Floors</span>
                <input v-model="autoFloorForm.total_floors" type="number" min="1" max="100" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500">
                <span v-if="autoFloorForm.errors.total_floors" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ autoFloorForm.errors.total_floors }}</span>
              </label>

              <!-- Floor Auto Preview -->
              <div v-if="generatedFloors.length" class="rounded-xl bg-blue-50 border border-blue-100 p-3.5 space-y-2 dark:bg-blue-500/10 dark:border-blue-500/20">
                <p class="text-xs font-bold uppercase text-blue-800 dark:text-blue-400">Sequence Preview ({{ generatedFloors.length }} floors):</p>
                <div class="flex flex-wrap gap-1.5 max-h-24 overflow-y-auto">
                  <span v-for="fl in generatedFloors" :key="fl.name" class="rounded-full bg-white px-2 py-0.5 text-xs text-blue-700 border border-blue-200 dark:bg-gray-900 dark:text-blue-400 dark:border-blue-500/20">
                    {{ fl.name }}
                  </span>
                </div>
              </div>

              <button type="submit" :disabled="autoFloorForm.processing || !generatedFloors.length" class="w-full rounded-xl bg-blue-600 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60 dark:bg-blue-600 dark:hover:bg-blue-500">
                {{ autoFloorForm.processing ? 'Generating...' : 'Generate Floors' }}
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Step 3: Room Management -->
      <div v-if="step === 3" class="grid lg:grid-cols-3 gap-6">
        
        <!-- Rooms list column -->
        <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <h3 class="text-lg font-bold text-slate-900 dark:text-gray-100">Manage Rooms</h3>

            <!-- Floor Selection Dropdown -->
            <label class="flex items-center gap-2 shrink-0">
              <span class="text-sm text-slate-500 font-semibold dark:text-gray-400">Active Floor:</span>
              <select v-model="selectedFloorId" class="rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500">
                <option v-for="floor in props.building?.floors" :key="floor.id" :value="floor.id">
                  {{ floor.name }} ({{ floor.rooms?.length || 0 }} rooms)
                </option>
              </select>
            </label>
          </div>

          <div v-if="selectedFloor" class="space-y-4">
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider dark:border-gray-800 dark:text-gray-400">
                    <th class="py-3 px-4">Room Number</th>
                    <th class="py-3 px-4">Capacity</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm dark:divide-gray-800">
                  <tr v-for="room in selectedFloor.rooms" :key="room.id" class="hover:bg-slate-50 transition dark:hover:bg-gray-800">
                    <td class="py-3 px-4 font-semibold text-slate-900 dark:text-gray-100">{{ room.room_number }}</td>
                    <td class="py-3 px-4 text-slate-600 dark:text-gray-300">{{ room.capacity ? `${room.capacity} people` : '-' }}</td>
                    <td class="py-3 px-4">
                      <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wider" :class="statusClass(room.status)">
                        {{ room.status }}
                      </span>
                    </td>
                    <td class="py-3 px-4 text-right">
                      <button type="button" @click="deleteRoom(room)" class="text-rose-600 hover:text-rose-900 font-semibold transition text-xs dark:text-rose-400 dark:hover:text-rose-300">
                        Delete
                      </button>
                    </td>
                  </tr>
                  <tr v-if="!selectedFloor.rooms?.length">
                    <td colspan="4" class="py-8 text-center text-slate-400 dark:text-gray-500">
                      No rooms added on this floor yet.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div v-else class="py-12 text-center text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200 dark:text-gray-500 dark:bg-gray-800/40 dark:border-gray-700">
            Please configure and select a floor first.
          </div>

          <div class="flex justify-between items-center mt-8 pt-4 border-t border-slate-100 dark:border-gray-800">
            <button type="button" @click="goToStep(2)" class="rounded-xl border border-slate-300 px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 flex items-center gap-2 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
              </svg>
              Back to Floors
            </button>

            <Link href="/dashboard/buildings" class="rounded-xl bg-blue-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 flex items-center gap-2 shadow-sm shadow-blue-100 dark:bg-blue-600 dark:hover:bg-blue-500 dark:shadow-none">
              Finish Setup
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
            </Link>
          </div>
        </div>

        <!-- Room Creation Form Column -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden flex flex-col dark:border-gray-800 dark:bg-gray-900">
          <div class="flex border-b border-slate-200 bg-slate-50 dark:border-gray-800 dark:bg-gray-800/40">
            <button type="button" @click="roomAddMode = 'manual'" class="flex-1 py-4 text-center text-sm font-semibold transition" :class="roomAddMode === 'manual' ? 'border-b-2 border-blue-600 text-blue-600 bg-white dark:bg-gray-900 dark:text-blue-400' : 'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200'" :disabled="!selectedFloorId">
              Manual Room
            </button>
            <button type="button" @click="roomAddMode = 'auto'" class="flex-1 py-4 text-center text-sm font-semibold transition" :class="roomAddMode === 'auto' ? 'border-b-2 border-blue-600 text-blue-600 bg-white dark:bg-gray-900 dark:text-blue-400' : 'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200'" :disabled="!selectedFloorId">
              Auto Room Sequence
            </button>
          </div>

          <div class="p-6 flex-1 flex flex-col justify-between">
            <div v-if="!selectedFloorId" class="text-center py-12 text-sm text-slate-400 dark:text-gray-500">
              Select or add a floor before adding rooms.
            </div>

            <!-- Manual Room Form -->
            <form v-else-if="roomAddMode === 'manual'" @submit.prevent="submitRoom" class="space-y-4">
              <label class="block">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Room Number</span>
                <input v-model="roomForm.room_number" type="text" placeholder="e.g. 101" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500">
                <span v-if="roomForm.errors.room_number" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ roomForm.errors.room_number }}</span>
              </label>

              <label class="block">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Capacity</span>
                <input v-model="roomForm.capacity" type="number" placeholder="Optional" min="1" @keydown="['-', 'e', 'E', '+'].includes($event.key) && $event.preventDefault()" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500">
                <span v-if="roomForm.errors.capacity" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ roomForm.errors.capacity }}</span>
              </label>

              <label class="block">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Status</span>
                <select v-model="roomForm.status" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500">
                  <option value="available">Available</option>
                  <option value="occupied">Occupied</option>
                  <option value="maintenance">Maintenance</option>
                </select>
                <span v-if="roomForm.errors.status" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ roomForm.errors.status }}</span>
              </label>

              <button type="submit" :disabled="roomForm.processing" class="w-full rounded-xl bg-blue-600 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60 dark:bg-blue-600 dark:hover:bg-blue-500">
                {{ roomForm.processing ? 'Creating...' : 'Create Room' }}
              </button>
            </form>

            <!-- Auto Room Form -->
            <form v-else-if="roomAddMode === 'auto'" @submit.prevent="submitAutoRoom" class="space-y-4">
              <label class="block">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Start Room Number</span>
                <input v-model="autoRoomForm.start_room_number" type="text" placeholder="e.g. 101" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500">
                <span v-if="autoRoomForm.errors.start_room_number" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ autoRoomForm.errors.start_room_number }}</span>
              </label>

              <label class="block">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">How Many Rooms</span>
                <input v-model="autoRoomForm.total_rooms" type="number" min="1" max="200" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500">
                <span v-if="autoRoomForm.errors.total_rooms" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ autoRoomForm.errors.total_rooms }}</span>
              </label>

              <label class="block">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Capacity</span>
                <input v-model="autoRoomForm.capacity" type="number" placeholder="Optional" min="1" @keydown="['-', 'e', 'E', '+'].includes($event.key) && $event.preventDefault()" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500">
                <span v-if="autoRoomForm.errors.capacity" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ autoRoomForm.errors.capacity }}</span>
              </label>

              <label class="block">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Status</span>
                <select v-model="autoRoomForm.status" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500">
                  <option value="available">Available</option>
                  <option value="occupied">Occupied</option>
                  <option value="maintenance">Maintenance</option>
                </select>
                <span v-if="autoRoomForm.errors.status" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ autoRoomForm.errors.status }}</span>
              </label>

              <!-- Room Auto Preview -->
              <div v-if="generatedRooms.length" class="rounded-xl bg-blue-50 border border-blue-100 p-3.5 space-y-2 dark:bg-blue-500/10 dark:border-blue-500/20">
                <p class="text-xs font-bold uppercase text-blue-800 dark:text-blue-400">Sequence Preview ({{ generatedRooms.length }} rooms):</p>
                <div class="flex flex-wrap gap-1.5 max-h-24 overflow-y-auto">
                  <span v-for="rm in generatedRooms" :key="rm" class="rounded-full bg-white px-2 py-0.5 text-xs text-blue-700 border border-blue-200 dark:bg-gray-900 dark:text-blue-400 dark:border-blue-500/20">
                    {{ rm }}
                  </span>
                </div>
              </div>

              <button type="submit" :disabled="autoRoomForm.processing || !generatedRooms.length" class="w-full rounded-xl bg-blue-600 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60 dark:bg-blue-600 dark:hover:bg-blue-500">
                {{ autoRoomForm.processing ? 'Generating...' : 'Generate Rooms' }}
              </button>
            </form>
          </div>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
