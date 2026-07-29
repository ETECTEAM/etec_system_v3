<script setup>
import { router } from "@inertiajs/vue3";
import {GraduationCap,Building2,DoorOpen,CalendarDays,Clock3,Users,BookOpen,} from "@lucide/vue";
import { ref, computed } from "vue";
import { QrcodeCanvas } from "qrcode.vue";
import NotificationBadge from "../notification-badge/NotificationBadge.vue";
import ClassActionMenu from "./ClassActionMenu.vue";
import BarClass from "../../../pages/backend/students/components/BarClass.vue";
// import { router } from "@inertiajs/vue3";

const props = defineProps({
    classData: Object,
    count: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits([
    "edit",
    "add-student",
    "qr",
    "switch-teacher",
    "view",
    // "attendance",
    // "export",
    "pre-end",
    "end",
]);

const capacity = computed(() => props.classData.capacity);
const online = computed(() =>
    props.classData.class_type === "online" ||
    String(props.classData.status ?? "").toLowerCase().includes("online")
);

const fill = computed(() => {
    if (!capacity.value) return 0;
    return (props.classData.students / capacity.value) * 100;
});

const statusStyle = computed(() => {
    switch (props.classData.status) {
        case 'active':    return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
        case 'inactive':  return 'bg-slate-100 text-slate-600 ring-slate-400/20';
        case 'completed': return 'bg-blue-50 text-blue-700 ring-blue-600/20';
        default:          return 'bg-blue-50 text-blue-700 ring-blue-600/20';
    }
});

const showBarDialog = ref(false);
const showQrDialog = ref(false);
const qrUrl = computed(() => `${window.location.origin}/dashboard/students/${props.classData.id}/students/create`);

function showViewClass () { 
   router.get(`/dashboard/students/view/${props.classData.id}`);
}

function showEditClass() {
    router.get(`/dashboard/students/edit/${props.classData.id}`);
}

function showAddStudent() {
    router.get(`/dashboard/students/${props.classData.id}/students/create`);
}

function showQr() {
    emit("qr", props.classData);
    showQrDialog.value = true;
}

function notifyPendingAction(label) {
    window.alert(`${label} is not available yet.`);
}

function updateStatus(status) {
    router.post(`/dashboard/students/${props.classData.id}/status`, { status }, {
        preserveScroll: true,
        onFinish: () => {
            showBarDialog.value = false;
        },
    });
}
</script>

<template>
<div
    class="group relative flex flex-col bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-xl hover:-translate-y-0.5 hover:border-indigo-200 transition-all duration-300 dark:bg-gray-900 dark:border-gray-800 dark:hover:border-indigo-500/40"
>
    <NotificationBadge :count="props.count" />
    <div class="p-5 sm:p-6 flex flex-col flex-1">

        <!-- Header -->
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-start gap-3 min-w-0">
                <div
                    class="shrink-0 flex items-center justify-center w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-400 dark:ring-indigo-500/20"
                >
                    <GraduationCap class="w-5 h-5 sm:w-6 sm:h-6" />
                </div>

                <div class="min-w-0">
                    <h3
                        class="text-sm sm:text-base font-semibold text-slate-900 truncate group-hover:text-indigo-600 transition-colors dark:text-gray-100 dark:group-hover:text-indigo-400"
                    >
                        {{ classData.title }}
                    </h3>

                    <div class="flex items-center gap-2 mt-1.5">
                        <span class="text-[11px] font-medium uppercase tracking-wider text-slate-400 dark:text-gray-500">
                            ID
                        </span>
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[11px] font-semibold tabular-nums leading-none dark:bg-gray-800 dark:text-gray-400"
                        >
                            #{{ classData.id }}
                        </span>
                    </div>
                </div>
            </div>

            <ClassActionMenu
                :classData="classData"
                @open-bar="showBarDialog = true"
            />
        </div>

        <!-- Information -->
        <div class="mt-4 sm:mt-5 space-y-3 flex-1">

            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 text-slate-500 dark:text-gray-400">
                    <BookOpen class="w-3.5 h-3.5 shrink-0" />
                    <span class="text-xs sm:text-sm">Lesson</span>
                </div>
                <span class="text-xs sm:text-sm font-medium text-slate-800 text-right truncate dark:text-gray-200">
                    {{ classData.lesson }}
                </span>
            </div>

            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 text-slate-500 dark:text-gray-400">
                    <Building2 class="w-3.5 h-3.5 shrink-0" />
                    <span class="text-xs sm:text-sm">Building</span>
                </div>
                <span class="text-xs sm:text-sm font-medium text-slate-800 text-right truncate dark:text-gray-200">
                    {{ classData.building }}
                </span>
            </div>

            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 text-slate-500 dark:text-gray-400">
                    <DoorOpen class="w-3.5 h-3.5 shrink-0" />
                    <span class="text-xs sm:text-sm">Room</span>
                </div>
                <span class="text-xs sm:text-sm font-medium text-slate-800 text-right truncate dark:text-gray-200">
                    {{ classData.floor }} {{ classData.room }}
                </span>
            </div>

            <div class="flex items-center justify-between gap-2">
                <span class="text-xs sm:text-sm text-slate-500 dark:text-gray-400">Status</span>
                <span
                    :class="[
                        'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold shrink-0',
                        online
                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400'
                            : 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400'
                    ]"
                >
                    <span
                        :class="[
                            'w-1.5 h-1.5 rounded-full',
                            classData.status === 'active' ? 'bg-emerald-500' : classData.status === 'inactive' ? 'bg-slate-400' : 'bg-blue-500',
                        ]"
                    ></span>
                    {{ classData.status }}
                </span>
            </div>

            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 text-slate-500 dark:text-gray-400">
                    <CalendarDays class="w-3.5 h-3.5 shrink-0" />
                    <span class="text-xs sm:text-sm">Days</span>
                </div>
                <span class="text-xs sm:text-sm font-medium text-slate-800 text-right truncate dark:text-gray-200">
                    {{ classData.term }}
                </span>
            </div>

            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 text-slate-500 dark:text-gray-400">
                    <Clock3 class="w-3.5 h-3.5 shrink-0" />
                    <span class="text-xs sm:text-sm">Time</span>
                </div>
                <span class="text-xs sm:text-sm font-medium text-emerald-600 text-right truncate dark:text-emerald-400">
                    {{ classData.time }}
                </span>
            </div>
        </div>

        <!-- ─── Student Progress ─── -->
        <div class="mt-4 sm:mt-5 pt-4 sm:pt-5 border-t border-slate-100 dark:border-gray-800">
            <div class="flex items-center justify-between gap-2 mb-2">
                <div class="flex items-center gap-2 text-slate-500 dark:text-gray-400">
                    <Users class="w-3.5 h-3.5 shrink-0" />
                    <span class="text-xs sm:text-sm">Students</span>
                </div>
                <span class="text-xs sm:text-sm font-semibold text-slate-800 tabular-nums dark:text-gray-200">
                    {{ classData.students }} / {{ capacity }}
                </span>
            </div>

            <div
                class="h-2 bg-slate-100 rounded-full overflow-hidden cursor-pointer dark:bg-gray-800"
                @click="showBarDialog = true"
            >
                <div
                    class="h-full rounded-full bg-gradient-to-r from-blue-900 to-blue-900 transition-all duration-700 ease-out dark:from-blue-500 dark:to-blue-500"
                    :style="{ width: Math.min(fill, 100) + '%' }"
                ></div>
            </div>

            <div class="flex items-center justify-between mt-1">
                <span class="text-[11px] text-slate-400 tabular-nums dark:text-gray-500">
                    {{ Math.round(fill) }}% filled
                </span>
                <span class="text-[11px] text-slate-400 tabular-nums dark:text-gray-500">
                    {{ Math.max(0, capacity - classData.students) }} left
                </span>
            </div>
        </div>

        <!-- ─── Footer ─── -->
        <button
            type="button"
            @click="showViewClass"
            class="mt-4 sm:mt-5 w-full inline-flex items-center justify-center gap-2 rounded-xl bg-blue-900 px-4 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-blue-800 active:bg-indigo-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 transition-all dark:bg-blue-600 dark:hover:bg-blue-500 dark:focus-visible:ring-offset-gray-900"
        >
            View Class
        </button>

    </div>
</div>

<BarClass
    :show="showBarDialog"
    :classData="classData"
    @close="showBarDialog = false"
    @view="showViewClass"
    @edit="showEditClass"
    @add-student="showAddStudent"
    @qr="showQr"
    @switch-teacher="notifyPendingAction('Switch teacher')"
    @attendance="notifyPendingAction('Attendance')"
    @export="notifyPendingAction('Export student list')"
    @pre-end="updateStatus('inactive')"
    @end="updateStatus('completed')"
/>

<Teleport to="body">
    <div v-if="showQrDialog" class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/50 px-4" @click.self="showQrDialog = false">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-xl dark:bg-gray-900">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">
                Generate QR
            </h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                {{ classData.title }}
            </p>

            <div class="mt-5 inline-flex rounded-2xl bg-white p-4 shadow-inner">
                <QrcodeCanvas :value="qrUrl" :size="220" level="H" />
            </div>

            <a :href="qrUrl" target="_blank" class="mt-4 block break-all text-xs text-blue-700 hover:underline dark:text-blue-400">
                {{ qrUrl }}
            </a>

            <button type="button" @click="showQrDialog = false" class="mt-5 w-full rounded-xl bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500">
                Close
            </button>
        </div>
    </div>
</Teleport>
</template>
