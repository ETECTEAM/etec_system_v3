<script setup>
import { computed, ref } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { CheckCircle2, Menu, X } from "@lucide/vue";
import FrontendFooter from "@/components/frontend/FrontendFooter.vue";

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
  <div class="min-h-screen bg-white text-slate-900">
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
          <Link href="/" class="rounded-full px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-[#1e5aa8]/10 hover:text-[#1e5aa8]">Home</Link>
          <Link v-for="item in menus.filter((menu) => menu.slug !== 'home')" :key="item.id" :href="item.url" class="rounded-full px-4 py-2 text-sm font-bold transition hover:bg-[#1e5aa8]/10 hover:text-[#1e5aa8]" :class="['course', 'courses'].includes(item.slug) ? 'bg-[#1e5aa8]/10 text-[#1e5aa8]' : 'text-slate-700'">
            {{ item.name }}
          </Link>
        </nav>

        <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 lg:hidden" aria-label="Toggle menu" :aria-expanded="menuOpen" @click="menuOpen = !menuOpen">
          <X v-if="menuOpen" class="h-5 w-5" />
          <Menu v-else class="h-5 w-5" />
        </button>
      </div>

      <nav v-if="menuOpen" class="mx-4 mb-4 grid gap-1 rounded-2xl border border-slate-200 bg-white p-3 shadow-xl lg:hidden">
        <Link href="/" class="block rounded-xl px-4 py-3 text-center text-sm font-bold text-slate-700 transition hover:bg-[#1e5aa8]/10 hover:text-[#1e5aa8]" @click="menuOpen = false">Home</Link>
        <Link v-for="item in menus.filter((menu) => menu.slug !== 'home')" :key="item.id" :href="item.url" class="block rounded-xl px-4 py-3 text-center text-sm font-bold transition hover:bg-[#1e5aa8]/10 hover:text-[#1e5aa8]" :class="['course', 'courses'].includes(item.slug) ? 'bg-[#1e5aa8]/10 text-[#1e5aa8]' : 'text-slate-700'" @click="menuOpen = false">
          {{ item.name }}
        </Link>
      </nav>
    </header>

    <main class="pt-20">
      <section class="relative overflow-hidden bg-slate-950">
        <img :src="heroImage" :alt="course.title" class="h-[360px] w-full object-cover sm:h-[460px] lg:h-[540px]" />
        <div class="absolute inset-0 bg-gradient-to-r from-[#051833]/90 via-[#051833]/60 to-[#051833]/20"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#051833]/70 via-transparent to-[#051833]/10"></div>
        <div class="absolute inset-0 mx-auto flex max-w-7xl flex-col justify-center px-4 text-white sm:px-6 lg:px-8">
          <p v-if="course.category || course.track" class="text-xs font-black uppercase tracking-[0.22em] text-[#f4a261] sm:text-sm">{{ course.category || course.track }}</p>
          <h1 class="mt-4 max-w-4xl text-4xl font-black leading-[1.06] sm:text-5xl lg:text-6xl">{{ course.title }}</h1>
          <p v-if="course.description" class="mt-5 max-w-2xl text-sm leading-7 text-blue-50 sm:text-lg sm:leading-8">{{ course.description }}</p>
          <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <Link href="/register" class="inline-flex justify-center rounded-full bg-[#f4a261] px-6 py-3 text-sm font-black text-slate-950 shadow-lg shadow-[#f4a261]/20 transition hover:-translate-y-0.5 hover:bg-[#f7c948]">Register Now</Link>
            <Link href="/courses" class="inline-flex justify-center rounded-full bg-white px-6 py-3 text-sm font-black text-[#174981] shadow-lg transition hover:-translate-y-0.5 hover:bg-blue-50">Back to Courses</Link>
          </div>
        </div>
      </section>

      <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-20 lg:px-8">
        <article class="min-w-0">
          <p class="text-sm font-black uppercase tracking-[0.22em] text-[#f4a261]">Course Detail</p>

          <div v-if="course.description" class="rounded-2xl border border-slate-200 bg-white p-6 leading-8 text-slate-600 shadow-sm">
            <h2 class="text-2xl font-black text-slate-950">Course Overview</h2>
            <p class="mt-3">{{ course.description }}</p>
          </div>

          <section class="mt-10">
            <h2 class="text-3xl font-black text-slate-950 sm:text-4xl">What you will study</h2>
            <div v-if="activeLessons.length" class="mt-6 grid gap-4">
              <article v-for="lesson in activeLessons" :key="lesson.id" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-[0_18px_45px_rgba(30,90,168,0.12)]">
                <div class="flex gap-4">
                  <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-[#1e5aa8] text-sm font-black text-white">{{ lesson.order_number }}</span>
                  <div class="min-w-0">
                    <div class="flex items-start gap-2">
                      <CheckCircle2 class="mt-1 h-5 w-5 shrink-0 text-[#f4a261]" />
                      <h3 class="text-lg font-black text-slate-950">{{ lesson.title }}</h3>
                    </div>
                    <p v-if="lesson.description" class="mt-2 leading-7 text-slate-600">{{ lesson.description }}</p>
                    <div v-if="lesson.duration || lesson.video_url" class="mt-3 flex flex-wrap gap-2 text-xs font-bold text-slate-500">
                      <span v-if="lesson.duration" class="rounded-full bg-slate-100 px-3 py-1">{{ lesson.duration }} minutes</span>
                      <span v-if="lesson.video_url" class="rounded-full bg-slate-100 px-3 py-1">Video included</span>
                    </div>
                  </div>
                </div>
              </article>
            </div>
            <div v-else class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-slate-600">
              Lessons have not been added for this course yet.
            </div>
          </section>
        </article>
      </section>
    </main>

    <FrontendFooter :settings="settings" :menus="menus" />
  </div>
</template>
