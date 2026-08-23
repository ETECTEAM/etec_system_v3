<script setup>
import { computed, ref, watch } from "vue";
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import axios from "axios";
import { Card } from "../../../../components/ui/card";
import { SelectSearch } from "../../../../components/ui/select-search";
import { PageHero } from "../../../../components/ui/page-hero";
import DashboardLayout from "../../../../layouts/DashboardLayout.vue";
import { useI18n } from "@/i18n";
import { useConfirm } from "@/composables/useConfirm";
import { useToast } from "vue-toastification";
import { Pencil, Plus, Trash2 } from "@lucide/vue";

const page = usePage();
const { t } = useI18n();
const { confirm } = useConfirm();
const toast = useToast();

const savingRoomId = ref(null);

const buildings = computed(() => page.props.buildings ?? []);
const summary = computed(
    () =>
        page.props.summary ?? {
            buildings: 0,
            floors: 0,
            rooms: 0,
        },
);

const selectedBuildingId = ref("");
const selectedFloorId = ref("");
const selectedRoomId = ref("");

// Computed list of floors based on selected building
const filterFloors = computed(() => {
    if (!selectedBuildingId.value) return [];
    const b = buildings.value.find(x => x.id === Number(selectedBuildingId.value));
    return b ? b.floors : [];
});

// Computed list of rooms based on selected floor
const filterRooms = computed(() => {
    if (!selectedFloorId.value) return [];
    const f = filterFloors.value.find(x => x.id === Number(selectedFloorId.value));
    return f ? f.rooms : [];
});

// Searchable filter options
const buildingOptions = computed(() =>
    buildings.value.map(b => ({ label: b.name, value: String(b.id) })),
);

const floorOptions = computed(() =>
    filterFloors.value.map(f => ({
        label: `${f.name} (${t('Lvl')} ${f.level ?? t('N/A')})`,
        value: String(f.id),
    })),
);

const roomOptions = computed(() =>
    filterRooms.value.map(r => ({
        label: `${t('Room')} ${r.room_number}`,
        value: String(r.id),
    })),
);

function resetFilters() {
    selectedBuildingId.value = "";
    selectedFloorId.value = "";
    selectedRoomId.value = "";
}

// Watchers to reset nested levels when parent selection changes
watch(selectedBuildingId, () => {
    selectedFloorId.value = "";
    selectedRoomId.value = "";
});
watch(selectedFloorId, () => {
    selectedRoomId.value = "";
});

// Reactive filtering logic
const filteredBuildings = computed(() => {
    let list = buildings.value;

    if (selectedBuildingId.value) {
        list = list.filter(b => b.id === Number(selectedBuildingId.value));
    }

    if (selectedFloorId.value) {
        list = list.map(b => {
            return {
                ...b,
                floors: b.floors.filter(f => f.id === Number(selectedFloorId.value))
            };
        }).filter(b => b.floors.length > 0);
    }

    if (selectedRoomId.value) {
        list = list.map(b => {
            return {
                ...b,
                floors: b.floors.map(f => {
                    return {
                        ...f,
                        rooms: f.rooms.filter(r => r.id === Number(selectedRoomId.value))
                    };
                }).filter(f => f.rooms.length > 0)
            };
        }).filter(b => b.floors.length > 0);
    }

    return list;
});

const floorForm = useForm({
    building_id: "",
    name: "",
    level: "",
});

const autoFloorForm = useForm({
    start_name: "",
    total_floors: 3,
    start_level: "",
});

const roomForm = useForm({
    floor_id: "",
    room_number: "",
    capacity: "",
    status: "available",
});

const autoRoomForm = useForm({
    floor_id: "",
    start_room_number: "",
    total_rooms: 3,
    capacity: "",
    status: "available",
});

const floorContext = ref({
    mode: "create",
    buildingId: null,
    floorId: null,
});
const roomContext = ref({
    mode: "create",
    buildingId: null,
    floorId: null,
    roomId: null,
});

// Tracks which floor tab is active per building, keyed by building id
const activeFloorMap = ref({});

function activeFloorId(building) {
    if (!building.floors || building.floors.length === 0) return null;

    const stored = activeFloorMap.value[building.id];
    if (stored && building.floors.some(f => f.id === stored)) {
        return stored;
    }

    return building.floors[0].id;
}

function setActiveFloor(buildingId, floorId) {
    activeFloorMap.value = { ...activeFloorMap.value, [buildingId]: floorId };
}

async function deleteBuilding(building) {
    const ok = await confirm({
        title: t("Delete Building"),
        message: t('Delete building ":name" and all its floors and rooms?', { name: building.name }),
        confirmText: t("Delete"),
        cancelText: t("Cancel"),
        danger: true,
    });

    if (!ok) return;

    router.delete(`/dashboard/buildings/${building.id}`, {
        preserveScroll: true,
    });
}

function openFloorCreate(building) {
    floorContext.value = {
        mode: "create",
        buildingId: building.id,
        floorId: null,
    };
    floorForm.building_id = building.id;
    floorForm.name = "";
    floorForm.level = "";
    floorForm.clearErrors();
}

