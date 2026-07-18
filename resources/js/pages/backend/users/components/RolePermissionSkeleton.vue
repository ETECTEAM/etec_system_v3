<script setup>
import { Skeleton } from '../../../../components/ui/skeleton'
</script>

<template>
  <div class="space-y-6">
    <Skeleton width="14rem" height="0.75rem" rounded="rounded" />

    <!-- Title + stat cards, matching the real header row. -->
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
      <div class="space-y-2">
        <Skeleton width="8rem" height="0.7rem" rounded="rounded" />
        <Skeleton width="16rem" height="1.75rem" rounded="rounded" />
        <Skeleton width="20rem" height="0.875rem" rounded="rounded" />
      </div>

      <div class="grid grid-cols-4 gap-3">
        <div v-for="card in 4" :key="`stat-${card}`" class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <Skeleton width="4rem" height="0.6rem" rounded="rounded" />
          <Skeleton class="mt-2" width="2rem" height="1.25rem" rounded="rounded" />
          <Skeleton class="mt-1" width="5rem" height="0.6rem" rounded="rounded" />
        </div>
      </div>
    </div>

    <!-- Three panels, matching RolesPanel / PermissionsMatrixPanel / AssignUsersPanel. -->
    <div class="grid gap-6 2xl:grid-cols-[280px_1fr_340px]">
      <aside class="self-start rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between pb-3">
          <Skeleton width="4rem" height="1rem" rounded="rounded" />
          <Skeleton width="5.5rem" height="1.75rem" rounded="rounded-lg" />
        </div>

        <div class="space-y-2">
          <div v-for="row in 4" :key="`role-${row}`" class="rounded-xl px-4 py-3">
            <Skeleton width="5rem" height="1.5rem" rounded="rounded-full" />
            <Skeleton class="mt-2" width="4rem" height="0.65rem" rounded="rounded" />
          </div>
        </div>
      </aside>

      <div class="self-start overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-4 border-b border-slate-200 p-5 dark:border-gray-800">
          <div class="flex items-start justify-between">
            <div class="space-y-2">
              <Skeleton width="10rem" height="1rem" rounded="rounded" />
              <Skeleton width="14rem" height="0.75rem" rounded="rounded" />
            </div>
            <div class="flex gap-2">
              <Skeleton width="5rem" height="1.75rem" rounded="rounded-lg" />
              <Skeleton width="2rem" height="1.75rem" rounded="rounded-lg" />
              <Skeleton width="6rem" height="1.75rem" rounded="rounded-lg" />
            </div>
          </div>
          <Skeleton height="2.5rem" rounded="rounded-xl" />
        </div>

        <div class="space-y-4 p-5">
          <div v-for="row in 6" :key="`matrix-${row}`" class="flex items-center gap-4">
            <Skeleton width="30%" height="0.875rem" rounded="rounded" />
            <Skeleton v-for="cell in 5" :key="`matrix-${row}-${cell}`" width="1.25rem" height="1.25rem" rounded="rounded" />
          </div>
        </div>
      </div>

      <div class="self-start rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-slate-200 p-5 dark:border-gray-800">
          <div class="flex items-start justify-between gap-3">
            <div class="space-y-2">
              <Skeleton width="9rem" height="1rem" rounded="rounded" />
              <Skeleton width="6rem" height="0.75rem" rounded="rounded" />
            </div>
            <Skeleton width="5.5rem" height="1.75rem" rounded="rounded-lg" />
          </div>
          <Skeleton class="mt-4" height="2.5rem" rounded="rounded-xl" />
        </div>

        <div class="space-y-4 p-5">
          <div v-for="row in 5" :key="`user-${row}`" class="flex items-center gap-3">
            <Skeleton width="1rem" height="1rem" rounded="rounded" />
            <div class="flex-1 space-y-1.5">
              <Skeleton width="60%" height="0.8rem" rounded="rounded" />
              <Skeleton width="80%" height="0.7rem" rounded="rounded" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
