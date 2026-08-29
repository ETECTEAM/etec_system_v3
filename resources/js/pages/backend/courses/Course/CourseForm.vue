<template>
    <DashboardLayout>
        <div class="w-full">

            <!-- Breadcrumb -->
            <nav class="flex items-center gap-1.5 text-sm text-slate-400 dark:text-gray-500 mb-4">
                <span>{{ $t('Dashboard') }}</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span>{{ $t('Course') }}</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <Link href="/dashboard/course/courses" class="hover:text-slate-600 dark:hover:text-gray-300 transition">{{ $t('Courses') }}</Link>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-slate-600 dark:text-gray-300 font-medium">{{ course ? $t('Edit') : $t('Create') }}</span>
            </nav>

            <!-- Header -->
            <div class="flex items-center gap-4 mb-6">
                <Link href="/dashboard/course/courses"
                    class="text-slate-500 hover:text-slate-700 transition p-2 rounded-xl hover:bg-slate-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800" :title="$t('Back')">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center shadow-sm shadow-blue-200 dark:shadow-none shrink-0">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s4.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight dark:text-gray-100">
                        {{ course ? $t('Edit Course') : $t('Create Course') }}
                    </h1>
                    <p class="text-sm text-slate-500 mt-0.5 dark:text-gray-400">
                        {{ course ? $t("Update this course's details") : $t('Add a new course to your catalog') }}
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-xl border border-slate-200 p-4 sm:p-6 shadow-sm dark:bg-gray-900 dark:border-gray-800"
                enctype="multipart/form-data">
                <div class="space-y-4">
                    <!-- Image Upload -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-gray-300">{{ $t('Thumbnail Image') }}</label>
                        <div class="flex items-center gap-4">
                            <div v-if="imagePreview || existingThumbnail"
                                class="relative w-32 h-32 rounded-lg border border-slate-200 overflow-hidden dark:border-gray-700">
                                <img :src="imagePreview || `/storage/${existingThumbnail}`" alt="Course thumbnail"
                                    class="w-full h-full object-cover" />
                                <button v-if="imagePreview || existingThumbnail" type="button" @click="removeImage"
                                    class="absolute top-1 right-1 bg-rose-500 text-white rounded-full p-1 hover:bg-rose-600 transition">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <div class="flex-1">
                                <label class="cursor-pointer">
                                    <div
                                        class="border-2 border-dashed border-slate-300 rounded-lg p-4 text-center hover:border-blue-500 transition dark:border-gray-600 dark:hover:border-blue-500">
                                        <svg class="w-8 h-8 mx-auto text-slate-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <p class="text-sm text-slate-600 dark:text-gray-300">{{ $t('Click to upload image') }}</p>
                                        <p class="text-xs text-slate-400 dark:text-gray-500">{{ $t('PNG, JPG, JPEG up to 2MB') }}</p>
                                    </div>
                                    <input type="file" accept="image/*" @change="handleImageUpload" class="hidden" />
                                </label>
                            </div>
                        </div>
                        <p v-if="errors.thumbnail" class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ errors.thumbnail }}</p>
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-gray-300">{{ $t('Title') }} <span class="text-rose-500">*</span></label>
                        <input v-model="form.title" type="text"
                            class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:ring-blue-500/20"
                            :placeholder="$t('Enter course title')" required />
                        <p v-if="errors.title" class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ errors.title }}</p>
                    </div>

                    <!-- Category, Sub Category, Track -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-gray-300">{{ $t('Category') }}</label>
                            <select v-model="form.category_id"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:ring-blue-500/20">
                                <option value="">{{ $t('Select Category') }}</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-gray-300">{{ $t('Sub Category') }}</label>
                            <select v-model="form.sub_category_id" :disabled="!form.category_id"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white disabled:bg-slate-50 disabled:text-slate-400 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:ring-blue-500/20 dark:disabled:bg-gray-800/50 dark:disabled:text-gray-600">
                                <option value="">
                                    {{ form.category_id ? $t('Select Sub Category') : $t('Select a category first') }}
                                </option>
                                <option v-for="sub in filteredSubCategories" :key="sub.id" :value="sub.id">
                                    {{ sub.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-gray-300">{{ $t('Track') }} <span class="text-rose-500">*</span></label>
                            <select v-model="form.course_track_id" :disabled="!form.sub_category_id"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white disabled:bg-slate-50 disabled:text-slate-400 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:ring-blue-500/20 dark:disabled:bg-gray-800/50 dark:disabled:text-gray-600">
                                <option value="">
                                    {{ form.sub_category_id ? $t('Select Track') : $t('Select a sub category first') }}
                                </option>
                                <option v-for="track in filteredTracks" :key="track.id" :value="track.id">
                                    {{ track.name }}
                                </option>
                            </select>
                            <p v-if="errors.course_track_id" class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ errors.course_track_id }}</p>
                        </div>
                    </div>

                    <!-- Price, Status -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-gray-300">{{ $t('Price') }}</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-gray-500">$</span>
                                <input v-model="form.price" type="number" min="0" step="0.01"
                                    class="w-full rounded-lg border border-slate-200 pl-8 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:ring-blue-500/20"
                                    :placeholder="$t('0.00')" />
                            </div>
                            <p v-if="errors.price" class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ errors.price }}</p>
                        </div>

                        <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200 dark:bg-gray-800/40 dark:border-gray-700">
                            <input v-model="form.status" type="checkbox"
                                class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 focus:ring-2 transition dark:border-gray-600 dark:bg-gray-800"
                                true-value="active" false-value="inactive" />
                            <label class="text-sm font-medium text-slate-700 cursor-pointer dark:text-gray-300">{{ $t('Active') }}</label>
                            <span class="text-xs text-slate-500 ml-auto dark:text-gray-500">
                                {{ form.status === 'active' ? $t('Course will be visible to students') : $t('Course will be hidden') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Divider -->
                <div class="my-6 border-t border-slate-200 dark:border-gray-800" />

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link href="/dashboard/course/courses"
                        class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                        {{ $t('Cancel') }}
                    </Link>
                    <button type="submit" :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500">
                        {{ form.processing ? $t('Saving...') : (course ? $t('Update Course') : $t('Create Course')) }}
                    </button>
                </div>
            </form>

            <!-- Edit-only: needs a course id. Each toggle saves immediately, independent of the form above. -->
            <div v-if="course" class="mt-6 bg-white rounded-xl border border-slate-200 p-4 sm:p-6 shadow-sm dark:bg-gray-900 dark:border-gray-800">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Class Schedules') }}</h2>
                    <p class="text-sm text-slate-500 mt-0.5 dark:text-gray-400">
                        {{ $t('Choose which existing schedule times this course is open for. Times come from Schedule Management - click one to open or close it for this course.') }}
                    </p>
                </div>

                <div class="space-y-3">
                    <div v-for="classType in schedules" :key="classType.class_type_id"
                        class="rounded-xl border overflow-hidden" :class="classTypeAccent(classType).border">
                        <div class="w-full flex items-center justify-between gap-3 px-4 py-3 transition" :class="classTypeAccent(classType).header">
                            <button type="button" class="flex items-center gap-2.5 min-w-0" @click="toggleCollapsed(classType.class_type_id)">
                                <span class="h-2.5 w-2.5 shrink-0 rounded-full" :class="classTypeAccent(classType).dot" />
                                <span class="font-semibold" :class="classTypeAccent(classType).text">{{ classType.class_type_name }}</span>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                    :class="classType.is_enabled ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-slate-200 text-slate-600 dark:bg-gray-700 dark:text-gray-400'">
                                    {{ classType.is_enabled ? $t('ON') : $t('OFF') }}
                                </span>
                            </button>
                            <div class="flex items-center gap-3 shrink-0">
                                <button type="button"
                                    :disabled="pendingKey === `classtype:${classType.class_type_id}`"
                                    class="text-xs font-semibold text-slate-500 hover:text-slate-800 hover:underline disabled:opacity-50 dark:text-gray-400 dark:hover:text-gray-100"
                                    @click="setClassTypeAvailability(classType, true)">
                                    {{ $t('Turn on all') }}
                                </button>
                                <span class="text-slate-300 dark:text-gray-600">|</span>
                                <button type="button"
                                    :disabled="pendingKey === `classtype:${classType.class_type_id}`"
                                    class="text-xs font-semibold text-slate-500 hover:text-slate-800 hover:underline disabled:opacity-50 dark:text-gray-400 dark:hover:text-gray-100"
                                    @click="setClassTypeAvailability(classType, false)">
                                    {{ $t('Turn off all') }}
                                </button>
                                <button type="button" @click="toggleCollapsed(classType.class_type_id)">
                                    <ChevronUp v-if="!collapsed.has(classType.class_type_id)" class="h-4 w-4 text-slate-400 dark:text-gray-500" />
                                    <ChevronDown v-else class="h-4 w-4 text-slate-400 dark:text-gray-500" />
                                </button>
                            </div>
                        </div>

                        <div v-if="!collapsed.has(classType.class_type_id)" class="px-4 py-4 space-y-4">
                            <div v-for="term in classType.terms" :key="term.schedule_id">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2 dark:text-gray-400">{{ term.term_name }}</p>
                                <div class="flex flex-wrap gap-2">
                                    <button v-for="time in term.times" :key="time.time_id" type="button"
                                        :disabled="pendingKey === `${term.schedule_id}:${time.time_id}`"
                                        @click="toggleTime(classType, term, time)"
                                        class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-medium transition disabled:cursor-not-allowed disabled:opacity-50"
                                        :class="time.is_open
                                            ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-400'
                                            : 'border-slate-300 text-slate-500 hover:border-slate-400 dark:border-gray-600 dark:text-gray-400 dark:hover:border-gray-500'">
                                        <span class="h-2 w-2 rounded-full" :class="time.is_open ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-gray-600'" />
                                        {{ time.time_name }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { ChevronDown, ChevronUp } from '@lucide/vue';
import DashboardLayout from '@/layouts/DashboardLayout.vue';

const props = defineProps({
    course: {
        type: Object,
        default: null
    },
    categories: {
        type: Array,
        default: () => []
    },
    subCategories: {
        type: Array,
        default: () => []
    },
    tracks: {
        type: Array,
        default: () => []
    },
    classSchedules: {
        type: Array,
        default: () => []
    },
    errors: {
        type: Object,
        default: () => ({})
    }
});

const toast = useToast();

// Local mutable copy for optimistic toggling - see toggleTime().
const schedules = ref(props.classSchedules.map((classType) => ({ ...classType })));
const collapsed = ref(new Set());
// "scheduleId:timeId" of the badge currently mid-request.
const pendingKey = ref(null);

// One accent per class type so the three sections are easy to tell apart at
// a glance - keyed by name since that's stable across environments, unlike class_type_id.
const CLASS_TYPE_ACCENTS = {
    'Physical Class': {
        header: 'bg-blue-50 hover:bg-blue-100 dark:bg-blue-500/10 dark:hover:bg-blue-500/15',
        border: 'border-blue-200 dark:border-blue-500/30',
        text: 'text-blue-800 dark:text-blue-300',
        dot: 'bg-blue-500',
    },
    'Scholarship Class': {
        header: 'bg-amber-50 hover:bg-amber-100 dark:bg-amber-500/10 dark:hover:bg-amber-500/15',
        border: 'border-amber-200 dark:border-amber-500/30',
        text: 'text-amber-800 dark:text-amber-300',
        dot: 'bg-amber-500',
    },
    'Online Class': {
        header: 'bg-violet-50 hover:bg-violet-100 dark:bg-violet-500/10 dark:hover:bg-violet-500/15',
        border: 'border-violet-200 dark:border-violet-500/30',
        text: 'text-violet-800 dark:text-violet-300',
        dot: 'bg-violet-500',
    },
};
const DEFAULT_ACCENT = {
    header: 'bg-slate-50 hover:bg-slate-100 dark:bg-gray-800/60 dark:hover:bg-gray-800',
    border: 'border-slate-200 dark:border-gray-800',
    text: 'text-slate-800 dark:text-gray-200',
    dot: 'bg-slate-400',
};

function classTypeAccent(classType) {
    return CLASS_TYPE_ACCENTS[classType.class_type_name] ?? DEFAULT_ACCENT
}

function toggleCollapsed(classTypeId) {
    if (collapsed.value.has(classTypeId)) {
        collapsed.value.delete(classTypeId)
    } else {
        collapsed.value.add(classTypeId)
    }
    // Reassign so the template's reactivity picks up the Set mutation.
    collapsed.value = new Set(collapsed.value)
}

function recomputeEnabled(classType) {
    classType.is_enabled = classType.terms.some((term) => term.times.some((time) => time.is_open))
}

async function toggleTime(classType, term, time) {
    const key = `${term.schedule_id}:${time.time_id}`
    const previous = time.is_open
    time.is_open = !previous
    recomputeEnabled(classType)
    pendingKey.value = key

    try {
        await axios.post(`/dashboard/course/courses/${props.course.id}/schedules/toggle`, {
            schedule_id: term.schedule_id,
            time_id: time.time_id,
        })
    } catch (error) {
        console.error('Failed to toggle schedule availability', error)
        time.is_open = previous
        recomputeEnabled(classType)
        toast.error(error.response?.data?.message ?? 'Failed to save. Please try again.')
    } finally {
        pendingKey.value = null
    }
}

// Bulk counterpart to toggleTime() - opens or closes every time under one
// class type in a single request instead of clicking each badge.
async function setClassTypeAvailability(classType, open) {
    const previous = classType.terms.map((term) => term.times.map((time) => time.is_open))
    classType.terms.forEach((term) => term.times.forEach((time) => { time.is_open = open }))
    recomputeEnabled(classType)
    const key = `classtype:${classType.class_type_id}`
    pendingKey.value = key

    try {
        await axios.post(`/dashboard/course/courses/${props.course.id}/schedules/class-type`, {
            class_type_id: classType.class_type_id,
            open,
        })
    } catch (error) {
        console.error('Failed to bulk-toggle class type availability', error)
        classType.terms.forEach((term, i) => term.times.forEach((time, j) => { time.is_open = previous[i][j] }))
        recomputeEnabled(classType)
        toast.error(error.response?.data?.message ?? 'Failed to save. Please try again.')
    } finally {
        pendingKey.value = null
    }
}

const imagePreview = ref(null);
const existingThumbnail = ref(props.course?.thumbnail || null);
const courseCategoryId = computed(() =>
    props.course?.category_id ||
    props.course?.track?.sub_category?.category_id ||
    props.course?.track?.subCategory?.category_id ||
    ''
);
const courseSubCategoryId = computed(() =>
    props.course?.sub_category_id ||
    props.course?.track?.sub_category_id ||
    props.course?.track?.subCategory?.id ||
    ''
);

const form = useForm({
    title: props.course?.title || '',
    category_id: courseCategoryId.value,
    sub_category_id: courseSubCategoryId.value,
    course_track_id: props.course?.course_track_id || '',
    price: props.course?.enroll_config?.price ?? '',
    status: props.course?.status || 'active',
    thumbnail: null
});

// Sub categories filtered to the selected category
const filteredSubCategories = computed(() => {
    if (!form.category_id) return [];
    return props.subCategories.filter(sub =>
        sub.category_id === parseInt(form.category_id)
    );
});

// Tracks filtered to the selected sub category
const filteredTracks = computed(() => {
    if (!form.sub_category_id) return [];
    return props.tracks.filter(track =>
        track.sub_category_id === parseInt(form.sub_category_id)
    );
});

// When editing an existing course, keep the pre-selected sub category / track
// even before the user touches the dropdowns (guards against the watchers below
// wiping them out on initial load).
let initializing = true;

// Reset sub category + track whenever category changes
watch(() => form.category_id, (newVal, oldVal) => {
    if (initializing) return;
    if (newVal !== oldVal) {
        form.sub_category_id = '';
        form.course_track_id = '';
    }
});

// Reset track whenever sub category changes
watch(() => form.sub_category_id, (newVal, oldVal) => {
    if (initializing) return;
    if (newVal !== oldVal) {
        form.course_track_id = '';
    }
});

// Allow the initial values (from an existing course) to settle before
// the watchers start clearing dependent fields on user interaction.
setTimeout(() => { initializing = false; }, 0);

const handleImageUpload = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!allowedTypes.includes(file.type)) {
        alert('Please upload a valid image file (JPEG, PNG, JPG)');
        event.target.value = '';
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        alert('Image size must be less than 2MB');
        event.target.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
        imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);

    form.thumbnail = file;
};

const removeImage = () => {
    imagePreview.value = null;
    existingThumbnail.value = null;
    form.thumbnail = null;
    const fileInput = document.querySelector('input[type="file"]');
    if (fileInput) fileInput.value = '';
};

watch(() => props.course, (newCourse) => {
    existingThumbnail.value = newCourse?.thumbnail || null;
}, { immediate: true });

const submit = () => {
    const formData = new FormData();

    Object.keys(form.data()).forEach(key => {
        if (key === 'thumbnail' && form.thumbnail instanceof File) {
            formData.append('thumbnail', form.thumbnail);
        } else if (key === 'thumbnail') {
            return;
        } else if (form[key] !== null && form[key] !== undefined) {
            formData.append(key, form[key]);
        }
    });

    if (props.course) {
        formData.append('_method', 'PUT');

        router.post(`/dashboard/course/courses/${props.course.id}`, formData, {
            onSuccess: () => {
                imagePreview.value = null;
            },
            onError: (errors) => {
                console.log('Update error:', errors);
            }
        });
    } else {
        router.post('/dashboard/course/courses', formData, {
            onSuccess: () => {
                imagePreview.value = null;
            }
        });
    }
};
</script>
