<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import {
  BookOpen,
  Calendar,
  Check,
  Clock,
  GraduationCap,
  Phone,
  Sparkles,
  UserRound,
} from "@lucide/vue";
import { SelectSearch } from "@/components/ui/select-search";
import { useTheme } from "@/composables/useTheme";

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

const form = useForm({
  name: "",
  gender: "",
  phone: "",
  category_id: "",
  course_id: "",
  term_id: "",
  time_id: "",
});

// A decorative pass number — purely cosmetic, regenerated once per visit.
const ticketCode = ref(
  Math.random().toString(36).slice(2, 7).toUpperCase()
);

const courseOptions = computed(() =>
  props.courses.map((course) => ({ value: String(course.id), label: course.title }))
);
const termOptions = computed(() =>
  props.terms.map((term) => ({ value: String(term.id), label: term.term_name }))
);
const timeOptions = computed(() =>
  props.times.map((time) => ({ value: String(time.id), label: time.time_name }))
);

// Matches this page's own input styling since SelectSearch's default button style assumes a dashboard/dark-mode context.
const selectClass =
  "flex w-full items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-left text-sm font-semibold outline-none transition focus:border-[#1A66FF] focus:bg-white focus:ring-4 focus:ring-[#1A66FF]/10 disabled:cursor-not-allowed disabled:text-slate-400 sm:rounded-xl sm:px-4 sm:py-3 sm:text-base";

const selectedCategory = computed(() =>
  props.categories.find((category) => String(category.id) === String(form.category_id))
);
const selectedCourse = computed(() =>
  props.courses.find((course) => String(course.id) === String(form.course_id))
);
const selectedTerm = computed(() =>
  props.terms.find((term) => String(term.id) === String(form.term_id))
);
const selectedTime = computed(() =>
  props.times.find((time) => String(time.id) === String(form.time_id))
);

const trackedFields = ["name", "gender", "phone", "course_id", "term_id", "time_id"];
const filledCount = computed(
  () => trackedFields.filter((field) => String(form[field] ?? "").length > 0).length
);
const progressPercent = computed(() => Math.round((filledCount.value / trackedFields.length) * 100));

// Category isn't a user-facing field anymore — derive it from whichever
// course gets picked so the backend's category_id/course_id match check still passes.
watch(() => form.course_id, (courseId) => {
  const course = props.courses.find((c) => String(c.id) === String(courseId));
  form.category_id = course ? String(course.category_id) : "";
});

function submit() {
  form.post("/student-register", {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  });
}
</script>

