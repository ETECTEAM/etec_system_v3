<script setup>
import { computed, ref, onMounted, watch } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import { Card } from "../../../components/ui/card";
import { PageHero } from "../../../components/ui/page-hero";
import { Breadcrumbs } from "../../../components/ui/breadcrumbs";
import DashboardLayout from "../../../layouts/DashboardLayout.vue";

const props = defineProps({
    buildings: {
        type: Array,
        default: () => [],
    },
});

const breadcrumbItems = [
    { label: "Dashboard", href: "/dashboard" },
    { label: "Buildings", href: "/dashboard/buildings" },
    { label: "Filter", current: true },
];

const selectedBuildingId = ref("");
const selectedFloorId = ref("");
const selectedRoomId = ref("");

// Computed list of floors based on selected building
const floors = computed(() => {
    if (!selectedBuildingId.value) return [];
    const building = props.buildings.find(b => b.id === Number(selectedBuildingId.value));
    return building ? building.floors : [];
});

// Computed list of rooms based on selected floor
const rooms = computed(() => {
    if (!selectedFloorId.value) return [];
    const floor = floors.value.find(f => f.id === Number(selectedFloorId.value));
    return floor ? floor.rooms : [];
});

// Computed full selected entities
const selectedBuilding = computed(() => {
    return props.buildings.find(b => b.id === Number(selectedBuildingId.value)) || null;
});

const selectedFloor = computed(() => {
    return floors.value.find(f => f.id === Number(selectedFloorId.value)) || null;
});

const selectedRoom = computed(() => {
    return rooms.value.find(r => r.id === Number(selectedRoomId.value)) || null;
});

// Reset logic
function resetFilters() {
    selectedBuildingId.value = "";
    selectedFloorId.value = "";
    selectedRoomId.value = "";
    updateUrlParams();
}

// Update URL parameters
function updateUrlParams() {
    const url = new URL(window.location.href);
    if (selectedBuildingId.value) {
        url.searchParams.set("building_id", selectedBuildingId.value);
    } else {
        url.searchParams.delete("building_id");
    }
    if (selectedFloorId.value) {
        url.searchParams.set("floor_id", selectedFloorId.value);
    } else {
        url.searchParams.delete("floor_id");
    }
    if (selectedRoomId.value) {
        url.searchParams.set("room_id", selectedRoomId.value);
    } else {
        url.searchParams.delete("room_id");
    }
    window.history.replaceState({}, "", url.toString());
}

// Watchers
watch(selectedBuildingId, () => {
    selectedFloorId.value = "";
    selectedRoomId.value = "";
    updateUrlParams();
});

watch(selectedFloorId, () => {
    selectedRoomId.value = "";
    updateUrlParams();
});

watch(selectedRoomId, () => {
    updateUrlParams();
});

// Load from URL parameters on mount
onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const bId = params.get("building_id");
    const fId = params.get("floor_id");
    const rId = params.get("room_id");

    if (bId) {
        selectedBuildingId.value = bId;
        // Wait for floors computed property to update, then set floor if exists
        setTimeout(() => {
            if (fId && floors.value.some(f => f.id === Number(fId))) {
                selectedFloorId.value = fId;
                // Wait for rooms computed property to update, then set room if exists
                setTimeout(() => {
                    if (rId && rooms.value.some(r => r.id === Number(rId))) {
                        selectedRoomId.value = rId;
                    }
                }, 50);
            }
        }, 50);
    }
});

function statusClass(status) {
    if (status === "occupied")
        return "bg-amber-50 text-amber-700 ring-1 ring-amber-200";
    if (status === "maintenance")
        return "bg-rose-50 text-rose-700 ring-1 ring-rose-200";

    return "bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200";
}
</script>

