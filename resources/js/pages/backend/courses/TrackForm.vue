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
            <Link href="/dashboard/course/tracks" class="hover:text-slate-600 transition">{{ $t('Tracks') }}</Link>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-slate-600 font-medium">{{ track ? $t('Edit') : $t('Create') }}</span>
        </nav>

        <div class="w-full">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 mb-6">
                <div class="flex items-center gap-3">
                    <Link href="/dashboard/course/tracks"
                        class="text-slate-500 hover:text-slate-700 transition p-1.5 rounded-lg hover:bg-slate-100"
                        :title="$t('Back')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-gray-100">
                        {{ track ? $t('Edit Track') : $t('Create Track') }}
                    </h1>
                </div>
                <span v-if="track" class="text-sm text-slate-500 bg-slate-100 px-3 py-1 rounded-full dark:text-gray-400 dark:bg-gray-800">
                    ID: #{{ track.id }}
                </span>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-lg border border-slate-200 p-4 sm:p-6 shadow-sm dark:bg-gray-900 dark:border-gray-800">
                <div class="space-y-5">
                    <!-- Sub Category Selection -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5 dark:text-gray-300">
                            {{ $t('Sub Category') }} <span class="text-red-500 dark:text-red-400">*</span>
                        </label>
                        <select v-model="form.sub_category_id"
                            class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition bg-white"
                            required>
                            <option value="">{{ $t('Select Sub Category') }}</option>
                            <option v-for="subCategory in subCategories" :key="subCategory.id" :value="subCategory.id">
                                {{ subCategory.name }}
                            </option>
                        </select>
                        <p v-if="errors.sub_category_id" class="mt-1.5 text-sm text-red-600 flex items-center gap-1 dark:text-red-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ errors.sub_category_id }}
                        </p>
                    </div>

                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5 dark:text-gray-300">
                            {{ $t('Name') }} <span class="text-red-500 dark:text-red-400">*</span>
                        </label>
                        <input v-model="form.name" type="text"
                            class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            :placeholder="$t('Enter track name')" required autofocus />
                        <p v-if="errors.name" class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ errors.name }}
                        </p>
                    </div>

                    <!-- Slug (Auto-generated) -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5 dark:text-gray-300">{{ $t('Slug') }}</label>
                        <div class="relative">
                            <input v-model="form.slug" type="text"
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm bg-slate-50 text-slate-600 cursor-not-allowed"
                                :placeholder="$t('Auto-generated from name')" readonly disabled />
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">{{ $t('Auto-generated from the track name') }}</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ $t('Description') }}</label>
                        <textarea v-model="form.description" rows="4"
                            class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-y"
                            :placeholder="$t('Enter track description (optional)')" />
                        <p v-if="errors.description" class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ errors.description }}
                        </p>
                    </div>

                    <!-- Status -->
                    <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200">
                        <input v-model="form.status" type="checkbox"
                            class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 focus:ring-2 transition"
                            true-value="active" false-value="inactive" />
                        <label class="text-sm font-medium text-slate-700 cursor-pointer">{{ $t('Active') }}</label>
                        <span class="text-xs text-slate-500 ml-auto">
                            {{ form.status === 'active' ? $t('Track will be visible to users') : $t('Track will be hidden') }}
                        </span>
                    </div>
                </div>

                <!-- Form Actions -->
                <div
                    class="mt-8 flex flex-col sm:flex-row items-center justify-end gap-3 border-t border-slate-200 pt-6">
                    <Link href="/dashboard/course/tracks"
                        class="w-full sm:w-auto px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 rounded-lg transition text-center border border-slate-200">
                        {{ $t('Cancel') }}
                    </Link>
                    <button type="submit" :disabled="form.processing"
                        class="w-full sm:w-auto px-6 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                        {{ form.processing ? $t('Saving...') : (track ? $t('Update Track') : $t('Create Track')) }}
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
    track: {
        type: Object,
        default: null
    },
    subCategories: {
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
    sub_category_id: props.track?.sub_category_id || '',
    name: props.track?.name || '',
    slug: props.track?.slug || '',
    description: props.track?.description || '',
    status: props.track?.status || 'active'
});

// Auto-generate slug from name
watch(() => form.name, (newName) => {
    if (!props.track || form.slug === props.track?.slug) {
        form.slug = generateSlug(newName);
    }
});

const submit = () => {
    if (props.track) {
        form.put(`/dashboard/course/tracks/${props.track.id}`);
    } else {
        form.post('/dashboard/course/tracks');
    }
};
</script>
