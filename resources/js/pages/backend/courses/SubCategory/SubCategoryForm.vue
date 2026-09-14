<template>
    <DashboardLayout>
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
                                <Link href="/dashboard/course/subcategories" class="hover:text-slate-600 transition">{{ $t('Sub Categories') }}</Link>

                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-slate-600 font-medium">{{ subCategory ? $t('Edit') : $t('Create') }}</span>
            </nav>
        <div class="w-full">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 mb-6">
                <div class="flex items-center gap-3">
                    <Link href="/dashboard/course/subcategories" class="text-slate-500 hover:text-slate-700 transition p-1.5 rounded-lg hover:bg-slate-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800" :title="$t('Back')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </Link>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-gray-100">
                        {{ subCategory ? $t('Edit Sub Category') : $t('Create Sub Category') }}
                    </h1>
                </div>
                <span v-if="subCategory" class="text-sm text-slate-500 bg-slate-100 px-3 py-1 rounded-full dark:text-gray-400 dark:bg-gray-800">
                    ID: #{{ subCategory.id }}
                </span>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-lg border border-slate-200 p-4 sm:p-6 shadow-sm dark:bg-gray-900 dark:border-gray-800">
                <div class="space-y-5">
                    <!-- Category Selection -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5 dark:text-gray-300">
                            {{ $t('Category') }} <span class="text-red-500 dark:text-red-400">*</span>
                        </label>
                        <select
                            v-model="form.category_id"
                            class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition bg-white dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                            required
                        >
                            <option value="">{{ $t('Select Category') }}</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                        <p v-if="errors.category_id" class="mt-1.5 text-sm text-red-600 flex items-center gap-1 dark:text-red-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ errors.category_id }}
                        </p>
                    </div>

                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5 dark:text-gray-300">
                            {{ $t('Name') }} <span class="text-red-500 dark:text-red-400">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500"
                            :placeholder="$t('Enter sub category name')"
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
                        <label class="block text-sm font-medium text-slate-700 mb-1.5 dark:text-gray-300">{{ $t('Slug') }}</label>
                        <div class="relative">
                            <input
                                v-model="form.slug"
                                type="text"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm bg-slate-50 text-slate-600 cursor-not-allowed dark:border-gray-700 dark:bg-gray-800/60 dark:text-gray-400"
                                :placeholder="$t('Auto-generated from name')"
                                readonly
                                disabled
                            />
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <svg class="w-4 h-4 text-slate-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">{{ $t('Auto-generated from the sub category name') }}</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5 dark:text-gray-300">{{ $t('Description') }}</label>
                        <textarea
                            v-model="form.description"
                            rows="4"
                            class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-y dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500"
                            :placeholder="$t('Enter description (optional)')"
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
                        <label class="text-sm font-medium text-slate-700 cursor-pointer dark:text-gray-300">{{ $t('Active') }}</label>
                        <span class="text-xs text-slate-500 ml-auto dark:text-gray-400">
                            {{ form.status === 'active' ? $t('Sub category will be visible to users') : $t('Sub category will be hidden') }}
                        </span>
                    </div>
                </div>

                <!-- Divider -->
                <div class="my-6 border-t border-slate-200 dark:border-gray-800" />

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link
                        href="/dashboard/course/subcategories"
                        class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        {{ $t('Cancel') }}
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500"
                    >
                        {{ form.processing ? $t('Saving...') : (subCategory ? $t('Update Sub Category') : $t('Create Sub Category')) }}
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
    subCategory: {
        type: Object,
        default: null
    },
    categories: {
        type: Array,
        default: () => []
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
    category_id: props.subCategory?.category_id || '',
    name: props.subCategory?.name || '',
    slug: props.subCategory?.slug || '',
    description: props.subCategory?.description || '',
    status: props.subCategory?.status || 'active'
});

// Auto-generate slug from name
watch(() => form.name, (newName) => {
    if (!props.subCategory || form.slug === props.subCategory?.slug) {
        form.slug = generateSlug(newName);
    }
});

const submit = () => {
    if (props.subCategory) {
        form.put(`/dashboard/course/subcategories/${props.subCategory.id}`);
    } else {
        form.post('/dashboard/course/subcategories');
    }
};
</script>
