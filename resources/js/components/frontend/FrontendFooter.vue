<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { MapPin, Phone, Mail, ArrowRight } from "@lucide/vue";

defineProps({
  settings: {
    type: Object,
    default: () => ({}),
  },
  menus: {
    type: Array,
    default: () => [],
  },
});

const currentYear = computed(() => new Date().getFullYear());
</script>

<template>
  <footer class="bg-[#0A1D3A] pt-20 pb-10 text-slate-300 relative overflow-hidden">
    <!-- Background Decor -->
    <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-[#1A66FF]/10 rounded-full blur-3xl translate-x-1/3 -translate-y-1/2 pointer-events-none"></div>
    <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-[#FFB800]/5 rounded-full blur-3xl -translate-x-1/3 translate-y-1/3 pointer-events-none"></div>

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
      
      <!-- Top Section: CTA -->
      <div class="flex flex-col md:flex-row items-center justify-between p-8 sm:p-12 bg-white/5 backdrop-blur-md border border-white/10 rounded-3xl mb-16 gap-8">
         <div>
            <h3 class="text-3xl font-black text-white mb-2">Ready to master your skills?</h3>
            <p class="text-blue-200/80 max-w-lg">Join thousands of students building their tech careers with our industry-leading curriculum.</p>
         </div>
         <Link href="/classes" class="shrink-0 flex items-center justify-center h-14 px-8 rounded-full bg-[#FFB800] text-slate-900 font-bold hover:bg-yellow-400 hover:-translate-y-1 transition-all shadow-[0_10px_30px_rgba(255,184,0,0.2)]">
            Explore Classes <ArrowRight class="ml-2 h-5 w-5" />
         </Link>
      </div>

      <!-- Main Footer Grid -->
      <div class="grid gap-12 sm:grid-cols-2 lg:grid-cols-4 lg:gap-8 mb-16">
        
        <!-- Column 1: Brand & About -->
        <div class="flex flex-col">
          <Link href="/" class="inline-flex items-center gap-3 mb-6">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white/10 text-xs font-black text-white backdrop-blur-sm border border-white/20">
              <img v-if="settings.logo_url" :src="settings.logo_url" :alt="settings.school_name" class="h-full w-full object-cover" />
              <span v-else class="text-lg">ETEC</span>
            </span>
            <span class="text-2xl font-black text-white">{{ settings.school_name || "ETEC Center" }}</span>
          </Link>
          <p class="text-sm leading-relaxed text-blue-100/70 mb-8 max-w-sm">
             We empower the next generation of IT professionals with real-world skills, practical knowledge, and industry-standard training.
          </p>
          
          <!-- Social Icons using standard SVGs -->
          <div class="flex gap-3 mt-auto">
             <a href="#" aria-label="Facebook" class="flex h-10 w-10 items-center justify-center rounded-full bg-white/5 border border-white/10 text-slate-300 transition-all hover:bg-[#1A66FF] hover:border-[#1A66FF] hover:text-white">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" /></svg>
             </a>
             <a href="#" aria-label="Twitter" class="flex h-10 w-10 items-center justify-center rounded-full bg-white/5 border border-white/10 text-slate-300 transition-all hover:bg-sky-500 hover:border-sky-500 hover:text-white">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" /></svg>
             </a>
             <a href="#" aria-label="LinkedIn" class="flex h-10 w-10 items-center justify-center rounded-full bg-white/5 border border-white/10 text-slate-300 transition-all hover:bg-blue-600 hover:border-blue-600 hover:text-white">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" clip-rule="evenodd" /></svg>
             </a>
             <a href="#" aria-label="YouTube" class="flex h-10 w-10 items-center justify-center rounded-full bg-white/5 border border-white/10 text-slate-300 transition-all hover:bg-red-600 hover:border-red-600 hover:text-white">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z" clip-rule="evenodd" /></svg>
             </a>
          </div>
        </div>

        <!-- Column 2: Quick Links -->
        <div>
          <h4 class="text-white font-bold text-lg mb-6">Quick Links</h4>
          <nav class="grid gap-3">
            <Link href="/classes" class="inline-flex text-sm text-blue-100/70 transition-colors hover:text-[#FFB800]">
              Classes
            </Link>
          </nav>
        </div>
        
        <!-- Column 3: Contact Info -->
        <div>
           <h4 class="text-white font-bold text-lg mb-6">Contact Us</h4>
           <ul class="grid gap-4">
              <li class="flex items-start gap-4 text-blue-100/70 text-sm leading-relaxed">
                 <MapPin class="h-5 w-5 shrink-0 text-[#1A66FF] mt-0.5" />
                 <span>Building 12, Street 456<br />Phnom Penh, Cambodia</span>
              </li>
              <li class="flex items-center gap-4 text-blue-100/70 text-sm">
                 <Phone class="h-5 w-5 shrink-0 text-[#1A66FF]" />
                 <span>{{ settings.phone || '+855 12 345 678' }}</span>
              </li>
              <li class="flex items-center gap-4 text-blue-100/70 text-sm">
                 <Mail class="h-5 w-5 shrink-0 text-[#1A66FF]" />
                 <span>{{ settings.email || 'info@etec.edu.kh' }}</span>
              </li>
           </ul>
        </div>

        <!-- Column 4: Newsletter -->
        <div>
           <h4 class="text-white font-bold text-lg mb-6">Stay Updated</h4>
           <p class="text-sm leading-relaxed text-blue-100/70 mb-4">
              Subscribe to our newsletter for the latest tech courses, workshops, and career opportunities.
           </p>
           <form @submit.prevent class="relative group">
              <input type="email" placeholder="Enter your email" class="w-full bg-white/5 border border-white/10 rounded-xl py-3.5 pl-4 pr-12 text-sm text-white placeholder:text-slate-500 focus:outline-none focus:border-[#1A66FF] focus:bg-white/10 transition-all" />
              <button type="submit" class="absolute right-2 top-2 h-9 w-9 rounded-lg bg-[#1A66FF] flex items-center justify-center text-white transition-all hover:bg-white hover:text-[#1A66FF]">
                 <ArrowRight class="h-4 w-4" />
              </button>
           </form>
        </div>

      </div>
      
      <!-- Bottom Bar -->
      <div class="border-t border-white/10 pt-8 flex flex-col md:flex-row items-center justify-between gap-4 text-sm text-slate-500">
         <p>© {{ currentYear }} {{ settings.school_name || "ETEC Center" }}. All rights reserved.</p>
         
         <div class="flex gap-6">
            <Link href="/privacy" class="hover:text-white transition-colors">Privacy Policy</Link>
            <Link href="/terms" class="hover:text-white transition-colors">Terms of Service</Link>
            <Link href="/cookies" class="hover:text-white transition-colors">Cookies Settings</Link>
         </div>
      </div>
    </div>
  </footer>
</template>
