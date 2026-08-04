<script setup>
import { computed, ref, watch } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import axios from "axios";
import { Search, CalendarDays, Clock, MapPin, X } from "@lucide/vue";
import FrontendFooter from "@/components/frontend/FrontendFooter.vue";

const props = defineProps({
  classes: {
    type: Object,
    default: () => ({ data: [], meta: null }),
  },
  activeClassFilters: {
    type: Object,
    default: () => ({}),
  },
});

const inertiaPage = usePage();
const website = computed(() => inertiaPage.props.website ?? {});
const settings = computed(() => website.value.settings ?? {});
const menus = computed(() => website.value.menus ?? []);

const classItems = ref(props.classes?.data ?? []);
const classMeta = ref(props.classes?.meta ?? null);
const search = ref(props.activeClassFilters.search ?? "");
const classType = ref(props.activeClassFilters.class_type ?? "");
const filtering = ref(false);
const loadingMore = ref(false);

let filterTimer = null;
let requestSequence = 0;

const hasMore = computed(() => {
  if (!classMeta.value) return false;
  return Number(classMeta.value.current_page) < Number(classMeta.value.last_page);
});

const activeQuery = computed(() => ({
  search: search.value || undefined,
  class_type: classType.value || undefined,
}));

const fetchClasses = async (page = 1, append = false) => {
  const requestId = ++requestSequence;

  filtering.value = !append;
  loadingMore.value = append;

  try {
    const response = await axios.get("/classes/load-more", {
      params: { ...activeQuery.value, page },
    });

    if (requestId !== requestSequence) {
      return;
    }

    classItems.value = append
      ? [...classItems.value, ...(response.data?.data ?? [])]
      : response.data?.data ?? [];
    classMeta.value = response.data?.meta ?? classMeta.value;
  } finally {
    if (requestId === requestSequence) {
      filtering.value = false;
      loadingMore.value = false;
    }
  }
};

const loadMoreClasses = async () => {
  if (loadingMore.value || !hasMore.value) {
    return;
  }

  await fetchClasses(Number(classMeta.value.current_page) + 1, true);
};

const resetFilters = () => {
  search.value = "";
  classType.value = "";
};

watch([search, classType], () => {
  if (filterTimer) {
    window.clearTimeout(filterTimer);
  }

  filterTimer = window.setTimeout(() => {
    fetchClasses(1);
  }, 400);
});

const dayAbbreviations = {
  Monday: "Mon",
  Tuesday: "Tue",
  Wednesday: "Wed",
  Thursday: "Thu",
  Friday: "Fri",
  Saturday: "Sat",
  Sunday: "Sun",
};

function scheduleLabel(cls) {
  const days = (cls.study_days ?? []).map((day) => dayAbbreviations[day] ?? day);
  return days.length ? days.join(" & ") : "Schedule TBA";
}

function timeLabel(cls) {
  return cls.start_time && cls.end_time ? `${cls.start_time} - ${cls.end_time}` : "Time TBA";
}

function priceLabel(cls) {
  return `$${Number(cls.price ?? 0).toFixed(2)}`;
}

const joinModalOpen = ref(false);
const activeJoinClass = ref(null);
const joinForm = useForm({
  name: "",
  gender: "",
  phone: "",
});

function openJoinModal(cls) {
  activeJoinClass.value = cls;
  joinForm.reset();
  joinForm.clearErrors();
  joinModalOpen.value = true;
}

function closeJoinModal() {
  joinModalOpen.value = false;
  activeJoinClass.value = null;
}

function submitJoin() {
  joinForm.post(`/classes/${activeJoinClass.value.id}/join`, {
    preserveScroll: true,
    onSuccess: () => closeJoinModal(),
  });
}
</script>

