<script setup>
import { onBeforeUnmount, onMounted, watch } from 'vue'
import AuthHero from '../pages/auth/components/AuthHero.vue'
import { initParticles } from '../lib/particles/initParticles'
import { useTheme } from '../composables/useTheme'

const { resolvedTheme } = useTheme()

let destroyParticles = null

function startParticles() {
  destroyParticles?.()
  destroyParticles = initParticles('particles-js', { isDark: resolvedTheme.value === 'dark' })
}

onMounted(startParticles)

// Re-render with theme-appropriate colors whenever the user flips light/dark.
watch(resolvedTheme, startParticles)

onBeforeUnmount(() => {
  destroyParticles?.()
})
</script>

<template>
  <main class="min-h-screen bg-slate-100 text-slate-900 dark:bg-gray-950 dark:text-gray-100">
    <div class="grid min-h-screen w-full grid-cols-1 lg:grid-cols-[1.1fr_0.9fr]">
      <AuthHero />

      <section class="relative z-10 flex min-h-screen items-center justify-center bg-white p-6 sm:p-10 lg:border-l lg:border-gray-200 lg:shadow-[-24px_0_48px_-20px_rgba(15,23,42,0.55)] xl:p-16 dark:bg-gray-900 dark:lg:border-gray-800 dark:lg:shadow-[-24px_0_48px_-20px_rgba(0,0,0,0.9)]">
        <slot />

      </section>
    </div>
  </main>
</template>
