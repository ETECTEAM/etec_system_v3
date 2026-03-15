// Sidebar menu configuration
// Used by Sidebar.vue to generate the dashboard menu
// Each section has roles allowed to see it

export default [

    // =============================
    // General menu (visible to all roles)
    // =============================
    {
        section: "General", // Section title
        roles: ["super_admin","admin","instructor"], // Allowed roles
        items: [
            {
                name: "Home",
                icon: null,        // icon placeholder
                route: "/"
            }
        ]
    },


    // =============================
    // Admin menu (admin + super_admin)
    // =============================
    {
        section: "Admin",
        roles: ["super_admin","admin"],
        items: [

            // =============================
            // Only super_admin menu
            // =============================
            {
                name: "Blacklisted Students",
                icon: null,
                route: "/blacklist-student",
                roles: ["super_admin"]
            },

            // =============================
            // Discount menu
            // =============================
            {
                name: "Discount Rule",
                icon: null,
                route: "/discount-rule"
            },

            // =============================
            // Building dropdown menu
            // =============================
            {
                name: "Building",
                icon: null,
                route: "/building"
            },

            // =============================
            // Class dropdown menu
            // =============================
            {
                name: "Class",
                icon: null,
                children: [
                {
                    name: "Class List",
                    route: "/class"
                },
                {
                    name: "Class Type",
                    route: "/class-type"
                },
                {
                    name: "Class Schedule",
                    route: "/class-schedule"
                }
                ]
            },

            // =============================
            // Student dropdown menu
            // =============================
            {
                name: "Student",
                icon: null,
                children: [
                    {
                        name: "Student List",
                        route: "/student-list"
                    },
                    {
                        name: "Student Enrollment",
                        route: "/student-enrollment"
                    },
                ]
            },

            // =============================
            // Permission dropdown menu
            // =============================
            {
                name: "Permission",
                icon: null,
                children: [
                    {
                        name: "Permission Rule",
                        route: "/permission-rule"
                    },
                    {
                        name: "Permission Record",
                        route: "/permission-record"
                    },
                ]
            },

            // =============================
            // Instructor dropdown menu
            // =============================
            {
                name: "Instructor",
                icon: null,
                children: [
                    {
                        name: "Instructor List",
                        route: "/instructor-list"
                    },
                    {
                        name: "Instructor Card",
                        route: "/instructor-card"
                    },
                ]
            },

            // =============================
            // Course dropdown menu
            // =============================
            {
                name: "Course",
                icon: null,
                children: [
                {
                    name: "Course",
                    route: "/course"
                },
                {
                    name: "Course Category",
                    route: "/course-category"
                }
                ]
            },

        ]
    },


    // =============================
    // Instructor menu
    // =============================
    {
        section: "Instructor",
        roles: ["instructor"],
        items: [

            {
                name: "Instructor Dashboard",
                icon: null,
                route: "/instructor-dashboard"
            },

            {
                name: "Class",
                icon: null,
                route: "/class"
            },

            {
                name: "Report",
                icon: null,
                route: "/report"
            }

        ]
    }

]