<template>
  <div class="min-h-screen bg-[#F4F7FA] font-sans selection:bg-[#1A66FF]/20 selection:text-[#1A66FF] [--public-header-height:5.5rem]">
    <section class="relative bg-[#0A1D3A] bg-gradient-to-tr from-[#0A1D3A] via-[#0A1D3A] to-[#12305c]">
      <div class="absolute inset-0 bg-gradient-to-tr from-[#1A66FF]/20 to-[#FFB800]/10 mix-blend-overlay"></div>
      <div class="relative z-10 mx-auto max-w-7xl px-4 py-24 sm:px-6 sm:py-28 lg:px-8">
        <p class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-slate-900/30 px-4 py-1.5 text-sm font-bold text-white shadow-xl backdrop-blur-md">
          Classes
        </p>
        <h1 class="mt-6 max-w-2xl text-4xl font-black text-white sm:text-6xl">Our Classes</h1>
        <p class="mt-4 max-w-xl text-lg text-white/70">Browse the classes currently open for enrollment — pick a schedule that fits and register today.</p>
      </div>
    </section>

    <main class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
      <section class="space-y-12">
        <div class="rounded-[2rem] border border-slate-100 bg-white p-6 shadow-[0_10px_40px_-10px_rgba(0,0,0,0.05)] sm:p-8">
          <div class="grid gap-6 lg:grid-cols-[1fr_220px_auto] lg:items-end">
            <label class="grid gap-3 text-sm font-bold text-slate-900">
              Search classes
              <span class="relative">
                <Search class="pointer-events-none absolute left-5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                <input v-model="search" type="search" class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-4 pl-12 pr-4 text-base font-medium outline-none transition focus:border-[#1A66FF] focus:bg-white focus:ring-4 focus:ring-[#1A66FF]/10 placeholder:font-normal" placeholder="Class or course name" />
              </span>
            </label>

            <label class="grid gap-3 text-sm font-bold text-slate-900">
              Class Type
              <select v-model="classType" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-base font-medium outline-none transition focus:border-[#1A66FF] focus:bg-white focus:ring-4 focus:ring-[#1A66FF]/10">
                <option value="">All types</option>
                <option value="physical">Physical</option>
                <option value="online">Online</option>
              </select>
            </label>

            <button type="button" class="rounded-2xl border border-slate-200 bg-white px-8 py-4 text-base font-bold text-slate-700 transition hover:border-[#1A66FF] hover:text-[#1A66FF] shadow-sm hover:shadow-md" @click="resetFilters">
              Reset
            </button>
          </div>
        </div>

        <div v-if="classItems.length" class="relative">
          <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3" :class="{ 'pointer-events-none opacity-60': filtering }" :aria-busy="filtering">
            <article v-for="cls in classItems" :key="cls.id" class="flex flex-col h-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition-all duration-300 hover:shadow-xl">
              <div class="relative w-full h-40 overflow-hidden bg-slate-100">
                <img v-if="cls.course_thumbnail_url" :src="cls.course_thumbnail_url" :alt="cls.course_title" class="h-full w-full object-cover" />
                <div v-else class="grid h-full w-full place-items-center bg-slate-200 text-slate-400">
                  <span class="text-4xl font-black opacity-30">{{ (cls.course_title || cls.title)?.charAt(0) ?? "C" }}</span>
                </div>
                <span
                  class="absolute left-3 top-3 rounded-full px-3 py-1 text-xs font-bold shadow-sm"
                  :class="cls.class_type === 'online' ? 'bg-[#1A66FF] text-white' : 'bg-[#FFB800] text-slate-900'"
                >
                  {{ cls.class_type_label }}
                </span>
              </div>

              <div class="flex flex-col flex-1 p-5 sm:p-6">
                <p class="text-xs font-bold uppercase tracking-wide text-[#1A66FF] mb-1">{{ cls.course_title }}</p>
                <h3 class="text-lg font-bold text-slate-900 leading-tight mb-3 line-clamp-2">{{ cls.title }}</h3>

                <div class="space-y-2 text-sm text-slate-600 mb-4">
                  <div class="flex items-center gap-2">
                    <CalendarDays class="w-4 h-4 shrink-0 text-slate-400" />
                    <span>{{ scheduleLabel(cls) }}</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <Clock class="w-4 h-4 shrink-0 text-slate-400" />
                    <span>{{ timeLabel(cls) }}</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <MapPin class="w-4 h-4 shrink-0 text-slate-400" />
                    <span>{{ cls.location ?? "Online" }}</span>
                  </div>
                </div>

                <div class="mt-auto">
                  <div class="flex items-center justify-between text-xs font-semibold text-slate-500 mb-1">
                    <span>{{ cls.current_students }}/{{ cls.capacity }} seats</span>
                    <span>{{ cls.available_seats }} left</span>
                  </div>
                  <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100 mb-4">
                    <div class="h-full rounded-full bg-[#1A66FF]" :style="{ width: cls.filled_percentage + '%' }"></div>
                  </div>

                  <div class="h-px w-full bg-slate-200 my-4"></div>

                  <div class="flex items-center justify-between mb-4">
                    <span class="text-sm text-slate-500">Starts {{ cls.start_date ?? "TBA" }}</span>
                    <span class="font-bold text-[#1A66FF] text-base">{{ priceLabel(cls) }}</span>
                  </div>

                  <button
                    type="button"
                    class="w-full rounded-full bg-[#1A66FF] px-4 py-3 text-sm font-black text-white transition hover:bg-[#1555D9] disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400"
                    :disabled="cls.available_seats <= 0"
                    @click="openJoinModal(cls)"
                  >
                    {{ cls.available_seats > 0 ? "Request to Join" : "Class Full" }}
                  </button>
                </div>
              </div>
            </article>
          </div>

          <div v-if="filtering" class="pointer-events-none absolute inset-x-0 top-0 flex justify-center z-20">
            <span class="rounded-full border border-slate-100 bg-white px-6 py-3 text-sm font-black text-[#1A66FF] shadow-xl animate-pulse">Updating classes...</span>
          </div>
        </div>

        <div v-else-if="filtering" class="rounded-[2rem] border border-slate-100 bg-white p-12 text-center text-lg font-bold text-slate-600 shadow-sm animate-pulse">Loading classes...</div>

        <div v-if="!filtering && classItems.length && hasMore" class="flex justify-center pt-8">
          <button type="button" class="rounded-full bg-[#FFB800] px-10 py-4 text-base font-black text-slate-900 shadow-[0_10px_30px_rgba(255,184,0,0.3)] transition hover:-translate-y-1 hover:bg-[#ffc833] disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:translate-y-0" :disabled="loadingMore" @click="loadMoreClasses">
            {{ loadingMore ? "Loading..." : "Load More Classes" }}
          </button>
        </div>

        <div v-if="!filtering && !classItems.length" class="rounded-[2rem] border-2 border-dashed border-slate-200 bg-white p-16 text-center shadow-sm">
          <Search class="mx-auto h-12 w-12 text-slate-300 mb-4" />
          <p class="text-xl font-black text-slate-700">No classes found.</p>
          <p class="mt-2 text-base text-slate-500">Try changing the search or class type filter.</p>
        </div>
      </section>
    </main>

    <FrontendFooter :settings="settings" :menus="menus" />

    <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="joinModalOpen" class="fixed inset-0 z-[200] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4" @click.self="closeJoinModal">
        <div class="w-full max-w-md rounded-[2rem] bg-white p-8 shadow-2xl">
          <div class="flex items-start justify-between mb-6">
            <div>
              <p class="text-xs font-bold uppercase tracking-wide text-[#1A66FF] mb-1">Request to Join</p>
              <h3 class="text-xl font-black text-slate-900">{{ activeJoinClass?.title }}</h3>
            </div>
            <button type="button" class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" @click="closeJoinModal">
              <X class="h-5 w-5" />
            </button>
          </div>

          <form @submit.prevent="submitJoin" class="grid gap-4">
            <label class="grid gap-2 text-sm font-bold text-slate-900">
              Full Name
              <input v-model="joinForm.name" type="text" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-base font-medium outline-none transition focus:border-[#1A66FF] focus:bg-white focus:ring-4 focus:ring-[#1A66FF]/10" placeholder="Your full name" />
              <span v-if="joinForm.errors.name" class="text-sm font-semibold text-red-600">{{ joinForm.errors.name }}</span>
            </label>

            <label class="grid gap-2 text-sm font-bold text-slate-900">
              Gender
              <select v-model="joinForm.gender" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-base font-medium outline-none transition focus:border-[#1A66FF] focus:bg-white focus:ring-4 focus:ring-[#1A66FF]/10">
                <option value="" disabled>Select gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
              </select>
              <span v-if="joinForm.errors.gender" class="text-sm font-semibold text-red-600">{{ joinForm.errors.gender }}</span>
            </label>

            <label class="grid gap-2 text-sm font-bold text-slate-900">
              Phone Number
              <input v-model="joinForm.phone" type="text" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-base font-medium outline-none transition focus:border-[#1A66FF] focus:bg-white focus:ring-4 focus:ring-[#1A66FF]/10" placeholder="012 345 678" />
              <span v-if="joinForm.errors.phone" class="text-sm font-semibold text-red-600">{{ joinForm.errors.phone }}</span>
            </label>

            <button type="submit" class="mt-2 rounded-full bg-[#1A66FF] px-8 py-4 text-base font-black text-white shadow-[0_10px_20px_rgba(26,102,255,0.2)] transition-all hover:bg-[#1555D9] disabled:cursor-not-allowed disabled:opacity-70" :disabled="joinForm.processing">
              {{ joinForm.processing ? "Sending..." : "Send Request" }}
            </button>
          </form>
        </div>
      </div>
    </transition>
  </div>
</template>
