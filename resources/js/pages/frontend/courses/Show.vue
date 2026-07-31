<script setup>
import { computed, ref } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { CheckCircle2, Menu, X } from "@lucide/vue";
import FrontendFooter from "@/components/frontend/FrontendFooter.vue";
import FrontendMenuLinks from "@/components/frontend/FrontendMenuLinks.vue";

const props = defineProps({
  course: {
    type: Object,
    required: true,
  },
});

const inertiaPage = usePage();
const website = computed(() => inertiaPage.props.website ?? {});
const settings = computed(() => website.value.settings ?? {});
const menus = computed(() => website.value.menus ?? []);
const schoolName = computed(() => settings.value.school_name || "ETEC Center");
const menuOpen = ref(false);

const fallbackImage = "https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&w=1800&q=80";
const heroImage = computed(() => props.course.thumbnail_url || fallbackImage);
const activeLessons = computed(() => props.course.lessons ?? []);
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
          <FrontendMenuLinks :menus="menus" :active-slugs="['course', 'courses']" />
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
            <FrontendMenuLinks :menus="menus" :active-slugs="['course', 'courses']" mobile @navigate="menuOpen = false" />
          </div>
        </nav>
      </transition>

    <main class="pt-[88px]">
      <section class="relative overflow-hidden bg-[#0A1D3A] py-16 sm:py-24">
        <!-- Background Decor -->
        <div class="absolute top-0 right-0 opacity-20 pointer-events-none">
          <svg width="400" height="400" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
            <path fill="#1A66FF" d="M45,-77C57,-69,65,-54,71,-39C77,-24,80,-8,77,7C74,22,65,36,54,49C43,62,30,74,15,79C-1,84,-18,82,-33,74C-48,66,-61,52,-70,36C-79,20,-84,2,-82,-15C-80,-32,-71,-48,-57,-58C-43,-68,-21,-72,-3,-69C15,-66,33,-85,45,-77Z" transform="translate(100 100)" />
          </svg>
        </div>
        
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10 flex flex-col lg:flex-row items-center gap-12 lg:gap-16">
          <div class="flex-1 text-white w-full">
            <p v-if="course.category || course.track" class="inline-flex items-center gap-2 rounded-full bg-[#1A66FF]/20 px-4 py-1.5 text-sm font-bold text-[#1A66FF] ring-1 ring-inset ring-[#1A66FF]/30 mb-6">
              {{ course.category || course.track }}
            </p>
            <h1 class="text-4xl sm:text-5xl md:text-6xl font-black leading-[1.1] mb-6">
              {{ course.title }}
            </h1>
            <p v-if="course.description" class="text-lg text-slate-300 max-w-xl leading-relaxed mb-8 line-clamp-3">
              {{ course.description }}
            </p>
            <div class="flex flex-wrap gap-4">
               <Link href="/register" class="inline-flex items-center justify-center rounded-full bg-[#FFB800] px-8 py-3.5 text-base font-bold text-slate-900 transition-all hover:bg-[#ffc833] hover:-translate-y-1 shadow-[0_10px_30px_rgba(255,184,0,0.3)]">
                 Enroll Now
               </Link>
               <Link href="/courses" class="inline-flex items-center justify-center rounded-full bg-white/10 px-8 py-3.5 text-base font-bold text-white backdrop-blur-sm transition-all hover:bg-white/20 hover:-translate-y-1">
                 View All Courses
               </Link>
            </div>
          </div>
          <div class="flex-1 w-full max-w-lg lg:max-w-none relative">
            <div class="absolute -inset-4 bg-gradient-to-tr from-[#1A66FF] to-[#FFB800] rounded-[2rem] opacity-30 blur-2xl z-0"></div>
            <img :src="heroImage" :alt="course.title" class="relative z-10 w-full h-[300px] md:h-[400px] object-cover rounded-[2.5rem] shadow-2xl border-4 border-white/10" />
            
            <div class="absolute -bottom-6 -left-6 z-20 rounded-2xl bg-white p-4 shadow-xl flex items-center gap-4 hidden sm:flex">
              <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#E8F0FF]">
                 <CheckCircle2 class="h-6 w-6 text-[#1A66FF]" />
              </div>
              <div>
                <p class="text-sm font-bold text-slate-500">Course Status</p>
                <p class="text-lg font-black text-slate-900">Enrolling</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="max-w-7xl mx-auto px-4 py-16 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
          <div class="lg:col-span-2 space-y-12">
            <!-- Overview -->
            <div v-if="course.description" class="rounded-[2rem] bg-white p-8 sm:p-10 shadow-[0_10px_40px_-10px_rgba(0,0,0,0.05)] border border-slate-100 relative overflow-hidden">
              <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-[#E8F0FF] to-transparent rounded-bl-full opacity-50"></div>
              <h2 class="text-3xl font-black text-slate-900 mb-6 flex items-center gap-3">
                <span class="w-1.5 h-8 rounded-full bg-[#FFB800]"></span>
                Course Overview
              </h2>
              <p class="text-lg text-slate-600 leading-relaxed">{{ course.description }}</p>
            </div>

            <!-- Curriculum -->
            <div>
              <h2 class="text-3xl font-black text-slate-900 mb-8 flex items-center gap-3">
                <span class="w-1.5 h-8 rounded-full bg-[#1A66FF]"></span>
                Course Curriculum
              </h2>
              <div v-if="activeLessons.length" class="space-y-4">
                <article v-for="lesson in activeLessons" :key="lesson.id" class="group relative rounded-2xl bg-white p-6 sm:px-8 shadow-[0_4px_20px_-5px_rgba(0,0,0,0.03)] border border-slate-100 transition-all duration-300 hover:shadow-[0_15px_40px_-10px_rgba(26,102,255,0.15)] hover:border-[#1A66FF]/20 overflow-hidden">
                  <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-slate-100 transition-colors group-hover:bg-[#1A66FF]"></div>
                  <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 sm:items-center">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-[#E8F0FF] text-xl font-black text-[#1A66FF] transition-transform group-hover:scale-110 group-hover:bg-[#1A66FF] group-hover:text-white">
                      {{ lesson.order_number }}
                    </span>
                    <div class="min-w-0 flex-1">
                      <h3 class="text-xl font-bold text-slate-900 group-hover:text-[#1A66FF] transition-colors">{{ lesson.title }}</h3>
                      <p v-if="lesson.description" class="mt-2 text-slate-600 line-clamp-2 text-base">{{ lesson.description }}</p>
                      
                      <div v-if="lesson.duration || lesson.video_url" class="mt-4 flex flex-wrap items-center gap-3">
                        <span v-if="lesson.duration" class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 px-3 py-1 text-xs font-bold text-slate-500 border border-slate-200">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                          {{ lesson.duration }} minutes
                        </span>
                        <span v-if="lesson.video_url" class="inline-flex items-center gap-1.5 rounded-full bg-[#FFF5EB] px-3 py-1 text-xs font-bold text-[#FFB800] border border-[#FFB800]/20">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                          Video included
                        </span>
                      </div>
                    </div>
                  </div>
                </article>
              </div>
              <div v-else class="rounded-[2rem] border-2 border-dashed border-slate-200 bg-white p-16 text-center text-slate-500 font-medium">
                <svg class="w-16 h-16 mx-auto text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                Lessons have not been added for this course yet.
              </div>
            </div>
          </div>
          
          <!-- Sticky Sidebar -->
          <div class="lg:col-span-1">
            <div class="sticky top-[120px] rounded-[2.5rem] bg-white p-8 shadow-[0_20px_60px_-15px_rgba(0,0,0,0.05)] border border-slate-100/80">
              <div class="text-center mb-8">
                <p class="text-xs font-bold tracking-widest text-[#1A66FF] uppercase mb-2">Start Learning</p>
                <h3 class="text-3xl font-black text-slate-900">Ready to begin?</h3>
              </div>
              <div class="space-y-4">
                <Link href="/register" class="flex w-full items-center justify-center rounded-2xl bg-[#1A66FF] px-6 py-4 text-base font-black text-white shadow-[0_10px_20px_rgba(26,102,255,0.2)] transition-all hover:bg-[#1555D9] hover:-translate-y-1">
                  Enroll in Course
                </Link>
                <Link href="/courses" class="flex w-full items-center justify-center rounded-2xl bg-slate-50 px-6 py-4 text-base font-bold text-slate-700 hover:bg-slate-100 hover:text-slate-900 transition-all">
                  Browse More
                </Link>
              </div>
              
              <ul class="mt-10 space-y-5 border-t border-slate-100 pt-8 text-base text-slate-700">
                <li class="flex items-center gap-4">
                  <div class="flex-shrink-0 w-8 h-8 rounded-full bg-[#FFF5EB] flex items-center justify-center">
                    <CheckCircle2 class="h-4 w-4 text-[#FFB800]" />
                  </div>
                  <span class="font-bold">100% Online Learning</span>
                </li>
                <li class="flex items-center gap-4">
                  <div class="flex-shrink-0 w-8 h-8 rounded-full bg-[#FFF5EB] flex items-center justify-center">
                    <CheckCircle2 class="h-4 w-4 text-[#FFB800]" />
                  </div>
                  <span class="font-bold">Lifetime access</span>
                </li>
                <li class="flex items-center gap-4">
                  <div class="flex-shrink-0 w-8 h-8 rounded-full bg-[#FFF5EB] flex items-center justify-center">
                    <CheckCircle2 class="h-4 w-4 text-[#FFB800]" />
                  </div>
                  <span class="font-bold">Expert Instruction</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </section>
    </main>

    <FrontendFooter :settings="settings" :menus="menus" />
  </div>
</template>
