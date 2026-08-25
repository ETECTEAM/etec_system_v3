<script setup>
import { computed } from 'vue'
import { useI18n } from '@/i18n'

const props = defineProps({
  status: {
    type: String,
    required: true,
  },
  size: {
    type: String,
    default: 'md',
  },
})

const { t } = useI18n()

// pending yellow / approved green / rejected red / revoked gray.
const styles = {
  pending: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300',
  approved: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300',
  rejected: 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300',
  revoked: 'border-slate-200 bg-slate-100 text-slate-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400',
}

const labels = {
  pending: 'Pending',
  approved: 'Approved',
  rejected: 'Rejected',
  revoked: 'Revoked',
}

const style = computed(() => styles[props.status] ?? styles.revoked)
const label = computed(() => t(labels[props.status] ?? props.status))
</script>

<template>
  <span :class="['inline-flex items-center rounded-lg border font-black capitalize', size === 'sm' ? 'px-2 py-0.5 text-[11px]' : 'px-3 py-1 text-xs', style]">
    {{ label }}
  </span>
</template>
