<?php

use App\Modules\EnRoll\Controllers\EnRollController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
// middleware('auth')

// Route::middleware('auth')->prefix('/dashboard/students')->group(function(){  

Route::prefix('/dashboard/students')->group(function () {
    Route::get('/', function () {
        return Inertia::render('backend/students/ClassList');
    })->name('students.index');
    Route::get('/create', function () {
        return Inertia::render('backend/students/CreateClass');
    })->name('students.create');
    Route::get('/view/{id}', function ($id) {

        $classes = [
            [
                'id' => 366,
                'title' => 'Web Design + React.js',
                'lesson' => 'Bootstrap',
                'building' => 'Building B',
                'floor' => 'Floor 1',
                'room' => 'ETEC B102',
                'status' => 'Physical Class',
                'term' => 'Mon & Thu',
                'time' => '09:00 am - 10:30 am',
                'teacher' => 'Mr. Socheat',
                'students' => 8,
                'capacity' => 20,
                'notifications' => 5,
            ],
            [
                'id' => 367,
                'title' => 'Laravel 12',
                'lesson' => 'Authentication',
                'building' => 'Building A',
                'floor' => 'Floor 2',
                'room' => 'ETEC A203',
                'status' => 'Physical Class',
                'term' => 'Tue & Fri',
                'time' => '10:45 am - 12:15 pm',
                'teacher' => 'Ms. Maly',
                'students' => 15,
                'capacity' => 25,
                'notifications' => 2,
            ],
        ];

        $class = collect($classes)->firstWhere('id', (int)$id);

        $students = [
            [
                'id' => 1,
                'name' => 'Sok Dara',
                'gender' => 'Male',
                'phone' => '012 345 678',
                'deposit_amount' => 150.00,
                'payment_date' => '2025-01-15',
                'payment_status' => 'Paid',
                'remaining_balance' => 0.00,
            ],
            [
                'id' => 2,
                'name' => 'Chan Thida',
                'gender' => 'Female',
                'phone' => '098 765 432',
                'deposit_amount' => 75.00,
                'payment_date' => '2025-01-20',
                'payment_status' => 'Partial',
                'remaining_balance' => 75.00,
            ],
            [
                'id' => 3,
                'name' => 'Vannak Rithy',
                'gender' => 'Male',
                'phone' => '015 987 654',
                'deposit_amount' => 0.00,
                'payment_date' => null,
                'payment_status' => 'Unpaid',
                'remaining_balance' => 150.00,
            ],
            [
                'id' => 4,
                'name' => 'Srey Pich',
                'gender' => 'Female',
                'phone' => '077 123 456',
                'deposit_amount' => 150.00,
                'payment_date' => '2025-01-18',
                'payment_status' => 'Paid',
                'remaining_balance' => 0.00,
            ],
            [
                'id' => 5,
                'name' => 'Heng Kimleng',
                'gender' => 'Male',
                'phone' => '069 258 147',
                'deposit_amount' => 50.00,
                'payment_date' => '2025-02-01',
                'payment_status' => 'Partial',
                'remaining_balance' => 100.00,
            ],
        ];

        $depositSummary = [
            'total_students' => 5,
            'paid_students' => 2,
            'partial_students' => 2,
            'unpaid_students' => 1,
            'total_deposit_collected' => 425.00,
        ];

        return Inertia::render('backend/students/ViewClass', [
            'classData' => $class,
            'students' => $students,
            'depositSummary' => $depositSummary,
        ]);
    })->name('students.show');

    Route::get('/edit/{id}', function ($id) {

        $classes = [
            [
                'id' => 366,
                'title' => 'Web Design + React.js',
                'lesson' => 'Bootstrap',
                'building' => 'Building B',
                'floor' => 'Floor 1',
                'room' => 'ETEC B102',
                'status' => 'Physical Class',
                'term' => 'Mon & Thu',
                'time' => '09:00 am - 10:30 am',
                'capacity' => 20,
                'price' => 150,
                'startEnroll' => '2025-01-01',
                'startDate' => '2025-01-15',
                'description' => 'React Course',
            ],
        ];

        $class = collect($classes)->firstWhere('id', (int) $id);

        abort_if(!$class, 404);

        return Inertia::render('backend/students/EditClass', [
            'classData' => $class,
        ]);
    })->name('students.edit');
});
