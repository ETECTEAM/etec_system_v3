<script setup>
import { useForm } from '@inertiajs/vue3';

defineProps({
    show: Boolean,
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

const submit = () => {
    form.post('/class-categories', {
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
            <div class="bg-white rounded-xl shadow-xl border border-slate-200 max-w-lg w-full overflow-hidden transform transition-all duration-300 scale-100 opacity-100">
                
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                    <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-indigo-600"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Add New Class Category
                    </h2>
                    <button type="button" @click="emit('close')" class="text-slate-400 hover:text-slate-600 text-sm">✕</button>
                </div>

                <form @submit.prevent="submit">
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-606 mb-1.5">Class Type *</label>
                            <select v-model="form.class_type_id" class="block w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none cursor-pointer transition-all" required>
                                <option value="" disabled>-- Select Class Type --</option>
                                <option v-for="type in classTypes" :key="type.class_type_id" :value="type.class_type_id">
                                    {{ type.type_name }}
                                </option>
                            </select>
                            <span v-if="form.errors.class_type_id" class="text-xs text-rose-600 mt-1 block">{{ form.errors.class_type_id }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-606 mb-1.5">Category Name *</label>
                                <input v-model="form.category_name" type="text" class="block w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all" placeholder="e.g. Web Design" required>
                                <span v-if="form.errors.category_name" class="text-xs text-rose-600 mt-1 block">{{ form.errors.category_name }}</span>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-606 mb-1.5">Category Code</label>
                                <input v-model="form.category_code" type="text" class="block w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all" placeholder="e.g. C001">
                                <span v-if="form.errors.category_code" class="text-xs text-rose-600 mt-1 block">{{ form.errors.category_code }}</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-606 mb-1.5">Description</label>
                            <textarea v-model="form.description" rows="3" class="block w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none resize-none transition-all" placeholder="Enter optional details..."></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-606 mb-1.5">Status</label>
                            <select v-model="form.is_active" class="block w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none cursor-pointer transition-all">
                                <option :value="1">Active</option>
                                <option :value="0">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-2 bg-slate-50/50">
                        <button type="button" @click="emit('close')" class="px-4 py-2 text-sm font-medium text-slate-500 hover:bg-slate-100 rounded-lg transition-colors border border-slate-200 bg-white">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-xs flex items-center gap-2 transition-all">
                            <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Save Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Transition>
</template>