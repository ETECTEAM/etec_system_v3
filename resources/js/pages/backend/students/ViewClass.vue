<script setup>
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import {
    ArrowLeft,
    GraduationCap,
    BookOpen,
    Building2,
    DoorOpen,
    CalendarDays,
    Clock3,
    Users,
    MonitorSmartphone,
    UserCheck,
} from "@lucide/vue";
import DashboardLayout from "../../../layouts/DashboardLayout.vue";
import Breadcrumbs from "../../../components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "../../../components/ui/page-hero/PageHero.vue";

const props = defineProps({
    classData: Object,
    enrolledStudents: Array,
});

const breadcrumbItems = [
    { label: "Dashboard", href: "/dashboard" },
    { label: "Class List", href: route("students.index") },
    { label: "View Class", current: true },
];

const enrollmentPercent = Math.min(
    (props.classData.students / props.classData.capacity) * 100,
    100
);

function goBack() {
    router.visit(route("students.index"));
}
</script>

<template>
    <DashboardLayout>
        <div class="w-full">
            <div class="mb-6">
                <Breadcrumbs :items="breadcrumbItems" />

                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mt-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-indigo-100 flex items-center justify-center shrink-0">
                            <GraduationCap class="w-7 h-7 text-indigo-600" />
                        </div>
                        <div>
                            <PageHero
                                eyebrow="EnRoll Management"
                                :title="classData.title"
                            />
                            <p class="text-sm text-slate-500 mt-0.5">Class #{{ classData.id }}</p>
                        </div>
                    </div>

                    <button
                        @click="goBack"
                        class="inline-flex items-center gap-2 bg-white text-slate-700 border border-slate-300 px-5 py-3 rounded-xl hover:bg-slate-50 transition self-start sm:self-auto whitespace-nowrap cursor-pointer"
                    >
                        <ArrowLeft class="w-4 h-4" />
                        Back to Class List
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                        <h2 class="text-lg font-semibold text-slate-900 mb-4">Class Information</h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-6">
                            <div class="flex items-center gap-3">
                                <BookOpen class="w-5 h-5 text-slate-400 shrink-0" />
                                <div>
                                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Lesson</p>
                                    <p class="text-sm font-semibold text-slate-800">{{ classData.lesson ?? '—' }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <Building2 class="w-5 h-5 text-slate-400 shrink-0" />
                                <div>
                                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Building</p>
                                    <p class="text-sm font-semibold text-slate-800">{{ classData.building ?? '—' }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <DoorOpen class="w-5 h-5 text-slate-400 shrink-0" />
                                <div>
                                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Floor / Room</p>
                                    <p class="text-sm font-semibold text-slate-800">{{ classData.floor ?? '—' }} {{ classData.room ?? '' }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <CalendarDays class="w-5 h-5 text-slate-400 shrink-0" />
                                <div>
                                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Study Day</p>
                                    <p class="text-sm font-semibold text-slate-800">{{ classData.term ?? '—' }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <Clock3 class="w-5 h-5 text-slate-400 shrink-0" />
                                <div>
                                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Study Time</p>
                                    <p class="text-sm font-semibold text-slate-800">{{ classData.time ?? '—' }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <MonitorSmartphone class="w-5 h-5 text-slate-400 shrink-0" />
                                <div>
                                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Status</p>
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold',
                                            classData.status === 'active'
                                                ? 'bg-emerald-50 text-emerald-700'
                                                : classData.status === 'inactive'
                                                    ? 'bg-slate-100 text-slate-600'
                                                    : 'bg-blue-50 text-blue-700',
                                        ]"
                                    >
                                        {{ classData.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                        <h2 class="text-lg font-semibold text-slate-900 mb-4">Enrolled Students</h2>

                        <div v-if="enrolledStudents.length" class="space-y-2">
                            <div
                                v-for="student in enrolledStudents"
                                :key="student.id"
                                class="flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-slate-100 transition"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-semibold">
                                        {{ student.full_name?.charAt(0) ?? '?' }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800">{{ student.full_name }}</p>
                                        <p class="text-xs text-slate-500">{{ student.phone ?? '—' }}</p>
                                    </div>
                                </div>
                                <span class="text-xs text-slate-500 capitalize">{{ student.gender ?? '—' }}</span>
                            </div>
                        </div>

                        <div v-else class="text-center py-8 text-slate-400">
                            <UserCheck class="w-10 h-10 mx-auto mb-2" />
                            <p class="text-sm">No students enrolled yet.</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                        <h2 class="text-lg font-semibold text-slate-900 mb-4">Capacity</h2>

                        <div class="flex items-end justify-between mb-2">
                            <span class="text-sm text-slate-500">Enrolled</span>
                            <span class="text-lg font-bold text-slate-900 tabular-nums">
                                {{ classData.students }} / {{ classData.capacity }}
                            </span>
                        </div>

                        <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 transition-all duration-700"
                                :style="{ width: enrollmentPercent + '%' }"
                            ></div>
                        </div>

                        <p class="text-xs text-slate-400 mt-2 tabular-nums">
                            {{ Math.round(enrollmentPercent) }}% filled
                            &middot;
                            {{ Math.max(0, classData.capacity - classData.students) }} spots left
                        </p>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                        <h2 class="text-lg font-semibold text-slate-900 mb-4">Quick Actions</h2>
                        <div class="space-y-2">
                            <button
                                class="w-full text-left px-4 py-3 rounded-xl bg-slate-50 hover:bg-indigo-50 text-sm font-medium text-slate-700 hover:text-indigo-700 transition"
                            >
                                Edit Class
                            </button>
                            <button
                                class="w-full text-left px-4 py-3 rounded-xl bg-slate-50 hover:bg-indigo-50 text-sm font-medium text-slate-700 hover:text-indigo-700 transition"
                            >
                                Add Student
                            </button>
                            <button
                                class="w-full text-left px-4 py-3 rounded-xl bg-slate-50 hover:bg-red-50 text-sm font-medium text-slate-700 hover:text-red-700 transition"
                            >
                                End Class
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
