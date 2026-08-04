<script setup>
import { computed, ref, watch } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import axios from "axios";
import { Search, Heart, Check, BookOpen, Users } from "@lucide/vue";
import FrontendFooter from "@/components/frontend/FrontendFooter.vue";
import AboutLayout from "@/components/frontend/pages/AboutLayout.vue";
import ContactLayout from "@/components/frontend/pages/ContactLayout.vue";

const props = defineProps({
  pageData: {
    type: Object,
    required: true,
  },
  preview: {
    type: Boolean,
    default: false,
  },
  courses: {
    type: [Array, Object],
    default: () => ({
      data: [],
      meta: null,
    }),
  },
  courseFilters: {
    type: Object,
    default: () => ({
      categories: [],
    }),
  },
  activeCourseFilters: {
    type: Object,
    default: () => ({}),
  },
});

const inertiaPage = usePage();
const website = computed(() => inertiaPage.props.website ?? {});
const settings = computed(() => website.value.settings ?? {});
const menus = computed(() => website.value.menus ?? []);
const courseItems = ref(Array.isArray(props.courses) ? props.courses : props.courses?.data ?? []);
const courseMeta = ref(Array.isArray(props.courses) ? null : props.courses?.meta ?? null);
const courseSearch = ref(props.activeCourseFilters.search ?? "");
const selectedCategory = ref(props.activeCourseFilters.category ?? "");
const selectedSubCategory = ref(props.activeCourseFilters.sub_category ?? "");
const filteringCourses = ref(false);
const loadingMoreCourses = ref(false);

const schoolName = computed(() => settings.value.school_name || "ETEC Center");
const hero = computed(() => props.pageData?.hero ?? null);
const activeSlug = computed(() => props.pageData?.slug ?? "");
const isCoursePage = computed(() => ["course", "courses"].includes(activeSlug.value));
const fallbackHero = "https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&w=1800&q=80";
let courseFilterTimer = null;
let courseRequestSequence = 0;

const alignmentClass = computed(() => ({
  left: "items-start text-left",
  center: "items-center text-center",
  right: "items-end text-right",
})[hero.value?.text_alignment ?? "center"]);

const hasMoreCourses = computed(() => {
  if (!courseMeta.value) {
    return false;
  }

  return Number(courseMeta.value.current_page) < Number(courseMeta.value.last_page);
});
const categoryOptions = computed(() => props.courseFilters?.categories ?? []);
const selectedCategoryData = computed(() =>
  categoryOptions.value.find((category) => category.name === selectedCategory.value) ?? null
);
const allSubCategoryOptions = computed(() => {
  const subCategories = new Map();

  categoryOptions.value.forEach((category) => {
    (category.sub_categories ?? []).forEach((subCategory) => {
      subCategories.set(subCategory.slug || subCategory.id, subCategory);
    });
  });

  return Array.from(subCategories.values());
});
const subCategoryOptions = computed(() => selectedCategoryData.value?.sub_categories ?? allSubCategoryOptions.value);
const activeCourseQuery = computed(() => ({
  search: courseSearch.value || undefined,
  category: selectedCategory.value || undefined,
  sub_category: selectedSubCategory.value || undefined,
}));

const fetchCourses = async (page = 1, append = false) => {
  const requestId = ++courseRequestSequence;

  filteringCourses.value = !append;
  loadingMoreCourses.value = append;

  try {
    const response = await axios.get(`/${activeSlug.value}/load-more`, {
      params: {
        ...activeCourseQuery.value,
        page,
      },
    });

    if (requestId !== courseRequestSequence) {
      return;
    }

    courseItems.value = append
      ? [...courseItems.value, ...(response.data?.data ?? [])]
      : response.data?.data ?? [];
    courseMeta.value = response.data?.meta ?? courseMeta.value;
  } finally {
    if (requestId === courseRequestSequence) {
      filteringCourses.value = false;
      loadingMoreCourses.value = false;
    }
  }
};

