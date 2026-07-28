<script setup>
import { Eye, Pencil, Printer, RotateCcw, Search } from "@lucide/vue";
import { ref, computed } from "vue";

import Table from "../../../../components/ui/table/Table.vue";
import TableHeader from "../../../../components/ui/table/TableHeader.vue";
import TableHead from "../../../../components/ui/table/TableHead.vue";
import TableBody from "../../../../components/ui/table/TableBody.vue";
import TableRow from "../../../../components/ui/table/TableRow.vue";
import TableCell from "../../../../components/ui/table/TableCell.vue";

const props = defineProps({
  students: {
    type: Array,
    default: () => [],
  },
});
const emit = defineEmits(["record-deposit"]);

const search = ref("");
function refresh() {
  search.value = "";
}

const filtered = computed(() => {
  if (!search.value) return props.students;
  const q = search.value.toLowerCase();
  return props.students.filter(
    (s) =>
      s.name?.toLowerCase().includes(q) ||
      String(s.roster_no).includes(q) ||
      String(s.id).includes(q) ||
      s.phone?.toLowerCase().includes(q)
  );
});

function statusBadge(status) {
  switch (status) {
    case "Paid":
      return "bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400";
    case "Partial":
      return "bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400";
    case "Unpaid":
      return "bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400";
    default:
      return "bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-300";
  }
}
</script>

<template>
  <div
    class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden dark:border-gray-800 dark:bg-gray-900"
  >
    <!-- Header -->
    <div
      class="flex flex-col gap-3 border-b border-slate-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800"
    >
      <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">
        {{ $t('Student Deposits') }}
        <span class="ml-1.5 text-sm font-normal text-slate-400 dark:text-gray-500">
          ({{ filtered.length }})
        </span>
      </h3>

      <!-- Search -->
      <div class="flex flex-col sm:flex-row gap-3 items-center">
        <!-- Search Input -->
        <div class="relative flex-1 w-full">
          <input
            v-model="search"
            type="text"
            :placeholder="$t('Search student...')"
            class="w-full rounded-xl border border-slate-300 px-4 py-2 pl-10 text-sm outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/20"
          />

          <Search
            class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 dark:text-gray-500"
          />
        </div>

        <!-- Reset Button -->
        <button
          @click="refresh"
          class="inline-flex items-center justify-center gap-2 rounded-xl  bg-blue-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500"
        >
          <RotateCcw class="w-4 h-4" />
          {{ $t('Reset') }}
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>{{ $t('Student ID') }}</TableHead>
            <TableHead>{{ $t('Student Name') }}</TableHead>
            <TableHead>{{ $t('Gender') }}</TableHead>
            <TableHead>{{ $t('Phone Number') }}</TableHead>
            <TableHead>{{ $t('Deposit Amount') }}</TableHead>
            <TableHead>{{ $t('Payment Date') }}</TableHead>
            <TableHead>{{ $t('Payment Status') }}</TableHead>
            <TableHead>{{ $t('Remaining Balance') }}</TableHead>
            <TableHead class="text-center">{{ $t('Action') }}</TableHead>
          </TableRow>
        </TableHeader>

        <TableBody>
          <TableRow v-for="student in filtered" :key="student.id">
            <TableCell class="font-medium text-slate-900 dark:text-gray-100">
              #{{ student.id }}
            </TableCell>
            <TableCell class="whitespace-nowrap font-medium text-slate-900 dark:text-gray-100">
              {{ student.name }}
            </TableCell>
            <TableCell>{{ student.gender }}</TableCell>
            <TableCell class="whitespace-nowrap">
              {{ student.phone ?? "—" }}
            </TableCell>
            <TableCell class="whitespace-nowrap font-medium">
              ${{ Number(student.amount_paid ?? student.deposit_amount ?? 0).toFixed(2) }}
            </TableCell>
            <TableCell class="whitespace-nowrap">
              {{ student.payment_date ?? "—" }}
            </TableCell>
            <TableCell>
              <span
                class="inline-block whitespace-nowrap rounded-full px-3 py-0.5 text-xs font-semibold"
                :class="statusBadge(student.payment_status)"
              >
                {{ student.payment_status }}
              </span>
            </TableCell>
            <TableCell class="whitespace-nowrap font-medium">
              ${{ Number(student.remaining_balance ?? 0).toFixed(2) }}
            </TableCell>
            <TableCell>
              <div class="flex justify-center gap-1.5">
                <button
                  class="rounded-lg bg-blue-50 p-2 text-blue-600 transition-colors hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20"
                  :title="$t('View Payment')"
                >
                  <Eye class="h-4 w-4" />
                </button>
                <button
                  class="rounded-lg bg-amber-50 p-2 text-amber-600 transition-colors hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20"
                  :title="$t('Edit Deposit')"
                >
                  <Pencil class="h-4 w-4" />
                </button>
                <button
                  class="rounded-lg bg-slate-50 p-2 text-slate-600 transition-colors hover:bg-slate-100 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                  :title="$t('Print Receipt')"
                >
                  <Printer class="h-4 w-4" />
                </button>
              </div>
            </TableCell>
          </TableRow>

          <!-- Empty state -->
          <TableRow v-if="filtered.length === 0">
            <TableCell colspan="9">
              <div class="py-12 text-center text-slate-400 dark:text-gray-500">
                <p class="text-base font-medium">{{ $t('No students found') }}</p>
                <p class="mt-1 text-sm">
                  {{
                    search
                      ? "Try a different search term."
                      : "No students are enrolled in this class yet."
                  }}
                </p>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>
  </div>
</template>
