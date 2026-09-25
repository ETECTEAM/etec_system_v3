<script setup>
import { computed, reactive } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { CalendarDays } from "@lucide/vue";
import DashboardLayout from "../../../layouts/DashboardLayout.vue";
import { Breadcrumbs } from "../../../components/ui/breadcrumbs";
import { PageHero } from "../../../components/ui/page-hero";
import { Pagination } from "../../../components/ui/pagination";
import { SelectSearch } from "../../../components/ui/select-search";
import { useI18n } from "@/i18n";

const props = defineProps({
    filters: { type: Object, required: true },
    timeOptions: { type: Array, default: () => [] },
    courseOptions: { type: Array, default: () => [] },
    instructorOptions: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
    rows: { type: Object, default: () => ({ data: [] }) },
});

const { t } = useI18n();
const filters = reactive({
    from: props.filters.from,
    to: props.filters.to,
    range: "custom",
    time_id: props.filters.time_id ?? "",
    course_id: props.filters.course_id ?? "",
    instructor_id: props.filters.instructor_id ?? "",
    tracking_status: props.filters.tracking_status ?? "",
    per_page: props.filters.per_page ?? 10,
});
const breadcrumbItems = computed(() => [
    { label: t("Dashboard"), href: "/dashboard" },
    { label: t("navigation.attendanceReport"), current: true },
]);
const cards = computed(() => [
    [
        "total_classes",
        "attendanceReportPage.totalClasses",
        "bg-slate-100 text-slate-800 dark:bg-gray-800 dark:text-gray-100",
    ],
    [
        "total_class_tracked",
        "attendanceReportPage.totalClassesTracked",
        "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300",
    ],
    [
        "present",
        "attendanceReportPage.present",
        "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300",
    ],
    [
        "absent",
        "attendanceReportPage.absent",
        "bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300",
    ],
    [
        "late",
        "attendanceReportPage.late",
        "bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300",
    ],
    [
        "permission",
        "attendanceReportPage.permission",
        "bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300",
    ],
]);

