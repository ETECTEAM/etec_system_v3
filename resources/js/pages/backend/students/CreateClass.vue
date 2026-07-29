<script setup>
import { ref, computed } from "vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useToast } from "vue-toastification";
import {
    ArrowLeft,
    Save,
    RefreshCw,
    Info,
    Calendar,
    MapPin,
    DollarSign,
    Users,
    FileText,
    Image,
    Settings2,
    Wand2,
    Hash,
} from "@lucide/vue";
import DashboardLayout from "@/layouts/DashboardLayout.vue";
import { Breadcrumbs } from "@/components/ui/breadcrumbs";
import { PageHero } from "@/components/ui/page-hero";
import { useI18n } from "@/i18n";
import ClassForm from "./components/ClassForm.vue";

const toast = useToast();
const { t } = useI18n();

const props = defineProps({
    options: {
        type: Object,
        default: () => ({}),
    },
    courses: { type: Array, default: () => [] },
    lessons: { type: Array, default: () => [] },
    teachers: { type: Array, default: () => [] },
    buildings: { type: Array, default: () => [] },
    floors: { type: Array, default: () => [] },
    rooms: { type: Array, default: () => [] },
    terms: { type: Array, default: () => [] },
    times: { type: Array, default: () => [] },
    currencies: {
        type: Array,
        default: () => [
            { value: "USD", label: "USD — US Dollar" },
            { value: "KHR", label: "KHR — Cambodian Riel" },
            { value: "THB", label: "THB — Thai Baht" },
            { value: "EUR", label: "EUR — Euro" },
        ],
    },
});

const normalizedOptions = computed(() => ({
    courses: props.options?.courses ?? props.courses ?? [],
    lessons: props.options?.lessons ?? props.lessons ?? [],
    teachers: props.options?.teachers ?? props.teachers ?? [],
    buildings: props.options?.buildings ?? props.buildings ?? [],
    floors: props.options?.floors ?? props.floors ?? [],
    rooms: props.options?.rooms ?? props.rooms ?? [],
    terms: props.options?.terms ?? props.terms ?? [],
    times: props.options?.times ?? props.times ?? [],
    classTypes: props.options?.classTypes ?? [
        { value: "physical", label: "Physical Class" },
        { value: "online", label: "Online Class" },
    ],
    studyDays: props.options?.studyDays ?? [
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday",
        "Saturday",
        "Sunday",
    ],
}));

// ─── Form ───────────────────────────────────────────────────────────────
const form = useForm({
    // General
    title: "",
    course_id: "",
    lesson_id: "",
    teacher_id: "",
    status: "active",
    class_type: "physical",

    // Schedule
    term_id: "",
    time_id: "",
    start_date: "",
    end_date: "",
    enrollment_start: "",
    enrollment_end: "",

    // Location
    building_id: "",
    floor_id: "",
    room_id: "",

    // Pricing
    price: "",
    discount_price: "",
    currency: "USD",

    // Capacity
    max_students: "",
    min_students: "",
    waiting_list: "no",

    // Course Details
    description: "",
    learning_objectives: "",
    prerequisites: "",

    // Media
    thumbnail: null,
    banner: null,

    // Settings
    featured: false,
    allow_enrollment: true,
    published: true,
    visibility: "public",
});

// ─── Computed ───────────────────────────────────────────────────────────
const descriptionCount = computed(() => form.description?.length ?? 0);

const dateError = computed(() => {
    if (form.start_date && form.end_date && form.end_date < form.start_date) {
        return t("End date cannot be before start date.");
    }
    if (
        form.enrollment_start &&
        form.enrollment_end &&
        form.enrollment_end < form.enrollment_start
    ) {
        return t("Enrollment end date cannot be before start date.");
    }
    return "";
});

// ─── Image preview refs ─────────────────────────────────────────────────
const thumbnailPreview = ref(null);
const bannerPreview = ref(null);

function previewFile(event, target) {
    const file = event.target.files?.[0];
    if (!file) return;
    if (target === "thumbnail") {
        form.thumbnail = file;
        thumbnailPreview.value = URL.createObjectURL(file);
    } else {
        form.banner = file;
        bannerPreview.value = URL.createObjectURL(file);
    }
}

