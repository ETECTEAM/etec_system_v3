<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import axios from "axios";
import { useToast } from "vue-toastification";
import {
  Search,
  CalendarDays,
  MapPin,
  DollarSign,
  CheckCircle2,
  Loader2,
  X,
  Building2,
  BookOpen,
  Code2,
  Database,
  Monitor,
  Network,
  Palette,
  Smartphone,
  Users,
} from "@lucide/vue";
import FrontendFooter from "@/components/frontend/FrontendFooter.vue";

const toast = useToast();

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
const showSkeleton = ref(false);
const skeletonCards = 4;

let filterTimer = null;
let requestSequence = 0;
let showSkeletonTimer = null;
let finishFilterTimer = null;
let skeletonShownAt = 0;
const skeletonDelayMs = 160;
const minimumSkeletonMs = 320;

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

  if (finishFilterTimer) {
    window.clearTimeout(finishFilterTimer);
    finishFilterTimer = null;
  }

  filtering.value = !append;
  loadingMore.value = append;

  if (!append) {
    showSkeleton.value = false;
    skeletonShownAt = 0;

    if (showSkeletonTimer) {
      window.clearTimeout(showSkeletonTimer);
    }

    showSkeletonTimer = window.setTimeout(() => {
      if (requestId === requestSequence && filtering.value) {
        skeletonShownAt = window.performance.now();
        showSkeleton.value = true;
      }
    }, skeletonDelayMs);
  }

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
      loadingMore.value = false;

      if (!append) {
        if (showSkeletonTimer) {
          window.clearTimeout(showSkeletonTimer);
          showSkeletonTimer = null;
        }

        if (!showSkeleton.value) {
          filtering.value = false;
          return;
        }

        const elapsed = window.performance.now() - skeletonShownAt;
        const remaining = Math.max(minimumSkeletonMs - elapsed, 0);

        finishFilterTimer = window.setTimeout(() => {
          if (requestId === requestSequence) {
            showSkeleton.value = false;
            filtering.value = false;
          }
        }, remaining);
      } else {
        filtering.value = false;
      }
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
  return days.length ? days.join(", ") : "Schedule to be announced";
}

function timeLabel(cls) {
  return cls.start_time && cls.end_time ? `${cls.start_time}–${cls.end_time}` : "Time to be announced";
}

function scheduleTimeLabel(cls) {
  if (!cls.start_time || !cls.end_time) {
    return scheduleLabel(cls);
  }

  return `${scheduleLabel(cls)}, ${timeLabel(cls)}`;
}

function locationLabel(cls) {
  if (cls.class_type === "online") {
    return "Online";
  }

  const building = cls.building_name ? `${cls.building_name}` : null;
  const floor = cls.floor_name ? `Floor ${cls.floor_name}` : null;
  const room = cls.room_number ? `Room ${cls.room_number}` : null;

  return [building, floor, room].filter(Boolean).join(", ") || cls.location || "Location to be announced";
}

function priceLabel(cls) {
  return `$${Number(cls.price ?? 0).toFixed(2)}`;
}

function categoryLabel(cls) {
  return cls.course_category || cls.course_title || "Course";
}

function normalizeClassLabel(label) {
  const normalized = String(label ?? "").trim();

  if (!normalized) {
    return "Physical";
  }

  return normalized
    .toLowerCase()
    .replace(/\s*class$/, "")
    .replace(/^\w/, (letter) => letter.toUpperCase());
}

const categoryAccents = [
  { pattern: /python/i, accent: "#15803d", tint: "#EAF7EE", icon: Code2 },
  { pattern: /c\+\+|cpp|oop|object/i, accent: "#6d28d9", tint: "#F1EAFF", icon: Code2 },
  { pattern: /web|front|html|css|javascript|react|vue|browser|it/i, accent: "#2563eb", tint: "#EAF1FF", icon: Monitor },
  { pattern: /code|program|backend|php|laravel|api|software/i, accent: "#0f766e", tint: "#E8F4F2", icon: Code2 },
  { pattern: /mobile|android|ios|app/i, accent: "#7c3aed", tint: "#F1EAFF", icon: Smartphone },
  { pattern: /database|data|sql/i, accent: "#0891b2", tint: "#E7F6FA", icon: Database },
  { pattern: /design|graphic|ui|ux|photo/i, accent: "#be185d", tint: "#FCEAF3", icon: Palette },
  { pattern: /network|security|server|cloud/i, accent: "#c2410c", tint: "#FFF1E8", icon: Network },
];

