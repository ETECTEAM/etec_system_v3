<script setup>
import { onMounted, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import axios from 'axios'

const roles = ref([])
const roleName = ref('')
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')

async function fetchRoles() {
    loading.value = true
    error.value = ''

    try {
        const response = await axios.get('/dashboard/permissions/roles')
        roles.value = response.data
    } catch (e) {
        error.value = 'Cannot fetch roles.'
        console.error(e)
    } finally {
        loading.value = false
    }
}

async function createRole() {
    error.value = ''
    success.value = ''

    if (!roleName.value.trim()) {
        error.value = 'Role name is required.'
        return
    }

    saving.value = true

    try {
        await axios.post('/dashboard/permissions/roles', {
            name: roleName.value.trim(),
            guard_name: 'web',
        })

        success.value = 'Role created successfully.'
        roleName.value = ''

        await fetchRoles()
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Cannot create role.'
        console.error(e)
    } finally {
        saving.value = false
    }
}

onMounted(() => {
    fetchRoles()
})
</script>

<template>
    <Head title="User Roles" />

    <DashboardLayout>
        <section class="space-y-6 p-4 sm:p-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">User Roles</h1>
                <p class="mt-1 text-sm text-slate-600">
                    View and create system roles.
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Create Role</h2>

                <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                    <input
                        v-model="roleName"
                        type="text"
                        placeholder="admin, instructor, super_admin"
                        class="w-full rounded-xl border border-slate-300 px-4 py-2 outline-none focus:border-blue-500"
                    />

                    <button
                        type="button"
                        class="rounded-xl bg-blue-600 px-5 py-2 font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60"
                        :disabled="saving"
                        @click="createRole"
                    >
                        {{ saving ? 'Saving...' : 'Save' }}
                    </button>
                </div>

                <p v-if="error" class="mt-3 text-sm font-medium text-red-600">
                    {{ error }}
                </p>

                <p v-if="success" class="mt-3 text-sm font-medium text-emerald-600">
                    {{ success }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-slate-900">Role List</h2>

                    <button
                        type="button"
                        class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        @click="fetchRoles"
                    >
                        Refresh
                    </button>
                </div>

                <p v-if="loading" class="mt-4 text-sm text-slate-500">
                    Loading...
                </p>

                <div v-else class="mt-4 overflow-x-auto">
                    <table class="w-full border border-slate-200 text-sm">
                        <thead>
                            <tr class="bg-slate-50">
                                <th class="border border-slate-200 p-3 text-left">ID</th>
                                <th class="border border-slate-200 p-3 text-left">Name</th>
                                <th class="border border-slate-200 p-3 text-left">Guard</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="role in roles" :key="role.id">
                                <td class="border border-slate-200 p-3">
                                    {{ role.id }}
                                </td>

                                <td class="border border-slate-200 p-3 font-semibold capitalize">
                                    {{ role.name.replace('_', ' ') }}
                                </td>

                                <td class="border border-slate-200 p-3">
                                    {{ role.guard_name }}
                                </td>
                            </tr>

                            <tr v-if="roles.length === 0">
                                <td colspan="3" class="p-4 text-center text-slate-500">
                                    No roles found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </DashboardLayout>
</template>