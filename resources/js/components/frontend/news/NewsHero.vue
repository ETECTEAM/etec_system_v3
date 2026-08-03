<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";

import { Link } from "@inertiajs/vue3";

import {
    Bell,
    ChevronLeft,
    ChevronRight,
    GraduationCap,
    Newspaper,
    Star,
} from "@lucide/vue";

const props = defineProps({
    schoolName: {
        type: String,
        default: "ETEC Center",
    },

    slides: {
        type: Array,
        default: () => [],
    },
});

const activeSlide = ref(0);
const dragStartX = ref(null);
const dragOffsetX = ref(0);
const isDragging = ref(false);

let slideTimer = null;

/*
|--------------------------------------------------------------------------
| Default static slides
|--------------------------------------------------------------------------
|
| These images are used when no slides are passed from News.vue.
| You can replace these URLs with images from your public folder later.
|
*/
const defaultSlides = [
    {
        image: "https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=1800&q=80",
        subtitle: "Latest School Updates",
        title: "News & Announcements",
        description:
            "Stay informed about school activities, student achievements, educational programs, and important announcements.",
    },
    {
        image: "https://images.unsplash.com/photo-1529390079861-591de354faf5?auto=format&fit=crop&w=1800&q=80",
        subtitle: "Student Achievements",
        title: "Celebrating Learning and Success",
        description:
            "Discover the achievements, projects, and inspiring stories from students across our learning community.",
    },
    {
        image: "https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=1800&q=80",
        subtitle: "School Activities",
        title: "Explore Events and Programs",
        description:
            "Follow our latest workshops, school events, educational activities, and community programs.",
    },
];

const heroSlides = computed(() => {
    return props.slides.length ? props.slides : defaultSlides;
});

const slideCount = computed(() => heroSlides.value.length);

const currentSlide = computed(() => {
    return heroSlides.value[activeSlide.value] ?? defaultSlides[0];
});

const heroTrackStyle = computed(() => ({
    transform: `translate3d(calc(-${
        (activeSlide.value % slideCount.value) * 100
    }% + ${dragOffsetX.value}px), 0, 0)`,

    transitionDuration: isDragging.value ? "0ms" : "900ms",
}));

function startTimer() {
    if (slideTimer) {
        window.clearInterval(slideTimer);
    }

    if (slideCount.value <= 1) {
        return;
    }

    slideTimer = window.setInterval(() => {
        activeSlide.value = (activeSlide.value + 1) % slideCount.value;
    }, 5000);
}

function goToSlide(index) {
    activeSlide.value = index;
    startTimer();
}

function nextSlide() {
    goToSlide((activeSlide.value + 1) % slideCount.value);
}

function previousSlide() {
    goToSlide((activeSlide.value - 1 + slideCount.value) % slideCount.value);
}

function startDrag(event) {
    if (slideCount.value <= 1) {
        return;
    }

    dragStartX.value = event.clientX ?? event.touches?.[0]?.clientX ?? null;

    dragOffsetX.value = 0;
    isDragging.value = true;
}

function moveDrag(event) {
    if (dragStartX.value === null) {
        return;
    }

    const currentX =
        event.clientX ?? event.touches?.[0]?.clientX ?? dragStartX.value;

    dragOffsetX.value = Math.max(
        Math.min(currentX - dragStartX.value, 90),
        -90,
    );
}

function endDrag() {
    if (dragStartX.value === null) {
        return;
    }

    if (dragOffsetX.value <= -35) {
        nextSlide();
    } else if (dragOffsetX.value >= 35) {
        previousSlide();
    }

    dragStartX.value = null;
    dragOffsetX.value = 0;
    isDragging.value = false;
}

onMounted(() => {
    startTimer();
});

onBeforeUnmount(() => {
    if (slideTimer) {
        window.clearInterval(slideTimer);
    }
});
</script>

