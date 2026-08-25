<script setup>
import { computed, onBeforeUnmount, onMounted } from "vue";
import { Head, useForm, usePage } from "@inertiajs/vue3";
import { ArrowRight, Phone } from "@lucide/vue";
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

  <div class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(37,99,235,0.16),_transparent_34%),linear-gradient(180deg,#f8fbff_0%,#eef5ff_100%)] px-3 py-4 text-slate-950 sm:px-6 sm:py-8">
    <div class="mx-auto flex min-h-[calc(100vh-2rem)] w-full max-w-md items-center justify-center">
      <section class="w-full rounded-[1.75rem] border border-white bg-white/95 p-5 shadow-2xl shadow-blue-950/10 sm:p-7">
        <div class="text-center">
          <p class="text-[11px] font-black uppercase tracking-[0.28em] text-blue-600">Student Registration</p>
          <h1 class="mt-3 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">
            Register Student
          </h1>
        </div>

        <p v-if="flashSuccess" class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
          {{ flashSuccess }}
        </p>
        <p v-else-if="flashError" class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
          {{ flashError }}
        </p>

        <p
          v-if="isLocked"
          class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700"
        >
          You already requested this class from this device. Please wait for instructor approval.
        </p>

        <form class="mt-5 space-y-4" @submit.prevent="submit">
          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Full Name</span>
            <input
              v-model="form.name"
              type="text"
              autocomplete="name"
              placeholder="Enter your full name"
              class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-base text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
            />
            <p v-if="form.errors.name" class="mt-1 text-xs font-semibold text-rose-600">{{ form.errors.name }}</p>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Gender</span>
            <select
              v-model="form.gender"
              class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-base text-slate-950 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
            >
              <option value="">Select gender</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
            </select>
            <p v-if="form.errors.gender" class="mt-1 text-xs font-semibold text-rose-600">{{ form.errors.gender }}</p>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Phone</span>
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
                class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 pl-11 pr-4 text-base text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
              />
            </div>
            <p v-if="form.errors.phone" class="mt-1 text-xs font-semibold text-rose-600">{{ form.errors.phone }}</p>
          </label>

          <button
            type="submit"
            :disabled="form.processing || isLocked"
            class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 text-sm font-bold text-white shadow-lg shadow-blue-600/25 transition hover:bg-blue-500 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-70"
          >
            <ArrowRight class="h-4 w-4" />
            {{ form.processing ? 'Sending...' : isLocked ? 'Already Requested' : 'Submit Registration' }}
          </button>
        </form>
      </section>
    </div>
  </div>
</template>
