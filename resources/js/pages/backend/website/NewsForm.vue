<script setup>
import { computed, ref } from "vue";
import { Link, useForm } from "@inertiajs/vue3";
import { Image } from "@lucide/vue";
import DashboardLayout from "@/layouts/DashboardLayout.vue";
import Breadcrumbs from "@/components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "@/components/ui/page-hero/PageHero.vue";

const props = defineProps({
  newsData: {
    type: Object,
    default: null,
  },
});

const isEditing = computed(() => Boolean(props.newsData));
const existingImages = ref([...(props.newsData?.images ?? [])]);
const imagePreviews = ref([]);
const maxImages = 6;

const form = useForm({
  _method: isEditing.value ? "put" : "post",
  title: props.newsData?.title ?? "",
  excerpt: props.newsData?.excerpt ?? "",
  description: props.newsData?.description ?? "",
  published_at: props.newsData?.published_at ?? "",
  sort_order: props.newsData?.sort_order ?? 0,
  is_featured: props.newsData?.is_featured ?? false,
  is_active: props.newsData?.is_active ?? true,
  images: [],
  image_states: Object.fromEntries((props.newsData?.images ?? []).map((image) => [image.id, image.is_active])),
  remove_images: {},
});

const breadcrumbs = [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Website Management", current: true },
  { label: "News Management", href: "/dashboard/website/news" },
  { label: isEditing.value ? "Edit News" : "Create News", current: true },
];

const imageCount = computed(() => existingImages.value.length + form.images.length);
const remainingImageSlots = computed(() => Math.max(maxImages - existingImages.value.length, 0));
const coverImage = computed(() => imagePreviews.value[0] || existingImages.value[0]?.image_url || "");

function chooseImages(event) {
  const files = Array.from(event.target.files ?? []).slice(0, remainingImageSlots.value);
  form.images = files;
  imagePreviews.value.forEach((url) => URL.revokeObjectURL(url));
  imagePreviews.value = files.map((file) => URL.createObjectURL(file));

  if (event.target.files?.length > files.length) {
    event.target.value = "";
  }
}

function toggleImage(image) {
  form.image_states[image.id] = !form.image_states[image.id];
}

function removeImage(image) {
  form.remove_images[image.id] = true;
  existingImages.value = existingImages.value.filter((item) => item.id !== image.id);
}

