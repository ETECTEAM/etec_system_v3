<script setup>
import { computed, ref, watch } from "vue";
import { Link, useForm } from "@inertiajs/vue3";
import DashboardLayout from "@/layouts/DashboardLayout.vue";
import Breadcrumbs from "@/components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "@/components/ui/page-hero/PageHero.vue";

const props = defineProps({
  pageData: {
    type: Object,
    default: null,
  },
});

const isEditing = computed(() => Boolean(props.pageData));
const imagePreview = ref(props.pageData?.hero?.background_image_url ?? "");

const form = useForm({
  _method: isEditing.value ? "put" : "post",
  title: props.pageData?.title ?? "",
  slug: props.pageData?.slug ?? "",
  is_active: props.pageData?.is_active ?? true,
  hero_is_active: props.pageData?.hero?.is_active ?? false,
  hero_title: props.pageData?.hero?.title ?? "",
  hero_subtitle: props.pageData?.hero?.subtitle ?? "",
  hero_description: props.pageData?.hero?.description ?? "",
  hero_background_image: null,
  remove_hero_image: false,
  primary_button_text: props.pageData?.hero?.primary_button_text ?? "",
  primary_button_url: props.pageData?.hero?.primary_button_url ?? "",
  secondary_button_text: props.pageData?.hero?.secondary_button_text ?? "",
  secondary_button_url: props.pageData?.hero?.secondary_button_url ?? "",
  overlay_opacity: props.pageData?.hero?.overlay_opacity ?? 50,
  text_alignment: props.pageData?.hero?.text_alignment ?? "center",
});

const breadcrumbs = [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Website Management", current: true },
  { label: "Page Management", href: "/dashboard/website/pages" },
  { label: isEditing.value ? "Edit Page" : "Create Page", current: true },
];

