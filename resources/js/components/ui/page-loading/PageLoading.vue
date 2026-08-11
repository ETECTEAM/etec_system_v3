<script setup>
import { useI18n } from '@/i18n'

const { t } = useI18n()
</script>

<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/35 dark:bg-black/55">
      <div class="h-12 w-12 animate-spin rounded-full border-4 border-blue-500 border-t-transparent"></div>
      <p class="sr-only">{{ t('Loading, please wait...') }}</p>
    </div>
  </Teleport>
</template>
