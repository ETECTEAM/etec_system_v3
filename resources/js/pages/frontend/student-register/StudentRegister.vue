<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import {
  BookOpen,
  Calendar,
  ChartColumn,
  Check,
  ChevronLeft,
  ChevronRight,
  Clock,
  Code,
  Dumbbell,
  Globe,
  GraduationCap,
  Laptop,
  MessageCircle,
  Palette,
  Phone,
  Search,
  Sparkles,
  Stethoscope,
  UserRound,
} from "@lucide/vue";
import { useTheme } from "@/composables/useTheme";
import { latinNameError } from "@/composables/useLatinNameValidation";

const props = defineProps({
  categories: {
    type: Array,
    default: () => [],
  },
  courses: {
    type: Array,
    default: () => [],
  },
  terms: {
    type: Array,
    default: () => [],
  },
  times: {
    type: Array,
    default: () => [],
  },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);

// This page has no dark-mode styling of its own; it's public and shouldn't
// inherit a dashboard visitor's stored theme preference from app.blade.php's
// FOUC-prevention script, which sets `.dark` on <html> before Vue mounts.
const { resolvedTheme } = useTheme();
onMounted(() => document.documentElement.classList.remove("dark"));
onBeforeUnmount(() => document.documentElement.classList.toggle("dark", resolvedTheme.value === "dark"));

// ---- flow state -----------------------------------------------------
// 'discover' (browse/filter courses) -> 'register' (details for the picked
// course) -> 'success' (backend confirmed the registration).
const view = ref("discover");
const categoryFilter = ref("all");
const searchQuery = ref("");
const selectedCourseId = ref(null);
const selectedSlot = ref(null); // { class_type_id, class_type_name, term_id, term_name, time_id, time_name }

const form = useForm({
  name: "",
  gender: "",
  phone: "",
  attendance_pin: "",
  category_id: "",
  course_id: "",
  class_type_id: "",
  term_id: "",
  time_id: "",
});

const nameLiveError = computed(() => latinNameError(form.name));

// time_name has no dedicated sortable column, just a label like "09:00 am -
// 10:30 am" — parse the start time so slots list morning-first instead of
// alphabetically (which puts "02:00 pm" before "08:00 am").
function timeSortKey(timeName) {
  const match = String(timeName).match(/^(\d{1,2}):(\d{2})\s*(am|pm)/i);

  if (!match) {
    return 0;
  }

  let hour = Number(match[1]);
  const minute = Number(match[2]);
  const meridiem = match[3].toLowerCase();

  if (meridiem === "am" && hour === 12) hour = 0;
  if (meridiem === "pm" && hour !== 12) hour += 12;

  return hour * 60 + minute;
}

// Every real (class type, term, time) combination this course can run in -
// strictly what's toggled open on the Class Schedules picker. A course with
// no open slots for a class type shows none for it here, full stop; there is
// no "uncurated = show everything" fallback, so what the admin sees as OFF
// is never secretly bookable.
function slotsForCourse(course) {
  const courseClassTypes = course.class_types ?? [];
  const slots = [];

  for (const term of props.terms) {
    for (const termClassType of term.class_types ?? []) {
      const allowedIds = (termClassType.time_ids ?? []).map(String);
      const courseClassType = courseClassTypes.find((ct) => ct.class_type_id === termClassType.class_type_id);

      const scopedIds = (courseClassType?.time_ids ?? []).map(String).filter((id) => allowedIds.includes(id));

      for (const idStr of scopedIds) {
        const time = props.times.find((t) => String(t.id) === idStr);

        if (time) {
          slots.push({
            class_type_id: termClassType.class_type_id,
            class_type_name: termClassType.class_type_name,
            term_id: term.id,
            term_name: term.term_name,
            time_id: time.id,
            time_name: time.time_name,
          });
        }
      }
    }
  }

  return slots.sort((a, b) => timeSortKey(a.time_name) - timeSortKey(b.time_name));
}

// Courses with no schedule slot at all can't be registered into, so they're
// dropped from the catalog rather than shown as a dead end.
const coursesWithSlots = computed(() =>
  props.courses
    .map((course) => ({ ...course, slots: slotsForCourse(course) }))
    .filter((course) => course.slots.length > 0)
);

// Only offer categories that actually have a registerable course right now.
const categoryOptions = computed(() => {
  const activeIds = new Set(coursesWithSlots.value.map((c) => String(c.category_id)));

  return props.categories.filter((category) => activeIds.has(String(category.id)));
});

const filteredCourses = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();

  return coursesWithSlots.value.filter((course) => {
    const matchesCategory = categoryFilter.value === "all" || String(course.category_id) === categoryFilter.value;
    const matchesQuery = !q
      || course.title.toLowerCase().includes(q)
      || (course.sub_category_name ?? "").toLowerCase().includes(q);

    return matchesCategory && matchesQuery;
  });
});