function slugify(value) {
  return String(value ?? "")
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

watch(
  () => form.title,
  (title) => {
    if (!isEditing.value && !form.slug) {
      form.slug = slugify(title);
    }
  },
);

watch(
  () => props.pageData?.hero?.background_image_url,
  (url) => {
    if (!form.hero_background_image && !form.remove_hero_image) {
      imagePreview.value = url ?? "";
    }
  },
);

function chooseHeroImage(event) {
  const file = event.target.files?.[0] ?? null;
  form.hero_background_image = file;
  form.remove_hero_image = false;
  imagePreview.value = file ? URL.createObjectURL(file) : props.pageData?.hero?.background_image_url ?? "";
}

function removeHeroImage() {
  form.hero_background_image = null;
  form.remove_hero_image = true;
  imagePreview.value = "";
}

function submit() {
  const url = isEditing.value
    ? `/dashboard/website/pages/${props.pageData.id}`
    : "/dashboard/website/pages";

  form.slug = slugify(form.slug || form.title);
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
        :title="isEditing ? 'Edit Page' : 'Create Page'"
        description="Manage page routing, status, and the hero section shown on the public website."
      />

      <form class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]" @submit.prevent="submit">
        <div class="space-y-6">
          <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-lg font-bold text-slate-900 dark:text-gray-100">Page Details</h2>
            <div class="mt-5 space-y-5">
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">Page Title</label>
                <input v-model="form.title" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" />
                <p v-if="form.errors.title" class="mt-1 text-sm text-rose-600">{{ form.errors.title }}</p>
              </div>

              <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">Page Slug</label>
                <div class="flex rounded-xl border border-slate-300 focus-within:border-blue-600 focus-within:ring-2 focus-within:ring-blue-100 dark:border-gray-700 dark:focus-within:ring-blue-500/20">
                  <span class="border-r border-slate-200 px-4 py-3 text-sm text-slate-500 dark:border-gray-700 dark:text-gray-400">/</span>
                  <input v-model="form.slug" type="text" class="min-w-0 flex-1 rounded-r-xl border-0 px-4 py-3 text-sm outline-none dark:bg-gray-800 dark:text-gray-100" @blur="form.slug = slugify(form.slug)" />
                </div>
                <p v-if="form.errors.slug" class="mt-1 text-sm text-rose-600">{{ form.errors.slug }}</p>
              </div>

              <label class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-800/50">
                <span>
                  <span class="block text-sm font-semibold text-slate-800 dark:text-gray-100">Active Page</span>
                  <span class="block text-xs text-slate-500 dark:text-gray-400">Inactive pages are not publicly accessible.</span>
                </span>
                <input v-model="form.is_active" type="checkbox" class="h-5 w-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
              </label>
            </div>
          </div>

          <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between gap-4">
              <h2 class="text-lg font-bold text-slate-900 dark:text-gray-100">Hero Section</h2>
              <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-gray-300">
                <input v-model="form.hero_is_active" type="checkbox" class="h-5 w-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                Enable Hero
              </label>
            </div>

            <div v-if="form.hero_is_active" class="mt-5 grid gap-5 md:grid-cols-2">
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">Hero Title</label>
                <input v-model="form.hero_title" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" />
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">Hero Subtitle</label>
                <input v-model="form.hero_subtitle" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" />
              </div>
              <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">Hero Description</label>
                <textarea v-model="form.hero_description" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" />
              </div>
              <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">Hero Background Image</label>
                <input type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="block w-full rounded-xl border border-slate-300 px-4 py-3 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:font-semibold file:text-blue-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200" @change="chooseHeroImage" />
                <button v-if="imagePreview" type="button" class="mt-2 text-sm font-semibold text-rose-600 hover:text-rose-700" @click="removeHeroImage">Remove current hero image</button>
                <p v-if="form.errors.hero_background_image" class="mt-1 text-sm text-rose-600">{{ form.errors.hero_background_image }}</p>
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">Primary Button Text</label>
                <input v-model="form.primary_button_text" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" />
                <p v-if="form.errors.primary_button_text" class="mt-1 text-sm text-rose-600">{{ form.errors.primary_button_text }}</p>
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">Primary Button URL</label>
                <input v-model="form.primary_button_url" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" placeholder="/courses" />
                <p v-if="form.errors.primary_button_url" class="mt-1 text-sm text-rose-600">{{ form.errors.primary_button_url }}</p>
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">Secondary Button Text</label>
                <input v-model="form.secondary_button_text" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" />
                <p v-if="form.errors.secondary_button_text" class="mt-1 text-sm text-rose-600">{{ form.errors.secondary_button_text }}</p>
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">Secondary Button URL</label>
                <input v-model="form.secondary_button_url" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" placeholder="/contact-us" />
                <p v-if="form.errors.secondary_button_url" class="mt-1 text-sm text-rose-600">{{ form.errors.secondary_button_url }}</p>
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">Overlay Opacity: {{ form.overlay_opacity }}%</label>
                <input v-model="form.overlay_opacity" type="range" min="0" max="100" class="w-full accent-blue-600" />
                <p v-if="form.errors.overlay_opacity" class="mt-1 text-sm text-rose-600">{{ form.errors.overlay_opacity }}</p>
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">Text Alignment</label>
                <select v-model="form.text_alignment" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                  <option value="left">Left</option>
                  <option value="center">Center</option>
                  <option value="right">Right</option>
                </select>
              </div>
            </div>
          </div>

          <div class="flex flex-wrap justify-end gap-3">
            <Link href="/dashboard/website/pages" class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-gray-700 dark:text-gray-200">Cancel</Link>
            <button type="submit" :disabled="form.processing" class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50">
              {{ form.processing ? "Saving..." : isEditing ? "Update Page" : "Create Page" }}
            </button>
          </div>
        </div>

        <aside class="space-y-4">
          <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="mb-3 text-sm font-bold text-slate-900 dark:text-gray-100">Hero Preview</p>
            <div
              class="relative min-h-80 overflow-hidden rounded-xl bg-blue-700 bg-cover bg-center"
              :style="imagePreview ? { backgroundImage: `url(${imagePreview})` } : {}"
            >
              <div class="absolute inset-0" :style="{ backgroundColor: `rgba(0,0,0,${Number(form.overlay_opacity || 0) / 100})` }"></div>
              <div
                class="relative flex min-h-80 flex-col justify-center p-6 text-white"
                :class="{
                  'items-start text-left': form.text_alignment === 'left',
                  'items-center text-center': form.text_alignment === 'center',
                  'items-end text-right': form.text_alignment === 'right',
                }"
              >
                <p v-if="form.hero_subtitle" class="mb-2 text-xs font-semibold uppercase tracking-widest text-blue-100">{{ form.hero_subtitle }}</p>
                <h3 class="max-w-sm text-3xl font-bold">{{ form.hero_title || form.title || "Hero Title" }}</h3>
                <p v-if="form.hero_description" class="mt-3 max-w-sm text-sm text-blue-50">{{ form.hero_description }}</p>
                <div class="mt-5 flex flex-wrap gap-2" :class="{ 'justify-end': form.text_alignment === 'right', 'justify-center': form.text_alignment === 'center' }">
                  <span v-if="form.primary_button_text && form.primary_button_url" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-blue-700">{{ form.primary_button_text }}</span>
                  <span v-if="form.secondary_button_text && form.secondary_button_url" class="rounded-lg border border-white/70 px-4 py-2 text-sm font-semibold text-white">{{ form.secondary_button_text }}</span>
                </div>
              </div>
            </div>
          </div>
        </aside>
      </form>
    </section>
  </DashboardLayout>
</template>
