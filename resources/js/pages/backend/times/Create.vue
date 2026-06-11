<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { Link, useForm, Head, usePage } from '@inertiajs/vue3'

const page = usePage()

const terms = page.props.terms

const form = useForm({
    time_name: '',
    term_id: '',
})

function submit() {
    form.post('/dashboard/times')
}
</script>

<template>
    <Head title="Create Time" />

    <DashboardLayout>
        <div class="max-w-xl mx-auto space-y-6">

            <div>
                <h1 class="text-2xl font-bold text-gray-900">Create Time</h1>
                <p class="text-sm text-gray-500">
                    Add a new time schedule
                </p>
            </div>

            <form
                @submit.prevent="submit"
                class="rounded-2xl border bg-white p-6 shadow-sm space-y-5"
            >

                <div>
                    <label class="text-sm font-medium text-gray-700">
                        Time Name
                    </label>

                    <input
                        v-model="form.time_name"
                        type="text"
                        class="mt-2 w-full rounded-xl border px-4 py-3 text-sm
                        focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none"
                        placeholder="Enter time name"
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
                        class="mt-2 w-full rounded-xl border px-4 py-3 text-sm
                        focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none"
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
                        class="rounded-xl border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </Link>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                    >
                        Create Time
                    </button>

                </div>

            </form>

        </div>
    </DashboardLayout>
</template>