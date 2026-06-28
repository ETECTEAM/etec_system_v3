<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Breadcrumbs } from '@/components/ui/breadcrumbs';
import { PageHero } from '@/components/ui/page-hero';

const form = useForm({
    type_name: '',
    description: '',
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
    <Head title="Create Class Type" />
    <DashboardLayout>
        <section class="space-y-6">
            <Breadcrumbs :items="breadcrumbItems" />
            <PageHero eyebrow="Management" title="Create Class Type" description="Add a new classification to your system." />

            <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Type Name</span>
                        <input v-model="form.type_name" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100" placeholder="e.g. Science" required>
                        <span v-if="form.errors.type_name" class="text-xs text-red-600">{{ form.errors.type_name }}</span>
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Description</span>
                        <textarea v-model="form.description" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100" rows="3"></textarea>
                    </label>

                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" v-model="form.is_active" class="h-4 w-4 rounded border-slate-300 text-blue-900 focus:ring-blue-900">
                            <span class="text-sm font-semibold text-slate-700">Active</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <Link href="/dashboard/class-types" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Cancel</Link>
                        <button type="submit" :disabled="form.processing" class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-70">
                            {{ form.processing ? 'Saving...' : 'Save Class Type' }}
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </DashboardLayout>
</template>