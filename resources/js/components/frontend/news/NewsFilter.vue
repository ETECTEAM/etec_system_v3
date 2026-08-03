<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { Search, Star } from "@lucide/vue";

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const search = ref(props.filters.search ?? "");

const featured = ref(
  ["1", 1, true, "true"].includes(props.filters.featured)
    ? "1"
    : "",
);

function submit() {
  router.get(
    "/news",
    {
      search: search.value.trim() || undefined,
      featured: featured.value || undefined,
      per_page: props.filters.per_page ?? 12,
      page: 1,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    },
  );
}

function clear() {
  search.value = "";
  featured.value = "";

  router.get(
    "/news",
    {
      per_page: props.filters.per_page ?? 12,
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
  <section class="relative z-10 -mt-7">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <form
        class="rounded-3xl border border-slate-200 bg-white p-4 shadow-xl sm:p-5"
        @submit.prevent="submit"
      >
        <div class="grid gap-3 md:grid-cols-[1fr_auto_auto]">
          <label class="relative block">
            <Search
              class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"
            />

            <input
              v-model="search"
              type="search"
              maxlength="120"
              placeholder="Search news..."
              class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-4 outline-none focus:border-[#1e5aa8]"
            />
          </label>

          <label
            class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4"
          >
            <input
              v-model="featured"
              type="checkbox"
              true-value="1"
              false-value=""
            />

            <Star class="h-4 w-4 text-[#f4a261]" />

            <span class="whitespace-nowrap text-sm font-bold">
              Featured only
            </span>
          </label>

          <button
            type="submit"
            class="rounded-2xl bg-[#1e5aa8] px-7 py-3 text-sm font-black text-white hover:bg-[#174981]"
          >
            Search
          </button>
        </div>

        <button
          v-if="search || featured"
          type="button"
          class="mt-3 text-sm font-bold text-[#1e5aa8]"
          @click="clear"
        >
          Clear filters
        </button>
      </form>
    </div>
  </section>
</template>