const selectedCourse = computed(
  () => coursesWithSlots.value.find((c) => String(c.id) === String(selectedCourseId.value)) ?? null
);

// Grouped Class Type -> Term instead of one flat list.
const groupedSlots = computed(() => {
  if (!selectedCourse.value) {
    return [];
  }

  const classTypeGroups = new Map();

  for (const slot of selectedCourse.value.slots) {
    if (!classTypeGroups.has(slot.class_type_id)) {
      classTypeGroups.set(slot.class_type_id, {
        class_type_id: slot.class_type_id,
        class_type_name: slot.class_type_name,
        terms: new Map(),
      });
    }

    const terms = classTypeGroups.get(slot.class_type_id).terms;

    if (!terms.has(slot.term_id)) {
      terms.set(slot.term_id, { term_id: slot.term_id, term_name: slot.term_name, slots: [] });
    }

    terms.get(slot.term_id).slots.push(slot);
  }

  return Array.from(classTypeGroups.values()).map((group) => ({
    ...group,
    terms: Array.from(group.terms.values()),
  }));
});

function isSlotSelected(slot) {
  return !!selectedSlot.value
    && selectedSlot.value.class_type_id === slot.class_type_id
    && selectedSlot.value.term_id === slot.term_id
    && selectedSlot.value.time_id === slot.time_id;
}

// A small, deterministic icon per category so the catalog reads visually
// even though courses have no thumbnail/description in the database.
const categoryIcons = [Laptop, Code, Palette, ChartColumn, Globe, MessageCircle, Dumbbell, Stethoscope];
function iconForCategory(categoryId) {
  return categoryIcons[Number(categoryId) % categoryIcons.length] ?? BookOpen;
}

function openCourse(course) {
  selectedCourseId.value = course.id;
  selectedSlot.value = null;
  form.clearErrors();
  view.value = "register";
  window.scrollTo({ top: 0, behavior: "smooth" });
}

function goBack() {
  view.value = "discover";
}

function pickSlot(slot) {
  selectedSlot.value = slot;
}

function submit() {
  if (nameLiveError.value || !selectedCourse.value) {
    return;
  }

  form.category_id = String(selectedCourse.value.category_id);
  form.course_id = String(selectedCourse.value.id);
  form.class_type_id = selectedSlot.value ? String(selectedSlot.value.class_type_id) : "";
  form.term_id = selectedSlot.value ? String(selectedSlot.value.term_id) : "";
  form.time_id = selectedSlot.value ? String(selectedSlot.value.time_id) : "";

  form.post("/student-register", {
    preserveScroll: true,
    onSuccess: () => {
      view.value = "success";
      // Clear the form so a stale name/phone/schedule isn't left filled in
      // behind the success screen.
      form.reset();
      form.clearErrors();
      selectedSlot.value = null;
      selectedCourseId.value = null;
    },
  });
}

function registerAnother() {
  view.value = "discover";
  selectedCourseId.value = null;
  selectedSlot.value = null;
  form.reset();
  form.clearErrors();
}

function normalizePhoneInput(event) {
  const value = event.target.value ?? "";
  form.phone = String(value).replace(/\D+/g, "").slice(0, 12);
}
</script>

