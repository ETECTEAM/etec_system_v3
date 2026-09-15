<script setup>
import { Head, Link } from "@inertiajs/vue3";

defineProps({
  studentName: { type: String, required: true },
  attendanceCode: { type: String, required: true },
});
</script>

<template>

  <Head title="Your Student Portal code" />
  <main class="flex min-h-screen items-center justify-center bg-[#E8EAE3] px-4 text-[#16223B]">
    <section class="w-full max-w-md rounded-lg border border-[#DCDFD6] bg-white p-7 text-center shadow-sm">
      <p class="text-xs font-black uppercase tracking-[.25em] text-[#2A4BD7]">Student Portal</p>
      <h1 class="mt-3 text-3xl font-black">Hello, {{ studentName }}</h1>
      <p class="mt-2 text-sm text-[#5A6478]">Your attendance code is</p>
      <p class="mt-5 rounded-lg bg-blue-50 px-4 py-4 text-2xl font-black tracking-[.18em] text-blue-700">{{
        attendanceCode }}</p>
      <p class="mt-4 text-sm text-[#5A6478]">Keep this code private. It is used to log in and scan attendance.</p>
      <Link href="/student-portal/login"
        class="mt-6 inline-flex h-12 items-center rounded bg-[#2A4BD7] px-5 font-bold text-white transition-colors hover:bg-[#16223B]">
        Open Student Portal</Link>
    </section>
  </main>
</template>
