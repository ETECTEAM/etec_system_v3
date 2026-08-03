<script setup>
import { computed, onMounted, ref } from "vue";
// import { usePage } from "@inertiajs/vue3";
import FrontendFooter from "@/components/frontend/FrontendFooter.vue";
// import FrontendHeader from "@/components/frontend/FrontendHeader.vue";
import FrontendMenuLinks from "@/components/frontend/FrontendMenuLinks.vue";
import { Link, usePage } from "@inertiajs/vue3";
import { Menu, X } from "@lucide/vue";
import NewsHero from "@/components/frontend/news/NewsHero.vue";
import NewsFilter from "@/components/frontend/news/NewsFilter.vue";
import NewsGrid from "@/components/frontend/news/NewsGrid.vue";
import NewsPagination from "@/components/frontend/news/NewsPagination.vue";

const props = defineProps({
  news: {
    type: Object,
    default: () => ({
      data: [],
      meta: {},
    }),
  },

  filters: {
    type: Object,
    default: () => ({
      search: "",
      featured: null,
      per_page: 12,
    }),
  },
});
const newsHeroSlides = [
  {
    image: "/images/news/news-hero-1.jpg",
    subtitle: "Latest School Updates",
    title: "News & Announcements",
    description:
      "Stay informed about activities and important school announcements.",
  },
  {
    image: "/images/news/news-hero-2.jpg",
    subtitle: "Student Success",
    title: "Celebrating Student Achievements",
    description:
      "Discover inspiring student projects, awards, and accomplishments.",
  },
];
const menuOpen = ref(false);

const page = usePage();

const website = computed(() => page.props.website ?? {});
const settings = computed(() => website.value.settings ?? {});
const menus = computed(() => website.value.menus ?? []);

// News API state
const newsItems = ref([]);
const meta = ref({
  current_page: 1,
  last_page: 1,
  per_page: 12,
  total: 0,
});
const search = ref("");
const featured = ref("");
const loading = ref(false);
const errorMessage = ref("");

async function fetchNews(requestedPage = 1) {
  loading.value = true;
  errorMessage.value = "";

  try {
    const params = new URLSearchParams({
      page: String(requestedPage),
      per_page: String(meta.value.per_page || 12),
    });

    if (search.value.trim()) {
      params.set("search", search.value.trim());
    }

    if (featured.value !== "") {
      params.set("featured", featured.value);
    }

    const response = await fetch(
      `/api/public/news?${params.toString()}`,
      {
        headers: {
          Accept: "application/json",
        },
      },
    );

    if (!response.ok) {
      throw new Error("Failed to load news.");
    }
     const result = await response.json();

    newsItems.value = result.data ?? [];
    meta.value = {
      current_page: Number(result.meta?.current_page ?? 1),
      last_page: Number(result.meta?.last_page ?? 1),
      per_page: Number(result.meta?.per_page ?? 12),
      total: Number(result.meta?.total ?? 0),
      next_page_url: result.meta?.next_page_url ?? null,
    };
  } catch (error) {
    console.error(error);

    newsItems.value = [];
    errorMessage.value =
      error instanceof Error
        ? error.message
        : "Something went wrong while loading news.";
  } finally {
    loading.value = false;
  }
}
function handleSearch(filters) {
  search.value = filters.search ?? "";
  featured.value = filters.featured ?? "";

  fetchNews(1);
}

function handleClearFilters() {
  search.value = "";
  featured.value = "";

  fetchNews(1);
}

function handlePageChange(pageNumber) {
  fetchNews(pageNumber);

  document
    .getElementById("latest-news")
    ?.scrollIntoView({
      behavior: "smooth",
      block: "start",
    });
}
onMounted(() => {
  fetchNews(1);
});


const schoolName = computed(
  () => settings.value.school_name || "ETEC Center",
);
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
    <!-- Mobile Menu Backdrop -->
      <transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="menuOpen" class="fixed inset-0 z-[100] bg-slate-900/60 backdrop-blur-sm lg:hidden" @click="menuOpen = false"></div>
      </transition>

      <!-- Off-canvas Sidebar -->
      <transition enter-active-class="transition duration-300 ease-out transform" enter-from-class="translate-x-full" enter-to-class="translate-x-0" leave-active-class="transition duration-200 ease-in transform" leave-from-class="translate-x-0" leave-to-class="translate-x-full">
        <nav v-if="menuOpen" class="fixed inset-y-0 right-0 z-[110] w-full max-w-sm bg-white shadow-2xl lg:hidden flex flex-col">
          <div class="flex items-center justify-between p-5 border-b border-slate-100">
            <span class="text-xl font-black text-slate-900">Menu</span>
            <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition hover:bg-slate-200 hover:text-slate-900" @click="menuOpen = false">
              <X class="h-5 w-5" />
            </button>
          </div>
          <div class="p-6 overflow-y-auto flex-1 grid gap-2">
            <FrontendMenuLinks :menus="menus" home-active mobile @navigate="menuOpen = false" />
          </div>
        </nav>
      </transition>

    <main class="pt-[5.5rem]">
      <NewsHero :school-name="schoolName" />

      <NewsFilter
        :search="search"
        :featured="featured"
        :loading="loading"
        @submit="handleSearch"
        @clear="handleClearFilters"
      />

      <div
        v-if="errorMessage"
        class="mx-auto mt-8 max-w-7xl px-4 sm:px-6 lg:px-8"
      >
        <div
          class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700"
        >
          {{ errorMessage }}
        </div>
      </div>

      <NewsGrid
        :news-items="newsItems"
        :total="meta.total"
        :loading="loading"
      />

      <NewsPagination
        :meta="meta"
        :loading="loading"
        @change-page="handlePageChange"
      />
    </main>

    <FrontendFooter
      :settings="settings"
      :menus="menus"
    />
  </div>
</template>