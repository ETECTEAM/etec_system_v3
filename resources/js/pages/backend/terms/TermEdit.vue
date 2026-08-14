<template>
    <DashboardLayout>
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-1.5 text-sm text-slate-400 dark:text-gray-500 mb-4">
            <span>{{ $t('Dashboard') }}</span>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <Link href="/dashboard/terms" class="hover:text-slate-600 dark:hover:text-gray-300 transition">{{ $t('Terms') }}</Link>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-slate-600 dark:text-gray-300 font-medium">{{ $t('Edit') }}</span>
        </nav>

        <div class="w-full">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 mb-6">
                <div class="flex items-center gap-3">
                    <Link href="/dashboard/terms" class="text-slate-500 hover:text-slate-700 transition p-1.5 rounded-lg hover:bg-slate-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800" :title="$t('Back')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </Link>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-gray-100">
                        {{ $t('Edit Term') }}
                    </h1>
                </div>
                <span class="text-sm text-slate-500 bg-slate-100 px-3 py-1 rounded-full dark:text-gray-400 dark:bg-gray-800">
                    ID: #{{ term.id }}
                </span>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-lg border border-slate-200 p-4 sm:p-6 shadow-sm dark:bg-gray-900 dark:border-gray-800">
                <div class="space-y-5">
                    <!-- Term Name -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5 dark:text-gray-300">
                            {{ $t('Term Name') }} <span class="text-red-500 dark:text-red-400">*</span>
                        </label>
                        <input
                            v-model="form.term_name"
                            type="text"
                            class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500"
                            :placeholder="$t('Enter term name')"
                            required
                            autofocus
                        />
                        <p v-if="form.errors.term_name" class="mt-1.5 text-sm text-red-600 flex items-center gap-1 dark:text-red-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ form.errors.term_name }}
                        </p>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="mt-8 flex flex-col sm:flex-row items-center justify-end gap-3 border-t border-slate-200 pt-6 dark:border-gray-800">
                    <Link
                        href="/dashboard/terms"
                        class="w-full sm:w-auto px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 rounded-lg transition text-center border border-slate-200 dark:text-gray-300 dark:hover:bg-gray-800 dark:border-gray-700"
                    >
                        {{ $t('Cancel') }}
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full sm:w-auto px-6 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 dark:bg-blue-600 dark:hover:bg-blue-500"
                    >
                        {{ form.processing ? $t('Saving...') : $t('Update Term') }}
                    </button>
                </div>
            </form>
        </div>
    </DashboardLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import DashboardLayout from '@/layouts/DashboardLayout.vue'

const props = defineProps({
    term: {
        type: Object,
        required: true,
    },
})

const form = useForm({
    term_name: props.term.term_name,
})

function submit() {
    form.put(`/dashboard/terms/${props.term.id}`)
}
</script>