function classAccent(cls) {
  const label = `${cls.course_category ?? ""} ${cls.course_title ?? ""} ${cls.title ?? ""}`;
  return categoryAccents.find((item) => item.pattern.test(label)) ?? {
    accent: "#2563eb",
    tint: "#EAF1FF",
    icon: BookOpen,
  };
}

function cardAccentStyle(cls) {
  const accent = classAccent(cls);
  return {
    "--class-accent": accent.accent,
    "--class-tint": accent.tint,
  };
}

function cardIcon(cls) {
  return classAccent(cls).icon;
}

function skeletonVisibilityClass(index) {
  if (index === 1) return "flex";
  if (index === 2) return "hidden sm:flex";
  if (index === 3) return "hidden lg:flex";
  return "hidden xl:flex";
}

function seatsFilledLabel(cls) {
  return `${cls.current_students ?? 0} of ${cls.capacity ?? 0} seats filled`;
}

function startDateLabel(cls) {
  return cls.start_date ? `Starts ${cls.start_date}` : "Starts to be announced";
}

function seatsLeftLabel(cls) {
  const seats = Number(cls.available_seats ?? 0);
  return seats === 1 ? "1 left" : `${seats} left`;
}

function seatsBadgeClass(cls) {
  return Number(cls.available_seats ?? 0) <= 5
    ? "bg-red-50 text-red-700 ring-red-200"
    : "bg-white text-slate-700 ring-slate-200";
}

const registerModalOpen = ref(false);
const showPendingModal = ref(false);
const pendingName = ref("");
const pendingPhone = ref("");
const pendingClassTitle = ref("");
const activeRegisterClass = ref(null);
const registerForm = useForm({
  name: "",
  gender: "",
  phone: "",
});

const REGISTRATION_STORAGE = {
  id: "active_registration_id",
  name: "active_registration_name",
  phone: "active_registration_phone",
  classTitle: "active_registration_class_title",
};

let paymentPollTimer = null;

function openRegisterModal(cls) {
  activeRegisterClass.value = cls;
  registerForm.reset();
  registerForm.clearErrors();
  registerModalOpen.value = true;
  document.body.classList.add("overflow-hidden");
}

function closeRegisterModal() {
  registerModalOpen.value = false;
  activeRegisterClass.value = null;
  document.body.classList.remove("overflow-hidden");
}

function submitRegister() {
  const classId = activeRegisterClass.value?.id;
  const classTitle = activeRegisterClass.value?.title ?? "";
  const name = registerForm.name;
  const phone = registerForm.phone;

  registerForm.post(`/classes/${activeRegisterClass.value.id}/register`, {
    preserveScroll: true,
    onSuccess: () => {
      closeRegisterModal();

      const enrollmentId = inertiaPage.props.flash?.enrollment_id;

      if (enrollmentId) {
        localStorage.setItem(REGISTRATION_STORAGE.id, String(enrollmentId));
        localStorage.setItem(REGISTRATION_STORAGE.name, name);
        localStorage.setItem(REGISTRATION_STORAGE.phone, phone);
        localStorage.setItem(REGISTRATION_STORAGE.classTitle, classTitle);
        openPendingModal(enrollmentId, name, phone, classTitle);

        // Server won't reflect this until the next page load - patch the card we
        // already have in memory so the button disables immediately instead of
        // only after a refresh.
        const registeredClass = classItems.value.find((cls) => cls.id === classId);
        if (registeredClass) {
          registeredClass.already_registered = true;
          registeredClass.current_students += 1;
          registeredClass.available_seats = Math.max(registeredClass.available_seats - 1, 0);
          registeredClass.filled_percentage = registeredClass.capacity > 0
            ? Math.round((registeredClass.current_students / registeredClass.capacity) * 10000) / 100
            : registeredClass.filled_percentage;
        }
      }
    },
  });
}

function stopPaymentPolling() {
  if (paymentPollTimer) {
    window.clearInterval(paymentPollTimer);
    paymentPollTimer = null;
  }
}

