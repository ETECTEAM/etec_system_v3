<script setup>
import { X } from "@lucide/vue";
import { useEnrollmentRegistrations } from "@/composables/useEnrollmentRegistrations";

const {
  confirmPaidModalOpen,
  pendingRow,
  paymentAmountInput,
  paymentAmountError,
  printingId,
  remainingBalance,
  cancelMarkPaid,
  confirmMarkPaidAndPrint,
} = useEnrollmentRegistrations();
</script>

<template>
  <div v-if="confirmPaidModalOpen && pendingRow" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
      <div class="flex items-start justify-between gap-4">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Record Payment & Print Receipt') }}</h3>
        <button type="button" :aria-label="$t('Close')" class="-mr-2 -mt-1 shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-gray-800 dark:hover:text-gray-200" @click="cancelMarkPaid">
          <X class="h-5 w-5" />
        </button>
      </div>

      <div class="mt-4 space-y-2 rounded-xl bg-slate-50 p-4 text-sm dark:bg-gray-800">
        <div class="flex justify-between">
          <span class="text-slate-500 dark:text-gray-400">{{ $t('Name') }}</span>
          <span class="font-semibold text-slate-800 dark:text-gray-100">{{ pendingRow.name }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-500 dark:text-gray-400">{{ $t('Gender') }}</span>
          <span class="font-semibold text-slate-800 dark:text-gray-100">{{ pendingRow.gender }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-500 dark:text-gray-400">Phone</span>
          <span class="font-semibold text-slate-800 dark:text-gray-100">{{ pendingRow.phone }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-500 dark:text-gray-400">{{ $t('Class') }}</span>
          <span class="font-semibold text-slate-800 dark:text-gray-100">{{ pendingRow.class_title }}</span>
        </div>
        <div class="flex justify-between border-t border-slate-200 pt-2 dark:border-gray-700">
          <span class="text-slate-500 dark:text-gray-400">{{ $t('Total Due') }}</span>
          <span class="font-semibold text-slate-800 dark:text-gray-100">${{ (Number(pendingRow.fee_amount) + Number(pendingRow.document_fee_amount)).toFixed(2) }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-500 dark:text-gray-400">{{ $t('Already Paid') }}</span>
          <span class="font-semibold text-slate-800 dark:text-gray-100">${{ Number(pendingRow.amount_paid).toFixed(2) }}</span>
        </div>
      </div>

      <label class="mt-4 grid gap-2 text-sm font-semibold text-slate-700 dark:text-gray-300">
        {{ $t('Amount to Pay Now') }}
        <input
          v-model="paymentAmountInput"
          type="number"
          min="0.01"
          step="0.01"
          :max="remainingBalance(pendingRow)"
          class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
        />
      </label>
      <p class="mt-1 text-xs text-slate-400 dark:text-gray-500">
        {{ $t('Remaining balance') }}: ${{ remainingBalance(pendingRow).toFixed(2) }}
      </p>
      <p v-if="paymentAmountError" class="mt-1 text-xs font-semibold text-red-600">{{ paymentAmountError }}</p>

      <p class="mt-4 text-sm text-slate-500 dark:text-gray-400">
        {{ $t('This records the payment above and opens the print dialog.') }}
      </p>

      <div class="mt-6 flex justify-end gap-3">
        <button type="button" @click="cancelMarkPaid" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
          {{ $t('Cancel') }}
        </button>
        <button
          type="button"
          @click="confirmMarkPaidAndPrint"
          :disabled="printingId === pendingRow.enrollment_id || !!paymentAmountError"
          class="rounded-xl bg-blue-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500"
        >
          {{ printingId === pendingRow.enrollment_id ? $t('Saving...') : $t('Record Payment & Print') }}
        </button>
      </div>
    </div>
  </div>
</template>
