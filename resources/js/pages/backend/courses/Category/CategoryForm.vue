<template>
    <DashboardLayout>
        <div class="w-full">

            <!-- Breadcrumb -->
            <nav class="flex items-center gap-1.5 text-sm text-slate-400 dark:text-gray-500 mb-4">
                <span>{{ $t('Dashboard') }}</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <Link href="/dashboard/course/categories" class="hover:text-slate-600 dark:hover:text-gray-300 transition">{{ $t('Categories') }}</Link>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-slate-600 dark:text-gray-300 font-medium">{{ category ? $t('Edit') : $t('Create') }}</span>
            </nav>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-gray-100 tracking-tight">
                            {{ category ? $t('Edit Category') : $t('Create Category') }}
                        </h1>
                        <p class="text-sm text-slate-500 dark:text-gray-400 mt-0.5">
                            {{ category ? $t("Update this category's details") : $t('Add a new learning category') }}
                        </p>
                    </div>
                </div>
                <span v-if="category" class="text-sm text-slate-500 bg-slate-100 dark:text-gray-400 dark:bg-gray-800 px-3 py-1.5 rounded-full font-medium">
                    ID: #{{ category.id }}
                </span>
            </div>

            <form @submit.prevent="submit" class="bg-white dark:bg-gray-900 rounded-xl border border-slate-200 dark:border-gray-800 p-4 sm:p-6 shadow-sm">
                <div class="grid gap-5 sm:grid-cols-2">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1.5">
                            {{ $t('Name') }} <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500"
                            :placeholder="$t('Enter category name')"
                            required
                            autofocus
                        />
                        <p v-if="errors.name" class="mt-1.5 text-sm text-rose-600 dark:text-rose-400 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ errors.name }}
                        </p>
                    </div>

                    <!-- Status -->
                    <div class="flex flex-col justify-center gap-1.5 p-3 bg-slate-50 rounded-lg border border-slate-200 dark:bg-gray-800/40 dark:border-gray-700">
                        <label class="flex items-center gap-2 text-sm font-medium text-slate-700 cursor-pointer dark:text-gray-300">
                            <input
                                v-model="form.status"
                                type="checkbox"
                                class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 focus:ring-2 transition dark:border-gray-600 dark:bg-gray-800"
                                true-value="active"
                                false-value="inactive"
                            />
                            {{ $t('Active') }}
                        </label>
                        <span class="text-xs text-slate-500 dark:text-gray-400">
                            {{ form.status === 'active' ? $t('Category will be visible to users') : $t('Category will be hidden') }}
                        </span>
                    </div>
                </div>

                <!-- Divider -->
                <div class="my-6 border-t border-slate-200 dark:border-gray-800" />

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link
                        href="/dashboard/course/categories"
                        class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        {{ $t('Cancel') }}
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500"
                    >
                        {{ form.processing ? $t('Saving...') : (category ? $t('Update Category') : $t('Create Category')) }}
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
    category: {
        type: Object,
        default: null
    },
    errors: {
        type: Object,
        default: () => ({})
    }
});

const form = useForm({
    name: props.category?.name || '',
    status: props.category?.status || 'active'
});

const submit = () => {
    if (props.category) {
        form.put(`/dashboard/course/categories/${props.category.id}`);
    } else {
        form.post('/dashboard/course/categories');
    }
};
</script>
