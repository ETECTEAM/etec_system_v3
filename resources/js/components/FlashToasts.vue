<script setup>
import { onMounted, onUnmounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { useToast } from "@/composables/useToast";

const page = usePage();
const toast = useToast();

// Some backend handlers (e.g. the throttle-lockout renderer in bootstrap/app.php)
// put the same message in both flash.error and the errors bag. Dedupe so it only
// toasts once on pages that don't render the inline field error.
function announce(props = {}) {
  const flash = props.flash ?? {};
  const errors = props.errors ?? {};

  const flashEntries = [
    ["success", flash.success],
    ["error", flash.error],
    ["warning", flash.warning],
    ["info", flash.info],
  ].filter(([, message]) => Boolean(message));

  flashEntries.forEach(([type, message]) => toast[type](message));

  const flashTexts = new Set(flashEntries.map(([, message]) => message));
  const errorMessage = Object.values(errors)
    .flat()
    .filter(Boolean)
    .find((message) => !flashTexts.has(message));

  if (errorMessage) {
    toast.error(errorMessage);
  }
}

let stopSuccess;
let stopError;

onMounted(() => {
  // Initial full page load (e.g. a login redirect landing with a flash).
  announce(page.props);

  // Fires exactly once per Inertia visit - so a repeat save with an unchanged
  // message still toasts, unlike watching page.props for a value change.
  stopSuccess = router.on("success", (event) => announce(event.detail.page.props));

  // 422s don't swap the page; surface the first validation error for pages
  // that don't show it inline.
  stopError = router.on("error", (event) => {
    const message = Object.values(event.detail.errors ?? {})
      .flat()
      .filter(Boolean)[0];

    if (message) {
      toast.error(message);
    }
  });
});

onUnmounted(() => {
  stopSuccess?.();
  stopError?.();
});
</script>

<template></template>
