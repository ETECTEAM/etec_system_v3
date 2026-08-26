<script setup>
import { router } from "@inertiajs/vue3";
import {GraduationCap,Building2,DoorOpen,CalendarDays,Clock3,Users,Users2,BookOpen,UserRound,} from "@lucide/vue";
import { ref, computed } from "vue";
import { QrcodeCanvas } from "qrcode.vue";
import NotificationBadge from "../notification-badge/NotificationBadge.vue";
import ClassActionMenu from "./ClassActionMenu.vue";
import CollapseClassModal from "./CollapseClassModal.vue";
import BarClass from "../../../pages/backend/students/components/BarClass.vue";
import { useConfirm } from "@/composables/useConfirm";
// import { router } from "@inertiajs/vue3";

const props = defineProps({
    classData: Object,
    count: {
        type: Number,
        default: 0,
    },
    // "View Class" target. The default enrollment page is super_admin only, so the
    // instructor dashboard points this at the instructor's own class screen.
    viewUrl: {
        type: String,
        default: null,
    },
    // Extra { label, icon, action } entries for the action menu, e.g. Attendance.
    extraItems: {
        type: Array,
        default: () => [],
    },
    // Off on the instructor dashboard, where every class is the viewer's own.
    showInstructor: {
        type: Boolean,
        default: true,
    },
    // Labels the action menu should leave out, e.g. "Copy Class" for instructors.
    hiddenItems: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits([
    "edit",
    "copy",
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
const lifecycleStatus = computed(() => String(props.classData.class_status ?? "").toLowerCase());
const normalizedLifecycleStatus = computed(() => {
    switch (lifecycleStatus.value) {
        case 'inactive':
            return 'pre_end';
        case 'completed':
            return 'ended';
        default:
            return lifecycleStatus.value;
    }
});
const lockedStudentActions = computed(() => ['pre_end', 'ended', 'cancelled'].includes(normalizedLifecycleStatus.value));
const lifecycleLabel = computed(() => {
    switch (lifecycleStatus.value) {
        case 'upcoming':
            return 'Upcoming';
        case 'active':
            return 'Active';
        case 'pre_end':
            return 'Pre-End';
        case 'ended':
            return 'Ended';
        case 'cancelled':
            return 'Cancelled';
        case 'inactive':
            return 'Pre-End';
        case 'completed':
            return 'Ended';
        default:
            return props.classData.class_status_label ?? props.classData.class_status ?? "—";
    }
});

const fill = computed(() => {
    if (!capacity.value) return 0;
    return (props.classData.students / capacity.value) * 100;
});

const statusStyle = computed(() => {
    switch (normalizedLifecycleStatus.value) {
        case 'active':
            return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
        case 'pre_end':
            return 'bg-amber-50 text-amber-700 ring-amber-600/20';
        case 'ended':
            return 'bg-slate-100 text-slate-600 ring-slate-400/20';
        case 'cancelled':
            return 'bg-rose-50 text-rose-700 ring-rose-600/20';
        default:
            return 'bg-blue-50 text-blue-700 ring-blue-600/20';
    }
});

const cardToneClasses = computed(() => {
    switch (normalizedLifecycleStatus.value) {
        case 'pre_end':
            return 'border-amber-200 bg-amber-50/70 hover:border-amber-300 dark:border-amber-500/20 dark:bg-amber-500/10 dark:hover:border-amber-400/30';
        case 'ended':
            return 'border-slate-200 bg-slate-100/70 hover:border-slate-300 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-gray-700';
        case 'active':
            return 'border-emerald-200 bg-white hover:border-emerald-200 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-emerald-500/30';
        default:
            return 'border-slate-200 bg-white hover:border-indigo-200 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-500/40';
    }
});

const showBarDialog = ref(false);
const showQrDialog = ref(false);
const showCollapseDialog = ref(false);
const { confirm } = useConfirm();

// "Collapse Class" splits the class between two instructors, each teaching their own
// days — offered for Basic IT classes only. The card owns the dialog, so it hands
// the menu the entry that opens it.
const canCollapse = computed(() =>
    ["course", "title"].some((field) =>
        String(props.classData?.[field] ?? "").trim().toLowerCase() === "basic it"
    )
);

const menuItems = computed(() => [
    ...props.extraItems,
    ...(canCollapse.value
        ? [
            {
                label: "Collapse Class",
                icon: Users,
                action: () => {
                    showCollapseDialog.value = true;
                },
                disabled: lockedStudentActions.value,
            },
        ]
        : []),
]);
const qrUrl = computed(() => `${window.location.origin}/join-class/${props.classData.id}`);

function showViewClass () {
   router.get(props.viewUrl ?? `/dashboard/enroll/view/${props.classData.id}`);
}

function showEditClass() {
    router.get(`/dashboard/enroll/edit/${props.classData.id}`);
}

function showCopyClass() {
    router.get(`/dashboard/enroll/copy/${props.classData.id}`);
}

function showAddStudent() {
    router.get(`/dashboard/enroll/${props.classData.id}/students/create`);
}

function showQr() {
    emit("qr", props.classData);
    showQrDialog.value = true;
}

function notifyPendingAction(label) {
    window.alert(`${label} is not available yet.`);
}

// The dialog lists the same actions as the menu, so an extra item (the instructor's
// Attendance page) wins over the "not available yet" placeholder.
function runExtraAction(label) {
    const item = props.extraItems.find((extra) => extra.label === label);

    return item ? item.action?.() : notifyPendingAction(label);
}

function updateStatus(status) {
    router.post(`/dashboard/enroll/${props.classData.id}/status`, { status }, {
        preserveScroll: true,
        onFinish: () => {
            showBarDialog.value = false;
        },
    });
}

async function confirmPreEnd() {
    const ok = await confirm({
        title: "Pre-End Class?",
        message: "This will lock attendance tracking and prevent new students from joining. Are you sure you want to pre-end this class?",
        confirmText: "Pre-End",
        cancelText: "Cancel",
        danger: true,
    });
    if (!ok) return;
    updateStatus("inactive");
}

async function confirmEnd() {
    const ok = await confirm({
        title: "End Class?",
        message: "This will permanently end the class and lock all activity. Are you sure you want to end this class?",
        confirmText: "End",
        cancelText: "Cancel",
        danger: true,
    });
    if (!ok) return;
    updateStatus("completed");
}
</script>

<template>
<div
    :class="[
        'group relative flex flex-col rounded-2xl border shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl',
        cardToneClasses,
    ]"
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
                    <div class="flex items-center gap-2 min-w-0">
                        <h3
                            class="text-sm sm:text-base font-semibold text-slate-900 truncate group-hover:text-indigo-600 transition-colors dark:text-gray-100 dark:group-hover:text-indigo-400"
                        >
                            {{ classData.title }}
                        </h3>
                        <span
                            v-if="classData.is_shared"
                            class="inline-flex shrink-0 items-center gap-1 rounded-full bg-violet-50 px-2 py-0.5 text-[11px] font-semibold text-violet-700 dark:bg-violet-500/10 dark:text-violet-400"
                        >
                            <Users2 class="h-3 w-3" />
                            {{ $t('Shared') }}
                        </span>
                    </div>

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

                    <p v-if="classData.is_shared" class="mt-1 truncate text-[11px] font-medium text-slate-500 dark:text-gray-400">
                        {{ $t('Shared with') }}: <span class="font-semibold text-slate-700 dark:text-gray-300">{{ classData.shared_with }}</span>
                        <span v-if="classData.subject"> · {{ $t('Teaching') }}: <span class="font-semibold text-slate-700 dark:text-gray-300">{{ classData.subject }}</span></span>
                    </p>
                </div>
            </div>

            <ClassActionMenu
                :classData="classData"
                :viewUrl="viewUrl"
                :extraItems="menuItems"
                :hiddenItems="hiddenItems"
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

            <div v-if="showInstructor" class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 text-slate-500 dark:text-gray-400">
                    <UserRound class="w-3.5 h-3.5 shrink-0" />
                    <span class="text-xs sm:text-sm">Instructor</span>
                </div>
                <span class="text-xs sm:text-sm font-medium text-slate-800 text-right truncate dark:text-gray-200">
                    {{ classData.teacher }}
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
                        statusStyle,
                        'ring-1 ring-inset',
                    ]"
                >
                    <span
                        :class="[
                        'w-1.5 h-1.5 rounded-full',
                            normalizedLifecycleStatus === 'active'
                                ? 'bg-emerald-500'
                                : normalizedLifecycleStatus === 'pre_end'
                                    ? 'bg-amber-500'
                                    : normalizedLifecycleStatus === 'ended'
                                        ? 'bg-slate-400'
                                        : 'bg-blue-500',
                        ]"
                    ></span>
                    {{ lifecycleLabel }}
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

<CollapseClassModal
    :show="showCollapseDialog"
    :classData="classData"
    @close="showCollapseDialog = false"
/>

<BarClass
    :show="showBarDialog"
    :classData="classData"
    :hiddenItems="hiddenItems"
    @close="showBarDialog = false"
    @view="showViewClass"
    @edit="showEditClass"
    @copy="showCopyClass"
    @add-student="showAddStudent"
    @qr="showQr"
    @switch-teacher="notifyPendingAction('Switch teacher')"
    @attendance="runExtraAction('Attendance')"
    @export="notifyPendingAction('Export student list')"
    @pre-end="confirmPreEnd"
    @end="confirmEnd"
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
