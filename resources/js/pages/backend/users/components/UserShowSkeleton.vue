<script setup>
import { Skeleton } from '../../../../components/ui/skeleton'
</script>

<template>
  <div class="space-y-6">
    <Skeleton width="10rem" height="0.75rem" rounded="rounded" />

    <!-- Title + Edit/Back buttons, matching the real header row. -->
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
      <div class="space-y-2">
        <Skeleton width="8rem" height="0.7rem" rounded="rounded" />
        <Skeleton width="12rem" height="1.75rem" rounded="rounded" />
        <Skeleton width="22rem" height="0.875rem" rounded="rounded" />
      </div>

      <div class="flex gap-3">
        <Skeleton width="6rem" height="2.5rem" rounded="rounded-xl" />
        <Skeleton width="6.5rem" height="2.5rem" rounded="rounded-xl" />
      </div>
    </div>

    <!-- Full Name / Email / Role cards. -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <div v-for="card in 3" :key="`detail-${card}`" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <Skeleton width="5rem" height="0.65rem" rounded="rounded" />
        <Skeleton class="mt-3" width="8rem" height="1.25rem" rounded="rounded" />
      </div>
    </div>
  </div>
</template>
