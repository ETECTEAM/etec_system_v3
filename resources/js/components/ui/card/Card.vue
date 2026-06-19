<script setup>
const props = defineProps({
  padding: {
    type: String,
    default: 'p-6',
  },
  cardClass: {
    type: String,
    default: '',
  },
})
</script>

<template>
  <div :class="['rounded-xl border border-slate-200 bg-white shadow-sm', props.padding, props.cardClass]">
    <slot />
  </div>
</template>
