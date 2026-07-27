<script setup>
import { ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { Eye, Pencil, Trash2 } from "@lucide/vue";
import DashboardLayout from "@/layouts/DashboardLayout.vue";
import Breadcrumbs from "@/components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "@/components/ui/page-hero/PageHero.vue";

const props = defineProps({
  menus: Object,
  filters: Object,
});

const search = ref(props.filters?.search ?? "");
const orderedMenus = ref([...(props.menus?.data ?? [])]);
const draggedIndex = ref(null);
const dragOverIndex = ref(null);
const isSavingOrder = ref(false);
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
  },
);

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
  orderedMenus.value = copy.map((menu, position) => ({ ...menu, position: (props.menus?.from ?? 1) + position }));
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
    menus: orderedMenus.value.map((menu, index) => ({ id: menu.id, position: (props.menus?.from ?? 1) + index })),
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

function deleteMenu(menu) {
  if (!window.confirm(`Delete menu "${menu.name}"? The connected page will not be deleted.`)) return;
  router.delete(`/dashboard/website/menus/${menu.id}`, { preserveScroll: true });
}
</script>

<template>
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHero eyebrow="Website Management" title="Menu Management" description="Control public website navigation, menu status, and display order." />

      <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 lg:flex-row lg:items-center lg:justify-between dark:border-gray-800">
          <input
            v-model="search"
            type="text"
            placeholder="Search menus..."
            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 sm:w-72 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
          />
          <div class="flex flex-wrap items-center gap-3">
            <span v-if="isSavingOrder" class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-700 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300">
              Saving order...
            </span>
            <Link href="/dashboard/website/menus/create" class="rounded-xl bg-blue-600 px-5 py-3 text-center text-sm font-semibold text-white transition hover:bg-blue-700">
              Add New Menu
            </Link>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full min-w-[900px] text-sm">
            <thead>
              <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-600 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-300">
                <th class="px-6 py-3">Order</th>
                <th class="px-6 py-3">Menu Name</th>
                <th class="px-6 py-3">Connected Page</th>
                <th class="px-6 py-3">Page Slug</th>
                <th class="px-6 py-3">Status</th>
                <th class="px-6 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(menu, index) in orderedMenus"
                :key="menu.id"
                draggable="true"
                class="cursor-move border-b border-slate-100 transition hover:bg-slate-50 dark:border-gray-800 dark:hover:bg-gray-800/60"
                :class="{
                  'opacity-50': draggedIndex === index,
                  'bg-blue-50 dark:bg-blue-500/10': dragOverIndex === index && draggedIndex !== index,
                }"
                @dragstart="startDrag(index)"
                @dragenter.prevent="enterDrag(index)"
                @dragover.prevent
                @drop.prevent="dropMenu(index)"
                @dragend="endDrag"
              >
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3 text-slate-400">
                    <span class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-bold text-slate-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ menu.position ?? ((menus?.from ?? 1) + index) }}</span>
                    <span class="select-none text-lg font-bold" title="Drag to reorder">↕</span>
                  </div>
                </td>
                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-gray-100">{{ menu.name }}</td>
                <td class="px-6 py-4 text-slate-600 dark:text-gray-300">{{ menu.page?.title ?? "Missing page" }}</td>
                <td class="px-6 py-4 text-slate-500 dark:text-gray-400">/{{ menu.page?.slug }}</td>
                <td class="px-6 py-4">
                  <button
                    type="button"
                    class="rounded-full px-3 py-1 text-xs font-semibold transition"
                    :class="menu.is_active ? 'bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-300' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-300'"
                    @click="toggleMenu(menu)"
                  >
                    {{ menu.is_active ? "Active" : "Inactive" }}
                  </button>
                </td>
                <td class="px-6 py-4">
                  <div class="flex justify-end gap-2">
                    <a
                      v-if="menu.resolved_url"
                      :href="menu.resolved_url"
                      target="_blank"
                      class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 transition hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20"
                      title="View page"
                      aria-label="View page"
                    >
                      <Eye class="h-4 w-4" />
                    </a>
                    <Link
                      :href="`/dashboard/website/menus/${menu.id}/edit`"
                      class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 transition hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20"
                      title="Edit menu"
                      aria-label="Edit menu"
                    >
                      <Pencil class="h-4 w-4" />
                    </Link>
                    <button
                      type="button"
                      class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 transition hover:bg-red-100 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20"
                      title="Delete menu"
                      aria-label="Delete menu"
                      @click="deleteMenu(menu)"
                    >
                      <Trash2 class="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!orderedMenus.length">
                <td colspan="6" class="px-6 py-12 text-center text-slate-500 dark:text-gray-400">
                  No menu items found. Create your first menu to display navigation on the public website.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/40">
          <p class="text-sm text-slate-500 dark:text-gray-400">Showing {{ menus.from ?? 0 }}-{{ menus.to ?? 0 }} of {{ menus.total ?? 0 }} menus</p>
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
