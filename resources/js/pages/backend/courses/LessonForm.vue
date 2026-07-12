<template>
    <DashboardLayout>

        <!-- Breadcrumb -->
            <nav class="flex items-center gap-1.5 text-sm text-slate-400 mb-4">
                <span>Dashboard</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span>Course</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <Link href="/dashboard/course/lessons" class="hover:text-slate-600 transition">Lessons</Link>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-slate-600 font-medium">{{ course ? 'Edit' : 'Create' }}</span>
            </nav>


        <div class="p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 mb-6">
                <div class="flex items-center gap-3">
                    <Link href="/dashboard/course/lessons" class="text-slate-500 hover:text-slate-700 transition p-1.5 rounded-lg hover:bg-slate-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800" title="Back">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </Link>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-gray-100">
                        {{ lesson ? 'Edit Lesson' : 'Create Lesson' }}
                    </h1>
                </div>
                <span v-if="lesson" class="text-sm text-slate-500 bg-slate-100 px-3 py-1 rounded-full dark:text-gray-400 dark:bg-gray-800">
                    ID: #{{ lesson.id }}
                </span>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-lg border border-slate-200 p-4 sm:p-6 shadow-sm dark:bg-gray-900 dark:border-gray-800">
                <div class="space-y-5">
                    <!-- Course Selection -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5 dark:text-gray-300">
                            Course <span class="text-red-500 dark:text-red-400">*</span>
                        </label>
                        <select
                            v-model="form.course_id"
                            class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition bg-white dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                            required
                        >
                            <option value="">Select Course</option>
                            <option v-for="course in courses" :key="course.id" :value="course.id">
                                {{ course.title }}
                            </option>
                        </select>
                        <p v-if="errors.course_id" class="mt-1.5 text-sm text-red-600 flex items-center gap-1 dark:text-red-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ errors.course_id }}
                        </p>
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5 dark:text-gray-300">
                            Title <span class="text-red-500 dark:text-red-400">*</span>
                        </label>
                        <input
                            v-model="form.title"
                            type="text"
                            class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500"
                            placeholder="Enter lesson title"
                            required
                            autofocus
                        />
                        <p v-if="errors.title" class="mt-1.5 text-sm text-red-600 flex items-center gap-1 dark:text-red-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ errors.title }}
                        </p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5 dark:text-gray-300">Description</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-y dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500"
                            placeholder="Enter lesson description (optional)"
                        />
                    </div>

                    <!-- Order Number and Duration -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5 dark:text-gray-300">Order Number</label>
                            <input
                                v-model.number="form.order_number"
                                type="number"
                                min="1"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500"
                                placeholder="1"
                            />
                            <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">Order of lesson in the course</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5 dark:text-gray-300">Duration (minutes)</label>
                            <input
                                v-model.number="form.duration"
                                type="number"
                                min="0"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500"
                                placeholder="0"
                            />
                            <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">Estimated time to complete</p>
                        </div>
                    </div>

                    <!-- Video URL -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5 dark:text-gray-300">Video URL</label>
                        <input
                            v-model="form.video_url"
                            type="url"
                            class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500"
                            placeholder="https://example.com/video.mp4"
                        />
                        <p v-if="errors.video_url" class="mt-1.5 text-sm text-red-600 flex items-center gap-1 dark:text-red-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ errors.video_url }}
                        </p>
                    </div>

                    <!-- Content -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5 dark:text-gray-300">Content</label>
                        <textarea
                            v-model="form.content"
                            rows="6"
                            class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-y dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500"
                            placeholder="Enter lesson content (optional)"
                        />
                    </div>

                    <!-- Status -->
                    <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200 dark:bg-gray-800/40 dark:border-gray-700">
                        <input
                            v-model="form.status"
                            type="checkbox"
                            class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 focus:ring-2 transition dark:border-gray-600 dark:bg-gray-800"
                            true-value="active"
                            false-value="inactive"
                        />
                        <label class="text-sm font-medium text-slate-700 cursor-pointer dark:text-gray-300">Active</label>
                        <span class="text-xs text-slate-500 ml-auto dark:text-gray-400">
                            {{ form.status === 'active' ? 'Lesson will be visible to students' : 'Lesson will be hidden' }}
                        </span>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="mt-8 flex flex-col sm:flex-row items-center justify-end gap-3 border-t border-slate-200 pt-6 dark:border-gray-800">
                    <Link
                        href="/dashboard/course/lessons"
                        class="w-full sm:w-auto px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 rounded-lg transition text-center border border-slate-200 dark:text-gray-300 dark:hover:bg-gray-800 dark:border-gray-700"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full sm:w-auto px-6 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 dark:bg-blue-600 dark:hover:bg-blue-500"
                    >
                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ form.processing ? 'Saving...' : (lesson ? 'Update Lesson' : 'Create Lesson') }}
                    </button>
                </div>
            </form>
        </div>
    </DashboardLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';

const props = defineProps({
    lesson: {
        type: Object,
        default: null
    },
    courses: {
        type: Array,
        default: () => []
    },
    errors: {
        type: Object,
        default: () => ({})
    }
});

const form = useForm({
    course_id: props.lesson?.course_id || '',
    title: props.lesson?.title || '',
    description: props.lesson?.description || '',
    content: props.lesson?.content || '',
    video_url: props.lesson?.video_url || '',
    duration: props.lesson?.duration || '',
    order_number: props.lesson?.order_number || 1,
    status: props.lesson?.status || 'active'
});

const submit = () => {
    if (props.lesson) {
        form.put(`/dashboard/course/lessons/${props.lesson.id}`);
    } else {
        form.post('/dashboard/course/lessons');
    }
};
</script>
