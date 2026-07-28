<script setup>
import { ref, watch, nextTick, onMounted, onUnmounted } from "vue";
import {
    GraduationCap,
    Pencil,
    UserPlus,
    QrCode,
    RefreshCcw,
    Eye,
    ClipboardCheck,
    Download,
    AlertTriangle,
    CircleStop,
    X,
} from "@lucide/vue";

const props = defineProps({
    show: Boolean,
    classData: Object,
});

const emit = defineEmits([
    "close",
    "edit",
    "add-student",
    "qr",
    "switch-teacher",
    "view",
    "attendance",
    "export",
    "pre-end",
    "end",
]);

const dialogRef = ref(null);

const items = [
    { icon: Pencil, label: "Edit Class", event: "edit" },
    { icon: UserPlus, label: "Add Student", event: "add-student" },
    { icon: QrCode, label: "QR Add", event: "qr" },
    { icon: RefreshCcw, label: "Switch Teacher", event: "switch-teacher" },
    { icon: Eye, label: "View Details", event: "view" },
    { icon: ClipboardCheck, label: "Attendance", event: "attendance" },
    { icon: Download, label: "Export Student List", event: "export" },
];

const dangerItems = [
    { icon: AlertTriangle, label: "Pre-End Class", event: "pre-end", danger: "warning" },
    { icon: CircleStop, label: "End Class", event: "end", danger: "critical" },
];

watch(() => props.show, (val) => {
    if (val) {
        document.body.style.overflow = "hidden";
        nextTick(() => {
            dialogRef.value?.focus();
        });
    } else {
        document.body.style.overflow = "";
    }
});

function onBackdropClick(e) {
    if (e.target === e.currentTarget) {
        emit("close");
    }
}

function onDocumentKeydown(e) {
    if (e.key === "Escape" && props.show) {
        emit("close");
    }
}

function onItemClick(event) {
    emit(event);
    if (event !== "end" && event !== "pre-end") {
        emit("close");
    }
}

onMounted(() => {
    document.addEventListener("keydown", onDocumentKeydown);
});

onUnmounted(() => {
    document.removeEventListener("keydown", onDocumentKeydown);
    document.body.style.overflow = "";
});
</script>

<template>
<Teleport to="body">
    <transition
        enter-active-class="transition duration-200 ease-out"
        leave-active-class="transition duration-150 ease-in"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="show"
            ref="dialogRef"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
            tabindex="-1"
            @click="onBackdropClick"
        >
            <transition
                enter-active-class="transition duration-200 ease-out"
                leave-active-class="transition duration-150 ease-in"
                enter-from-class="opacity-0 scale-95 translate-y-4"
                enter-to-class="opacity-100 scale-100 translate-y-0"
                leave-from-class="opacity-100 scale-100 translate-y-0"
                leave-to-class="opacity-0 scale-95 translate-y-4"
            >
                <div
                    v-if="show"
                    class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden dark:bg-gray-900 dark:border-gray-800"
                >

                    <!-- ─── Header ─── -->
                    <div class="flex items-center justify-between px-5 sm:px-6 pt-5 sm:pt-6 pb-4">
                        <div class="flex items-start gap-3 min-w-0">
                            <div class="shrink-0 flex items-center justify-center w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-400 dark:ring-indigo-500/20">
                                <GraduationCap class="w-5 h-5" />
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-base sm:text-lg font-semibold text-slate-900 truncate dark:text-gray-100">
                                    {{ classData?.title }}
                                </h3>
                                <p class="text-sm text-slate-500 mt-0.5 dark:text-gray-400">
                                    <span class="tabular-nums">#{{ classData?.id }}</span>
                                    <span class="mx-1.5 text-slate-300 dark:text-gray-600">{{ $t('&middot;') }}</span>
                                    <span class="tabular-nums">{{ classData?.students }} / {{ classData?.capacity }} students</span>
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="emit('close')"
                            class="shrink-0 p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 active:bg-slate-200 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-gray-500 dark:hover:text-gray-300 dark:hover:bg-gray-800 dark:active:bg-gray-700"
                            :aria-label="$t('Close dialog')"
                        >
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- ─── Actions ─── -->
                    <div class="px-3 sm:px-4 pb-2 space-y-0.5">
                        <button
                            v-for="item in items"
                            :key="item.event"
                            type="button"
                            @click="onItemClick(item.event)"
                            class="flex items-center gap-3 w-full px-3 py-2.5 sm:py-3 rounded-xl text-sm font-medium text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 active:bg-indigo-100 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-gray-300 dark:hover:bg-indigo-500/10 dark:hover:text-indigo-400 dark:active:bg-indigo-500/20"
                        >
                            <component :is="item.icon" class="w-4 h-4 shrink-0 text-slate-400 group-hover:text-indigo-500 dark:text-gray-500" />
                            <span>{{ item.label }}</span>
                        </button>
                    </div>

                    <!-- ─── Divider ─── -->
                    <div class="mx-5 sm:mx-6 my-2 border-t border-slate-100 dark:border-gray-800"></div>

                    <!-- ─── Danger Zone ─── -->
                    <div class="px-3 sm:px-4 pb-3 sm:pb-4 space-y-0.5">
                        <p class="px-3 pt-1 pb-1.5 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500">
                            Danger Zone
                        </p>
                        <button
                            v-for="item in dangerItems"
                            :key="item.event"
                            type="button"
                            @click="onItemClick(item.event)"
                            :class="[
                                'flex items-center gap-3 w-full px-3 py-2.5 sm:py-3 rounded-xl text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2',
                                item.danger === 'critical'
                                    ? 'text-red-600 hover:bg-red-50 active:bg-red-100 focus-visible:ring-red-500 dark:text-red-400 dark:hover:bg-red-500/10 dark:active:bg-red-500/20'
                                    : 'text-amber-600 hover:bg-amber-50 active:bg-amber-100 focus-visible:ring-amber-500 dark:text-amber-400 dark:hover:bg-amber-500/10 dark:active:bg-amber-500/20',
                            ]"
                        >
                            <component :is="item.icon" :class="[
                                'w-4 h-4 shrink-0',
                                item.danger === 'critical' ? 'text-red-500 dark:text-red-400' : 'text-amber-500 dark:text-amber-400',
                            ]" />
                            <span class="font-semibold">{{ item.label }}</span>
                        </button>
                    </div>

                </div>
            </transition>
        </div>
    </transition>
</Teleport>
</template>
