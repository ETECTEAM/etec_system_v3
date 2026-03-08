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

<style scoped>

.layout{
    display:flex;
}

.main{
    flex:1;
}

.content{
    padding:20px;
}
</style>