<template>
    <section
        class="relative min-h-[460px] overflow-hidden bg-[#0a1d3a] text-white sm:min-h-[520px] lg:min-h-[600px]"
        @pointerdown="startDrag"
        @pointermove="moveDrag"
        @pointerup="endDrag"
        @pointercancel="endDrag"
        @pointerleave="endDrag"
    >
        <!-- Sliding Images -->
        <div
            class="absolute inset-0 flex cursor-grab touch-pan-y select-none transition-transform ease-[cubic-bezier(0.22,1,0.36,1)] will-change-transform active:cursor-grabbing"
            :style="heroTrackStyle"
        >
            <div
                v-for="(slide, index) in heroSlides"
                :key="`${slide.image}-${index}`"
                class="relative h-full w-full shrink-0 overflow-hidden"
            >
                <img
                    :src="slide.image"
                    :alt="slide.title || schoolName"
                    draggable="false"
                    class="h-full w-full object-cover object-center"
                />
            </div>
        </div>

        <!-- Dark overlays -->
        <div
            class="pointer-events-none absolute inset-0 bg-gradient-to-r from-[#07162c]/95 via-[#0a1d3a]/75 to-[#0a1d3a]/25"
        ></div>

        <div
            class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#07162c]/90 via-transparent to-[#07162c]/25"
        ></div>

        <!-- Decorative glow -->
        <div
            class="pointer-events-none absolute -right-32 top-10 h-[420px] w-[420px] rounded-full bg-[#1a66ff]/20 blur-[110px]"
        ></div>

        <div
            class="pointer-events-none absolute -bottom-32 left-1/4 h-[360px] w-[360px] rounded-full bg-[#ffb800]/15 blur-[100px]"
        ></div>

        <!-- Content -->
        <div
            class="relative mx-auto flex min-h-[460px] max-w-7xl items-center px-4 py-16 sm:min-h-[520px] sm:px-6 lg:min-h-[600px] lg:px-8"
        >
            <div :key="activeSlide" class="max-w-3xl hero-content-enter">
                <p
                    class="hero-item hero-delay-1 inline-flex items-center gap-3 rounded-full border border-white/20 bg-white/10 px-5 py-2 text-xs font-black uppercase tracking-[0.2em] text-[#ffb800] backdrop-blur-md sm:text-sm"
                >
                    <span class="relative flex h-3 w-3">
                        <span
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#ffb800] opacity-75"
                        ></span>

                        <span
                            class="relative inline-flex h-3 w-3 rounded-full bg-[#ffb800]"
                        ></span>
                    </span>

                    {{ currentSlide.subtitle }}
                </p>

                <h1
                    class="hero-item hero-delay-2 mt-6 max-w-4xl text-4xl font-black leading-[1.05] text-white drop-shadow-md sm:text-5xl lg:text-7xl"
                >
                    {{ currentSlide.title }}
                </h1>

                <p
                    class="hero-item hero-delay-3 mt-6 max-w-2xl text-base leading-8 text-slate-200 sm:text-lg sm:leading-9"
                >
                    {{ currentSlide.description }}
                </p>

                <div
                    class="hero-item hero-delay-4 mt-9 flex flex-col gap-4 sm:flex-row sm:flex-wrap"
                >
                    <a
                        href="#latest-news"
                        class="inline-flex items-center justify-center gap-3 rounded-full bg-[#ffb800] px-8 py-4 text-sm font-black text-slate-950 shadow-[0_12px_30px_rgba(255,184,0,0.25)] transition-all duration-300 hover:-translate-y-1 hover:bg-[#ffc833]"
                    >
                        <Newspaper class="h-5 w-5" />
                        Explore Latest News
                    </a>

                    <Link
                        href="/contact"
                        class="inline-flex items-center justify-center gap-3 rounded-full border border-white/25 bg-white/10 px-8 py-4 text-sm font-black text-white backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:bg-white hover:text-[#0a1d3a]"
                    >
                        <Bell class="h-5 w-5" />
                        Contact School
                    </Link>
                </div>
            </div>

            <!-- Floating education badge -->
            <div
                :key="`badge-top-${activeSlide}`"
                class="floating-badge absolute right-6 top-20 z-20 hidden items-center gap-4 rounded-3xl border border-white/20 bg-white/10 p-4 shadow-2xl backdrop-blur-md md:flex lg:right-[7%] lg:top-24"
            >
                <div class="rounded-full bg-[#ffb800] p-3 text-[#0a1d3a]">
                    <Star class="h-6 w-6 fill-current" />
                </div>

                <div>
                    <p
                        class="text-sm font-black uppercase tracking-wider text-white"
                    >
                        Latest Updates
                    </p>

                    <p class="text-xs text-slate-300">From {{ schoolName }}</p>
                </div>
            </div>

            <!-- Floating learning badge -->
            <div
  :key="`badge-bottom-${activeSlide}`"
  class="floating-badge floating-badge-delay absolute bottom-20 right-6 z-20 hidden items-center gap-4 rounded-3xl border border-white/20 bg-white/10 p-4 shadow-2xl backdrop-blur-md md:flex lg:bottom-24 lg:right-[15%]"
