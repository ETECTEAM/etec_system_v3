<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Breadcrumbs } from '@/components/ui/breadcrumbs';
import { PageHero } from '@/components/ui/page-hero';

const form = useForm({
    type_name: '',
    is_active: true,
});

const breadcrumbItems = [
    { label: 'Dashboard', href: '/dashboard' },
    { label: 'Class Types', href: '/dashboard/class-types' },
    { label: 'Create', current: true },
];

const submit = () => {
    form.post('/dashboard/class-types');
};
</script>

<template>
    <Head :title="$t('Create Class Type')" />
    <DashboardLayout>
        <section class="space-y-6">
            <Breadcrumbs :items="breadcrumbItems" />
            <PageHero eyebrow="Management" :title="$t('Create Class Type')" description="Add a new classification to your system." />

            <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-gray-800 dark:bg-gray-900">
                <form @submit.prevent="submit" class="space-y-6">
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Type Name') }}</span>
                        <input v-model="form.type_name" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20" :placeholder="$t('e.g. Hybrid')" required>
                        <span v-if="form.errors.type_name" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.type_name }}</span>
                    </label>

                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" v-model="form.is_active" class="h-4 w-4 rounded border-slate-300 text-blue-900 focus:ring-blue-900 dark:border-gray-600 dark:bg-gray-800">
                            <span class="text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Active') }}</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-gray-800">
                        <Link href="/dashboard/class-types" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">{{ $t('Cancel') }}</Link>
                        <button type="submit" :disabled="form.processing" class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500">
                            {{ form.processing ? $t('Saving...') : $t('Save Class Type') }}
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </DashboardLayout>
</template>
