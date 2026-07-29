<script setup>
import { computed, ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { ChevronRight, Eye, Pencil, Trash2 } from "@lucide/vue";
import DashboardLayout from "@/layouts/DashboardLayout.vue";
import Breadcrumbs from "@/components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "@/components/ui/page-hero/PageHero.vue";
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

      <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 lg:flex-row lg:items-center lg:justify-between dark:border-gray-800">
          <input
            v-model="search"
            type="text"
            :placeholder="$t('Search menus...')"
            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 sm:w-72 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
          />
          <div class="flex flex-wrap items-center gap-3">
            <span v-if="isSavingOrder" class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-700 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300">
              {{ $t('Saving order...') }}
            </span>
            <Link href="/dashboard/website/menus/create" class="rounded-xl bg-blue-600 px-5 py-3 text-center text-sm font-semibold text-white transition hover:bg-blue-700">
              {{ $t('Add New Menu') }}
            </Link>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full min-w-[900px] text-sm">
            <thead>
              <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-600 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-300">
                <th class="w-12 px-4 py-3"></th>
                <th class="px-6 py-3">{{ $t('Order') }}</th>
                <th class="px-6 py-3">{{ $t('Menu Name') }}</th>
                <th class="px-6 py-3">{{ $t('Base Menu') }}</th>
                <th class="px-6 py-3">{{ $t('Connected Page') }}</th>
                <th class="px-6 py-3">{{ $t('Page Slug') }}</th>
                <th class="px-6 py-3">{{ $t('Status') }}</th>
                <th class="px-6 py-3 text-right">{{ $t('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in menuRows"
                :key="row.menu.id"
                draggable="true"
                class="cursor-move border-b border-slate-100 transition hover:bg-slate-50 dark:border-gray-800 dark:hover:bg-gray-800/60"
                :class="{
                  'opacity-50': draggedIndex === row.index,
                  'bg-blue-50 dark:bg-blue-500/10': dragOverIndex === row.index && draggedIndex !== row.index,
                  'bg-slate-50/70 dark:bg-gray-800/30': row.depth > 0,
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
                    class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    :aria-expanded="expandedMenus[row.menu.id]"
                    :title="expandedMenus[row.menu.id] ? $t('Collapse submenu') : $t('Expand submenu')"
                    @click.stop="toggleExpand(row.menu)"
                  >
                    <ChevronRight class="h-4 w-4 transition" :class="expandedMenus[row.menu.id] ? 'rotate-90' : ''" />
                  </button>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3 text-slate-400">
                    <span v-if="row.depth === 0" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-bold text-slate-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ row.displayPosition }}</span>
                    <span v-else class="text-xs font-semibold text-slate-400 dark:text-gray-500">-</span>
                    <span class="select-none text-lg font-bold" :title="$t('Drag to reorder')">↕</span>
                  </div>
                </td>
                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-gray-100">
                  <div class="flex items-center gap-2" :class="row.depth > 0 ? 'pl-8' : ''">
                    <span v-if="row.depth > 0" class="h-px w-5 shrink-0 bg-slate-300 dark:bg-gray-700"></span>
                    <span>{{ row.menu.name }}</span>
                    <span v-if="row.depth === 0 && (row.menu.children_count ?? 0) > 0" class="rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-bold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
                      {{ row.menu.children_count }}
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4 text-slate-600 dark:text-gray-300">
                  <span v-if="row.menu.parent" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600 dark:bg-gray-800 dark:text-gray-300">{{ row.menu.parent.name }}</span>
                  <span v-else class="text-slate-400 dark:text-gray-500">{{ $t('Top-level menu') }}</span>
                </td>
                <td class="px-6 py-4 text-slate-600 dark:text-gray-300">{{ row.menu.page?.title ?? $t('Missing page') }}</td>
                <td class="px-6 py-4 text-slate-500 dark:text-gray-400">/{{ row.menu.page?.slug }}</td>
                <td class="px-6 py-4">
                  <button
                    type="button"
                    class="rounded-full px-3 py-1 text-xs font-semibold transition"
                    :class="row.menu.is_active ? 'bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-300' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-300'"
                    @click="toggleMenu(row.menu)"
                  >
                    {{ row.menu.is_active ? $t('Active') : $t('Inactive') }}
                  </button>
                </td>
                <td class="px-6 py-4">
                  <div class="flex justify-end gap-2">
                    <a
                      v-if="row.menu.resolved_url"
                      :href="row.menu.resolved_url"
                      target="_blank"
                      class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 transition hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20"
                      :title="$t('View page')"
                      :aria-label="$t('View page')"
                    >
                      <Eye class="h-4 w-4" />
                    </a>
                    <Link
                      :href="`/dashboard/website/menus/${row.menu.id}/edit`"
                      class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 transition hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20"
                      :title="$t('Edit menu')"
                      :aria-label="$t('Edit menu')"
                    >
                      <Pencil class="h-4 w-4" />
                    </Link>
                    <button
                      type="button"
                      class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 transition hover:bg-red-100 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20"
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
                <td colspan="8" class="px-6 py-12 text-center text-slate-500 dark:text-gray-400">
                  {{ $t('No menu items found. Create your first menu to display navigation on the public website.') }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/40">
          <p class="text-sm text-slate-500 dark:text-gray-400">{{ $t('Showing :from-:to of :total menus', { from: menus.from ?? 0, to: menus.to ?? 0, total: menus.total ?? 0 }) }}</p>
          <div class="flex flex-wrap gap-2 text-sm">
            <Link
              v-for="link in menus.links"
              :key="link.label"
              :href="link.url || '#'"
              v-html="link.label"
              class="rounded-lg border px-3 py-2 transition dark:border-gray-700 dark:text-gray-300"
              :class="{ 'border-blue-600 bg-blue-600 text-white': link.active, 'pointer-events-none opacity-40': !link.url }"
            />
          </div>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
