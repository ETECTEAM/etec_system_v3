<script setup>
import { computed, ref, watch } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import axios from "axios";
import { Mail, MapPin, Menu, Phone, Search, X } from "@lucide/vue";
import FrontendFooter from "@/components/frontend/FrontendFooter.vue";
import FrontendMenuLinks from "@/components/frontend/FrontendMenuLinks.vue";

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
const menuOpen = ref(false);
const courseItems = ref(Array.isArray(props.courses) ? props.courses : props.courses?.data ?? []);
const courseMeta = ref(Array.isArray(props.courses) ? null : props.courses?.meta ?? null);
const courseSearch = ref(props.activeCourseFilters.search ?? "");
const selectedCategory = ref(props.activeCourseFilters.category ?? "");
const selectedSubCategory = ref(props.activeCourseFilters.sub_category ?? "");
const filteringCourses = ref(false);
const loadingMoreCourses = ref(false);
const contactSubmitting = ref(false);
const contactSuccess = ref("");
const contactErrors = ref({});
const contactForm = ref({
  name: "",
  email: "",
  phone: "",
  subject: "",
  message: "",
});

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

const submitContact = async () => {
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
  <div class="min-h-screen bg-white text-slate-900 [--public-header-height:5rem]">
    <div v-if="preview" class="bg-amber-100 px-4 py-2 text-center text-sm font-semibold text-amber-900">Preview mode</div>

    <header class="fixed left-0 right-0 top-0 z-40 border-b border-slate-200/70 bg-white/90 shadow-sm backdrop-blur-xl">
      <div class="mx-auto flex min-h-20 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <Link href="/" class="flex min-w-0 items-center gap-3 rounded-full pr-2 transition hover:opacity-90">
          <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-[#25258f] text-xs font-black text-white shadow-sm">
            <img v-if="settings.logo_url" :src="settings.logo_url" :alt="schoolName" class="h-full w-full object-contain" />
            <span v-else>ETEC</span>
          </span>
          <span class="max-w-[12rem] truncate text-sm font-black leading-tight text-slate-950 sm:max-w-xs sm:text-base">{{ schoolName }}</span>
        </Link>

        <nav class="hidden items-center gap-1 lg:flex">
          <FrontendMenuLinks :menus="menus" :active-slug="activeSlug" :home-active="activeSlug === 'home'" />
        </nav>

        <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 lg:hidden" aria-label="Toggle menu" @click="menuOpen = !menuOpen">
          <X v-if="menuOpen" class="h-5 w-5" />
          <Menu v-else class="h-5 w-5" />
        </button>
      </div>

      <nav v-if="menuOpen" class="mx-4 mb-4 grid gap-1 rounded-2xl border border-slate-200 bg-white p-3 shadow-xl lg:hidden">
        <FrontendMenuLinks :menus="menus" :active-slug="activeSlug" :home-active="activeSlug === 'home'" mobile @navigate="menuOpen = false" />
      </nav>
    </header>

    <section class="relative mt-20 min-h-[400px] bg-slate-900 bg-cover bg-center sm:min-h-[500px] lg:min-h-[600px]" :style="{ backgroundImage: `url(${hero?.background_image_url || fallbackHero})` }">
      <div class="absolute inset-0 bg-gradient-to-r from-[#091f42]/85 to-[#091f42]/45"></div>
      <div class="relative mx-auto flex min-h-[400px] max-w-7xl flex-col justify-center px-4 py-14 text-white sm:min-h-[500px] sm:px-6 sm:py-20 lg:min-h-[600px] lg:px-8" :class="alignmentClass">
        <p class="mb-3 text-xs font-black uppercase tracking-[0.22em] text-[#f4a261] sm:text-sm">{{ hero?.subtitle || schoolName }}</p>
        <h1 class="max-w-4xl text-4xl font-black leading-[1.06] text-white sm:text-5xl lg:text-7xl">{{ hero?.title || pageData.title }}</h1>
        <p class="mt-5 max-w-2xl text-sm leading-7 text-blue-50 sm:text-lg sm:leading-8">{{ hero?.description || "This public route uses Vue content and dashboard-managed page settings." }}</p>
        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap" :class="{ 'justify-center': hero?.text_alignment === 'center', 'justify-end': hero?.text_alignment === 'right' }">
          <Link v-if="hero?.primary_button_text && hero?.primary_button_url" :href="hero.primary_button_url" class="inline-flex justify-center rounded-full bg-[#f4a261] px-6 py-3 text-sm font-black text-slate-950 shadow-lg shadow-[#f4a261]/20 transition hover:-translate-y-0.5 hover:bg-[#f7c948]">{{ hero.primary_button_text }}</Link>
          <Link v-if="hero?.secondary_button_text && hero?.secondary_button_url" :href="hero.secondary_button_url" class="inline-flex justify-center rounded-full bg-white px-6 py-3 text-sm font-black text-[#174981] shadow-lg transition hover:-translate-y-0.5 hover:bg-blue-50">{{ hero.secondary_button_text }}</Link>
        </div>
      </div>
    </section>

    <main class="mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-20 lg:px-8">
      <section v-if="activeSlug === 'about'" class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
        <img src="https://images.unsplash.com/photo-1577896851231-70ef18881754?auto=format&fit=crop&w=1200&q=80" alt="School classroom" class="h-full min-h-80 w-full rounded-3xl object-cover shadow-[0_18px_45px_rgba(30,90,168,0.16)] sm:min-h-96" />
        <div>
          <p class="text-sm font-bold uppercase tracking-widest text-[#f4a261]">About our school</p>
          <h2 class="mt-3 text-3xl font-black text-slate-950 sm:text-4xl">Custom Vue content for this route</h2>
          <div v-if="pageData.content" class="rich-content mt-5 leading-8 text-slate-600" v-html="pageData.content"></div>
          <p v-else class="mt-5 leading-8 text-slate-600">This page is ready for dashboard-managed content.</p>
        </div>
      </section>

      <section v-else-if="isCoursePage" class="space-y-8">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
          <div class="grid gap-4 lg:grid-cols-[1fr_220px_220px_auto] lg:items-end">
            <label class="grid gap-2 text-sm font-bold text-slate-700">
              Search courses
              <span class="relative">
                <Search class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                <input v-model="courseSearch" type="search" class="w-full rounded-xl border border-slate-300 py-3 pl-11 pr-4 text-sm font-normal outline-none transition focus:border-[#1e5aa8] focus:ring-4 focus:ring-[#1e5aa8]/10" placeholder="Name, slug, or description" />
              </span>
            </label>

            <label class="grid gap-2 text-sm font-bold text-slate-700">
              Category
              <select v-model="selectedCategory" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm font-normal outline-none transition focus:border-[#1e5aa8] focus:ring-4 focus:ring-[#1e5aa8]/10">
                <option value="">All categories</option>
                <option v-for="category in categoryOptions" :key="category.id" :value="category.name">{{ category.name }}</option>
              </select>
            </label>

            <label class="grid gap-2 text-sm font-bold text-slate-700">
              Sub Category
              <select v-model="selectedSubCategory" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm font-normal outline-none transition focus:border-[#1e5aa8] focus:ring-4 focus:ring-[#1e5aa8]/10">
                <option value="">All sub categories</option>
                <option v-for="subCategory in subCategoryOptions" :key="subCategory.id" :value="subCategory.slug">{{ subCategory.name }}</option>
              </select>
            </label>

            <button type="button" class="rounded-full border border-slate-300 px-5 py-3 text-sm font-black text-slate-700 transition hover:border-[#1e5aa8] hover:text-[#1e5aa8]" @click="resetCourseFilters">
              Reset
            </button>
          </div>
        </div>

        <div v-if="courseItems.length" class="relative">
          <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" :class="{ 'pointer-events-none opacity-60': filteringCourses }" :aria-busy="filteringCourses">
            <article v-for="course in courseItems" :key="course.id" class="group flex h-full overflow-hidden rounded-[18px] border border-slate-200/80 bg-white shadow-[0_10px_30px_rgba(15,23,42,0.08)] ring-1 ring-transparent transition duration-300 hover:-translate-y-1 hover:border-[#1e5aa8]/25 hover:shadow-[0_22px_55px_rgba(30,90,168,0.16)] hover:ring-[#1e5aa8]/10">
              <div class="flex min-h-full w-full flex-col">
                <div class="relative overflow-hidden bg-slate-100">
                  <img v-if="course.thumbnail_url" :src="course.thumbnail_url" :alt="course.title" class="aspect-[16/9] w-full object-cover transition duration-500 group-hover:scale-105" />
                  <div v-else class="grid aspect-[16/9] place-items-center bg-gradient-to-br from-slate-100 via-blue-50 to-orange-50 text-3xl font-black text-[#1e5aa8]">
                    {{ course.title?.charAt(0) ?? "C" }}
                  </div>
                  <div class="absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-slate-950/18 to-transparent"></div>
                </div>

                <div class="flex flex-1 flex-col p-6">
                  <div class="flex flex-wrap gap-2">
                    <span v-if="course.category || course.track" class="rounded-full bg-[#f4a261]/18 px-3 py-1 text-xs font-black text-[#8a4b12]">{{ course.category || course.track }}</span>
                    <span v-if="course.sub_category" class="rounded-full bg-[#1e5aa8]/10 px-3 py-1 text-xs font-bold text-[#174981]">{{ course.sub_category }}</span>
                  </div>

                  <h3 class="course-card-title mt-4 min-h-[3.5rem] text-xl font-black leading-7 text-slate-950">{{ course.title }}</h3>
                  <div class="mt-3 flex flex-wrap items-center gap-2 text-sm font-bold capitalize text-slate-500">
                    <span>{{ course.level || "Course" }}</span>
                    <span v-if="course.duration" class="h-1 w-1 rounded-full bg-slate-300"></span>
                    <span v-if="course.duration">{{ course.duration }}</span>
                  </div>

                  <div class="mt-6 flex flex-col gap-2 sm:flex-row">
                    <Link :href="`/courses/${course.slug}`" class="inline-flex flex-1 justify-center rounded-full bg-[#1e5aa8] px-5 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-[#174981]">View Details</Link>
                    <Link href="/register" class="inline-flex flex-1 justify-center rounded-full bg-[#f4a261] px-5 py-2.5 text-sm font-black text-slate-950 shadow-sm transition hover:bg-[#f7c948]">Register</Link>
                  </div>
                </div>
              </div>
            </article>
          </div>

          <div v-if="filteringCourses" class="pointer-events-none absolute inset-x-0 top-0 flex justify-center">
            <span class="rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-black text-[#1e5aa8] shadow-lg">Updating courses...</span>
          </div>
        </div>

        <div v-else-if="filteringCourses" class="rounded-2xl border border-slate-200 bg-slate-50 p-8 text-center font-bold text-slate-600">Loading courses...</div>

        <div v-if="!filteringCourses && courseItems.length && hasMoreCourses" class="flex justify-center pt-2">
          <button type="button" class="rounded-full bg-[#f4a261] px-6 py-3 text-sm font-black text-slate-950 shadow-[0_12px_28px_rgba(244,162,97,0.28)] transition hover:bg-[#f7c948] disabled:cursor-not-allowed disabled:opacity-70" :disabled="loadingMoreCourses" @click="loadMoreCourses">
            {{ loadingMoreCourses ? "Loading..." : "Load More" }}
          </button>
        </div>

        <div v-if="!filteringCourses && !courseItems.length" class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
          <p class="font-bold text-slate-700">No courses found.</p>
          <p class="mt-2 text-sm text-slate-500">Try changing the search or filters.</p>
        </div>
      </section>

      <section v-else-if="activeSlug === 'contact'" class="grid gap-8 lg:grid-cols-[0.8fr_1.2fr]">
        <div class="space-y-4">
          <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <Phone class="h-5 w-5 text-[#1e5aa8]" />
            <p class="mt-3 font-black text-slate-950">Contact the school</p>
            <p class="mt-2 leading-7 text-slate-600">Send your question through the form and the school team can follow up with current contact details.</p>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <Mail class="h-5 w-5 text-[#1e5aa8]" />
            <p class="mt-3 font-black text-slate-950">Admissions and course questions</p>
            <p class="mt-2 leading-7 text-slate-600">Use a clear subject so your message reaches the right team faster.</p>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <MapPin class="h-5 w-5 text-[#1e5aa8]" />
            <p class="mt-3 font-black text-slate-950">Visit information</p>
            <p class="mt-2 leading-7 text-slate-600">Address and opening hour fields can be displayed here when they are added to school settings.</p>
          </div>
        </div>
        <form class="rounded-2xl border border-slate-200 bg-slate-50 p-5 sm:p-6" @submit.prevent="submitContact">
          <h2 class="text-2xl font-black text-slate-950 sm:text-3xl">Contact Form</h2>
          <p v-if="contactSuccess" class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ contactSuccess }}</p>
          <p v-if="contactErrors.form?.[0]" class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ contactErrors.form[0] }}</p>
          <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <label class="grid gap-2 text-sm font-bold text-slate-700">
              Full name
              <input v-model="contactForm.name" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-normal" :class="{ 'border-red-300': contactErrors.name }" />
              <span v-if="contactErrors.name?.[0]" class="text-xs text-red-600">{{ contactErrors.name[0] }}</span>
            </label>
            <label class="grid gap-2 text-sm font-bold text-slate-700">
              Email
              <input v-model="contactForm.email" type="email" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-normal" :class="{ 'border-red-300': contactErrors.email }" />
              <span v-if="contactErrors.email?.[0]" class="text-xs text-red-600">{{ contactErrors.email[0] }}</span>
            </label>
            <label class="grid gap-2 text-sm font-bold text-slate-700">
              Phone
              <input v-model="contactForm.phone" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-normal" :class="{ 'border-red-300': contactErrors.phone }" />
              <span v-if="contactErrors.phone?.[0]" class="text-xs text-red-600">{{ contactErrors.phone[0] }}</span>
            </label>
            <label class="grid gap-2 text-sm font-bold text-slate-700">
              Subject
              <input v-model="contactForm.subject" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-normal" :class="{ 'border-red-300': contactErrors.subject }" />
              <span v-if="contactErrors.subject?.[0]" class="text-xs text-red-600">{{ contactErrors.subject[0] }}</span>
            </label>
            <label class="grid gap-2 text-sm font-bold text-slate-700 sm:col-span-2">
              Message
              <textarea v-model="contactForm.message" class="min-h-32 rounded-xl border border-slate-300 px-4 py-3 text-sm font-normal" :class="{ 'border-red-300': contactErrors.message }"></textarea>
              <span v-if="contactErrors.message?.[0]" class="text-xs text-red-600">{{ contactErrors.message[0] }}</span>
            </label>
            <button type="submit" class="rounded-full bg-[#1e5aa8] px-6 py-3 text-sm font-bold text-white transition hover:bg-[#174981] disabled:cursor-not-allowed disabled:opacity-70 sm:col-span-2" :disabled="contactSubmitting">
              {{ contactSubmitting ? "Sending..." : "Send Message" }}
            </button>
          </div>
        </form>
      </section>

      <article v-else class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 sm:p-8">
        <p class="text-sm font-bold uppercase tracking-widest text-[#f4a261]">{{ activeSlug || 'Page' }}</p>
        <h2 class="mt-3 text-3xl font-black text-slate-950 sm:text-4xl">{{ pageData.title }}</h2>
        <div v-if="pageData.content" class="rich-content mt-4 max-w-4xl leading-8 text-slate-600" v-html="pageData.content"></div>
        <p v-else class="mt-4 max-w-3xl leading-8 text-slate-600">This page is ready for dashboard-managed content.</p>
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
</style>