async function pollPaymentStatus(enrollmentId) {
  try {
    const response = await axios.get(`/public/enrollments/${enrollmentId}/status`);

    if (response.data?.payment_status === "Paid") {
      stopPaymentPolling();
      localStorage.removeItem(REGISTRATION_STORAGE.id);
      localStorage.removeItem(REGISTRATION_STORAGE.name);
      localStorage.removeItem(REGISTRATION_STORAGE.phone);
      localStorage.removeItem(REGISTRATION_STORAGE.classTitle);
      showPendingModal.value = false;
      document.body.classList.remove("overflow-hidden");
      toast.success("Payment confirmed! Your class registration is active.");
    }
  } catch {
    // Transient polling failures must not unlock the modal; retry next tick.
  }
}

function startPaymentPolling(enrollmentId) {
  stopPaymentPolling();
  pollPaymentStatus(enrollmentId);
  paymentPollTimer = window.setInterval(() => {
    pollPaymentStatus(enrollmentId);
  }, 3000);
}

function openPendingModal(enrollmentId, name, phone, classTitle) {
  pendingName.value = name;
  pendingPhone.value = phone;
  pendingClassTitle.value = classTitle;
  showPendingModal.value = true;
  document.body.classList.add("overflow-hidden");
  startPaymentPolling(enrollmentId);
}

function onKeydown(event) {
  if (event.key === "Escape" && registerModalOpen.value) {
    closeRegisterModal();
    return;
  }

  if (event.key === "Escape" && showPendingModal.value) {
    event.preventDefault();
    event.stopPropagation();
  }
}

onMounted(() => {
  window.addEventListener("keydown", onKeydown, true);

  const storedId = localStorage.getItem(REGISTRATION_STORAGE.id);

  if (storedId) {
    openPendingModal(
      storedId,
      localStorage.getItem(REGISTRATION_STORAGE.name) ?? "",
      localStorage.getItem(REGISTRATION_STORAGE.phone) ?? "",
      localStorage.getItem(REGISTRATION_STORAGE.classTitle) ?? "",
    );
  }
});

onUnmounted(() => {
  if (showSkeletonTimer) {
    window.clearTimeout(showSkeletonTimer);
  }

  if (finishFilterTimer) {
    window.clearTimeout(finishFilterTimer);
  }

  stopPaymentPolling();
  window.removeEventListener("keydown", onKeydown, true);
  document.body.classList.remove("overflow-hidden");
});
</script>

