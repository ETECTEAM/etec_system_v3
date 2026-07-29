<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { CalendarDays, ChevronLeft, ChevronRight, GraduationCap, Menu, Search, Users, X } from "@lucide/vue";
import FrontendFooter from "@/components/frontend/FrontendFooter.vue";
import FrontendMenuLinks from "@/components/frontend/FrontendMenuLinks.vue";

const props = defineProps({
  courses: {
    type: Array,
    default: () => [],
  },
  pageData: {
    type: Object,
    default: null,
  },
});

const page = usePage();
const website = computed(() => page.props.website ?? {});
const settings = computed(() => website.value.settings ?? {});
const menus = computed(() => website.value.menus ?? []);
const menuOpen = ref(false);
const activeHero = ref(0);
const dragStartX = ref(null);
const dragOffsetX = ref(0);
const isHeroDragging = ref(false);
let heroTimer = null;

const schoolName = computed(() => settings.value.school_name || "ETEC Center");
const hero = computed(() => props.pageData?.hero ?? null);
const heroEnabled = computed(() => Boolean(hero.value?.is_active));
const activeDashboardHeroImages = computed(() =>
  (hero.value?.images ?? []).filter((image) => image.is_active && image.image_url).slice(0, 3)
);
const heroSlides = [
  "https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=1800&q=80",
  "https://images.unsplash.com/photo-1588072432836-e10032774350?auto=format&fit=crop&w=1800&q=80",
  "https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&w=1800&q=80",
];
const aboutImage = "https://images.unsplash.com/photo-1588072432836-e10032774350?auto=format&fit=crop&w=1200&q=80";
const heroSlideUrls = computed(() =>
  heroEnabled.value && activeDashboardHeroImages.value.length
    ? activeDashboardHeroImages.value.map((image) => image.image_url)
    : heroEnabled.value && hero.value?.background_image_url
      ? [hero.value.background_image_url]
      : heroSlides
);
const heroSlideCount = computed(() => heroSlideUrls.value.length);
const heroTrackStyle = computed(() => ({
  transform: `translate3d(calc(-${(activeHero.value % heroSlideCount.value) * 100}% + ${dragOffsetX.value}px), 0, 0)`,
  transitionDuration: isHeroDragging.value ? "0ms" : "900ms",
}));
const heroOverlayStyle = computed(() => {
  if (!heroEnabled.value) {
    return {};
  }

  return {
    backgroundColor: `rgba(9,31,66,${Number(hero.value?.overlay_opacity ?? 55) / 100})`,
  };
});
const heroAlignmentClass = computed(() => ({
  left: "items-start text-left",
  center: "items-center text-center",
  right: "items-end text-right",
})[heroEnabled.value ? hero.value?.text_alignment ?? "left" : "left"]);
const heroButtonAlignmentClass = computed(() => ({
  left: "",
  center: "justify-center",
  right: "justify-end",
})[heroEnabled.value ? hero.value?.text_alignment ?? "left" : "left"]);
const heroSubtitle = computed(() => heroEnabled.value && hero.value?.subtitle ? hero.value.subtitle : "Welcome to our school");
const heroTitle = computed(() => heroEnabled.value && hero.value?.title ? hero.value.title : schoolName.value);
const heroDescription = computed(() =>
  heroEnabled.value && hero.value?.description
    ? hero.value.description
    : "Learn from real courses managed in the system, with public pages and heroes controlled by the dashboard."
);
const primaryButtonText = computed(() => heroEnabled.value && hero.value?.primary_button_text ? hero.value.primary_button_text : "Explore Courses");
const primaryButtonUrl = computed(() => heroEnabled.value && hero.value?.primary_button_url ? hero.value.primary_button_url : linkFor("course", "/course"));
const secondaryButtonText = computed(() => heroEnabled.value && hero.value?.secondary_button_text ? hero.value.secondary_button_text : "Contact Us");
const secondaryButtonUrl = computed(() => heroEnabled.value && hero.value?.secondary_button_url ? hero.value.secondary_button_url : linkFor("contact", "/contact"));

function linkFor(slug, fallback = `/${slug}`) {
  return menus.value.find((item) => item.slug === slug)?.url ?? fallback;
}

