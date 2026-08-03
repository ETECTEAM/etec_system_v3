<script setup>
import { router } from "@inertiajs/vue3";
import { Newspaper } from "@lucide/vue";

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({}),
  },
});

function clearFilters() {
  router.get(
    "/news",
    {
      per_page: props.filters?.per_page ?? 12,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    },
  );
}
</script>

<template>
  <div
    class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-16 text-center"
  >
    <div
      class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-[#1e5aa8]/10"
    >
      <Newspaper class="h-10 w-10 text-[#1e5aa8]" />
    </div>

    <h3 class="mt-6 text-2xl font-black text-slate-950">
      No news found
    </h3>

    <p class="mx-auto mt-3 max-w-md leading-7 text-slate-500">
      We could not find any news matching your search. Try another keyword or
      clear the selected filters.
    </p>

    <button
      type="button"
      class="mt-6 inline-flex rounded-full bg-[#1e5aa8] px-6 py-3 text-sm font-black text-white transition hover:bg-[#174981]"
      @click="clearFilters"
    >
      View All News
    </button>
  </div>
</template>