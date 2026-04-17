<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import PageHero from '../../../components/PageHero.vue'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'

const page = usePage()
const users = page.props.users ?? []
const canCreateUser = page.props.canCreateUser ?? false
</script>

<template>
  <Head title="User" />

  <DashboardLayout>
    <section class="space-y-6 p-4 sm:p-6">
      <PageHero eyebrow="User Management" title="User" description="View existing users and manage account roles." />

      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-lg font-bold text-slate-900">User List</h2>
          <Link v-if="canCreateUser" href="/dashboard/users/create" class="rounded-lg bg-blue-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
            Create User
          </Link>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full border-collapse text-left text-sm">
            <thead>
              <tr class="border-b border-slate-200 text-slate-600">
                <th class="px-3 py-2 font-semibold">Name</th>
                <th class="px-3 py-2 font-semibold">Email</th>
                <th class="px-3 py-2 font-semibold">Roles</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in users" :key="user.id" class="border-b border-slate-100 text-slate-800">
                <td class="px-3 py-2">{{ user.name }}</td>
                <td class="px-3 py-2">{{ user.email }}</td>
                <td class="px-3 py-2">
                  <span
                    v-for="role in user.roles"
                    :key="`${user.id}-${role}`"
                    class="mr-2 inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold capitalize text-slate-700"
                  >
                    {{ role.replace('_', ' ') }}
                  </span>
                </td>
              </tr>
              <tr v-if="users.length === 0">
                <td colspan="3" class="px-3 py-6 text-center text-slate-500">No users found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
