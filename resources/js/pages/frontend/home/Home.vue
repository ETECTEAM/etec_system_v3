<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { CalendarDays, GraduationCap, Menu, Search, Users, X } from "@lucide/vue";
import FrontendFooter from "@/components/frontend/FrontendFooter.vue";

const props = defineProps({
  courses: {
    type: Array,
    default: () => [],
  },
});

const page = usePage();
const website = computed(() => page.props.website ?? {});
const settings = computed(() => website.value.settings ?? {});
const menus = computed(() => website.value.menus ?? []);
const menuOpen = ref(false);
const activeHero = ref(0);
let heroTimer = null;

const schoolName = computed(() => settings.value.school_name || "ETEC Center");
const heroSlides = [
  "https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=1800&q=80",
  "https://images.unsplash.com/photo-1588072432836-e10032774350?auto=format&fit=crop&w=1800&q=80",
  "https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&w=1800&q=80",
];
const aboutImage = "https://images.unsplash.com/photo-1588072432836-e10032774350?auto=format&fit=crop&w=1200&q=80";

function linkFor(slug, fallback = `/${slug}`) {
  return menus.value.find((item) => item.slug === slug)?.url ?? fallback;
}

onMounted(() => {
  heroTimer = window.setInterval(() => {
    activeHero.value = (activeHero.value + 1) % heroSlides.length;
  }, 5000);
});

onBeforeUnmount(() => {
  if (heroTimer) window.clearInterval(heroTimer);
});
</script>