function openFloorAuto(building) {
    floorContext.value = {
        mode: "auto",
        buildingId: building.id,
        floorId: null,
    };
    autoFloorForm.start_name = "";
    autoFloorForm.total_floors = 3;
    autoFloorForm.start_level = "";
    autoFloorForm.clearErrors();
}

function openFloorEdit(building, floor) {
    floorContext.value = {
        mode: "edit",
        buildingId: building.id,
        floorId: floor.id,
    };
    floorForm.building_id = building.id;
    floorForm.name = floor.name ?? "";
    floorForm.level = floor.level ?? "";
    floorForm.clearErrors();
}

function closeFloorForm() {
    floorContext.value = {
        mode: "create",
        buildingId: null,
        floorId: null,
    };
    floorForm.reset();
    floorForm.clearErrors();
    autoFloorForm.reset();
    autoFloorForm.total_floors = 3;
    autoFloorForm.clearErrors();
}

function submitFloor() {
    const buildingId = floorContext.value.buildingId ?? floorForm.building_id;
    const options = {
        preserveScroll: true,
        onSuccess: () => closeFloorForm(),
    };

    if (floorContext.value.mode === "edit" && floorContext.value.floorId) {
        floorForm.put(
            `/dashboard/buildings/${buildingId}/floors/${floorContext.value.floorId}`,
            options,
        );
        return;
    }

    floorForm.post(`/dashboard/buildings/${buildingId}/floors`, options);
}

function submitAutoFloor() {
    const buildingId = floorContext.value.buildingId;

    autoFloorForm.post(
        `/dashboard/buildings/${buildingId}/floors/auto-generate`,
        {
            preserveScroll: true,
            onSuccess: () => closeFloorForm(),
        },
    );
}

async function deleteFloor(building, floor) {
    const ok = await confirm({
        title: t("Delete Floor"),
        message: t('Delete floor ":name" and all rooms inside it?', { name: floor.name }),
        confirmText: t("Delete"),
        cancelText: t("Cancel"),
        danger: true,
    });

    if (!ok) return;

    router.delete(`/dashboard/buildings/${building.id}/floors/${floor.id}`, {
        preserveScroll: true,
    });
}

function openRoomCreate(building, floor) {
    roomContext.value = {
        mode: "create",
        buildingId: building.id,
        floorId: floor.id,
        roomId: null,
    };
    roomForm.floor_id = floor.id;
    roomForm.room_number = "";
    roomForm.capacity = "";
    roomForm.status = "available";
    roomForm.clearErrors();
}

function openRoomAuto(building, floor) {
    roomContext.value = {
        mode: "auto",
        buildingId: building.id,
        floorId: floor.id,
        roomId: null,
    };
    autoRoomForm.floor_id = floor.id;
    autoRoomForm.start_room_number = "";
    autoRoomForm.total_rooms = 3;
    autoRoomForm.capacity = "";
    autoRoomForm.status = "available";
    autoRoomForm.clearErrors();
}

function openRoomEdit(building, floor, room) {
    roomContext.value = {
        mode: "edit",
        buildingId: building.id,
        floorId: floor.id,
        roomId: room.id,
    };
    roomForm.floor_id = floor.id;
    roomForm.room_number = room.room_number ?? "";
    roomForm.capacity = room.capacity ?? "";
    roomForm.status = room.status ?? "available";
    roomForm.clearErrors();
}

function closeRoomForm() {
    roomContext.value = {
        mode: "create",
        buildingId: null,
        floorId: null,
        roomId: null,
    };
    roomForm.reset();
    roomForm.status = "available";
    roomForm.clearErrors();
    autoRoomForm.reset();
    autoRoomForm.total_rooms = 3;
    autoRoomForm.status = "available";
    autoRoomForm.clearErrors();
}

function submitRoom() {
    const { buildingId, floorId, roomId, mode } = roomContext.value;
    const options = {
        preserveScroll: true,
        onSuccess: () => closeRoomForm(),
    };

    if (mode === "edit" && roomId) {
        roomForm.put(
            `/dashboard/buildings/${buildingId}/floors/${floorId}/rooms/${roomId}`,
            options,
        );
        return;
    }

    roomForm.post(
        `/dashboard/buildings/${buildingId}/floors/${floorId}/rooms`,
        options,
    );
}

async function deleteRoom(building, floor, room) {
    const ok = await confirm({
        title: t("Delete Room"),
        message: t('Delete room ":number"?', { number: room.room_number }),
        confirmText: t("Delete"),
        cancelText: t("Cancel"),
        danger: true,
    });

    if (!ok) return;

    router.delete(
        `/dashboard/buildings/${building.id}/floors/${floor.id}/rooms/${room.id}`,
        {
            preserveScroll: true,
        },
    );
}

function submitAutoRoom() {
    autoRoomForm.post("/dashboard/rooms/auto-generate", {
        preserveScroll: true,
        onSuccess: () => closeRoomForm(),
    });
}

