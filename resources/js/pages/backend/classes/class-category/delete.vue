<script setup>
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

// ប្ដូរ props ពី classType មកជា classCategory
const props = defineProps({
    show: Boolean,
    classCategory: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close']);
const isProcessing = ref(false);

const submitDelete = () => {
    if (!props.classCategory || !props.classCategory.class_category_id) return;
    
    isProcessing.value = true;

    // ប្ដូរ URL ឱ្យត្រូវនឹង Route របស់ Categories
    router.delete(`/class-categories/${props.classCategory.class_category_id}`, {
        onSuccess: () => {
            isProcessing.value = false;
            emit('close');
        },
        onError: (errors) => {
            console.error("Delete failed:", errors);
            isProcessing.value = false;
        },
        onFinish: () => {
            isProcessing.value = false;
        }
    });
};
</script>

<template>
    <Transition
        enter-active-class="ease-out duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="show" class="fixed inset-0 bg-slate-900/15 backdrop-blur-[1px] flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-xl border border-slate-200 max-w-md w-full overflow-hidden transform transition-all duration-300 animate-slide-down">
                <div class="p-6 flex flex-col items-center text-center">
                    <div class="w-12 h-12 rounded-full bg-rose-50 flex items-center justify-center text-rose-600 mb-4 border border-rose-100 animate-soft-bounce">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.34 9m-4.78 0L9 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-1.8c0-.621-.504-1.125-1.125-1.125h-2.25c-.621 0-1.125.504-1.125 1.125v1.8M16.5 12.005h-.008v.008H16.5v-.008Zm-3.75 0h-.008v.008h.008v-.008Zm-3.75 0H9v.008h.008v-.008Z" />
                        </svg>
                    </div>

                    <h3 class="text-base font-bold text-slate-800 mb-1">Delete Category?</h3>
                    <p class="text-sm text-slate-500 max-w-xs">
                        Are you sure you want to permanently delete 
                        <span class="font-bold text-slate-800 break-words">"{{ classCategory?.category_name }}"</span>? 
                        This action cannot be undone.
                    </p>
                </div>

                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex flex-col-reverse sm:flex-row justify-end gap-2">
                    <button type="button" :disabled="isProcessing" @click="$emit('close')" class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-slate-500 hover:bg-slate-100 rounded-lg border border-slate-200 bg-white">
                        No, Cancel
                    </button>
                    <button type="button" :disabled="isProcessing" @click="submitDelete" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 rounded-lg gap-2">
                        <svg v-if="isProcessing" class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        Yes, Delete It
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.animate-slide-down { animation: slideDown 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards; }
@keyframes slideDown { from { opacity: 0; transform: translateY(-2rem) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(100%); } }
.animate-soft-bounce { animation: softBounce 1s infinite; }
@keyframes softBounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
</style>