<template>
  <div class="min-h-screen bg-[#F5F6F8] font-sans selection:bg-slate-900/10 selection:text-slate-950 [--public-header-height:5.5rem]">
    <section class="bg-[#0A1D3A]">
      <div class="mx-auto max-w-7xl px-8 pb-16 pt-10">
        <h1 class="max-w-2xl text-[28px] font-medium leading-tight text-white">Our classes</h1>
        <p class="mt-3 max-w-[480px] text-sm leading-6 text-white/65">Browse the classes currently open for enrollment and pick a schedule that fits.</p>
      </div>
    </section>

    <main class="mx-auto -mt-5 max-w-7xl px-4 pb-10 sm:px-6 lg:px-8">
      <section class="space-y-6">
        <div class="rounded-xl border border-[#E5E7EB] bg-white p-4 shadow-sm shadow-slate-200/60">
          <div class="grid gap-4 lg:grid-cols-[1fr_220px_auto] lg:items-end">
            <label class="grid gap-2 text-sm font-semibold text-slate-900">
              Search classes
              <span class="relative">
                <Search class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input v-model="search" type="search" class="w-full rounded-xl border border-[#E5E7EB] bg-white py-3 pl-11 pr-4 text-sm font-medium text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-900/5" placeholder="Class or course name" />
              </span>
            </label>

            <label class="grid gap-2 text-sm font-semibold text-slate-900">
              Class type
              <select v-model="classType" class="w-full rounded-xl border border-[#E5E7EB] bg-white px-4 py-3 text-sm font-medium text-slate-900 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-900/5">
                <option value="">All types</option>
                <option value="physical">Physical</option>
                <option value="online">Online</option>
              </select>
            </label>

            <button type="button" class="rounded-xl border border-[#E5E7EB] bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:text-slate-950" @click="resetFilters">
              Reset
            </button>
          </div>
        </div>

        <Transition
          mode="out-in"
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 translate-y-2"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-1"
        >
          <div v-if="showSkeleton" key="class-skeletons" class="flex min-h-[340px] flex-wrap justify-start gap-4" aria-busy="true" aria-live="polite">
            <article
              v-for="item in skeletonCards"
              :key="`class-skeleton-${item}`"
              :class="[
                'h-full w-full animate-pulse flex-col overflow-hidden rounded-xl border border-[#E5E7EB] bg-white sm:w-[calc((100%-1rem)/2)] lg:w-[calc((100%-2rem)/3)] xl:w-[calc((100%-3rem)/4)]',
                skeletonVisibilityClass(item),
              ]"
            >
              <div class="relative h-[110px] bg-slate-100">
                <div class="absolute left-3 top-3 h-6 w-20 rounded-full bg-white ring-1 ring-[#E5E7EB]"></div>
                <div class="absolute right-3 top-3 h-6 w-16 rounded-full bg-white ring-1 ring-[#E5E7EB]"></div>
                <div class="absolute left-1/2 top-1/2 h-11 w-11 -translate-x-1/2 -translate-y-1/2 rounded-xl bg-slate-200"></div>
              </div>

              <div class="flex flex-1 flex-col p-[14px]">
                <div class="h-3 w-24 rounded-full bg-slate-200"></div>
                <div class="mt-3 h-4 w-4/5 rounded-full bg-slate-200"></div>
                <div class="mt-4 space-y-2">
                  <div class="h-3 w-3/4 rounded-full bg-slate-200"></div>
                  <div class="h-3 w-5/6 rounded-full bg-slate-200"></div>
                </div>
                <div class="mt-4 flex items-center justify-between gap-3">
                  <div class="h-3 w-28 rounded-full bg-slate-200"></div>
                  <div class="h-3 w-24 rounded-full bg-slate-200"></div>
                </div>
                <div class="mt-2 h-1 rounded-full bg-slate-200"></div>
                <div class="mt-4 flex items-center justify-between gap-4">
                  <div class="h-5 w-20 rounded-full bg-slate-200"></div>
                  <div class="h-9 w-24 rounded-full bg-slate-200"></div>
                </div>
              </div>
            </article>
          </div>

          <div v-else-if="classItems.length" key="class-results" class="relative min-h-[340px]">
            <div class="flex flex-wrap justify-start gap-4">
            <article
              v-for="cls in classItems"
              :key="cls.id"
              class="flex h-full w-full flex-col overflow-hidden rounded-xl border border-[#E5E7EB] bg-white sm:w-[calc((100%-1rem)/2)] lg:w-[calc((100%-2rem)/3)] xl:w-[calc((100%-3rem)/4)]"
              :style="cardAccentStyle(cls)"
            >
              <div class="relative grid h-[110px] w-full place-items-center overflow-hidden" :style="{ backgroundColor: 'var(--class-tint)' }">
                <component :is="cardIcon(cls)" class="h-11 w-11 stroke-[1.6]" :style="{ color: 'var(--class-accent)' }" />
                <span class="absolute left-3 top-3 inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-[#E5E7EB]">
                  <MapPin class="h-3 w-3" />
                  {{ normalizeClassLabel(cls.class_type_label) }}
                </span>
                <span
                  class="absolute right-3 top-3 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1"
                  :class="seatsBadgeClass(cls)"
                >
                  <Users class="h-3 w-3" />
                  {{ seatsLeftLabel(cls) }}
                </span>
              </div>

              <div class="flex flex-1 flex-col p-[14px]">
                <p class="mb-2 truncate text-xs font-semibold" :style="{ color: 'var(--class-accent)' }">{{ categoryLabel(cls) }}</p>
                <h3 class="line-clamp-2 text-[15px] font-bold leading-snug text-slate-900">{{ cls.title }}</h3>

                <div class="mt-3 space-y-2 text-sm text-slate-600">
                  <div class="flex min-w-0 items-center gap-2">
                    <CalendarDays class="h-4 w-4 shrink-0 text-slate-400" />
                    <span class="truncate">{{ scheduleTimeLabel(cls) }}</span>
                  </div>
                  <div class="flex min-w-0 items-center gap-2">
                    <Building2 class="h-4 w-4 shrink-0 text-slate-400" />
                    <span class="truncate">{{ locationLabel(cls) }}</span>
                  </div>
                </div>

                <div class="mt-4">
                  <div class="mb-2 flex items-center justify-between gap-3 text-xs font-medium text-slate-500">
                    <span class="truncate">{{ seatsFilledLabel(cls) }}</span>
                    <span class="shrink-0">{{ startDateLabel(cls) }}</span>
                  </div>
                  <div class="h-1 w-full overflow-hidden rounded-full bg-[#E5E7EB]">
                    <div class="h-full rounded-full" :style="{ width: `${Math.min(cls.filled_percentage ?? 0, 100)}%`, backgroundColor: 'var(--class-accent)' }"></div>
                  </div>
                </div>

                <div class="mt-auto flex items-center justify-between gap-4 pt-4">
                  <span class="text-[17px] font-bold tabular-nums text-slate-900">{{ priceLabel(cls) }}</span>
                  <button
                    type="button"
                    class="shrink-0 rounded-full bg-[#1A66FF] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#1555D9] disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400 disabled:hover:bg-slate-200"
                    :disabled="cls.already_registered || cls.available_seats <= 0"
                    @click="openRegisterModal(cls)"
                  >
                    {{ cls.already_registered ? "Already registered" : (cls.available_seats > 0 ? "Register" : "Class full") }}
                  </button>
                </div>
              </div>
            </article>
            </div>
          </div>
        </Transition>

        <div v-if="!filtering && classItems.length && hasMore" class="flex justify-center pt-8">
          <button type="button" class="rounded-full bg-[#FFB800] px-10 py-4 text-base font-black text-slate-900 transition hover:bg-[#ffc833] disabled:cursor-not-allowed disabled:opacity-70" :disabled="loadingMore" @click="loadMoreClasses">
            {{ loadingMore ? "Loading..." : "Load more classes" }}
          </button>
        </div>

        <div v-if="!filtering && !classItems.length" class="rounded-xl border-2 border-dashed border-slate-200 bg-white p-16 text-center">
          <Search class="mx-auto h-12 w-12 text-slate-300 mb-4" />
          <p class="text-xl font-black text-slate-700">No classes found.</p>
          <p class="mt-2 text-base text-slate-500">Try changing the search or class type filter.</p>
        </div>
      </section>
    </main>

    <FrontendFooter :settings="settings" :menus="menus" />

    <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="registerModalOpen" class="fixed inset-0 z-[200] flex items-center justify-center bg-black/60 p-4" @click.self="closeRegisterModal">
        <div class="w-full max-w-[420px] rounded-2xl bg-white p-6 shadow-xl" role="dialog" aria-modal="true" aria-labelledby="class-registration-title">
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-[#1A66FF]">Class registration</p>
              <h3 id="class-registration-title" class="mt-1 text-xl font-bold leading-snug text-slate-900">{{ activeRegisterClass?.title }}</h3>
            </div>
            <button type="button" class="shrink-0 rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-4 focus:ring-slate-900/10" aria-label="Close registration modal" @click="closeRegisterModal">
              <X class="h-5 w-5" />
            </button>
          </div>

          <div class="mt-5 rounded-xl border border-[#E5E7EB] bg-slate-50 p-4">
            <div class="grid gap-3 text-sm text-slate-600">
              <div class="flex min-w-0 items-center gap-2">
                <CalendarDays class="h-4 w-4 shrink-0 text-slate-400" />
                <span class="truncate">{{ activeRegisterClass ? scheduleTimeLabel(activeRegisterClass) : "Schedule to be announced" }}</span>
              </div>
              <div class="flex min-w-0 items-center gap-2">
                <Building2 class="h-4 w-4 shrink-0 text-slate-400" />
                <span class="truncate">{{ activeRegisterClass ? locationLabel(activeRegisterClass) : "Location to be announced" }}</span>
              </div>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-3 border-t border-[#E5E7EB] pt-3 text-sm">
              <div class="flex min-w-0 items-center gap-2 text-slate-600">
                <DollarSign class="h-4 w-4 shrink-0 text-slate-400" />
                <span class="font-semibold text-slate-900">{{ activeRegisterClass ? priceLabel(activeRegisterClass) : "$0.00" }}</span>
              </div>
              <div class="flex min-w-0 items-center gap-2 text-slate-600">
                <CalendarDays class="h-4 w-4 shrink-0 text-slate-400" />
                <span class="truncate">{{ activeRegisterClass ? startDateLabel(activeRegisterClass) : "Starts to be announced" }}</span>
              </div>
            </div>
          </div>

          <form @submit.prevent="submitRegister" class="mt-5 grid gap-4">
            <label class="grid gap-2 text-sm font-semibold text-slate-900" for="registration-name">
              <span>Full name <span class="text-[#1A66FF]">*</span></span>
              <input id="registration-name" v-model="registerForm.name" type="text" autocomplete="name" required class="w-full rounded-xl border border-[#E5E7EB] bg-white px-4 py-3 text-sm font-medium text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1A66FF] focus:ring-4 focus:ring-[#1A66FF]/10" placeholder="Your full name" />
              <span v-if="registerForm.errors.name" class="text-sm font-semibold text-red-600">{{ registerForm.errors.name }}</span>
            </label>

            <label class="grid gap-2 text-sm font-semibold text-slate-900" for="registration-gender">
              <span>Gender <span class="text-[#1A66FF]">*</span></span>
              <select id="registration-gender" v-model="registerForm.gender" required class="w-full rounded-xl border border-[#E5E7EB] bg-white px-4 py-3 text-sm font-medium text-slate-900 outline-none transition focus:border-[#1A66FF] focus:ring-4 focus:ring-[#1A66FF]/10">
                <option value="" disabled>Select gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
              </select>
              <span v-if="registerForm.errors.gender" class="text-sm font-semibold text-red-600">{{ registerForm.errors.gender }}</span>
            </label>

            <label class="grid gap-2 text-sm font-semibold text-slate-900" for="registration-phone">
              <span>Phone number <span class="text-[#1A66FF]">*</span></span>
              <input id="registration-phone" v-model="registerForm.phone" type="tel" autocomplete="tel" required class="w-full rounded-xl border border-[#E5E7EB] bg-white px-4 py-3 text-sm font-medium text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1A66FF] focus:ring-4 focus:ring-[#1A66FF]/10" placeholder="012 345 678" />
              <span v-if="registerForm.errors.phone" class="text-sm font-semibold text-red-600">{{ registerForm.errors.phone }}</span>
            </label>

            <p class="text-xs leading-5 text-slate-500">You will receive confirmation after the admin reviews your registration.</p>

            <button type="submit" class="rounded-xl bg-[#1A66FF] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#1555D9] focus:outline-none focus:ring-4 focus:ring-[#1A66FF]/20 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500" :disabled="registerForm.processing">
              {{ registerForm.processing ? "Registering..." : "Register" }}
            </button>
          </form>
        </div>
      </div>
    </transition>

    <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showPendingModal" class="fixed inset-0 z-[200] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4" role="dialog" aria-modal="true" aria-live="assertive">
        <div class="w-full max-w-md rounded-[2rem] bg-white p-8 text-center shadow-2xl">
          <div class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-emerald-50">
            <CheckCircle2 class="h-9 w-9 text-emerald-500" />
          </div>

          <h3 class="mt-5 text-2xl font-black text-slate-900">Registration received!</h3>
          <p class="mt-3 text-base font-medium text-slate-600">Please complete your payment with the admin at the counter. This window will unlock automatically once payment is confirmed.</p>

          <div class="mt-6 rounded-2xl bg-slate-50 px-5 py-4 text-sm font-semibold text-slate-600">
            <p class="flex items-center justify-between gap-4">
              <span class="text-slate-400">Name</span>
              <span class="truncate text-slate-800">{{ pendingName || "—" }}</span>
            </p>
            <p class="mt-2 flex items-center justify-between gap-4">
              <span class="text-slate-400">Class</span>
              <span class="truncate text-slate-800">{{ pendingClassTitle || "—" }}</span>
            </p>
            <p class="mt-2 flex items-center justify-between gap-4">
              <span class="text-slate-400">Phone number</span>
              <span class="text-slate-800">{{ pendingPhone || "—" }}</span>
            </p>
          </div>

          <div class="mt-6 flex items-center justify-center gap-2 text-sm font-bold text-[#1A66FF]">
            <Loader2 class="h-4 w-4 animate-spin" />
            <span>Waiting for admin payment confirmation...</span>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>
