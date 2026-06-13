<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    show: Boolean,
    classCategory: Object,
    classTypes: Array
});

const emit = defineEmits(['close']);

const form = useForm({
    class_type_id: '',
    category_name: '',
    category_code: '',
    description: '',
    is_active: 1
});

// Watch for changes to the active item to populate the form
watch(() => props.classCategory, (newData) => {
    if (newData) {
        form.class_type_id = newData.class_type_id;
        form.category_name = newData.category_name;
        form.category_code = newData.category_code || '';
        form.description = newData.description || '';
        form.is_active = newData.is_active ? 1 : 0;
    }
}, { immediate: true });

const submit = () => {
    form.put(`/class-categories/${props.classCategory.class_category_id}`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
            form.reset();
        }
    });
};
</script>

<template>
    <Transition
        enter-active-class="ease-out duration-300 font-sans"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="ease-in duration-200 font-sans"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="show" class="fixed inset-0 bg-slate-900/15 backdrop-blur-[1px] flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-xl border border-slate-200 max-w-lg w-full overflow-hidden transform transition-all duration-300">
                
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                    <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-indigo-600"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" /></svg>
                        Edit Class Category
                    </h2>
                    <button type="button" @click="emit('close')" class="text-slate-400 hover:text-slate-600 text-sm">✕</button>
                </div>

                <form @submit.prevent="submit">
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Class Type *</label>
                            <select v-model="form.class_type_id" class="block w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none cursor-pointer transition-all" required>
                                <option v-for="type in classTypes" :key="type.class_type_id" :value="type.class_type_id">
                                    {{ type.type_name }}
                                </option>
                            </select>
                            <span v-if="form.errors.class_type_id" class="text-xs text-rose-600 mt-1 block">{{ form.errors.class_type_id }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5">Category Name *</label>
                                <input v-model="form.category_name" type="text" class="block w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all" required>
                                <span v-if="form.errors.category_name" class="text-xs text-rose-600 mt-1 block">{{ form.errors.category_name }}</span>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5">Category Code</label>
                                <input v-model="form.category_code" type="text" class="block w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Description</label>
                            <textarea v-model="form.description" rows="3" class="block w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none resize-none transition-all"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Status</label>
                            <select v-model="form.is_active" class="block w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none cursor-pointer transition-all">
                                <option :value="1">Active</option>
                                <option :value="0">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-2 bg-slate-50/50">
                        <button type="button" @click="emit('close')" class="px-4 py-2 text-sm font-medium text-slate-500 hover:bg-slate-100 rounded-lg transition-colors border border-slate-200 bg-white">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-xs flex items-center gap-2 transition-all disabled:opacity-50">
                            <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Transition>
</template>