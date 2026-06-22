<template>
    <DashboardLayout>
        <div class="p-6">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Courses</h1>
                        <p class="text-gray-500 mt-1">Manage your course catalog</p>
                    </div>
                    <button 
                        @click="openCreateModal"
                        class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-5 py-2.5 rounded-xl font-medium hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-lg hover:shadow-xl flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add New Course
                    </button>
                </div>
            </div>

            <!-- Success Message -->
            <div v-if="flashSuccess" class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg shadow-sm animate-slideDown">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ flashSuccess }}
                </div>
            </div>

            <!-- Courses Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- Search Bar -->
                <div class="p-4 border-b border-gray-100">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input 
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search courses..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="course in filteredCourses" :key="course.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm text-gray-500">#{{ course.id }}</td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ course.title }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 max-w-md">
                                    <div class="truncate">{{ truncate(course.description, 60) }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(course.created_at) }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <button 
                                            @click="openEditModal(course)"
                                            class="text-blue-600 hover:text-blue-800 p-1.5 rounded-lg hover:bg-blue-50 transition-colors"
                                            title="Edit"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <button 
                                            @click="confirmDelete(course)"
                                            class="text-red-600 hover:text-red-800 p-1.5 rounded-lg hover:bg-red-50 transition-colors"
                                            title="Delete"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredCourses.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                    <p>No courses found</p>
                                    <button @click="openCreateModal" class="mt-3 text-blue-600 hover:text-blue-700 font-medium">
                                        Create your first course →
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                            Showing {{ courses.from || 0 }} to {{ courses.to || 0 }} of {{ courses.total || 0 }} results
                        </div>
                        <div class="flex gap-2">
                            <button 
                                v-for="page in courses.last_page" 
                                :key="page"
                                @click="fetchCourses(page)"
                                class="px-3 py-1.5 text-sm rounded-lg transition-colors"
                                :class="courses.current_page === page 
                                    ? 'bg-blue-600 text-white' 
                                    : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'"
                            >
                                {{ page }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal - Centered with light background -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen p-4">
                <!-- Light backdrop -->
                <div class="fixed inset-0 bg-white/50  transition-opacity" @click="closeModal"></div>

                <!-- Modal Panel - Centered -->
                <div class="relative bg-gradient-to-br from-white to-gray-50 rounded-2xl shadow-2xl max-w-md w-full transform transition-all animate-modalSlideIn border border-gray-100">
                    <!-- Modal Header -->
                    <div class="flex justify-between items-center p-6 border-b border-gray-100 bg-white/50 rounded-t-2xl">
                        <h3 class="text-xl font-bold text-gray-800">
                            {{ isEditing ? 'Edit Course' : 'Create New Course' }}
                        </h3>
                        <button 
                            @click="closeModal"
                            class="text-gray-400 hover:text-gray-600 transition-colors"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <form @submit.prevent="saveCourse" class="p-6 space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Course Title *</label>
                            <input 
                                v-model="form.title"
                                type="text"
                                required
                                class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                :class="{ 'border-red-500 ring-2 ring-red-200': form.errors.title }"
                                placeholder="Enter course title"
                            />
                            <div v-if="form.errors.title" class="text-red-500 text-sm mt-1">{{ form.errors.title }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                            <textarea 
                                v-model="form.description"
                                rows="5"
                                required
                                class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none"
                                :class="{ 'border-red-500 ring-2 ring-red-200': form.errors.description }"
                                placeholder="Enter course description"
                            ></textarea>
                            <div v-if="form.errors.description" class="text-red-500 text-sm mt-1">{{ form.errors.description }}</div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="flex gap-3 pt-4">
                            <button 
                                type="button"
                                @click="closeModal"
                                class="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-gray-700 font-medium hover:bg-gray-100 transition-colors"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit"
                                :disabled="form.processing"
                                class="flex-1 px-4 py-2.5 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl font-medium hover:from-blue-600 hover:to-blue-700 transition-all shadow-md disabled:opacity-50"
                            >
                                {{ form.processing ? 'Saving...' : (isEditing ? 'Update Course' : 'Create Course') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal - Centered with light background -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen p-4">
                <!-- Light backdrop -->
                <div class="fixed inset-0 bg-white/50 transition-opacity" @click="showDeleteModal = false"></div>
                
                <div class="relative bg-gradient-to-br from-white to-gray-50 rounded-2xl shadow-2xl max-w-sm w-full transform transition-all animate-modalSlideIn border border-gray-100">
                    <div class="p-6 text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800 mb-2">Delete Course</h3>
                        <p class="text-gray-500 mb-6">
                            Are you sure you want to delete "<span class="font-medium text-gray-700">{{ deleteCourseTitle }}</span>"? This action cannot be undone.
                        </p>
                        <div class="flex gap-3">
                            <button 
                                @click="showDeleteModal = false"
                                class="flex-1 px-4 py-2 border border-gray-200 rounded-xl text-gray-700 font-medium hover:bg-gray-100 transition-colors"
                            >
                                Cancel
                            </button>
                            <button 
                                @click="deleteCourse"
                                class="flex-1 px-4 py-2 bg-red-500 text-white rounded-xl font-medium hover:bg-red-600 transition-colors"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'

const page = usePage()

// Get courses directly from props (passed by Laravel controller)
const courses = ref(page.props.courses || { data: [], total: 0, from: 0, to: 0, last_page: 1, current_page: 1 })
const loading = ref(false)
const showModal = ref(false)
const showDeleteModal = ref(false)
const isEditing = ref(false)
const deleteCourseId = ref(null)
const deleteCourseTitle = ref('')
const searchQuery = ref('')

const flashSuccess = computed(() => page.props.flash?.success)

const form = useForm({
    id: null,
    title: '',
    description: ''
})

// Filter courses based on search
const filteredCourses = computed(() => {
    if (!searchQuery.value) return courses.value.data || []
    return courses.value.data.filter(course => 
        course.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        course.description.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
})

// Open create modal
const openCreateModal = () => {
    form.reset()
    form.clearErrors()
    isEditing.value = false
    form.id = null
    showModal.value = true
}

// Open edit modal
const openEditModal = (course) => {
    form.reset()
    form.clearErrors()
    isEditing.value = true
    form.id = course.id
    form.title = course.title
    form.description = course.description
    showModal.value = true
}

// Close modal
const closeModal = () => {
    showModal.value = false
    form.reset()
    form.clearErrors()
}

// Save course
const saveCourse = () => {
    if (isEditing.value) {
        form.put(`/admin/courses/${form.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                closeModal()
                // Refresh the page to get updated data
                window.location.reload()
            }
        })
    } else {
        form.post('/admin/courses', {
            preserveScroll: true,
            onSuccess: () => {
                closeModal()
                // Refresh the page to get updated data
                window.location.reload()
            }
        })
    }
}

// Confirm delete
const confirmDelete = (course) => {
    deleteCourseId.value = course.id
    deleteCourseTitle.value = course.title
    showDeleteModal.value = true
}

// Delete course
const deleteCourse = () => {
    router.delete(`/admin/courses/${deleteCourseId.value}`, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            window.location.reload()
        }
    })
}

// Helper functions
const truncate = (text, length) => {
    if (!text) return '—'
    return text.length > length ? text.substring(0, length) + '...' : text
}

const formatDate = (date) => {
    if (!date) return '—'
    return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

// No need for fetchCourses() - data is already in props
</script>