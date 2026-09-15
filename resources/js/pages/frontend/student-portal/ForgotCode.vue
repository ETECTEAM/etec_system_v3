<script setup>
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import { ArrowLeft, KeyRound, Mail, Send } from "@lucide/vue";

const props = defineProps({ returnTo: { type: String, default: "/student-portal/login" } });
const form = useForm({ email: "", return_to: props.returnTo });
const page = usePage();

function submit() {
  form.post("/student-portal/forgot-code");
}
</script>

<template>
  <Head title="Recover Student Portal code" />

  <main class="min-h-screen bg-[linear-gradient(180deg,#f7fbff_0%,#eef5ff_100%)] px-4 py-5 text-slate-950 sm:px-6 sm:py-8">
    <div class="mx-auto flex min-h-[calc(100vh-2.5rem)] w-full max-w-md items-center justify-center">
      <form class="w-full rounded-[1.75rem] border border-white bg-white/95 p-5 shadow-2xl shadow-blue-950/10 sm:p-7" @submit.prevent="submit">
        <div class="text-center">
          <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-600/25"><KeyRound :size="23" /></span>
          <p class="mt-4 text-[11px] font-black uppercase tracking-[0.28em] text-blue-600">Student Portal</p>
          <h1 class="mt-2 text-2xl font-black tracking-tight sm:text-3xl">Forgot your code?</h1>
          <p class="mt-2 text-sm font-semibold leading-6 text-slate-500">Enter your Student Portal email to receive a new attendance code.</p>
        </div>

        <div v-if="page.props.flash?.success" class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm font-semibold text-emerald-700">
          {{ page.props.flash.success }}
        </div>

        <div class="mt-6 rounded-2xl border border-blue-100 bg-blue-50 p-4">
          <div class="flex items-center gap-2 text-blue-700"><Mail :size="17" /><p class="text-xs font-black uppercase tracking-[0.16em]">Student Portal email</p></div>
          <p class="mt-2 text-sm font-semibold leading-5 text-slate-600">Use the email created for your account, ending in <span class="font-bold text-slate-800">@etec.com</span>.</p>
        </div>

        <label class="mt-5 block">
          <span class="mb-2 block text-sm font-semibold text-slate-700">Email address</span>
          <div class="relative">
            <Mail class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input v-model="form.email" type="email" pattern="[^@\s]+@etec\.com" title="Use an email ending in @etec.com" class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 pl-11 pr-4 text-base text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100" placeholder="your.name@etec.com" autocomplete="email" />
          </div>
        </label>
        <p v-if="form.errors.email" class="mt-2 text-sm font-semibold text-rose-600">{{ form.errors.email }}</p>

        <button type="submit" :disabled="form.processing" class="mt-5 inline-flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 text-sm font-bold text-white shadow-lg shadow-blue-600/25 transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-70">
          <Send :size="17" />{{ form.processing ? "Checking..." : "Continue" }}
        </button>
        <Link href="/student-portal/login" class="mt-5 inline-flex w-full items-center justify-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-blue-600"><ArrowLeft :size="16" />Back to login</Link>
      </form>
    </div>
  </main>
</template>
