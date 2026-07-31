<script setup>
import { ref, computed } from "vue";
import { Mail, MapPin, Phone, User, Info, Edit2, CheckCircle2 } from "@lucide/vue";
import axios from "axios";
import { usePage } from "@inertiajs/vue3";

const props = defineProps({
  pageData: {
    type: Object,
    default: () => ({}),
  }
});

const inertiaPage = usePage();
const website = computed(() => inertiaPage.props.website ?? {});
const settings = computed(() => website.value.settings ?? {});

const contactSubmitting = ref(false);
const contactSuccess = ref("");
const contactErrors = ref({});
const contactForm = ref({
  name: "",
  email: "",
  phone: "",
  subject: "",
  message: "",
  agree: false
});

const submitContact = async () => {
  if (!contactForm.value.agree) {
     contactErrors.value = { agree: ["Please agree to data collection before sending."] };
     return;
  }
  
  contactSubmitting.value = true;
  contactSuccess.value = "";
  contactErrors.value = {};

  try {
    const response = await axios.post("/api/public/contact", contactForm.value);
    contactSuccess.value = response.data?.data?.message || "Your message has been received.";
    contactForm.value = {
      name: "",
      email: "",
      phone: "",
      subject: "",
      message: "",
      agree: false
    };
  } catch (error) {
    if (error.response?.status === 422) {
      contactErrors.value = error.response.data?.errors ?? {};
    } else {
      contactErrors.value = {
        form: ["Sorry, your message could not be sent right now."],
      };
    }
  } finally {
    contactSubmitting.value = false;
  }
};
</script>

