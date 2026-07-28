<script setup>
import { ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { Image, Pencil, Star, Trash2 } from "@lucide/vue";
import DashboardLayout from "@/layouts/DashboardLayout.vue";
import Breadcrumbs from "@/components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "@/components/ui/page-hero/PageHero.vue";

const props = defineProps({
  news: Object,
  filters: Object,
});

const search = ref(props.filters?.search ?? "");
const status = ref(props.filters?.status ?? "");
let timeout = null;

const breadcrumbs = [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Website Management", current: true },
  { label: "News Management", current: true },
];

function refresh() {
  router.get("/dashboard/website/news", { search: search.value, status: status.value, page: 1 }, {
    preserveState: true,
    replace: true,
  });
}

watch(search, () => {
  clearTimeout(timeout);
  timeout = setTimeout(refresh, 350);
});

watch(status, refresh);

function toggleNews(item) {
  router.patch(`/dashboard/website/news/${item.id}/status`, { is_active: !item.is_active }, { preserveScroll: true });
}

function deleteNews(item) {
  if (!window.confirm(`Delete news "${item.title}"?`)) return;
  router.delete(`/dashboard/website/news/${item.id}`, { preserveScroll: true });
}
</script>

<template>
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHero eyebrow="Website Management" title="News Management" description="Manage featured news, article details, publishing status, and image galleries." />

      <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 lg:flex-row lg:items-center lg:justify-between dark:border-gray-800">
          <div class="flex flex-col gap-3 sm:flex-row">
            <input
              v-model="search"
              type="text"
              placeholder="Search news..."
              class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 sm:w-72 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
            />
            <select
              v-model="status"
              class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
            >
              <option value="">All news</option>
              <option value="active">Published news</option>
              <option value="inactive">Draft news</option>
              <option value="featured">Featured news</option>
            </select>
          </div>
          <Link href="/dashboard/website/news/create" class="rounded-xl bg-blue-600 px-5 py-3 text-center text-sm font-semibold text-white transition hover:bg-blue-700">
            Add News
          </Link>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full min-w-[1080px] text-sm">
            <thead>
              <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-600 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-300">
                <th class="px-6 py-3">News</th>
                <th class="px-6 py-3">Author</th>
                <th class="px-6 py-3">Published</th>
                <th class="px-6 py-3">Images</th>
                <th class="px-6 py-3">Order</th>
                <th class="px-6 py-3">Featured</th>
                <th class="px-6 py-3">Status</th>
                <th class="px-6 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in news.data" :key="item.id" class="border-b border-slate-100 transition hover:bg-slate-50 dark:border-gray-800 dark:hover:bg-gray-800/60">
                <td class="px-6 py-4">
                  <div class="flex min-w-0 items-center gap-4">
                    <div class="h-16 w-24 flex-shrink-0 overflow-hidden rounded-lg bg-slate-100 dark:bg-gray-800">
                      <img v-if="item.images?.[0]?.image_url" :src="item.images[0].image_url" :alt="item.title" class="h-full w-full object-cover" />
                      <div v-else class="flex h-full w-full items-center justify-center text-slate-400">
                        <Image class="h-6 w-6" />
                      </div>
                    </div>
                    <div class="min-w-0">
                      <p class="truncate font-semibold text-slate-900 dark:text-gray-100">{{ item.title }}</p>
                      <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">/{{ item.slug }}</p>
                      <p class="mt-1 line-clamp-2 max-w-md text-xs text-slate-500 dark:text-gray-400">{{ item.excerpt || item.description || "No description" }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-slate-600 dark:text-gray-300">{{ item.author || "Not set" }}</td>
                <td class="px-6 py-4 text-slate-600 dark:text-gray-300">{{ item.published_at || "Not set" }}</td>
                <td class="px-6 py-4 text-slate-600 dark:text-gray-300">{{ item.images_count }}</td>
                <td class="px-6 py-4 text-slate-600 dark:text-gray-300">{{ item.sort_order }}</td>
                <td class="px-6 py-4">
                  <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold" :class="item.is_featured ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' : 'bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-300'">
                    <Star class="h-3.5 w-3.5" />
                    {{ item.is_featured ? "Featured" : "Standard" }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <button
                    type="button"
                    class="rounded-full px-3 py-1 text-xs font-semibold transition"
                    :class="item.is_active ? 'bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-300' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-300'"
                    @click="toggleNews(item)"
                  >
                    {{ item.is_active ? "Published" : "Draft" }}
                  </button>
                </td>
                <td class="px-6 py-4">
                  <div class="flex justify-end gap-2">
                    <Link
                      :href="`/dashboard/website/news/${item.id}/edit`"
                      class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 transition hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20"
                      title="Edit news"
                      aria-label="Edit news"
                    >
                      <Pencil class="h-4 w-4" />
                    </Link>
                    <button
                      type="button"
                      class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 transition hover:bg-red-100 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20"
                      title="Delete news"
                      aria-label="Delete news"
                      @click="deleteNews(item)"
                    >
                      <Trash2 class="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!news.data?.length">
                <td colspan="8" class="px-6 py-12 text-center text-slate-500 dark:text-gray-400">
                  No news found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/40">
          <p class="text-sm text-slate-500 dark:text-gray-400">Showing {{ news.from ?? 0 }}-{{ news.to ?? 0 }} of {{ news.total ?? 0 }} news</p>
          <div class="flex flex-wrap gap-2 text-sm">
            <Link
              v-for="link in news.links"
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
