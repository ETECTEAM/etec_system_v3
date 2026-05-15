<script setup>
import { Card } from '../card'

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
  <Card :card-class="props.gradientClass" padding="p-6">
    <div class="flex flex-col gap-2">
      <p v-if="props.eyebrow" class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400">{{ props.eyebrow }}</p>
      <h1 class="text-2xl font-semibold text-slate-900 sm:text-3xl">{{ props.title }}</h1>
      <p v-if="props.description" class="max-w-2xl text-sm text-slate-600">{{ props.description }}</p>
    </div>
  </Card>
</template>