<template>
  <div class="min-h-screen bg-[#F5F8FC] px-3 py-4 font-sans text-slate-900 selection:bg-[#1A66FF]/20 selection:text-[#1A66FF] sm:px-6 sm:py-10 lg:px-8 lg:py-12">
    <main class="mx-auto max-w-6xl">
      <header class="mb-4 max-w-2xl sm:mb-8">
        <p class="flex items-center gap-1.5 text-[9px] font-black uppercase tracking-[0.18em] text-[#1A66FF] sm:text-xs sm:tracking-[0.2em]">
          <Sparkles class="h-3 w-3 sm:h-3.5 sm:w-3.5" /> Enrollment
        </p>
        <h1 class="mt-1 text-xl font-black tracking-tight sm:mt-2 sm:text-4xl">Register for a class</h1>
        <p class="mt-1 text-[11px] font-semibold leading-5 text-slate-500 sm:mt-2 sm:text-sm">
          Fill in your details and pick a course, term, and time. Your pass fills in as you go.
        </p>
      </header>

      <div v-if="flashSuccess" class="mb-4 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-bold text-emerald-700 sm:mb-6 sm:gap-3 sm:px-5 sm:py-4 sm:text-sm">
        <Check class="h-4 w-4 shrink-0 sm:h-5 sm:w-5" />
        {{ flashSuccess }}
      </div>

      <form class="grid gap-5 sm:gap-8 lg:grid-cols-5 lg:items-start" @submit.prevent="submit">
        <!-- Form fields -->
        <div class="space-y-4 lg:col-span-3 lg:space-y-6">
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
                    class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-9 pr-3 text-sm font-semibold outline-none transition focus:border-[#1A66FF] focus:bg-white focus:ring-4 focus:ring-[#1A66FF]/10 sm:rounded-xl sm:py-3 sm:pl-12 sm:pr-4 sm:text-base"
                    placeholder="Your full name"
                  />
                </span>
                <span v-if="form.errors.name" class="text-xs font-semibold text-red-600 sm:text-sm">{{ form.errors.name }}</span>
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
                    class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-9 pr-3 text-sm font-semibold outline-none transition focus:border-[#1A66FF] focus:bg-white focus:ring-4 focus:ring-[#1A66FF]/10 sm:rounded-xl sm:py-3 sm:pl-12 sm:pr-4 sm:text-base"
                    placeholder="012 345 678"
                  />
                </span>
                <span v-if="form.errors.phone" class="text-xs font-semibold text-red-600 sm:text-sm">{{ form.errors.phone }}</span>
              </label>
            </div>
          </section>

          <section class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm sm:rounded-2xl sm:p-7">
            <div class="flex items-center gap-2.5 sm:gap-3">
              <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-[#FFB800]/20 font-mono text-[11px] font-black text-slate-900 sm:h-9 sm:w-9 sm:rounded-xl sm:text-sm">02</span>
              <div>
                <h2 class="text-sm font-black sm:text-lg">Class preference</h2>
                <p class="text-[10px] font-semibold text-slate-400 sm:text-xs">Pick a course, term, and time</p>
              </div>
            </div>

            <div class="mt-3.5 space-y-3.5 sm:mt-5 sm:space-y-5">
              <label class="grid gap-1.5 text-[11px] font-bold sm:gap-2 sm:text-sm">
                Course
                <SelectSearch
                  v-model="form.course_id"
                  :options="courseOptions"
                  placeholder="Select course"
                  search-placeholder="Search course..."
                  :button-class="selectClass"
                />
                <span v-if="form.errors.course_id" class="text-xs font-semibold text-red-600 sm:text-sm">{{ form.errors.course_id }}</span>
                <span v-if="form.errors.category_id" class="text-xs font-semibold text-red-600 sm:text-sm">{{ form.errors.category_id }}</span>
              </label>

              <div class="grid gap-3 sm:grid-cols-2 sm:gap-4">
                <label class="grid gap-1.5 text-[11px] font-bold sm:gap-2 sm:text-sm">
                  Term
                  <SelectSearch
                    v-model="form.term_id"
                    :options="termOptions"
                    placeholder="Select term"
                    search-placeholder="Search term..."
                    :button-class="selectClass"
                  />
                  <span v-if="form.errors.term_id" class="text-xs font-semibold text-red-600 sm:text-sm">{{ form.errors.term_id }}</span>
                </label>

                <label class="grid gap-1.5 text-[11px] font-bold sm:gap-2 sm:text-sm">
                  Time
                  <SelectSearch
                    v-model="form.time_id"
                    :options="timeOptions"
                    placeholder="Select time"
                    search-placeholder="Search time..."
                    :button-class="selectClass"
                  />
                  <span v-if="form.errors.time_id" class="text-xs font-semibold text-red-600 sm:text-sm">{{ form.errors.time_id }}</span>
                </label>
              </div>
            </div>
          </section>

          <button
            type="submit"
            :disabled="form.processing"
            class="flex w-full items-center justify-center gap-2 rounded-full bg-[#1A66FF] px-6 py-3 text-sm font-black text-white shadow-[0_12px_24px_rgba(26,102,255,0.22)] transition hover:bg-[#1555D9] disabled:cursor-not-allowed disabled:opacity-70 sm:px-8 sm:py-4 sm:text-base"
          >
            {{ form.processing ? "Registering…" : "Register" }}
          </button>
        </div>

        <aside class="hidden lg:sticky lg:top-8 lg:col-span-2 lg:block">
          <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="bg-slate-950 p-6 text-white">
              <div class="flex items-center justify-between gap-4">
                <div>
                  <p class="text-[10px] font-black uppercase tracking-[0.22em] text-blue-200">Student pass</p>
                  <p class="mt-2 font-mono text-2xl font-black tracking-widest">{{ ticketCode }}</p>
                </div>
                <div class="grid h-12 w-12 place-items-center rounded-2xl bg-white/10">
                  <GraduationCap class="h-6 w-6 text-[#FFB800]" />
                </div>
              </div>

              <div class="mt-7">
                <div class="flex items-end justify-between text-xs font-bold text-blue-100">
                  <span>Progress</span>
                  <span class="font-mono text-xl text-white">{{ progressPercent }}%</span>
                </div>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/15">
                  <div class="h-full rounded-full bg-[#FFB800] transition-all" :style="{ width: `${progressPercent}%` }"></div>
                </div>
              </div>
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
                <BookOpen class="mt-0.5 h-5 w-5 shrink-0 text-[#1A66FF]" />
                <div class="min-w-0">
                  <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Course</p>
                  <p class="truncate text-sm font-black">{{ selectedCourse?.title || "Choose a course" }}</p>
                  <p class="truncate text-xs font-semibold text-slate-500">{{ selectedCategory?.name || "No category yet" }}</p>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-slate-50 p-4">
                  <Calendar class="h-5 w-5 text-[#1A66FF]" />
                  <p class="mt-3 text-xs font-black uppercase tracking-[0.14em] text-slate-400">Term</p>
                  <p class="truncate text-sm font-black">{{ selectedTerm?.term_name || "Pending" }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                  <Clock class="h-5 w-5 text-[#1A66FF]" />
                  <p class="mt-3 text-xs font-black uppercase tracking-[0.14em] text-slate-400">Time</p>
                  <p class="truncate text-sm font-black">{{ selectedTime?.time_name || "Pending" }}</p>
                </div>
              </div>

              <div class="rounded-xl border border-dashed border-slate-200 p-4 text-xs font-semibold leading-5 text-slate-500">
                Complete all fields before submitting. The pass updates instantly as the student fills the form.
              </div>
            </div>
          </div>
        </aside>
      </form>
    </main>
  </div>
</template>
