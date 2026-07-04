<script setup>
import { useForm, router } from "@inertiajs/vue3";
import { GraduationCap,ArrowLeft, Save,} from "@lucide/vue";

const form = useForm({
    title: "",
    lesson: "",
    building: "",
    floor: "",
    room: "",
    status: "",
    term: "",
    time: "",
    capacity: 20,
    description: "",
});

function back() {
    router.get("/dashboard/students");
}

function submit() {
    form.post("/dashboard/students");
}
</script>
<template>
    <DashboardLayout>
        <div class="max-w-6xl mx-auto py-8 px-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl bg-indigo-100 flex items-center justify-center">
                        <GraduationCap class="w-8 h-8 text-indigo-600"/>
                    </div>
                    <div>
                        <h1
                            class="text-3xl font-bold text-slate-900">
                            Create New Class
                        </h1>
                        <p class="text-slate-500">
                            Create and manage class information.
                        </p>
                    </div>
                </div>
                <button
                    @click="back"
                    class="flex items-center gap-2 border border-slate-300 rounded-xl px-5 py-3 hover:bg-slate-100">
                    <ArrowLeft class="w-4 h-4"/>
                    Back
                </button>
            </div>

            <!-- Card -->

            <div
                class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
                <div
                    class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

                    <!-- Title -->
                    <div>
                        <label class="font-semibold mb-2 block">
                            Class Title
                        </label>
                        <input
                            v-model="form.title"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-indigo-500 outline-none"
                            placeholder="Web Design + React.js"/>
                    </div>

                    <!-- Lesson -->

                    <div>
                        <label class="font-semibold mb-2 block">
                            Lesson
                        </label>
                        <input
                            v-model="form.lesson"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3"
                            placeholder="Bootstrap"/>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="font-semibold mb-2 block">
                            Status
                        </label>
                        <select v-model="form.status" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                            <option value="">
                                Select Status
                            </option>
                            <option>
                                Physical Class
                            </option>
                            <option>
                                Online Class
                            </option>
                        </select>
                    </div>

                    <!-- Building -->

                    <div>
                        <label class="font-semibold mb-2 block">
                            Building
                        </label>

                        <select
                            v-model="form.building"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3">
                            <option>
                                Building A
                            </option>
                            <option>
                                Building B
                            </option>
                            <option>
                                Building C
                            </option>
                        </select>
                    </div>

                    <!-- Floor -->
                    <div>
                        <label class="font-semibold mb-2 block">
                            Floor
                        </label>
                        <select v-model="form.floor" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                            <option>
                                Floor 1
                            </option>
                            <option>
                                Floor 2
                            </option>
                            <option>
                                Floor 3
                            </option>
                        </select>
                    </div>

                    <!-- Room -->
                    <div>
                        <label class="font-semibold mb-2 block">
                            Room
                        </label>
                        <input v-model="form.room" class="w-full rounded-xl border border-slate-300 px-4 py-3" placeholder="B101"/>
                    </div>

                    <!-- Term -->
                    <div>
                        <label class="font-semibold mb-2 block">
                            Study Days
                        </label>
                        <input  v-model="form.term" class="w-full rounded-xl border border-slate-300 px-4 py-3" placeholder="Mon & Thu"/>
                    </div>

                    <!-- Time -->
                    <div>
                        <label class="font-semibold mb-2 block">
                            Study Time
                        </label>
                        <input v-model="form.time" class="w-full rounded-xl border border-slate-300 px-4 py-3" placeholder="09:00 AM - 10:30 AM"/>
                    </div>

                    <!-- Capacity -->
                    <div>
                        <label class="font-semibold mb-2 block">
                            Capacity
                        </label>
                        <input type="number" min="1" v-model="form.capacity" class="w-full rounded-xl border border-slate-300 px-4 py-3"/>
                    </div>
                </div>
                <!-- Footer -->

                <div class="flex justify-end gap-4 mt-10">

                    <button
                        @click="back"
                        class="px-6 py-3 rounded-xl border border-slate-300 hover:bg-slate-100">

                        Cancel

                    </button>

                    <button
                        @click="submit"
                        class="px-8 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white flex items-center gap-2">

                        <Save class="w-4 h-4"/>

                        Save Class

                    </button>

                </div>

            </div>

        </div>

    </DashboardLayout>

</template>