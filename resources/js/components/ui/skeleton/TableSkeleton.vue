<script setup>
import { TableCell, TableRow } from '@/components/ui/table'
import Skeleton from './Skeleton.vue'

// Renders placeholder rows shaped like the real table so loading state
// doesn't cause layout shift once data arrives.
defineProps({
  rows: {
    type: Number,
    default: 5,
  },
  // One entry per column: { width, height, rounded, align }.
  columns: {
    type: Array,
    default: () => [{ width: '100%' }],
  },
})
</script>

<template>
  <TableRow v-for="row in rows" :key="`skeleton-row-${row}`">
    <TableCell
      v-for="(column, index) in columns"
      :key="`skeleton-cell-${row}-${index}`"
      :class="column.align === 'right' ? 'text-right' : ''"
    >
      <div :class="column.align === 'right' ? 'flex justify-end' : ''">
        <Skeleton :width="column.width" :height="column.height" :rounded="column.rounded" />
      </div>
    </TableCell>
  </TableRow>
</template>
