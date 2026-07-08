<!-- resources/js/pages/backend/courses/Index.vue -->
<template>
    <div>
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 mb-6 dark:border-gray-800">
            <nav class="-mb-px flex space-x-8">
                <button @click="activeTab = 'categories'"
                        class="py-2 px-1 border-b-2 font-medium text-sm"
                        :class="activeTab === 'categories'
                            ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:border-gray-600'">
                    📁 Categories
                </button>
                <button @click="activeTab = 'subcategories'"
                        class="py-2 px-1 border-b-2 font-medium text-sm"
                        :class="activeTab === 'subcategories'
                            ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:border-gray-600'">
                    📂 Sub Categories
                </button>
                <button @click="activeTab = 'tracks'"
                        class="py-2 px-1 border-b-2 font-medium text-sm"
                        :class="activeTab === 'tracks'
                            ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:border-gray-600'">
                    📋 Tracks
                </button>
                <button @click="activeTab = 'courses'"
                        class="py-2 px-1 border-b-2 font-medium text-sm"
                        :class="activeTab === 'courses'
                            ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:border-gray-600'">
                    📖 Courses
                </button>
                <button @click="activeTab = 'lessons'"
                        class="py-2 px-1 border-b-2 font-medium text-sm"
                        :class="activeTab === 'lessons'
                            ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:border-gray-600'">
                    📝 Lessons
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div v-if="activeTab === 'categories'">
            <Categories :categories="categories" @openForm="openCategoryForm" />
        </div>
        <div v-else-if="activeTab === 'subcategories'">
            <SubCategories :subCategories="subCategories" :allCategories="allCategories" @openForm="openSubCategoryForm" />
        </div>
        <div v-else-if="activeTab === 'tracks'">
            <Tracks :tracks="tracks" :allSubCategories="allSubCategories" @openForm="openTrackForm" />
        </div>
        <div v-else-if="activeTab === 'courses'">
            <Courses :courses="courses" :allTracks="allTracks" @openForm="openCourseForm" />
        </div>
        <div v-else-if="activeTab === 'lessons'">
            <Lessons :lessons="lessons" :allCourses="allCourses" @openForm="openLessonForm" />
        </div>

        <!-- Popup Modal -->
        <div v-if="showPopup" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closePopup">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-black/70"></div>
                <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full mx-auto max-h-[90vh] overflow-y-auto dark:bg-gray-900">
                    <div class="flex items-center justify-between p-4 border-b sticky top-0 bg-white z-10 dark:border-gray-800 dark:bg-gray-900">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ popupTitle }}</h3>
                        <button @click="closePopup" class="text-gray-400 hover:text-gray-600 text-2xl dark:text-gray-500 dark:hover:text-gray-300">&times;</button>
                    </div>
                    <div class="p-6">
                        <component :is="popupComponent" 
                                   :popupData="popupData"
                                   @close="closePopup" 
                                   @saved="handleSaved" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Categories from './Categories.vue';
import SubCategories from './SubCategories.vue';
import Tracks from './Tracks.vue';
import Courses from './Courses.vue';
import Lessons from './Lessons.vue';
import CategoryForm from './CategoryForm.vue';
import SubCategoryForm from './SubCategoryForm.vue';
import TrackForm from './TrackForm.vue';
import CourseForm from './CourseForm.vue';
import LessonForm from './LessonForm.vue';

const props = defineProps({
    categories: Array,
    subCategories: Array,
    tracks: Array,
    courses: Array,
    lessons: Array,
    allCategories: Array,
    allSubCategories: Array,
    allTracks: Array,
    allCourses: Array,
    activeTab: String,
    showForm: String,
    editCategory: Object,
    editSubCategory: Object,
    editTrack: Object,
    editCourse: Object,
    editLesson: Object,
});

const activeTab = ref(props.activeTab || 'categories');
const showPopup = ref(false);
const popupComponent = ref(null);
const popupTitle = ref('');
const popupData = ref(null);

const openCategoryForm = (category = null) => {
    popupComponent.value = CategoryForm;
    popupTitle.value = category ? 'Edit Category' : 'Add Category';
    popupData.value = category;
    showPopup.value = true;
};

const openSubCategoryForm = (subCategory = null) => {
    popupComponent.value = SubCategoryForm;
    popupTitle.value = subCategory ? 'Edit Sub Category' : 'Add Sub Category';
    popupData.value = { subCategory, categories: props.allCategories };
    showPopup.value = true;
};

const openTrackForm = (track = null) => {
    popupComponent.value = TrackForm;
    popupTitle.value = track ? 'Edit Track' : 'Add Track';
    popupData.value = { track, subCategories: props.allSubCategories };
    showPopup.value = true;
};

const openCourseForm = (course = null) => {
    popupComponent.value = CourseForm;
    popupTitle.value = course ? 'Edit Course' : 'Add Course';
    popupData.value = { course, tracks: props.allTracks };
    showPopup.value = true;
};

const openLessonForm = (lesson = null) => {
    popupComponent.value = LessonForm;
    popupTitle.value = lesson ? 'Edit Lesson' : 'Add Lesson';
    popupData.value = { lesson, courses: props.allCourses };
    showPopup.value = true;
};

const closePopup = () => {
    showPopup.value = false;
    popupComponent.value = null;
    popupData.value = null;
};

const handleSaved = () => {
    closePopup();
    router.reload();
};
</script>
