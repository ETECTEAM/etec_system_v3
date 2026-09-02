<script setup>
import { Head, router } from "@inertiajs/vue3";
import { CheckCircle2, XCircle } from "@lucide/vue";

import DashboardLayout from "../../../layouts/DashboardLayout.vue";

defineProps({
  requests: {
    type: Array,
    default: () => [],
  },
});

const statusClasses = {
  pending: "bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300",
  approved: "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300",
  rejected: "bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300",
  completed: "bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300",
};

function reviewRequest(request, status) {
  router.put(`/dashboard/pre-attendance-requests/${request.id}`, { status }, {
    preserveScroll: true,
  });
}
</script>

<template>
  <Head :title="$t('Pre-Att Request')" />

  <DashboardLayout>
    <section class="space-y-6">
      <div class="flex flex-col gap-2">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-600 dark:text-amber-300">{{ $t("Attendance Recovery") }}</p>
        <h1 class="text-2xl font-black text-slate-950 dark:text-gray-100 sm:text-3xl">{{ $t("Pre-attendance requests") }}</h1>
      </div>

      <div class="overflow-hidden rounded-lg border border-slate-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-slate-200 px-5 py-4 dark:border-gray-800">
          <h2 class="text-lg font-black text-slate-950 dark:text-gray-100">{{ $t("Requests") }}</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-[1080px] w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs font-black uppercase tracking-[0.12em] text-slate-500 dark:bg-gray-950 dark:text-gray-400">
              <tr>
                <th class="px-5 py-4">{{ $t("Class") }}</th>
                <th class="px-5 py-4">{{ $t("Instructor") }}</th>
                <th class="px-5 py-4">{{ $t("Date") }}</th>
                <th class="px-5 py-4">{{ $t("Reason") }}</th>
                <th class="px-5 py-4">{{ $t("Status") }}</th>
                <th class="px-5 py-4">{{ $t("Requested") }}</th>
                <th class="px-5 py-4">{{ $t("Reviewed") }}</th>
                <th class="px-5 py-4 text-right">{{ $t("Action") }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="request in requests" :key="request.id" class="border-t border-slate-100 dark:border-gray-800">
                <td class="px-5 py-4 font-bold text-slate-900 dark:text-gray-100">{{ request.class_title }}</td>
                <td class="px-5 py-4 text-slate-700 dark:text-gray-300">{{ request.instructor }}</td>
                <td class="px-5 py-4 text-slate-700 dark:text-gray-300">{{ request.session_date }}</td>
                <td class="max-w-xs px-5 py-4 text-slate-700 dark:text-gray-300">{{ request.note ?? '-' }}</td>
                <td class="px-5 py-4">
                  <span class="rounded-full px-2.5 py-1 text-xs font-black" :class="statusClasses[request.status] ?? statusClasses.pending">
                    {{ request.status_label }}
                  </span>
                </td>
                <td class="px-5 py-4 text-slate-600 dark:text-gray-400">{{ request.requested_at }}</td>
                <td class="px-5 py-4 text-slate-600 dark:text-gray-400">{{ request.reviewed_by ?? '-' }}</td>
                <td class="px-5 py-4">
                  <div v-if="request.status === 'pending' || request.status === 'approved'" class="flex justify-end gap-2">
                    <button type="button" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-emerald-600 px-3 text-xs font-bold text-white hover:bg-emerald-500" @click="reviewRequest(request, 'approved')">
                      <CheckCircle2 class="h-4 w-4" />
                      {{ $t("Approve") }}
                    </button>
                    <button type="button" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-rose-600 px-3 text-xs font-bold text-white hover:bg-rose-500" @click="reviewRequest(request, 'rejected')">
                      <XCircle class="h-4 w-4" />
                      {{ $t("Reject") }}
                    </button>
                  </div>
                  <span v-else class="block text-right text-xs font-bold text-slate-400">{{ request.completed_at ?? '-' }}</span>
                </td>
              </tr>
              <tr v-if="!requests.length">
                <td colspan="8" class="px-5 py-12 text-center text-sm font-bold text-slate-400">{{ $t("No pre-attendance requests yet") }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
