<script setup>
import { ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { Eye, ExternalLink, Pencil, Trash2 } from "@lucide/vue";
import DashboardLayout from "@/layouts/DashboardLayout.vue";
import Breadcrumbs from "@/components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "@/components/ui/page-hero/PageHero.vue";

const props = defineProps({
  pages: Object,
  filters: Object,
});

const search = ref(props.filters?.search ?? "");
const status = ref(props.filters?.status ?? "");
let timeout = null;

const breadcrumbs = [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Website Management", current: true },
  { label: "Page Management", current: true },
];

function refresh() {
  router.get("/dashboard/website/pages", { search: search.value, status: status.value, page: 1 }, {
    preserveState: true,
    replace: true,
  });
}

watch(search, () => {
  clearTimeout(timeout);
  timeout = setTimeout(refresh, 350);
});

watch(status, refresh);

function togglePage(page) {
  router.patch(`/dashboard/website/pages/${page.id}/status`, { is_active: !page.is_active }, { preserveScroll: true });
}

function deletePage(page) {
  if (!window.confirm(`Delete page "${page.title}"? This is only allowed when no menus are connected.`)) return;
  router.delete(`/dashboard/website/pages/${page.id}`, { preserveScroll: true });
}
</script>

<template>
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHero eyebrow="Website Management" title="Page Management" description="Create public pages, manage their content, and configure each page hero." />

      <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 lg:flex-row lg:items-center lg:justify-between dark:border-gray-800">
          <div class="flex flex-col gap-3 sm:flex-row">
            <input
              v-model="search"
              type="text"
              placeholder="Search pages..."
              class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 sm:w-72 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
            />
            <select
              v-model="status"
              class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
            >
              <option value="">All pages</option>
              <option value="active">Active pages</option>
              <option value="inactive">Inactive pages</option>
              <option value="hero">Pages with active hero</option>
              <option value="no_hero">Pages without a hero</option>
            </select>
          </div>
          <Link href="/dashboard/website/pages/create" class="rounded-xl bg-blue-600 px-5 py-3 text-center text-sm font-semibold text-white transition hover:bg-blue-700">
            Add New Page
          </Link>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full min-w-[980px] text-sm">
            <thead>
              <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-600 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-300">
                <th class="px-6 py-3">Page Title</th>
                <th class="px-6 py-3">Slug</th>
                <th class="px-6 py-3">Connected Menu</th>
                <th class="px-6 py-3">Hero Status</th>
                <th class="px-6 py-3">Page Status</th>
                <th class="px-6 py-3">Created Date</th>
                <th class="px-6 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="page in pages.data" :key="page.id" class="border-b border-slate-100 transition hover:bg-slate-50 dark:border-gray-800 dark:hover:bg-gray-800/60">
                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-gray-100">{{ page.title }}</td>
                <td class="px-6 py-4 text-slate-500 dark:text-gray-400">/{{ page.slug }}</td>
                <td class="px-6 py-4 text-slate-600 dark:text-gray-300">{{ page.connected_menu || "Not connected" }}</td>
                <td class="px-6 py-4">
                  <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="page.hero_status === 'Hero Enabled' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-300'">
                    {{ page.hero_status }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <button
                    type="button"
                    class="rounded-full px-3 py-1 text-xs font-semibold transition"
                    :class="page.is_active ? 'bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-300' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-300'"
                    @click="togglePage(page)"
                  >
                    {{ page.is_active ? "Active" : "Inactive" }}
                  </button>
                </td>
                <td class="px-6 py-4 text-slate-500 dark:text-gray-400">{{ page.created_at }}</td>
                <td class="px-6 py-4">
                  <div class="flex justify-end gap-2">
                    <Link
                      :href="`/dashboard/website/pages/${page.id}`"
                      class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 text-slate-600 transition hover:bg-slate-100 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                      title="View page"
                      aria-label="View page"
                    >
                      <Eye class="h-4 w-4" />
                    </Link>
                    <Link
                      :href="`/dashboard/website/pages/${page.id}/preview`"
                      class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 transition hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20"
                      title="Preview page"
                      aria-label="Preview page"
                    >
                      <ExternalLink class="h-4 w-4" />
                    </Link>
                    <Link
                      :href="`/dashboard/website/pages/${page.id}/edit`"
                      class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 transition hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20"
                      title="Edit page"
                      aria-label="Edit page"
                    >
                      <Pencil class="h-4 w-4" />
                    </Link>
                    <button
                      type="button"
                      class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 transition hover:bg-red-100 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20"
                      title="Delete page"
                      aria-label="Delete page"
                      @click="deletePage(page)"
                    >
                      <Trash2 class="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!pages.data?.length">
                <td colspan="7" class="px-6 py-12 text-center text-slate-500 dark:text-gray-400">
                  No pages found. Create your first page and connect it to a menu.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/40">
          <p class="text-sm text-slate-500 dark:text-gray-400">Showing {{ pages.from ?? 0 }}-{{ pages.to ?? 0 }} of {{ pages.total ?? 0 }} pages</p>
          <div class="flex flex-wrap gap-2 text-sm">
            <Link
              v-for="link in pages.links"
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
