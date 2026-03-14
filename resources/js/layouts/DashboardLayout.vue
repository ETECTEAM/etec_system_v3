<script setup>
import Sidebar from "./Sidebar.vue"

</script>

<template>

<div class="layout">

    <!-- Sidebar -->
    <Sidebar />

    <div class="main">
        <!-- Page content -->
        <div class="content">
            <slot></slot>
        </div>

    </div>

</div>
</template>
