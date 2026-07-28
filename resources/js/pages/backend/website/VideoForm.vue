<script setup>
import { computed, ref } from "vue";
import { Link, useForm } from "@inertiajs/vue3";
import { Play } from "@lucide/vue";
import DashboardLayout from "@/layouts/DashboardLayout.vue";
import Breadcrumbs from "@/components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "@/components/ui/page-hero/PageHero.vue";

const props = defineProps({
  videoData: {
    type: Object,
    default: null,
  },
});

const isEditing = computed(() => Boolean(props.videoData));
const videoPreview = ref(props.videoData?.video_url ?? "");
const thumbnailPreview = ref(props.videoData?.thumbnail_url ?? "");

const form = useForm({
  _method: isEditing.value ? "put" : "post",
  title: props.videoData?.title ?? "",
  description: props.videoData?.description ?? "",
  video: null,
  thumbnail: null,
  duration: props.videoData?.duration ?? "",
  sort_order: props.videoData?.sort_order ?? 0,
  is_featured: props.videoData?.is_featured ?? false,
  is_active: props.videoData?.is_active ?? true,
  remove_thumbnail: false,
});

const breadcrumbs = [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Website Management", current: true },
  { label: "Video Management", href: "/dashboard/website/videos" },
  { label: isEditing.value ? "Edit Video" : "Create Video", current: true },
];

function chooseVideo(event) {
  const file = event.target.files?.[0] ?? null;
  form.video = file;
  videoPreview.value = file ? URL.createObjectURL(file) : props.videoData?.video_url ?? "";
}

function chooseThumbnail(event) {
  const file = event.target.files?.[0] ?? null;
  form.thumbnail = file;
  form.remove_thumbnail = false;
  thumbnailPreview.value = file ? URL.createObjectURL(file) : props.videoData?.thumbnail_url ?? "";
}

function removeThumbnail() {
  form.thumbnail = null;
  form.remove_thumbnail = true;
  thumbnailPreview.value = "";
}

function submit() {
  const url = isEditing.value
    ? `/dashboard/website/videos/${props.videoData.id}`
    : "/dashboard/website/videos";

  form.post(url, {
    forceFormData: true,
    preserveScroll: true,
  });
}
</script>

<template>
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHero
        eyebrow="Website Management"
        :title="isEditing ? 'Edit Video' : 'Create Video'"
        description="Upload video content and tune the details shown around the viewer."
      />

      <form class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]" @submit.prevent="submit">
        <div class="space-y-6">
          <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-lg font-bold text-slate-900 dark:text-gray-100">Video Details</h2>
            <div class="mt-5 space-y-5">
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">Title</label>
                <input v-model="form.title" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" />
                <p v-if="form.errors.title" class="mt-1 text-sm text-rose-600">{{ form.errors.title }}</p>
              </div>

              <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">Description</label>
                <textarea v-model="form.description" rows="5" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" />
                <p v-if="form.errors.description" class="mt-1 text-sm text-rose-600">{{ form.errors.description }}</p>
              </div>

              <div class="grid gap-5 md:grid-cols-2">
                <div>
                  <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">Duration</label>
                  <input v-model="form.duration" type="text" placeholder="03:45" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" />
                  <p v-if="form.errors.duration" class="mt-1 text-sm text-rose-600">{{ form.errors.duration }}</p>
                </div>

                <div>
                  <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">Sort Order</label>
                  <input v-model.number="form.sort_order" type="number" min="0" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" />
                  <p v-if="form.errors.sort_order" class="mt-1 text-sm text-rose-600">{{ form.errors.sort_order }}</p>
                </div>
              </div>

              <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">Video File</label>
                <input type="file" accept=".mp4,.mov,.webm,.ogg,video/mp4,video/quicktime,video/webm,video/ogg" class="block w-full rounded-xl border border-slate-300 px-4 py-3 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:font-semibold file:text-blue-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200" @change="chooseVideo" />
                <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">MP4, MOV, WebM, OGG. Max 1GB.</p>
                <p v-if="form.errors.video" class="mt-1 text-sm text-rose-600">{{ form.errors.video }}</p>
              </div>

              <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">Thumbnail</label>
                <input type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="block w-full rounded-xl border border-slate-300 px-4 py-3 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:font-semibold file:text-blue-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200" @change="chooseThumbnail" />
                <button v-if="thumbnailPreview" type="button" class="mt-2 text-sm font-semibold text-rose-600 hover:text-rose-700" @click="removeThumbnail">Remove thumbnail</button>
                <p v-if="form.errors.thumbnail" class="mt-1 text-sm text-rose-600">{{ form.errors.thumbnail }}</p>
              </div>

              <div class="grid gap-3 md:grid-cols-2">
                <label class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-800/50">
                  <span class="block text-sm font-semibold text-slate-800 dark:text-gray-100">Active</span>
                  <input v-model="form.is_active" type="checkbox" class="h-5 w-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                </label>

                <label class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-800/50">
                  <span class="block text-sm font-semibold text-slate-800 dark:text-gray-100">Featured</span>
                  <input v-model="form.is_featured" type="checkbox" class="h-5 w-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                </label>
              </div>
            </div>
          </div>

          <div class="flex flex-wrap justify-end gap-3">
            <Link href="/dashboard/website/videos" class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-gray-700 dark:text-gray-200">Cancel</Link>
            <button type="submit" :disabled="form.processing" class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50">
              {{ form.processing ? "Saving..." : isEditing ? "Update Video" : "Create Video" }}
            </button>
          </div>
        </div>

        <aside class="space-y-4">
          <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="mb-3 text-sm font-bold text-slate-900 dark:text-gray-100">Viewer Preview</p>
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-950 dark:border-gray-800">
              <video
                v-if="videoPreview"
                :src="videoPreview"
                :poster="thumbnailPreview || undefined"
                controls
                playsinline
                class="aspect-video w-full bg-black object-contain"
              />
              <div v-else class="flex aspect-video w-full items-center justify-center bg-slate-900 text-white">
                <Play class="h-12 w-12" />
              </div>
            </div>
            <div class="mt-4 space-y-2">
              <div class="flex flex-wrap items-center gap-2">
                <span v-if="form.duration" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 dark:bg-gray-800 dark:text-gray-200">{{ form.duration }}</span>
                <span v-if="form.is_featured" class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">Featured</span>
                <span class="rounded-full px-3 py-1 text-xs font-bold" :class="form.is_active ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300'">
                  {{ form.is_active ? "Active" : "Inactive" }}
                </span>
              </div>
              <h3 class="text-lg font-bold text-slate-900 dark:text-gray-100">{{ form.title || "Video title" }}</h3>
              <p class="whitespace-pre-line text-sm leading-6 text-slate-600 dark:text-gray-300">{{ form.description || "Video description" }}</p>
            </div>
          </div>
        </aside>
      </form>
    </section>
  </DashboardLayout>
</template>
