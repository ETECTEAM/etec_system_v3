<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

// Import your custom UI components if you have them, 
// otherwise, the standard classes below maintain the design system
const props = defineProps({
    show: Boolean,
    classType: Object
});

const emit = defineEmits(['close']);

const form = useForm({
    type_name: '',
    description: '',
    is_active: 1
});

watch(() => props.classType, (newData) => {
    if (newData) {
        form.type_name = newData.type_name;
        form.description = newData.description || '';
        form.is_active = newData.is_active ? 1 : 0;
    }
}, { immediate: true });

const submit = () => {
    form.put(`/class-types/${props.classType.class_type_id}`, {
        onSuccess: () => emit('close')
    });
};
</script>

<template>
    <Transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="show" class="fixed inset-0 bg-slate-900/15 backdrop-blur-[1px] flex items-center justify-center z-50 p-4">
            
            <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-lg w-full overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h2 class="font-bold text-slate-800">Edit Class Type</h2>
                    <button @click="emit('close')" class="text-slate-400 hover:text-slate-600 font-bold">✕</button>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Type Name</label>
                        <input v-model="form.type_name" type="text" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition outline-none" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Description</label>
                        <textarea v-model="form.description" rows="3" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition outline-none resize-none"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Status</label>
                        <select v-model="form.is_active" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition outline-none cursor-pointer">
                            <option :value="1">Active</option>
                            <option :value="0">Inactive</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" @click="emit('close')" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 rounded-lg transition">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition flex items-center gap-2">
                            <svg v-if="form.processing" class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Transition>
</template>