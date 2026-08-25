<script setup>
import { ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { Pencil, Play, Plus, Search, Star, Trash2 } from "@lucide/vue";
import DashboardLayout from "@/layouts/DashboardLayout.vue";
import Breadcrumbs from "@/components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "@/components/ui/page-hero/PageHero.vue";
import DirectoryPagination from "@/pages/backend/website/components/DirectoryPagination.vue";
import { useI18n } from "@/i18n";

const { t } = useI18n();

const props = defineProps({
  videos: Object,
  filters: Object,
});

const search = ref(props.filters?.search ?? "");
const status = ref(props.filters?.status ?? "");
let timeout = null;

const breadcrumbs = [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Website Management", current: true },
  { label: "Video Management", current: true },
];

function refresh() {
  router.get("/dashboard/website/videos", { search: search.value, status: status.value, page: 1 }, {
    preserveState: true,
    replace: true,
  });
}

watch(search, () => {
  clearTimeout(timeout);
  timeout = setTimeout(refresh, 350);
});

watch(status, refresh);

function toggleVideo(video) {
  router.patch(`/dashboard/website/videos/${video.id}/status`, { is_active: !video.is_active }, { preserveScroll: true });
}

function deleteVideo(video) {
  if (!window.confirm(t('Delete video ":title"?', { title: video.title }))) return;
  router.delete(`/dashboard/website/videos/${video.id}`, { preserveScroll: true });
}

function rowNumber(index) {
  const currentPage = props.videos?.current_page ?? 1;
  const perPage = props.videos?.per_page ?? props.videos?.data?.length ?? 0;

  return ((currentPage - 1) * perPage) + index + 1;
}
</script>

<template>
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHero eyebrow="Website Management" :title="$t('Video Management')" :description="$t('Manage website videos, viewer thumbnails, playback details, and publishing status.')" />

      <div class="overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-[0_18px_45px_rgba(15,23,42,0.08)] dark:border-gray-800 dark:bg-gray-900 dark:shadow-[0_18px_45px_rgba(15,23,42,0.28)]">
        <div class="flex flex-col gap-5 border-b border-slate-200 px-6 py-5 lg:flex-row lg:items-start lg:justify-between dark:border-gray-800">
          <div>
            <p class="text-xs font-bold uppercase tracking-[0.32em] text-slate-500 dark:text-slate-400">{{ $t('Video Directory') }}</p>
            <p class="mt-2 text-base text-slate-600 dark:text-slate-300">{{ $t('Read, create, update, and control featured video records.') }}</p>
          </div>
          <div class="flex w-full flex-col gap-3 sm:flex-row lg:w-auto lg:items-center">
            <label class="relative block min-w-0 lg:w-72">
              <Search class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
              <input
                v-model="search"
                type="text"
                :placeholder="$t('Search videos...')"
                class="w-full rounded-2xl border border-slate-300 bg-white py-3 pl-11 pr-4 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 dark:border-gray-700 dark:bg-gray-800/80 dark:text-slate-100 dark:placeholder:text-slate-500"
              />
            </label>
            <select
              v-model="status"
              class="rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 dark:border-gray-700 dark:bg-gray-800/80 dark:text-slate-100"
            >
              <option value="">{{ $t('All videos') }}</option>
              <option value="active">{{ $t('Active videos') }}</option>
              <option value="inactive">{{ $t('Inactive videos') }}</option>
              <option value="featured">{{ $t('Featured videos') }}</option>
            </select>
            <Link href="/dashboard/website/videos/create" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-500">
              <Plus class="h-4 w-4" />
              {{ $t('New Video') }}
            </Link>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full min-w-[1120px] text-sm">
            <thead>
              <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-bold uppercase tracking-[0.28em] text-slate-500 dark:border-gray-800 dark:bg-gray-800/70 dark:text-slate-400">
                <th class="px-6 py-4">{{ $t('No') }}</th>
                <th class="px-6 py-4">{{ $t('Name') }}</th>
                <th class="px-6 py-4">{{ $t('Duration') }}</th>
                <th class="px-6 py-4">{{ $t('Views') }}</th>
                <th class="px-6 py-4">{{ $t('Order') }}</th>
                <th class="px-6 py-4">{{ $t('Featured') }}</th>
                <th class="px-6 py-4">{{ $t('Status') }}</th>
                <th class="px-6 py-4 text-right">{{ $t('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(video, index) in videos.data" :key="video.id" class="border-b border-slate-100 text-slate-700 transition hover:bg-slate-50 dark:border-gray-800 dark:text-slate-200 dark:hover:bg-gray-800/60">
                <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ rowNumber(index) }}</td>
                <td class="px-6 py-4">
                  <div class="flex min-w-0 items-center gap-4">
                    <div class="relative h-16 w-28 flex-shrink-0 overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-900">
                      <img v-if="video.thumbnail_url" :src="video.thumbnail_url" :alt="video.title" class="h-full w-full object-cover" />
                      <div v-else class="flex h-full w-full items-center justify-center text-slate-500 dark:text-white">
                        <Play class="h-6 w-6" />
                      </div>
                      <span v-if="video.duration" class="absolute bottom-1 right-1 rounded bg-black/75 px-1.5 py-0.5 text-[11px] font-bold text-white">{{ video.duration }}</span>
                    </div>
                    <div class="min-w-0">
                      <p class="truncate font-semibold text-slate-900 dark:text-white">{{ video.title }}</p>
                      <p class="mt-2 line-clamp-2 max-w-md text-xs text-slate-500 dark:text-slate-400">{{ video.description || $t('No description') }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-slate-600 dark:text-slate-300">{{ video.duration || $t('Not set') }}</td>
                <td class="px-6 py-4 text-slate-600 dark:text-slate-300">{{ video.views_count }}</td>
                <td class="px-6 py-4 text-slate-600 dark:text-slate-300">{{ video.sort_order }}</td>
                <td class="px-6 py-4">
                  <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold" :class="video.is_featured ? 'bg-amber-500/15 text-amber-700 dark:text-amber-300' : 'bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-slate-300'">
                    <Star class="h-3.5 w-3.5" />
                    {{ video.is_featured ? $t('Featured') : $t('Standard') }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <button
                    type="button"
                    class="rounded-full px-3 py-1 text-xs font-semibold transition"
                    :class="video.is_active ? 'bg-emerald-500/15 text-emerald-700 hover:bg-emerald-500/25 dark:text-emerald-300' : 'bg-rose-500/15 text-rose-700 hover:bg-rose-500/25 dark:text-rose-300'"
                    @click="toggleVideo(video)"
                  >
                    {{ video.is_active ? $t('Active') : $t('Inactive') }}
                  </button>
                </td>
                <td class="px-6 py-4">
                  <div class="flex justify-end gap-2">
                    <a
                      :href="video.video_url"
                      target="_blank"
                      rel="noreferrer"
                      class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-blue-500/15 text-blue-700 transition hover:bg-blue-500/25 dark:text-blue-300"
                      :title="$t('Open video')"
                      :aria-label="$t('Open video')"
                    >
                      <Play class="h-4 w-4" />
                    </a>
                    <Link
                      :href="`/dashboard/website/videos/${video.id}/edit`"
                      class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500/15 text-amber-700 transition hover:bg-amber-500/25 dark:text-amber-300"
                      :title="$t('Edit video')"
                      :aria-label="$t('Edit video')"
                    >
                      <Pencil class="h-4 w-4" />
                    </Link>
                    <button
                      type="button"
                      class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-rose-500/15 text-rose-700 transition hover:bg-rose-500/25 dark:text-rose-300"
                      :title="$t('Delete video')"
                      :aria-label="$t('Delete video')"
                      @click="deleteVideo(video)"
                    >
                      <Trash2 class="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!videos.data?.length">
                <td colspan="8" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                  {{ $t('No videos found.') }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <DirectoryPagination
          :items="videos"
          :summary="$t('Showing :from to :to of :total videos', { from: videos.from ?? 0, to: videos.to ?? 0, total: videos.total ?? 0 })"
        />
      </div>
    </section>
  </DashboardLayout>
</template>
