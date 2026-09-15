import * as accessLocation from "./accessLocation";
import * as studentManagement from "./studentManagement";
import * as building from "./building";
import * as classes from "./classes";
import * as classHistory from "./classHistory";
import * as enroll from "./enroll";
import * as certificate from "./certificate";
import * as enrollConfig from "./enrollConfig";
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

export const menuDomains = [
  building,
  classes,
  classHistory,
  enroll,
  certificate,
  enrollConfig,
  course,
  profile,
  attendance,
  user,
  // website,
  instructorScheduleBlocks,
  instructorAvailability,
  schedule,
  loginSecurity,
  otpSettings,
  holiday,
  officialLeave,
  absenceBlock,
  accessLocation,
  studentManagement,
];
