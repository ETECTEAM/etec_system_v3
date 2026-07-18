<template>
    <DashboardLayout>
        <div class="p-6 max-w-3xl mx-auto">
            <div class="flex items-center gap-4 mb-6">
                <Link href="/dashboard/course/courses" class="text-slate-500 hover:text-slate-700 transition dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </Link>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-gray-100">
                    {{ course ? 'Edit Course' : 'Create Course' }}
                </h1>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-lg border border-slate-200 p-6 dark:bg-gray-900 dark:border-gray-800" enctype="multipart/form-data">
                <div class="space-y-4">
                    <!-- Image Upload -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-gray-300">Thumbnail Image</label>
                        <div class="flex items-center gap-4">
                            <div v-if="imagePreview || form.thumbnail" class="relative w-32 h-32 rounded-lg border border-slate-200 overflow-hidden dark:border-gray-700">
                                <img
                                    :src="imagePreview || `/storage/${form.thumbnail}`"
                                    alt="Course thumbnail"
                                    class="w-full h-full object-cover"
                                />
                                <button
                                    v-if="imagePreview || form.thumbnail"
                                    type="button"
                                    @click="removeImage"
                                    class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="flex-1">
                                <label class="cursor-pointer">
                                    <div class="border-2 border-dashed border-slate-300 rounded-lg p-4 text-center hover:border-blue-500 transition dark:border-gray-700 dark:hover:border-blue-500">
                                        <svg class="w-8 h-8 mx-auto text-slate-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <p class="text-sm text-slate-600 dark:text-gray-300">Click to upload image</p>
                                        <p class="text-xs text-slate-400 dark:text-gray-500">PNG, JPG, JPEG up to 2MB</p>
                                    </div>
                                    <input
                                        type="file"
                                        accept="image/*"
                                        @change="handleImageUpload"
                                        class="hidden"
                                    />
                                </label>
                            </div>
                        </div>
                        <p v-if="errors.thumbnail" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.thumbnail }}</p>
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-gray-300">Title *</label>
                        <input
                            v-model="form.title"
                            type="text"
                            class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500"
                            placeholder="Enter course title"
                            required
                        />
                        <p v-if="errors.title" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.title }}</p>
                    </div>

                    <!-- Category, Sub Category, Track -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-gray-300">Category</label>
                            <select
                                v-model="form.category_id"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                            >
                                <option value="">Select Category</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-gray-300">Sub Category</label>
                            <select
                                v-model="form.sub_category_id"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                            >
                                <option value="">Select Sub Category</option>
                                <option v-for="sub in subCategories" :key="sub.id" :value="sub.id">
                                    {{ sub.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-gray-300">Track *</label>
                            <select
                                v-model="form.course_track_id"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                            >
                                <option value="">Select Track</option>
                                <option v-for="track in tracks" :key="track.id" :value="track.id">
                                    {{ track.name }}
                                </option>
                            </select>
                            <p v-if="errors.course_track_id" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.course_track_id }}</p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-gray-300">Description</label>
                        <textarea
                            v-model="form.description"
                            rows="4"
                            class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500"
                            placeholder="Enter course description"
                        />
                    </div>

                    <!-- Level, Duration, Price -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-gray-300">Level</label>
                            <select
                                v-model="form.level"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                            >
                                <option value="beginner">Beginner</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="advanced">Advanced</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-gray-300">Duration (hours)</label>
                            <input
                                v-model.number="form.duration"
                                type="number"
                                min="0"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-gray-300">Price ($)</label>
                            <input
                                v-model.number="form.price"
                                type="number"
                                min="0"
                                step="0.01"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                            />
                        </div>
                    </div>

                    <!-- Language and Certificate -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-gray-300">Language</label>
                            <input
                                v-model="form.language"
                                type="text"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500"
                                placeholder="e.g., English, Khmer"
                            />
                        </div>
                        <div class="flex items-center gap-2 pt-6">
                            <input
                                v-model="form.certificate_available"
                                type="checkbox"
                                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800"
                            />
                            <label class="text-sm text-slate-700 dark:text-gray-300">Certificate Available</label>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="flex items-center gap-2">
                        <input
                            v-model="form.status"
                            type="checkbox"
                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800"
                            true-value="active"
                            false-value="inactive"
                        />
                        <label class="text-sm text-slate-700 dark:text-gray-300">Active</label>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-200 pt-6 dark:border-gray-800">
                    <Link href="/dashboard/course/courses" class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 rounded-lg transition dark:text-gray-300 dark:hover:bg-gray-800">
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition disabled:opacity-50 dark:bg-blue-600 dark:hover:bg-blue-500"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Course' }}
                    </button>
                </div>
            </form>
        </div>
    </DashboardLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
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

const form = useForm({
    title: props.course?.title || '',
    category_id: props.course?.category_id || '',
    sub_category_id: props.course?.sub_category_id || '',
    course_track_id: props.course?.course_track_id || '',
    description: props.course?.description || '',
    level: props.course?.level || 'beginner',
    duration: props.course?.duration || '',
    price: props.course?.price || '',
    language: props.course?.language || 'en',
    certificate_available: props.course?.certificate_available ?? false,
    status: props.course?.status || 'active',
    thumbnail: null
});

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
    form.thumbnail = null;
    const fileInput = document.querySelector('input[type="file"]');
    if (fileInput) fileInput.value = '';
};

watch(() => props.course, (newCourse) => {
    if (newCourse?.thumbnail) {
        form.thumbnail = newCourse.thumbnail;
    }
}, { immediate: true });

const submit = () => {
    const formData = new FormData();
    
    Object.keys(form.data()).forEach(key => {
        if (key === 'thumbnail' && form.thumbnail instanceof File) {
            formData.append('thumbnail', form.thumbnail);
        } else if (key === 'certificate_available') {
            formData.append(key, form[key] ? 1 : 0);
        } else if (form[key] !== null && form[key] !== undefined) {
            formData.append(key, form[key]);
        }
    });

    if (props.course) {
        formData.append('_method', 'PUT');
        
        router.post(`/dashboard/course/courses/${props.course.id}`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            onSuccess: () => {
                imagePreview.value = null;
            },
            onError: (errors) => {
                console.log('Update error:', errors);
            }
        });
    } else {
        router.post('/dashboard/course/courses', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            onSuccess: () => {
                imagePreview.value = null;
            }
        });
    }
};
</script>