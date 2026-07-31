<template>
  <div class="min-h-screen bg-[#F4F7FA] text-slate-900 font-sans selection:bg-[#1A66FF]/20 selection:text-[#1A66FF] flex flex-col">
    <Head title="Student Registration" />

    <header class="fixed left-0 right-0 top-0 z-40 border-b border-slate-200/50 bg-white/80 shadow-sm backdrop-blur-xl">
      <div class="mx-auto flex min-h-[5.5rem] max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <Link href="/" class="flex min-w-0 items-center gap-3 group">
          <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-[#1A66FF] shadow-[0_5px_15px_rgba(26,102,255,0.3)] transition-transform group-hover:scale-105">
            <img v-if="settings.logo_url" :src="settings.logo_url" :alt="schoolName" class="h-full w-full object-contain p-1" />
            <span v-else class="text-xs font-black text-white">ETEC</span>
          </span>
          <span class="max-w-[12rem] truncate text-xl font-black text-slate-900 sm:max-w-xs transition-colors group-hover:text-[#1A66FF]">{{ schoolName }}</span>
        </Link>
        <div class="flex items-center gap-4">
          <Link href="/" class="inline-flex items-center gap-2 text-sm font-bold text-slate-500 hover:text-[#1A66FF] transition-colors">
            Back to Home
          </Link>
        </div>
      </div>
    </header>

    <main class="flex-1 flex items-center justify-center p-4 py-32 relative overflow-hidden">
      <!-- Background Decorative Elements -->
      <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80" aria-hidden="true">
        <div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#1A66FF] to-[#FFB800] opacity-20 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]"></div>
      </div>

      <div class="bg-white p-8 sm:p-12 rounded-[2.5rem] shadow-[0_20px_60px_-15px_rgba(0,0,0,0.05)] w-full max-w-2xl ring-1 ring-slate-100 relative z-10">
        <div class="mb-10 text-center">
            <span class="inline-flex items-center gap-2 rounded-full bg-[#E8F0FF] px-4 py-1.5 text-sm font-bold text-[#1A66FF] mb-4">
              <span class="w-2 h-2 rounded-full bg-[#1A66FF]"></span>
              Join Us
            </span>
            <h1 class="text-3xl font-black text-slate-900 sm:text-4xl">Student Registration</h1>
            <p class="mt-4 text-base text-slate-500">Fast and easy registration for your next course.</p>
        </div>

        <transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
          <div v-if="success" class="mb-8 rounded-2xl border border-emerald-200 bg-emerald-50 px-6 py-4 text-base font-bold text-emerald-800 flex items-center gap-3">
             <CheckCircle2 class="h-6 w-6 text-emerald-500 shrink-0" />
             {{ success }}
          </div>
        </transition>

        <form @submit.prevent="submit" class="grid gap-6 sm:grid-cols-2">
          <div class="sm:col-span-2">
            <label class="grid gap-2 text-sm font-bold text-slate-900">
              Full Name
              <input v-model="form.full_name" type="text" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-base font-medium outline-none transition focus:border-[#1A66FF] focus:bg-white focus:ring-4 focus:ring-[#1A66FF]/10 placeholder:font-normal" placeholder="Your full name" required :class="{'border-red-300 focus:border-red-500 focus:ring-red-100': form.errors.full_name}">
              <span v-if="form.errors.full_name" class="text-sm font-semibold text-red-600">{{ form.errors.full_name }}</span>
            </label>
          </div>

          <div>
            <label class="grid gap-2 text-sm font-bold text-slate-900">
              Gender
              <select v-model="form.gender" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-base font-medium outline-none transition focus:border-[#1A66FF] focus:bg-white focus:ring-4 focus:ring-[#1A66FF]/10" :class="{'border-red-300 focus:border-red-500 focus:ring-red-100': form.errors.gender}">
                <option value="male">Male</option>
                <option value="female">Female</option>
              </select>
              <span v-if="form.errors.gender" class="text-sm font-semibold text-red-600">{{ form.errors.gender }}</span>
            </label>
          </div>
          
          <div>
            <label class="grid gap-2 text-sm font-bold text-slate-900">
              Date of Birth
              <input v-model="form.date_of_birth" type="date" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-base font-medium outline-none transition focus:border-[#1A66FF] focus:bg-white focus:ring-4 focus:ring-[#1A66FF]/10" :class="{'border-red-300 focus:border-red-500 focus:ring-red-100': form.errors.date_of_birth}">
              <span v-if="form.errors.date_of_birth" class="text-sm font-semibold text-red-600">{{ form.errors.date_of_birth }}</span>
            </label>
          </div>

          <div class="sm:col-span-2">
            <label class="grid gap-2 text-sm font-bold text-slate-900">
              Phone Number
              <input v-model="form.phone" type="text" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-base font-medium outline-none transition focus:border-[#1A66FF] focus:bg-white focus:ring-4 focus:ring-[#1A66FF]/10 placeholder:font-normal" placeholder="012 345 678" required :class="{'border-red-300 focus:border-red-500 focus:ring-red-100': form.errors.phone}">
              <span v-if="form.errors.phone" class="text-sm font-semibold text-red-600">{{ form.errors.phone }}</span>
            </label>
          </div>

          <div class="sm:col-span-2">
            <label class="grid gap-2 text-sm font-bold text-slate-900">
              Address
              <textarea v-model="form.address" rows="3" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-base font-medium outline-none transition focus:border-[#1A66FF] focus:bg-white focus:ring-4 focus:ring-[#1A66FF]/10 placeholder:font-normal" placeholder="Your current address" :class="{'border-red-300 focus:border-red-500 focus:ring-red-100': form.errors.address}"></textarea>
              <span v-if="form.errors.address" class="text-sm font-semibold text-red-600">{{ form.errors.address }}</span>
            </label>
          </div>

          <div class="sm:col-span-2">
            <label class="grid gap-2 text-sm font-bold text-slate-900">
              Select Class & Time
              <select v-model="form.class_id" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-base font-medium outline-none transition focus:border-[#1A66FF] focus:bg-white focus:ring-4 focus:ring-[#1A66FF]/10" required :class="{'border-red-300 focus:border-red-500 focus:ring-red-100': form.errors.class_id}">
                <option value="" disabled>Choose a class...</option>
                <option v-for="cls in classes" :key="cls.id" :value="cls.id">
                  {{ cls.class_name }} - {{ cls.time?.time_name }} ({{ cls.registered_count }}/{{ cls.capacity }})
                </option>
              </select>
              <span v-if="form.errors.class_id" class="text-sm font-semibold text-red-600">{{ form.errors.class_id }}</span>
            </label>
          </div>

          <button type="submit" class="mt-4 sm:col-span-2 rounded-full bg-[#1A66FF] px-8 py-4 text-base font-black text-white shadow-[0_10px_20px_rgba(26,102,255,0.2)] transition-all hover:bg-[#1555D9] hover:-translate-y-1 disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:translate-y-0" :disabled="form.processing">
             {{ form.processing ? "Registering..." : "Complete Registration" }}
          </button>
        </form>
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useForm, Head, Link, usePage } from '@inertiajs/vue3';
import { CheckCircle2 } from '@lucide/vue';

const props = defineProps({
  classes: Array,
  success: String,
});

const inertiaPage = usePage();
const website = computed(() => inertiaPage.props.website ?? {});
const settings = computed(() => website.value.settings ?? {});
const schoolName = computed(() => settings.value.school_name || "ETEC Center");

const form = useForm({
  full_name: '',
  gender: 'male',
  date_of_birth: '',
  phone: '',
  address: '',
  class_id: '',
});

const submit = () => {
  form.post('/register', {
    onSuccess: () => form.reset(),
    preserveScroll: true,
  });
};
</script>
