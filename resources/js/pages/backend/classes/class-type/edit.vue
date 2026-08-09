<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { Breadcrumbs } from '@/components/ui/breadcrumbs'
import { PageHero } from '@/components/ui/page-hero'

const props = defineProps({
    classType: Object
});

const form = useForm({
    type_name: props.classType.type_name,
    description: props.classType.description || '',
    is_active: props.classType.is_active ? 1 : 0,
});

const breadcrumbItems = [
    { label: 'Dashboard', href: '/dashboard' },
    { label: 'Class Types', href: '/dashboard/class-types' },
    { label: 'Edit', current: true },
];

function submit() {
    form.put(`/dashboard/class-types/${props.classType.class_type_id}`);
}
</script>

<template>
    <Head :title="`Edit Class Type - ${classType.type_name}`" />

    <DashboardLayout>
        <section class="space-y-6">
            <Breadcrumbs :items="breadcrumbItems" />
            <PageHero
                eyebrow="Management"
                :title="$t('Edit Class Type')"
                description="Update the name, description, and status of this class category."
            />

            <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-gray-800 dark:bg-gray-900">
                <form class="grid gap-6 sm:grid-cols-2" @submit.prevent="submit">

                    <label class="block sm:col-span-2">
                        <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Type Name') }}</span>
                        <input
                            v-model="form.type_name"
                            type="text"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                            :placeholder="$t('e.g., Full-Time, Part-Time')"
                            required
                        >
                        <span v-if="form.errors.type_name" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.type_name }}</span>
                    </label>

                    <label class="block sm:col-span-2">
                        <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Description') }}</span>
                        <textarea
                            v-model="form.description"
                            rows="4"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                            :placeholder="$t('Briefly describe this class type')"
                        ></textarea>
                    </label>

                    <label class="block sm:col-span-2">
                        <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Status') }}</span>
                        <select
                            v-model="form.is_active"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                        >
                            <option :value="1">{{ $t('Active') }}</option>
                            <option :value="0">{{ $t('Inactive') }}</option>
                        </select>
                    </label>

                    <div class="flex justify-end gap-3 sm:col-span-2 pt-4 border-t border-slate-100 dark:border-gray-800">
                        <Link
                            href="/dashboard/class-types"
                            class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                        >
                            {{ $t('Cancel') }}
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:opacity-70 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                        >
                            {{ form.processing ? $t('Saving...') : $t('Save Changes') }}
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </DashboardLayout>
</template>