// ─── Auto-generate course code ──────────────────────────────────────────
function generateCode() {
    if (!form.title) {
        toast.warning(t("Please enter a class title first."));
        return;
    }
    const cleaned = form.title
        .toUpperCase()
        .replace(/[^A-Z0-9\s]/g, "")
        .split(/\s+/)
        .filter(Boolean);
    const prefix = cleaned.map((w) => w[0]).join("").slice(0, 6);
    const suffix = Date.now().toString().slice(-4);
    form.course_code = `${prefix}-${suffix}`;
}

// ─── Breadcrumbs ────────────────────────────────────────────────────────
const breadcrumbItems = [
    { label: "Dashboard", href: "/dashboard" },
    { label: "Students", href: "/dashboard/students" },
    { label: "Create Class", current: true },
];

// ─── Navigation ─────────────────────────────────────────────────────────
function back() {
    router.visit("/dashboard/students");
}

function resetForm() {
    form.reset();
    thumbnailPreview.value = null;
    bannerPreview.value = null;
    toast.info(t("Form has been reset."));
}

// ─── Submit ─────────────────────────────────────────────────────────────
function submit() {
    form.post(route("students.store"), {
        onSuccess: () => {
            form.reset();
            thumbnailPreview.value = null;
            bannerPreview.value = null;
            toast.success(t("Class created successfully!"));
        },
        onError: () => {
            toast.error(t("Please fix the validation errors."));
        },
    });
}

// ─── Reusable input class helper ────────────────────────────────────────
const baseInput = [
    "w-full rounded-xl border bg-white px-4 py-3 text-sm outline-none transition",
    "focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20",
];

function inputClass(field) {
    return [
        ...baseInput,
        form.errors[field]
            ? "border-red-400 focus:border-red-500 focus:ring-red-500/20"
            : "border-slate-300",
    ];
}

// ─── Section card helper ────────────────────────────────────────────────
const sectionCard =
    "rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 lg:p-7";
const sectionTitleClass =
    "text-base font-semibold text-slate-900";
const sectionDescClass =
    "text-sm text-slate-500";
const formCardClass =
    "w-full rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-gray-800 dark:bg-gray-900";
const fieldLabelClass =
    "mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200";
const visibleInputClass =
    "w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20";
</script>

