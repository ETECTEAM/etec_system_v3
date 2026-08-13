<script setup>
import { usePage } from "@inertiajs/vue3";
import { watch } from "vue";
import { useToast } from "vue-toastification";

const page = usePage();
const toast = useToast();

let lastSignature = "";

// Some backend handlers (e.g. the throttle-lockout exception renderer in
// bootstrap/app.php) put the same message in both the errors bag (so a page
// can show it inline under a field) and flash.error (so pages without that
// field still get a toast). Treating those as one combined, de-duped source
// avoids toasting the identical message twice on pages like student-register
// that don't render errors.login/errors.throttle inline.
function collectMessages(flash = {}, errors = {}) {
  const flashEntries = [
    ["success", flash.success],
    ["error", flash.error],
    ["warning", flash.warning],
    ["info", flash.info],
  ].filter(([, message]) => Boolean(message));

  const flashTexts = new Set(flashEntries.map(([, message]) => message));
  const errorMessage = Object.values(errors)
    .flat()
    .filter(Boolean)
    .find((message) => !flashTexts.has(message));

  return { flashEntries, errorMessage };
}

function showAll() {
  const { flashEntries, errorMessage } = collectMessages(page.props.flash, page.props.errors);

  const signature = JSON.stringify([flashEntries, errorMessage]);
  if (signature === lastSignature) return;
  lastSignature = signature;

  flashEntries.forEach(([type, message]) => toast[type](message));

  if (errorMessage) {
    toast.error(errorMessage);
  }
}

watch(
  () => [page.props.flash, page.props.errors],
  () => showAll(),
  { deep: true, immediate: true },
);
</script>

<template></template>
