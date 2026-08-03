<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
  items: {
    type: Object,
    required: true,
  },
  summary: {
    type: String,
    required: true,
  },
});

const links = computed(() => props.items?.links ?? []);
const prevLink = computed(() => links.value.find((link) => link.label.includes("Previous")) ?? null);
const nextLink = computed(() => links.value.find((link) => link.label.includes("Next")) ?? null);
const pageLinks = computed(() => links.value.filter((link) => !link.label.includes("Previous") && !link.label.includes("Next")));
</script>

<template>
  <div class="flex flex-col gap-3 border-t border-slate-800 bg-slate-900 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
    <p class="text-sm text-slate-400">{{ summary }}</p>

    <div class="flex items-center gap-2 text-sm">
      <component
        :is="prevLink?.url ? Link : 'span'"
        :href="prevLink?.url || undefined"
        class="inline-flex min-w-14 items-center justify-center rounded-xl border px-4 py-2 font-medium transition"
        :class="prevLink?.url
          ? 'border-slate-700 bg-slate-800/80 text-slate-300 hover:border-slate-500 hover:bg-slate-800'
          : 'cursor-not-allowed border-slate-700 bg-slate-800/40 text-slate-500 opacity-70'"
      >
        {{ $t("Prev") }}
      </component>

      <component
        :is="link.url ? Link : 'span'"
        v-for="link in pageLinks"
        :key="link.label"
        :href="link.url || undefined"
        class="inline-flex h-9 min-w-9 items-center justify-center rounded-xl border px-3 font-semibold transition"
        :class="link.active
          ? 'border-blue-500 bg-blue-600 text-white shadow-[0_0_0_1px_rgba(59,130,246,0.25)]'
          : link.url
            ? 'border-slate-700 bg-slate-800/80 text-slate-300 hover:border-slate-500 hover:bg-slate-800'
            : 'cursor-default border-slate-700 bg-slate-800/40 text-slate-500 opacity-70'"
      >
        {{ link.label }}
      </component>

      <component
        :is="nextLink?.url ? Link : 'span'"
        :href="nextLink?.url || undefined"
        class="inline-flex min-w-14 items-center justify-center rounded-xl border px-4 py-2 font-medium transition"
        :class="nextLink?.url
          ? 'border-slate-700 bg-slate-800/80 text-slate-300 hover:border-slate-500 hover:bg-slate-800'
          : 'cursor-not-allowed border-slate-700 bg-slate-800/40 text-slate-500 opacity-70'"
      >
        {{ $t("Next") }}
      </component>
    </div>
  </div>
</template>
