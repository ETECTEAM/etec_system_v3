<script setup>
import { computed, ref } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import axios from "axios";
import { Mail, MapPin, Menu, Phone, Search, X } from "@lucide/vue";
import FrontendFooter from "@/components/frontend/FrontendFooter.vue";

const props = defineProps({
  pageData: {
    type: Object,
    required: true,
  },
  preview: {
    type: Boolean,
    default: false,
  },
  courses: {
    type: [Array, Object],
    default: () => ({
      data: [],
      meta: null,
    }),
  },
});

const inertiaPage = usePage();
const website = computed(() => inertiaPage.props.website ?? {});
const settings = computed(() => website.value.settings ?? {});
const menus = computed(() => website.value.menus ?? []);
const menuOpen = ref(false);
const courseItems = ref(Array.isArray(props.courses) ? props.courses : props.courses?.data ?? []);
const courseMeta = ref(Array.isArray(props.courses) ? null : props.courses?.meta ?? null);
const loadingMoreCourses = ref(false);

const schoolName = computed(() => settings.value.school_name || "ETEC Center");
const hero = computed(() => props.pageData?.hero ?? null);
const activeSlug = computed(() => props.pageData?.slug ?? "");
const isCoursePage = computed(() => ["course", "courses"].includes(activeSlug.value));
const fallbackHero = "https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&w=1800&q=80";

const alignmentClass = computed(() => ({
  left: "items-start text-left",
  center: "items-center text-center",
  right: "items-end text-right",
})[hero.value?.text_alignment ?? "center"]);

const hasMoreCourses = computed(() => {
  if (!courseMeta.value) {
    return false;
  }

  return Number(courseMeta.value.current_page) < Number(courseMeta.value.last_page);
});

const loadMoreCourses = async () => {
  if (loadingMoreCourses.value || !hasMoreCourses.value) {
    return;
  }

  loadingMoreCourses.value = true;

  try {
    const nextPage = Number(courseMeta.value.current_page) + 1;
    const response = await axios.get(`/${activeSlug.value}/load-more`, {
      params: {
        page: nextPage,
      },
    });

    courseItems.value = [
      ...courseItems.value,
      ...(response.data?.data ?? []),
    ];
    courseMeta.value = response.data?.meta ?? courseMeta.value;
  } finally {
    loadingMoreCourses.value = false;
  }
};
</script>

