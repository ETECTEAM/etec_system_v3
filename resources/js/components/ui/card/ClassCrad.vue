<script setup>
import { router } from "@inertiajs/vue3";
import {GraduationCap,Building2,DoorOpen,CalendarDays,Clock3,Users,BookOpen,} from "@lucide/vue";
import { ref, computed } from "vue";
import NotificationBadge from "../notification-badge/NotificationBadge.vue";
import ClassActionMenu from "./ClassActionMenu.vue";
import BarClass from "../../../pages/backend/students/components/BarClass.vue";
import { route } from "ziggy-js";

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
    "attendance",
    "export",
    "pre-end",
    "end",
]);

const capacity = computed(() => props.classData.capacity);

const fill = computed(() => {
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

function showViewClass(){
    router.visit(route('students.show', { class: props.classData.id }));
}
</script>

<template>
<div class="group relative flex flex-col bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-xl hover:-translate-y-0.5 hover:border-indigo-200 transition-all duration-300">
    <NotificationBadge :count="props.count" />
    <div class="p-5 sm:p-6 flex flex-col flex-1">

        <!-- Header -->
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-start gap-3 min-w-0">
                <div
                    class="shrink-0 flex items-center justify-center w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100"
                >
                    <GraduationCap class="w-5 h-5 sm:w-6 sm:h-6" />
                </div>

                <div class="min-w-0">
                    <h3
                        class="text-sm sm:text-base font-semibold text-slate-900 truncate group-hover:text-indigo-600 transition-colors"
                    >
                        {{ classData.title }}
                    </h3>

                    <div class="flex items-center gap-2 mt-1.5">
                        <span class="text-[11px] font-medium uppercase tracking-wider text-slate-400">
                            ID
                        </span>
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[11px] font-semibold tabular-nums leading-none"
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
                <div class="flex items-center gap-2 text-slate-500">
                    <BookOpen class="w-3.5 h-3.5 shrink-0" />
                    <span class="text-xs sm:text-sm">Lesson</span>
                </div>
                <span class="text-xs sm:text-sm font-medium text-slate-800 text-right truncate">
                    {{ classData.lesson }}
                </span>
            </div>

            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 text-slate-500">
                    <Building2 class="w-3.5 h-3.5 shrink-0" />
                    <span class="text-xs sm:text-sm">Building</span>
                </div>
                <span class="text-xs sm:text-sm font-medium text-slate-800 text-right truncate">
                    {{ classData.building }}
                </span>
            </div>

            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 text-slate-500">
                    <DoorOpen class="w-3.5 h-3.5 shrink-0" />
                    <span class="text-xs sm:text-sm">Room</span>
                </div>
                <span class="text-xs sm:text-sm font-medium text-slate-800 text-right truncate">
                    {{ classData.floor }} {{ classData.room }}
                </span>
            </div>

            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 text-slate-500">
                <Users class="w-3.5 h-3.5 shrink-0"/>
                <span class="text-xs sm:text-sm">Status</span>
                </div>
                <span
                    :class="[
                        'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold shrink-0 ring-1',
                        statusStyle,
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
                <div class="flex items-center gap-2 text-slate-500">
                    <CalendarDays class="w-3.5 h-3.5 shrink-0" />
                    <span class="text-xs sm:text-sm">Days</span>
                </div>
                <span class="text-xs sm:text-sm font-medium text-slate-800 text-right truncate">
                    {{ classData.term }}
                </span>
            </div>

            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 text-slate-500">
                    <Clock3 class="w-3.5 h-3.5 shrink-0" />
                    <span class="text-xs sm:text-sm">Time</span>
                </div>
                <span class="text-xs sm:text-sm font-medium text-emerald-600 text-right truncate">
                    {{ classData.time }}
                </span>
            </div>
        </div>

        <!-- Student Progress -->
        <div class="mt-4 sm:mt-5 pt-4 sm:pt-5 border-t border-slate-100">
            <div class="flex items-center justify-between gap-2 mb-2">
                <div class="flex items-center gap-2 text-slate-500">
                    <Users class="w-3.5 h-3.5 shrink-0" />
                    <span class="text-xs sm:text-sm">Students</span>
                </div>
                <span class="text-xs sm:text-sm font-semibold text-slate-800 tabular-nums">
                    {{ classData.students }} / {{ capacity }}
                </span>
            </div>

            <div
                class="h-2 bg-slate-100 rounded-full overflow-hidden cursor-pointer"
                @click="showBarDialog = true"
            >
                <div
                    class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 transition-all duration-700 ease-out"
                    :style="{ width: Math.min(fill, 100) + '%' }"
                ></div>
            </div>
        </div>

        <!-- Footer -->
        <button type="button"
            @click="showViewClass"
            class="mt-4 sm:mt-5 w-full inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 transition-all"
        >
            View Class
        </button>

    </div>
</div>

<BarClass
    :show="showBarDialog"
    :classData="classData"
    @close="showBarDialog = false"
/>
</template>
