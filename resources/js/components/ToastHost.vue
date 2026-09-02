<script setup>
import { Check, Info, TriangleAlert, X } from "@lucide/vue";
import { useToastStore } from "@/composables/useToast";

const { toasts, remove, pause, resume } = useToastStore();

// Icon + colour per toast type. Falls back to the "info" look for anything else.
const META = {
  success: { icon: Check, title: "Success", card: "bg-emerald-50 border-emerald-100 dark:bg-emerald-500/10 dark:border-emerald-500/25", badge: "bg-emerald-500" },
  error: { icon: TriangleAlert, title: "Error", card: "bg-rose-50 border-rose-100 dark:bg-rose-500/10 dark:border-rose-500/25", badge: "bg-rose-500" },
  warning: { icon: Info, title: "Warning", card: "bg-amber-50 border-amber-100 dark:bg-amber-500/10 dark:border-amber-500/25", badge: "bg-amber-400" },
  info: { icon: Info, title: "Notice", card: "bg-blue-50 border-blue-100 dark:bg-blue-500/10 dark:border-blue-500/25", badge: "bg-blue-500" },
};

function metaFor(type) {
  return META[type] ?? META.info;
}
</script>

<template>
  <div class="pointer-events-none fixed bottom-4 right-4 z-[9999] flex w-full max-w-sm flex-col gap-3">
    <TransitionGroup name="toast">
      <div v-for="toast in toasts" :key="toast.id" class="pointer-events-auto flex items-start gap-3 rounded-2xl border p-4 shadow-lg" :class="metaFor(toast.type).card" @mouseenter="pause(toast.id)" @mouseleave="resume(toast.id)">
        <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-white" :class="metaFor(toast.type).badge">
          <component :is="metaFor(toast.type).icon" class="h-5 w-5" />
        </span>
        <div class="min-w-0 flex-1">
          <p class="text-sm font-bold text-slate-900 dark:text-gray-100">{{ $t(metaFor(toast.type).title) }}</p>
          <p class="mt-0.5 break-words text-sm text-slate-600 dark:text-gray-300">{{ toast.message }}</p>
        </div>
        <button type="button" class="-m-1 shrink-0 rounded p-1 text-slate-400 transition hover:text-slate-600 dark:hover:text-gray-200" @click="remove(toast.id)">
          <X class="h-4 w-4" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-enter-active {
  transition: opacity 0.3s ease, transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}

.toast-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
  /* Pull a departing toast out of flow so the rest reflow via .toast-move. */
  position: absolute;
  right: 0;
  width: 100%;
}

/* The slide-up of the toasts left behind when one is dismissed. */
.toast-move {
  transition: transform 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(1.75rem) scale(0.97);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(1.75rem);
}
</style>
