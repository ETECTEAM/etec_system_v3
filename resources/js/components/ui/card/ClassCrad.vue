<script setup>
import {
    GraduationCap,
    EllipsisVertical,
    Building2,
    DoorOpen,
    CalendarDays,
    Clock3,
    Users,
    BookOpen,
    MonitorSmartphone,
} from "@lucide/vue";

import { computed } from "vue";
import NotificationBadge from "../notification-badge/NotificationBadge.vue";

const props = defineProps({
    classData: Object,
    count: {
        type: Number,
        default: 0,
    },
});

const capacity = computed(() => props.classData.capacity);

const fill = computed(() => {
    return (props.classData.students / capacity.value) * 100;
});

const online = computed(() => {
    return props.classData.status === "Online Class";
});
</script>

<template>

<div class="bg-white rounded-xl shadow shadow  relative">

    <NotificationBadge :count="props.count" />
    <div class="p-5">
        <div class="flex justify-between">
            <div class="flex gap-3">
                <div
                    class="w-12 h-12 rounded-lg bg-indigo-100 flex justify-center items-center"
                >
                    <GraduationCap class="w-7 h-7 text-indigo-700"/>
                </div>

                <div>

                    <h2 class="font-bold text-lg">

                        {{ classData.title }}

                    </h2>

                    <div class="mt-2">

                        <span class="text-sm">

                            Class ID

                        </span>

                        <span
                            class="bg-indigo-700 text-white px-2 py-1 rounded text-xs ml-2"
                        >
                            {{ classData.id }}
                        </span>

                    </div>

                </div>

            </div>

            <EllipsisVertical class="w-5"/>

        </div>

        <div class="mt-5 space-y-4">

            <div class="flex justify-between">

                <div class="flex gap-2">

                    <BookOpen class="w-4"/>

                    Lesson

                </div>

                <span>{{ classData.lesson }}</span>

            </div>

            <div class="flex justify-between">

                <div class="flex gap-2">

                    <Building2 class="w-4"/>

                    Building

                </div>

                <span>{{ classData.building }}</span>

            </div>

            <div class="flex justify-between">

                <div class="flex gap-2">

                    <DoorOpen class="w-4"/>

                    Room

                </div>

                <span>

                    {{ classData.floor }}

                    {{ classData.room }}

                </span>

            </div>

            <div class="flex justify-between">

                <span>Status</span>

                <span
                    class="px-2 py-1 rounded-full bg-indigo-100 text-indigo-700 text-xs"
                >

                    <MonitorSmartphone
                        v-if="online"
                        class="inline w-3 h-3 mr-1"
                    />

                    {{ classData.status }}

                </span>

            </div>

            <div class="flex justify-between">

                <div class="flex gap-2">

                    <CalendarDays class="w-4"/>

                    Days

                </div>

                <span>{{ classData.term }}</span>

            </div>

            <div class="flex justify-between">

                <div class="flex gap-2">

                    <Clock3 class="w-4"/>

                    Time

                </div>

                <span class="text-green-600">

                    {{ classData.time }}

                </span>

            </div>

            <div>

                <div class="flex justify-between">

                    <div class="flex gap-2">

                        <Users class="w-4"/>

                        Students

                    </div>

                    <span>

                        {{ classData.students }}

                        /

                        {{ capacity }}

                    </span>

                </div>

                <div class="bg-gray-200 h-2 rounded-full mt-2">

                    <div
                        class="bg-indigo-600 h-2 rounded-full"
                        :style="{width:fill+'%'}"
                    />

                </div>

            </div>

        </div>

        <button
            class="mt-6 w-full rounded-lg py-3 bg-blue-500 hover:bg-indigo-700 text-white"
        >

            View Class

        </button>

    </div>

</div>

</template>