<script setup>
import { computed } from 'vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

// Some i18n setups echo the key when a translation is missing, so guard
// against showing raw keys in the loading UI.
function tf(key, fallback) {
  const value = t(key)
  return !value || value === key ? fallback : value
}

const loadingLabel = computed(() => tf('common.loading', 'Loading'))
const subtitleLabel = computed(() => tf('loading.subtitle', 'Just a moment while we get things ready.'))
</script>

<template>
  <Transition
    enter-active-class="transition duration-200 ease-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition duration-150 ease-in"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <!-- <div
      class="page-loading-shell fixed inset-0 z-[60] flex items-center justify-center overflow-hidden"
      role="status"
      aria-live="polite"
      aria-busy="true"
    >
      <div class="page-loading-backdrop" aria-hidden="true"></div>
      <div class="page-loading-glow page-loading-glow--left" aria-hidden="true"></div>
      <div class="page-loading-glow page-loading-glow--right" aria-hidden="true"></div>

      <div class="page-loading-card relative z-10 flex flex-col items-center gap-5 px-9 py-8 text-center">
        <div class="page-loading-spinner" aria-hidden="true">
          <svg class="page-loading-ring" viewBox="0 0 100 100" focusable="false" aria-hidden="true">
            <circle class="page-loading-ring-track" cx="50" cy="50" r="42" />
            <circle class="page-loading-ring-arc" cx="50" cy="50" r="42" />
          </svg>
          <span class="page-loading-core" aria-hidden="true"></span>
        </div>

        <div class="max-w-sm space-y-1.5">
          <p class="page-loading-title text-sm font-semibold tracking-[0.22em] uppercase">
            {{ loadingLabel }}
          </p>
          <p class="page-loading-subtitle text-sm leading-6">
            {{ subtitleLabel }}
          </p>
        </div>
      </div>
    </div> -->
  </Transition>
</template>

<!-- <style scoped>
.page-loading-shell {
  --pl-bg-1: #f4f6f8;
  --pl-bg-2: #e9edf2;
  --pl-sheen: rgba(255, 255, 255, 0.18);
  --pl-glow-left: rgba(148, 163, 184, 0.08);
  --pl-glow-right: rgba(148, 163, 184, 0.05);
  --pl-track: rgba(148, 163, 184, 0.2);
  --pl-arc: #94a3b8;
  --pl-core: #64748b;
  --pl-core-glow: rgba(148, 163, 184, 0.12);
  --pl-title: #475569;
  --pl-subtitle: #6b7280;
  --pl-card-bg: rgba(248, 250, 252, 0.92);
  --pl-card-border: rgba(226, 232, 240, 0.92);
  --pl-card-shadow: rgba(71, 85, 105, 0.06);

  background:
    radial-gradient(circle at 50% 35%, var(--pl-sheen), transparent 40%),
    linear-gradient(180deg, var(--pl-bg-1) 0%, var(--pl-bg-2) 100%);
  color-scheme: light;
}

@media (prefers-color-scheme: dark) {
  .page-loading-shell {
    --pl-bg-1: #15191f;
    --pl-bg-2: #1c222b;
    --pl-sheen: rgba(148, 163, 184, 0.04);
    --pl-glow-left: rgba(96, 165, 250, 0.04);
    --pl-glow-right: rgba(148, 163, 184, 0.03);
    --pl-track: rgba(148, 163, 184, 0.16);
    --pl-arc: #cbd5e1;
    --pl-core: #e2e8f0;
    --pl-core-glow: rgba(148, 163, 184, 0.12);
    --pl-title: #cbd5e1;
    --pl-subtitle: #94a3b8;
    --pl-card-bg: rgba(30, 35, 43, 0.94);
    --pl-card-border: rgba(148, 163, 184, 0.14);
    --pl-card-shadow: rgba(0, 0, 0, 0.3);
    color-scheme: dark;
  }
}

