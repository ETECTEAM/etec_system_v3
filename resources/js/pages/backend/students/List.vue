<script setup>
import { ref } from 'vue'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import { QrcodeCanvas } from 'qrcode.vue'
import { QrCode } from '@lucide/vue';

const showQr = ref(false)
const qrUrl = 'https://last-provolone-pantry.ngrok-free.dev/dashboard/students/form'
// const qrUrl = 'http://127.0.0.1:8000/students/form';
const students = ref([
    {
        id: 1,
        name: 'Plok Joy',
        course: 'Vue JS',
        time: '08:00 AM',
        status: 'Present',
    },

    {
        id: 2,
        name: 'Dara',
        course: 'Laravel',
        time: '09:30 AM',
        status: 'Late',
    },
])

</script>

<template>

    <DashboardLayout>

        <div class="space-y-5">

            <!-- Header -->

            <div class=" flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 rounded-xl border border-slate-200 shadow-sm dark:bg-gray-900 dark:border-gray-800" >
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-gray-100">
                        QR Registration
                    </h1>
                    <p class="text-sm text-slate-500 mt-1 dark:text-gray-400">
                        Generate QR for student registration
                    </p>
                </div>

                <button @click="showQr = true" class="bg-blue-600 hover:bg-blue-700 transition text-white px-4 py-3 rounded-lg flex justify-center items-center gap-3">
                    <QrCode /> Generate QR
                </button>

            </div>

            <!-- Table -->

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden dark:bg-gray-900 dark:border-gray-800" >
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-100 dark:bg-gray-800">
                            <tr>
                                <th class="px-6 py-4 text-left">
                                    ID
                                </th>
                                <th class="px-6 py-4 text-left">
                                    Name
                                </th>
                                <th class="px-6 py-4 text-left">
                                    Course
                                </th>
                                <th class="px-6 py-4 text-left">
                                    Time
                                </th>
                                <th class="px-6 py-4 text-left">
                                    Status
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="student in students"
                                :key="student.id"
                                class="border-b last:border-0 hover:bg-slate-50 dark:border-gray-800 dark:hover:bg-gray-800"
                            >
                                <td class="px-6 py-4">
                                    #{{ student.id }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ student.name }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ student.course }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ student.time }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        v-if="student.status === 'Present'"
                                        class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-medium dark:bg-green-500/10 dark:text-green-400"
                                    >
                                        Present
                                    </span>
                                    <span
                                        v-else
                                        class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-medium dark:bg-yellow-500/10 dark:text-yellow-400"
                                    >
                                        Late
                                    </span>
                                </td>
                            </tr>
                        </tbody>

                    </table>

                </div>

            </div>

            <!-- QR Modal -->

            <transition
                enter-active-class="transition duration-200"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-150"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >

                <div
                    v-if="showQr"
                    class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
                    @click.self="showQr = false"
                >

                    <div
                        class="bg-white rounded-3xl w-full max-w-sm p-6 shadow-2xl dark:bg-gray-900"
                    >

                        <div
                            class="flex justify-between items-center mb-5"
                        >

                            <h2 class="text-xl font-bold dark:text-gray-100">
                                Scan QR Code
                            </h2>

                            <button @click="showQr = false" class="text-gray-500 hover:text-black text-xl dark:text-gray-400 dark:hover:text-gray-200">
                                ✕
                            </button>
                        </div>

                        <div class="flex justify-center">
                            <div class="bg-white p-4 rounded-2xl">
                               <QrcodeCanvas :value="qrUrl" :size="240" level="H"/>
                            </div>
                        </div>
                        <p class="text-center text-sm text-gray-500 mt-4 dark:text-gray-400" >
                            Scan QR using mobile phone
                        </p>
                        <a :href="qrUrl" target="_blank" class="mt-5 block text-center bg-blue-600 hover:bg-blue-700 transition text-white py-3 rounded-xl">
                            Open Form
                        </a>
                    </div>
                </div>
            </transition>
        </div>
    </DashboardLayout>
</template>