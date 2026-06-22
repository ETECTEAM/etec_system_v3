<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import UserAccountForm from './components/UserAccountForm.vue'
const roleOptions = usePage().props.roleOptions ?? []
const form = useForm({ name: '', email: '', password: '', password_confirmation: '', role: roleOptions[0] ?? 'admin', account_status: true, avatar: null, student_code: '', student_full_name: '', student_first_name: '', student_last_name: '', student_full_name_kh: '', student_gender: '', student_date_of_birth: '', student_phone: '', student_email: '', student_class_id: '', parent_name: '', parent_phone: '', student_address: '', student_status: true, instructor_code: '', instructor_full_name: '', instructor_first_name: '', instructor_last_name: '', instructor_full_name_kh: '', instructor_gender: '', instructor_date_of_birth: '', instructor_phone: '', instructor_email: '', specialization: '', employment_type: 'full_time', shift_preference: 'morning_evening', available_for_class: true, hire_date: '', instructor_address: '', instructor_status: true })
</script>
<template><Head title="Create User" /><DashboardLayout><section class="space-y-6"><Breadcrumbs :items="[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Users', href: '/dashboard/users' }, { label: 'Create', current: true }]" /><PageHero eyebrow="User Management" title="Create New User" description="Create an account and its role-specific profile." /><div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8"><UserAccountForm :form="form" :role-options="roleOptions" submit-label="Create User" @submit="form.post('/dashboard/users')" /></div></section></DashboardLayout></template>
