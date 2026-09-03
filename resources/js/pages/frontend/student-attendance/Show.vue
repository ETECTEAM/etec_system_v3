<script setup>
import { computed, onBeforeUnmount, ref } from "vue";
import { Head } from "@inertiajs/vue3";
import { Building2, CalendarCheck, CalendarX, Clock, FileCheck2, GraduationCap, Inbox, Monitor, Plus, Smartphone, X } from "@lucide/vue";
import { useI18n } from "@/i18n";

// Public, read-only page a family member opens by scanning the receipt QR code.
const props = defineProps({
  enrollment: {
    type: Object,
    default: () => ({}),
  },
  attendances: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    default: () => ({ present: 0, absent: 0, late: 0, permission: 0, total: 0 }),
  },
});

const { t, locale, setLocale, supportedLocales } = useI18n();

// Page-local language: Khmer is the default for this family-facing page, an
// explicit KH/EN choice is remembered per browser, and the app's own locale is
// restored on unmount so this page never leaks into the rest of the site.
const PAGE_LOCALE_KEY = "student-attendance.locale";
const appLocale = locale.value;
const storedLocale = typeof window !== "undefined" ? window.localStorage.getItem(PAGE_LOCALE_KEY) : null;
const pageLocale = supportedLocales.some((item) => item.code === storedLocale) ? storedLocale : "km";
setLocale(pageLocale);
onBeforeUnmount(() => setLocale(appLocale));

// Remember which attendance page this visitor looked at so the installed PWA
// app can reopen directly to it the next time it's launched from the home screen.
// The URL is kept both in localStorage (for any page-level fallback) and in
// IndexedDB (readable by the service worker, so it can intercept the PWA launch
// and jump straight to attendance without ever showing the register page).
const ATTENDANCE_URL_KEY = "etec.attendance.url";
const ATTENDANCE_DB = "etec-attendance";
const ATTENDANCE_DB_STORE = "attendance";
const attendanceUrl = typeof window !== "undefined" ? window.location.pathname + window.location.search : "";

if (typeof window !== "undefined") {
  window.localStorage.setItem(ATTENDANCE_URL_KEY, attendanceUrl);
}

if (typeof indexedDB !== "undefined") {
  const request = indexedDB.open(ATTENDANCE_DB, 1);
  request.onupgradeneeded = () => {
    if (!request.result.objectStoreNames.contains(ATTENDANCE_DB_STORE)) {
      request.result.createObjectStore(ATTENDANCE_DB_STORE);
    }
  };
  request.onsuccess = () => {
    const tx = request.result.transaction(ATTENDANCE_DB_STORE, "readwrite");
    tx.objectStore(ATTENDANCE_DB_STORE).put(attendanceUrl, "url");
    tx.oncomplete = () => request.result.close();
  };
}

// KH first (the default), then EN — a visitor-facing order, not the registry order.
const languageOptions = [{ code: "km" }, { code: "en" }].filter((item) =>
  supportedLocales.some((supported) => supported.code === item.code)
);

function chooseLanguage(code) {
  setLocale(code);
  window.localStorage.setItem(PAGE_LOCALE_KEY, code);
}

function languageLabel(code) {
  return code === "km" ? "ខ្មែរ" : "EN";
}

// ---- Add to Home Screen / Desktop ----------------------------------------
// A persistent "+ Add to screen" button pinned to the bottom-right.
// Android/Chrome fire beforeinstallprompt once the app is installable and
// tapping the button launches the native install prompt; iOS has no prompt, so
// it opens an explainer sheet instead. Desktop browsers show bookmark/pin
// instructions. The button hides once the app is installed or the visitor
// dismisses it via the sheet.
const A2HS_KEY = "etec.a2hs";
const deferredPrompt = ref(null);
const installFABVisible = ref(false);
const installSheetOpen = ref(false);
const isIOS = typeof navigator !== "undefined" && /iphone|ipad|ipod/i.test(navigator.userAgent);
const isDesktop = typeof navigator !== "undefined" && !/iphone|ipad|ipod|android|webos|blackberry|opera mini|iemobile/i.test(navigator.userAgent);

