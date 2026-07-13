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
    default: '',
  },
})
</script>

<template>
  <div :class="['flex flex-col gap-2', props.gradientClass]">
    <p v-if="props.eyebrow" class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-gray-500">{{ props.eyebrow }}</p>
    <h1 class="text-3xl font-bold text-slate-900 sm:text-4xl dark:text-gray-100">{{ props.title }}</h1>
    <p v-if="props.description" class="max-w-3xl text-base text-slate-600 dark:text-gray-400">{{ props.description }}</p>
  </div>
</template>
