<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { Head, useForm, Link, usePage } from '@inertiajs/vue3'

const page = usePage()

const time = page.props.time
const terms = page.props.terms

const form = useForm({
    time_name: time.time_name,
    term_id: time.term_id,
})

function submit() {
    form.put(`/dashboard/times/${time.id}`)
}
</script>

<template>
    <Head title="Edit Time" />

    <DashboardLayout>

        <div class="max-w-xl mx-auto space-y-6">

            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Edit Time
                </h1>

                <p class="text-sm text-gray-500">
                    Update time information
                </p>
            </div>

            <form
                @submit.prevent="submit"
                class="rounded-md border bg-white p-6 shadow-sm space-y-6"
            >

                <div>
                    <label class="text-sm font-medium text-gray-700">
                        Time Name
                    </label>

                    <input
                        v-model="form.time_name"
                        type="text"
                        class="mt-2 w-full rounded-md border border-gray-300 px-4 py-3 text-sm
                        focus:border-yellow-500 focus:ring-2 focus:ring-yellow-100 outline-none"
                    />

                    <p
                        v-if="form.errors.time_name"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.time_name }}
                    </p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">
                        Term
                    </label>

                    <select
                        v-model="form.term_id"
                        class="mt-2 w-full rounded-md border border-gray-300 px-4 py-3 text-sm
                        focus:border-yellow-500 focus:ring-2 focus:ring-yellow-100 outline-none"
                    >
                        <option value="">Select Term</option>

                        <option
                            v-for="term in terms"
                            :key="term.id"
                            :value="term.id"
                        >
                            {{ term.term_name }}
                        </option>
                    </select>

                    <p
                        v-if="form.errors.term_id"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.term_id }}
                    </p>
                </div>

                <div class="flex justify-end gap-3">

                    <Link
                        href="/dashboard/times"
                        class="rounded-md border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </Link>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-md bg-yellow-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-yellow-600 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Updating...' : 'Update Time' }}
                    </button>

                </div>

            </form>

        </div>

    </DashboardLayout>
</template>