<template>
  <section class="relative">
    
    <!-- White Card containing the form and info -->
    <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 mt-12 mb-[-80px] lg:mb-[-120px]">
      <div class="rounded-xl bg-white p-8 sm:p-12 lg:p-16 shadow-2xl flex flex-col lg:flex-row shadow-black/5">
        
        <!-- Left Side: Form -->
        <div class="lg:w-2/3 lg:pr-12 lg:border-r lg:border-slate-200">
          <h2 class="text-3xl font-bold text-slate-900 mb-8">Send Me Message</h2>
          
          <p v-if="contactSuccess" class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 flex items-center gap-3">
             <CheckCircle2 class="h-5 w-5 text-emerald-500 shrink-0" />
             {{ contactSuccess }}
          </p>
          <p v-if="contactErrors.form?.[0]" class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ contactErrors.form[0] }}</p>

          <form @submit.prevent="submitContact" class="grid gap-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 gap-y-12">
               
               <!-- Name -->
               <div class="relative">
                  <User class="absolute left-0 top-3 h-4 w-4 text-slate-400" />
                  <input v-model="contactForm.name" type="text" placeholder="Your Name" class="w-full border-0 border-b border-slate-200 bg-transparent py-2.5 pl-8 text-sm text-slate-900 transition-colors placeholder:text-slate-500 focus:border-[#1A66FF] focus:ring-0" />
                  <span v-if="contactErrors.name?.[0]" class="absolute -bottom-5 left-0 text-xs text-red-500">{{ contactErrors.name[0] }}</span>
               </div>
               
               <!-- Email -->
               <div class="relative">
                  <Mail class="absolute left-0 top-3 h-4 w-4 text-slate-400" />
                  <input v-model="contactForm.email" type="email" placeholder="Email Address" class="w-full border-0 border-b border-slate-200 bg-transparent py-2.5 pl-8 text-sm text-slate-900 transition-colors placeholder:text-slate-500 focus:border-[#1A66FF] focus:ring-0" />
                  <span v-if="contactErrors.email?.[0]" class="absolute -bottom-5 left-0 text-xs text-red-500">{{ contactErrors.email[0] }}</span>
               </div>
               
               <!-- Phone -->
               <div class="relative">
                  <Phone class="absolute left-0 top-3 h-4 w-4 text-slate-400" />
                  <input v-model="contactForm.phone" type="text" placeholder="Your Number" class="w-full border-0 border-b border-slate-200 bg-transparent py-2.5 pl-8 text-sm text-slate-900 transition-colors placeholder:text-slate-500 focus:border-[#1A66FF] focus:ring-0" />
                  <span v-if="contactErrors.phone?.[0]" class="absolute -bottom-5 left-0 text-xs text-red-500">{{ contactErrors.phone[0] }}</span>
               </div>
               
               <!-- Subject -->
               <div class="relative">
                  <Info class="absolute left-0 top-3 h-4 w-4 text-slate-400" />
                  <input v-model="contactForm.subject" type="text" placeholder="Select Subject" class="w-full border-0 border-b border-slate-200 bg-transparent py-2.5 pl-8 text-sm text-slate-900 transition-colors placeholder:text-slate-500 focus:border-[#1A66FF] focus:ring-0" />
                  <span v-if="contactErrors.subject?.[0]" class="absolute -bottom-5 left-0 text-xs text-red-500">{{ contactErrors.subject[0] }}</span>
               </div>

               <!-- Message -->
               <div class="relative md:col-span-2">
                  <Edit2 class="absolute left-0 top-3 h-4 w-4 text-slate-400" />
                  <textarea v-model="contactForm.message" rows="2" placeholder="Feel free to get in touch!" class="w-full resize-none border-0 border-b border-slate-200 bg-transparent py-2.5 pl-8 text-sm text-slate-900 transition-colors placeholder:text-slate-500 focus:border-[#1A66FF] focus:ring-0"></textarea>
                  <span v-if="contactErrors.message?.[0]" class="absolute -bottom-5 left-0 text-xs text-red-500">{{ contactErrors.message[0] }}</span>
               </div>
               
            </div>

            <div class="mt-4 flex flex-col md:flex-row md:items-center gap-6">
               <button type="submit" :disabled="contactSubmitting" class="shrink-0 rounded-md bg-[#1A66FF] px-8 py-3 text-sm font-bold text-white transition-colors hover:bg-blue-700 disabled:opacity-70 disabled:cursor-not-allowed">
                  {{ contactSubmitting ? 'Sending...' : 'Send Message' }}
               </button>
               
               <label class="flex items-center gap-3 cursor-pointer select-none">
                  <input v-model="contactForm.agree" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[#1A66FF] focus:ring-[#1A66FF]" />
                  <span class="text-xs text-slate-500">I agree that my data is collected and stored.</span>
               </label>
            </div>
            <p v-if="contactErrors.agree" class="text-xs text-red-500 -mt-4">{{ contactErrors.agree[0] }}</p>
          </form>
        </div>

        <!-- Right Side: Info -->
        <div class="lg:w-1/3 mt-12 lg:mt-0 lg:pl-12 flex flex-col">
          <h2 class="text-3xl font-bold text-slate-900 mb-8">Get In Touch</h2>
          
          <ul class="grid gap-8 flex-1">
             <li>
                <div class="flex items-center gap-2 text-slate-400 mb-1">
                   <MapPin class="h-4 w-4" />
                   <span class="text-sm font-medium">Address</span>
                </div>
                <p class="text-sm font-semibold text-slate-900 pl-6">Building 12, Street 456, Phnom Penh</p>
             </li>
             <li>
                <div class="flex items-center gap-2 text-slate-400 mb-1">
                   <Phone class="h-4 w-4" />
                   <span class="text-sm font-medium">Phone</span>
                </div>
                <p class="text-sm font-semibold text-slate-900 pl-6">{{ settings.phone || '+855 12 345 678' }}</p>
             </li>
             <li>
                <div class="flex items-center gap-2 text-slate-400 mb-1">
                   <Mail class="h-4 w-4" />
                   <span class="text-sm font-medium">Email</span>
                </div>
                <p class="text-sm font-semibold text-slate-900 pl-6">{{ settings.email || 'info@etec.edu.kh' }}</p>
             </li>
          </ul>

          <div class="mt-8">
             <p class="text-sm text-slate-400 font-medium mb-4 pl-6">Social Media</p>
             <div class="flex gap-2 pl-6">
                <a href="#" class="flex h-10 w-10 items-center justify-center rounded border border-slate-200 text-slate-400 transition-colors hover:border-[#1A66FF] hover:text-[#1A66FF]">
                   <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" /></svg>
                </a>
                <a href="#" class="flex h-10 w-10 items-center justify-center rounded border border-slate-200 text-slate-400 transition-colors hover:border-[#1A66FF] hover:text-[#1A66FF]">
                   <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" /></svg>
                </a>
                <a href="#" class="flex h-10 w-10 items-center justify-center rounded border border-slate-200 text-slate-400 transition-colors hover:border-[#1A66FF] hover:text-[#1A66FF]">
                   <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" clip-rule="evenodd" /></svg>
                </a>
             </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Full Width Map breaking out of container -->
    <div class="mt-24 h-[500px] w-[100vw] relative left-1/2 -translate-x-1/2 bg-slate-100">
       <iframe src="https://maps.google.com/maps?q=ETEC%20Center,%20Phnom%20Penh,%20Cambodia&t=&z=16&ie=UTF8&iwloc=&output=embed" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>

  </section>
</template>

<style scoped>
/* To style inputs using standard tailwind forms plugin, we rely on classes. */
input[type="text"], input[type="email"], textarea {
  box-shadow: none !important;
}
</style>
