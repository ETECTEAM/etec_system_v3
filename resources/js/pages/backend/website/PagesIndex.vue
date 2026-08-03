<script setup>
import { ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { Eye, ExternalLink, Pencil, Plus, Search, Trash2 } from "@lucide/vue";
import DashboardLayout from "@/layouts/DashboardLayout.vue";
import Breadcrumbs from "@/components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "@/components/ui/page-hero/PageHero.vue";
import DirectoryPagination from "@/pages/backend/website/components/DirectoryPagination.vue";
import { useI18n } from "@/i18n";

const { t } = useI18n();

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
  if (!window.confirm(t('Delete page ":title"? This is only allowed when no menus are connected.', { title: page.title }))) return;
  router.delete(`/dashboard/website/pages/${page.id}`, { preserveScroll: true });
}

function rowNumber(index) {
  const currentPage = props.pages?.current_page ?? 1;
  const perPage = props.pages?.per_page ?? props.pages?.data?.length ?? 0;

  return ((currentPage - 1) * perPage) + index + 1;
}
</script>

<template>
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHero eyebrow="Website Management" :title="$t('Page Management')" :description="$t('Create public pages, manage their content, and configure each page hero.')" />

      <div class="overflow-hidden rounded-[26px] border border-slate-800 bg-slate-900 shadow-[0_18px_45px_rgba(15,23,42,0.28)]">
        <div class="flex flex-col gap-5 border-b border-slate-800 px-6 py-5 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <p class="text-xs font-bold uppercase tracking-[0.32em] text-slate-400">{{ $t('Page Directory') }}</p>
            <p class="mt-2 text-base text-slate-300">{{ $t('Read, create, update, and manage public website pages in one place.') }}</p>
          </div>
          <div class="flex w-full flex-col gap-3 sm:flex-row lg:w-auto lg:items-center">
            <label class="relative block min-w-0 lg:w-72">
              <Search class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
              <input
                v-model="search"
                type="text"
                :placeholder="$t('Search pages...')"
                class="w-full rounded-2xl border border-slate-700 bg-slate-800/80 py-3 pl-11 pr-4 text-sm text-slate-100 outline-none transition placeholder:text-slate-500 focus:border-blue-500"
              />
            </label>
            <select
              v-model="status"
              class="rounded-2xl border border-slate-700 bg-slate-800/80 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-blue-500"
            >
              <option value="">{{ $t('All pages') }}</option>
              <option value="active">{{ $t('Active pages') }}</option>
              <option value="inactive">{{ $t('Inactive pages') }}</option>
              <option value="hero">{{ $t('Pages with active hero') }}</option>
              <option value="no_hero">{{ $t('Pages without a hero') }}</option>
            </select>
            <Link href="/dashboard/website/pages/create" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-500">
              <Plus class="h-4 w-4" />
              {{ $t('New Page') }}
            </Link>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full min-w-[1040px] text-sm">
            <thead>
              <tr class="border-b border-slate-700 bg-slate-800/70 text-left text-xs font-bold uppercase tracking-[0.28em] text-slate-400">
                <th class="px-6 py-4">{{ $t('No') }}</th>
                <th class="px-6 py-4">{{ $t('Name') }}</th>
                <th class="px-6 py-4">{{ $t('Slug') }}</th>
                <th class="px-6 py-4">{{ $t('Menu') }}</th>
                <th class="px-6 py-4">{{ $t('Hero') }}</th>
                <th class="px-6 py-4">{{ $t('Status') }}</th>
                <th class="px-6 py-4">{{ $t('Created') }}</th>
                <th class="px-6 py-4 text-right">{{ $t('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(page, index) in pages.data" :key="page.id" class="border-b border-slate-800 text-slate-200 transition hover:bg-slate-800/55">
                <td class="px-6 py-4 text-slate-400">{{ rowNumber(index) }}</td>
                <td class="px-6 py-4 font-semibold text-white">{{ page.title }}</td>
                <td class="px-6 py-4">
                  <span class="inline-flex rounded-lg bg-slate-800 px-3 py-1 font-mono text-xs text-slate-300">/{{ page.slug }}</span>
                </td>
                <td class="px-6 py-4 text-slate-300">{{ page.connected_menu || $t('Not connected') }}</td>
                <td class="px-6 py-4">
                  <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="page.hero_status === 'Hero Enabled' ? 'bg-emerald-500/15 text-emerald-300' : 'bg-slate-800 text-slate-300'">
                    {{ $t(page.hero_status) }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <button
                    type="button"
                    class="rounded-full px-3 py-1 text-xs font-semibold transition"
                    :class="page.is_active ? 'bg-emerald-500/15 text-emerald-300 hover:bg-emerald-500/25' : 'bg-rose-500/15 text-rose-300 hover:bg-rose-500/25'"
                    @click="togglePage(page)"
                  >
                    {{ page.is_active ? $t('Active') : $t('Inactive') }}
                  </button>
                </td>
                <td class="px-6 py-4 text-slate-400">{{ page.created_at }}</td>
                <td class="px-6 py-4">
                  <div class="flex justify-end gap-2">
                    <Link
                      :href="`/dashboard/website/pages/${page.id}`"
                      class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-800 text-slate-300 transition hover:bg-slate-700"
                      :title="$t('View page')"
                      :aria-label="$t('View page')"
                    >
                      <Eye class="h-4 w-4" />
                    </Link>
                    <Link
                      :href="`/dashboard/website/pages/${page.id}/preview`"
                      class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-blue-500/15 text-blue-300 transition hover:bg-blue-500/25"
                      :title="$t('Preview page')"
                      :aria-label="$t('Preview page')"
                    >
                      <ExternalLink class="h-4 w-4" />
                    </Link>
                    <Link
                      :href="`/dashboard/website/pages/${page.id}/edit`"
                      class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500/15 text-amber-300 transition hover:bg-amber-500/25"
                      :title="$t('Edit page')"
                      :aria-label="$t('Edit page')"
                    >
                      <Pencil class="h-4 w-4" />
                    </Link>
                    <button
                      type="button"
                      class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-rose-500/15 text-rose-300 transition hover:bg-rose-500/25"
                      :title="$t('Delete page')"
                      :aria-label="$t('Delete page')"
                      @click="deletePage(page)"
                    >
                      <Trash2 class="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!pages.data?.length">
                <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                  {{ $t('No pages found. Create your first page and connect it to a menu.') }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <DirectoryPagination
          :items="pages"
          :summary="$t('Showing :from to :to of :total pages', { from: pages.from ?? 0, to: pages.to ?? 0, total: pages.total ?? 0 })"
        />
      </div>
    </section>
  </DashboardLayout>
</template>