const installHint = computed(() => {
  if (deferredPrompt.value) return t("Install the app for one-tap access to attendance.");
  if (isIOS) return t("On Safari, tap the Share button and choose 'Add to Home Screen'.");
  if (isDesktop) return t("Bookmark this page or pin the tab for quick access.");
  return t("Install the app for one-tap access to attendance.");
});

function maybeShowInstallFAB() {
  if (installFABVisible.value || localStorage.getItem(A2HS_KEY) === "dismissed") return;
  installFABVisible.value = true;
}

if (typeof window !== "undefined") {
  const isStandalone =
    window.matchMedia("(display-mode: standalone)").matches || navigator.standalone === true;

  if (!isStandalone) {
    maybeShowInstallFAB();
  }

  window.addEventListener("beforeinstallprompt", (event) => {
    event.preventDefault();
    deferredPrompt.value = event;
    maybeShowInstallFAB();
  });

  window.addEventListener("appinstalled", () => {
    installFABVisible.value = false;
    installSheetOpen.value = false;
    deferredPrompt.value = null;
  });
}

function handleInstallTap() {
  if (deferredPrompt.value) {
    installApp();
  } else {
    installSheetOpen.value = true;
  }
}

async function installApp() {
  const prompt = deferredPrompt.value;
  if (!prompt) return;
  prompt.prompt();
  await prompt.userChoice;
  installFABVisible.value = false;
  deferredPrompt.value = null;
}

function dismissInstall() {
  window.localStorage.setItem(A2HS_KEY, "dismissed");
  installFABVisible.value = false;
  installSheetOpen.value = false;
}

function closeInstallSheet() {
  installSheetOpen.value = false;
}

const student = computed(() => props.enrollment.student ?? {});
const classInfo = computed(() => props.enrollment.class ?? {});

const attendanceRate = computed(() =>
  props.stats.total > 0 ? Math.round((props.stats.present / props.stats.total) * 100) : 0
);

const summaryCards = computed(() => [
  { 
    key: "present", 
    label: t("Present"), 
    value: props.stats.present, 
    classes: "border-emerald-200 bg-emerald-50/60 text-emerald-800 dark:border-emerald-900/40 dark:bg-emerald-950/20 dark:text-emerald-300" 
  },
  { 
    key: "late", 
    label: t("Late"), 
    value: props.stats.late, 
    classes: "border-amber-200 bg-amber-50/60 text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-300" 
  },
  { 
    key: "absent", 
    label: t("Absent"), 
    value: props.stats.absent, 
    classes: "border-rose-200 bg-rose-50/60 text-rose-800 dark:border-rose-900/40 dark:bg-rose-950/20 dark:text-rose-300" 
  },
  { 
    key: "permission", 
    label: t("Permission"), 
    value: props.stats.permission, 
    classes: "border-sky-200 bg-sky-50/60 text-sky-800 dark:border-sky-900/40 dark:bg-sky-950/20 dark:text-sky-300" 
  },
]);

const statusStyles = {
  present: "border-l-emerald-500 bg-emerald-50/40 text-emerald-900 dark:bg-emerald-950/20 dark:text-emerald-200",
  late: "border-l-amber-500 bg-amber-50/40 text-amber-900 dark:bg-amber-950/20 dark:text-amber-200",
  absent: "border-l-rose-500 bg-rose-50/40 text-rose-900 dark:bg-rose-950/20 dark:text-rose-200",
  permission: "border-l-sky-500 bg-sky-50/40 text-sky-900 dark:bg-sky-950/20 dark:text-sky-200",
};

function rowClasses(status) {
  return statusStyles[status] ?? "border-l-slate-400 bg-slate-50 text-slate-700 dark:bg-gray-800/50 dark:text-gray-300";
}

function statusLabel(status) {
  return status ? t(status.charAt(0).toUpperCase() + status.slice(1)) : "-";
}

// Parse as local midnight so a UTC-parsed date can't roll back a day in Asia/Phnom_Penh.
function formatDate(date) {
  if (!date) return "-";
  return new Date(`${date}T00:00:00`).toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
}
</script>

