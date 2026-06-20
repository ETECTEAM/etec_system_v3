<script setup>
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    show: Boolean,
    classType: Object
});

const emit = defineEmits(['close']);
const isProcessing = ref(false);

const submitDelete = () => {
    if (!props.classType) return;
    
    isProcessing.value = true;
    router.delete(`/class-types/${props.classType.class_type_id}`, {
        onSuccess: () => emit('close'),
        onFinish: () => isProcessing.value = false
    });
};
</script>

<template>
    <Transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="show" class="fixed inset-0 bg-slate-900/15 backdrop-blur-[1px] flex items-center justify-center z-50 p-4">
            
            <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-sm w-full overflow-hidden">
                
                <div class="p-8 flex flex-col items-center text-center">
                    <div class="w-14 h-14 rounded-full bg-rose-50 flex items-center justify-center text-rose-600 mb-5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-7 h-7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.34 9m-4.78 0L9 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-1.8c0-.621-.504-1.125-1.125-1.125h-2.25c-.621 0-1.125.504-1.125 1.125v1.8M16.5 12.005h-.008v.008H16.5v-.008Zm-3.75 0h-.008v.008h.008v-.008Zm-3.75 0H9v.008h.008v-.008Z" />
                        </svg>
                    </div>

                    <h3 class="text-lg font-bold text-slate-900 mb-2">Delete Class Type</h3>
                    <p class="text-sm text-slate-500 mb-6">
                        Are you sure you want to permanently remove <span class="font-bold text-slate-900">{{ classType?.type_name }}</span>? This action cannot be undone.
                    </p>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex gap-3">
                    <button type="button" :disabled="isProcessing" @click="emit('close')" class="flex-1 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-white border border-slate-200 rounded-lg transition">
                        Cancel
                    </button>
                    <button type="button" :disabled="isProcessing" @click="submitDelete" class="flex-1 px-4 py-2 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm transition flex items-center justify-center gap-2">
                        <svg v-if="isProcessing" class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>