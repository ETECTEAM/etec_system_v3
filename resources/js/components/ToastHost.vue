<script setup>
import { Check, Info, TriangleAlert, X } from "@lucide/vue";
import { useToastStore } from "@/composables/useToast";

const { toasts, remove, pause, resume } = useToastStore();

// Icon + colour per toast type. Falls back to the "info" look for anything else.
// Solid (non-translucent) card backgrounds — a tinted-opacity background lets
// whatever's behind the toast (e.g. another floating panel) bleed through and
// muddy the text, so dark mode uses a solid dark surface with a colored
// left-border accent instead of a colored, see-through fill.
const META = {
  success: { icon: Check, title: "Success", card: "bg-emerald-50 border-emerald-100 border-l-4 border-l-emerald-500 dark:bg-gray-900 dark:border-gray-800 dark:border-l-emerald-500", badge: "bg-emerald-500" },
  error: { icon: TriangleAlert, title: "Error", card: "bg-rose-50 border-rose-100 border-l-4 border-l-rose-500 dark:bg-gray-900 dark:border-gray-800 dark:border-l-rose-500", badge: "bg-rose-500" },
  warning: { icon: Info, title: "Warning", card: "bg-amber-50 border-amber-100 border-l-4 border-l-amber-400 dark:bg-gray-900 dark:border-gray-800 dark:border-l-amber-400", badge: "bg-amber-400" },
  info: { icon: Info, title: "Notice", card: "bg-blue-50 border-blue-100 border-l-4 border-l-blue-500 dark:bg-gray-900 dark:border-gray-800 dark:border-l-blue-500", badge: "bg-blue-500" },
};

function metaFor(type) {
  return META[type] ?? META.info;
}
</script>

<template>
  <div class="pointer-events-none fixed top-4 right-4 z-[9999] flex w-full max-w-sm flex-col gap-3">
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