function submit() {
  const url = isEditing.value
    ? `/dashboard/website/news/${props.newsData.id}`
    : "/dashboard/website/news";

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
        :title="isEditing ? $t('Edit News') : $t('Create News')"
        :description="$t('Create featured news with publishing details and image galleries.')"
      />

      <form class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]" @submit.prevent="submit">
        <div class="space-y-6">
          <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-lg font-bold text-slate-900 dark:text-gray-100">{{ $t('News Details') }}</h2>
            <div class="mt-5 space-y-5">
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Title') }}</label>
                <input v-model="form.title" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" />
                <p v-if="form.errors.title" class="mt-1 text-sm text-rose-600">{{ form.errors.title }}</p>
              </div>

              <div v-if="isEditing" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-gray-800 dark:bg-gray-800/50">
                <span class="font-semibold text-slate-700 dark:text-gray-300">{{ $t('Generated Slug') }}</span>
                <span class="mt-1 block text-slate-500 dark:text-gray-400">/news/{{ props.newsData.slug }}</span>
              </div>

              <div class="grid gap-5 md:grid-cols-2">
                <div>
                  <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Published Date') }}</label>
                  <input v-model="form.published_at" type="date" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" />
                  <p v-if="form.errors.published_at" class="mt-1 text-sm text-rose-600">{{ form.errors.published_at }}</p>
                </div>
                <div>
                  <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Sort Order') }}</label>
                  <input v-model.number="form.sort_order" type="number" min="0" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" />
                  <p v-if="form.errors.sort_order" class="mt-1 text-sm text-rose-600">{{ form.errors.sort_order }}</p>
                </div>
              </div>

              <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Short Description') }}</label>
                <textarea v-model="form.excerpt" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" />
                <p v-if="form.errors.excerpt" class="mt-1 text-sm text-rose-600">{{ form.errors.excerpt }}</p>
              </div>

              <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Description') }}</label>
                <textarea v-model="form.description" rows="9" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" />
                <p v-if="form.errors.description" class="mt-1 text-sm text-rose-600">{{ form.errors.description }}</p>
              </div>

              <div class="grid gap-3 md:grid-cols-2">
                <label class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-800/50">
                  <span class="block text-sm font-semibold text-slate-800 dark:text-gray-100">{{ $t('Published') }}</span>
                  <input v-model="form.is_active" type="checkbox" class="h-5 w-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                </label>

                <label class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-800/50">
                  <span class="block text-sm font-semibold text-slate-800 dark:text-gray-100">{{ $t('Featured') }}</span>
                  <input v-model="form.is_featured" type="checkbox" class="h-5 w-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                </label>
              </div>
            </div>
          </div>

          <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-lg font-bold text-slate-900 dark:text-gray-100">{{ $t('News Images') }}</h2>
            <div class="mt-5">
              <input type="file" multiple accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" :disabled="remainingImageSlots === 0" class="block w-full rounded-xl border border-slate-300 px-4 py-3 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:font-semibold file:text-blue-700 disabled:bg-slate-50 disabled:text-slate-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:disabled:bg-gray-800/50" @change="chooseImages" />
              <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-gray-400">{{ $t(':count/:max images used. JPEG, PNG, WebP up to 4MB each.', { count: imageCount, max: maxImages }) }}</p>
              <p v-if="form.errors.images" class="mt-1 text-sm text-rose-600">{{ form.errors.images }}</p>

              <div v-if="existingImages.length || imagePreviews.length" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="image in existingImages" :key="image.id" class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50 dark:border-gray-700 dark:bg-gray-800">
                  <img :src="image.image_url" :alt="$t('News image')" class="aspect-video w-full object-cover" />
                  <div class="flex items-center justify-between gap-3 p-3">
                    <button type="button" class="rounded-lg px-3 py-1.5 text-xs font-bold" :class="form.image_states[image.id] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600 dark:bg-gray-700 dark:text-gray-300'" @click="toggleImage(image)">
                      {{ form.image_states[image.id] ? $t('Active') : $t('Inactive') }}
                    </button>
                    <button type="button" class="text-xs font-bold text-rose-600 hover:text-rose-700" @click="removeImage(image)">{{ $t('Remove') }}</button>
                  </div>
                </div>

                <div v-for="(url, index) in imagePreviews" :key="url" class="overflow-hidden rounded-xl border border-blue-200 bg-blue-50 dark:border-blue-500/20 dark:bg-blue-500/10">
                  <img :src="url" :alt="$t('New news image')" class="aspect-video w-full object-cover" />
                  <p class="p-3 text-xs font-bold text-blue-700 dark:text-blue-300">{{ $t('New image :number - Active after save', { number: index + 1 }) }}</p>
                </div>
              </div>
            </div>
          </div>

          <div class="flex flex-wrap justify-end gap-3">
            <Link href="/dashboard/website/news" class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-gray-700 dark:text-gray-200">{{ $t('Cancel') }}</Link>
            <button type="submit" :disabled="form.processing" class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50">
              {{ form.processing ? $t("Saving...") : isEditing ? $t("Update News") : $t("Create News") }}
            </button>
          </div>
        </div>

        <aside class="space-y-4">
          <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="mb-3 text-sm font-bold text-slate-900 dark:text-gray-100">{{ $t('News Preview') }}</p>
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50 dark:border-gray-800 dark:bg-gray-800">
              <img v-if="coverImage" :src="coverImage" alt="News preview" class="aspect-video w-full object-cover" />
              <div v-else class="flex aspect-video w-full items-center justify-center text-slate-400">
                <Image class="h-12 w-12" />
              </div>
            </div>
            <div class="mt-4 space-y-2">
              <div class="flex flex-wrap items-center gap-2">
                <span v-if="form.published_at" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 dark:bg-gray-800 dark:text-gray-200">{{ form.published_at }}</span>
                <span v-if="form.is_featured" class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">{{ $t('Featured') }}</span>
                <span class="rounded-full px-3 py-1 text-xs font-bold" :class="form.is_active ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300'">
                  {{ form.is_active ? $t('Published') : $t('Draft') }}
                </span>
              </div>
              <h3 class="text-lg font-bold text-slate-900 dark:text-gray-100">{{ form.title || $t('News title') }}</h3>
              <p class="text-sm leading-6 text-slate-600 dark:text-gray-300">{{ form.excerpt || form.description || $t('News description') }}</p>
            </div>
          </div>
        </aside>
      </form>
    </section>
  </DashboardLayout>
</template>
