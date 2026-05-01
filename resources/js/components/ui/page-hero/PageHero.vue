<script setup>
const props = defineProps({
  eyebrow: {
    type: String,
    default: '',
  },
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
  gradientClass: {
    type: String,
    default: 'from-blue-950 via-blue-900 to-sky-800',
  },
})
</script>

<template>
  <div :class="`rounded-3xl bg-linear-to-r p-6 text-white shadow-xl ${props.gradientClass}`">
    <p v-if="props.eyebrow" class="text-xs uppercase tracking-[0.3em] text-blue-100">{{ props.eyebrow }}</p>
    <h1 class="mt-3 text-3xl font-black">{{ props.title }}</h1>
    <p v-if="props.description" class="mt-2 max-w-2xl text-sm text-blue-100/90">{{ props.description }}</p>
  </div>
</template>
