<script setup>
defineProps({
  width: {
    type: String,
    default: '100%',
  },
  height: {
    type: String,
    default: '0.875rem',
  },
  rounded: {
    type: String,
    default: 'rounded-md',
  },
})
</script>

<template>
  <div
    class="animate-pulse bg-slate-200 dark:bg-gray-700"
    :class="rounded"
    :style="{ width, height }"
  />
</template>
