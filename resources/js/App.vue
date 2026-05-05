<script setup>
import DashboardLayout from "./layouts/DashboardLayout.vue"
import { RouterView } from "vue-router"

</script>

<template>
    <!-- All Layout website put it on DashboardLayout -->
    <DashboardLayout>
        <RouterView />
    </DashboardLayout>
</template>
