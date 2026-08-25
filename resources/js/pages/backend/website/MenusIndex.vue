<script setup>
import { computed, ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { ChevronRight, Pencil, Plus, Search, Trash2 } from "@lucide/vue";
import DashboardLayout from "@/layouts/DashboardLayout.vue";
import Breadcrumbs from "@/components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "@/components/ui/page-hero/PageHero.vue";
import DirectoryPagination from "@/pages/backend/website/components/DirectoryPagination.vue";
import { useI18n } from "@/i18n";

const { t } = useI18n();

const props = defineProps({
  menus: Object,
  filters: Object,
});

const search = ref(props.filters?.search ?? "");
const orderedMenus = ref([...(props.menus?.data ?? [])]);
const draggedIndex = ref(null);
const dragOverIndex = ref(null);
const isSavingOrder = ref(false);
const expandedMenus = ref({});
let timeout = null;

const breadcrumbs = [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Website Management", current: true },
  { label: "Menu Management", current: true },
];

watch(
  () => props.menus,
  (menus) => {
    orderedMenus.value = [...(menus?.data ?? [])];
    expandedMenus.value = Object.fromEntries(
      orderedMenus.value
        .filter((menu) => !menu.parent_id && (menu.children_count ?? 0) > 0)
        .map((menu) => [menu.id, expandedMenus.value[menu.id] ?? false]),
    );
  },
  { immediate: true },
);

const menuRows = computed(() => {
  const childMenus = new Map();
  const rows = [];
  let basePosition = 0;

  orderedMenus.value.forEach((menu, index) => {
    if (!menu.parent_id) return;

    const children = childMenus.get(menu.parent_id) ?? [];
    children.push({ menu, index, depth: 1 });
    childMenus.set(menu.parent_id, children);
  });

  orderedMenus.value.forEach((menu, index) => {
    if (menu.parent_id) return;

    basePosition += 1;
    rows.push({ menu, index, depth: 0, displayPosition: basePosition });

    if (expandedMenus.value[menu.id]) {
      rows.push(
        ...(childMenus.get(menu.id) ?? []).map((row, childIndex) => ({
          ...row,
          displayPosition: childIndex + 1,
        })),
      );
    }
  });

  orderedMenus.value.forEach((menu, index) => {
    if (menu.parent_id && !orderedMenus.value.some((parent) => parent.id === menu.parent_id)) {
      rows.push({ menu, index, depth: 1, displayPosition: index + 1 });
    }
  });

  return rows;
});

watch(search, () => {
  clearTimeout(timeout);
  timeout = setTimeout(() => {
    router.get("/dashboard/website/menus", { search: search.value, page: 1 }, { preserveState: true, replace: true });
  }, 350);
});

function moveMenu(fromIndex, toIndex) {
  if (fromIndex === toIndex || fromIndex === null || toIndex === null) return false;
  if (toIndex < 0 || toIndex >= orderedMenus.value.length) return false;
  const copy = [...orderedMenus.value];
  const [item] = copy.splice(fromIndex, 1);
  copy.splice(toIndex, 0, item);
  orderedMenus.value = copy;
  return true;
}

function startDrag(index) {
  draggedIndex.value = index;
}

function enterDrag(index) {
  dragOverIndex.value = index;
}

function dropMenu(index) {
  const changed = moveMenu(draggedIndex.value, index);
  draggedIndex.value = null;
  dragOverIndex.value = null;

  if (changed) {
    saveOrder();
  }
}

function endDrag() {
  draggedIndex.value = null;
  dragOverIndex.value = null;
}

function saveOrder() {
  isSavingOrder.value = true;

  router.put("/dashboard/website/menus/reorder", {
    menus: orderedMenus.value.map((menu) => ({
      id: menu.id,
      position: orderedMenus.value.filter((item) => item.parent_id === menu.parent_id).findIndex((item) => item.id === menu.id) + 1,
    })),
  }, {
    preserveScroll: true,
    onFinish: () => {
      isSavingOrder.value = false;
    },
  });
}

function toggleMenu(menu) {
  router.patch(`/dashboard/website/menus/${menu.id}/status`, { is_active: !menu.is_active }, { preserveScroll: true });
}

function toggleExpand(menu) {
  if ((menu.children_count ?? 0) === 0) return;
  expandedMenus.value[menu.id] = !expandedMenus.value[menu.id];
}

function deleteMenu(menu) {
  if (!window.confirm(t('Delete menu ":name"? The connected page will not be deleted.', { name: menu.name }))) return;
  router.delete(`/dashboard/website/menus/${menu.id}`, { preserveScroll: true });
}
</script>

<template>
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHero eyebrow="Website Management" :title="$t('Menu Management')" :description="$t('Control public website navigation, menu status, and display order.')" />

      <div class="overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-[0_18px_45px_rgba(15,23,42,0.08)] dark:border-gray-800 dark:bg-gray-900 dark:shadow-[0_18px_45px_rgba(15,23,42,0.28)]">
        <div class="flex flex-col gap-5 border-b border-slate-200 px-6 py-5 lg:flex-row lg:items-start lg:justify-between dark:border-gray-800">
          <div>
            <p class="text-xs font-bold uppercase tracking-[0.32em] text-slate-500 dark:text-slate-400">{{ $t('Menu Directory') }}</p>
            <p class="mt-2 text-base text-slate-600 dark:text-slate-300">{{ $t('Read, create, update, and reorder website menu records.') }}</p>
          </div>
          <div class="flex w-full flex-col gap-3 sm:flex-row lg:w-auto lg:items-center">
            <label class="relative block min-w-0 lg:w-72">
              <Search class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
              <input
                v-model="search"
                type="text"
                :placeholder="$t('Search menus...')"
                class="w-full rounded-2xl border border-slate-300 bg-white py-3 pl-11 pr-4 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 dark:border-gray-700 dark:bg-gray-800/80 dark:text-slate-100 dark:placeholder:text-slate-500"
              />
            </label>
            <span v-if="isSavingOrder" class="rounded-2xl border border-blue-500/20 bg-blue-500/10 px-4 py-3 text-sm font-semibold text-blue-700 dark:text-blue-300">
              {{ $t('Saving order...') }}
            </span>
            <Link href="/dashboard/website/menus/create" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-500">
              <Plus class="h-4 w-4" />
              {{ $t('New Menu') }}
            </Link>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full min-w-[900px] text-sm">
            <thead>
              <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-bold uppercase tracking-[0.28em] text-slate-500 dark:border-gray-800 dark:bg-gray-800/70 dark:text-slate-400">
                <th class="w-14 px-4 py-4"></th>
                <th class="px-6 py-4">{{ $t('No') }}</th>
                <th class="px-6 py-4">{{ $t('Name') }}</th>
                <th class="px-6 py-4">{{ $t('Parent') }}</th>
                <th class="px-6 py-4">{{ $t('Page') }}</th>
                <th class="px-6 py-4">{{ $t('Slug') }}</th>
                <th class="px-6 py-4">{{ $t('Status') }}</th>
                <th class="px-6 py-4 text-right">{{ $t('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in menuRows"
                :key="row.menu.id"
                draggable="true"
                class="cursor-move border-b border-slate-100 text-slate-700 transition hover:bg-slate-50 dark:border-gray-800 dark:text-slate-200 dark:hover:bg-gray-800/60"
                :class="{
                  'opacity-50': draggedIndex === row.index,
                  'bg-blue-500/10': dragOverIndex === row.index && draggedIndex !== row.index,
                  'bg-slate-50 dark:bg-gray-800/35': row.depth > 0,
                }"
                @dragstart="startDrag(row.index)"
                @dragenter.prevent="enterDrag(row.index)"
                @dragover.prevent
                @drop.prevent="dropMenu(row.index)"
                @dragend="endDrag"
              >
                <td class="px-4 py-4">
                  <button
                    v-if="row.depth === 0 && (row.menu.children_count ?? 0) > 0"
                    type="button"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300 dark:hover:bg-gray-700"
                    :aria-expanded="expandedMenus[row.menu.id]"
                    :title="expandedMenus[row.menu.id] ? $t('Collapse submenu') : $t('Expand submenu')"
                    @click.stop="toggleExpand(row.menu)"
                  >
                    <ChevronRight class="h-4 w-4 transition" :class="expandedMenus[row.menu.id] ? 'rotate-90' : ''" />
                  </button>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3 text-slate-500 dark:text-slate-400">
                    <span v-if="row.depth === 0" class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 dark:bg-gray-800 dark:text-slate-300">{{ row.displayPosition }}</span>
                    <span v-else class="text-xs font-semibold text-slate-500 dark:text-slate-500">-</span>
                    <span class="select-none text-lg font-bold" :title="$t('Drag to reorder')">↕</span>
                  </div>
                </td>
                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">
                  <div class="flex items-center gap-2" :class="row.depth > 0 ? 'pl-8' : ''">
                    <span v-if="row.depth > 0" class="h-px w-5 shrink-0 bg-slate-300 dark:bg-slate-600"></span>
                    <span>{{ row.menu.name }}</span>
                    <span v-if="row.depth === 0 && (row.menu.children_count ?? 0) > 0" class="rounded-full bg-blue-500/15 px-2 py-0.5 text-[11px] font-bold text-blue-700 dark:text-blue-300">
                      {{ row.menu.children_count }}
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4 text-slate-600 dark:text-slate-300">
                  <span v-if="row.menu.parent" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 dark:bg-gray-800 dark:text-slate-300">{{ row.menu.parent.name }}</span>
                  <span v-else class="text-slate-500 dark:text-slate-500">{{ $t('Top-level menu') }}</span>
                </td>
                <td class="px-6 py-4 text-slate-600 dark:text-slate-300">{{ row.menu.page?.title ?? $t('Missing page') }}</td>
                <td class="px-6 py-4">
                  <span class="inline-flex rounded-lg bg-slate-100 px-3 py-1 font-mono text-xs text-slate-700 dark:bg-gray-800 dark:text-slate-300">/{{ row.menu.page?.slug }}</span>
                </td>
                <td class="px-6 py-4">
                  <button
                    type="button"
                    class="rounded-full px-3 py-1 text-xs font-semibold transition"
                    :class="row.menu.is_active ? 'bg-emerald-500/15 text-emerald-700 hover:bg-emerald-500/25 dark:text-emerald-300' : 'bg-rose-500/15 text-rose-700 hover:bg-rose-500/25 dark:text-rose-300'"
                    @click="toggleMenu(row.menu)"
                  >
                    {{ row.menu.is_active ? $t('Active') : $t('Inactive') }}
                  </button>
                </td>
                <td class="px-6 py-4">
                  <div class="flex justify-end gap-2">
                    <Link
                      :href="`/dashboard/website/menus/${row.menu.id}/edit`"
                      class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500/15 text-amber-700 transition hover:bg-amber-500/25 dark:text-amber-300"
                      :title="$t('Edit menu')"
                      :aria-label="$t('Edit menu')"
                    >
                      <Pencil class="h-4 w-4" />
                    </Link>
                    <button
                      type="button"
                      class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-rose-500/15 text-rose-700 transition hover:bg-rose-500/25 dark:text-rose-300"
                      :title="$t('Delete menu')"
                      :aria-label="$t('Delete menu')"
                      @click="deleteMenu(row.menu)"
                    >
                      <Trash2 class="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!orderedMenus.length">
                <td colspan="8" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                  {{ $t('No menu items found. Create your first menu to display navigation on the public website.') }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <DirectoryPagination
          :items="menus"
          :summary="$t('Showing :from to :to of :total menus', { from: menus.from ?? 0, to: menus.to ?? 0, total: menus.total ?? 0 })"
        />
      </div>
    </section>
  </DashboardLayout>
</template>
