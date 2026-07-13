<script setup>
import {
  Users,
  CheckCircle2,
  XCircle,
  DollarSign,
} from "@lucide/vue";

defineProps({
  depositSummary: {
    type: Object,
    default: null,
  },
});
</script>

<template>
  <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
    <!-- Total Students -->
    <div
      class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-gray-800 dark:bg-gray-900"
    >
      <div class="flex items-center justify-between">
        <div>
          <p class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500">
            Total Students
          </p>
          <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-gray-100">
            {{ depositSummary?.total_students ?? 0 }}
          </p>
        </div>
        <div
          class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-500/10"
        >
          <Users class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
        </div>
      </div>
    </div>

    <!-- Paid Students -->
    <div
      class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-gray-800 dark:bg-gray-900"
    >
      <div class="flex items-center justify-between">
        <div>
          <p class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500">
            Paid Students
          </p>
          <p class="mt-1 text-2xl font-bold text-emerald-700 dark:text-emerald-400">
            {{ depositSummary?.paid_students ?? 0 }}
          </p>
        </div>
        <div
          class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-500/10"
        >
          <CheckCircle2 class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
        </div>
      </div>
    </div>

    <!-- Unpaid Students (includes Partial + Unpaid) -->
    <div
      class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-gray-800 dark:bg-gray-900"
    >
      <div class="flex items-center justify-between">
        <div>
          <p class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500">
            Unpaid Students
          </p>
          <p class="mt-1 text-2xl font-bold text-red-700 dark:text-red-400">
            {{
              (depositSummary?.unpaid_students ?? 0) +
              (depositSummary?.partial_students ?? 0)
            }}
          </p>
        </div>
        <div
          class="flex h-11 w-11 items-center justify-center rounded-xl bg-red-100 dark:bg-red-500/10"
        >
          <XCircle class="h-5 w-5 text-red-600 dark:text-red-400" />
        </div>
      </div>
    </div>

    <!-- Total Deposit Collected -->
    <div
      class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-gray-800 dark:bg-gray-900"
    >
      <div class="flex items-center justify-between">
        <div>
          <p class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500">
            Total Collected
          </p>
          <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-gray-100">
            ${{ depositSummary?.total_deposit_collected?.toFixed(2) ?? "0.00" }}
          </p>
        </div>
        <div
          class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-500/10"
        >
          <DollarSign class="h-5 w-5 text-amber-600 dark:text-amber-400" />
        </div>
      </div>
    </div>
  </div>
</template>