function floorFormVisibleFor(buildingId, floorId = null) {
    if (floorContext.value.mode === "auto") {
        return false;
    }

    return (
        floorContext.value.buildingId === buildingId &&
        floorContext.value.floorId === floorId
    );
}

function roomFormVisibleFor(buildingId, floorId, roomId = null) {
    if (roomContext.value.mode === "auto") {
        return false;
    }

    return (
        roomContext.value.buildingId === buildingId &&
        roomContext.value.floorId === floorId &&
        roomContext.value.roomId === roomId
    );
}

const generatedRooms = computed(() => {
    const start = String(autoRoomForm.start_room_number ?? "").trim();
    const total = Number(autoRoomForm.total_rooms ?? 0);

    if (!start || !Number.isInteger(total) || total <= 0) {
        return [];
    }

    const match = start.match(/^(.*?)(\d+)$/);
    if (!match) {
        return [];
    }

    const prefix = match[1] ?? "";
    const base = Number(match[2] ?? 0);
    const width = String(match[2] ?? "").length;

    return Array.from({ length: total }, (_, index) => {
        const current = base + index;
        return `${prefix}${String(current).padStart(width, "0")}`;
    });
});

const generatedFloors = computed(() => {
    const start = String(autoFloorForm.start_name ?? "").trim();
    const total = Number(autoFloorForm.total_floors ?? 0);
    const startLevel =
        autoFloorForm.start_level === ""
            ? null
            : Number(autoFloorForm.start_level);

    if (!start || !Number.isInteger(total) || total <= 0) {
        return [];
    }

    if (/^[A-Za-z]+$/.test(start)) {
        const base = lettersToNumber(start.toUpperCase());

        return Array.from({ length: total }, (_, index) => ({
            name: numberToLetters(base + index),
            level:
                startLevel === null || Number.isNaN(startLevel)
                    ? null
                    : startLevel + index,
        }));
    }

    const match = start.match(/^(.*?)(\d+)$/);
    if (!match) {
        return [];
    }

    const prefix = match[1] ?? "";
    const base = Number(match[2] ?? 0);
    const width = String(match[2] ?? "").length;

    return Array.from({ length: total }, (_, index) => {
        const current = base + index;

        return {
            name: `${prefix}${String(current).padStart(width, "0")}`,
            level:
                startLevel === null || Number.isNaN(startLevel)
                    ? null
                    : startLevel + index,
        };
    });
});

function lettersToNumber(letters) {
    return letters
        .split("")
        .reduce(
            (total, character) => total * 26 + (character.charCodeAt(0) - 64),
            0,
        );
}

function numberToLetters(number) {
    let value = number;
    let result = "";

    while (value > 0) {
        value -= 1;
        result = String.fromCharCode((value % 26) + 65) + result;
        value = Math.floor(value / 26);
    }

    return result;
}

function statusClass(status) {
    if (status === "occupied")
        return "bg-amber-50 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20";
    if (status === "maintenance")
        return "bg-rose-50 text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-500/20";
    if (status === "closed")
        return "bg-slate-100 text-slate-600 ring-1 ring-slate-300 dark:bg-slate-500/10 dark:text-slate-400 dark:ring-slate-500/20";

    return "bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20";
}

