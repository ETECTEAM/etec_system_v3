<script setup>
import { computed } from "vue";
import { router } from "@inertiajs/vue3";
import ClassCrad from "@/components/ui/card/ClassCrad.vue";
import EmptyState from "@/components/ui/empty-state/EmptyState.vue";

const props = defineProps({
  // Paginated class list from GetClassList::handle — { data, links }.
  classes: {
    type: Object,
    default: () => ({ data: [], links: [] }),
  },
  // Free-text filter shared with the toolbar (client-side, current page only).
  search: {
    type: String,
    default: "",
  },
});

const rows = computed(() => {
  const q = props.search.trim().toLowerCase();
  const data = props.classes?.data ?? [];
  if (!q) return data;

  return data.filter((item) =>
    [item.title, item.course, item.teacher, item.room, item.floor, item.building]
      .filter(Boolean)
      .some((field) => String(field).toLowerCase().includes(q)),
  );
});

function goCreateClass() {
  router.visit("/dashboard/enroll/create");
}
</script>

<template>
  <div class="w-full">
    <div v-if="rows.length > 0" class="grid grid-cols-1 gap-5 md:grid-cols-3 xl:grid-cols-4">
      <ClassCrad
        v-for="item in rows"
        :key="item.id"
        :class-data="item"
        :count="item.notifications"
      />
    </div>

    <EmptyState v-else @action="goCreateClass" />

    <div v-if="classes?.links?.length > 3" class="mt-6 flex flex-wrap justify-center gap-2">
      <button
        v-for="link in classes.links"
        :key="link.label"
        type="button"
        :disabled="!link.url"
        @click="link.url && router.visit(link.url, { preserveState: true, preserveScroll: true })"
        v-html="link.label"
        :class="[
          'rounded-lg border px-3 py-2 text-sm',
          link.active ? 'border-blue-900 bg-blue-900 text-white' : 'border-slate-300 bg-white text-slate-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300',
          !link.url ? 'cursor-not-allowed opacity-50' : 'hover:bg-slate-100 dark:hover:bg-gray-800',
        ]"
      ></button>
    </div>
  </div>
</template>
