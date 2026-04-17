<script setup>
import { onBeforeUnmount, onMounted } from 'vue'
import AuthHero from '../components/auth/AuthHero.vue'
import { initAuthParticles } from '../lib/initAuthParticles'

let destroyParticles = null

onMounted(() => {
  destroyParticles = initAuthParticles()
})

onBeforeUnmount(() => {
  destroyParticles?.()
})
</script>

<template>
  <main class="min-h-screen bg-slate-100 text-slate-900">
    <div class="grid min-h-screen w-full grid-cols-1 lg:grid-cols-[1.1fr_0.9fr]">
      <AuthHero />

      <section class="flex min-h-screen items-center justify-center bg-white p-6 sm:p-10 xl:border-l xl:border-gray-200 xl:p-16">
        <slot />
      </section>
    </div>
  </main>
</template>