function applyFilters(page = 1) {
    router.get(
        "/dashboard/attendance-report",
        { ...filters, page },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
}

const rowsMeta = computed(() => ({
    current_page: props.rows.current_page ?? 1,
    last_page: props.rows.last_page ?? 1,
    from: props.rows.from ?? 0,
    to: props.rows.to ?? 0,
    total: props.rows.total ?? 0,
}));

const courseSelectOptions = computed(() =>
    props.courseOptions.map((course) => ({
        value: String(course.id),
        label: course.title,
    })),
);
const instructorSelectOptions = computed(() =>
    props.instructorOptions.map((instructor) => ({
        value: String(instructor.id),
        label: instructor.name,
    })),
);
const filterSelectClass =
    "mt-1 flex h-10 w-full items-center justify-between rounded-lg border border-slate-300 bg-white px-3 text-left text-sm dark:border-gray-700 dark:bg-gray-950";

function setCourse(courseId) {
    filters.course_id = courseId;
    applyFilters();
}

function setInstructor(instructorId) {
    filters.instructor_id = instructorId;
    applyFilters();
}

const rangeOptions = computed(() => [
    ["today", t("attendanceReportPage.today")],
    ["yesterday", t("attendanceReportPage.yesterday")],
    ["current_week", t("attendanceReportPage.currentWeek")],
    ["last_week", t("attendanceReportPage.lastWeek")],
    ["this_month", t("attendanceReportPage.thisMonth")],
    ["last_month", t("attendanceReportPage.lastMonth")],
    ["this_year", t("attendanceReportPage.thisYear")],
    ["last_year", t("attendanceReportPage.lastYear")],
    ["custom", t("attendanceReportPage.custom")],
]);

function formatDate(date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
}

function setRange() {
    if (filters.range === "custom") return;

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    let from = new Date(today);
    let to = new Date(today);

    if (filters.range === "yesterday")
        (from.setDate(from.getDate() - 1), (to = new Date(from)));
    if (filters.range === "current_week" || filters.range === "last_week") {
        from.setDate(from.getDate() - ((from.getDay() + 6) % 7));
        if (filters.range === "last_week") from.setDate(from.getDate() - 7);
        to = new Date(from);
        to.setDate(to.getDate() + 6);
    }
    if (filters.range === "this_month" || filters.range === "last_month") {
        from.setDate(1);
        if (filters.range === "last_month") from.setMonth(from.getMonth() - 1);
        to = new Date(from.getFullYear(), from.getMonth() + 1, 0);
    }
    if (filters.range === "this_year" || filters.range === "last_year") {
        from = new Date(
            from.getFullYear() - (filters.range === "last_year" ? 1 : 0),
            0,
            1,
        );
        to = new Date(from.getFullYear(), 11, 31);
    }

    filters.from = formatDate(from);
    filters.to = formatDate(to);
    applyFilters();
}
</script>

<template>
    <Head :title="t('navigation.attendanceReport')" />
    <DashboardLayout>
        <section class="space-y-5 pb-10">
            <Breadcrumbs :items="breadcrumbItems" />
            <PageHero
                :title="t('navigation.attendanceReport')"
                :description="t('attendanceReportPage.description')"
                eyebrow="Attendance"
            />

            <div
                class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-2 xl:grid-cols-7 dark:border-gray-800 dark:bg-gray-900"
            >
                <label
                    class="text-sm font-semibold text-slate-700 dark:text-gray-200"
                    >{{ t("attendanceReportPage.range")
                    }}<select
                        v-model="filters.range"
                        class="mt-1 block h-10 w-full rounded-lg border border-slate-300 bg-white px-3 dark:border-gray-700 dark:bg-gray-950"
                        @change="setRange"
                    >
                        <option
                            v-for="[value, label] in rangeOptions"
                            :key="value"
                            :value="value"
                        >
                            {{ label }}
                        </option>
                    </select></label
                >
                <label
                    class="text-sm font-semibold text-slate-700 dark:text-gray-200"
                    >{{ t("attendanceReportPage.from")
                    }}<input
                        v-model="filters.from"
                        type="date"
                        @change="
                            filters.range = 'custom';
                            applyFilters();
                        "
                        class="mt-1 block h-10 w-full rounded-lg border border-slate-300 bg-white px-3 dark:border-gray-700 dark:bg-gray-950"
                /></label>
                <label
                    class="text-sm font-semibold text-slate-700 dark:text-gray-200"
                    >{{ t("attendanceReportPage.to")
                    }}<input
                        v-model="filters.to"
                        type="date"
                        @change="
                            filters.range = 'custom';
                            applyFilters();
                        "
                        class="mt-1 block h-10 w-full rounded-lg border border-slate-300 bg-white px-3 dark:border-gray-700 dark:bg-gray-950"
                /></label>
                <label
                    class="text-sm font-semibold text-slate-700 dark:text-gray-200"
                    >{{ t("attendanceReportPage.time")
                    }}<select
                        v-model="filters.time_id"
                        @change="applyFilters"
                        class="mt-1 block h-10 w-full rounded-lg border border-slate-300 bg-white px-3 dark:border-gray-700 dark:bg-gray-950"
                    >
                        <option value="">
                            {{ t("attendanceReportPage.allTimes") }}
                        </option>
                        <option
                            v-for="time in timeOptions"
                            :key="time.id"
                            :value="time.id"
                        >
                            {{ time.time_name }}
                        </option>
                    </select></label
                >
                <label
                    class="text-sm font-semibold text-slate-700 dark:text-gray-200"
                    >{{ t("attendanceReportPage.course")
                    }}<SelectSearch
                        :model-value="String(filters.course_id ?? '')"
                        :options="courseSelectOptions"
                        :placeholder="t('attendanceReportPage.allCourses')"
                        :empty-text="t('attendanceReportPage.empty')"
                        :button-class="filterSelectClass"
                        @update:model-value="setCourse"
                /></label>
                <label
                    class="text-sm font-semibold text-slate-700 dark:text-gray-200"
                    >{{ t("attendanceReportPage.instructor")
                    }}<SelectSearch
                        :model-value="String(filters.instructor_id ?? '')"
                        :options="instructorSelectOptions"
                        :placeholder="t('attendanceReportPage.allInstructors')"
                        :empty-text="t('attendanceReportPage.empty')"
                        :button-class="filterSelectClass"
                        @update:model-value="setInstructor"
                /></label>
                <label
                    class="text-sm font-semibold text-slate-700 dark:text-gray-200"
                    >{{ t("attendanceReportPage.status")
                    }}<select
                        v-model="filters.tracking_status"
                        class="mt-1 block h-10 w-full rounded-lg border border-slate-300 bg-white px-3 dark:border-gray-700 dark:bg-gray-950"
                        @change="applyFilters"
                    >
                        <option value="">
                            {{ t("attendanceReportPage.allStatuses") }}
                        </option>
                        <option value="tracked">
                            {{ t("attendanceReportPage.tracked") }}
                        </option>
                        <option value="not_tracked">
                            {{ t("attendanceReportPage.notTracked") }}
                        </option>
                    </select></label
                >
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                <article
                    v-for="[key, label, color] in cards"
                    :key="key"
                    :class="[
                        'rounded-xl border border-slate-200 p-4 shadow-sm dark:border-gray-800',
                        color,
                    ]"
                >
                    <p class="text-sm font-semibold">{{ t(label) }}</p>
                    <p class="mt-1 text-2xl font-black">
                        {{ summary[key] ?? 0 }}
                    </p>
                </article>
            </div>

            <article
                class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
            >
                <header
                    class="flex items-center gap-2 border-b border-slate-200 px-4 py-3 font-black dark:border-gray-800"
                >
                    <CalendarDays class="h-4 w-4 text-blue-600" />{{
                        t("attendanceReportPage.results")
                    }}
                </header>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-left dark:bg-gray-950">
                                <th>
                                    {{ t("attendanceReportPage.date") }}
                                </th>
                                <th>{{ t("attendanceReportPage.time") }}</th>
                                <th>
                                    {{ t("attendanceReportPage.course") }}
                                </th>
                                <th>
                                    {{ t("attendanceReportPage.instructor") }}
                                </th>
                                <th>
                                    {{
                                        t("attendanceReportPage.totalStudents")
                                    }}
                                </th>
                                <th>
                                    {{ t("attendanceReportPage.present") }}
                                </th>
                                <th>
                                    {{ t("attendanceReportPage.absent") }}
                                </th>
                                <th>
                                    {{ t("attendanceReportPage.late") }}
                                </th>
                                <th>
                                    {{ t("attendanceReportPage.permission") }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in rows.data" :key="row.session_id">
                                <td>{{ row.date }}</td>
                                <td>{{ row.time }}</td>
                                <td>{{ row.course }}</td>
                                <td>{{ row.instructor_name }}</td>
                                <td>{{ row.total_students }}</td>
                                <td>{{ row.present }}</td>
                                <td>{{ row.absent }}</td>
                                <td>{{ row.late }}</td>
                                <td>{{ row.permission }}</td>
                            </tr>
                            <tr v-if="!rows.data.length">
                                <td
                                    colspan="9"
                                    class="py-8 text-center text-slate-500"
                                >
                                    {{ t("attendanceReportPage.empty") }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <footer
                    class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 text-sm dark:border-gray-800 dark:bg-gray-800/40 sm:flex-row sm:items-center sm:justify-between"
                >
                    <label class="flex items-center gap-2 font-semibold">
                        {{ t("attendanceReportPage.rowsPerPage") }}
                        <select
                            v-model.number="filters.per_page"
                            class="h-9 rounded-lg border border-slate-300 bg-white px-2 dark:border-gray-700 dark:bg-gray-950"
                            @change="applyFilters"
                        >
                            <option
                                v-for="size in [10, 25, 50, 75, 100]"
                                :key="size"
                                :value="size"
                            >
                                {{ size }}
                            </option>
                        </select>
                    </label>
                    <span class="text-slate-500">
                        {{ t("attendanceReportPage.showing", rowsMeta) }}
                    </span>
                    <Pagination
                        :current-page="rowsMeta.current_page"
                        :last-page="rowsMeta.last_page"
                        @page-change="applyFilters"
                    />
                </footer>
            </article>
        </section>
    </DashboardLayout>
</template>

<style scoped>
th,
td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid rgb(226 232 240);
}
.dark th,
.dark td {
    border-color: rgb(31 41 55);
}
</style>