<template>
  <Head :title="$t('Attendance Summary')" />

  <div class="min-h-screen bg-slate-100 px-4 py-6 antialiased sm:py-10 dark:bg-gray-950">
    <div class="fixed right-3 top-3 z-50 flex items-center rounded-full border border-slate-200 bg-white/90 p-0.5 shadow-sm backdrop-blur dark:border-gray-700 dark:bg-gray-900/90">
      <button
        v-for="item in languageOptions"
        :key="item.code"
        type="button"
        :class="['h-7 rounded-full px-3 text-[11px] font-black uppercase transition', locale === item.code ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200']"
        @click="chooseLanguage(item.code)"
      >
        {{ languageLabel(item.code) }}
      </button>
    </div>
    <div class="mx-auto w-full max-w-md space-y-4 sm:max-w-2xl sm:space-y-5 lg:max-w-4xl">
      
      <!-- Institution Branding Header -->
      <div class="flex items-center justify-center gap-1.5 text-xs font-bold uppercase tracking-widest text-slate-400 dark:text-gray-500">
        <Building2 class="h-4 w-4" />
        <span>ETEC Center</span>
      </div>

      <!-- Symmetric two-column breakout on tablet + laptop; stacks on phone -->
      <div class="grid gap-4 md:grid-cols-2 lg:gap-5">
      <!-- Student Profile & Enrollment Details -->
      <section class="flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center gap-3.5">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-sm shadow-blue-500/30">
            <GraduationCap class="h-6 w-6" />
          </div>
          <div class="min-w-0 flex-1">
            <h1 class="truncate text-lg font-bold text-slate-900 dark:text-gray-100">{{ student.name || 'Student Name' }}</h1>
            <div class="mt-0.5 flex items-center gap-1.5 text-xs text-slate-500 dark:text-gray-400">
              <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono font-medium text-slate-600 dark:bg-gray-800 dark:text-gray-300">
                #{{ student.id }}
              </span>
              <span>·</span>
              <span class="truncate font-medium">{{ enrollment.reference }}</span>
            </div>
          </div>
        </div>

        <!-- Responsive Stacked Data Rows for Long Titles -->
        <dl class="mt-5 space-y-3 border-t border-slate-100 pt-4 text-xs sm:text-sm dark:border-gray-800">
          <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
            <dt class="shrink-0 text-slate-500 dark:text-gray-400">{{ $t('Course') }}</dt>
            <dd class="line-clamp-2 font-semibold text-slate-900 sm:text-right dark:text-gray-100" :title="classInfo.course">
              {{ classInfo.course || '-' }}
            </dd>
          </div>

          <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
            <dt class="shrink-0 text-slate-500 dark:text-gray-400">{{ $t('Class') }}</dt>
            <dd class="line-clamp-2 font-semibold text-slate-900 sm:text-right dark:text-gray-100" :title="classInfo.title">
              {{ classInfo.title || '-' }}
            </dd>
          </div>

          <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
            <dt class="shrink-0 text-slate-500 dark:text-gray-400">{{ $t('Schedule') }}</dt>
            <dd class="font-semibold text-slate-900 sm:text-right dark:text-gray-100">
              {{ classInfo.term ? `${classInfo.term} · ` : '' }}{{ classInfo.time || '-' }}
            </dd>
          </div>
        </dl>
      </section>

      <!-- Attendance Rate Indicator -->
      <section class="flex h-full flex-col rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-1 flex-col">
          <div class="flex items-end justify-between">
          <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-gray-500">{{ $t('Attendance Rate') }}</p>
            <p 
              class="mt-0.5 text-3xl font-extrabold tracking-tight"
              :class="stats.total > 0 ? 'text-slate-900 dark:text-gray-100' : 'text-slate-400 dark:text-gray-500'"
            >
              {{ attendanceRate }}%
            </p>
          </div>
          <p class="text-xs font-medium text-slate-500 dark:text-gray-400">
            <span class="font-bold text-slate-900 dark:text-gray-100">{{ stats.present }}</span> / {{ stats.total }} {{ $t('Total Sessions') }}
          </p>
        </div>
        <div class="mt-3.5 h-2.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-gray-800">
          <div 
            class="h-full rounded-full transition-all duration-500 ease-out"
            :class="attendanceRate > 0 ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-gray-700'"
            :style="{ width: `${attendanceRate}%` }"
          ></div>
        </div>
        </div>
      </section>
      </div>

      <!-- Grid Metric Badges -->
      <section class="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
        <div 
          v-for="card in summaryCards" 
          :key="card.key" 
          class="flex flex-col items-center justify-center rounded-xl border p-3 text-center transition-transform active:scale-[0.98]"
          :class="card.classes"
        >
          <p class="text-2xl font-black leading-none tracking-tight">{{ card.value }}</p>
          <p class="mt-1 text-[10px] font-bold uppercase tracking-widest opacity-80">{{ card.label }}</p>
        </div>
      </section>

      <!-- Attendance Logs & History -->
      <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between">
          <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-gray-500">{{ $t('Attendance History') }}</h2>
          <span class="inline-flex items-center justify-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-600 dark:bg-gray-800 dark:text-gray-400">
            {{ stats.total }}
          </span>
        </div>

        <ul v-if="attendances.length" class="mt-4 space-y-2">
          <li 
            v-for="(row, index) in attendances" 
            :key="index" 
            class="flex items-center justify-between rounded-xl border-l-4 border-y border-r border-slate-200/60 px-3.5 py-2.5 text-xs sm:text-sm dark:border-gray-800"
            :class="rowClasses(row.status)"
          >
            <div class="flex items-center gap-2 font-medium">
              <CalendarCheck v-if="row.status === 'present'" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
              <Clock v-else-if="row.status === 'late'" class="h-4 w-4 text-amber-600 dark:text-amber-400" />
              <FileCheck2 v-else-if="row.status === 'permission'" class="h-4 w-4 text-sky-600 dark:text-sky-400" />
              <CalendarX v-else class="h-4 w-4 text-rose-600 dark:text-rose-400" />
              <span>{{ formatDate(row.date) }}</span>
            </div>

            <div class="flex items-center gap-2">
              <span 
                v-if="row.verification_status && row.verification_status !== 'verified'" 
                class="rounded-md bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800 dark:bg-amber-950/60 dark:text-amber-300"
              >
                {{ $t('Unverified') }}
              </span>
              <span class="font-bold uppercase tracking-wider text-[11px]">{{ statusLabel(row.status) }}</span>
            </div>
          </li>
        </ul>

        <!-- Empty State Container -->
        <div v-else class="my-3 flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-200/90 py-8 text-center dark:border-gray-800">
          <div class="rounded-full bg-slate-50 p-3 text-slate-400 dark:bg-gray-800/50 dark:text-gray-500">
            <Inbox class="h-7 w-7" />
          </div>
          <p class="mt-3 text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('No attendance records yet') }}</p>
          <p class="mt-1 max-w-[220px] text-xs text-slate-400 dark:text-gray-500">{{ $t('Class sessions will appear here once attendance has been taken.') }}</p>
        </div>
      </section>

      <!-- Footer Disclaimer -->
      <footer class="pt-2 text-center text-xs text-slate-400 dark:text-gray-600">
        <p>&copy; {{ new Date().getFullYear() }} ETEC Center. All rights reserved.</p>
      </footer>

    </div>

    <!-- Add to Home Screen / Desktop button + explainer sheet -->
    <Transition name="fab">
      <button
        v-if="installFABVisible"
        type="button"
        class="group fixed bottom-4 right-4 z-50 flex h-12 items-center gap-2.5 rounded-full bg-blue-600 pl-2 pr-4 text-sm font-bold text-white shadow-lg shadow-blue-600/25 transition-all duration-200 hover:-translate-y-0.5 hover:bg-blue-700 hover:shadow-xl hover:shadow-blue-600/30 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 active:scale-95 dark:shadow-blue-950/40 sm:bottom-6 sm:right-6"
        @click="handleInstallTap"
      >
        <span class="grid h-8 w-8 place-items-center rounded-full bg-white/20 text-white transition group-hover:scale-105 group-hover:bg-white/25">
          <Plus class="h-4 w-4" stroke-width="3" />
        </span>
        <span class="whitespace-nowrap">{{ isDesktop ? $t('Add To screen') : $t('Add To phone screen') }}</span>
      </button>
    </Transition>

    <div
      v-if="installSheetOpen"
      class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/40 p-4 backdrop-blur-[2px] sm:items-center"
      @click.self="closeInstallSheet"
    >
      <div class="w-full max-w-sm rounded-3xl border border-slate-200/80 bg-white p-5 shadow-2xl dark:border-gray-700 dark:bg-gray-900">
        <div class="flex items-start gap-3">
          <div class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-600/30">
            <Smartphone v-if="!isDesktop" class="h-5 w-5" />
            <Monitor v-else class="h-5 w-5" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-sm font-bold text-slate-900 dark:text-gray-100">{{ isDesktop ? $t('Add ETEC to your Desktop') : $t('Add ETEC to your Home Screen') }}</p>
            <p class="mt-0.5 text-xs font-medium text-slate-500 dark:text-gray-400">{{ installHint }}</p>
          </div>
          <button type="button" class="grid h-7 w-7 shrink-0 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-gray-800 dark:hover:text-gray-300" :aria-label="$t('Not now')" @click="closeInstallSheet">
            <X class="h-4 w-4" />
          </button>
        </div>

        <!-- Mobile instructions -->
        <div v-if="!isDesktop" class="mt-4 rounded-xl bg-slate-50 p-3.5 dark:bg-gray-800/60">
          <p class="text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-gray-500">{{ $t('How to install') }}</p>
          <ol class="mt-2.5 space-y-2">
            <li class="flex items-start gap-2.5">
              <span class="mt-px grid h-5 w-5 shrink-0 place-items-center rounded-full bg-blue-600 text-[10px] font-black text-white">1</span>
              <span class="text-xs font-medium leading-5 text-slate-600 dark:text-gray-300">{{ $t('Open this page in your browser.') }}</span>
            </li>
            <li class="flex items-start gap-2.5">
              <span class="mt-px grid h-5 w-5 shrink-0 place-items-center rounded-full bg-blue-600 text-[10px] font-black text-white">2</span>
              <span class="text-xs font-medium leading-5 text-slate-600 dark:text-gray-300">{{ $t('Tap your browser\u2019s menu (\u22ee or Share).') }}</span>
            </li>
            <li class="flex items-start gap-2.5">
              <span class="mt-px grid h-5 w-5 shrink-0 place-items-center rounded-full bg-blue-600 text-[10px] font-black text-white">3</span>
              <span class="text-xs font-medium leading-5 text-slate-600 dark:text-gray-300">{{ $t('Choose \u2018Add to Home Screen\u2019.') }}</span>
            </li>
          </ol>
        </div>

        <!-- Desktop instructions -->
        <div v-else class="mt-4 rounded-xl bg-slate-50 p-3.5 dark:bg-gray-800/60">
          <p class="text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-gray-500">{{ $t('How to add') }}</p>
          <ol class="mt-2.5 space-y-2">
            <li class="flex items-start gap-2.5">
              <span class="mt-px grid h-5 w-5 shrink-0 place-items-center rounded-full bg-blue-600 text-[10px] font-black text-white">1</span>
              <span class="text-xs font-medium leading-5 text-slate-600 dark:text-gray-300">{{ $t('Click the bookmark icon in your browser\u2019s address bar.') }}</span>
            </li>
            <li class="flex items-start gap-2.5">
              <span class="mt-px grid h-5 w-5 shrink-0 place-items-center rounded-full bg-blue-600 text-[10px] font-black text-white">2</span>
              <span class="text-xs font-medium leading-5 text-slate-600 dark:text-gray-300">{{ $t('Or right-click the tab and select \u2018Pin Tab\u2019.') }}</span>
            </li>
            <li class="flex items-start gap-2.5">
              <span class="mt-px grid h-5 w-5 shrink-0 place-items-center rounded-full bg-blue-600 text-[10px] font-black text-white">3</span>
              <span class="text-xs font-medium leading-5 text-slate-600 dark:text-gray-300">{{ $t('For Chrome/Edge, you can also install as an app from the menu.') }}</span>
            </li>
          </ol>
        </div>

        <div class="mt-4 flex items-center justify-between gap-3">
          <button type="button" class="rounded-lg px-2 py-1.5 text-xs font-semibold text-slate-400 transition hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300" @click="dismissInstall">
            {{ $t("Don't show again") }}
          </button>
          <button type="button" class="inline-flex h-9 items-center justify-center rounded-xl bg-blue-600 px-5 text-xs font-bold text-white shadow-sm transition hover:bg-blue-700" @click="closeInstallSheet">
            {{ $t('Got it') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fab-enter-active,
.fab-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.fab-enter-from,
.fab-leave-to {
  opacity: 0;
  transform: translateY(12px);
}
</style>