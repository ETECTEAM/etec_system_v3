<template>
    <DashboardLayout>
        <div class="w-full">

            <!-- Breadcrumb -->
            <nav class="flex items-center gap-1.5 text-sm text-slate-400 mb-4">
                <span>{{ $t('Dashboard') }}</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span>{{ $t('Course') }}</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <Link href="/dashboard/course/courses" class="hover:text-slate-600 transition">{{ $t('Courses') }}</Link>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-slate-600 font-medium">{{ course ? $t('Edit') : $t('Create') }}</span>
            </nav>

            <!-- Header -->
            <div class="flex items-center gap-4 mb-6">
                <Link href="/dashboard/course/courses"
                    class="text-slate-500 hover:text-slate-700 transition p-2 rounded-xl hover:bg-slate-100" :title="$t('Back')">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center shadow-sm shadow-blue-200 shrink-0">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s4.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                        {{ course ? $t('Edit Course') : $t('Create Course') }}
                    </h1>
                    <p class="text-sm text-slate-500 mt-0.5">
                        {{ course ? $t("Update this course's details") : $t('Add a new course to your catalog') }}
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-xl border border-slate-200 p-4 sm:p-6 shadow-sm"
                enctype="multipart/form-data">
                <div class="space-y-4">
                    <!-- Image Upload -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-gray-300">{{ $t('Thumbnail Image') }}</label>
                        <div class="flex items-center gap-4">
                            <div v-if="imagePreview || existingThumbnail"
                                class="relative w-32 h-32 rounded-lg border border-slate-200 overflow-hidden">
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
                                        class="border-2 border-dashed border-slate-300 rounded-lg p-4 text-center hover:border-blue-500 transition">
                                        <svg class="w-8 h-8 mx-auto text-slate-400" fill="none" stroke="currentColor"
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
                        <p v-if="errors.thumbnail" class="mt-1 text-sm text-rose-600">{{ errors.thumbnail }}</p>
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('Title') }} <span class="text-rose-500">*</span></label>
                        <input v-model="form.title" type="text"
                            class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            :placeholder="$t('Enter course title')" required />
                        <p v-if="errors.title" class="mt-1 text-sm text-rose-600">{{ errors.title }}</p>
                    </div>

                    <!-- Category, Sub Category, Track -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('Category') }}</label>
                            <select v-model="form.category_id"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                <option value="">{{ $t('Select Category') }}</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('Sub Category') }}</label>
                            <select v-model="form.sub_category_id" :disabled="!form.category_id"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white disabled:bg-slate-50 disabled:text-slate-400">
                                <option value="">
                                    {{ form.category_id ? $t('Select Sub Category') : $t('Select a category first') }}
                                </option>
                                <option v-for="sub in filteredSubCategories" :key="sub.id" :value="sub.id">
                                    {{ sub.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('Track') }} <span class="text-rose-500">*</span></label>
                            <select v-model="form.course_track_id" :disabled="!form.sub_category_id"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white disabled:bg-slate-50 disabled:text-slate-400">
                                <option value="">
                                    {{ form.sub_category_id ? $t('Select Track') : $t('Select a sub category first') }}
                                </option>
                                <option v-for="track in filteredTracks" :key="track.id" :value="track.id">
                                    {{ track.name }}
                                </option>
                            </select>
                            <p v-if="errors.course_track_id" class="mt-1 text-sm text-rose-600">{{ errors.course_track_id }}</p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('Description') }}</label>
                        <textarea v-model="form.description" rows="4"
                            class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-y"
                            :placeholder="$t('Enter course description')" />
                    </div>

                    <!-- Duration, Price, Document Price -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('Duration (hours)') }}</label>
                            <input v-model.number="form.duration" type="number" min="0"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('Price ($)') }}</label>
                            <input v-model.number="form.price" type="number" min="0" step="0.01"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('Document Price ($)') }}</label>
                            <input v-model.number="form.document_price" type="number" min="0" step="0.01"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" />
                            <p class="mt-1 text-xs text-slate-400">{{ $t('Leave 0 if documents are free for this course') }}</p>
                        </div>
                    </div>

                    <!-- Language and Certificate -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('Language') }}</label>
                            <input v-model="form.language" type="text"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                :placeholder="$t('e.g., English, Khmer')" />
                        </div>
                        <div class="flex items-center gap-2 pt-6">
                            <input v-model="form.certificate_available" type="checkbox"
                                class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 focus:ring-2 transition" />
                            <label class="text-sm text-slate-700">{{ $t('Certificate Available') }}</label>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200">
                        <input v-model="form.status" type="checkbox"
                            class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 focus:ring-2 transition"
                            true-value="active" false-value="inactive" />
                        <label class="text-sm font-medium text-slate-700 cursor-pointer">{{ $t('Active') }}</label>
                        <span class="text-xs text-slate-500 ml-auto">
                            {{ form.status === 'active' ? $t('Course will be visible to students') : $t('Course will be hidden') }}
                        </span>
                    </div>
                </div>

                <div class="mt-8 flex items-center justify-end gap-3 border-t border-slate-200 pt-6">
                    <Link href="/dashboard/course/courses"
                        class="px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 rounded-xl transition border border-slate-200">
                        {{ $t('Cancel') }}
                    </Link>
                    <button type="submit" :disabled="form.processing"
                        class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition disabled:opacity-50 flex items-center justify-center gap-2">
                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ form.processing ? $t('Saving...') : $t('Save Course') }}
                    </button>
                </div>
            </form>
        </div>
    </DashboardLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
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
    errors: {
        type: Object,
        default: () => ({})
    }
});

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
const toBoolean = (value) => value === true || value === 1 || value === '1';

const form = useForm({
    title: props.course?.title || '',
    category_id: courseCategoryId.value,
    sub_category_id: courseSubCategoryId.value,
    course_track_id: props.course?.course_track_id || '',
    description: props.course?.description || '',
    duration: props.course?.duration || '',
    price: props.course?.price || '',
    document_price: props.course?.document_price || '',
    language: props.course?.language || 'en',
    certificate_available: toBoolean(props.course?.certificate_available),
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
        } else if (key === 'certificate_available') {
            formData.append(key, form[key] ? 1 : 0);
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
