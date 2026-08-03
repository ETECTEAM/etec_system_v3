<script setup>
import { computed, ref } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import {
  ArrowLeft,
  CalendarDays,
  Menu,
  Newspaper,
  Star,
  User,
  X,
} from "@lucide/vue";

import FrontendMenuLinks from "@/components/frontend/FrontendMenuLinks.vue";
import FrontendFooter from "@/components/frontend/FrontendFooter.vue";


const menuOpen = ref(false);
const props = defineProps({
  news: {
    type: Object,
    required: true,
  },
});

const page = usePage();

const website = computed(() => page.props.website ?? {});
const settings = computed(() => website.value.settings ?? {});
const menus = computed(() => website.value.menus ?? []);

const schoolName = computed(
  () => settings.value.school_name || "ETEC Center",
);

const activeImages = computed(() =>
  (props.news.images ?? []).filter(
    (image) => image.is_active && image.image_url,
  ),
);

const mainImage = computed(
  () => activeImages.value[0]?.image_url ?? null,
);

const formattedDate = computed(() => {
  if (!props.news.published_at) {
    return "Recently published";
  }

  const date = new Date(`${props.news.published_at}T00:00:00`);

  if (Number.isNaN(date.getTime())) {
    return props.news.published_at;
  }

  return new Intl.DateTimeFormat("en-US", {
    day: "2-digit",
    month: "long",
    year: "numeric",
  }).format(date);
});
</script>

<template>
  <div
  class="min-h-screen bg-[#F4F7FA] font-sans text-slate-900 selection:bg-[#1A66FF]/20 selection:text-[#1A66FF]"
>
    <header
  class="fixed inset-x-0 top-0 z-40 border-b border-slate-200/50 bg-white/80 shadow-[0_10px_35px_rgba(15,23,42,0.08)] backdrop-blur-xl"