function startHeroTimer() {
  if (heroTimer) window.clearInterval(heroTimer);

  if (heroSlideCount.value <= 1) {
    return;
  }

  heroTimer = window.setInterval(() => {
    activeHero.value = (activeHero.value + 1) % heroSlideCount.value;
  }, 5000);
}

function goToHero(index) {
  activeHero.value = index;
  startHeroTimer();
}

function nextHero() {
  goToHero((activeHero.value + 1) % heroSlideCount.value);
}

function previousHero() {
  goToHero((activeHero.value - 1 + heroSlideCount.value) % heroSlideCount.value);
}

function startHeroDrag(event) {
  if (heroSlideCount.value <= 1) return;

  dragStartX.value = event.clientX ?? event.touches?.[0]?.clientX ?? null;
  dragOffsetX.value = 0;
  isHeroDragging.value = true;
}

function moveHeroDrag(event) {
  if (dragStartX.value === null) return;

  const currentX = event.clientX ?? event.touches?.[0]?.clientX ?? dragStartX.value;
  dragOffsetX.value = Math.max(Math.min(currentX - dragStartX.value, 90), -90);
}

function endHeroDrag() {
  if (dragStartX.value === null) return;

  if (dragOffsetX.value <= -35) {
    nextHero();
  } else if (dragOffsetX.value >= 35) {
    previousHero();
  }

  dragStartX.value = null;
  dragOffsetX.value = 0;
  isHeroDragging.value = false;
}

onMounted(() => {
  startHeroTimer();
});

onBeforeUnmount(() => {
  if (heroTimer) window.clearInterval(heroTimer);
});
</script>