<template>
    <Head :title="$t('Create Class')" />
    <DashboardLayout>
    <div class="w-full">
        <div class="space-y-6">
            <Breadcrumbs :items="breadcrumbItems" />

            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <PageHero
                    eyebrow="Class Management"
                    :title="$t('Create New Class')"
                    :description="$t('Create and manage class information.')"
                />

                <button
                    type="button"
                    @click="back"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500"
                >
                    <ArrowLeft class="h-4 w-4" />
                    {{ $t('Back') }}
                </button>
            </div>

            <ClassForm :options="normalizedOptions" mode="create" />

            <!-- ── Form ────────────────────────────────────────────────── -->
            <!-- Kept for future use. Enable this template when the full create-class form is needed again. -->
            <template v-if="false">
            <form @submit.prevent="submit" class="space-y-8">
                <!-- ══════════════════════════════════════════════════════
                     SECTION 1 — General Information
                     ══════════════════════════════════════════════════════ -->
                <div :class="sectionCard">
                    <div class="mb-5 flex items-start gap-3">
                        <Info class="mt-0.5 h-5 w-5 text-indigo-600" />
                        <div>
                            <h2 :class="sectionTitleClass">{{ $t('General Information') }}</h2>
                            <p :class="sectionDescClass">
                                Basic details about the class.
                            </p>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-4 lg:gap-6"
                    >
                        <!-- Class Title -->
                        <div class="sm:col-span-2 lg:col-span-2">
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                {{ $t('Class Title') }} <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.title"
                                    type="text"
                                    :placeholder="$t('e.g. Web Development — Batch 2026')"
                                    :class="inputClass('title')"
                                />
                                <button
                                    type="button"
                                    @click="generateCode"
                                    :title="$t('Auto-generate course code')"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-indigo-600"
                                >
                                    <Wand2 class="h-4 w-4" />
                                </button>
                            </div>
                            <p
                                v-if="form.errors.title"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.title }}
                            </p>
                        </div>

                        <!-- Course Code -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Course Code
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.course_code"
                                    type="text"
                                    :placeholder="$t('Auto-generated')"
                                    :class="inputClass('course_code')"
                                />
                                <Hash
                                    class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"
                                />
                            </div>
                            <p
                                v-if="form.errors.course_code"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.course_code }}
                            </p>
                        </div>

                        <!-- Course / Subject -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Course / Subject
                            </label>
                            <select
                                v-model="form.course_id"
                                :class="inputClass('course_id')"
                            >
                                <option value="">{{ $t('Select course...') }}</option>
                                <option
                                    v-for="c in courses"
                                    :key="c.id"
                                    :value="c.id"
                                >
                                    {{ c.title }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.course_id"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.course_id }}
                            </p>
                        </div>

                        <!-- Lesson / Subject -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Lesson / Subject
                            </label>
                            <select
                                v-model="form.lesson_id"
                                :class="inputClass('lesson_id')"
                            >
                                <option value="">{{ $t('Select lesson...') }}</option>
                                <option
                                    v-for="l in lessons"
                                    :key="l.id"
                                    :value="l.id"
                                >
                                    {{ l.title }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.lesson_id"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.lesson_id }}
                            </p>
                        </div>

                        <!-- Teacher -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Teacher
                            </label>
                            <select
                                v-model="form.teacher_id"
                                :class="inputClass('teacher_id')"
                            >
                                <option value="">{{ $t('Select teacher...') }}</option>
                                <option
                                    v-for="t in teachers"
                                    :key="t.id"
                                    :value="t.id"
                                >
                                    {{ t.name ?? t.full_name ?? t.email }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.teacher_id"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.teacher_id }}
                            </p>
                        </div>

                        <!-- Status -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                {{ $t('Status') }}
                            </label>
                            <select
                                v-model="form.status"
                                :class="inputClass('status')"
                            >
                                <option value="active">{{ $t('Active') }}</option>
                                <option value="inactive">{{ $t('Inactive') }}</option>
                            </select>
                            <p
                                v-if="form.errors.status"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.status }}
                            </p>
                        </div>

                        <!-- Class Type -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                {{ $t('Class Type') }}
                            </label>
                            <select
                                v-model="form.class_type"
                                :class="inputClass('class_type')"
                            >
                                <option value="physical">{{ $t('Physical Class') }}</option>
                                <option value="online">{{ $t('Online Class') }}</option>
                            </select>
                            <p
                                v-if="form.errors.class_type"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.class_type }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ══════════════════════════════════════════════════════
                     SECTION 2 — Schedule
                     ══════════════════════════════════════════════════════ -->
                <div :class="sectionCard">
                    <div class="mb-5 flex items-start gap-3">
                        <Calendar class="mt-0.5 h-5 w-5 text-indigo-600" />
                        <div>
                            <h2 :class="sectionTitleClass">{{ $t('Schedule') }}</h2>
                            <p :class="sectionDescClass">
                                Set the class schedule and enrollment period.
                            </p>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-4 lg:gap-6"
                    >
                        <!-- Study Days -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                {{ $t('Study Days') }}
                            </label>
                            <select
                                v-model="form.term_id"
                                :class="inputClass('term_id')"
                            >
                                <option value="">{{ $t('Select days...') }}</option>
                                <option
                                    v-for="t in terms"
                                    :key="t.id"
                                    :value="t.id"
                                >
                                    {{ t.term_name }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.term_id"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.term_id }}
                            </p>
                        </div>

                        <!-- Study Time -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                {{ $t('Study Time') }} <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.time_id"
                                :class="inputClass('time_id')"
                            >
                                <option value="">{{ $t('Select time...') }}</option>
                                <option
                                    v-for="t in times"
                                    :key="t.id"
                                    :value="t.id"
                                >
                                    {{ t.time_name }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.time_id"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.time_id }}
                            </p>
                        </div>

                        <!-- Start Date -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                {{ $t('Start Date') }}
                            </label>
                            <input
                                v-model="form.start_date"
                                type="date"
                                :class="inputClass('start_date')"
                            />
                            <p
                                v-if="form.errors.start_date"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.start_date }}
                            </p>
                        </div>

                        <!-- End Date -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                End Date
                            </label>
                            <input
                                v-model="form.end_date"
                                type="date"
                                :class="inputClass('end_date')"
                            />
                            <p
                                v-if="form.errors.end_date"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.end_date }}
                            </p>
                        </div>

                        <!-- Enrollment Start -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Enrollment Start Date
                            </label>
                            <input
                                v-model="form.enrollment_start"
                                type="date"
                                :class="inputClass('enrollment_start')"
                            />
                            <p
                                v-if="form.errors.enrollment_start"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.enrollment_start }}
                            </p>
                        </div>

                        <!-- Enrollment End -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Enrollment End Date
                            </label>
                            <input
                                v-model="form.enrollment_end"
                                type="date"
                                :class="inputClass('enrollment_end')"
                            />
                            <p
                                v-if="form.errors.enrollment_end"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.enrollment_end }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ══════════════════════════════════════════════════════
                     SECTION 3 — Location
                     ══════════════════════════════════════════════════════ -->
                <div :class="sectionCard">
                    <div class="mb-5 flex items-start gap-3">
                        <MapPin class="mt-0.5 h-5 w-5 text-indigo-600" />
                        <div>
                            <h2 :class="sectionTitleClass">{{ $t('Location') }}</h2>
                            <p :class="sectionDescClass">
                                Select the physical location for this class.
                            </p>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-4 lg:gap-6"
                    >
                        <!-- Building -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                {{ $t('Building') }}
                            </label>
                            <select
                                v-model="form.building_id"
                                :class="inputClass('building_id')"
                            >
                                <option value="">{{ $t('Select building...') }}</option>
                                <option
                                    v-for="b in buildings"
                                    :key="b.id"
                                    :value="b.id"
                                >
                                    {{ b.name }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.building_id"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.building_id }}
                            </p>
                        </div>

                        <!-- Floor -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                {{ $t('Floor') }}
                            </label>
                            <select
                                v-model="form.floor_id"
                                :class="inputClass('floor_id')"
                            >
                                <option value="">{{ $t('Select floor...') }}</option>
                                <option
                                    v-for="f in floors"
                                    :key="f.id"
                                    :value="f.id"
                                >
                                    {{ f.name }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.floor_id"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.floor_id }}
                            </p>
                        </div>

                        <!-- Room -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                {{ $t('Room') }}
                            </label>
                            <select
                                v-model="form.room_id"
                                :class="inputClass('room_id')"
                            >
                                <option value="">{{ $t('Select room...') }}</option>
                                <option
                                    v-for="r in rooms"
                                    :key="r.id"
                                    :value="r.id"
                                >
                                    {{ r.room_number }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.room_id"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.room_id }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ══════════════════════════════════════════════════════
                     SECTION 4 — Pricing
                     ══════════════════════════════════════════════════════ -->
                <div :class="sectionCard">
                    <div class="mb-5 flex items-start gap-3">
                        <DollarSign class="mt-0.5 h-5 w-5 text-indigo-600" />
                        <div>
                            <h2 :class="sectionTitleClass">{{ $t('Pricing') }}</h2>
                            <p :class="sectionDescClass">
                                Set the class fee structure.
                            </p>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-4 lg:gap-6"
                    >
                        <!-- Price -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                {{ $t('Price') }}
                            </label>
                            <input
                                v-model="form.price"
                                type="number"
                                min="0"
                                step="0.01"
                                placeholder="0.00"
                                :class="inputClass('price')"
                            />
                            <p
                                v-if="form.errors.price"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.price }}
                            </p>
                        </div>

                        <!-- Discount Price -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Discount Price
                            </label>
                            <input
                                v-model="form.discount_price"
                                type="number"
                                min="0"
                                step="0.01"
                                placeholder="0.00"
                                :class="inputClass('discount_price')"
                            />
                            <p
                                v-if="form.errors.discount_price"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.discount_price }}
                            </p>
                        </div>

                        <!-- Currency -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Currency
                            </label>
                            <select
                                v-model="form.currency"
                                :class="inputClass('currency')"
                            >
                                <option
                                    v-for="c in currencies"
                                    :key="c.value"
                                    :value="c.value"
                                >
                                    {{ c.label }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.currency"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.currency }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ══════════════════════════════════════════════════════
                     SECTION 5 — Capacity
                     ══════════════════════════════════════════════════════ -->
                <div :class="sectionCard">
                    <div class="mb-5 flex items-start gap-3">
                        <Users class="mt-0.5 h-5 w-5 text-indigo-600" />
                        <div>
                            <h2 :class="sectionTitleClass">{{ $t('Capacity') }}</h2>
                            <p :class="sectionDescClass">
                                Manage student limits and availability.
                            </p>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-4 lg:gap-6"
                    >
                        <!-- Max Students -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Maximum Students
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.max_students"
                                type="number"
                                min="1"
                                :placeholder="$t('e.g. 30')"
                                :class="inputClass('max_students')"
                            />
                            <p
                                v-if="form.errors.max_students"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.max_students }}
                            </p>
                        </div>

                        <!-- Min Students -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Minimum Students
                            </label>
                            <input
                                v-model="form.min_students"
                                type="number"
                                min="0"
                                :placeholder="$t('e.g. 5')"
                                :class="inputClass('min_students')"
                            />
                            <p
                                v-if="form.errors.min_students"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.min_students }}
                            </p>
                        </div>

                        <!-- Waiting List -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Waiting List
                            </label>
                            <select
                                v-model="form.waiting_list"
                                :class="inputClass('waiting_list')"
                            >
                                <option value="no">{{ $t('No') }}</option>
                                <option value="yes">{{ $t('Yes') }}</option>
                            </select>
                            <p
                                v-if="form.errors.waiting_list"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.waiting_list }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ══════════════════════════════════════════════════════
                     SECTION 6 — Course Details
                     ══════════════════════════════════════════════════════ -->
                <div :class="sectionCard">
                    <div class="mb-5 flex items-start gap-3">
                        <FileText class="mt-0.5 h-5 w-5 text-indigo-600" />
                        <div>
                            <h2 :class="sectionTitleClass">{{ $t('Course Details') }}</h2>
                            <p :class="sectionDescClass">
                                Provide a detailed overview of the class.
                            </p>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <!-- Description -->
                        <div>
                            <div
                                class="mb-1.5 flex items-center justify-between"
                            >
                                <label
                                    class="block text-sm font-semibold text-slate-700"
                                >
                                    {{ $t('Description') }}
                                </label>
                                <span
                                    class="text-xs text-slate-400"
                                    :class="{
                                        'text-amber-600':
                                            descriptionCount > 500,
                                    }"
                                >
                                    {{ descriptionCount }} / 1000
                                </span>
                            </div>
                            <textarea
                                v-model="form.description"
                                rows="4"
                                :placeholder="$t('Describe the class content, goals, and what students will learn...')"
                                :class="inputClass('description')"
                                maxlength="1000"
                            ></textarea>
                            <p
                                v-if="form.errors.description"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.description }}
                            </p>
                        </div>

                        <!-- Learning Objectives -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Learning Objectives
                            </label>
                            <textarea
                                v-model="form.learning_objectives"
                                rows="3"
                                :placeholder="$t('List the key learning outcomes. One per line is recommended...')"
                                :class="inputClass('learning_objectives')"
                            ></textarea>
                            <p
                                v-if="form.errors.learning_objectives"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.learning_objectives }}
                            </p>
                        </div>

                        <!-- Prerequisites -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Prerequisites
                            </label>
                            <textarea
                                v-model="form.prerequisites"
                                rows="3"
                                :placeholder="$t('Any required knowledge, tools, or materials students need before joining...')"
                                :class="inputClass('prerequisites')"
                            ></textarea>
                            <p
                                v-if="form.errors.prerequisites"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.prerequisites }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ══════════════════════════════════════════════════════
                     SECTION 7 — Media
                     ══════════════════════════════════════════════════════ -->
                <div :class="sectionCard">
                    <div class="mb-5 flex items-start gap-3">
                        <Image class="mt-0.5 h-5 w-5 text-indigo-600" />
                        <div>
                            <h2 :class="sectionTitleClass">{{ $t('Media') }}</h2>
                            <p :class="sectionDescClass">
                                Upload class images and branding assets.
                            </p>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-6 sm:grid-cols-2"
                    >
                        <!-- Thumbnail Upload -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                {{ $t('Thumbnail Image') }}
                            </label>
                            <div
                                @click="$refs.thumbnailInput.click()"
                                class="group relative flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-6 transition hover:border-indigo-400 hover:bg-indigo-50/30"
                            >
                                <img
                                    v-if="thumbnailPreview"
                                    :src="thumbnailPreview"
                                    alt="Thumbnail preview"
                                    class="mb-3 h-32 w-full rounded-lg object-cover"
                                />
                                <Image
                                    v-else
                                    class="mb-2 h-8 w-8 text-slate-400"
                                />
                                <p
                                    v-if="!thumbnailPreview"
                                    class="text-sm text-slate-500"
                                >
                                    Click to upload thumbnail
                                </p>
                                <p
                                    v-if="!thumbnailPreview"
                                    class="mt-0.5 text-xs text-slate-400"
                                >
                                    PNG, JPG or WebP — recommended 800x450
                                </p>
                                <p
                                    v-if="thumbnailPreview"
                                    class="mt-2 text-xs font-medium text-indigo-600 group-hover:underline"
                                >
                                    Change image
                                </p>
                            </div>
                            <input
                                ref="thumbnailInput"
                                type="file"
                                accept="image/png,image/jpeg,image/webp"
                                class="hidden"
                                @change="previewFile($event, 'thumbnail')"
                            />
                            <p
                                v-if="form.errors.thumbnail"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.thumbnail }}
                            </p>
                        </div>

                        <!-- Banner Upload -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Banner Image
                            </label>
                            <div
                                @click="$refs.bannerInput.click()"
                                class="group relative flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-6 transition hover:border-indigo-400 hover:bg-indigo-50/30"
                            >
                                <img
                                    v-if="bannerPreview"
                                    :src="bannerPreview"
                                    alt="Banner preview"
                                    class="mb-3 h-32 w-full rounded-lg object-cover"
                                />
                                <Image
                                    v-else
                                    class="mb-2 h-8 w-8 text-slate-400"
                                />
                                <p
                                    v-if="!bannerPreview"
                                    class="text-sm text-slate-500"
                                >
                                    Click to upload banner
                                </p>
                                <p
                                    v-if="!bannerPreview"
                                    class="mt-0.5 text-xs text-slate-400"
                                >
                                    PNG, JPG or WebP — recommended 1920x480
                                </p>
                                <p
                                    v-if="bannerPreview"
                                    class="mt-2 text-xs font-medium text-indigo-600 group-hover:underline"
                                >
                                    Change image
                                </p>
                            </div>
                            <input
                                ref="bannerInput"
                                type="file"
                                accept="image/png,image/jpeg,image/webp"
                                class="hidden"
                                @change="previewFile($event, 'banner')"
                            />
                            <p
                                v-if="form.errors.banner"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.banner }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ══════════════════════════════════════════════════════
                     SECTION 8 — Settings
                     ══════════════════════════════════════════════════════ -->
                <div :class="sectionCard">
                    <div class="mb-5 flex items-start gap-3">
                        <Settings2 class="mt-0.5 h-5 w-5 text-indigo-600" />
                        <div>
                            <h2 :class="sectionTitleClass">{{ $t('Settings') }}</h2>
                            <p :class="sectionDescClass">
                                Configure visibility and publishing options.
                            </p>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <!-- Featured Class toggle -->
                        <div>
                            <label
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Featured Class
                            </label>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="form.featured"
                                :class="[
                                    'relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200',
                                    form.featured
                                        ? 'bg-indigo-600'
                                        : 'bg-slate-300',
                                ]"
                                @click="form.featured = !form.featured"
                            >
                                <span
                                    :class="[
                                        'inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200',
                                        form.featured
                                            ? 'translate-x-5'
                                            : 'translate-x-0',
                                    ]"
                                />
                            </button>
                            <p class="mt-1 text-xs text-slate-500">
                                {{
                                    form.featured
                                        ? "Showcased on homepage"
                                        : "Regular listing"
                                }}
                            </p>
                        </div>

                        <!-- Allow Enrollment toggle -->
                        <div>
                            <label
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Allow Enrollment
                            </label>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="form.allow_enrollment"
                                :class="[
                                    'relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200',
                                    form.allow_enrollment
                                        ? 'bg-indigo-600'
                                        : 'bg-slate-300',
                                ]"
                                @click="
                                    form.allow_enrollment =
                                        !form.allow_enrollment
                                "
                            >
                                <span
                                    :class="[
                                        'inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200',
                                        form.allow_enrollment
                                            ? 'translate-x-5'
                                            : 'translate-x-0',
                                    ]"
                                />
                            </button>
                            <p class="mt-1 text-xs text-slate-500">
                                {{
                                    form.allow_enrollment
                                        ? "Students can enroll"
                                        : "Enrollment closed"
                                }}
                            </p>
                        </div>

                        <!-- Publish Immediately toggle -->
                        <div>
                            <label
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Publish Immediately
                            </label>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="form.published"
                                :class="[
                                    'relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200',
                                    form.published
                                        ? 'bg-indigo-600'
                                        : 'bg-slate-300',
                                ]"
                                @click="form.published = !form.published"
                            >
                                <span
                                    :class="[
                                        'inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200',
                                        form.published
                                            ? 'translate-x-5'
                                            : 'translate-x-0',
                                    ]"
                                />
                            </button>
                            <p class="mt-1 text-xs text-slate-500">
                                {{
                                    form.published
                                        ? "Visible to students"
                                        : "Saved as draft"
                                }}
                            </p>
                        </div>

                        <!-- Visibility -->
                        <div>
                            <label
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Visibility
                            </label>
                            <select
                                v-model="form.visibility"
                                :class="inputClass('visibility')"
                            >
                                <option value="public">{{ $t('Public') }}</option>
                                <option value="private">{{ $t('Private') }}</option>
                            </select>
                            <p
                                v-if="form.errors.visibility"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.visibility }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ══════════════════════════════════════════════════════
                     FOOTER — Sticky action bar
                     ══════════════════════════════════════════════════════ -->
                <div
                    class="sticky bottom-0 -mx-4 -mb-4 mt-10 border-t border-slate-200 bg-white/90 px-4 py-4 backdrop-blur sm:-mx-6 sm:-mb-6 sm:px-6 lg:-mx-8 lg:-mb-8 lg:px-8"
                >
                    <div
                        class="flex flex-col-reverse items-center gap-3 sm:flex-row sm:justify-end sm:gap-4"
                    >
                        <button
                            type="button"
                            @click="back"
                            class="w-full rounded-xl border border-slate-300 px-6 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-100 sm:w-auto"
                        >
                            {{ $t('Cancel') }}
                        </button>

                        <button
                            type="button"
                            @click="resetForm"
                            class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 px-6 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-100 sm:w-auto"
                        >
                            <RefreshCw class="h-4 w-4" />
                            {{ $t('Reset') }}
                        </button>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-8 py-3 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-indigo-400 sm:w-auto"
                        >
                            <Save v-if="!form.processing" class="h-4 w-4" />
                            <svg
                                v-else
                                class="h-4 w-4 animate-spin"
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                />
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                />
                            </svg>
                            {{ form.processing ? $t("Saving...") : $t("Save Class") }}
                        </button>
                    </div>
                </div>
            </form>
            </template>
        </div>
    </div>
    </DashboardLayout>
</template>
