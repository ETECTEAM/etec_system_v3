<script setup>
import { onBeforeUnmount, onMounted } from 'vue'
import AuthHero from '../pages/auth/components/AuthHero.vue'
import { initParticles } from '../lib/particles/initParticles'

let destroyParticles = null

onMounted(() => {
  destroyParticles = initParticles()
})

onBeforeUnmount(() => {
  destroyParticles?.()
})
</script>

<template>
  <main class="min-h-screen bg-slate-100 text-slate-900 dark:bg-gray-950 dark:text-gray-100">
    <div class="grid min-h-screen w-full grid-cols-1 lg:grid-cols-[1.1fr_0.9fr]">
      <AuthHero />

      <section class="flex min-h-screen items-center justify-center bg-white p-6 sm:p-10 xl:border-l xl:border-gray-200 xl:p-16 dark:bg-gray-900 dark:xl:border-gray-800">
        <slot />
      
      </section>
    </div>
  </main>
</template>
