<script setup>
import Sidebar from "./Sidebar.vue"
</script>

<template>

<div class="flex min-h-screen w-full bg-slate-50">

    <!-- Sidebar -->
    <Sidebar />

    <div class="flex-1 min-w-0">
        <!-- Page content -->
        <div class="p-6">
            <slot></slot>
        </div>
    </div>

</div>
</template>
