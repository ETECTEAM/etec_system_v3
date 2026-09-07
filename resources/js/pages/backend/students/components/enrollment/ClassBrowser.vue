<script setup>
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { Search, LayoutGrid, Table2, X } from "@lucide/vue";
import ClassCrad from "@/components/ui/card/ClassCrad.vue";
import EmptyState from "@/components/ui/empty-state/EmptyState.vue";
import ClassTable from "../ClassTable.vue";
import RegisterStudentModal from "../RegisterStudentModal.vue";

const props = defineProps({
  classes: {
    type: Object,
    default: () => ({ data: [], links: [] }),
  },
  filters: {
    type: Object,
    default: () => ({ search: "" }),
  },
});

const CLASS_VIEW_KEY = "enroll.classBrowser.view";

function storedView() {
  if (typeof window === "undefined") return "card";
  const stored = window.localStorage.getItem(CLASS_VIEW_KEY);
  return ["card", "table"].includes(stored) ? stored : "card";
}

const classView = ref(storedView());
watch(classView, (value) => {
  if (typeof window !== "undefined") window.localStorage.setItem(CLASS_VIEW_KEY, value);
});

const rows = computed(() => props.classes?.data ?? []);
const search = ref(props.filters?.search ?? "");

let searchTimer = null;

function reload() {
  router.get(
    "/dashboard/enroll",
    { search: search.value || null, tab: "register-class" },
    { preserveState: true, replace: true, preserveScroll: true, only: ["classes", "filters", "depositSummary"] },
  );
}

watch(search, () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(reload, 350);
});

onBeforeUnmount(() => clearTimeout(searchTimer));

function clearSearch() {
  clearTimeout(searchTimer);
  search.value = "";
  reload();
}

function goCreateClass() {
  router.visit("/dashboard/enroll/create");
}

// Table-view "Register Student": the picked class row, or null when closed.
const registerTarget = ref(null);
</script>

<template>
  <div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="relative w-full sm:w-80">
        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500" />
        <input
          v-model="search"
          type="text"
          :placeholder="$t('Search class, course, instructor, room')"
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

      <div class="inline-flex h-9 shrink-0 overflow-hidden rounded-lg border border-slate-300 bg-white p-0.5 dark:border-gray-600 dark:bg-gray-800">
        <button
          type="button"
          @click="classView = 'card'"
          :class="[
            'inline-flex items-center justify-center gap-1.5 rounded-md px-3 text-xs font-semibold transition',
            classView === 'card' ? 'bg-blue-900 text-white shadow-sm dark:bg-blue-600' : 'text-slate-600 hover:bg-slate-100 dark:text-gray-300 dark:hover:bg-gray-700',
          ]"
        >
          <LayoutGrid class="h-3.5 w-3.5" />
          {{ $t('Card') }}
        </button>
        <button
          type="button"
          @click="classView = 'table'"
          :class="[
            'inline-flex items-center justify-center gap-1.5 rounded-md px-3 text-xs font-semibold transition',
            classView === 'table' ? 'bg-blue-900 text-white shadow-sm dark:bg-blue-600' : 'text-slate-600 hover:bg-slate-100 dark:text-gray-300 dark:hover:bg-gray-700',
          ]"
        >
          <Table2 class="h-3.5 w-3.5" />
          {{ $t('Table') }}
        </button>
      </div>
    </div>

    <div v-if="rows.length > 0 && classView === 'card'" class="grid grid-cols-1 gap-5 md:grid-cols-3 xl:grid-cols-4">
      <ClassCrad v-for="item in rows" :key="item.id" :class-data="item" :count="item.notifications" />
    </div>

    <div v-else-if="rows.length > 0" class="w-full overflow-x-auto">
      <ClassTable :items="rows" @register-student="registerTarget = $event" />
    </div>

    <EmptyState v-else @action="goCreateClass" />

    <div v-if="classes?.links?.length > 3" class="mt-2 flex flex-wrap justify-center gap-2">
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

    <RegisterStudentModal
      :show="!!registerTarget"
      :class-id="registerTarget?.id"
      :class-title="registerTarget?.title"
      :seats-left="registerTarget ? Math.max(0, (registerTarget.capacity ?? 0) - (registerTarget.students ?? 0)) : null"
      @close="registerTarget = null"
    />
  </div>
</template>
