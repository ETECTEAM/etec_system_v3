<script setup>
import { useI18n } from '@/i18n'

const { t } = useI18n()
</script>

<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-50 dark:bg-gray-950">
      <div class="flex flex-col items-center gap-3 rounded-2xl border border-slate-200 bg-white px-10 py-10 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="h-10 w-10 animate-spin rounded-full border-4 border-blue-500 border-t-transparent"></div>
        <p class="text-sm font-medium text-slate-600 dark:text-gray-300">{{ t('Loading, please wait...') }}</p>
      </div>
    </div>
  </Teleport>
</template>
