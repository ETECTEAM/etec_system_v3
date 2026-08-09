<script setup>
import { computed } from "vue";
import { Terminal, Target, Trophy, Building, Users, Code, Laptop } from "@lucide/vue";

const props = defineProps({
  pageData: {
    type: Object,
    default: () => ({}),
  }
});
</script>

<template>
  <section class="grid gap-16 lg:gap-24 pb-16">
    <!-- Bento Box: Mission & Vision -->
    <div class="grid lg:grid-cols-3 gap-6">
       <div class="lg:col-span-2 relative overflow-hidden rounded-[2.5rem] bg-gradient-to-br from-[#1A66FF] to-[#0A1D3A] p-10 sm:p-14 text-white shadow-2xl">
          <div class="absolute top-0 right-0 p-12 opacity-10 pointer-events-none">
             <Terminal class="w-64 h-64 rotate-12" />
          </div>
          <p class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-sm font-bold text-white uppercase tracking-widest mb-6 border border-white/20 backdrop-blur-md">
             <Target class="w-4 h-4" /> Our Mission
          </p>
          <h2 class="text-4xl sm:text-5xl font-black leading-[1.1] mb-6">Engineering the future of <span class="text-[#FFB800] bg-clip-text text-transparent bg-gradient-to-r from-[#FFB800] to-yellow-300">IT professionals.</span></h2>
          <p class="text-xl text-blue-100/90 leading-relaxed max-w-2xl font-medium">
             At ETEC Center, we don't just teach code. We immerse you in real-world technology environments, building the critical problem-solving skills required by top-tier tech companies.
          </p>
       </div>
       
       <div class="grid gap-6">
          <div class="rounded-[2.5rem] bg-white p-8 border border-slate-100 shadow-[0_20px_40px_-15px_rgba(0,0,0,0.05)] flex flex-col justify-center relative overflow-hidden group">
             <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-emerald-50 rounded-full transition-transform group-hover:scale-150 z-0"></div>
             <div class="relative z-10 flex items-center justify-between mb-4">
                <h3 class="text-xl font-black text-slate-900">100% Practical</h3>
                <div class="w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                   <Code class="w-6 h-6" />
                </div>
             </div>
             <p class="text-slate-600 font-medium relative z-10">We believe in learning by doing. Zero crowded lecture halls, purely hands-on programming.</p>
          </div>
          
          <div class="rounded-[2.5rem] bg-white p-8 border border-slate-100 shadow-[0_20px_40px_-15px_rgba(0,0,0,0.05)] flex flex-col justify-center relative overflow-hidden group">
             <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-amber-50 rounded-full transition-transform group-hover:scale-150 z-0"></div>
             <div class="relative z-10 flex items-center justify-between mb-4">
                <h3 class="text-xl font-black text-slate-900">Industry Standard</h3>
                <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center text-amber-600">
                   <Trophy class="w-6 h-6" />
                </div>
             </div>
             <p class="text-slate-600 font-medium relative z-10">Our curriculum is directly mapped to the exact requirements of leading tech employers.</p>
          </div>
       </div>
    </div>

    <!-- By the Numbers -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
       <div class="rounded-[2.5rem] border border-slate-100 bg-white p-8 text-center shadow-sm transition hover:border-[#1A66FF]/20 hover:shadow-xl">
          <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-[#E8F0FF] text-[#1A66FF]">
             <Users class="h-7 w-7" />
          </div>
          <div class="text-4xl font-black text-slate-900 mb-1">5,000+</div>
          <div class="text-sm font-bold text-slate-500 uppercase tracking-wider">Happy Students</div>
       </div>
       <div class="rounded-[2.5rem] border border-slate-100 bg-white p-8 text-center shadow-sm transition hover:border-[#FFB800]/20 hover:shadow-xl">
          <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-[#FFF5EB] text-[#FFB800]">
             <Laptop class="h-7 w-7" />
          </div>
          <div class="text-4xl font-black text-slate-900 mb-1">50+</div>
          <div class="text-sm font-bold text-slate-500 uppercase tracking-wider">Expert Courses</div>
       </div>
       <div class="rounded-[2.5rem] border border-slate-100 bg-white p-8 text-center shadow-sm transition hover:border-emerald-500/20 hover:shadow-xl">
          <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500">
             <Building class="h-7 w-7" />
          </div>
          <div class="text-4xl font-black text-slate-900 mb-1">100+</div>
          <div class="text-sm font-bold text-slate-500 uppercase tracking-wider">Tech Partners</div>
       </div>
       <div class="rounded-[2.5rem] border border-slate-100 bg-white p-8 text-center shadow-sm transition hover:border-purple-500/20 hover:shadow-xl">
          <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-purple-50 text-purple-500">
             <Trophy class="h-7 w-7" />
          </div>
          <div class="text-4xl font-black text-slate-900 mb-1">98%</div>
          <div class="text-sm font-bold text-slate-500 uppercase tracking-wider">Hiring Rate</div>
       </div>
    </div>

    <!-- Campus / Content generated from Editor if exists -->
    <div v-if="pageData.content" class="rounded-[2.5rem] border border-slate-100 bg-white p-10 sm:p-16 shadow-[0_10px_40px_-10px_rgba(0,0,0,0.05)] relative overflow-hidden">
       <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-bl from-[#1A66FF]/5 to-transparent rounded-full pointer-events-none blur-3xl"></div>
       <h2 class="text-3xl font-black text-slate-900 mb-2">Our Campus & Infrastructure</h2>
       <p class="text-slate-500 font-medium mb-10 text-lg max-w-2xl">A deeper look at the modern facilities and learning environment our students train in daily.</p>
       
       <div class="rich-content text-lg max-w-4xl leading-relaxed text-slate-600 relative z-10" v-html="pageData.content"></div>
    </div>
  </section>
</template>
