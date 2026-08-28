<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { Head } from "@inertiajs/vue3";
import axios from "axios";
import { AlertTriangle, CheckCircle2, Clock3, MapPin, ScanLine, ShieldAlert, Smartphone } from "@lucide/vue";

const props = defineProps({
  state: {
    type: String,
    default: "invalid",
  },
  classData: {
    type: Object,
    default: null,
  },
  session: {
    type: Object,
    default: null,
  },
});

const form = ref({
  student_id: "",
});

const submitting = ref(false);
const submitted = ref(false);
const responseData = ref(null);
const errorMessage = ref("");
const currentPosition = ref(null);

const submitUrl = computed(() => window.location.pathname + window.location.search);

function deviceIdentifier() {
  const key = "attendance_device_identifier";
  const existing = window.localStorage.getItem(key);

  if (existing) {
    return existing;
  }

  const value = window.crypto?.randomUUID?.() ?? `device-${Date.now()}-${Math.random().toString(16).slice(2)}`;
  window.localStorage.setItem(key, value);

  return value;
}

function geoPromise() {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      reject(new Error("Your browser does not support GPS location."));
      return;
    }

    navigator.geolocation.getCurrentPosition(resolve, reject, {
      enableHighAccuracy: true,
      timeout: 15000,
      maximumAge: 0,
    });
  });
}

async function submit() {
  submitting.value = true;
  errorMessage.value = "";
  responseData.value = null;

  try {
    const position = await geoPromise();
    currentPosition.value = position.coords;

    const response = await axios.post(submitUrl.value, {
      student_id: Number(form.value.student_id),
      latitude: position.coords.latitude,
      longitude: position.coords.longitude,
      accuracy: position.coords.accuracy,
      user_agent: navigator.userAgent,
      device_identifier: deviceIdentifier(),
    });

    submitted.value = true;
    responseData.value = response.data?.attendance ?? response.data ?? null;
  } catch (error) {
    const firstError = Object.values(error.response?.data?.errors ?? {})
      .flat()
      .find(Boolean);

    errorMessage.value = firstError ?? error.response?.data?.message ?? error.message ?? "Failed to submit attendance.";
  } finally {
    submitting.value = false;
  }
}

const pageTitle = computed(() => {
  if (props.classData?.title) {
    return `Attendance - ${props.classData.title}`;
  }

  return "Attendance";
});

onMounted(() => {
  document.documentElement.classList.remove("dark");
});

onBeforeUnmount(() => {
  document.documentElement.classList.remove("dark");
});
</script>

<template>
  <Head :title="pageTitle" />

  <div class="min-h-screen bg-[linear-gradient(180deg,#f7fbff_0%,#eef5ff_100%)] px-4 py-5 text-slate-950 sm:px-6 sm:py-8">
    <div class="mx-auto flex min-h-[calc(100vh-2.5rem)] w-full max-w-md items-center justify-center">
      <section class="w-full rounded-[1.75rem] border border-white bg-white/95 p-5 shadow-2xl shadow-blue-950/10 sm:p-7">
        <div class="text-center">
          <p class="text-[11px] font-black uppercase tracking-[0.28em] text-blue-600">Attendance</p>
          <h1 class="mt-3 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">{{ classData?.title ?? "Unknown Class" }}</h1>
          <p class="mt-2 text-sm font-semibold text-slate-500">{{ session?.attendance_date ? `Date: ${session.attendance_date}` : "Date unavailable" }}</p>
        </div>

        <div v-if="state === 'invalid'" class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm font-semibold text-rose-700">
          <div class="flex items-center gap-2">
            <AlertTriangle class="h-4 w-4" />
            Invalid attendance QR code.
          </div>
        </div>

        <div v-else-if="state === 'expired'" class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm font-semibold text-amber-800">
          <div class="flex items-center gap-2">
            <Clock3 class="h-4 w-4" />
            This attendance QR code has expired. Please scan the current QR code.
          </div>
        </div>

        <div v-else-if="state === 'stopped'" class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm font-semibold text-amber-800">
          <div class="flex items-center gap-2">
            <ShieldAlert class="h-4 w-4" />
            Attendance has been stopped by the teacher.
          </div>
        </div>

        <div v-else-if="submitted" class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm font-semibold text-emerald-700">
          <div class="flex items-center gap-2">
            <CheckCircle2 class="h-4 w-4" />
            Attendance recorded successfully.
          </div>
          <div class="mt-3 space-y-1 text-sm font-semibold text-slate-700">
            <p>Student: #{{ responseData?.student_id ?? form.student_id }}</p>
            <p>Class: {{ classData?.title ?? "-" }}</p>
            <p>Date: {{ responseData?.date ?? session?.attendance_date ?? "-" }}</p>
            <p>Time: {{ responseData?.time ?? "-" }}</p>
            <p>Status: {{ responseData?.verification_status === 'suspicious' ? 'SUSPICIOUS' : 'PRESENT' }}</p>
          </div>
          <p v-if="responseData?.verification_status === 'suspicious'" class="mt-3 text-xs font-semibold text-amber-700">
            {{ responseData?.verification_reason ?? "This submission was marked suspicious and should be reviewed." }}
          </p>
        </div>

        <form v-else class="mt-6 space-y-4" @submit.prevent="submit">
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex items-center gap-2 text-slate-600">
              <Smartphone class="h-4 w-4" />
              <p class="text-xs font-black uppercase tracking-[0.16em]">Student Flow</p>
            </div>
            <p class="mt-2 text-sm font-semibold text-slate-600">Scan the code, enter your Student ID, allow GPS, then submit once from your device.</p>
          </div>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Student ID</span>
            <input
              v-model="form.student_id"
              type="number"
              inputmode="numeric"
              placeholder="Enter Student ID"
              class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-base text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
            />
          </label>

          <button
            type="submit"
            :disabled="submitting || state !== 'active'"
            class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 text-sm font-bold text-white shadow-lg shadow-blue-600/25 transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-70"
          >
            <ScanLine class="h-4 w-4" />
            {{ submitting ? "Submitting..." : "Submit Attendance" }}
          </button>

         

          <p v-if="errorMessage" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
            {{ errorMessage }}
          </p>
        </form>

        <p class="mt-5 text-center text-xs font-semibold text-slate-400">ETEC Center</p>
      </section>
    </div>
  </div>
</template>