<template>
  <div class="min-h-screen bg-white text-slate-900">
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
          <Link href="/" class="rounded-full bg-[#1e5aa8]/10 px-4 py-2 text-sm font-bold text-[#1e5aa8]">Home</Link>
          <Link v-for="item in menus.filter((menu) => menu.slug !== 'home')" :key="item.id" :href="item.url" class="rounded-full px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-[#1e5aa8]/10 hover:text-[#1e5aa8]">
            {{ item.name }}
          </Link>
        </nav>

        <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-700 lg:hidden" aria-label="Toggle menu" @click="menuOpen = !menuOpen">
          <X v-if="menuOpen" class="h-5 w-5" />
          <Menu v-else class="h-5 w-5" />
        </button>
      </div>

      <nav v-if="menuOpen" class="mx-4 mb-4 rounded-xl border border-slate-200 bg-white p-3 shadow-lg lg:hidden">
        <Link href="/" class="block rounded-full bg-[#1e5aa8]/10 px-4 py-2 text-center text-sm font-bold text-[#1e5aa8]">Home</Link>
        <Link v-for="item in menus.filter((menu) => menu.slug !== 'home')" :key="item.id" :href="item.url" class="mt-1 block rounded-full px-4 py-2 text-center text-sm font-bold text-slate-700">
          {{ item.name }}
        </Link>
      </nav>
    </header>

    <main class="pt-20">
      <section class="relative min-h-[min(760px,calc(100vh-80px))] overflow-hidden bg-slate-900 text-white">
        <div
          v-for="(slide, index) in heroSlides"
          :key="slide"
          class="absolute inset-0 bg-cover bg-center transition-opacity duration-1000"
          :class="index === activeHero ? 'opacity-100' : 'opacity-0'"
          :style="{ backgroundImage: `url(${slide})` }"
        ></div>
        <div class="absolute inset-0 bg-gradient-to-r from-[#091f42]/85 to-[#091f42]/40"></div>
        <div class="relative mx-auto flex min-h-[min(760px,calc(100vh-80px))] max-w-7xl flex-col justify-center px-4 py-20 sm:px-6 lg:px-8">
          <p class="text-sm font-bold uppercase tracking-widest text-[#f4a261]">Welcome to our school</p>
          <h1 class="mt-4 max-w-4xl text-4xl font-black leading-tight sm:text-5xl lg:text-7xl">{{ schoolName }}</h1>
          <p class="mt-5 max-w-2xl text-base leading-8 text-blue-50 sm:text-lg">
            Learn from real courses managed in the system, with public pages and heroes controlled by the dashboard.
          </p>
          <div class="mt-8 flex flex-wrap gap-3">
            <Link :href="linkFor('course', '/course')" class="rounded-full bg-[#f4a261] px-6 py-3 text-sm font-bold text-slate-950 shadow-lg transition hover:-translate-y-0.5 hover:bg-[#f7c948]">Explore Courses</Link>
            <Link :href="linkFor('contact', '/contact')" class="rounded-full bg-white px-6 py-3 text-sm font-bold text-[#174981] shadow-lg transition hover:-translate-y-0.5 hover:bg-blue-50">Contact Us</Link>
          </div>
        </div>
        <div class="absolute bottom-7 left-1/2 flex -translate-x-1/2 gap-2">
          <button v-for="(_, index) in heroSlides" :key="index" type="button" class="h-1.5 rounded-full transition-all" :class="index === activeHero ? 'w-9 bg-[#f4a261]' : 'w-5 bg-white/50'" :aria-label="`Hero slide ${index + 1}`" @click="activeHero = index"></button>
        </div>
      </section>

      <section class="py-16">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
          <article class="rounded-xl border border-slate-200 bg-white p-6 text-center shadow-sm">
            <Users class="mx-auto h-7 w-7 text-[#1e5aa8]" />
            <p class="mt-4 text-4xl font-black text-[#1e5aa8]">Active</p>
            <h3 class="mt-2 text-base font-bold text-slate-800">Student Learning</h3>
          </article>
          <article class="rounded-xl border border-slate-200 bg-white p-6 text-center shadow-sm">
            <GraduationCap class="mx-auto h-7 w-7 text-[#1e5aa8]" />
            <p class="mt-4 text-4xl font-black text-[#1e5aa8]">{{ courses.length }}</p>
            <h3 class="mt-2 text-base font-bold text-slate-800">Available Courses</h3>
          </article>
          <article class="rounded-xl border border-slate-200 bg-white p-6 text-center shadow-sm">
            <Search class="mx-auto h-7 w-7 text-[#1e5aa8]" />
            <p class="mt-4 text-4xl font-black text-[#1e5aa8]">Public</p>
            <h3 class="mt-2 text-base font-bold text-slate-800">Website Pages</h3>
          </article>
          <article class="rounded-xl border border-slate-200 bg-white p-6 text-center shadow-sm">
            <CalendarDays class="mx-auto h-7 w-7 text-[#1e5aa8]" />
            <p class="mt-4 text-4xl font-black text-[#1e5aa8]">ETEC</p>
            <h3 class="mt-2 text-base font-bold text-slate-800">Training Center</h3>
          </article>
        </div>
      </section>

      <section class="bg-slate-50 py-20">
        <div class="mx-auto grid max-w-7xl gap-11 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
          <img :src="aboutImage" alt="Modern classroom" class="h-full min-h-96 w-full rounded-xl object-cover shadow-[0_14px_35px_rgba(30,90,168,0.12)]" />
          <div class="flex flex-col justify-center">
            <p class="text-sm font-bold uppercase tracking-widest text-[#f4a261]">About our school</p>
            <h2 class="mt-3 text-3xl font-black text-slate-950 sm:text-4xl">Public website connected to your system</h2>
            <p class="mt-5 leading-8 text-slate-600">
              The page route, menu, logo, and hero are managed from Website Management. Course content comes from your existing Course module.
            </p>
            <Link :href="linkFor('about', '/about')" class="mt-5 inline-flex w-fit rounded-full bg-[#1e5aa8] px-6 py-3 text-sm font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-[#174981]">Learn More</Link>
          </div>
        </div>
      </section>

      <section class="py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
              <p class="text-sm font-bold uppercase tracking-widest text-[#f4a261]">Programs</p>
              <h2 class="mt-2 text-3xl font-black text-slate-950">Featured Courses</h2>
            </div>
            <Link :href="linkFor('course', '/course')" class="font-extrabold text-[#1e5aa8] hover:text-[#f4a261]">See All</Link>
          </div>

          <div v-if="courses.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <article v-for="course in courses" :key="course.id" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-[0_14px_35px_rgba(30,90,168,0.12)]">
              <img v-if="course.thumbnail_url" :src="course.thumbnail_url" :alt="course.title" class="aspect-video w-full object-cover" />
              <div v-else class="grid aspect-video place-items-center bg-[#1e5aa8]/10 text-xl font-black text-[#1e5aa8]">{{ course.title?.charAt(0) ?? "C" }}</div>
              <div class="p-6">
                <span v-if="course.category || course.track" class="rounded-full bg-[#f4a261]/20 px-3 py-1 text-xs font-bold text-[#8a4b12]">{{ course.category || course.track }}</span>
                <h3 class="mt-4 text-xl font-black text-slate-950">{{ course.title }}</h3>
                <p class="mt-3 text-sm font-bold capitalize text-slate-500">{{ course.level || "Course" }}</p>
                <p class="mt-1 text-sm font-bold text-slate-500">{{ Number(course.price || 0) > 0 ? `$${course.price}` : "Contact for price" }}</p>
              </div>
            </article>
          </div>

          <div v-else class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
            <p class="font-bold text-slate-700">No active courses yet.</p>
            <p class="mt-2 text-sm text-slate-500">Create active courses in the Course module and they will display here.</p>
          </div>
        </div>
      </section>

      <section class="bg-slate-50 py-20">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
          <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8">
            <p class="text-sm font-bold uppercase tracking-widest text-[#f4a261]">Updates</p>
            <h2 class="mt-2 text-2xl font-black text-slate-950">News module ready</h2>
            <p class="mt-3 text-slate-600">When a real news model is added, this section can read from it instead of static content.</p>
          </div>
          <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8">
            <p class="text-sm font-bold uppercase tracking-widest text-[#f4a261]">Calendar</p>
            <h2 class="mt-2 text-2xl font-black text-slate-950">Events module ready</h2>
            <p class="mt-3 text-slate-600">Public event cards should connect to an events table when available.</p>
          </div>
          <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8">
            <p class="text-sm font-bold uppercase tracking-widest text-[#f4a261]">Videos</p>
            <h2 class="mt-2 text-2xl font-black text-slate-950">Videos module ready</h2>
            <p class="mt-3 text-slate-600">Video content should come from a real video module or media table later.</p>
          </div>
        </div>
      </section>

      <section class="bg-gradient-to-r from-[#1e5aa8] to-[#43a4d8] py-20 text-white">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[1fr_auto] lg:items-center lg:px-8">
          <div>
            <h2 class="text-3xl font-black">Start Your IT Learning Journey With {{ schoolName }}</h2>
            <p class="mt-3 max-w-2xl text-blue-50">Explore courses from the system and contact the school for more information.</p>
          </div>
          <div class="flex flex-wrap gap-3">
            <Link :href="linkFor('course', '/course')" class="rounded-full bg-[#f4a261] px-6 py-3 text-sm font-bold text-slate-950">View Courses</Link>
            <Link :href="linkFor('contact', '/contact')" class="rounded-full bg-white px-6 py-3 text-sm font-bold text-[#174981]">Contact Us</Link>
          </div>
        </div>
      </section>
    </main>

    <FrontendFooter :settings="settings" :menus="menus" />
  </div>
</template>
