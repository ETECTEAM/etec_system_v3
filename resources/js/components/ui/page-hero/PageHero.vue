<script setup>
import { useI18n } from '@/i18n'

const { t } = useI18n()

const props = defineProps({
  eyebrow: {
    type: String,
    default: '',
  },
  title: {
    type: String,
    default: '',
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
    <p v-if="props.eyebrow" class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-gray-500">{{ t(props.eyebrow) }}</p>
    <h1 v-if="props.title" class="text-3xl font-bold text-slate-900 sm:text-4xl dark:text-gray-100">{{ t(props.title) }}</h1>
    <p v-if="props.description" class="max-w-3xl text-base text-slate-600 dark:text-gray-400">{{ t(props.description) }}</p>
  </div>
</template>
