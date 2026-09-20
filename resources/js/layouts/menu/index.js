import * as accessLocation from "./accessLocation";
import * as studentManagement from "./studentManagement";
import * as building from "./building";
import * as classes from "./classes";
import * as classHistory from "./classHistory";
import * as enroll from "./enroll";
import * as certificate from "./certificate";
import * as course from "./course";
import * as profile from "./profile";
import * as attendance from "./attendance";
import * as user from "./user";
import * as instructorScheduleBlocks from "./instructorScheduleBlocks";
import * as instructorAvailability from "./instructorAvailability";
import * as schedule from "./schedule";
import * as loginSecurity from "./loginSecurity";
import * as otpSettings from "./otpSettings";
import * as holiday from "./holiday";
import * as officialLeave from "./officialLeave";
import * as absenceBlock from "./absenceBlock";
// import * as website from "./website";

// Display order. Each item's `section` (daily by default, setup, system) decides its heading in Sidebar.vue.
export const menuDomains = [
  // Daily work
  enroll,
  studentManagement,
  classes,
  attendance,
  officialLeave,
  classHistory,
  profile,
  instructorScheduleBlocks,
  // Setup
  course,
  schedule,
  building,
  holiday,
  instructorAvailability,
  certificate,
  // Rules & security
  absenceBlock,
  user,
  loginSecurity,
  otpSettings,
  accessLocation,
  // website,
];
