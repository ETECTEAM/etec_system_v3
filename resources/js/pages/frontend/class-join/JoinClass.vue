<script setup>
import { computed, onBeforeUnmount, onMounted } from "vue";
import { Head, useForm, usePage } from "@inertiajs/vue3";
import { ArrowRight, BookOpen, CheckCircle2, Clock3, GraduationCap, MapPin, Phone, UserRound } from "@lucide/vue";
import { useTheme } from "@/composables/useTheme";

const props = defineProps({
  classData: {
    type: Object,
    required: true,
  },
  isLocked: {
    type: Boolean,
    default: false,
  },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const { resolvedTheme } = useTheme();
onMounted(() => document.documentElement.classList.remove("dark"));
onBeforeUnmount(() => document.documentElement.classList.toggle("dark", resolvedTheme.value === "dark"));

const form = useForm({
  name: "",
  gender: "",
  phone: "",
});

const joinUrl = computed(() => `/join-class/${props.classData.id}`);

function submit() {
  if (props.isLocked) {
    return;
  }

  form.post(joinUrl.value, {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  });
}

function normalizePhoneInput(event) {
  const value = event.target.value ?? "";
  form.phone = String(value).replace(/\D+/g, "").slice(0, 12);
}
</script>

<template>
  <Head :title="`Join ${classData.title}`" />

  <div class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.16),_transparent_38%),linear-gradient(180deg,#07111f_0%,#0b1220_100%)] px-4 py-6 text-slate-100 sm:px-6 sm:py-10">
    <div class="mx-auto flex min-h-[calc(100vh-3rem)] w-full max-w-5xl items-center">
      <div class="grid w-full gap-6 lg:grid-cols-[1.05fr_0.95fr]">
        <section class="rounded-[2rem] border border-white/10 bg-white/6 p-6 shadow-2xl shadow-slate-950/30 backdrop-blur-xl sm:p-8">
          <p class="text-[11px] font-black uppercase tracking-[0.32em] text-blue-300/90">Class Join</p>
          <h1 class="mt-3 text-3xl font-black tracking-tight text-white sm:text-4xl">
            Request access to {{ classData.title }}
          </h1>
          <p class="mt-3 max-w-xl text-sm leading-6 text-slate-300 sm:text-base">
            Scan the QR code, submit your details, and the instructor will approve your request before you join the class.
          </p>

          <div class="mt-6 grid gap-3 sm:grid-cols-2">
            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
              <div class="flex items-center gap-3 text-blue-300">
                <BookOpen class="h-4 w-4" />
                <span class="text-xs font-bold uppercase tracking-[0.18em]">Course</span>
              </div>
              <p class="mt-2 text-sm font-semibold text-white">{{ classData.course }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
              <div class="flex items-center gap-3 text-blue-300">
                <Clock3 class="h-4 w-4" />
                <span class="text-xs font-bold uppercase tracking-[0.18em]">Schedule</span>
              </div>
              <p class="mt-2 text-sm font-semibold text-white">{{ classData.term }} · {{ classData.time }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
              <div class="flex items-center gap-3 text-blue-300">
                <MapPin class="h-4 w-4" />
                <span class="text-xs font-bold uppercase tracking-[0.18em]">Location</span>
              </div>
              <p class="mt-2 text-sm font-semibold text-white">{{ classData.building }} · {{ classData.room }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
              <div class="flex items-center gap-3 text-blue-300">
                <GraduationCap class="h-4 w-4" />
                <span class="text-xs font-bold uppercase tracking-[0.18em]">Capacity</span>
              </div>
              <p class="mt-2 text-sm font-semibold text-white">{{ classData.students }} / {{ classData.capacity }}</p>
            </div>
          </div>

          <div class="mt-6 rounded-2xl border border-amber-400/20 bg-amber-400/10 p-4 text-sm text-amber-100">
            <div class="flex items-center gap-2 font-semibold">
              <CheckCircle2 class="h-4 w-4" />
              Approval required
            </div>
            <p class="mt-2 text-amber-50/90">
              Your request will be saved as pending. You will only be added to the class after an instructor approves it.
            </p>
          </div>

          <div
            v-if="isLocked"
            class="mt-4 rounded-2xl border border-rose-400/20 bg-rose-400/10 p-4 text-sm text-rose-100"
          >
            You already requested this class from this device. Please wait for instructor approval or choose another class.
          </div>
        </section>

        <section class="rounded-[2rem] border border-white/10 bg-slate-950/70 p-6 shadow-2xl shadow-slate-950/30 backdrop-blur-xl sm:p-8">
          <p class="text-[11px] font-black uppercase tracking-[0.32em] text-blue-300/90">Student Details</p>
          <h2 class="mt-3 text-2xl font-black text-white">Request to join</h2>

          <p v-if="flashSuccess" class="mt-4 rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm font-semibold text-emerald-100">
            {{ flashSuccess }}
          </p>
          <p v-else-if="flashError" class="mt-4 rounded-xl border border-rose-400/20 bg-rose-400/10 px-4 py-3 text-sm font-semibold text-rose-100">
            {{ flashError }}
          </p>

          <form class="mt-5 space-y-4" @submit.prevent="submit">
            <label class="block">
              <span class="mb-2 block text-sm font-semibold text-slate-200">Full Name</span>
              <input
                v-model="form.name"
                type="text"
                placeholder="Enter your full name"
                class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white outline-none transition placeholder:text-slate-500 focus:border-blue-400 focus:ring-2 focus:ring-blue-400/20"
              />
              <p v-if="form.errors.name" class="mt-1 text-xs font-semibold text-rose-300">{{ form.errors.name }}</p>
            </label>

            <label class="block">
              <span class="mb-2 block text-sm font-semibold text-slate-200">Gender</span>
              <select
                v-model="form.gender"
                class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-400/20"
              >
                <option value="" class="text-slate-900">Select gender</option>
                <option value="male" class="text-slate-900">Male</option>
                <option value="female" class="text-slate-900">Female</option>
              </select>
              <p v-if="form.errors.gender" class="mt-1 text-xs font-semibold text-rose-300">{{ form.errors.gender }}</p>
            </label>

            <label class="block">
              <span class="mb-2 block text-sm font-semibold text-slate-200">Phone</span>
              <div class="relative">
                <Phone class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input
                  v-model="form.phone"
                  type="text"
                  inputmode="numeric"
                  autocomplete="tel"
                  pattern="[0-9]*"
                  maxlength="12"
                  @input="normalizePhoneInput"
                  placeholder="Enter phone number"
                  class="w-full rounded-2xl border border-white/10 bg-white/5 py-3 pl-11 pr-4 text-sm text-white outline-none transition placeholder:text-slate-500 focus:border-blue-400 focus:ring-2 focus:ring-blue-400/20"
                />
              </div>
              <p v-if="form.errors.phone" class="mt-1 text-xs font-semibold text-rose-300">{{ form.errors.phone }}</p>
            </label>

            <button
              type="submit"
              :disabled="form.processing || isLocked"
              class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 text-sm font-bold text-white shadow-lg shadow-blue-600/30 transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-70"
            >
              <ArrowRight class="h-4 w-4" />
              {{ form.processing ? 'Sending...' : isLocked ? 'Already Requested' : 'Send Join Request' }}
            </button>
          </form>
        </section>
      </div>
    </div>
  </div>
</template>
