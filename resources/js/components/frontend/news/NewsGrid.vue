<script setup>
import NewsCard from "./NewsCard.vue";
import NewsEmptyState from "./NewsEmptyState.vue";

defineProps({
  newsItems: {
    type: Array,
    default: () => [],
  },

  total: {
    type: Number,
    default: 0,
  },

  filters: {
    type: Object,
    default: () => ({}),
  },
});
</script>

<template>
  <section id="latest-news" class="scroll-mt-24 py-14 sm:py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="mb-8">
        <p
          class="text-sm font-black uppercase tracking-[0.2em] text-[#f4a261]"
        >
          Latest Updates
        </p>

        <h2 class="mt-2 text-3xl font-black text-slate-950 sm:text-4xl">
          News & Announcements
        </h2>

        <p class="mt-2 text-sm text-slate-500">
          Showing {{ newsItems.length }} of {{ total }} articles
        </p>
      </div>

      <div
        v-if="newsItems.length"
        class="grid gap-7 sm:grid-cols-2 lg:grid-cols-3"
      >
        <NewsCard
          v-for="item in newsItems"
          :key="item.id"
          :item="item"
        />
      </div>

      <NewsEmptyState
        v-else
        :filters="filters"
      />
    </div>
  </section>
</template>