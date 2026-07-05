<script setup>
import { ref } from "vue";
import { UserPlus, Banknote, Download, Printer } from "@lucide/vue";

const actionMode = ref("student");
</script>

<template>
  <div class="flex flex-wrap items-center gap-3">
    <!-- Add Student -->
    <button
      @click="actionMode = 'student'"
      :class="[
        'inline-flex items-center justify-center gap-2 rounded-xl border px-5 py-2.5 text-sm font-semibold shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-2',
        actionMode === 'student'
          ? 'border-indigo-900 bg-indigo-900 text-white'
          : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100',
      ]"
    >
      <UserPlus class="h-4 w-4" />
      Add Student
    </button>

    <!-- Record Deposit -->
    <button
      @click="actionMode = 'deposit'"
      :class="[
        'inline-flex items-center justify-center gap-2 rounded-xl border px-5 py-2.5 text-sm font-semibold shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-2',
        actionMode === 'deposit'
          ? 'border-indigo-900 bg-indigo-900 text-white'
          : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100',
      ]"
    >
      <Banknote class="h-4 w-4" />
      Record Deposit
    </button>

    <!-- Export -->
    <button
      @click="actionMode = 'export'"
      :class="[
        'inline-flex items-center justify-center gap-2 rounded-xl border px-5 py-2.5 text-sm font-semibold shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-2',
        actionMode === 'export'
          ? 'border-indigo-900 bg-indigo-900 text-white'
          : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100',
      ]"
    >
      <Download class="h-4 w-4" />
      Export Report
    </button>

    <!-- Print -->
    <button
      @click="actionMode = 'print'"
      :class="[
        'inline-flex items-center justify-center gap-2 rounded-xl border px-5 py-2.5 text-sm font-semibold shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-2',
        actionMode === 'print'
          ? 'border-indigo-900 bg-indigo-900 text-white'
          : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100',
      ]"
    >
      <Printer class="h-4 w-4" />
      Print List
    </button>
  </div>
</template>