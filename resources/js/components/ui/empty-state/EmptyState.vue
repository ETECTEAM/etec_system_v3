<script setup>
import { computed } from "vue";
import { GraduationCap } from "@lucide/vue";
import { useI18n } from '@/i18n'

const { t } = useI18n()
const emit = defineEmits(['action'])

const props = defineProps({
  icon: {
    type: [Object, Function],
    default: null,
  },
  title: {
    type: String,
    default: 'No Classes Found',
  },
  description: {
    type: String,
    default: 'There are currently no classes available. Create a new enrollment class to get started.',
  },
  actionLabel: {
    type: String,
    default: 'Add Enroll',
  },
})

const icon = computed(() => props.icon ?? GraduationCap)
</script>

<template>
  <div class="flex flex-col items-center justify-center gap-4 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center dark:border-gray-700 dark:bg-gray-900">
    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-500 ring-1 ring-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-400 dark:ring-indigo-500/20">
      <component :is="icon" class="h-8 w-8" />
    </div>
    <div class="space-y-1">
      <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">{{ t(props.title) }}</h3>
      <p class="max-w-md text-sm text-slate-500 dark:text-gray-400">{{ t(props.description) }}</p>
    </div>
    <!-- <button v-if="props.actionLabel" type="button" @click="emit('action')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500">
      {{ t(props.actionLabel) }}
    </button> -->
  </div>
</template>
