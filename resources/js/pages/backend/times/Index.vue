<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({
    times: Object,
    filters: Object,
})

const search = ref(props.filters.search ?? '')

let timeout = null

watch(search, (value) => {
    clearTimeout(timeout)

    timeout = setTimeout(() => {
        router.get(
            '/dashboard/times',
            {
                search: value,
                page: 1,
            },
            {
                preserveState: true,
                replace: true,
            }
        )
    }, 400)
})
</script>

<template>
    <DashboardLayout>

        <div class="space-y-6">

            <div class="flex items-center justify-between">

                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        Times
                    </h1>

                    <p class="text-sm text-gray-500">
                        Manage all times
                    </p>
                </div>

                <div class="flex items-center justify-end gap-4 w-[40%]">

                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search time..."
                        class="w-full max-w-sm rounded-xl border px-4 py-2 text-sm
                        focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none"
                    />

                    <Link
                        href="/dashboard/times/create"
                        class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                    >
                        + Create Time
                    </Link>

                </div>

            </div>

            <div class="rounded-2xl bg-white shadow-sm overflow-hidden">

                <table class="w-full text-left">

                    <thead class="border-b bg-gray-100 text-gray-600 text-sm">
                        <tr>
                            <th class="p-4">ID</th>
                            <th class="p-4">Time Name</th>
                            <th class="p-4">Term</th>
                            <th class="p-4 text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <tr
                            v-for="time in times.data"
                            :key="time.id"
                            class="border-b hover:bg-gray-50"
                        >
                            <td class="p-4 text-gray-500">
                                {{ time.id }}
                            </td>

                            <td class="p-4 font-medium text-gray-900">
                                {{ time.time_name }}
                            </td>

                            <td class="p-4">
                                {{ time.term?.term_name }}
                            </td>

                            <td class="p-4 text-right space-x-2">

                                <Link
                                    :href="`/dashboard/times/${time.id}/edit`"
                                    class="rounded-lg bg-yellow-500 px-3 py-1 text-white text-sm hover:bg-yellow-600"
                                >
                                    Edit
                                </Link>

                                <Link
                                    :href="`/dashboard/times/${time.id}`"
                                    method="delete"
                                    as="button"
                                    class="rounded-lg bg-red-500 px-3 py-1 text-white text-sm hover:bg-red-600"
                                >
                                    Delete
                                </Link>

                            </td>
                        </tr>

                        <tr v-if="!times?.data?.length">
                            <td
                                colspan="4"
                                class="text-center py-6 text-gray-500"
                            >
                                No times found
                            </td>
                        </tr>

                    </tbody>

                </table>

            </div>

            <div class="flex justify-between items-center text-sm text-gray-600">

                <div>
                    Showing {{ times.from }} to {{ times.to }}
                    of {{ times.total }}
                </div>

                <div class="space-x-2">

                    <Link
                        v-for="link in times.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="px-3 py-1 border rounded-lg"
                        :class="{
                            'bg-blue-600 text-white': link.active,
                            'opacity-50 pointer-events-none': !link.url
                        }"
                    />

                </div>

            </div>

        </div>

    </DashboardLayout>
</template>