<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { GraduationCap, KeyRound, LogIn } from "@lucide/vue";

const form = useForm({ attendance_code: "" });

function submit() {
  form.post("/student-portal/login");
}
</script>

<template>
  <Head title="Student Portal" />

  <main class="min-h-screen bg-[linear-gradient(180deg,#f7fbff_0%,#eef5ff_100%)] px-4 py-5 text-slate-950 sm:px-6 sm:py-8">
    <div class="mx-auto flex min-h-[calc(100vh-2.5rem)] w-full max-w-md items-center justify-center">
      <form class="w-full rounded-[1.75rem] border border-white bg-white/95 p-5 shadow-2xl shadow-blue-950/10 sm:p-7" @submit.prevent="submit">
        <div class="text-center">
          <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-600/25"><GraduationCap :size="24" /></span>
          <p class="mt-4 text-[11px] font-black uppercase tracking-[0.28em] text-blue-600">Student Portal</p>
          <h1 class="mt-2 text-2xl font-black tracking-tight sm:text-3xl">Check your attendance</h1>
          <p class="mt-2 text-sm font-semibold leading-6 text-slate-500">Enter the attendance code provided when you joined a class.</p>
        </div>

        <div class="mt-6 rounded-2xl border border-blue-100 bg-blue-50 p-4">
          <div class="flex items-center gap-2 text-blue-700"><KeyRound :size="17" /><p class="text-xs font-black uppercase tracking-[0.16em]">Attendance code</p></div>
          <p class="mt-2 text-sm font-semibold leading-5 text-slate-600">Your code lets you enter the portal and record attendance after scanning a QR code.</p>
        </div>

        <label class="mt-5 block">
          <span class="mb-2 block text-sm font-semibold text-slate-700">Student code</span>
          <div class="relative">
            <KeyRound class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input v-model="form.attendance_code" type="text" maxlength="9" autocomplete="off" class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 pl-11 pr-4 text-base font-semibold uppercase tracking-[0.12em] text-slate-950 outline-none transition placeholder:font-normal placeholder:tracking-normal placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100" placeholder="XXXX-XXXX" />
          </div>
        </label>
        <p v-if="form.errors.attendance_code" class="mt-2 text-sm font-semibold text-rose-600">{{ form.errors.attendance_code }}</p>

        <button type="submit" :disabled="form.processing" class="mt-5 inline-flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 text-sm font-bold text-white shadow-lg shadow-blue-600/25 transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-70">
          <LogIn :size="17" />{{ form.processing ? "Checking..." : "Open Student Portal" }}
        </button>
        <Link href="/student-portal/forgot-code" class="mt-5 inline-flex w-full items-center justify-center text-sm font-semibold text-blue-600 transition hover:text-blue-700 hover:underline">Forgot your code?</Link>
      </form>
    </div>
  </main>
</template>
