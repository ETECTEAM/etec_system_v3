<template>
    <DashboardLayout>
        <div class="p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 mb-6">
                <div class="flex items-center gap-3">
                    <Link href="/dashboard/course/categories" class="text-slate-500 hover:text-slate-700 transition p-1.5 rounded-lg hover:bg-slate-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800" title="Back">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </Link>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-gray-100">
                        {{ category ? 'Edit Category' : 'Create Category' }}
                    </h1>
                </div>
                <span v-if="category" class="text-sm text-slate-500 bg-slate-100 px-3 py-1 rounded-full dark:text-gray-400 dark:bg-gray-800">
                    ID: #{{ category.id }}
                </span>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-lg border border-slate-200 p-4 sm:p-6 shadow-sm dark:bg-gray-900 dark:border-gray-800">
                <div class="space-y-5">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5 dark:text-gray-300">
                            Name <span class="text-red-500 dark:text-red-400">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500"
                            placeholder="Enter category name"
                            required
                            autofocus
                        />
                        <p v-if="errors.name" class="mt-1.5 text-sm text-red-600 flex items-center gap-1 dark:text-red-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ errors.name }}
                        </p>
                    </div>

                    <!-- Slug (Auto-generated) -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5 dark:text-gray-300">Slug</label>
                        <div class="relative">
                            <input
                                v-model="form.slug"
                                type="text"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm bg-slate-50 text-slate-600 cursor-not-allowed dark:border-gray-700 dark:bg-gray-800/60 dark:text-gray-400"
                                placeholder="Auto-generated from name"
                                readonly
                                disabled
                            />
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <svg class="w-4 h-4 text-slate-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">Auto-generated from the category name</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5 dark:text-gray-300">Description</label>
                        <textarea
                            v-model="form.description"
                            rows="4"
                            class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-y dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500"
                            placeholder="Enter category description (optional)"
                        />
                        <p v-if="errors.description" class="mt-1.5 text-sm text-red-600 flex items-center gap-1 dark:text-red-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ errors.description }}
                        </p>
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
                            {{ form.status === 'active' ? 'Category will be visible to users' : 'Category will be hidden' }}
                        </span>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="mt-8 flex flex-col sm:flex-row items-center justify-end gap-3 border-t border-slate-200 pt-6 dark:border-gray-800">
                    <Link
                        href="/dashboard/course/categories"
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
                        {{ form.processing ? 'Saving...' : (category ? 'Update Category' : 'Create Category') }}
                    </button>
                </div>
            </form>
        </div>
    </DashboardLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';

const props = defineProps({
    category: {
        type: Object,
        default: null
    },
    errors: {
        type: Object,
        default: () => ({})
    }
});

// Generate slug from name
const generateSlug = (name) => {
    return name
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
};

const form = useForm({
    name: props.category?.name || '',
    slug: props.category?.slug || '',
    description: props.category?.description || '',
    status: props.category?.status || 'active'
});

// Auto-generate slug from name (only for new categories or when name changes and slug is empty)
watch(() => form.name, (newName) => {
    if (!props.category || form.slug === props.category?.slug) {
        form.slug = generateSlug(newName);
    }
});

const submit = () => {
    if (props.category) {
        form.put(`/dashboard/course/categories/${props.category.id}`);
    } else {
        form.post('/dashboard/course/categories');
    }
};
</script>