>
  <div class="rounded-full bg-[#1a66ff] p-3 text-white">
    <GraduationCap class="h-6 w-6" />
  </div>

  <div>
    <p class="text-sm font-black uppercase tracking-wider text-white">
      Education News
    </p>

    <p class="text-xs text-slate-300">
      Learn. Grow. Achieve.
    </p>
  </div>
</div>
        </div>

        <!-- Previous button -->
        <button
            v-if="slideCount > 1"
            type="button"
            class="absolute left-5 top-1/2 z-10 hidden h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full border border-white/20 bg-slate-950/30 text-white shadow-xl backdrop-blur-md transition hover:border-transparent hover:bg-[#1a66ff] md:inline-flex"
            aria-label="Previous news hero slide"
            @click.stop="previousSlide"
        >
            <ChevronLeft class="h-6 w-6" />
        </button>

        <!-- Next button -->
        <button
            v-if="slideCount > 1"
            type="button"
            class="group absolute right-5 top-1/2 z-10 hidden h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full border border-white/20 bg-slate-950/30 text-white shadow-xl backdrop-blur-md transition hover:border-transparent hover:bg-[#1a66ff] md:inline-flex"
            aria-label="Next news hero slide"
            @click.stop="nextSlide"
        >
            <ChevronRight
                class="h-6 w-6 transition-transform group-hover:translate-x-0.5"
            />
        </button>

        <!-- Slide indicators -->
        <div
            v-if="slideCount > 1"
            class="absolute bottom-7 left-1/2 z-10 flex -translate-x-1/2 gap-2"
        >
            <button
                v-for="(_, index) in heroSlides"
                :key="index"
                type="button"
                class="h-2.5 rounded-full border border-white/60 transition-all duration-300"
                :class="
                    index === activeSlide
                        ? 'w-10 bg-[#ffb800]'
                        : 'w-2.5 bg-white/50 hover:bg-white'
                "
                :aria-label="`News hero slide ${index + 1}`"
                @click.stop="goToSlide(index)"
            ></button>
        </div>

        <!-- Progress -->
        <div v-if="slideCount > 1" class="absolute inset-x-0 bottom-0 z-10">
            <div class="h-1 bg-white/20">
                <div
                    :key="activeSlide"
                    class="hero-progress h-full bg-[#ffb800]"
                ></div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.hero-progress {
    animation: hero-progress 5s linear forwards;
    transform-origin: left;
}

.hero-content-enter {
    animation: hero-content-enter 700ms ease-out both;
}

.hero-item {
    opacity: 0;
    transform: translateY(28px);
    animation: hero-item-enter 700ms ease-out forwards;
}

.hero-delay-1 {
    animation-delay: 100ms;
}

.hero-delay-2 {
    animation-delay: 220ms;
}

.hero-delay-3 {
    animation-delay: 340ms;
}

.hero-delay-4 {
    animation-delay: 460ms;
}

@keyframes hero-content-enter {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

@keyframes hero-item-enter {
    from {
        opacity: 0;
        transform: translateY(28px);
        filter: blur(5px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
        filter: blur(0);
    }
}

@keyframes hero-progress {
    from {
        transform: scaleX(0);
    }

    to {
        transform: scaleX(1);
    }
}
.floating-badge {
    animation:
        badge-enter 800ms ease-out both,
        badge-float 4s ease-in-out 900ms infinite;
}

.floating-badge-delay {
    animation-delay: 300ms, 1.2s;
}

@keyframes badge-enter {
    from {
        opacity: 0;
        transform: translateX(40px) scale(0.92);
    }

    to {
        opacity: 1;
        transform: translateX(0) scale(1);
    }
}

@keyframes badge-float {
    0%,
    100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-10px);
    }
}
</style>
