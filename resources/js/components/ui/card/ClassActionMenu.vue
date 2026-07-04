<script setup>
defineProps({
    classData: Object,
});

const emit = defineEmits(["open-bar"]);

function onTriggerClick() {
    emit("open-bar");
}
</script>

<template>
<div class="relative inline-flex">
    <button
        type="button"
        @click.stop="onTriggerClick"
        class="flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 transition"
        aria-label="Class actions"
        aria-haspopup="dialog"
    >
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <circle cx="12" cy="5" r="1.5" />
            <circle cx="12" cy="12" r="1.5" />
            <circle cx="12" cy="19" r="1.5" />
        </svg>
    </button>
</div>
</template>
