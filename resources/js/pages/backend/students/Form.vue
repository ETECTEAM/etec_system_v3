<script setup>
import { ref, onMounted } from "vue";

const loading = ref(true);
const allowed = ref(false);
const error = ref("");
const distanceText = ref("");

const name = ref("");
const course = ref("");
// const time = ref("");
const props = defineProps({
  times: {
    type: Array,
    default: () => [],
  },
});

const selectedTime = ref("");

// ETEC Center
const locationArea = {
  name: "ETEC Center",
  lat: 11.562203,
  lng: 104.8905442,
  radius: 3000,
};

function calculateDistance(lat1, lon1, lat2, lon2) {
  const R = 6371000;

  const dLat = ((lat2 - lat1) * Math.PI) / 180;
  const dLon = ((lon2 - lon1) * Math.PI) / 180;

  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos((lat1 * Math.PI) / 180) *
      Math.cos((lat2 * Math.PI) / 180) *
      Math.sin(dLon / 2) *
      Math.sin(dLon / 2);

  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

  return R * c;
}

function checkLocation() {
  if (!navigator.geolocation) {
    error.value = "Browser does not support location";
    loading.value = false;
    return;
  }

  navigator.geolocation.getCurrentPosition(
    (position) => {
      const userLat = position.coords.latitude;
      const userLng = position.coords.longitude;

      const distance = calculateDistance(
        userLat,
        userLng,
        locationArea.lat,
        locationArea.lng
      );

      distanceText.value = Math.round(distance) + " meters";

      console.log("User Lat:", userLat);
      console.log("User Lng:", userLng);
      console.log("Distance:", distance);

      if (distance <= locationArea.radius) {
        allowed.value = true;
        error.value = "";
      } else {
        allowed.value = false;
        error.value = "You are outside ETEC area";
      }

      loading.value = false;
    },

    (err) => {
      console.log(err);

      error.value = "Please allow location access";

      loading.value = false;
    },

    {
      enableHighAccuracy: true,
      timeout: 15000,
      maximumAge: 0,
    }
  );
}

function submitForm() {
  if (!name.value || !course.value || !time.value) {
    alert("Please fill all fields");
    return;
  }

  alert(
    `Registration Success

Name: ${name.value}
Course: ${course.value}
Time: ${time.value}`
  );

  console.log({
    name: name.value,
    course: course.value,
    time: time.value,
  });

  name.value = "";
  course.value = "";
  time.value = "";
}

onMounted(() => {
  console.log("NEW VERSION 2026");
  checkLocation();
});
</script>

<template>
  <div class="min-h-screen bg-slate-100 flex items-center justify-center p-5">
    <div class="bg-white rounded-3xl shadow-xl p-8 w-full max-w-lg">
      <!-- Loading -->

      <div v-if="loading" class="text-center">
        <h1 class="text-3xl font-bold mb-4">Checking Location...</h1>

        <p>Please wait...</p>
      </div>

      <!-- Form -->

      <div v-else-if="allowed">
        <h1 class="text-3xl font-bold text-blue-600 text-center mb-6">
          Student Registration
        </h1>

        <form @submit.prevent="submitForm" class="space-y-5">
          <div>
            <label class="block mb-2 font-semibold"> Student Name </label>

            <input
              v-model="name"
              type="text"
              placeholder="Enter Name"
              class="w-full border rounded-xl p-4"
            />
          </div>

          <div>
            <label class="block mb-2 font-semibold"> Course </label>

            <select v-model="course" class="w-full border rounded-xl p-4">
              <option value="">Select Course</option>

              <option value="Vue JS">Vue JS</option>

              <option value="Laravel">Laravel</option>

              <option value="PHP">PHP</option>

              <option value="JavaScript">JavaScript</option>
            </select>
          </div>

          <div>
            <label class="block mb-2 font-semibold text-gray-700">
              Time Schedule
            </label>

            <select
              v-model="selectedTime"
              class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-blue-500 focus:outline-none"
            >
              <option value="">Select Time</option>

              <option
                v-for="time in props.times"
                :key="time.id"
                :value="time.id"
              >
                {{ time.time_name }}
              </option>
            </select>
          </div>

          <button
            type="submit"
            class="w-full bg-blue-600 text-white rounded-xl p-4"
          >
            Submit Registration
          </button>
        </form>
      </div>

      <!-- Denied -->

      <div v-else>
        <h1 class="text-3xl font-bold text-red-600 text-center mb-4">
          Access Denied
        </h1>

        <p class="text-center mb-5">
          {{ error }}
        </p>

        <div class="bg-slate-100 rounded-xl p-4 mb-5">
          Distance:
          {{ distanceText }}
        </div>

        <button
          @click="checkLocation"
          class="w-full bg-black text-white rounded-xl p-4"
        >
          Retry Location
        </button>
      </div>
    </div>
  </div>
</template> 