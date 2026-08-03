<script setup>
import { computed } from "vue";
import {
  ChevronLeft,
  ChevronRight,
} from "@lucide/vue";

const props = defineProps({
  meta: {
    type: Object,
    default: () => ({
      current_page: 1,
      last_page: 1,
    }),
  },

  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["change-page"]);

const currentPage = computed(
  () => Number(props.meta?.current_page ?? 1),
);

const lastPage = computed(
  () => Number(props.meta?.last_page ?? 1),
);

const pages = computed(() => {
  const result = [];
  const start = Math.max(1, currentPage.value - 2);
  const end = Math.min(
    lastPage.value,
    currentPage.value + 2,
  );

  for (let page = start; page <= end; page++) {
    result.push(page);
  }

  return result;
});

function goToPage(pageNumber) {
  if (
    props.loading ||
    pageNumber < 1 ||
    pageNumber > lastPage.value ||
    pageNumber === currentPage.value
  ) {
    return;
  }

  emit("change-page", pageNumber);
}
</script>

<template>
  <nav
    v-if="lastPage > 1"
    class="mb-16 flex flex-wrap items-center justify-center gap-2"
    aria-label="News pagination"
  >
    <button
      type="button"
      :disabled="currentPage <= 1 || loading"
      class="inline-flex h-11 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 transition hover:border-[#1e5aa8] hover:text-[#1e5aa8] disabled:cursor-not-allowed disabled:opacity-40"
      @click="goToPage(currentPage - 1)"
    >
      <ChevronLeft class="h-4 w-4" />
      Previous
    </button>

    <button
      v-for="pageNumber in pages"
      :key="pageNumber"
      type="button"
      :disabled="loading"
      class="h-11 min-w-11 rounded-xl border px-3 text-sm font-black transition"
      :class="
        pageNumber === currentPage
          ? 'border-[#1e5aa8] bg-[#1e5aa8] text-white'
          : 'border-slate-200 bg-white text-slate-700 hover:border-[#1e5aa8] hover:text-[#1e5aa8]'
      "
      @click="goToPage(pageNumber)"
    >
      {{ pageNumber }}
    </button>

    <button
      type="button"
      :disabled="currentPage >= lastPage || loading"
      class="inline-flex h-11 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 transition hover:border-[#1e5aa8] hover:text-[#1e5aa8] disabled:cursor-not-allowed disabled:opacity-40"
      @click="goToPage(currentPage + 1)"
    >
      Next
      <ChevronRight class="h-4 w-4" />
    </button>
  </nav>
</template>