<script setup>
import { Card } from '../../../../components/ui/card'
import { Skeleton, TableSkeleton } from '../../../../components/ui/skeleton'
import { Table, TableBody, TableHead, TableHeader, TableRow } from '../../../../components/ui/table'
</script>

<template>
  <div class="space-y-6">
    <Skeleton width="8rem" height="0.75rem" rounded="rounded" />

    <div class="space-y-2">
      <Skeleton width="8rem" height="0.7rem" rounded="rounded" />
      <Skeleton width="12rem" height="1.75rem" rounded="rounded" />
      <Skeleton width="20rem" height="0.875rem" rounded="rounded" />
    </div>

    <!-- Matches UserTableSection: toolbar + table with the same columns. -->
    <Card padding="p-0">
      <div class="border-b border-slate-200 px-6 py-5 dark:border-gray-800">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div class="space-y-2">
            <Skeleton width="8rem" height="0.7rem" rounded="rounded" />
            <Skeleton width="14rem" height="0.875rem" rounded="rounded" />
          </div>

          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-3">
            <Skeleton width="14rem" height="2.5rem" rounded="rounded-xl" />
            <Skeleton width="10rem" height="2.5rem" rounded="rounded-xl" />
            <Skeleton width="8rem" height="2.5rem" rounded="rounded-xl" />
          </div>
        </div>
      </div>

      <Table>
        <TableHeader>
          <TableRow>
            <TableHead class="w-16">No</TableHead>
            <TableHead>Name</TableHead>
            <TableHead>Email</TableHead>
            <TableHead>Roles</TableHead>
            <TableHead>Status</TableHead>
            <TableHead>Created By</TableHead>
            <TableHead>Created At</TableHead>
            <TableHead class="text-right">Actions</TableHead>
          </TableRow>
        </TableHeader>

        <TableBody>
          <TableSkeleton
            :rows="5"
            :columns="[
              { width: '1.5rem' },
              { width: '70%' },
              { width: '85%' },
              { width: '5rem', rounded: 'rounded-full' },
              { width: '4rem', rounded: 'rounded-full' },
              { width: '60%' },
              { width: '75%' },
              { width: '2rem', align: 'right' },
            ]"
          />
        </TableBody>
      </Table>

      <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/40">
        <Skeleton width="12rem" height="0.8rem" rounded="rounded" />
        <Skeleton width="10rem" height="2rem" rounded="rounded-lg" />
      </div>
    </Card>
  </div>
</template>