<template>
  <div class="min-h-screen bg-white text-slate-900">
    <div v-if="preview" class="bg-amber-100 px-4 py-2 text-center text-sm font-semibold text-amber-900">Preview mode</div>

    <header class="fixed left-0 right-0 top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur">
      <div class="mx-auto flex min-h-20 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <Link href="/" class="flex min-w-0 items-center gap-3">
          <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-[#25258f] text-xs font-black text-white">
            <img v-if="settings.logo_url" :src="settings.logo_url" :alt="schoolName" class="h-full w-full object-contain" />
            <span v-else>ETEC</span>
          </span>
          <span class="truncate text-base font-extrabold text-slate-900">{{ schoolName }}</span>
        </Link>

        <nav class="hidden items-center gap-1 lg:flex">
          <Link href="/" class="rounded-full px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-[#1e5aa8]/10 hover:text-[#1e5aa8]">Home</Link>
          <Link v-for="menu in menus.filter((item) => item.slug !== 'home')" :key="menu.id" :href="menu.url" class="rounded-full px-4 py-2 text-sm font-bold transition" :class="menu.slug === activeSlug ? 'bg-[#1e5aa8]/10 text-[#1e5aa8]' : 'text-slate-700 hover:bg-[#1e5aa8]/10 hover:text-[#1e5aa8]'">
            {{ menu.name }}
          </Link>
        </nav>

        <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-700 lg:hidden" aria-label="Toggle menu" @click="menuOpen = !menuOpen">
          <X v-if="menuOpen" class="h-5 w-5" />
          <Menu v-else class="h-5 w-5" />
        </button>
      </div>

      <nav v-if="menuOpen" class="mx-4 mb-4 rounded-xl border border-slate-200 bg-white p-3 shadow-lg lg:hidden">
        <Link href="/" class="block rounded-full px-4 py-2 text-center text-sm font-bold text-slate-700">Home</Link>
        <Link v-for="menu in menus.filter((item) => item.slug !== 'home')" :key="menu.id" :href="menu.url" class="mt-1 block rounded-full px-4 py-2 text-center text-sm font-bold" :class="menu.slug === activeSlug ? 'bg-[#1e5aa8]/10 text-[#1e5aa8]' : 'text-slate-700'">
          {{ menu.name }}
        </Link>
      </nav>
    </header>

    <section class="relative mt-20 min-h-80 bg-slate-900 bg-cover bg-center" :style="{ backgroundImage: `url(${hero?.background_image_url || fallbackHero})` }">
      <div class="absolute inset-0 bg-gradient-to-r from-[#091f42]/85 to-[#091f42]/45"></div>
      <div class="relative mx-auto flex min-h-[420px] max-w-7xl flex-col justify-center px-4 py-20 text-white sm:px-6 lg:px-8" :class="alignmentClass">
        <p class="mb-3 text-sm font-bold uppercase tracking-widest text-[#f4a261]">{{ hero?.subtitle || schoolName }}</p>
        <h1 class="max-w-3xl text-4xl font-black leading-tight sm:text-5xl">{{ hero?.title || pageData.title }}</h1>
        <p class="mt-5 max-w-2xl text-lg text-blue-50">{{ hero?.description || "This public route uses Vue content and dashboard-managed page settings." }}</p>
        <div class="mt-8 flex flex-wrap gap-3" :class="{ 'justify-center': hero?.text_alignment === 'center', 'justify-end': hero?.text_alignment === 'right' }">
          <Link v-if="hero?.primary_button_text && hero?.primary_button_url" :href="hero.primary_button_url" class="rounded-full bg-[#f4a261] px-6 py-3 text-sm font-bold text-slate-950 transition hover:bg-[#f7c948]">{{ hero.primary_button_text }}</Link>
          <Link v-if="hero?.secondary_button_text && hero?.secondary_button_url" :href="hero.secondary_button_url" class="rounded-full bg-white px-6 py-3 text-sm font-bold text-[#174981] transition hover:bg-blue-50">{{ hero.secondary_button_text }}</Link>
        </div>
      </div>
    </section>

    <main class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
      <section v-if="activeSlug === 'about'" class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
        <img src="https://images.unsplash.com/photo-1577896851231-70ef18881754?auto=format&fit=crop&w=1200&q=80" alt="School classroom" class="h-full min-h-96 rounded-xl object-cover shadow-[0_14px_35px_rgba(30,90,168,0.12)]" />
        <div>
          <p class="text-sm font-bold uppercase tracking-widest text-[#f4a261]">About our school</p>
          <h2 class="mt-3 text-3xl font-black text-slate-950">Custom Vue content for this route</h2>
          <p class="mt-5 leading-8 text-slate-600">This page is separated by route and can be developed as its own Vue section. Admin users manage only the route, menu, status, and hero.</p>
        </div>
      </section>

      <section v-else-if="isCoursePage" class="space-y-8">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-center rounded-xl border border-slate-200 px-4 py-3 text-slate-500">
            <Search class="mr-2 h-5 w-5" /> Courses below come from your Course module.
          </div>
        </div>

        <div v-if="courseItems.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <article v-for="course in courseItems" :key="course.id" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-[0_14px_35px_rgba(30,90,168,0.12)]">
            <img v-if="course.thumbnail_url" :src="course.thumbnail_url" :alt="course.title" class="aspect-video w-full object-cover" />
            <div v-else class="grid aspect-video place-items-center bg-[#1e5aa8]/10 text-xl font-black text-[#1e5aa8]">{{ course.title?.charAt(0) ?? "C" }}</div>
            <div class="p-6">
              <span v-if="course.category || course.track" class="rounded-full bg-[#f4a261]/20 px-3 py-1 text-xs font-bold text-[#8a4b12]">{{ course.category || course.track }}</span>
              <h3 class="mt-4 text-xl font-black text-slate-950">{{ course.title }}</h3>
              <p class="mt-3 text-sm font-bold capitalize text-slate-500">{{ course.level || "Course" }}</p>
              <p class="mt-1 text-sm font-bold text-slate-500">{{ Number(course.price || 0) > 0 ? `$${course.price}` : "Contact for price" }}</p>
              <Link href="/register" class="mt-5 inline-flex rounded-full bg-[#1e5aa8] px-5 py-2.5 text-sm font-bold text-white">Register Now</Link>
            </div>
          </article>
        </div>

        <div v-if="courseItems.length && hasMoreCourses" class="flex justify-center pt-2">
          <button type="button" class="rounded-full bg-[#f4a261] px-6 py-3 text-sm font-black text-slate-950 shadow-[0_12px_28px_rgba(244,162,97,0.28)] transition hover:bg-[#f7c948] disabled:cursor-not-allowed disabled:opacity-70" :disabled="loadingMoreCourses" @click="loadMoreCourses">
            {{ loadingMoreCourses ? "Loading..." : "Load More" }}
          </button>
        </div>

        <div v-if="!courseItems.length" class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
          <p class="font-bold text-slate-700">No active courses yet.</p>
          <p class="mt-2 text-sm text-slate-500">Create active courses in the Course module and they will display here.</p>
        </div>
      </section>

      <section v-else-if="activeSlug === 'contact'" class="grid gap-8 lg:grid-cols-[0.8fr_1.2fr]">
        <div class="space-y-4">
          <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"><Phone class="h-5 w-5 text-[#1e5aa8]" /><p class="mt-3 font-black text-slate-950">Phone</p><p class="text-slate-600">+855 12 345 678</p></div>
          <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"><Mail class="h-5 w-5 text-[#1e5aa8]" /><p class="mt-3 font-black text-slate-950">Email</p><p class="text-slate-600">info@etec-center.edu.kh</p></div>
          <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"><MapPin class="h-5 w-5 text-[#1e5aa8]" /><p class="mt-3 font-black text-slate-950">Address</p><p class="text-slate-600">Phnom Penh, Cambodia</p></div>
        </div>
        <form class="rounded-xl border border-slate-200 bg-slate-50 p-6">
          <h2 class="text-2xl font-black text-slate-950">Contact Form</h2>
          <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <input class="rounded-xl border border-slate-300 px-4 py-3 text-sm" placeholder="Full name" />
            <input class="rounded-xl border border-slate-300 px-4 py-3 text-sm" placeholder="Email" />
            <input class="rounded-xl border border-slate-300 px-4 py-3 text-sm" placeholder="Phone" />
            <input class="rounded-xl border border-slate-300 px-4 py-3 text-sm" placeholder="Subject" />
            <textarea class="min-h-32 rounded-xl border border-slate-300 px-4 py-3 text-sm sm:col-span-2" placeholder="Message"></textarea>
            <button type="button" class="rounded-full bg-[#1e5aa8] px-6 py-3 text-sm font-bold text-white sm:col-span-2">Send Message</button>
          </div>
        </form>
      </section>

      <article v-else class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-8">
        <p class="text-sm font-bold uppercase tracking-widest text-[#f4a261]">{{ activeSlug || 'Page' }}</p>
        <h2 class="mt-3 text-3xl font-black text-slate-950">{{ pageData.title }}</h2>
        <p class="mt-4 max-w-3xl leading-8 text-slate-600">This route is ready for its own Vue content. No static sample objects are used.</p>
      </article>
    </main>
<!-- 
    <section class="bg-gradient-to-r from-[#1e5aa8] to-[#43a4d8] py-16 text-white">
      <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
        <div>
          <h2 class="text-3xl font-black">Start Your IT Learning Journey With {{ schoolName }}</h2>
          <p class="mt-3 text-blue-50">Explore real courses from the system and contact the school for more information.</p>
        </div>
        <Link href="/contact" class="w-fit rounded-full bg-[#f4a261] px-6 py-3 text-sm font-bold text-slate-950">Contact Us</Link>
      </div>
    </section> -->

    <FrontendFooter :settings="settings" :menus="menus" />
  </div>
</template>
