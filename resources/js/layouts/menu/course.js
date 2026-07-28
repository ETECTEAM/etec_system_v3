export const key = "course";

export function isRoute(path) {
  const p = path.split("?")[0];
  return (
    p.startsWith("/dashboard/course") ||
    p.startsWith("/dashboard/categories") ||
    p.startsWith("/dashboard/subcategories") ||
    p.startsWith("/dashboard/tracks") ||
    p.startsWith("/dashboard/courses") ||
    p.startsWith("/dashboard/lessons")
  );
}

export function build(ctx) {
  if (!ctx.isSuperAdmin) return null;

  return {
    label: "Course Management",
    labelKey: "navigation.courseManagement",
    key,
    match: ["/dashboard/course"],
    icon: "course",
    children: [
      {
        label: "Categories",
        labelKey: "navigation.categories",
        href: "/dashboard/course/categories",
        match: ["/dashboard/course/categories"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/course/categories"),
      },
      {
        label: "Sub Categories",
        labelKey: "navigation.subCategories",
        href: "/dashboard/course/subcategories",
        match: ["/dashboard/course/subcategories"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/course/subcategories"),
      },
      {
        label: "Tracks",
        labelKey: "navigation.tracks",
        href: "/dashboard/course/tracks",
        match: ["/dashboard/course/tracks"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/course/tracks"),
      },
      {
        label: "Courses",
        labelKey: "navigation.courses",
        href: "/dashboard/course/courses",
        match: ["/dashboard/course/courses"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/course/courses"),
      },
      {
        label: "Lessons",
        labelKey: "navigation.lessons",
        href: "/dashboard/course/lessons",
        match: ["/dashboard/course/lessons"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/course/lessons"),
      },
    ],
  };
}
