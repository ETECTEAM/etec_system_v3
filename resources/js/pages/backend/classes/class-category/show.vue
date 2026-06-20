<script setup>
defineProps({
    show: Boolean,
    classCategory: Object
});

const emit = defineEmits(['close']);
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
            <div class="bg-white rounded-xl shadow-xl border border-slate-200 max-w-md w-full overflow-hidden transform transition-all duration-300">
                
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-indigo-600"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                        Category Details
                    </h2>
                    <button type="button" @click="emit('close')" class="text-slate-400 hover:text-slate-600 text-sm">✕</button>
                </div>

                <div class="p-6 space-y-4 text-sm">
                    <div class="flex justify-between border-b border-slate-100 pb-2">
                        <span class="text-slate-500 font-medium">Category Code:</span>
                        <span class="font-mono font-bold text-slate-800">{{ classCategory?.category_code || '-' }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-2">
                        <span class="text-slate-500 font-medium">Category Name:</span>
                        <span class="font-bold text-slate-900">{{ classCategory?.category_name }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-2">
                        <span class="text-slate-500 font-medium">Class Type:</span>
                        <span class="font-semibold text-indigo-600">{{ classCategory?.class_type?.type_name || 'N/A' }}</span>
                    </div>
                    <div class="flex flex-col border-b border-slate-100 pb-2 gap-1">
                        <span class="text-slate-500 font-medium">Description:</span>
                        <p class="text-slate-700 bg-slate-50 p-2.5 rounded-lg text-xs font-medium">{{ classCategory?.description || 'No description provided.' }}</p>
                    </div>
                    <div class="flex justify-between pt-1">
                        <span class="text-slate-500 font-medium">Status:</span>
                        <span :class="classCategory?.is_active ? 'text-emerald-600 font-bold' : 'text-slate-500 font-bold'">
                            {{ classCategory?.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>

                <div class="px-6 py-3 border-t border-slate-100 bg-slate-50/50 flex justify-end">
                    <button type="button" @click="emit('close')" class="px-4 py-2 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">Close</button>
                </div>
            </div>
        </div>
    </Transition>
</template>