>
  <div
    class="mx-auto flex min-h-[5.5rem] max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8"
  >
    <Link
      href="/"
      class="group flex min-w-0 items-center gap-3"
    >
      <span
        class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-[#1A66FF] shadow-[0_5px_15px_rgba(26,102,255,0.3)] transition-all duration-300 group-hover:scale-105"
      >
        <img
          v-if="settings.logo_url"
          :src="settings.logo_url"
          :alt="schoolName"
          class="h-full w-full object-contain p-1"
        />

        <span
          v-else
          class="text-xs font-black text-white"
        >
          ETEC
        </span>
      </span>

      <span
        class="max-w-[12rem] truncate text-lg font-black text-slate-900 transition-colors duration-300 group-hover:text-[#1A66FF] sm:max-w-xs sm:text-xl"
      >
        {{ schoolName }}
      </span>
    </Link>

    <nav class="hidden items-center gap-2 lg:flex">
      <FrontendMenuLinks :menus="menus" />
    </nav>

    <button
      type="button"
      class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#E8F0FF] text-[#1A66FF] transition hover:bg-[#1A66FF] hover:text-white lg:hidden"
      :aria-expanded="menuOpen"
      aria-label="Toggle navigation menu"
      @click="menuOpen = !menuOpen"
    >
      <X v-if="menuOpen" class="h-5 w-5" />
      <Menu v-else class="h-5 w-5" />
    </button>
  </div>

  <nav
    v-if="menuOpen"
    class="mx-4 mb-4 grid gap-1 rounded-2xl border border-slate-200 bg-white p-3 shadow-xl lg:hidden"
  >
    <FrontendMenuLinks
      :menus="menus"
      mobile
      @navigate="menuOpen = false"
    />
  </nav>
</header>

    <main class="pt-[5.5rem]">
      <!-- Hero -->
      <section class="relative overflow-hidden bg-[#0b2550] py-16 text-white">
        <div
          class="absolute -right-24 -top-24 h-80 w-80 rounded-full bg-[#1e5aa8]/30 blur-3xl"
        ></div>

        <div
          class="absolute -bottom-24 -left-24 h-80 w-80 rounded-full bg-[#f4a261]/20 blur-3xl"
        ></div>

        <div class="relative mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
          <Link
            href="/news"
            class="inline-flex items-center gap-2 text-sm font-bold text-blue-100 transition hover:text-[#f4a261]"
          >
            <ArrowLeft class="h-4 w-4" />
            Back to News
          </Link>

          <div class="mt-8 flex flex-wrap items-center gap-3">
            <span
              v-if="news.is_featured"
              class="inline-flex items-center gap-1.5 rounded-full bg-[#f4a261] px-3 py-1.5 text-xs font-black text-slate-950"
            >
              <Star class="h-3.5 w-3.5 fill-current" />
              Featured
            </span>

            <span class="inline-flex items-center gap-2 text-sm text-blue-100">
              <CalendarDays class="h-4 w-4 text-[#f4a261]" />
              {{ formattedDate }}
            </span>

            <span
              v-if="news.author"
              class="inline-flex items-center gap-2 text-sm text-blue-100"
            >
              <User class="h-4 w-4 text-[#f4a261]" />
              {{ news.author }}
            </span>
          </div>

          <h1
            class="mt-6 max-w-4xl text-4xl font-black leading-tight sm:text-5xl lg:text-6xl"
          >
            {{ news.title }}
          </h1>

          <p
            v-if="news.excerpt"
            class="mt-6 max-w-3xl text-base leading-8 text-blue-50 sm:text-lg"
          >
            {{ news.excerpt }}
          </p>
        </div>
      </section>

      <!-- Article -->
      <section class="py-12 sm:py-16">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
          <div
            v-if="mainImage"
            class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-100 shadow-xl"
          >
            <img
              :src="mainImage"
              :alt="news.title"
              class="max-h-[620px] w-full object-cover"
            />
          </div>

          <div
            v-else
            class="grid min-h-80 place-items-center rounded-3xl bg-gradient-to-br from-[#1e5aa8]/10 to-[#f4a261]/20"
          >
            <Newspaper class="h-24 w-24 text-[#1e5aa8]" />
          </div>

          <article
            class="prose prose-slate mt-10 max-w-none prose-headings:font-black prose-headings:text-slate-950 prose-a:text-[#1e5aa8] prose-img:rounded-2xl"
            v-html="news.description"
          ></article>

          <!-- Other Images -->
          <div
            v-if="activeImages.length > 1"
            class="mt-12 grid gap-5 sm:grid-cols-2"
          >
            <img
              v-for="image in activeImages.slice(1)"
              :key="image.id"
              :src="image.image_url"
              :alt="news.title"
              class="aspect-video w-full rounded-2xl object-cover shadow-md"
            />
          </div>

          <div class="mt-12 border-t border-slate-200 pt-8">
            <Link
              href="/news"
              class="inline-flex items-center gap-2 rounded-full bg-[#1e5aa8] px-6 py-3 text-sm font-black text-white transition hover:-translate-y-0.5 hover:bg-[#174981]"
            >
              <ArrowLeft class="h-4 w-4" />
              Back to All News
            </Link>
          </div>
        </div>
      </section>

     <!-- Modern CTA -->
<section class="relative overflow-hidden py-20">
    <!-- Background -->
    <div
        class="absolute inset-0 bg-gradient-to-br from-[#0b2550] via-[#12376b] to-[#1e5aa8]"
    ></div>

    <!-- Decorative circles -->
    <div
        class="absolute -left-20 top-0 h-72 w-72 rounded-full bg-[#f4a261]/10 blur-3xl"
    ></div>

    <div
        class="absolute -right-16 bottom-0 h-80 w-80 rounded-full bg-white/10 blur-3xl"
    ></div>

    <div
        class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
    >
        <div
            class="overflow-hidden rounded-[2rem] border border-white/10 bg-white/5 backdrop-blur-xl"
        >
            <div
                class="grid gap-10 p-10 lg:grid-cols-[1fr_auto] lg:items-center lg:p-14"
            >
                <!-- Left -->
                <div class="max-w-3xl">
                    <span
                        class="inline-flex items-center rounded-full bg-[#f4a261]/15 px-4 py-2 text-sm font-bold text-[#f4a261]"
                    >
                        Continue Exploring
                    </span>

                    <h2
                        class="mt-5 text-3xl font-black leading-tight text-white sm:text-5xl"
                    >
                        Stay Connected with
                        <span class="text-[#f4a261]">
                            {{ schoolName }}
                        </span>
                    </h2>

                    <p
                        class="mt-6 text-lg leading-8 text-blue-100"
                    >
                        Discover more educational news, student achievements,
                        school events, and important announcements. Never miss
                        an update from our community.
                    </p>

                    <!-- Stats -->
                    <div
                        class="mt-8 flex flex-wrap gap-6 text-white"
                    >
                        <div>
                            <p class="text-3xl font-black">
                                {{ activeImages.length }}
                            </p>
                            <span
                                class="text-sm text-blue-200"
                            >
                                Gallery Photos
                            </span>
                        </div>

                        <div class="h-12 w-px bg-white/20"></div>

                        <div>
                            <p class="text-3xl font-black">
                                {{ news.author || "ETEC" }}
                            </p>

                            <span
                                class="text-sm text-blue-200"
                            >
                                Published By
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Right -->
                <div
                    class="flex flex-col gap-4 lg:w-72"
                >
                    <Link
                        href="/news"
                        class="inline-flex items-center justify-center rounded-full bg-[#f4a261] px-7 py-4 text-base font-black text-slate-900 transition-all duration-300 hover:-translate-y-1 hover:bg-[#f7c948] hover:shadow-xl"
                    >
                        View All News
                    </Link>

                    <Link
                        href="/contact"
                        class="inline-flex items-center justify-center rounded-full border border-white/20 bg-white/10 px-7 py-4 text-base font-bold text-white backdrop-blur transition-all duration-300 hover:bg-white hover:text-[#12376b]"
                    >
                        Contact Us
                    </Link>
                </div>
            </div>
        </div>
    </div>
</section>
    </main>

    <FrontendFooter
      :settings="settings"
      :menus="menus"
    />
  </div>
</template>