<script setup>
import { computed, ref, watch } from "vue";

const props = defineProps({
    count: {
        type: Number,
        default: 0,
    },
});

const displayCount = computed(() =>
    props.count > 99 ? "99+" : props.count
);

const isVisible = computed(() => props.count > 0);

const pulsing = ref(false);

watch(
    () => props.count,
    (newVal, oldVal) => {
        if (newVal > 0 && newVal > oldVal) {
            pulsing.value = true;
            setTimeout(() => {
                pulsing.value = false;
            }, 700);
        }
    }
);
</script>

<template>
    <div
        v-if="isVisible"
        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full border-2 border-white shadow-lg flex items-center justify-center text-xs font-bold min-w-5 h-5 px-1 z-50 dark:border-gray-950"
        :class="pulsing ? 'animate-pulse-custom' : 'animate-scale-in'"
    >
        {{ displayCount }}
    </div>
</template>

<style>
@keyframes scale-in {
    from {
        transform: scale(0.8);
    }
    to {
        transform: scale(1);
    }
}
@keyframes pulse-custom {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
    }
}
.animate-scale-in {
    animation: scale-in 200ms ease-out;
}
.animate-pulse-custom {
    animation: pulse-custom 600ms ease-out;
}
</style>