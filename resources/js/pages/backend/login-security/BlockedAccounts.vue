<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'

defineProps({
  blocked: Array,
})

const form = useForm({ login: '' })

function unblock(login) {
  form.login = login
  form.post('/dashboard/login-security/blocked-accounts/unblock', { preserveScroll: true })
}

function formatBannedUntil(value) {
  return new Date(value).toLocaleString(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
  })
}

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Blocked Accounts', current: true },
]
</script>

<template>
  <Head title="Blocked Accounts" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero
        eyebrow="Security"
        title="Blocked Accounts"
        description="Accounts currently locked out by repeated failed logins. Unblocking lets them try again immediately, but doesn't erase their offense history."
      />

      <div v-if="$page.props.flash?.success" class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400">
        {{ $page.props.flash.success }}
      </div>

      <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-gray-800 dark:bg-gray-900">
        <p v-if="!blocked.length" class="py-8 text-center text-sm text-slate-500 dark:text-gray-400">
          No accounts are currently blocked.
        </p>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead>
              <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase dark:border-gray-800 dark:text-gray-400">
                <th class="py-3 pr-4">Login</th>
                <th class="py-3 pr-4">Offense</th>
                <th class="py-3 pr-4">Status</th>
                <th class="py-3 pr-4">Blocked Until</th>
                <th class="py-3 pr-4"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="account in blocked" :key="account.login" class="border-b border-slate-100 last:border-0 dark:border-gray-800">
                <td class="py-3 pr-4 font-medium text-slate-800 dark:text-gray-200">{{ account.login }}</td>
                <td class="py-3 pr-4 text-slate-600 dark:text-gray-400">{{ account.offense_number }}</td>
                <td class="py-3 pr-4">
                  <span
                    class="rounded-full px-2.5 py-1 text-xs font-semibold"
                    :class="account.is_hard_block
                      ? 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400'
                      : 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400'"
                  >
                    {{ account.is_hard_block ? 'Hard blocked' : 'Timed lockout' }}
                  </span>
                </td>
                <td class="py-3 pr-4 text-slate-600 dark:text-gray-400">{{ formatBannedUntil(account.banned_until) }}</td>
                <td class="py-3 pr-4 text-right">
                  <button
                    type="button"
                    :disabled="form.processing"
                    class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20"
                    @click="unblock(account.login)"
                  >
                    Unblock
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
