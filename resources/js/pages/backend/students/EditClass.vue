<script setup>
import { useForm, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { GraduationCap, ArrowLeft, Save } from "@lucide/vue";
import DashboardLayout from "../../../layouts/DashboardLayout.vue";
import Breadcrumbs from "../../../components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "../../../components/ui/page-hero/PageHero.vue";

const props = defineProps({
    classData: Object,
    courses: Array,
    lessons: Array,
    buildings: Array,
    floors: Array,
    rooms: Array,
    terms: Array,
    times: Array,
});

const form = useForm({
    title: props.classData.title ?? "",
    course_id: props.classData.course_id ?? "",
    lesson_id: props.classData.lesson_id ?? "",
    building_id: props.classData.building_id ?? "",
    floor_id: props.classData.floor_id ?? "",
    room_id: props.classData.room_id ?? "",
    term_id: props.classData.term_id ?? "",
    time_id: props.classData.time_id ?? "",
    capacity: props.classData.capacity ?? 20,
    status: props.classData.status ?? "active",
});

const breadcrumbItems = [
    { label: "Dashboard", href: "/dashboard" },
    { label: "Class List", href: route("students.index") },
    { label: "Edit Class", current: true },
];

function back() {
    router.visit(route("students.index"));
}

function submit() {
    form.put(route("students.update", { class: props.classData.id }), {
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <DashboardLayout>
        <div class="w-full">
            <div class="space-y-4 sm:space-y-5">
                <Breadcrumbs :items="breadcrumbItems" />
                <PageHero />

                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-indigo-100 flex items-center justify-center shrink-0">
                            <GraduationCap class="w-6 h-6 sm:w-7 sm:h-7 text-indigo-600" />
                        </div>
                        <div>
                            <h1 class="text-xl sm:text-2xl font-bold text-slate-900">Edit Class</h1>
                            <p class="text-sm sm:text-base text-slate-500">Update class information for #{{ classData.id }}.</p>
                        </div>
                    </div>
                    <button
                        @click="back"
                        class="flex items-center gap-2 border border-slate-300 rounded-xl px-4 sm:px-5 py-2.5 sm:py-3 hover:bg-slate-100 self-start sm:self-auto text-sm sm:text-base whitespace-nowrap"
                    >
                        <ArrowLeft class="w-4 h-4" />
                        Back
                    </button>
                </div>

                <div v-if="form.hasErrors" class="bg-red-50 border border-red-200 rounded-xl p-4">
                    <p class="text-sm font-semibold text-red-800 mb-1">Please fix the following errors:</p>
                    <ul class="list-disc list-inside text-sm text-red-600">
                        <li v-for="(msg, field) in form.errors" :key="field">{{ msg }}</li>
                    </ul>
                </div>

                <div class="bg-white rounded-2xl sm:rounded-3xl shadow-xl border border-slate-200 p-4 sm:p-6 lg:p-8">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5 lg:gap-6">
                        <div>
                            <label class="font-semibold mb-2 block">Class Title <span class="text-red-500">*</span></label>
                            <input
                                v-model="form.title"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-indigo-500 outline-none"
                            />
                            <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
                        </div>

                        <div>
                            <label class="font-semibold mb-2 block">Course</label>
                            <select v-model="form.course_id" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                                <option value="">Select Course</option>
                                <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.title }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="font-semibold mb-2 block">Lesson</label>
                            <select v-model="form.lesson_id" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                                <option value="">Select Lesson</option>
                                <option v-for="l in lessons" :key="l.id" :value="l.id">{{ l.title }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="font-semibold mb-2 block">Status</label>
                            <select v-model="form.status" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>

                        <div>
                            <label class="font-semibold mb-2 block">Building</label>
                            <select v-model="form.building_id" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                                <option value="">Select Building</option>
                                <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="font-semibold mb-2 block">Floor</label>
                            <select v-model="form.floor_id" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                                <option value="">Select Floor</option>
                                <option v-for="f in floors" :key="f.id" :value="f.id">{{ f.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="font-semibold mb-2 block">Room</label>
                            <select v-model="form.room_id" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                                <option value="">Select Room</option>
                                <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.room_number }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="font-semibold mb-2 block">Study Days</label>
                            <select v-model="form.term_id" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                                <option value="">Select Days</option>
                                <option v-for="t in terms" :key="t.id" :value="t.id">{{ t.term_name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="font-semibold mb-2 block">Study Time <span class="text-red-500">*</span></label>
                            <select v-model="form.time_id" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                                <option value="">Select Time</option>
                                <option v-for="t in times" :key="t.id" :value="t.id">{{ t.time_name }}</option>
                            </select>
                            <p v-if="form.errors.time_id" class="text-red-500 text-xs mt-1">{{ form.errors.time_id }}</p>
                        </div>

                        <div>
                            <label class="font-semibold mb-2 block">Capacity <span class="text-red-500">*</span></label>
                            <input
                                type="number"
                                min="1"
                                v-model="form.capacity"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3"
                            />
                            <p v-if="form.errors.capacity" class="text-red-500 text-xs mt-1">{{ form.errors.capacity }}</p>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 sm:gap-4 mt-6 sm:mt-8 lg:mt-10">
                        <button
                            @click="back"
                            class="w-full sm:w-auto px-6 py-3 rounded-xl border border-slate-300 hover:bg-slate-100 text-sm sm:text-base"
                        >
                            Cancel
                        </button>

                        <button
                            @click="submit"
                            :disabled="form.processing"
                            class="w-full sm:w-auto px-6 sm:px-8 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white flex items-center justify-center gap-2 text-sm sm:text-base"
                        >
                            <Save class="w-4 h-4" />
                            {{ form.processing ? 'Updating...' : 'Update Class' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