const loadMoreCourses = async () => {
  if (loadingMoreCourses.value || !hasMoreCourses.value) {
    return;
  }

  await fetchCourses(Number(courseMeta.value.current_page) + 1, true);
};

const resetCourseFilters = () => {
  courseSearch.value = "";
  selectedCategory.value = "";
  selectedSubCategory.value = "";
};

watch(selectedCategory, () => {
  if (!selectedSubCategory.value) {
    return;
  }

  const selectedSubCategoryStillVisible = subCategoryOptions.value.some(
    (subCategory) => subCategory.slug === selectedSubCategory.value || subCategory.name === selectedSubCategory.value
  );

  if (!selectedSubCategoryStillVisible) {
    selectedSubCategory.value = "";
  }
});

watch([courseSearch, selectedCategory, selectedSubCategory], () => {
  if (!isCoursePage.value) {
    return;
  }

  if (courseFilterTimer) {
    window.clearTimeout(courseFilterTimer);
  }

  courseFilterTimer = window.setTimeout(() => {
    fetchCourses(1);
  }, 400);
});
</script>

<template>
  <div class="min-h-screen bg-[#F4F7FA] font-sans selection:bg-[#1A66FF]/20 selection:text-[#1A66FF] [--public-header-height:5.5rem]">
    <div v-if="preview" class="bg-amber-100 px-4 py-2 text-center text-sm font-semibold text-amber-900">Preview mode</div>

    <section class="relative min-h-screen bg-[#0A1D3A] bg-cover bg-center" :style="{ backgroundImage: `url(${hero?.background_image_url || fallbackHero})` }">
      <div class="absolute inset-0 bg-gradient-to-r from-[#0A1D3A]/90 to-[#0A1D3A]/50"></div>
      <div class="absolute inset-0 bg-gradient-to-tr from-[#1A66FF]/20 to-[#FFB800]/10 mix-blend-overlay"></div>
      
      <div class="relative z-10 flex min-h-screen w-full mx-auto max-w-7xl items-center px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-2xl py-24 sm:py-32">
          <p class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-slate-900/30 px-4 py-1.5 text-sm font-bold text-white shadow-xl backdrop-blur-md">
            {{ activeSlug || 'Page' }}
          </p>
          <h1 class="mt-6 text-4xl font-black text-white sm:text-6xl">{{ pageData.title }}</h1>
        </div>
      </div>
    </section>
    <main class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
      <section v-if="activeSlug === 'about'" class="grid gap-16 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
        <div class="relative">
          <div class="absolute -inset-4 bg-gradient-to-tr from-[#1A66FF] to-[#FFB800] rounded-[2.5rem] opacity-20 blur-2xl z-0"></div>
          <img src="https://images.unsplash.com/photo-1577896851231-70ef18881754?auto=format&fit=crop&w=1200&q=80" alt="School classroom" class="relative z-10 w-full rounded-[2.5rem] object-cover h-[400px] lg:h-[500px] shadow-2xl border-4 border-white/50" />
        </div>
        <div>
          <p class="inline-flex items-center gap-2 rounded-full bg-[#E8F0FF] px-4 py-1.5 text-sm font-bold text-[#1A66FF] mb-6 w-fit mb-4">
            <span class="w-2 h-2 rounded-full bg-[#1A66FF]"></span>
            About our school
          </p>
          <h2 class="text-4xl font-black text-slate-900 sm:text-5xl leading-tight mb-6">Custom Vue content for this route</h2>
          <div v-if="pageData.content" class="rich-content mt-5 text-lg leading-relaxed text-slate-600" v-html="pageData.content"></div>
          <p v-else class="mt-5 text-lg leading-relaxed text-slate-600">This page is ready for dashboard-managed content.</p>
        </div>
      </section>

      <section v-else-if="isCoursePage" class="space-y-12">
        <div class="rounded-[2rem] border border-slate-100 bg-white p-6 shadow-[0_10px_40px_-10px_rgba(0,0,0,0.05)] sm:p-8">
          <div class="grid gap-6 lg:grid-cols-[1fr_220px_220px_auto] lg:items-end">
            <label class="grid gap-3 text-sm font-bold text-slate-900">
              Search courses
              <span class="relative">
                <Search class="pointer-events-none absolute left-5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                <input v-model="courseSearch" type="search" class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-4 pl-12 pr-4 text-base font-medium outline-none transition focus:border-[#1A66FF] focus:bg-white focus:ring-4 focus:ring-[#1A66FF]/10 placeholder:font-normal" placeholder="Name, slug, or description" />
              </span>
            </label>

            <label class="grid gap-3 text-sm font-bold text-slate-900">
              Category
              <select v-model="selectedCategory" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-base font-medium outline-none transition focus:border-[#1A66FF] focus:bg-white focus:ring-4 focus:ring-[#1A66FF]/10">
                <option value="">All categories</option>
                <option v-for="category in categoryOptions" :key="category.id" :value="category.name">{{ category.name }}</option>
              </select>
            </label>

            <label class="grid gap-3 text-sm font-bold text-slate-900">
              Sub Category
              <select v-model="selectedSubCategory" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-base font-medium outline-none transition focus:border-[#1A66FF] focus:bg-white focus:ring-4 focus:ring-[#1A66FF]/10">
                <option value="">All sub categories</option>
                <option v-for="subCategory in subCategoryOptions" :key="subCategory.id" :value="subCategory.slug">{{ subCategory.name }}</option>
              </select>
            </label>

            <button type="button" class="rounded-2xl border border-slate-200 bg-white px-8 py-4 text-base font-bold text-slate-700 transition hover:border-[#1A66FF] hover:text-[#1A66FF] shadow-sm hover:shadow-md" @click="resetCourseFilters">
              Reset
            </button>
          </div>
        </div>

        <div v-if="courseItems.length" class="relative">
          <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3" :class="{ 'pointer-events-none opacity-60': filteringCourses }" :aria-busy="filteringCourses">
            <div v-for="course in courseItems" :key="course.id" class="course-wrapper group block relative h-full hover:z-[60] focus-within:z-[60]">
               <!-- Main Course Card -->
               <article class="flex flex-col h-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition-all duration-300 md:group-hover:shadow-xl relative cursor-pointer">
                 <div class="relative w-full h-48 sm:h-52 overflow-hidden bg-slate-100">
                   <img v-if="course.thumbnail_url" :src="course.thumbnail_url" :alt="course.title" class="h-full w-full object-cover transition-transform duration-500 md:group-hover:scale-105" />
                   <div v-else class="grid h-full w-full place-items-center bg-slate-200 text-slate-400">
                     <span class="text-4xl font-black opacity-30">{{ course.title?.charAt(0) ?? "C" }}</span>
                   </div>
                 </div>

                 <div class="flex flex-col flex-1 p-5 sm:p-6">
                   <!-- Author / Brand -->
                   <div class="flex items-center gap-2.5 mb-3">
                     <div class="w-6 h-6 rounded border border-slate-200 p-0.5 overflow-hidden flex items-center justify-center shrink-0">
                        <img v-if="settings.logo_url" :src="settings.logo_url" class="h-full w-full object-contain" />
                        <span v-else class="text-[10px] font-black text-slate-400">ET</span>
                     </div>
                     <span class="text-sm font-medium text-slate-500">{{ schoolName }}</span>
                   </div>

                   <!-- Title -->
                   <h3 class="text-lg font-bold text-slate-900 leading-tight mb-2 line-clamp-2 md:group-hover:text-[#1A66FF] transition-colors">
                     {{ course.title }}
                   </h3>
                   
                   <div class="mt-auto">
                      <!-- Divider -->
                      <div class="h-px w-full bg-slate-200 my-4"></div>
                      
                      <!-- Footer Stats & Price -->
                      <div class="flex items-center justify-between">
                         <div class="flex items-center gap-4 text-sm font-medium text-slate-500">
                            <span class="flex items-center gap-1.5"><BookOpen class="w-4 h-4" /> 23</span>
                            <span class="flex items-center gap-1.5"><Users class="w-4 h-4" /> 50</span>
                         </div>
                         <div class="flex items-center gap-2 text-sm">
                            <span class="text-slate-400 line-through">$130.00</span>
                            <span class="font-bold text-[#1A66FF] text-base">$86.00</span>
                         </div>
                      </div>
                   </div>
                 </div>
               </article>

               <!-- Advanced Hover Popover -->
               <div class="course-popover absolute top-0 w-80 bg-white rounded-xl p-8 shadow-[0_30px_60px_-15px_rgba(0,0,0,0.2)] border border-slate-100 opacity-0 invisible transition-all duration-300 md:group-hover:opacity-100 md:group-hover:visible z-50 pointer-events-none md:group-hover:pointer-events-auto">
                  <div class="popover-arrow absolute top-24 w-4 h-4 bg-white border-l border-t border-slate-100 rotate-[-45deg]"></div>
                  
                  <div class="flex items-center gap-2.5 mb-5">
                     <div class="w-6 h-6 rounded border border-slate-200 p-0.5 overflow-hidden flex items-center justify-center shrink-0">
                        <img v-if="settings.logo_url" :src="settings.logo_url" class="h-full w-full object-contain" />
                        <span v-else class="text-[10px] font-black text-slate-400">ET</span>
                     </div>
                     <span class="text-sm font-medium text-slate-500">{{ schoolName }}</span>
                  </div>

                  <h4 class="text-xl font-bold text-[#0A1D3A] mb-4 leading-tight">{{ course.title }}</h4>
                  <p class="text-sm text-slate-500 mb-6 leading-relaxed">{{ course.description || 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa.' }}</p>
                  
                  <ul class="text-sm text-slate-600 space-y-3 mb-8">
                     <li class="flex items-start gap-2.5">
                        <Check class="w-4 h-4 text-slate-600 shrink-0 mt-0.5" stroke-width="1.5" />
                        <span class="leading-relaxed">Eita quad ex, rhonchus vitae lectors in, digicam pharetra ipsum.</span>
                     </li>
                     <li class="flex items-start gap-2.5">
                        <Check class="w-4 h-4 text-slate-600 shrink-0 mt-0.5" stroke-width="1.5" />
                        <span class="leading-relaxed">Magmas dis parturient mantes</span>
                     </li>
                     <li class="flex items-start gap-2.5">
                        <Check class="w-4 h-4 text-slate-600 shrink-0 mt-0.5" stroke-width="1.5" />
                        <span class="leading-relaxed">Morbi critique lorem sit a met arco utricles tempus.</span>
                     </li>
                  </ul>

                  <div class="flex gap-3">
                     <Link href="/register" class="flex-1 inline-flex items-center justify-center rounded-lg bg-[#1A66FF] text-white py-3 text-sm font-bold shadow-sm hover:bg-blue-700 transition-colors">
                        Add To Cart
                     </Link>
                     <button class="shrink-0 flex items-center justify-center h-12 w-12 rounded-full border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-red-500 transition-colors">
                        <Heart class="w-5 h-5" stroke-width="1.5" />
                     </button>
                  </div>
               </div>
            </div>
          </div>

          <div v-if="filteringCourses" class="pointer-events-none absolute inset-x-0 top-0 flex justify-center z-20">
            <span class="rounded-full border border-slate-100 bg-white px-6 py-3 text-sm font-black text-[#1A66FF] shadow-xl animate-pulse">Updating courses...</span>
          </div>
        </div>

        <div v-else-if="filteringCourses" class="rounded-[2rem] border border-slate-100 bg-white p-12 text-center text-lg font-bold text-slate-600 shadow-sm animate-pulse">Loading courses...</div>

        <div v-if="!filteringCourses && courseItems.length && hasMoreCourses" class="flex justify-center pt-8">
          <button type="button" class="rounded-full bg-[#FFB800] px-10 py-4 text-base font-black text-slate-900 shadow-[0_10px_30px_rgba(255,184,0,0.3)] transition hover:-translate-y-1 hover:bg-[#ffc833] disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:translate-y-0" :disabled="loadingMoreCourses" @click="loadMoreCourses">
            {{ loadingMoreCourses ? "Loading..." : "Load More Courses" }}
          </button>
        </div>

        <div v-if="!filteringCourses && !courseItems.length" class="rounded-[2rem] border-2 border-dashed border-slate-200 bg-white p-16 text-center shadow-sm">
          <Search class="mx-auto h-12 w-12 text-slate-300 mb-4" />
          <p class="text-xl font-black text-slate-700">No courses found.</p>
          <p class="mt-2 text-base text-slate-500">Try changing the search or category filters.</p>
        </div>
      </section>

      <!-- Customized Premium ABOUT Page Layout -->
      <section v-else-if="activeSlug === 'about'">
         <AboutLayout :page-data="pageData" />
      </section>

      <!-- Customized Premium CONTACT Page Layout -->
      <section v-else-if="activeSlug === 'contact'">
         <ContactLayout :page-data="pageData" />
      </section>

      <!-- Generic Page Layout -->
      <article v-else class="rounded-[2.5rem] border border-slate-100 bg-white p-10 sm:p-16 shadow-[0_10px_40px_-10px_rgba(0,0,0,0.05)]">
        <p class="inline-flex items-center gap-2 rounded-full bg-[#FFF5EB] px-4 py-1.5 text-sm font-bold text-[#FFB800] uppercase tracking-widest mb-4">
           {{ activeSlug || 'Page' }}
        </p>
        <h2 class="mt-2 text-4xl font-black text-slate-900 sm:text-5xl leading-tight mb-8">{{ pageData.title }}</h2>
        <div v-if="pageData.content" class="rich-content text-lg max-w-4xl leading-relaxed text-slate-600" v-html="pageData.content"></div>
        <p v-else class="text-lg max-w-3xl leading-relaxed text-slate-600">This page is ready for dashboard-managed content.</p>
      </article>
    </main>
    <FrontendFooter :settings="settings" :menus="menus" />
  </div>
</template>

<style scoped>
.course-card-title {
  display: -webkit-box;
  overflow: hidden;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}
</style>

<style scoped>
.rich-content :deep(img) {
  height: auto;
  max-width: 100%;
  border-radius: 1rem;
}

.rich-content :deep(table) {
  display: block;
  max-width: 100%;
  overflow-x: auto;
}

.rich-content :deep(a) {
  color: #1e5aa8;
  font-weight: 800;
}

/* 
 * Course Popover Smart Placement
 * Desktop: 3 columns. Items 1,2 point Right -> Right side popover. Item 3 points Left -> Left side popover.
 * Tablet: 2 columns. Item 1 points Right. Item 2 points Left.
 * Mobile: Hidden. 
 */
@media (max-width: 639px) {
   .course-popover {
      display: none !important;
   }
}

@media (min-width: 640px) and (max-width: 1023px) {
   .course-popover {
      left: calc(100% + 1rem); 
      margin-left: 0;
   }
   .popover-arrow {
      left: -0.5rem;
      border-right: none;
      border-bottom: none;
   }
   
   .course-wrapper:nth-child(2n) .course-popover {
      left: auto;
      right: calc(100% + 1rem);
   }
   .course-wrapper:nth-child(2n) .popover-arrow {
      left: auto;
      right: -0.5rem;
      border-left: none;
      border-top: none;
      border-right: 1px solid #f1f5f9;
      border-bottom: 1px solid #f1f5f9;
   }
}

@media (min-width: 1024px) {
   .course-popover {
      left: calc(100% + 1rem); 
      margin-left: 0;
   }
   .popover-arrow {
      left: -0.5rem;
      border-right: none;
      border-bottom: none;
   }
   
   .course-wrapper:nth-child(3n) .course-popover {
      left: auto;
      right: calc(100% + 1rem);
   }
   .course-wrapper:nth-child(3n) .popover-arrow {
      left: auto;
      right: -0.5rem;
      border-left: none;
      border-top: none;
      border-right: 1px solid #f1f5f9;
      border-bottom: 1px solid #f1f5f9;
   }
}
</style>