async function changeRoomStatus(room, status) {
    if (status === room.status || savingRoomId.value !== null) return;
    const previous = room.status;
    savingRoomId.value = room.id;

    try {
        const response = await axios.put(`/dashboard/rooms/${room.id}/status`, { status });
        room.status = response.data.room?.status ?? status;
    } catch (error) {
        room.status = previous;
        console.error("Failed to update room status", error);
        toast.error(t(error.response?.data?.message ?? 'Failed to update room. Please try again.'));
    } finally {
        savingRoomId.value = null;
    }
}
</script>
<template>
    <Head :title="$t('Buildings')" />

    <DashboardLayout>
        <section class="space-y-6">
            <div class="grid gap-6 md:grid-cols-3">
                <Card
                    v-for="item in [
                        {
                            label: 'Buildings',
                            value: summary.buildings,
                            tint: 'from-sky-100 to-white text-sky-900 dark:from-sky-500/10 dark:to-transparent dark:text-sky-400',
                        },
                        {
                            label: 'Floors',
                            value: summary.floors,
                            tint: 'from-emerald-100 to-white text-emerald-900 dark:from-emerald-500/10 dark:to-transparent dark:text-emerald-400',
                        },
                        {
                            label: 'Rooms',
                            value: summary.rooms,
                            tint: 'from-amber-100 to-white text-amber-900 dark:from-amber-500/10 dark:to-transparent dark:text-amber-400',
                        },
                    ]"
                    :key="item.label"
                    card-class="overflow-hidden"
                >
                    <div :class="['rounded-2xl ', item.tint]">
                        <p
                            class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-gray-400"
                        >
                            {{ item.label }}
                        </p>
                        <p class="mt-3 text-4xl font-bold">{{ item.value }}</p>
                    </div>
                </Card>
            </div>
            <!-- Filter & Action Section -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">

                    <!-- Filters Grid -->
                    <div class="grid w-full gap-4 grid-cols-1 sm:grid-cols-3 max-w-4xl">
                        <!-- Building Dropdown -->
                        <div class="space-y-1.5 text-left">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">{{ $t('Building') }}</label>
                            <SelectSearch v-model="selectedBuildingId" :options="buildingOptions" :placeholder="t('All Buildings')" />
                        </div>

                        <!-- Floor Dropdown -->
                        <div class="space-y-1.5 text-left">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">{{ $t('Floor') }}</label>
                            <SelectSearch v-model="selectedFloorId" :options="floorOptions" :placeholder="t('All Floors')" :disabled="!selectedBuildingId" />
                        </div>

                        <!-- Room Dropdown -->
                        <div class="space-y-1.5 text-left">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">{{ $t('Room') }}</label>
                            <SelectSearch v-model="selectedRoomId" :options="roomOptions" :placeholder="t('All Rooms')" :disabled="!selectedFloorId" />
                        </div>
                    </div>

                    <!-- Reset & Add Buttons -->
                    <div class="flex gap-2 w-full lg:w-auto justify-end">
                        <button
                            v-if="selectedBuildingId"
                            type="button"
                            @click="resetFilters"
                            class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            {{ $t('Reset') }}
                        </button>

                        <Link
                            href="/dashboard/buildings/create"
                            class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 whitespace-nowrap dark:bg-blue-600 dark:hover:bg-blue-500"
                        >
                            {{ $t('Add Building') }}
                        </Link>
                    </div>

            </div>
        </div>

        <div class="space-y-5">
            <Card v-if="filteredBuildings.length === 0" card-class="border-dashed">
                    <div class="rounded-2xl bg-slate-50 p-10 text-center dark:bg-gray-800/40">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">
                            {{ summary.buildings === 0 ? $t("No buildings yet") : $t("No matching buildings") }}
                        </h3>
                        <p class="mt-2 text-sm text-slate-500 dark:text-gray-400">
                            {{ summary.buildings === 0 ? $t("Use the Add Building button to create the first building.") : $t("Try adjusting or resetting your filter selections.") }}
                        </p>
                    </div>
                </Card>

                <Card
                    v-for="building in filteredBuildings"
                    :key="building.id"
                    padding="p-0"
                    card-class="overflow-hidden"
                >
                    <div
                        class="border-b border-slate-200 bg-gradient-to-r from-slate-50 via-white to-sky-50 px-6 py-5 dark:border-gray-800 dark:from-gray-800/40 dark:via-gray-900 dark:to-gray-900"
                    >
                        <div
                            class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
                        >
                            <div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <h2
                                        class="text-2xl font-semibold text-slate-900 dark:text-gray-100"
                                    >
                                        {{ building.name }}
                                    </h2>
                                    <span
                                        v-if="building.code"
                                        class="rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-white dark:bg-gray-700"
                                    >
                                        {{ building.code }}
                                    </span>
                                </div>

                                <p
                                    v-if="building.description"
                                    class="mt-3 max-w-3xl text-sm leading-6 text-slate-500 dark:text-gray-400"
                                >
                                    {{ building.description }}
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <Link
                                    :href="`/dashboard/buildings/edit/${building.id}`"
                                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                                >
                                    {{ $t('Edit Building') }}
                                </Link>
                                <!-- <button
                                    type="button"
                                    class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-100 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20"
                                    @click="openFloorCreate(building)"
                                >
                                    {{ $t('Add Floor') }}
                                </button> -->
                                <!-- <button
                                    type="button"
                                    class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100 dark:border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-400 dark:hover:bg-indigo-500/20"
                                    @click="openFloorAuto(building)"
                                >
                                    {{ $t('Auto Floor') }}
                                </button> -->
                                <button
                                    type="button"
                                    class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-500/20"
                                    @click="deleteBuilding(building)"
                                >
                                    {{ $t('Delete') }}
                                </button>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-3 text-sm">
                            <span
                                class="rounded-full bg-white px-3 py-1 font-medium text-slate-700 ring-1 ring-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700"
                            >
                                {{ $t(':count floors', { count: building.floors_count }) }}
                            </span>
                            <span
                                class="rounded-full bg-white px-3 py-1 font-medium text-slate-700 ring-1 ring-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700"
                            >
                                {{ $t(':count rooms', { count: building.rooms_count }) }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-4 px-6 py-5">
                        <div
                            v-if="floorFormVisibleFor(building.id, null)"
                            class="rounded-2xl border border-blue-200 bg-blue-50/60 p-4 dark:border-blue-500/20 dark:bg-blue-500/10"
                        >
                            <div class="flex items-center justify-between">
                                <h3
                                    class="text-lg font-semibold text-slate-900 dark:text-gray-100"
                                >
                                    {{
                                        floorContext.mode === "edit"
                                            ? $t("Edit Floor")
                                            : $t("Add Floor to :name", { name: building.name })
                                    }}
                                </h3>
                                <button
                                    type="button"
                                    class="text-sm font-semibold text-slate-500 transition hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200"
                                    @click="closeFloorForm"
                                >
                                    {{ $t('Cancel') }}
                                </button>
                            </div>

                            <form
                                class="mt-4 grid gap-4 md:grid-cols-2"
                                @submit.prevent="submitFloor"
                            >
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-gray-300"
                                        >{{ $t('Floor Name') }}</label
                                    >
                                    <input
                                        v-model="floorForm.name"
                                        type="text"
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                                        :placeholder="$t('Ground Floor')"
                                    />
                                    <p
                                        v-if="floorForm.errors.name"
                                        class="mt-1 text-xs text-red-600 dark:text-red-400"
                                    >
                                        {{ floorForm.errors.name }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-gray-300"
                                        >{{ $t('Level') }}</label
                                    >
                                    <input
                                        v-model="floorForm.level"
                                        type="number"
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                                        placeholder="0"
                                    />
                                    <p
                                        v-if="floorForm.errors.level"
                                        class="mt-1 text-xs text-red-600 dark:text-red-400"
                                    >
                                        {{ floorForm.errors.level }}
                                    </p>
                                </div>

                                <div class="md:col-span-2">
                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-blue-600 dark:hover:bg-blue-500"
                                        :disabled="floorForm.processing"
                                    >
                                        {{
                                            floorContext.mode === "edit"
                                                ? $t("Save Floor")
                                                : $t("Create Floor")
                                        }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div
                            v-if="
                                floorContext.mode === 'auto' &&
                                floorContext.buildingId === building.id
                            "
                            class="rounded-2xl border border-indigo-200 bg-indigo-50/60 p-4 dark:border-indigo-500/20 dark:bg-indigo-500/10"
                        >
                            <div class="flex items-center justify-between">
                                <h3
                                    class="text-lg font-semibold text-slate-900 dark:text-gray-100"
                                >
                                    {{ $t('Auto Floor for :name', { name: building.name }) }}
                                </h3>
                                <button
                                    type="button"
                                    class="text-sm font-semibold text-slate-500 transition hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200"
                                    @click="closeFloorForm"
                                >
                                    {{ $t('Cancel') }}
                                </button>
                            </div>

                            <form
                                class="mt-4 grid gap-4 md:grid-cols-3"
                                @submit.prevent="submitAutoFloor"
                            >
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-gray-300"
                                        >{{ $t('Start Floor') }}</label
                                    >
                                    <input
                                        v-model="autoFloorForm.start_name"
                                        type="text"
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/20"
                                        :placeholder="$t('A-001')"
                                    />
                                    <p
                                        v-if="autoFloorForm.errors.start_name"
                                        class="mt-1 text-xs text-red-600 dark:text-red-400"
                                    >
                                        {{ autoFloorForm.errors.start_name }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-gray-300"
                                        >{{ $t('How Many Floors') }}</label
                                    >
                                    <input
                                        v-model="autoFloorForm.total_floors"
                                        type="number"
                                        min="1"
                                        max="100"
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/20"
                                    />
                                    <p
                                        v-if="autoFloorForm.errors.total_floors"
                                        class="mt-1 text-xs text-red-600 dark:text-red-400"
                                    >
                                        {{ autoFloorForm.errors.total_floors }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-gray-300"
                                        >{{ $t('Start Level') }}</label
                                    >
                                    <input
                                        v-model="autoFloorForm.start_level"
                                        type="number"
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/20"
                                        :placeholder="$t('Optional')"
                                    />
                                    <p
                                        v-if="autoFloorForm.errors.start_level"
                                        class="mt-1 text-xs text-red-600 dark:text-red-400"
                                    >
                                        {{ autoFloorForm.errors.start_level }}
                                    </p>
                                </div>

                                <div
                                    class="md:col-span-3 rounded-2xl border border-dashed border-slate-300 bg-white p-4 dark:border-gray-700 dark:bg-gray-900"
                                >
                                    <div
                                        class="flex items-center justify-between gap-3"
                                    >
                                        <div>
                                            <h4
                                                class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-gray-400"
                                            >
                                                {{ $t('Preview') }}
                                            </h4>
                                            <p
                                                class="mt-1 text-sm text-slate-600 dark:text-gray-300"
                                            >
                                                {{ $t('Supports both `A, B, C` and `A-001, A-002, A-003` styles.') }}
                                            </p>
                                        </div>
                                        <span
                                            class="rounded-full bg-slate-50 px-3 py-1 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700"
                                        >
                                            {{ $t(':count floors', { count: generatedFloors.length }) }}
                                        </span>
                                    </div>

                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <span
                                            v-for="floorPreview in generatedFloors"
                                            :key="floorPreview.name"
                                            class="rounded-full bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-700 ring-1 ring-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-400 dark:ring-indigo-500/20"
                                        >
                                            {{ floorPreview.name
                                            }}<span
                                                v-if="
                                                    floorPreview.level !== null
                                                "
                                            >
                                                / {{ $t('Level') }}
                                                {{ floorPreview.level }}</span
                                            >
                                        </span>
                                        <span
                                            v-if="generatedFloors.length === 0"
                                            class="text-sm text-slate-500 dark:text-gray-400"
                                        >
                                            {{ $t('Type a valid start floor like `A` or `A-001`.') }}
                                        </span>
                                    </div>
                                </div>

                                <div class="md:col-span-3">
                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-indigo-600 dark:hover:bg-indigo-500"
                                        :disabled="
                                            autoFloorForm.processing ||
                                            generatedFloors.length === 0
                                        "
                                    >
                                        {{
                                            autoFloorForm.processing
                                                ? $t("Generating...")
                                                : $t("Create Auto Floors")
                                        }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div
                            v-if="building.floors.length === 0"
                            class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-6 text-center text-sm text-slate-500 dark:border-gray-700 dark:bg-gray-800/40 dark:text-gray-400"
                        >
                            {{ $t('No floors in this building yet. Use Add Floor to create the first one.') }}
                        </div>

                        <div v-else class="space-y-4">
                            <!-- Floor tabs -->
                            <div class="flex flex-wrap items-center gap-1 border-b border-slate-200 dark:border-gray-800">
                                <button
                                    v-for="floor in building.floors"
                                    :key="floor.id"
                                    type="button"
                                    class="shrink-0 border-b-2 px-4 py-2.5 text-sm font-semibold transition"
                                    :class="
                                        activeFloorId(building) === floor.id
                                            ? 'border-rose-500 text-rose-600 dark:border-rose-400 dark:text-rose-400'
                                            : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200'
                                    "
                                    @click="setActiveFloor(building.id, floor.id)"
                                >
                                    {{ floor.name }}
                                </button>

                                <div class="ml-auto flex shrink-0 items-center gap-2 py-1.5">
                                    <button
                                        type="button"
                                        class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100 dark:border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-400 dark:hover:bg-indigo-500/20"
                                        @click="openFloorAuto(building)"
                                    >
                                        {{ $t('Auto Floor') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-lg border border-dashed border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-blue-400 hover:text-blue-600 dark:border-gray-600 dark:text-gray-300 dark:hover:border-blue-500 dark:hover:text-blue-400"
                                        @click="openFloorCreate(building)"
                                    >
                                        <Plus class="h-3.5 w-3.5" />
                                        {{ $t('Add Floor') }}
                                    </button>
                                </div>
                            </div>

                            <template v-for="floor in building.floors" :key="floor.id">
                                <div v-show="activeFloorId(building) === floor.id" class="space-y-4">
                                    <!-- Floor toolbar -->
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h4 class="text-base font-semibold text-slate-900 dark:text-gray-100">
                                                {{ floor.name }}
                                            </h4>
                                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                                {{ $t('Level') }} {{ floor.level ?? $t("N/A") }}
                                            </span>
                                            <span class="text-sm text-slate-500 dark:text-gray-400">
                                                {{ $t(':count rooms on this floor', { count: floor.rooms_count }) }}
                                            </span>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <button
                                                type="button"
                                                class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20"
                                                @click="openRoomAuto(building, floor)"
                                            >
                                                {{ $t('Auto Room') }}
                                            </button>
                                            <button
                                                type="button"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600 transition hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                                :title="$t('Edit Floor')"
                                                :aria-label="$t('Edit Floor')"
                                                @click="openFloorEdit(building, floor)"
                                            >
                                                <Pencil class="h-4 w-4" />
                                            </button>
                                            <button
                                                type="button"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600 transition hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-500/20"
                                                :title="$t('Delete Floor')"
                                                :aria-label="$t('Delete Floor')"
                                                @click="deleteFloor(building, floor)"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Edit floor form -->
                                    <div
                                        v-if="floorFormVisibleFor(building.id, floor.id)"
                                        class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-gray-700 dark:bg-gray-800/40"
                                    >
                                        <div class="flex items-center justify-between">
                                            <h5 class="text-base font-semibold text-slate-900 dark:text-gray-100">
                                                {{ $t('Update Floor') }}
                                            </h5>
                                            <button
                                                type="button"
                                                class="text-sm font-semibold text-slate-500 transition hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200"
                                                @click="closeFloorForm"
                                            >
                                                {{ $t('Cancel') }}
                                            </button>
                                        </div>

                                        <form class="mt-4 grid gap-4 md:grid-cols-2" @submit.prevent="submitFloor">
                                            <div>
                                                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-gray-300">{{ $t('Floor Name') }}</label>
                                                <input
                                                    v-model="floorForm.name"
                                                    type="text"
                                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                                                />
                                                <p v-if="floorForm.errors.name" class="mt-1 text-xs text-red-600 dark:text-red-400">
                                                    {{ floorForm.errors.name }}
                                                </p>
                                            </div>

                                            <div>
                                                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-gray-300">{{ $t('Level') }}</label>
                                                <input
                                                    v-model="floorForm.level"
                                                    type="number"
                                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                                                />
                                                <p v-if="floorForm.errors.level" class="mt-1 text-xs text-red-600 dark:text-red-400">
                                                    {{ floorForm.errors.level }}
                                                </p>
                                            </div>

                                            <div class="md:col-span-2">
                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-gray-700 dark:hover:bg-gray-600"
                                                    :disabled="floorForm.processing"
                                                >
                                                    {{ $t('Save Floor') }}
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- Add / edit room form -->
                                    <div
                                        v-if="roomContext.mode !== 'auto' && roomContext.buildingId === building.id && roomContext.floorId === floor.id"
                                        class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/10"
                                    >
                                        <div class="flex items-center justify-between">
                                            <h5 class="text-base font-semibold text-slate-900 dark:text-gray-100">
                                                {{ roomContext.mode === "edit" ? $t("Edit Room") : $t("Add Room to :name", { name: floor.name }) }}
                                            </h5>
                                            <button
                                                type="button"
                                                class="text-sm font-semibold text-slate-500 transition hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200"
                                                @click="closeRoomForm"
                                            >
                                                {{ $t('Cancel') }}
                                            </button>
                                        </div>

                                        <form class="mt-4 grid gap-4 md:grid-cols-4" @submit.prevent="submitRoom">
                                            <div>
                                                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-gray-300">{{ $t('Room Number') }}</label>
                                                <input
                                                    v-model="roomForm.room_number"
                                                    type="text"
                                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                                                    :placeholder="$t('A-101')"
                                                />
                                                <p v-if="roomForm.errors.room_number" class="mt-1 text-xs text-red-600 dark:text-red-400">
                                                    {{ roomForm.errors.room_number }}
                                                </p>
                                            </div>

                                            <div>
                                                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-gray-300">{{ $t('Capacity') }}</label>
                                                <input
                                                    v-model="roomForm.capacity"
                                                    type="number"
                                                    min="1"
                                                    @keydown="['-', 'e', 'E', '+'].includes($event.key) && $event.preventDefault()"
                                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                                                    placeholder="30"
                                                />
                                                <p v-if="roomForm.errors.capacity" class="mt-1 text-xs text-red-600 dark:text-red-400">
                                                    {{ roomForm.errors.capacity }}
                                                </p>
                                            </div>

                                            <div>
                                                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-gray-300">{{ $t('Status') }}</label>
                                                <select
                                                    v-model="roomForm.status"
                                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                                                >
                                                    <option value="available">{{ $t('Available') }}</option>
                                                    <option value="occupied">{{ $t('Occupied') }}</option>
                                                    <option value="maintenance">{{ $t('Maintenance') }}</option>
                                                </select>
                                                <p v-if="roomForm.errors.status" class="mt-1 text-xs text-red-600 dark:text-red-400">
                                                    {{ roomForm.errors.status }}
                                                </p>
                                            </div>

                                            <div class="flex items-end">
                                                <button
                                                    type="submit"
                                                    class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-emerald-600 dark:hover:bg-emerald-500"
                                                    :disabled="roomForm.processing"
                                                >
                                                    {{ roomContext.mode === "edit" ? $t("Save Room") : $t("Create Room") }}
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- Auto-generate rooms form -->
                                    <div
                                        v-if="roomContext.mode === 'auto' && roomContext.buildingId === building.id && roomContext.floorId === floor.id"
                                        class="rounded-2xl border border-blue-200 bg-blue-50/60 p-4 dark:border-blue-500/20 dark:bg-blue-500/10"
                                    >
                                        <div class="flex items-center justify-between">
                                            <h5 class="text-base font-semibold text-slate-900 dark:text-gray-100">
                                                {{ $t('Auto Room for :name', { name: floor.name }) }}
                                            </h5>
                                            <button
                                                type="button"
                                                class="text-sm font-semibold text-slate-500 transition hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200"
                                                @click="closeRoomForm"
                                            >
                                                {{ $t('Cancel') }}
                                            </button>
                                        </div>

                                        <form class="mt-4 grid gap-4 md:grid-cols-4" @submit.prevent="submitAutoRoom">
                                            <div>
                                                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-gray-300">{{ $t('Start Room') }}</label>
                                                <input
                                                    v-model="autoRoomForm.start_room_number"
                                                    type="text"
                                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                                                    :placeholder="$t('A-101')"
                                                />
                                                <p v-if="autoRoomForm.errors.start_room_number" class="mt-1 text-xs text-red-600 dark:text-red-400">
                                                    {{ autoRoomForm.errors.start_room_number }}
                                                </p>
                                            </div>

                                            <div>
                                                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-gray-300">{{ $t('How Many Rooms') }}</label>
                                                <input
                                                    v-model="autoRoomForm.total_rooms"
                                                    type="number"
                                                    min="1"
                                                    max="200"
                                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                                                />
                                                <p v-if="autoRoomForm.errors.total_rooms" class="mt-1 text-xs text-red-600 dark:text-red-400">
                                                    {{ autoRoomForm.errors.total_rooms }}
                                                </p>
                                            </div>

                                            <div>
                                                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-gray-300">{{ $t('Capacity') }}</label>
                                                <input
                                                    v-model="autoRoomForm.capacity"
                                                    type="number"
                                                    min="1"
                                                    @keydown="['-', 'e', 'E', '+'].includes($event.key) && $event.preventDefault()"
                                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                                                    placeholder="30"
                                                />
                                                <p v-if="autoRoomForm.errors.capacity" class="mt-1 text-xs text-red-600 dark:text-red-400">
                                                    {{ autoRoomForm.errors.capacity }}
                                                </p>
                                            </div>

                                            <div>
                                                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-gray-300">{{ $t('Status') }}</label>
                                                <select
                                                    v-model="autoRoomForm.status"
                                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                                                >
                                                    <option value="available">{{ $t('Available') }}</option>
                                                    <option value="occupied">{{ $t('Occupied') }}</option>
                                                    <option value="maintenance">{{ $t('Maintenance') }}</option>
                                                </select>
                                                <p v-if="autoRoomForm.errors.status" class="mt-1 text-xs text-red-600 dark:text-red-400">
                                                    {{ autoRoomForm.errors.status }}
                                                </p>
                                            </div>

                                            <div class="md:col-span-4 rounded-2xl border border-dashed border-slate-300 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                                                <div class="flex items-center justify-between gap-3">
                                                    <div>
                                                        <h6 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-gray-400">
                                                            {{ $t('Preview') }}
                                                        </h6>
                                                        <p class="mt-1 text-sm text-slate-600 dark:text-gray-300">
                                                            {{ $t('Example: `A-101` with `3` rooms becomes `A-101`, `A-102`, `A-103`.') }}
                                                        </p>
                                                    </div>
                                                    <span class="rounded-full bg-slate-50 px-3 py-1 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700">
                                                        {{ generatedRooms.length }} {{ $t('rooms') }}
                                                    </span>
                                                </div>

                                                <div class="mt-4 flex flex-wrap gap-2">
                                                    <span
                                                        v-for="room in generatedRooms"
                                                        :key="room"
                                                        class="rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700 ring-1 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:ring-blue-500/20"
                                                    >
                                                        {{ room }}
                                                    </span>
                                                    <span v-if="generatedRooms.length === 0" class="text-sm text-slate-500 dark:text-gray-400">
                                                        {{ $t('Type a valid start room like `A-101`.') }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="md:col-span-4">
                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-blue-600 dark:hover:bg-blue-500"
                                                    :disabled="autoRoomForm.processing || generatedRooms.length === 0"
                                                >
                                                    {{ autoRoomForm.processing ? $t("Generating...") : $t("Create Auto Rooms") }}
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- Room grid -->
                                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6">
                                        <button
                                            type="button"
                                            class="flex min-h-[92px] flex-col items-center justify-center gap-1.5 rounded-2xl border-2 border-dashed border-slate-300 p-4 text-slate-400 transition hover:border-blue-400 hover:text-blue-500 dark:border-gray-700 dark:text-gray-500 dark:hover:border-blue-500 dark:hover:text-blue-400"
                                            @click="openRoomCreate(building, floor)"
                                        >
                                            <Plus class="h-5 w-5" />
                                            <span class="text-xs font-semibold">{{ $t('Add Room') }}</span>
                                        </button>

                                        <div
                                            v-for="room in floor.rooms"
                                            :key="room.id"
                                            class="group relative min-h-[92px] rounded-2xl border border-slate-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
                                        >
                                            <div class="absolute right-2 top-2 flex gap-1 opacity-0 transition group-hover:opacity-100 group-focus-within:opacity-100">
                                                <button
                                                    type="button"
                                                    class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-slate-100 text-slate-600 transition hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                                    :title="$t('Edit Room')"
                                                    :aria-label="$t('Edit Room')"
                                                    @click="openRoomEdit(building, floor, room)"
                                                >
                                                    <Pencil class="h-3.5 w-3.5" />
                                                </button>
                                                <button
                                                    type="button"
                                                    class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-rose-50 text-rose-600 transition hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-500/20"
                                                    :title="$t('Delete Room')"
                                                    :aria-label="$t('Delete Room')"
                                                    @click="deleteRoom(building, floor, room)"
                                                >
                                                    <Trash2 class="h-3.5 w-3.5" />
                                                </button>
                                            </div>

                                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500">
                                                {{ $t('Room no') }}
                                            </p>
                                            <p class="mt-0.5 truncate text-base font-bold text-slate-900 dark:text-gray-100" :title="room.room_number">
                                                {{ room.room_number }}
                                            </p>
                                            <select :value="room.status" :disabled="savingRoomId === room.id" :title="$t('Click to change status')" :class="['mt-2 inline-flex cursor-pointer appearance-none rounded-full px-2 py-0.5 text-[11px] font-semibold text-center capitalize transition hover:opacity-80 focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:opacity-50 dark:focus:ring-blue-500/20', statusClass(room.status)]" @change="changeRoomStatus(room, $event.target.value)">
                                                <option value="available">{{ $t('Available') }}</option>
                                                <option value="occupied">{{ $t('Occupied') }}</option>
                                                <option value="maintenance">{{ $t('Maintenance') }}</option>
                                                <option value="closed">{{ $t('Closed') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </Card>
            </div>
        </section>
    </DashboardLayout>
</template>
