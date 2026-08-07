import {
  Home,
  Bell,
  NotebookPen,
  UserPlus,
  Layers,
  BookOpen,
  Users,
  Building2,
  Globe,
  Clock,
  CalendarClock,
  ShieldCheck,
  Lock,
  UserCircle,
  LayoutDashboard,
} from "@lucide/vue";

// Maps a menu item's `icon` string to its Lucide component.
export const menuIcons = {
  home: Home,
  notification: Bell,
  "notebook-pen": NotebookPen,
  "user-plus": UserPlus,
  classes: Layers,
  course: BookOpen,
  user: Users,
  building_management: Building2,
  website: Globe,
  shift: Clock,
  schedule: CalendarClock,
  account_security: ShieldCheck,
  login_security: Lock,
  profile: UserCircle,
};

// Used when an item's `icon` key has no entry above.
export const defaultMenuIcon = LayoutDashboard;
