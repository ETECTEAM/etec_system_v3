<script setup>
import { computed, ref } from "vue";
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import { Card } from "../../../components/ui/card";
import { PageHero } from "../../../components/ui/page-hero";
import DashboardLayout from "../../../layouts/DashboardLayout.vue";

const page = usePage();

const buildings = computed(() => page.props.buildings ?? []);
const summary = computed(
    () =>
        page.props.summary ?? {
            buildings: 0,
            floors: 0,
            rooms: 0,
        },
);

const floorForm = useForm({
    building_id: "",
    name: "",
    code: "",
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

function deleteBuilding(building) {
    if (
        !window.confirm(
            `Delete building "${building.name}" and all its floors and rooms?`,
        )
    ) {
        return;
    }

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
    floorForm.code = "";
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
    floorForm.code = floor.code ?? "";
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

function deleteFloor(building, floor) {
    if (
        !window.confirm(`Delete floor "${floor.name}" and all rooms inside it?`)
    ) {
        return;
    }

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

function deleteRoom(building, floor, room) {
    if (!window.confirm(`Delete room "${room.room_number}"?`)) {
        return;
    }

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
        return "bg-amber-50 text-amber-700 ring-1 ring-amber-200";
    if (status === "maintenance")
        return "bg-rose-50 text-rose-700 ring-1 ring-rose-200";

    return "bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200";
}
</script>
<template>
    <Head title="Buildings" />

    <DashboardLayout>
        <section class="space-y-6">
            <div class="grid gap-4 md:grid-cols-4">
                <Card
                    v-for="item in [
                        {
                            label: 'Buildings',
                            value: summary.buildings,
                            tint: 'from-sky-100 to-white text-sky-900',
                        },
                        {
                            label: 'Floors',
                            value: summary.floors,
                            tint: 'from-emerald-100 to-white text-emerald-900',
                        },
                        {
                            label: 'Rooms',
                            value: summary.rooms,
                            tint: 'from-amber-100 to-white text-amber-900',
                        },
                    ]"
                    :key="item.label"
                    card-class="overflow-hidden"
                >
                    <div :class="['rounded-2xl ', item.tint]">
                        <p
                            class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500"
                        >
                            {{ item.label }}
                        </p>
                        <p class="mt-3 text-4xl font-bold">{{ item.value }}</p>
                    </div>
                </Card>
            </div>
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
            >
               <div>
                
               </div>

                <div class="flex gap-2">
                    <Link
                        href="/dashboard/buildings/filter"
                        class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700"
                    >
                        Filter Building
                    </Link>

                    <Link
                        href="/dashboard/buildings/create"
                        class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700"
                    >
                        Add Building
                    </Link>
                </div>
            </div>

            <div class="space-y-5">
                <Card v-if="buildings.length === 0" card-class="border-dashed">
                    <div class="rounded-2xl bg-slate-50 p-10 text-center">
                        <h3 class="text-lg font-semibold text-slate-900">
                            No buildings yet
                        </h3>
                        <p class="mt-2 text-sm text-slate-500">
                            Use the Add Building button to create the first
                            building.
                        </p>
                    </div>
                </Card>

                <Card
                    v-for="building in buildings"
                    :key="building.id"
                    padding="p-0"
                    card-class="overflow-hidden"
                >
                    <div
                        class="border-b border-slate-200 bg-gradient-to-r from-slate-50 via-white to-sky-50 px-6 py-5"
                    >
                        <div
                            class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
                        >
                            <div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <h2
                                        class="text-2xl font-semibold text-slate-900"
                                    >
                                        {{ building.name }}
                                    </h2>
                                    <span
                                        v-if="building.code"
                                        class="rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-white"
                                    >
                                        {{ building.code }}
                                    </span>
                                </div>

                                <p class="mt-2 text-sm text-slate-600">
                                    {{
                                        building.address ||
                                        "No address provided."
                                    }}
                                </p>
                                <p
                                    v-if="building.description"
                                    class="mt-3 max-w-3xl text-sm leading-6 text-slate-500"
                                >
                                    {{ building.description }}
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <Link
                                    :href="`/dashboard/buildings/edit/${building.id}`"
                                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                >
                                    Edit Building
                                </Link>
                                <button
                                    type="button"
                                    class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-100"
                                    @click="openFloorCreate(building)"
                                >
                                    Add Floor
                                </button>
                                <button
                                    type="button"
                                    class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100"
                                    @click="openFloorAuto(building)"
                                >
                                    Auto Floor
                                </button>
                                <button
                                    type="button"
                                    class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100"
                                    @click="deleteBuilding(building)"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-3 text-sm">
                            <span
                                class="rounded-full bg-white px-3 py-1 font-medium text-slate-700 ring-1 ring-slate-200"
                            >
                                {{ building.floors_count }} floors
                            </span>
                            <span
                                class="rounded-full bg-white px-3 py-1 font-medium text-slate-700 ring-1 ring-slate-200"
                            >
                                {{ building.rooms_count }} rooms
                            </span>
                        </div>
                    </div>

                    <div class="space-y-4 px-6 py-5">
                        <div
                            v-if="floorFormVisibleFor(building.id, null)"
                            class="rounded-2xl border border-blue-200 bg-blue-50/60 p-4"
                        >
                            <div class="flex items-center justify-between">
                                <h3
                                    class="text-lg font-semibold text-slate-900"
                                >
                                    {{
                                        floorContext.mode === "edit"
                                            ? "Edit Floor"
                                            : `Add Floor to ${building.name}`
                                    }}
                                </h3>
                                <button
                                    type="button"
                                    class="text-sm font-semibold text-slate-500 transition hover:text-slate-800"
                                    @click="closeFloorForm"
                                >
                                    Cancel
                                </button>
                            </div>

                            <form
                                class="mt-4 grid gap-4 md:grid-cols-3"
                                @submit.prevent="submitFloor"
                            >
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-slate-700"
                                        >Floor Name</label
                                    >
                                    <input
                                        v-model="floorForm.name"
                                        type="text"
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                        placeholder="Ground Floor"
                                    />
                                    <p
                                        v-if="floorForm.errors.name"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{ floorForm.errors.name }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-slate-700"
                                        >Code</label
                                    >
                                    <input
                                        v-model="floorForm.code"
                                        type="text"
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                        placeholder="GF"
                                    />
                                    <p
                                        v-if="floorForm.errors.code"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{ floorForm.errors.code }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-slate-700"
                                        >Level</label
                                    >
                                    <input
                                        v-model="floorForm.level"
                                        type="number"
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                        placeholder="0"
                                    />
                                    <p
                                        v-if="floorForm.errors.level"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{ floorForm.errors.level }}
                                    </p>
                                </div>

                                <div class="md:col-span-3">
                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="floorForm.processing"
                                    >
                                        {{
                                            floorContext.mode === "edit"
                                                ? "Save Floor"
                                                : "Create Floor"
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
                            class="rounded-2xl border border-indigo-200 bg-indigo-50/60 p-4"
                        >
                            <div class="flex items-center justify-between">
                                <h3
                                    class="text-lg font-semibold text-slate-900"
                                >
                                    Auto Floor for {{ building.name }}
                                </h3>
                                <button
                                    type="button"
                                    class="text-sm font-semibold text-slate-500 transition hover:text-slate-800"
                                    @click="closeFloorForm"
                                >
                                    Cancel
                                </button>
                            </div>

                            <form
                                class="mt-4 grid gap-4 md:grid-cols-3"
                                @submit.prevent="submitAutoFloor"
                            >
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-slate-700"
                                        >Start Floor</label
                                    >
                                    <input
                                        v-model="autoFloorForm.start_name"
                                        type="text"
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                        placeholder="A-001"
                                    />
                                    <p
                                        v-if="autoFloorForm.errors.start_name"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{ autoFloorForm.errors.start_name }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-slate-700"
                                        >How Many Floors</label
                                    >
                                    <input
                                        v-model="autoFloorForm.total_floors"
                                        type="number"
                                        min="1"
                                        max="100"
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                    />
                                    <p
                                        v-if="autoFloorForm.errors.total_floors"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{ autoFloorForm.errors.total_floors }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-slate-700"
                                        >Start Level</label
                                    >
                                    <input
                                        v-model="autoFloorForm.start_level"
                                        type="number"
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                        placeholder="Optional"
                                    />
                                    <p
                                        v-if="autoFloorForm.errors.start_level"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{ autoFloorForm.errors.start_level }}
                                    </p>
                                </div>

                                <div
                                    class="md:col-span-3 rounded-2xl border border-dashed border-slate-300 bg-white p-4"
                                >
                                    <div
                                        class="flex items-center justify-between gap-3"
                                    >
                                        <div>
                                            <h4
                                                class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500"
                                            >
                                                Preview
                                            </h4>
                                            <p
                                                class="mt-1 text-sm text-slate-600"
                                            >
                                                Supports both `A, B, C` and
                                                `A-001, A-002, A-003` styles.
                                            </p>
                                        </div>
                                        <span
                                            class="rounded-full bg-slate-50 px-3 py-1 text-sm font-semibold text-slate-700 ring-1 ring-slate-200"
                                        >
                                            {{ generatedFloors.length }} floors
                                        </span>
                                    </div>

                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <span
                                            v-for="floorPreview in generatedFloors"
                                            :key="floorPreview.name"
                                            class="rounded-full bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-700 ring-1 ring-indigo-200"
                                        >
                                            {{ floorPreview.name
                                            }}<span
                                                v-if="
                                                    floorPreview.level !== null
                                                "
                                            >
                                                / Level
                                                {{ floorPreview.level }}</span
                                            >
                                        </span>
                                        <span
                                            v-if="generatedFloors.length === 0"
                                            class="text-sm text-slate-500"
                                        >
                                            Type a valid start floor like `A` or
                                            `A-001`.
                                        </span>
                                    </div>
                                </div>

                                <div class="md:col-span-3">
                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="
                                            autoFloorForm.processing ||
                                            generatedFloors.length === 0
                                        "
                                    >
                                        {{
                                            autoFloorForm.processing
                                                ? "Generating..."
                                                : "Create Auto Floors"
                                        }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div
                            v-if="building.floors.length === 0"
                            class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-6 text-sm text-slate-500"
                        >
                            No floors in this building yet.
                        </div>

                        <div
                            v-for="floor in building.floors"
                            :key="floor.id"
                            class="rounded-2xl border border-slate-200 bg-white"
                        >
                            <div class="border-b border-slate-200 px-5 py-4">
                                <div
                                    class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                                >
                                    <div>
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <h3
                                                class="text-lg font-semibold text-slate-900"
                                            >
                                                {{ floor.name }}
                                            </h3>
                                            <span
                                                v-if="floor.code"
                                                class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600"
                                            >
                                                {{ floor.code }}
                                            </span>
                                            <span
                                                class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                                            >
                                                Level {{ floor.level ?? "N/A" }}
                                            </span>
                                        </div>
                                        <p class="mt-2 text-sm text-slate-500">
                                            {{ floor.rooms_count }} rooms on
                                            this floor
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                            @click="
                                                openFloorEdit(building, floor)
                                            "
                                        >
                                            Edit Floor
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100"
                                            @click="
                                                openRoomCreate(building, floor)
                                            "
                                        >
                                            Add Room
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-100"
                                            @click="
                                                openRoomAuto(building, floor)
                                            "
                                        >
                                            Auto Room
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100"
                                            @click="
                                                deleteFloor(building, floor)
                                            "
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4 px-5 py-4">
                                <div
                                    v-if="
                                        floorFormVisibleFor(
                                            building.id,
                                            floor.id,
                                        )
                                    "
                                    class="rounded-2xl border border-slate-200 bg-slate-50 p-4"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <h4
                                            class="text-base font-semibold text-slate-900"
                                        >
                                            Update Floor
                                        </h4>
                                        <button
                                            type="button"
                                            class="text-sm font-semibold text-slate-500 transition hover:text-slate-800"
                                            @click="closeFloorForm"
                                        >
                                            Cancel
                                        </button>
                                    </div>

                                    <form
                                        class="mt-4 grid gap-4 md:grid-cols-3"
                                        @submit.prevent="submitFloor"
                                    >
                                        <div>
                                            <label
                                                class="mb-1.5 block text-sm font-medium text-slate-700"
                                                >Floor Name</label
                                            >
                                            <input
                                                v-model="floorForm.name"
                                                type="text"
                                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                            />
                                            <p
                                                v-if="floorForm.errors.name"
                                                class="mt-1 text-xs text-red-600"
                                            >
                                                {{ floorForm.errors.name }}
                                            </p>
                                        </div>

                                        <div>
                                            <label
                                                class="mb-1.5 block text-sm font-medium text-slate-700"
                                                >Code</label
                                            >
                                            <input
                                                v-model="floorForm.code"
                                                type="text"
                                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                            />
                                            <p
                                                v-if="floorForm.errors.code"
                                                class="mt-1 text-xs text-red-600"
                                            >
                                                {{ floorForm.errors.code }}
                                            </p>
                                        </div>

                                        <div>
                                            <label
                                                class="mb-1.5 block text-sm font-medium text-slate-700"
                                                >Level</label
                                            >
                                            <input
                                                v-model="floorForm.level"
                                                type="number"
                                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                            />
                                            <p
                                                v-if="floorForm.errors.level"
                                                class="mt-1 text-xs text-red-600"
                                            >
                                                {{ floorForm.errors.level }}
                                            </p>
                                        </div>

                                        <div class="md:col-span-3">
                                            <button
                                                type="submit"
                                                class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                                                :disabled="floorForm.processing"
                                            >
                                                Save Floor
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <div
                                    v-if="
                                        roomFormVisibleFor(
                                            building.id,
                                            floor.id,
                                            null,
                                        )
                                    "
                                    class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <h4
                                            class="text-base font-semibold text-slate-900"
                                        >
                                            {{
                                                roomContext.mode === "edit"
                                                    ? "Edit Room"
                                                    : `Add Room to ${floor.name}`
                                            }}
                                        </h4>
                                        <button
                                            type="button"
                                            class="text-sm font-semibold text-slate-500 transition hover:text-slate-800"
                                            @click="closeRoomForm"
                                        >
                                            Cancel
                                        </button>
                                    </div>

                                    <form
                                        class="mt-4 grid gap-4 md:grid-cols-4"
                                        @submit.prevent="submitRoom"
                                    >
                                        <div>
                                            <label
                                                class="mb-1.5 block text-sm font-medium text-slate-700"
                                                >Room Number</label
                                            >
                                            <input
                                                v-model="roomForm.room_number"
                                                type="text"
                                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                                placeholder="A-101"
                                            />
                                            <p
                                                v-if="
                                                    roomForm.errors.room_number
                                                "
                                                class="mt-1 text-xs text-red-600"
                                            >
                                                {{
                                                    roomForm.errors.room_number
                                                }}
                                            </p>
                                        </div>

                                        <div>
                                            <label
                                                class="mb-1.5 block text-sm font-medium text-slate-700"
                                                >Capacity</label
                                            >
                                            <input
                                                v-model="roomForm.capacity"
                                                type="number"
                                                min="1"
                                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                                placeholder="30"
                                            />
                                            <p
                                                v-if="roomForm.errors.capacity"
                                                class="mt-1 text-xs text-red-600"
                                            >
                                                {{ roomForm.errors.capacity }}
                                            </p>
                                        </div>

                                        <div>
                                            <label
                                                class="mb-1.5 block text-sm font-medium text-slate-700"
                                                >Status</label
                                            >
                                            <select
                                                v-model="roomForm.status"
                                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                            >
                                                <option value="available">
                                                    Available
                                                </option>
                                                <option value="occupied">
                                                    Occupied
                                                </option>
                                                <option value="maintenance">
                                                    Maintenance
                                                </option>
                                            </select>
                                            <p
                                                v-if="roomForm.errors.status"
                                                class="mt-1 text-xs text-red-600"
                                            >
                                                {{ roomForm.errors.status }}
                                            </p>
                                        </div>

                                        <div class="flex items-end">
                                            <button
                                                type="submit"
                                                class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                                                :disabled="roomForm.processing"
                                            >
                                                {{
                                                    roomContext.mode === "edit"
                                                        ? "Save Room"
                                                        : "Create Room"
                                                }}
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <div
                                    v-if="
                                        roomContext.mode === 'auto' &&
                                        roomContext.buildingId ===
                                            building.id &&
                                        roomContext.floorId === floor.id
                                    "
                                    class="rounded-2xl border border-blue-200 bg-blue-50/60 p-4"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <h4
                                            class="text-base font-semibold text-slate-900"
                                        >
                                            Auto Room for {{ floor.name }}
                                        </h4>
                                        <button
                                            type="button"
                                            class="text-sm font-semibold text-slate-500 transition hover:text-slate-800"
                                            @click="closeRoomForm"
                                        >
                                            Cancel
                                        </button>
                                    </div>

                                    <form
                                        class="mt-4 grid gap-4 md:grid-cols-4"
                                        @submit.prevent="submitAutoRoom"
                                    >
                                        <div>
                                            <label
                                                class="mb-1.5 block text-sm font-medium text-slate-700"
                                                >Start Room</label
                                            >
                                            <input
                                                v-model="
                                                    autoRoomForm.start_room_number
                                                "
                                                type="text"
                                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                                placeholder="A-101"
                                            />
                                            <p
                                                v-if="
                                                    autoRoomForm.errors
                                                        .start_room_number
                                                "
                                                class="mt-1 text-xs text-red-600"
                                            >
                                                {{
                                                    autoRoomForm.errors
                                                        .start_room_number
                                                }}
                                            </p>
                                        </div>

                                        <div>
                                            <label
                                                class="mb-1.5 block text-sm font-medium text-slate-700"
                                                >How Many Rooms</label
                                            >
                                            <input
                                                v-model="
                                                    autoRoomForm.total_rooms
                                                "
                                                type="number"
                                                min="1"
                                                max="200"
                                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                            />
                                            <p
                                                v-if="
                                                    autoRoomForm.errors
                                                        .total_rooms
                                                "
                                                class="mt-1 text-xs text-red-600"
                                            >
                                                {{
                                                    autoRoomForm.errors
                                                        .total_rooms
                                                }}
                                            </p>
                                        </div>

                                        <div>
                                            <label
                                                class="mb-1.5 block text-sm font-medium text-slate-700"
                                                >Capacity</label
                                            >
                                            <input
                                                v-model="autoRoomForm.capacity"
                                                type="number"
                                                min="1"
                                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                                placeholder="30"
                                            />
                                            <p
                                                v-if="
                                                    autoRoomForm.errors.capacity
                                                "
                                                class="mt-1 text-xs text-red-600"
                                            >
                                                {{
                                                    autoRoomForm.errors.capacity
                                                }}
                                            </p>
                                        </div>

                                        <div>
                                            <label
                                                class="mb-1.5 block text-sm font-medium text-slate-700"
                                                >Status</label
                                            >
                                            <select
                                                v-model="autoRoomForm.status"
                                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                            >
                                                <option value="available">
                                                    Available
                                                </option>
                                                <option value="occupied">
                                                    Occupied
                                                </option>
                                                <option value="maintenance">
                                                    Maintenance
                                                </option>
                                            </select>
                                            <p
                                                v-if="
                                                    autoRoomForm.errors.status
                                                "
                                                class="mt-1 text-xs text-red-600"
                                            >
                                                {{ autoRoomForm.errors.status }}
                                            </p>
                                        </div>

                                        <div
                                            class="md:col-span-4 rounded-2xl border border-dashed border-slate-300 bg-white p-4"
                                        >
                                            <div
                                                class="flex items-center justify-between gap-3"
                                            >
                                                <div>
                                                    <h5
                                                        class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500"
                                                    >
                                                        Preview
                                                    </h5>
                                                    <p
                                                        class="mt-1 text-sm text-slate-600"
                                                    >
                                                        Example: `A-101` with
                                                        `3` rooms becomes
                                                        `A-101`, `A-102`,
                                                        `A-103`.
                                                    </p>
                                                </div>
                                                <span
                                                    class="rounded-full bg-slate-50 px-3 py-1 text-sm font-semibold text-slate-700 ring-1 ring-slate-200"
                                                >
                                                    {{
                                                        generatedRooms.length
                                                    }}
                                                    rooms
                                                </span>
                                            </div>

                                            <div
                                                class="mt-4 flex flex-wrap gap-2"
                                            >
                                                <span
                                                    v-for="room in generatedRooms"
                                                    :key="room"
                                                    class="rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700 ring-1 ring-blue-200"
                                                >
                                                    {{ room }}
                                                </span>
                                                <span
                                                    v-if="
                                                        generatedRooms.length ===
                                                        0
                                                    "
                                                    class="text-sm text-slate-500"
                                                >
                                                    Type a valid start room like
                                                    `A-101`.
                                                </span>
                                            </div>
                                        </div>

                                        <div class="md:col-span-4">
                                            <button
                                                type="submit"
                                                class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                                                :disabled="
                                                    autoRoomForm.processing ||
                                                    generatedRooms.length === 0
                                                "
                                            >
                                                {{
                                                    autoRoomForm.processing
                                                        ? "Generating..."
                                                        : "Create Auto Rooms"
                                                }}
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <div
                                    v-if="floor.rooms.length === 0"
                                    class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-sm text-slate-500"
                                >
                                    No rooms on this floor yet.
                                </div>

                                <div
                                    v-for="room in floor.rooms"
                                    :key="room.id"
                                    class="rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-4"
                                >
                                    <div
                                        class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
                                    >
                                        <div>
                                            <div
                                                class="flex flex-wrap items-center gap-2"
                                            >
                                                <p
                                                    class="text-base font-semibold text-slate-900"
                                                >
                                                    {{ room.room_number }}
                                                </p>
                                                <span
                                                    :class="[
                                                        'rounded-full px-2.5 py-1 text-xs font-semibold',
                                                        statusClass(
                                                            room.status,
                                                        ),
                                                    ]"
                                                >
                                                    {{ room.status }}
                                                </span>
                                            </div>
                                            <p
                                                class="mt-2 text-sm text-slate-500"
                                            >
                                                Capacity:
                                                {{ room.capacity ?? "N/A" }}
                                            </p>
                                        </div>

                                        <div class="flex flex-wrap gap-2">
                                            <button
                                                type="button"
                                                class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                                                @click="
                                                    openRoomEdit(
                                                        building,
                                                        floor,
                                                        room,
                                                    )
                                                "
                                            >
                                                Edit Room
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100"
                                                @click="
                                                    deleteRoom(
                                                        building,
                                                        floor,
                                                        room,
                                                    )
                                                "
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </div>

                                    <div
                                        v-if="
                                            roomFormVisibleFor(
                                                building.id,
                                                floor.id,
                                                room.id,
                                            )
                                        "
                                        class="mt-4 rounded-2xl border border-white bg-white p-4"
                                    >
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <h5
                                                class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500"
                                            >
                                                Room Editor
                                            </h5>
                                            <button
                                                type="button"
                                                class="text-sm font-semibold text-slate-500 transition hover:text-slate-800"
                                                @click="closeRoomForm"
                                            >
                                                Cancel
                                            </button>
                                        </div>

                                        <form
                                            class="mt-4 grid gap-4 md:grid-cols-4"
                                            @submit.prevent="submitRoom"
                                        >
                                            <div>
                                                <label
                                                    class="mb-1.5 block text-sm font-medium text-slate-700"
                                                    >Room Number</label
                                                >
                                                <input
                                                    v-model="
                                                        roomForm.room_number
                                                    "
                                                    type="text"
                                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                                />
                                                <p
                                                    v-if="
                                                        roomForm.errors
                                                            .room_number
                                                    "
                                                    class="mt-1 text-xs text-red-600"
                                                >
                                                    {{
                                                        roomForm.errors
                                                            .room_number
                                                    }}
                                                </p>
                                            </div>

                                            <div>
                                                <label
                                                    class="mb-1.5 block text-sm font-medium text-slate-700"
                                                    >Capacity</label
                                                >
                                                <input
                                                    v-model="roomForm.capacity"
                                                    type="number"
                                                    min="1"
                                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                                />
                                                <p
                                                    v-if="
                                                        roomForm.errors.capacity
                                                    "
                                                    class="mt-1 text-xs text-red-600"
                                                >
                                                    {{
                                                        roomForm.errors.capacity
                                                    }}
                                                </p>
                                            </div>

                                            <div>
                                                <label
                                                    class="mb-1.5 block text-sm font-medium text-slate-700"
                                                    >Status</label
                                                >
                                                <select
                                                    v-model="roomForm.status"
                                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                                >
                                                    <option value="available">
                                                        Available
                                                    </option>
                                                    <option value="occupied">
                                                        Occupied
                                                    </option>
                                                    <option value="maintenance">
                                                        Maintenance
                                                    </option>
                                                </select>
                                                <p
                                                    v-if="
                                                        roomForm.errors.status
                                                    "
                                                    class="mt-1 text-xs text-red-600"
                                                >
                                                    {{ roomForm.errors.status }}
                                                </p>
                                            </div>

                                            <div class="flex items-end">
                                                <button
                                                    type="submit"
                                                    class="inline-flex w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                                                    :disabled="
                                                        roomForm.processing
                                                    "
                                                >
                                                    Save Room
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
        </section>
    </DashboardLayout>
</template>
