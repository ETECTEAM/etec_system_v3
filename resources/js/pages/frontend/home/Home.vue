<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { CalendarDays, ChevronLeft, ChevronRight, GraduationCap, Menu, Search, Users, X, Star, CheckCircle, MonitorPlay, Award, Code2, Terminal, Database, Cpu, Globe, Smartphone, Cloud } from "@lucide/vue";
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
  <div class="min-h-screen bg-[#F4F7FA] font-sans selection:bg-[#1A66FF]/20 selection:text-[#1A66FF] [--public-header-height:5.5rem]">
    <header class="fixed left-0 right-0 top-0 z-40 border-b border-slate-200/50 bg-white/80 shadow-sm backdrop-blur-xl">
      <div class="mx-auto flex min-h-[5.5rem] max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <Link href="/" class="flex min-w-0 items-center gap-3 group">
          <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-[#1A66FF] shadow-[0_5px_15px_rgba(26,102,255,0.3)] transition-transform group-hover:scale-105">
            <img v-if="settings.logo_url" :src="settings.logo_url" :alt="schoolName" class="h-full w-full object-contain p-1" />
            <span v-else class="text-xs font-black text-white">ETEC</span>
          </span>
          <span class="max-w-[12rem] truncate text-xl font-black text-slate-900 sm:max-w-xs transition-colors group-hover:text-[#1A66FF]">{{ schoolName }}</span>
        </Link>
        <nav class="hidden items-center gap-2 lg:flex">
          <FrontendMenuLinks :menus="menus" home-active />
        </nav>
        <button type="button" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#E8F0FF] text-[#1A66FF] transition hover:bg-[#1A66FF] hover:text-white lg:hidden" aria-label="Toggle menu" :aria-expanded="menuOpen" @click="menuOpen = true">
          <Menu class="h-5 w-5" />
        </button>
      </div>
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

    <main class="pt-[88px]">
      <section
        class="relative min-h-screen overflow-hidden bg-[#0A1D3A] text-white"
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
          :class="heroEnabled ? '' : 'bg-[#0A1D3A]/20'"
          :style="heroOverlayStyle"
        ></div>
        <!-- Theme Gradients -->
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-[#091629]/95 via-[#0A1D3A]/70 to-[#0A1D3A]/20"></div>
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-transparent via-[#091629]/30 to-[#091629]/95"></div>

        <div class="relative mx-auto flex min-h-screen max-w-7xl flex-col justify-center px-4 py-16 sm:px-6 sm:py-24 lg:px-8" :class="heroAlignmentClass">
          
          <!-- Decorative Animated Shapes -->
          <div class="absolute top-1/4 right-0 opacity-40 pointer-events-none hidden lg:block animation-spin-slow">
             <div class="w-[800px] h-[800px] bg-gradient-to-bl from-[#1A66FF] to-[#FFB800] rounded-full mix-blend-screen blur-[120px] translate-x-1/3 -translate-y-1/3"></div>
          </div>
          
          <!-- Floating Glassmorphic Badges -->
          <div class="absolute top-32 right-[10%] hidden md:flex items-center gap-4 bg-white/10 backdrop-blur-md border border-white/20 p-4 rounded-3xl shadow-[0_20px_40px_rgba(0,0,0,0.3)] animation-float">
             <div class="bg-[#FFB800] rounded-full p-2.5 shadow-lg shadow-[#FFB800]/30"><Star class="w-6 h-6 text-[#091629]" fill="currentColor" /></div>
             <div>
                <p class="text-sm font-bold text-white uppercase tracking-wider">Top Rated</p>
                <p class="text-xs text-slate-300">Award Winning Center</p>
             </div>
          </div>

          <div class="absolute bottom-40 right-[20%] hidden lg:flex items-center gap-4 bg-white/10 backdrop-blur-md border border-white/20 p-4 rounded-3xl shadow-[0_20px_40px_rgba(0,0,0,0.3)] animation-float animation-delay-1">
             <div class="bg-[#1A66FF] rounded-full p-2.5 shadow-lg shadow-[#1A66FF]/30"><Users class="w-6 h-6 text-white" /></div>
             <div>
                <p class="text-sm font-bold text-white uppercase tracking-wider">5,000+ Students</p>
                <p class="text-xs text-slate-300">Successfully Graduated</p>
             </div>
          </div>

          <p class="inline-flex items-center gap-2 rounded-full bg-white/10 backdrop-blur-md px-5 py-2 text-sm font-bold text-[#FFB800] border border-white/20 mb-8 uppercase tracking-[0.2em] relative z-10 w-max shadow-lg animation-fade-in-up">
            <span class="relative flex h-3 w-3">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#FFB800] opacity-75"></span>
              <span class="relative inline-flex rounded-full h-3 w-3 bg-[#FFB800]"></span>
            </span>
            {{ heroSubtitle }}
          </p>
          <h1 class="max-w-4xl text-5xl font-black leading-[1.05] text-white drop-shadow-md sm:text-6xl lg:text-7xl mb-6 relative z-10 animation-fade-in-up animation-delay-1">{{ heroTitle }}</h1>
          <p class="max-w-2xl text-lg leading-relaxed text-slate-200 drop-shadow sm:text-xl sm:leading-9 mb-10 relative z-10 animation-fade-in-up animation-delay-2">{{ heroDescription }}</p>
          
          <div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap relative z-10 animation-fade-in-up animation-delay-3" :class="heroButtonAlignmentClass">
            <Link v-if="primaryButtonText && primaryButtonUrl" :href="primaryButtonUrl" class="inline-flex items-center justify-center gap-3 rounded-full bg-[#FFB800] px-8 py-4 text-base font-black text-slate-900 transition-all hover:bg-[#ffc833] hover:-translate-y-1 hover:shadow-[0_15px_30px_rgba(255,184,0,0.4)] shadow-[0_10px_20px_rgba(255,184,0,0.2)]">
               {{ primaryButtonText }}
               <ChevronRight class="w-5 h-5 bg-slate-900 text-[#FFB800] rounded-full p-0.5" />
            </Link>
            <Link v-if="secondaryButtonText && secondaryButtonUrl" :href="secondaryButtonUrl" class="inline-flex items-center justify-center gap-3 rounded-full bg-white/10 border border-white/20 px-8 py-4 text-base font-bold text-white backdrop-blur-md transition-all hover:bg-white/20 hover:-translate-y-1 shadow-lg">
               {{ secondaryButtonText }}
            </Link>
          </div>
        </div>

        <div v-if="heroSlideCount > 1" class="absolute inset-x-0 bottom-0 z-10">
          <div class="h-1 bg-white/20">
            <div :key="activeHero" class="hero-progress h-full bg-[#FFB800]"></div>
          </div>
        </div>

        <button v-if="heroSlideCount > 1" type="button" class="absolute left-6 top-1/2 z-10 hidden h-14 w-14 -translate-y-1/2 items-center justify-center rounded-full border border-white/20 bg-slate-900/30 text-white shadow-xl backdrop-blur-md transition hover:bg-[#1A66FF] hover:border-transparent md:inline-flex" aria-label="Previous hero slide" @click="previousHero">
          <ChevronLeft class="h-6 w-6" />
        </button>
        <button v-if="heroSlideCount > 1" type="button" class="absolute right-6 top-1/2 z-10 hidden h-14 w-14 -translate-y-1/2 items-center justify-center rounded-full border border-white/20 bg-slate-900/30 text-white shadow-xl backdrop-blur-md transition hover:bg-[#1A66FF] hover:border-transparent md:inline-flex group" aria-label="Next hero slide" @click="nextHero">
          <ChevronRight class="h-7 w-7 transition-transform group-hover:translate-x-1" />
        </button>
      </section>

      <!-- Tech Stack Marquee (Infinite Scroll) -->
      <section class="bg-[#091629] border-y border-white/5 py-6 overflow-hidden relative flex items-center z-20">
         <div class="absolute inset-y-0 left-0 w-32 bg-gradient-to-r from-[#091629] to-transparent z-10"></div>
         <div class="absolute inset-y-0 right-0 w-32 bg-gradient-to-l from-[#091629] to-transparent z-10"></div>
         
         <div class="flex animate-marquee whitespace-nowrap items-center hover:[animation-play-state:paused]">
            <!-- Group 1 -->
            <div class="flex items-center justify-around gap-16 px-8 min-w-full">
               <div class="flex items-center gap-3 text-slate-400 hover:text-white transition-colors duration-300">
                 <Code2 class="w-6 h-6" /> <span class="text-xl font-black tracking-widest uppercase">Web Dev</span>
               </div>
               <div class="flex items-center gap-3 text-slate-400 hover:text-[#42b883] transition-colors duration-300">
                 <Globe class="w-6 h-6" /> <span class="text-xl font-black tracking-widest uppercase">Vue.js</span>
               </div>
               <div class="flex items-center gap-3 text-slate-400 hover:text-[#1A66FF] transition-colors duration-300">
                 <Database class="w-6 h-6" /> <span class="text-xl font-black tracking-widest uppercase">SQL / DB</span>
               </div>
               <div class="flex items-center gap-3 text-slate-400 hover:text-[#FFB800] transition-colors duration-300">
                 <Terminal class="w-6 h-6" /> <span class="text-xl font-black tracking-widest uppercase">Python</span>
               </div>
               <div class="flex items-center gap-3 text-slate-400 hover:text-white transition-colors duration-300">
                 <Smartphone class="w-6 h-6" /> <span class="text-xl font-black tracking-widest uppercase">Mobile iOS</span>
               </div>
               <div class="flex items-center gap-3 text-slate-400 hover:text-[#e34c26] transition-colors duration-300">
                 <Cpu class="w-6 h-6" /> <span class="text-xl font-black tracking-widest uppercase">Hardware</span>
               </div>
               <div class="flex items-center gap-3 text-slate-400 hover:text-[#00ADD8] transition-colors duration-300">
                 <Cloud class="w-6 h-6" /> <span class="text-xl font-black tracking-widest uppercase">Cloud AWS</span>
               </div>
            </div>
            <!-- Duplicate for seamless scroll -->
            <div class="flex items-center justify-around gap-16 px-8 min-w-full">
               <div class="flex items-center gap-3 text-slate-400 hover:text-white transition-colors duration-300">
                 <Code2 class="w-6 h-6" /> <span class="text-xl font-black tracking-widest uppercase">Web Dev</span>
               </div>
               <div class="flex items-center gap-3 text-slate-400 hover:text-[#42b883] transition-colors duration-300">
                 <Globe class="w-6 h-6" /> <span class="text-xl font-black tracking-widest uppercase">Vue.js</span>
               </div>
               <div class="flex items-center gap-3 text-slate-400 hover:text-[#1A66FF] transition-colors duration-300">
                 <Database class="w-6 h-6" /> <span class="text-xl font-black tracking-widest uppercase">SQL / DB</span>
               </div>
               <div class="flex items-center gap-3 text-slate-400 hover:text-[#FFB800] transition-colors duration-300">
                 <Terminal class="w-6 h-6" /> <span class="text-xl font-black tracking-widest uppercase">Python</span>
               </div>
               <div class="flex items-center gap-3 text-slate-400 hover:text-white transition-colors duration-300">
                 <Smartphone class="w-6 h-6" /> <span class="text-xl font-black tracking-widest uppercase">Mobile iOS</span>
               </div>
               <div class="flex items-center gap-3 text-slate-400 hover:text-[#e34c26] transition-colors duration-300">
                 <Cpu class="w-6 h-6" /> <span class="text-xl font-black tracking-widest uppercase">Hardware</span>
               </div>
               <div class="flex items-center gap-3 text-slate-400 hover:text-[#00ADD8] transition-colors duration-300">
                 <Cloud class="w-6 h-6" /> <span class="text-xl font-black tracking-widest uppercase">Cloud AWS</span>
               </div>
            </div>
         </div>
      </section>

      <!-- Stats Section -->
      <section class="py-16 sm:py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-[-80px] relative z-20 hidden md:block">
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          <article class="rounded-[2rem] border border-slate-100 bg-white p-8 text-center shadow-[0_10px_40px_-10px_rgba(0,0,0,0.08)] transition hover:-translate-y-2 hover:shadow-[0_15px_40px_-10px_rgba(26,102,255,0.15)] group">
            <div class="mx-auto w-16 h-16 rounded-full bg-[#E8F0FF] flex items-center justify-center mb-6 transition-transform group-hover:scale-110">
              <Users class="h-8 w-8 text-[#1A66FF]" />
            </div>
            <p class="text-4xl font-black text-slate-900 mb-2 group-hover:text-[#1A66FF] transition-colors">Active</p>
            <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Student Learning</h3>
          </article>
          <article class="rounded-[2rem] border border-slate-100 bg-white p-8 text-center shadow-[0_10px_40px_-10px_rgba(0,0,0,0.08)] transition hover:-translate-y-2 hover:shadow-[0_15px_40px_-10px_rgba(26,102,255,0.15)] group">
            <div class="mx-auto w-16 h-16 rounded-full bg-[#FFF5EB] flex items-center justify-center mb-6 transition-transform group-hover:scale-110">
              <GraduationCap class="h-8 w-8 text-[#FFB800]" />
            </div>
            <p class="text-4xl font-black text-slate-900 mb-2 group-hover:text-[#FFB800] transition-colors">{{ courses.length }}</p>
            <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Available Courses</h3>
          </article>
          <article class="rounded-[2rem] border border-slate-100 bg-white p-8 text-center shadow-[0_10px_40px_-10px_rgba(0,0,0,0.08)] transition hover:-translate-y-2 hover:shadow-[0_15px_40px_-10px_rgba(26,102,255,0.15)] group">
            <div class="mx-auto w-16 h-16 rounded-full bg-[#E8F0FF] flex items-center justify-center mb-6 transition-transform group-hover:scale-110">
              <Search class="h-8 w-8 text-[#1A66FF]" />
            </div>
            <p class="text-4xl font-black text-slate-900 mb-2 group-hover:text-[#1A66FF] transition-colors">Public</p>
            <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Website Pages</h3>
          </article>
          <article class="rounded-[2rem] border border-slate-100 bg-white p-8 text-center shadow-[0_10px_40px_-10px_rgba(0,0,0,0.08)] transition hover:-translate-y-2 hover:shadow-[0_15px_40px_-10px_rgba(26,102,255,0.15)] group">
            <div class="mx-auto w-16 h-16 rounded-full bg-[#FFF5EB] flex items-center justify-center mb-6 transition-transform group-hover:scale-110">
              <CalendarDays class="h-8 w-8 text-[#FFB800]" />
            </div>
            <p class="text-4xl font-black text-slate-900 mb-2 group-hover:text-[#FFB800] transition-colors">ETEC</p>
            <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Training Center</h3>
          </article>
        </div>
      </section>

      <!-- About Section -->
      <section class="bg-white py-24 sm:py-32 relative overflow-hidden">
        <div class="mx-auto grid max-w-7xl gap-16 px-4 sm:px-6 lg:grid-cols-2 lg:px-8 items-center relative z-10">
          <div class="relative w-full h-[500px] lg:h-[650px] mx-auto md:max-w-[85%] lg:max-w-full">
            <div class="absolute -top-6 -left-6 w-32 h-32 bg-[radial-gradient(#1A66FF_3px,transparent_3px)] [background-size:16px_16px] opacity-20 z-0"></div>
            <div class="absolute -bottom-6 -right-6 w-32 h-32 bg-[radial-gradient(#FFB800_3px,transparent_3px)] [background-size:16px_16px] opacity-30 z-0"></div>
            
            <img :src="aboutImage" alt="Modern classroom" class="absolute top-0 right-0 z-10 w-[80%] h-[75%] rounded-[3rem] rounded-tr-xl object-cover shadow-[0_20px_50px_rgba(26,102,255,0.15)] ring-4 ring-white transition-transform duration-700 hover:scale-[1.02]" />
            <img src="https://images.unsplash.com/photo-1577896851231-70ef18881754?auto=format&fit=crop&w=800&q=80" alt="Students learning" class="absolute bottom-0 left-0 z-20 w-[65%] h-[60%] rounded-[3rem] rounded-bl-xl object-cover shadow-[0_20px_50px_rgba(0,0,0,0.2)] ring-8 ring-white transition-transform duration-700 hover:scale-[1.02]" />
            
            <div class="absolute top-1/2 -left-8 z-30 rounded-3xl bg-white p-5 shadow-[0_15px_35px_rgba(0,0,0,0.1)] border border-slate-100 animation-float hidden md:flex items-center gap-4 group">
               <div class="h-14 w-14 rounded-full bg-[#1A66FF] flex items-center justify-center group-hover:rotate-12 transition-transform">
                 <span class="text-xl font-black text-white">#1</span>
               </div>
               <div>
                 <p class="text-lg font-black text-slate-900 leading-none mb-1">Education</p>
                 <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Platform</p>
               </div>
            </div>
          </div>

          <div class="flex flex-col justify-center lg:pl-10">
            <p class="inline-flex items-center gap-2 rounded-full bg-[#E8F0FF] px-4 py-1.5 text-sm font-bold text-[#1A66FF] mb-6 w-fit shadow-sm">
              <span class="w-2 h-2 rounded-full bg-[#1A66FF] animate-pulse"></span>
              About our school
            </p>
            <h2 class="text-4xl font-black text-slate-900 sm:text-5xl leading-tight mb-8">Elevate your learning experience</h2>
            <p class="text-lg leading-relaxed text-slate-600 mb-10">
              The page route, menu, logo, and hero are managed directly from your central Website Management dashboard. Integrate deeply with existing course modules for a unified experience.
            </p>
            
            <div class="grid sm:grid-cols-2 gap-4 mb-10">
               <div class="flex items-center gap-3">
                  <div class="bg-[#FFF5EB] p-2 rounded-full"><CheckCircle class="w-5 h-5 text-[#FFB800]" /></div>
                  <span class="font-bold text-slate-700">Expert Instructors</span>
               </div>
               <div class="flex items-center gap-3">
                  <div class="bg-[#FFF5EB] p-2 rounded-full"><CheckCircle class="w-5 h-5 text-[#FFB800]" /></div>
                  <span class="font-bold text-slate-700">Online Certificates</span>
               </div>
               <div class="flex items-center gap-3">
                  <div class="bg-[#FFF5EB] p-2 rounded-full"><CheckCircle class="w-5 h-5 text-[#FFB800]" /></div>
                  <span class="font-bold text-slate-700">Flexible Learning</span>
               </div>
               <div class="flex items-center gap-3">
                  <div class="bg-[#FFF5EB] p-2 rounded-full"><CheckCircle class="w-5 h-5 text-[#FFB800]" /></div>
                  <span class="font-bold text-slate-700">Support 24/7</span>
               </div>
            </div>

            <Link :href="linkFor('about', '/about')" class="inline-flex w-fit items-center justify-center rounded-full bg-[#1A66FF] px-10 py-4 text-base font-black text-white shadow-[0_10px_30px_rgba(26,102,255,0.3)] transition-all hover:-translate-y-1 hover:bg-[#1555D9] group">
               Discover More
               <ChevronRight class="ml-2 w-5 h-5 transition-transform group-hover:translate-x-1" />
            </Link>
          </div>
        </div>
      </section>

      <!-- Courses Section -->
      <section class="py-20 sm:py-32 bg-[#F4F7FA]">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div class="mb-12 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
              <p class="inline-flex items-center gap-2 rounded-full bg-[#FFF5EB] px-4 py-1.5 text-sm font-bold text-[#FFB800] mb-4">
                <span class="w-2 h-2 rounded-full bg-[#FFB800]"></span>
                Programs
              </p>
              <h2 class="text-4xl font-black text-slate-900 sm:text-5xl border-l-4 border-[#1A66FF] pl-4">Featured Courses</h2>
            </div>
            <Link :href="linkFor('course', '/course')" class="inline-flex items-center gap-2 font-bold text-[#1A66FF] hover:text-[#1555D9] transition-colors group">
              See All Courses
              <ChevronRight class="w-5 h-5 transition-transform group-hover:translate-x-1" />
            </Link>
          </div>

          <div v-if="courses.length" class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
            <article v-for="course in courses" :key="course.id" class="group flex flex-col overflow-hidden rounded-[2rem] border border-slate-100 bg-white shadow-[0_5px_15px_rgba(0,0,0,0.04)] transition-all duration-300 hover:-translate-y-2 hover:shadow-[0_20px_40px_-5px_rgba(26,102,255,0.15)] relative">
              <div class="relative overflow-hidden w-full aspect-[4/3]">
                <img v-if="course.thumbnail_url" :src="course.thumbnail_url" :alt="course.title" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                <div v-else class="grid h-full w-full place-items-center bg-[#E8F0FF] text-4xl font-black text-[#1A66FF] transition-transform duration-500 group-hover:scale-105">{{ course.title?.charAt(0) ?? "C" }}</div>
                <div v-if="course.category || course.track" class="absolute top-4 left-4 z-10">
                   <span class="rounded-full bg-white/90 backdrop-blur px-4 py-1.5 text-xs font-bold text-[#1A66FF] shadow-sm">{{ course.category || course.track }}</span>
                </div>
              </div>
              <div class="flex flex-col flex-1 p-8">
                <h3 class="text-2xl font-black text-slate-900 mb-2 group-hover:text-[#1A66FF] transition-colors line-clamp-2">{{ course.title }}</h3>
                <p class="text-sm font-bold capitalize text-slate-500 mb-6 flex-1">{{ course.level || "Course" }}</p>
                <Link :href="`/courses/${course.slug}`" class="inline-flex items-center justify-center rounded-2xl bg-slate-50 px-6 py-3.5 text-sm font-black text-slate-700 transition hover:bg-[#1A66FF] hover:text-white border border-slate-100 group-hover:border-transparent">View Course Details</Link>
              </div>
            </article>
          </div>

          <div v-else class="rounded-[2rem] border-2 border-dashed border-slate-200 bg-white p-16 text-center shadow-sm">
            <Search class="mx-auto h-12 w-12 text-slate-300 mb-4" />
            <p class="text-lg font-black text-slate-700">No active courses yet.</p>
            <p class="mt-2 text-slate-500">Create active courses in the Course module and they will display here.</p>
          </div>
        </div>
      </section>

      <!-- CTA Section -->
      <section class="relative bg-[#0A1D3A] py-20 text-white sm:py-32 overflow-hidden">
        <!-- Abstract Shapes -->
        <div class="absolute inset-0 pointer-events-none opacity-20">
           <svg class="absolute top-0 left-0 transform -translate-x-1/2 -translate-y-1/2" width="600" height="600" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
              <path fill="#1A66FF" d="M45,-77C57,-69,65,-54,71,-39C77,-24,80,-8,77,7C74,22,65,36,54,49C43,62,30,74,15,79C-1,84,-18,82,-33,74C-48,66,-61,52,-70,36C-79,20,-84,2,-82,-15C-80,-32,-71,-48,-57,-58C-43,-68,-21,-72,-3,-69C15,-66,33,-85,45,-77Z" transform="translate(100 100)" />
           </svg>
        </div>
        
        <div class="mx-auto flex max-w-7xl flex-col items-center text-center px-4 relative z-10 sm:px-6 lg:px-8">
          <p class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-sm font-bold text-[#FFB800] mb-8 uppercase tracking-[0.2em] backdrop-blur-sm">
            <span class="w-2 h-2 rounded-full bg-[#FFB800]"></span>
            Start learning today
          </p>
          <h2 class="max-w-4xl text-4xl font-black leading-tight sm:text-5xl lg:text-6xl mb-8">Begin your IT learning journey with {{ schoolName }}</h2>
          <p class="max-w-2xl text-lg leading-relaxed text-slate-300 mb-12">Explore active courses from the system and contact the school for more information to accelerate your career.</p>
          <div class="flex flex-col gap-4 sm:flex-row w-full sm:w-auto">
            <Link :href="linkFor('course', '/course')" class="inline-flex items-center justify-center rounded-full bg-[#FFB800] px-10 py-4 text-base font-black text-slate-900 shadow-[0_10px_30px_rgba(255,184,0,0.3)] transition hover:-translate-y-1 hover:bg-[#ffc833]">View All Courses</Link>
            <Link :href="linkFor('contact', '/contact')" class="inline-flex items-center justify-center rounded-full bg-white/10 px-10 py-4 text-base font-black text-white shadow-lg backdrop-blur-sm transition hover:-translate-y-1 hover:bg-white/20">Contact Us</Link>
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
  from { transform: scaleX(0); }
  to { transform: scaleX(1); }
}

.animation-float {
  animation: float 6s ease-in-out infinite;
}

@keyframes float {
  0% { transform: translateY(0px); }
  50% { transform: translateY(-15px); }
  100% { transform: translateY(0px); }
}

.animation-fade-in-up {
  animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

.animation-spin-slow {
  animation: spin 30s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.animate-marquee {
  animation: marquee 35s linear infinite;
}

@keyframes marquee {
  0% { transform: translateX(0%); }
  100% { transform: translateX(-100%); }
}

.animation-delay-1 { animation-delay: 150ms; }
.animation-delay-2 { animation-delay: 300ms; }
.animation-delay-3 { animation-delay: 450ms; }

:deep(svg) { display: inline-block; }
</style>
