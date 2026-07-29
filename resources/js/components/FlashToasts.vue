<script setup>
import { usePage } from "@inertiajs/vue3";
import { watch } from "vue";
import { useToast } from "vue-toastification";

const page = usePage();
const toast = useToast();

let lastFlashSignature = "";
let lastErrorSignature = "";

function showFlash(flash = {}) {
  const entries = [
    ["success", flash.success],
    ["error", flash.error],
    ["warning", flash.warning],
    ["info", flash.info],
  ].filter(([, message]) => Boolean(message));

  const signature = JSON.stringify(entries);
  if (!entries.length || signature === lastFlashSignature) return;

  lastFlashSignature = signature;

  entries.forEach(([type, message]) => {
    toast[type](message);
  });
}

function showValidationErrors(errors = {}) {
  const messages = Object.values(errors)
    .flat()
    .filter(Boolean);
  const signature = JSON.stringify(messages);

  if (!messages.length || signature === lastErrorSignature) return;

  lastErrorSignature = signature;
  toast.error(messages[0] ?? "Please fix the validation errors.");
}

watch(
  () => page.props.flash,
  (flash) => showFlash(flash),
  { deep: true, immediate: true },
);

watch(
  () => page.props.errors,
  (errors) => showValidationErrors(errors),
  { deep: true, immediate: true },
);
</script>

<template></template>
