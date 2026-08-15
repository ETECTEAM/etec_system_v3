<script setup>
import { useI18n } from '@/i18n'

const { t } = useI18n()
</script>

<template>
  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <!-- z-[60]: above the sidebar (z-40), header, and its dropdowns (z-30),
         but below ConfirmDialog (z-[100]) and the bug-annotation tool
         (z-[9999]/[10000]) so an open confirm prompt or annotation session
         doesn't disappear behind the glass if a navigation starts mid-use. -->
    <div
      class="page-loading-glass fixed inset-0 z-[60] flex items-center justify-center overflow-hidden"
      role="status"
      aria-live="polite"
    >
      <!-- decorative glass depth: off-center glow + diagonal light sheen -->
      <div class="page-loading-glow" aria-hidden="true"></div>
      <div class="page-loading-sheen" aria-hidden="true"></div>

      <div class="relative flex flex-col items-center gap-4 px-6 text-center">
        <div class="page-loading-spinner h-12 w-12 animate-spin rounded-full"></div>
        <p class="text-sm font-medium tracking-wide text-white drop-shadow-[0_1px_4px_rgba(20,20,22,0.45)]">
          {{ t('Loading, please wait...') }}
        </p>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
/* Translucent gray tint + real backdrop blur, so the whole dashboard
   behind it (sidebar + navbar + content - this overlay is a fixed, viewport-
   level sibling of all three, not nested inside <main>) stays visible
   through the glass instead of being hidden by an opaque layer. */
.page-loading-glass {
  background-color: rgba(120, 122, 128, 0.18);
  background-image: linear-gradient(135deg, rgba(20, 20, 22, 0.32), rgba(110, 113, 120, 0.24) 50%, rgba(200, 202, 206, 0.18));
  /* backdrop-filter: blur(18px) saturate(130%); */
  /* -webkit-backdrop-filter: blur(18px) saturate(130%); */
}

/* Soft off-center glow so the glass reads as lit rather than flat-tinted. */
.page-loading-glow {
  position: absolute;
  inset: -25%;
  background:
    radial-gradient(circle at 28% 22%, rgba(255, 255, 255, 0.4), transparent 55%),
    radial-gradient(circle at 78% 78%, rgba(180, 182, 188, 0.35), transparent 50%);
  filter: blur(40px);
  pointer-events: none;
}

/* Diagonal highlight band, like light catching a glass/mirror surface. */
.page-loading-sheen {
  position: absolute;
  inset: 0;
  background: linear-gradient(115deg, rgba(255, 255, 255, 0.28) 0%, rgba(255, 255, 255, 0) 28%, rgba(255, 255, 255, 0) 72%, rgba(255, 255, 255, 0.14) 100%);
  mix-blend-mode: overlay;
  pointer-events: none;
}

.page-loading-spinner {
  border: 3px solid rgba(255, 255, 255, 0.35);
  border-top-color: #ffffff;
  box-shadow: 0 0 18px rgba(226, 227, 230, 0.6);
}

@media (prefers-reduced-motion: reduce) {
  .page-loading-spinner {
    animation-duration: 1.5s;
  }
}
</style>