<template>
    <Head title="Filter Buildings" />

    <DashboardLayout>
        <section class="space-y-6">
            <Breadcrumbs :items="breadcrumbItems" />

            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <PageHero
                    eyebrow="Building Management"
                    title="Filter Buildings"
                    description="Drill down to find specific rooms, floors, and buildings across the campus."
                />
                
                <Link
                    href="/dashboard/buildings"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    Back to Buildings
                </Link>
            </div>

            <!-- Dropdown Filter Section -->
            <Card padding="p-6">
                <div class="grid gap-6 md:grid-cols-4 items-end">
                    <!-- Building Dropdown -->
                    <div class="space-y-2">
                        <label for="building-filter" class="block text-sm font-semibold tracking-wide text-slate-600">
                            Building
                        </label>
                        <div class="relative">
                            <select
                                id="building-filter"
                                v-model="selectedBuildingId"
                                class="w-full appearance-none rounded-xl border border-slate-300 bg-white px-4 py-3 pr-10 text-sm font-medium text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            >
                                <option value="">All Buildings</option>
                                <option
                                    v-for="b in props.buildings"
                                    :key="b.id"
                                    :value="b.id"
                                >
                                    {{ b.name }} ({{ b.code || 'No Code' }})
                                </option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Floor Dropdown -->
                    <div class="space-y-2">
                        <label for="floor-filter" class="block text-sm font-semibold tracking-wide text-slate-600">
                            Floor
                        </label>
                        <div class="relative">
                            <select
                                id="floor-filter"
                                v-model="selectedFloorId"
                                :disabled="!selectedBuildingId"
                                class="w-full appearance-none rounded-xl border border-slate-300 bg-white px-4 py-3 pr-10 text-sm font-medium text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400"
                            >
                                <option value="">All Floors</option>
                                <option
                                    v-for="f in floors"
                                    :key="f.id"
                                    :value="f.id"
                                >
                                    {{ f.name }} (Level {{ f.level ?? 'N/A' }})
                                </option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Room Dropdown -->
                    <div class="space-y-2">
                        <label for="room-filter" class="block text-sm font-semibold tracking-wide text-slate-600">
                            Room
                        </label>
                        <div class="relative">
                            <select
                                id="room-filter"
                                v-model="selectedRoomId"
                                :disabled="!selectedFloorId"
                                class="w-full appearance-none rounded-xl border border-slate-300 bg-white px-4 py-3 pr-10 text-sm font-medium text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400"
                            >
                                <option value="">All Rooms</option>
                                <option
                                    v-for="r in rooms"
                                    :key="r.id"
                                    :value="r.id"
                                >
                                    Room {{ r.room_number }}
                                </option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Reset Filters Button -->
                    <div>
                        <button
                            id="reset-filter-btn"
                            type="button"
                            @click="resetFilters"
                            class="w-full inline-flex items-center justify-center rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-3 text-sm font-semibold transition focus:outline-none"
                        >
                            Reset Filters
                        </button>
                    </div>
                </div>
            </Card>

            <!-- Results Section -->
            <div class="space-y-6">
                <!-- Case 1: Room Selected -->
                <div v-if="selectedRoom" class="space-y-4">
                    <h3 class="text-lg font-bold text-slate-800 uppercase tracking-wider">Filtered Room Result</h3>
                    <Card cardClass="overflow-hidden border-blue-200">
                        <div class="bg-gradient-to-r from-blue-50 to-white px-6 py-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <span class="text-xs font-bold text-blue-600 uppercase tracking-widest">Selected Room</span>
                                <h2 class="text-3xl font-extrabold text-slate-900 mt-1">Room {{ selectedRoom.room_number }}</h2>
                            </div>
                            <div>
                                <span :class="['rounded-full px-4 py-1.5 text-sm font-semibold uppercase tracking-wider', statusClass(selectedRoom.status)]">
                                    {{ selectedRoom.status }}
                                </span>
                            </div>
                        </div>
                        <div class="grid gap-6 p-6 sm:grid-cols-3">
                            <div class="space-y-1">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Building</p>
                                <p class="text-lg font-bold text-slate-800">{{ selectedBuilding?.name }}</p>
                                <p class="text-sm text-slate-500">{{ selectedBuilding?.code ? `Code: ${selectedBuilding.code}` : '' }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Floor</p>
                                <p class="text-lg font-bold text-slate-800">{{ selectedFloor?.name }}</p>
                                <p class="text-sm text-slate-500">Level: {{ selectedFloor?.level ?? 'N/A' }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Capacity</p>
                                <p class="text-lg font-bold text-slate-800">{{ selectedRoom.capacity }} Persons</p>
                                <p class="text-sm text-slate-500">Max comfortable occupancy</p>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Case 2: Floor Selected (No Room Selected) -->
                <div v-else-if="selectedFloor" class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <h3 class="text-lg font-bold text-slate-800 uppercase tracking-wider">
                            Rooms on {{ selectedFloor.name }} (Level {{ selectedFloor.level ?? 'N/A' }})
                        </h3>
                        <span class="text-sm font-semibold text-slate-500 bg-slate-100 rounded-full px-3 py-1">
                            {{ selectedFloor.rooms?.length || 0 }} Rooms Found
                        </span>
                    </div>

                    <Card padding="p-0" cardClass="overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th class="px-6 py-4">Room Number</th>
                                        <th class="px-6 py-4">Capacity</th>
                                        <th class="px-6 py-4">Status</th>
                                        <th class="px-6 py-4">Floor</th>
                                        <th class="px-6 py-4">Building</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="r in selectedFloor.rooms" :key="r.id" class="transition hover:bg-slate-50">
                                        <td class="px-6 py-4 font-bold text-slate-900">Room {{ r.room_number }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ r.capacity }} Persons</td>
                                        <td class="px-6 py-4">
                                            <span :class="['rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wider', statusClass(r.status)]">
                                                {{ r.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-slate-500">{{ selectedFloor.name }}</td>
                                        <td class="px-6 py-4 text-slate-500">{{ selectedBuilding?.name }}</td>
                                    </tr>
                                    <tr v-if="!selectedFloor.rooms || selectedFloor.rooms.length === 0">
                                        <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                            No rooms have been added to this floor yet.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>

                <!-- Case 3: Building Selected (No Floor/Room Selected) -->
                <div v-else-if="selectedBuilding" class="space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <h3 class="text-lg font-bold text-slate-800 uppercase tracking-wider">
                            Floors & Rooms in {{ selectedBuilding.name }}
                        </h3>
                        <div class="flex gap-2 text-xs font-semibold text-slate-600">
                            <span class="bg-slate-100 rounded-full px-3 py-1">
                                {{ selectedBuilding.floors?.length || 0 }} Floors
                            </span>
                            <span class="bg-slate-100 rounded-full px-3 py-1">
                                {{ selectedBuilding.rooms_count || 0 }} Rooms
                            </span>
                        </div>
                    </div>

                    <div v-if="selectedBuilding.floors && selectedBuilding.floors.length > 0" class="space-y-6">
                        <Card
                            v-for="f in selectedBuilding.floors"
                            :key="f.id"
                            padding="p-0"
                            cardClass="overflow-hidden"
                        >
                            <div class="bg-gradient-to-r from-slate-50 to-white border-b border-slate-150 px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <h4 class="font-bold text-slate-800 text-lg">{{ f.name }}</h4>
                                    <p class="text-xs text-slate-500 font-medium">Level {{ f.level ?? 'N/A' }} | Code: {{ f.code || 'None' }}</p>
                                </div>
                                <span class="text-xs font-semibold text-slate-500 bg-slate-100 rounded-full px-3 py-1">
                                    {{ f.rooms?.length || 0 }} Rooms
                                </span>
                            </div>
                            <div class="p-6">
                                <div v-if="f.rooms && f.rooms.length > 0" class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                                    <div
                                        v-for="r in f.rooms"
                                        :key="r.id"
                                        class="rounded-xl border border-slate-100 p-4 transition-all hover:-translate-y-0.5 hover:shadow-md bg-white hover:border-blue-100 duration-200"
                                    >
                                        <div class="flex items-center justify-between">
                                            <span class="font-extrabold text-slate-900 text-base">Room {{ r.room_number }}</span>
                                            <span :class="['rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider', statusClass(r.status)]">
                                                {{ r.status }}
                                            </span>
                                        </div>
                                        <div class="mt-3 text-xs text-slate-500 font-semibold">
                                            Capacity: {{ r.capacity }} Persons
                                        </div>
                                    </div>
                                </div>
                                <p v-else class="text-sm text-slate-400 text-center py-4">
                                    No rooms on this floor yet.
                                </p>
                            </div>
                        </Card>
                    </div>
                    <Card v-else padding="p-8" cardClass="text-center text-slate-400">
                        No floors have been added to this building yet.
                    </Card>
                </div>

                <!-- Case 4: No selection (Default State) -->
                <div v-else class="grid gap-6 md:grid-cols-3">
                    <Card
                        v-for="b in props.buildings"
                        :key="b.id"
                        cardClass="cursor-pointer transition-all hover:-translate-y-1 hover:shadow-lg border-transparent hover:border-blue-250 overflow-hidden flex flex-col group"
                        padding="p-0"
                    >
                        <div class="bg-gradient-to-br from-slate-50 to-white px-6 py-5 border-b border-slate-100 flex-grow">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="font-extrabold text-slate-900 text-lg group-hover:text-blue-600">{{ b.name }}</h3>
                                <span v-if="b.code" class="rounded-full bg-slate-900 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-widest text-white">
                                    {{ b.code }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-2 line-clamp-2">{{ b.description || 'No description provided.' }}</p>
                            <p class="text-xs text-slate-400 mt-3 flex items-center gap-1.5 font-medium">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{ b.address || 'No address provided.' }}
                            </p>
                        </div>
                        <div class="bg-slate-50 px-6 py-4 flex items-center justify-between border-t border-slate-100 text-xs font-bold text-slate-600">
                            <span>{{ b.floors?.length || 0 }} Floors</span>
                            <span>{{ b.rooms_count || 0 }} Rooms</span>
                            <button
                                type="button"
                                @click="selectedBuildingId = b.id"
                                class="text-blue-600 hover:text-blue-800 transition"
                            >
                                Select Filter &rarr;
                            </button>
                        </div>
                    </Card>

                    <!-- Empty state if there are no buildings at all -->
                    <div v-if="props.buildings.length === 0" class="col-span-full">
                        <Card padding="p-12 text-center">
                            <p class="text-slate-400 font-medium">No buildings found in the system.</p>
                            <Link href="/dashboard/buildings/create" class="mt-4 inline-flex items-center gap-1 text-sm font-bold text-blue-600 hover:text-blue-800">
                                Create a building to get started
                            </Link>
                        </Card>
                    </div>
                </div>
            </div>
        </section>
    </DashboardLayout>
</template>
