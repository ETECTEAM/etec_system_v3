<script setup>
import { onBeforeUnmount, watch } from "vue";
import { Search, LayoutGrid, Table2, X } from "@lucide/vue";
import DepositSummaryCard from "../DepositSummaryCard.vue";
import RegistrationTableView from "./RegistrationTableView.vue";
import RegistrationCardView from "./RegistrationCardView.vue";
import { useEnrollmentRegistrations } from "@/composables/useEnrollmentRegistrations";

defineProps({
  depositSummary: {
    type: Object,
    default: null,
  },
  view: {
    type: String,
    default: "table",
  },
});

const emit = defineEmits(["update:view"]);

const { search, fetchRegistrations } = useEnrollmentRegistrations();

let searchTimer = null;
watch(search, () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => fetchRegistrations(1), 350);
});
onBeforeUnmount(() => clearTimeout(searchTimer));

function clearSearch() {
  clearTimeout(searchTimer);
  search.value = "";
  fetchRegistrations(1);
}
</script>

<template>
  <div class="space-y-6">
    <DepositSummaryCard :deposit-summary="depositSummary" />

    <!-- Toolbar -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="relative w-full sm:w-80">
        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500" />
        <input
          v-model="search"
          type="text"
          :placeholder="$t('Search student...')"
          class="h-10 w-full rounded-xl border border-slate-300 bg-white py-2 pl-10 pr-10 text-sm font-medium text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
        />
        <button
          v-if="search"
          type="button"
          :aria-label="$t('Clear search')"
          class="absolute right-2 top-1/2 inline-flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-200"
          @click="clearSearch"
        >
          <X class="h-4 w-4" />
        </button>
      </div>

      <!-- Card / Table view switch — deliberately smaller than the main tabs -->
      <div class="inline-flex h-9 shrink-0 overflow-hidden rounded-lg border border-slate-300 bg-white p-0.5 dark:border-gray-600 dark:bg-gray-800">
        <button
          type="button"
          @click="emit('update:view', 'table')"
          :class="[
            'inline-flex items-center justify-center gap-1.5 rounded-md px-3 text-xs font-semibold transition',
            view === 'table'
              ? 'bg-blue-900 text-white shadow-sm dark:bg-blue-600'
              : 'text-slate-600 hover:bg-slate-100 dark:text-gray-300 dark:hover:bg-gray-700',
          ]"
        >
          <Table2 class="h-3.5 w-3.5" />
          {{ $t('Table') }}
        </button>
        <button
          type="button"
          @click="emit('update:view', 'card')"
          :class="[
            'inline-flex items-center justify-center gap-1.5 rounded-md px-3 text-xs font-semibold transition',
            view === 'card'
              ? 'bg-blue-900 text-white shadow-sm dark:bg-blue-600'
              : 'text-slate-600 hover:bg-slate-100 dark:text-gray-300 dark:hover:bg-gray-700',
          ]"
        >
          <LayoutGrid class="h-3.5 w-3.5" />
          {{ $t('Card') }}
        </button>
      </div>
    </div>

    <RegistrationTableView v-if="view === 'table'" />
    <RegistrationCardView v-else />
  </div>
</template>