<template>
  <div class="min-h-screen bg-white text-slate-900 [--public-header-height:5rem]">
    <header class="fixed left-0 right-0 top-0 z-40 border-b border-slate-200/70 bg-white/90 shadow-sm backdrop-blur-xl">
      <div class="mx-auto flex min-h-20 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <Link href="/" class="flex min-w-0 items-center gap-3 rounded-full pr-2 transition hover:opacity-90">
          <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-[#25258f] text-xs font-black text-white shadow-sm">
            <img v-if="settings.logo_url" :src="settings.logo_url" :alt="schoolName" class="h-full w-full object-contain" />
            <span v-else>ETEC</span>
          </span>
          <span class="max-w-[12rem] truncate text-sm font-black leading-tight text-slate-950 sm:max-w-xs sm:text-base">{{ schoolName }}</span>
        </Link>

        <nav class="hidden items-center gap-1 lg:flex">
          <FrontendMenuLinks :menus="menus" home-active />
        </nav>

        <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 lg:hidden" aria-label="Toggle menu" @click="menuOpen = !menuOpen">
          <X v-if="menuOpen" class="h-5 w-5" />
          <Menu v-else class="h-5 w-5" />
        </button>
      </div>

      <nav v-if="menuOpen" class="mx-4 mb-4 grid gap-1 rounded-2xl border border-slate-200 bg-white p-3 shadow-xl lg:hidden">
        <FrontendMenuLinks :menus="menus" home-active mobile @navigate="menuOpen = false" />
      </nav>
    </header>

    <main class="pt-20">
      <section
        class="relative min-h-[440px] overflow-hidden bg-slate-900 text-white sm:min-h-[520px] lg:min-h-[640px]"
        @pointerdown="startHeroDrag"
        @pointermove="moveHeroDrag"
        @pointerup="endHeroDrag"
        @pointercancel="endHeroDrag"
        @pointerleave="endHeroDrag"
      >
        <div
          class="absolute inset-0 flex cursor-grab touch-pan-y select-none transition-transform ease-[cubic-bezier(0.22,1,0.36,1)] will-change-transform active:cursor-grabbing"
          :style="heroTrackStyle"
        >
          <div
            v-for="slide in heroSlideUrls"
            :key="slide"
            class="relative h-full w-full shrink-0 overflow-hidden"
          >
            <img
              :src="slide"
              :alt="schoolName"
              draggable="false"
              class="h-full w-full object-cover object-center"
            />
          </div>
        </div>
        <div
          class="pointer-events-none absolute inset-0"
          :class="heroEnabled ? '' : 'bg-[#091f42]/20'"
          :style="heroOverlayStyle"
        ></div>
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-[#051833]/90 via-[#051833]/60 to-[#051833]/15"></div>
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#051833]/70 via-transparent to-[#051833]/20"></div>

        <div class="relative mx-auto flex min-h-[440px] max-w-7xl flex-col justify-center px-4 py-14 sm:min-h-[520px] sm:px-6 sm:py-20 lg:min-h-[640px] lg:px-8" :class="heroAlignmentClass">
          <p class="max-w-full text-xs font-black uppercase tracking-[0.22em] text-[#f4a261] sm:text-sm">{{ heroSubtitle }}</p>
          <h1 class="mt-4 max-w-4xl text-4xl font-black leading-[1.05] text-white drop-shadow-sm sm:text-5xl lg:text-7xl">{{ heroTitle }}</h1>
          <p class="mt-5 max-w-2xl text-sm leading-7 text-blue-50 drop-shadow sm:text-lg sm:leading-8">{{ heroDescription }}</p>
          <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap" :class="heroButtonAlignmentClass">
            <Link v-if="primaryButtonText && primaryButtonUrl" :href="primaryButtonUrl" class="inline-flex justify-center rounded-full bg-[#f4a261] px-6 py-3 text-sm font-black text-slate-950 shadow-lg shadow-[#f4a261]/20 transition hover:-translate-y-0.5 hover:bg-[#f7c948]">{{ primaryButtonText }}</Link>
            <Link v-if="secondaryButtonText && secondaryButtonUrl" :href="secondaryButtonUrl" class="inline-flex justify-center rounded-full bg-white px-6 py-3 text-sm font-black text-[#174981] shadow-lg transition hover:-translate-y-0.5 hover:bg-blue-50">{{ secondaryButtonText }}</Link>
          </div>
        </div>

        <div v-if="heroSlideCount > 1" class="absolute inset-x-0 bottom-0 z-10">
          <div class="h-1 bg-white/20">
            <div :key="activeHero" class="hero-progress h-full bg-[#f4a261]"></div>
          </div>
        </div>

        <button v-if="heroSlideCount > 1" type="button" class="absolute left-4 top-1/2 z-10 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/30 bg-slate-950/30 text-white shadow-lg backdrop-blur transition hover:bg-white hover:text-[#174981] md:inline-flex" aria-label="Previous hero slide" @click="previousHero">
          <ChevronLeft class="h-5 w-5" />
        </button>
        <button v-if="heroSlideCount > 1" type="button" class="absolute right-4 top-1/2 z-10 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/30 bg-slate-950/30 text-white shadow-lg backdrop-blur transition hover:bg-white hover:text-[#174981] md:inline-flex" aria-label="Next hero slide" @click="nextHero">
          <ChevronRight class="h-5 w-5" />
        </button>

        <div v-if="heroSlideCount > 1" class="absolute bottom-7 left-1/2 z-10 flex -translate-x-1/2 gap-2">
          <button v-for="index in heroSlideCount" :key="index" type="button" class="h-2.5 rounded-full border border-white/60 transition-all" :class="index - 1 === activeHero % heroSlideCount ? 'w-10 bg-[#f4a261]' : 'w-2.5 bg-white/60 hover:bg-white'" :aria-label="`Hero slide ${index}`" @click="goToHero(index - 1)"></button>
        </div>
      </section>

      <section class="py-12 sm:py-16">
        <div class="mx-auto grid max-w-7xl gap-4 px-4 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
          <article class="rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
            <Users class="mx-auto h-7 w-7 text-[#1e5aa8]" />
            <p class="mt-4 text-4xl font-black text-[#1e5aa8]">Active</p>
            <h3 class="mt-2 text-base font-bold text-slate-800">Student Learning</h3>
          </article>
          <article class="rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
            <GraduationCap class="mx-auto h-7 w-7 text-[#1e5aa8]" />
            <p class="mt-4 text-4xl font-black text-[#1e5aa8]">{{ courses.length }}</p>
            <h3 class="mt-2 text-base font-bold text-slate-800">Available Courses</h3>
          </article>
          <article class="rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
            <Search class="mx-auto h-7 w-7 text-[#1e5aa8]" />
            <p class="mt-4 text-4xl font-black text-[#1e5aa8]">Public</p>
            <h3 class="mt-2 text-base font-bold text-slate-800">Website Pages</h3>
          </article>
          <article class="rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
            <CalendarDays class="mx-auto h-7 w-7 text-[#1e5aa8]" />
            <p class="mt-4 text-4xl font-black text-[#1e5aa8]">ETEC</p>
            <h3 class="mt-2 text-base font-bold text-slate-800">Training Center</h3>
          </article>
        </div>
      </section>

      <section class="bg-slate-50 py-14 sm:py-20">
        <div class="mx-auto grid max-w-7xl gap-11 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
          <img :src="aboutImage" alt="Modern classroom" class="h-full min-h-80 w-full rounded-3xl object-cover shadow-[0_18px_45px_rgba(30,90,168,0.16)] sm:min-h-96" />
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

      <section class="py-14 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
              <p class="text-sm font-bold uppercase tracking-widest text-[#f4a261]">Programs</p>
              <h2 class="mt-2 text-3xl font-black text-slate-950">Featured Courses</h2>
            </div>
            <Link :href="linkFor('course', '/course')" class="font-extrabold text-[#1e5aa8] hover:text-[#f4a261]">See All</Link>
          </div>

          <div v-if="courses.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <article v-for="course in courses" :key="course.id" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-[0_18px_45px_rgba(30,90,168,0.14)]">
              <img v-if="course.thumbnail_url" :src="course.thumbnail_url" :alt="course.title" class="aspect-video w-full object-cover" />
              <div v-else class="grid aspect-video place-items-center bg-[#1e5aa8]/10 text-xl font-black text-[#1e5aa8]">{{ course.title?.charAt(0) ?? "C" }}</div>
              <div class="p-6">
                <span v-if="course.category || course.track" class="rounded-full bg-[#f4a261]/20 px-3 py-1 text-xs font-bold text-[#8a4b12]">{{ course.category || course.track }}</span>
                <h3 class="mt-4 text-xl font-black text-slate-950">{{ course.title }}</h3>
                <p class="mt-3 text-sm font-bold capitalize text-slate-500">{{ course.level || "Course" }}</p>
                <Link :href="`/courses/${course.slug}`" class="mt-5 inline-flex rounded-full bg-[#1e5aa8] px-5 py-2.5 text-sm font-black text-white transition hover:bg-[#174981]">View Details</Link>
              </div>
            </article>
          </div>

          <div v-else class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
            <p class="font-bold text-slate-700">No active courses yet.</p>
            <p class="mt-2 text-sm text-slate-500">Create active courses in the Course module and they will display here.</p>
          </div>
        </div>
      </section>

      <section class="bg-[#0b2550] py-14 text-white sm:py-20">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
          <div>
            <p class="text-sm font-black uppercase tracking-[0.22em] text-[#f4a261]">Start learning</p>
            <h2 class="mt-3 max-w-3xl text-3xl font-black leading-tight sm:text-4xl">Begin your IT learning journey with {{ schoolName }}</h2>
            <p class="mt-4 max-w-2xl leading-7 text-blue-50">Explore active courses from the system and contact the school for more information.</p>
          </div>
          <div class="flex flex-col gap-3 sm:flex-row lg:shrink-0">
            <Link :href="linkFor('course', '/course')" class="inline-flex justify-center rounded-full bg-[#f4a261] px-6 py-3 text-sm font-black text-slate-950 shadow-lg shadow-[#f4a261]/20 transition hover:-translate-y-0.5 hover:bg-[#f7c948]">View Courses</Link>
            <Link :href="linkFor('contact', '/contact')" class="inline-flex justify-center rounded-full bg-white px-6 py-3 text-sm font-black text-[#174981] shadow-lg transition hover:-translate-y-0.5 hover:bg-blue-50">Contact Us</Link>
          </div>
        </div>
      </section>
    </main>

    <FrontendFooter :settings="settings" :menus="menus" />
  </div>
</template>

<style scoped>
.hero-progress {
  animation: hero-progress 5s linear forwards;
  transform-origin: left;
}

@keyframes hero-progress {
  from {
    transform: scaleX(0);
  }

  to {
    transform: scaleX(1);
  }
}
</style>