<template>
  <div class="min-h-screen bg-[#F5F8FC] px-3 py-4 font-sans text-slate-900 selection:bg-[#1A66FF]/20 selection:text-[#1A66FF] sm:px-6 sm:py-10 lg:px-8 lg:py-12">
    <main class="mx-auto max-w-5xl">

      <!-- ============ DISCOVER ============ -->
      <div v-if="view === 'discover'">
        <header class="max-w-2xl">
          <p class="flex items-center gap-1.5 text-[9px] font-black uppercase tracking-[0.18em] text-[#1A66FF] sm:text-xs sm:tracking-[0.2em]">
            <Sparkles class="h-3 w-3 sm:h-3.5 sm:w-3.5" /> Enrollment
          </p>
          <h1 class="mt-1 text-xl font-black tracking-tight sm:mt-2 sm:text-4xl">Find your course</h1>
          <p class="mt-1 text-[11px] font-semibold leading-5 text-slate-500 sm:mt-2 sm:text-sm">
            Browse what we offer, tap the one that fits, then pick a time that works for you.
          </p>
        </header>

        <div class="mt-4 sm:mt-6">
          <span class="relative block">
            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search course or topic..."
              class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm font-semibold outline-none transition focus:border-[#1A66FF] focus:ring-4 focus:ring-[#1A66FF]/10 sm:py-3"
            />
          </span>
        </div>

        <div class="mt-3 flex gap-2 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] sm:mt-4 [&::-webkit-scrollbar]:hidden">
          <button
            type="button"
            class="shrink-0 whitespace-nowrap rounded-full border-2 px-3.5 py-2 text-[11px] font-bold transition sm:text-xs"
            :class="categoryFilter === 'all'
              ? 'border-[#1A66FF] bg-[#1A66FF]/10 text-[#1A66FF]'
              : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300'"
            @click="categoryFilter = 'all'"
          >
            All courses
          </button>
          <button
            v-for="category in categoryOptions"
            :key="category.id"
            type="button"
            class="shrink-0 whitespace-nowrap rounded-full border-2 px-3.5 py-2 text-[11px] font-bold transition sm:text-xs"
            :class="categoryFilter === String(category.id)
              ? 'border-[#1A66FF] bg-[#1A66FF]/10 text-[#1A66FF]'
              : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300'"
            @click="categoryFilter = String(category.id)"
          >
            {{ category.name }}
          </button>
        </div>

        <div class="mt-4 grid gap-2.5 sm:mt-6 sm:grid-cols-2 sm:gap-4">
          <button
            v-for="course in filteredCourses"
            :key="course.id"
            type="button"
            class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white p-3.5 text-left shadow-sm transition hover:border-[#1A66FF]/40 hover:shadow-md sm:p-5"
            @click="openCourse(course)"
          >
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-[#1A66FF]/10 sm:h-13 sm:w-13">
              <component :is="iconForCategory(course.category_id)" class="h-5 w-5 text-[#1A66FF] sm:h-6 sm:w-6" />
            </span>

            <span class="min-w-0 flex-1">
              <span class="flex flex-wrap items-center gap-1.5">
                <span class="text-[9px] font-extrabold uppercase tracking-wide text-[#1A66FF] sm:text-[10px]">{{ course.sub_category_name }}</span>
                <span v-if="course.level" class="h-0.5 w-0.5 rounded-full bg-slate-300"></span>
                <span v-if="course.level" class="text-[9px] font-extrabold uppercase tracking-wide text-slate-400 sm:text-[10px]">{{ course.level }}</span>
              </span>
              <span class="mt-0.5 block text-sm font-black leading-snug text-slate-900 sm:text-base">{{ course.title }}</span>

              <span class="mt-2 flex flex-wrap gap-1.5">
                <span class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-[#F5F8FC] px-2 py-0.5 text-[10px] font-bold text-slate-600">
                  <Calendar class="h-2.5 w-2.5 text-slate-400" /> {{ course.slots[0].term_name }}
                </span>
                <span class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-[#F5F8FC] px-2 py-0.5 text-[10px] font-bold text-slate-600">
                  <Clock class="h-2.5 w-2.5 text-slate-400" /> {{ course.slots[0].time_name }}
                </span>
                <span v-if="course.slots.length > 1" class="inline-flex items-center rounded-full border border-slate-200 bg-[#F5F8FC] px-2 py-0.5 text-[10px] font-bold text-slate-400">
                  +{{ course.slots.length - 1 }} more
                </span>
              </span>
            </span>

            <ChevronRight class="mt-1 h-4 w-4 shrink-0 text-slate-300" />
          </button>

          <div v-if="filteredCourses.length === 0" class="col-span-full py-12 text-center">
            <p class="text-xs font-bold text-slate-400 sm:text-sm">No courses match that search.</p>
            <button
              type="button"
              class="mt-2 text-xs font-extrabold text-[#1A66FF] sm:text-sm"
              @click="categoryFilter = 'all'; searchQuery = ''"
            >
              Clear filters
            </button>
          </div>
        </div>
      </div>

      <!-- ============ REGISTER ============ -->
      <div v-else-if="view === 'register' && selectedCourse">
        <div class="flex items-center gap-2.5 sm:gap-3">
          <button
            type="button"
            class="grid h-8 w-8 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white sm:h-9 sm:w-9 sm:rounded-xl"
            @click="goBack"
          >
            <ChevronLeft class="h-4 w-4" />
          </button>
          <div>
            <p class="text-[9px] font-black uppercase tracking-[0.18em] text-[#1A66FF] sm:text-xs sm:tracking-[0.2em]">Step 2 of 2</p>
            <h1 class="text-lg font-black tracking-tight sm:text-2xl">Your details</h1>
          </div>
        </div>

        <div class="mt-3.5 flex items-center gap-3 rounded-xl bg-slate-950 p-3.5 sm:mt-6 sm:rounded-2xl sm:p-5">
          <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-white/10 sm:h-12 sm:w-12 sm:rounded-xl">
            <GraduationCap class="h-5 w-5 text-[#FFB800] sm:h-6 sm:w-6" />
          </span>
          <div class="min-w-0">
            <p class="truncate text-[9px] font-extrabold uppercase tracking-wide text-blue-200 sm:text-[10px]">
              {{ selectedCourse.sub_category_name }} &middot; {{ selectedCourse.level }}
            </p>
            <p class="truncate text-sm font-black text-white sm:text-base">{{ selectedCourse.title }}</p>
          </div>
        </div>

        <form class="mt-3.5 grid gap-3.5 sm:mt-6 sm:gap-6 lg:grid-cols-5 lg:items-start" @submit.prevent="submit">
          <div class="space-y-3.5 lg:col-span-3 lg:space-y-6">
            <section class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm sm:rounded-2xl sm:p-7">
              <div class="flex items-center gap-2.5 sm:gap-3">
                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-[#1A66FF]/10 font-mono text-[11px] font-black text-[#1A66FF] sm:h-9 sm:w-9 sm:rounded-xl sm:text-sm">01</span>
                <div>
                  <h2 class="text-sm font-black sm:text-lg">Student information</h2>
                  <p class="text-[10px] font-semibold text-slate-400 sm:text-xs">Tell us who's enrolling</p>
                </div>
              </div>

              <div class="mt-3.5 space-y-3.5 sm:mt-5 sm:space-y-5">
                <label class="grid gap-1.5 text-[11px] font-bold sm:gap-2 sm:text-sm">
                  Full name
                  <span class="relative">
                    <UserRound class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400 sm:left-4 sm:h-5 sm:w-5" />
                    <input
                      v-model="form.name"
                      type="text"
                      :class="[
                        'w-full rounded-lg border bg-slate-50 py-2.5 pl-9 pr-3 text-sm font-semibold outline-none transition focus:bg-white focus:ring-4 sm:rounded-xl sm:py-3 sm:pl-12 sm:pr-4 sm:text-base',
                        nameLiveError || form.errors.name ? 'border-red-300 focus:border-red-500 focus:ring-red-100' : 'border-slate-200 focus:border-[#1A66FF] focus:ring-[#1A66FF]/10',
                      ]"
                      placeholder="Your full name"
                    />
                  </span>
                  <span v-if="nameLiveError || form.errors.name" class="text-xs font-semibold text-red-600 sm:text-sm">{{ nameLiveError || form.errors.name }}</span>
                </label>

                <div class="grid gap-1.5 text-[11px] font-bold sm:gap-2 sm:text-sm">
                  Gender
                  <div class="grid grid-cols-2 gap-2.5 sm:gap-3">
                    <button
                      type="button"
                      class="rounded-lg border-2 px-3 py-2.5 text-[11px] font-bold transition sm:rounded-xl sm:px-4 sm:py-3 sm:text-sm"
                      :class="form.gender === 'male'
                        ? 'border-[#1A66FF] bg-[#1A66FF]/10 text-[#1A66FF]'
                        : 'border-slate-200 bg-slate-50 text-slate-500 hover:border-slate-300'"
                      @click="form.gender = 'male'"
                    >
                      Male
                    </button>
                    <button
                      type="button"
                      class="rounded-lg border-2 px-3 py-2.5 text-[11px] font-bold transition sm:rounded-xl sm:px-4 sm:py-3 sm:text-sm"
                      :class="form.gender === 'female'
                        ? 'border-[#1A66FF] bg-[#1A66FF]/10 text-[#1A66FF]'
                        : 'border-slate-200 bg-slate-50 text-slate-500 hover:border-slate-300'"
                      @click="form.gender = 'female'"
                    >
                      Female
                    </button>
                  </div>
                  <span v-if="form.errors.gender" class="text-xs font-semibold text-red-600 sm:text-sm">{{ form.errors.gender }}</span>
                </div>

                <label class="grid gap-1.5 text-[11px] font-bold sm:gap-2 sm:text-sm">
                  Phone number
                  <span class="relative">
                    <Phone class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400 sm:left-4 sm:h-5 sm:w-5" />
                    <input
                      v-model="form.phone"
                      type="text"
                      inputmode="numeric"
                      autocomplete="tel"
                      pattern="[0-9]*"
                      maxlength="12"
                      @input="normalizePhoneInput"
                      class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-9 pr-3 text-sm font-semibold outline-none transition focus:border-[#1A66FF] focus:bg-white focus:ring-4 focus:ring-[#1A66FF]/10 sm:rounded-xl sm:py-3 sm:pl-12 sm:pr-4 sm:text-base"
                      placeholder="012 345 678"
                    />
                  </span>
                  <span v-if="form.errors.phone" class="text-xs font-semibold text-red-600 sm:text-sm">{{ form.errors.phone }}</span>
                </label>

                <label class="grid gap-1.5 text-[11px] font-bold sm:gap-2 sm:text-sm">
                  Attendance PIN
                  <input
                    v-model="form.attendance_pin"
                    type="password"
                    autocomplete="off"
                    class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-semibold outline-none transition placeholder:text-slate-400 focus:border-[#1A66FF] focus:bg-white focus:ring-4 focus:ring-[#1A66FF]/10 sm:rounded-xl sm:px-4 sm:py-3 sm:text-base"
                    placeholder="Optional PIN"
                  />
                </label>
              </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm sm:rounded-2xl sm:p-7">
              <div class="flex items-center gap-2.5 sm:gap-3">
                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-[#FFB800]/20 font-mono text-[11px] font-black text-slate-900 sm:h-9 sm:w-9 sm:rounded-xl sm:text-sm">02</span>
                <div>
                  <h2 class="text-sm font-black sm:text-lg">Pick a schedule</h2>
                  <p class="text-[10px] font-semibold text-slate-400 sm:text-xs">Only slots this course actually runs</p>
                </div>
              </div>

              <div class="mt-3.5 space-y-4 sm:mt-5 sm:space-y-5">
                <div v-for="classTypeGroup in groupedSlots" :key="classTypeGroup.class_type_id">
                  <p class="mb-2 text-xs font-black text-slate-800 sm:text-sm">{{ classTypeGroup.class_type_name }}</p>
                  <div class="grid grid-cols-2 gap-3 sm:gap-5">
                    <div v-for="group in classTypeGroup.terms" :key="group.term_id" class="min-w-0">
                      <p class="mb-1.5 flex items-center gap-1.5 text-[9px] font-black uppercase tracking-wide text-slate-500 sm:mb-2 sm:text-[11px]">
                        <Calendar class="h-3 w-3 shrink-0 text-slate-400" /> {{ group.term_name }}
                      </p>
                      <div class="flex flex-col gap-1.5">
                        <button
                          v-for="slot in group.slots"
                          :key="`${slot.class_type_id}-${slot.term_id}-${slot.time_id}`"
                          type="button"
                          class="flex items-center justify-between gap-1.5 rounded-lg border-2 px-2.5 py-2 text-left transition sm:rounded-xl sm:px-3 sm:py-2.5"
                          :class="isSlotSelected(slot)
                            ? 'border-[#1A66FF] bg-[#1A66FF]/5'
                            : 'border-slate-200 bg-slate-50 hover:border-slate-300'"
                          @click="pickSlot(slot)"
                        >
                          <span class="truncate text-[10px] font-bold text-slate-700 sm:text-xs">{{ slot.time_name }}</span>
                          <Check v-if="isSlotSelected(slot)" class="h-3.5 w-3.5 shrink-0 text-[#1A66FF]" />
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <span v-if="form.errors.class_type_id || form.errors.term_id || form.errors.time_id" class="mt-2 block text-xs font-semibold text-red-600 sm:text-sm">Please pick a schedule slot.</span>
            </section>

            <button
              type="submit"
              :disabled="form.processing || !!nameLiveError || !selectedSlot"
              class="flex w-full items-center justify-center gap-2 rounded-full bg-[#1A66FF] px-6 py-3 text-sm font-black text-white shadow-[0_12px_24px_rgba(26,102,255,0.22)] transition hover:bg-[#1555D9] disabled:cursor-not-allowed disabled:opacity-70 sm:px-8 sm:py-4 sm:text-base"
            >
              {{ form.processing ? "Registering…" : "Complete registration" }}
            </button>
          </div>

          <aside class="hidden lg:sticky lg:top-8 lg:col-span-2 lg:block">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
              <div class="bg-slate-950 p-6 text-white">
                <p class="text-[10px] font-black uppercase tracking-[0.22em] text-blue-200">Enrollment summary</p>
                <p class="mt-2 text-base font-black leading-snug">{{ selectedCourse.title }}</p>
              </div>

              <div class="space-y-4 p-6">
                <div class="flex items-start gap-3 rounded-xl bg-slate-50 p-4">
                  <UserRound class="mt-0.5 h-5 w-5 shrink-0 text-[#1A66FF]" />
                  <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Student</p>
                    <p class="truncate text-sm font-black">{{ form.name || "Waiting for name" }}</p>
                    <p class="text-xs font-semibold capitalize text-slate-500">{{ form.gender || "Gender not selected" }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3 rounded-xl bg-slate-50 p-4">
                  <Calendar class="mt-0.5 h-5 w-5 shrink-0 text-[#1A66FF]" />
                  <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Schedule</p>
                    <p class="truncate text-sm font-black">{{ selectedSlot ? `${selectedSlot.term_name} · ${selectedSlot.time_name}` : "Not picked yet" }}</p>
                  </div>
                </div>

                <div class="rounded-xl border border-dashed border-slate-200 p-4 text-xs font-semibold leading-5 text-slate-500">
                  This updates as you fill the form. Staff confirm your seat right after you submit.
                </div>
              </div>
            </div>
          </aside>
        </form>
      </div>

      <!-- ============ SUCCESS ============ -->
      <div v-else class="flex min-h-[70vh] flex-col items-center justify-center px-4 text-center">
        <span class="grid h-16 w-16 place-items-center rounded-full border border-emerald-200 bg-emerald-50">
          <Check class="h-7 w-7 text-emerald-600" />
        </span>
        <h1 class="mt-4 text-xl font-black sm:text-2xl">All set!</h1>
        <p class="mt-2 max-w-sm text-xs font-semibold leading-6 text-slate-500 sm:text-sm">
          {{ flashSuccess || "Registration received." }}
        </p>
        <button
          type="button"
          class="mt-6 rounded-full bg-slate-950 px-6 py-3 text-xs font-extrabold text-white sm:text-sm"
          @click="registerAnother"
        >
          Browse another course
        </button>
      </div>

    </main>
  </div>
</template>