:global(html.dark) .page-loading-shell {
  --pl-bg-1: #15191f;
  --pl-bg-2: #1c222b;
  --pl-sheen: rgba(148, 163, 184, 0.04);
  --pl-glow-left: rgba(96, 165, 250, 0.04);
  --pl-glow-right: rgba(148, 163, 184, 0.03);
  --pl-track: rgba(148, 163, 184, 0.16);
  --pl-arc: #cbd5e1;
  --pl-core: #e2e8f0;
  --pl-core-glow: rgba(148, 163, 184, 0.12);
  --pl-title: #cbd5e1;
  --pl-subtitle: #94a3b8;
  --pl-card-bg: rgba(30, 35, 43, 0.94);
  --pl-card-border: rgba(148, 163, 184, 0.14);
  --pl-card-shadow: rgba(0, 0, 0, 0.3);
  color-scheme: dark;
}

:global(html.light) .page-loading-shell {
  --pl-bg-1: #f4f6f8;
  --pl-bg-2: #e9edf2;
  --pl-sheen: rgba(255, 255, 255, 0.18);
  --pl-glow-left: rgba(148, 163, 184, 0.08);
  --pl-glow-right: rgba(148, 163, 184, 0.05);
  --pl-track: rgba(148, 163, 184, 0.2);
  --pl-arc: #94a3b8;
  --pl-core: #64748b;
  --pl-core-glow: rgba(148, 163, 184, 0.12);
  --pl-title: #475569;
  --pl-subtitle: #6b7280;
  --pl-card-bg: rgba(248, 250, 252, 0.92);
  --pl-card-border: rgba(226, 232, 240, 0.92);
  --pl-card-shadow: rgba(71, 85, 105, 0.06);
  color-scheme: light;
}

.page-loading-backdrop {
  position: absolute;
  inset: 0;
  backdrop-filter: blur(4px) saturate(100%);
  -webkit-backdrop-filter: blur(4px) saturate(100%);
}

.page-loading-glow {
  position: absolute;
  width: 30rem;
  height: 30rem;
  border-radius: 9999px;
  filter: blur(96px);
  opacity: 0.3;
  pointer-events: none;
}

.page-loading-glow--left {
  top: -10rem;
  left: -8rem;
  background: var(--pl-glow-left);
}

.page-loading-glow--right {
  right: -8rem;
  bottom: -10rem;
  background: var(--pl-glow-right);
}

.page-loading-card {
  border-radius: 1.25rem;
  background: var(--pl-card-bg);
  border: 1px solid var(--pl-card-border);
  box-shadow: 0 10px 28px -18px var(--pl-card-shadow);
  backdrop-filter: blur(8px) saturate(104%);
  -webkit-backdrop-filter: blur(8px) saturate(104%);
}

.page-loading-title {
  color: var(--pl-title);
}

.page-loading-subtitle {
  color: var(--pl-subtitle);
}

.page-loading-spinner {
  position: relative;
  width: 3.75rem;
  height: 3.75rem;
}

.page-loading-ring {
  width: 100%;
  height: 100%;
  transform: rotate(-90deg);
  animation: page-loading-rotate 1.2s linear infinite;
}

.page-loading-ring-track {
  fill: none;
  stroke: var(--pl-track);
  stroke-width: 6;
}

.page-loading-ring-arc {
  fill: none;
  stroke: var(--pl-arc);
  stroke-width: 6;
  stroke-linecap: round;
  stroke-dasharray: 150 264;
  stroke-dashoffset: 28;
}

.page-loading-core {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0.42rem;
  height: 0.42rem;
  border-radius: 9999px;
  transform: translate(-50%, -50%);
  background: var(--pl-core);
  box-shadow: 0 0 8px var(--pl-core-glow);
}

@keyframes page-loading-rotate {
  to {
    transform: rotate(270deg);
  }
}

@media (prefers-reduced-motion: reduce) {
  .page-loading-ring {
    animation-duration: 2.6s;
  }
}
</style> -->
