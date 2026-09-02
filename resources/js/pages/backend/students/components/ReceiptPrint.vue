<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import QrcodeVue from "qrcode.vue";
import logoSrc from "@/assets/etecLogoBase64.js";

const props = defineProps({
  classData: {
    type: Object,
    default: null,
  },
  student: {
    type: Object,
    default: null,
  },
});

const page = usePage();

const settings = computed(() => page.props.website?.settings ?? {});
const schoolName = computed(
  () => settings.value.school_name || "Engineering of Technology and Electronic Center"
);
const today = computed(() => new Date().toISOString().slice(0, 10));

const receiptNo = computed(() => {
  if (!props.student?.enrollment_id) return "ETEC";
  return `ETEC${String(props.student.enrollment_id).padStart(6, "0")}`;
});

// Public attendance summary the family reaches by scanning the printed QR -
// only present once an enrollment exists (parked pre-registrations have no id).
const attendanceUrl = computed(() => {
  const enrollmentId = props.student?.enrollment_id;
  return enrollmentId ? `${window.location.origin}/student-attendance/${enrollmentId}` : "";
});

const copies = [
  { key: "school", title: "School Copy", khTitle: "ច្បាប់សាលា" },
  { key: "student", title: "Student Copy", khTitle: "ច្បាប់សិស្ស" },
];

const dayAbbreviations = {
  Monday: "Mon",
  Tuesday: "Tue",
  Wednesday: "Wed",
  Thursday: "Thu",
  Friday: "Fri",
  Saturday: "Sat",
  Sunday: "Sun",
};

function money(value) {
  return `$${Number(value ?? 0).toFixed(2)}`;
}

function riel(value) {
  return `${Math.round(Number(value ?? 0)).toLocaleString("en-US")}៛`;
}

function valueOrDash(value) {
  return value === null || value === undefined || value === "" ? "-" : value;
}

function formatGender(value) {
  const gender = valueOrDash(value);
  if (gender === "-") return gender;

  const normalized = String(gender).toLowerCase();
  if (normalized === "male") return "Male";
  if (normalized === "female") return "Female";

  return gender;
}

function rowAmountValue() {
  return totalAmount();
}

function totalAmount() {
  return courseFeeAmount() + documentFeeAmount();
}

// The charged Course Price (may be a discount off the unit price).
function courseFeeAmount() {
  return Number(props.classData?.resolved_price ?? props.classData?.price ?? props.student?.fee_amount ?? props.classData?.course_price ?? 0);
}

// The Enroll Config list / unit price. Snapshotted onto the student/enrollment
// at registration; falls back to the charged fee when no snapshot exists
// (receipts printed before this was tracked).
function unitPriceAmount() {
  const unit = Number(props.classData?.unit_price ?? props.student?.unit_price ?? 0);
  return unit > 0 ? unit : courseFeeAmount();
}

function discountAmount() {
  return Math.max(unitPriceAmount() - courseFeeAmount(), 0);
}

function documentFeeAmount() {
  return Number(props.classData?.document_price ?? props.student?.document_fee_amount ?? 0);
}

function paidAmount() {
  return Number(props.student?.amount_paid ?? props.student?.deposit_amount ?? 0);
}

function remainingAmount() {
  return Math.max(rowAmountValue() - paidAmount(), 0);
}

function rowAmount() {
  return money(rowAmountValue());
}

function khmerRateAmount() {
  return riel(rowAmountValue() * 4000);
}

function abbreviateDays(value) {
  return Object.entries(dayAbbreviations).reduce(
    (text, [day, shortDay]) => text.replace(new RegExp(`\\b${day}\\b`, "g"), shortDay),
    String(value ?? "")
  );
}

function documentFeeLabel() {
  const amount = documentFeeAmount();
  return amount > 0 ? `Document ${money(amount)}` : "Document Free";
}

function timeWithTerm() {
  const time = abbreviateDays(valueOrDash(props.classData?.time));
  const term = abbreviateDays(valueOrDash(props.classData?.term));

  if (time === "-" && term === "-") return "-";
  if (time === "-") return term;
  if (term === "-") return time;

  return `${time} (${term})`;
}
</script>

<template>
  <div v-if="student" id="receipt-print-area" class="receipt-print-area">
    <section v-for="copy in copies" :key="copy.key" class="receipt-copy">
      <header class="receipt-header">
        <div class="receipt-left">
          <div class="receipt-logo" aria-hidden="true">
            <img :src="logoSrc" :alt="schoolName" />
          </div>
          <p>Since 2012</p>
        </div>

        <div class="receipt-title">
          <p class="kh-title">មជ្ឈមណ្ឌលវិស្វកម្មបច្ចេកវិទ្យា និង អេឡិចត្រូនិច</p>
          <h1>Engineering Technology Electronic Center</h1>
          <h1>គុណភាព សមត្ថភាព ជំនាញ</h1>
        </div>
      </header>

      <div class="receipt-rule">
        <span></span>
      </div>

      <div class="receipt-kicker">
        <div class="receipt-heading">
          <p class="kh-subtitle">វិក្កយបត្រ / បង្កាន់ដៃបង់ប្រាក់</p>
          <h2>Receipt</h2>
        </div>

        <div class="receipt-meta">
          <p>លេខកូដ/ID: {{ receiptNo }}</p>
        </div>
      </div>

      <div class="receipt-body">
        <div class="receipt-row">
          <span class="label">ឈ្មោះ / Name</span>
          <strong class="line">{{ valueOrDash(student.name) }}</strong>
          <span class="label tiny">ភេទ / Gender</span>
          <strong class="line time-term">{{ formatGender(student.gender) }}</strong>
        </div>
        <div class="receipt-row">
          <span class="label">វគ្គសិក្សា / Course</span>
          <strong class="line">{{ valueOrDash(classData?.course) }}</strong>
          <span class="label tiny">ម៉ោងសិក្សា</span>
          <strong class="line time-term">{{ timeWithTerm() }}</strong>
        </div>
        <div class="receipt-row">
          <span class="label">តម្លៃឯកតា / Unit Price</span>
          <strong class="line time-term">{{ money(unitPriceAmount()) }}</strong>
          <span class="label tiny">កាលបរិច្ឆេទចូលរៀន</span>
          <strong class="line time-term">{{ valueOrDash(classData?.enroll_start_date) }}</strong>
        </div>
        <div class="receipt-row">
          <span class="label">តម្លៃវគ្គ / Course Price</span>
          <strong class="line time-term">{{ money(courseFeeAmount()) }}<span v-if="discountAmount() > 0"> (- {{ money(discountAmount()) }})</span></strong>
          <span class="label tiny">ប្រាក់ត្រូវបង់</span>
          <strong class="line payment-line">
            <span>{{ rowAmount() }} /{{ khmerRateAmount() }} &emsp;&emsp;{{ documentFeeLabel() }}</span>
          </strong>
        </div>
        <div class="note">
          <div class="note-left">
            <span>***ប្រាក់ដែលបានបង់រួច មិនអាចដកវិញបានទេ/None refundable***</span>

            <div class="class-info">
              <p class="class-info-title">ព័ត៌មានថ្នាក់រៀន / Class Info</p>
              <div class="class-info-grid">
                <div class="class-info-item">
                  <span class="label tiny">គ្រូបង្រៀន / Instructor:</span>
                  <strong>{{ valueOrDash(classData?.teacher) }}</strong>
                </div>
                <div class="class-info-item">
                  <span class="label tiny">អាគារ / Building:</span>
                  <strong>{{ valueOrDash(classData?.building) }}</strong>
                </div>
                <div class="class-info-item">
                  <span class="label tiny">ជាន់ / Floor:</span>
                  <strong>{{ valueOrDash(classData?.floor) }}</strong>
                </div>
                <div class="class-info-item">
                  <span class="label tiny">បន្ទប់ / Room:</span>
                  <strong>{{ valueOrDash(classData?.room) }}</strong>
                </div>
              </div>
            </div>
          </div>

          <div class="note-right">
             <div class="date">
              <span class="label tiny">កាលបរិច្ឆេទ / Date :</span>
              <div class="date-value">
                <strong class="line">{{ valueOrDash(student.payment_date ?? today) }}</strong>
                <strong class="cashier">បេឡា/Cashier</strong>
              </div>
             </div>

             <div v-if="attendanceUrl" class="receipt-qr">
               <QrcodeVue :value="attendanceUrl" :size="85" level="M" render-as="svg" />
               <span>ស្កេនមើលវត្តមាន</span>
             </div>

             <strong class="sigature">គ្រូអាយធីចិត្តល្អ</strong>
          </div>
        </div>
      </div>


      <footer class="receipt-contact">
        <span>អាសយដ្ឋាន៖ ភ្នំពេញថ្មី រាជធានីភ្នំពេញ</span>
        <span>HP: +855 96 226 8884 / +855 77 368 884</span>
        <span>Email: info@eteccenter.info</span>
        <span>Website: www.eteccenter.info</span>
      </footer>
    </section>
  </div>
</template>

<style scoped>
.receipt-print-area {
  display: none;
}

@media print {
  @page {
    size: A4 portrait;
    margin: 0;
  }

  /* Force a clean white canvas even when the dashboard is in dark mode:
     <html>'s color-scheme: dark otherwise tints the printed page grey, and
     app.css's universal color transition animates <body> to white, which
     reads as the whole screen "flashing" to light in the print preview. */
  :global(html) {
    color-scheme: light;
    background: #fff;
  }

  :global(html *),
  :global(html *::before),
  :global(html *::after) {
    transition: none !important;
  }

  :global(body *) {
    visibility: hidden;
  }

  :global(body) {
    margin: 0;
    background: #fff;
    color-scheme: light;
    overflow: hidden;
  }

  :global(#app) {
    width: 210mm;
    height: 297mm;
    overflow: hidden;
  }

  .receipt-print-area,
  .receipt-print-area * {
    visibility: visible;
  }

  .receipt-print-area {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    width: 210mm;
    height: 297mm;
    overflow: hidden;
    background: #fff;
    color: #1f2933;
    font-family: "Khmer OS Battambang", "Noto Sans Khmer", Arial, sans-serif;
    print-color-adjust: exact !important;
    -webkit-print-color-adjust: exact !important;
  }

  .receipt-copy {
    position: relative;
    display: flex;
    box-sizing: border-box;
    height: 148.5mm;
    flex: 0 0 148.5mm;
    flex-direction: column;
    overflow: hidden;
    padding: 7mm 10mm 0;
    border-bottom: 1px dashed #9ca3af;
    break-inside: auto;
    page-break-inside: auto;
  }

  .receipt-copy:last-child {
    border-bottom: 0;
  }

  .receipt-header {
    display: grid;
    grid-template-columns: 33mm 1fr 33mm;
    align-items: end;
    gap: 4mm;
    min-height: 23mm;
  }

  .receipt-left {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: end;
  }

  .receipt-left p {
    margin: 1mm 0 0;
    color: #374151;
    font-family: Arial, sans-serif;
    font-size: 9pt;
    font-weight: 800;
    line-height: 1;
    text-align: center;
    writing-mode: horizontal-tb;
  }

  .receipt-logo {
    display: flex;
    width: 17mm;
    height: 17mm;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border: 0;
    border-radius: 3.5mm;
    color: #1e3a8a;
    font-family: Arial, sans-serif;
    font-size: 11pt;
    font-weight: 800;
  }

  .receipt-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
  }

  .receipt-title {
    align-self: end;
    padding-top: 0;
    text-align: center;
    margin-bottom: 0.5mm;
  }

  .receipt-title h1 {
    margin: 1mm 0 0;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 12pt;
    font-weight: 700;
    line-height: 1.4;
  }

  .receipt-title p {
    margin: 0;
  }

  .kh-title {
    font-size: 15pt;
    font-weight: 700;
    line-height: 1.4;
    white-space: nowrap;
  }

  .copy-label {
    font-size: 9.5pt;
  }

  .receipt-rule {
    display: grid;
    grid-template-columns: 1fr;
    margin: 5mm 0 0;
    border-top: 0.55mm solid #374151;
  }

  .receipt-rule span {
    display: block;
    margin-top: 0.8mm;
    /* border-top: 0.25mm solid #9ca3af; */
  }

  .receipt-kicker {
    position: relative;
    min-height: 12mm;
    margin-bottom: 1.5mm;
  }

  .receipt-heading {
    position: absolute;
    left: 50%;
    top: -0.2mm;
    width: 70mm;
    transform: translateX(-50%);
    text-align: center;
  }

  .kh-subtitle,
  .receipt-heading h2 {
    margin: 0;
    font-size: 11.5pt;
    font-weight: 700;
    line-height: 1.15;
    margin-top: 5px;
  }

  .receipt-meta {
    position: absolute;
    right: 0;
    top: 1.1mm;
    color: #111827;
    font-size: 8.8pt;
    font-weight: 700;
    text-align: right;
    white-space: nowrap;
  }

  .receipt-meta p {
    margin: 0;
  }

  .receipt-body {
    position: relative;
    z-index: 1;
    margin-top: 0;
  }

  .receipt-row {
    display: flex;
    align-items: end;
    min-height: 7mm;
    gap: 2mm;
    font-size: 10pt;
  }

  .label {
    flex: 0 0 30mm;
    color: #111827;
    font-weight: 700;
    line-height: 4.8mm;
    white-space: nowrap;
  }

  .label.tiny {
    flex-basis: 30mm;
    margin-left: 4mm;
  }

  .line {
    display: block;
    flex: 1 1 auto;
    min-width: 24mm;
    min-height: 4.2mm;
    border-bottom: 0.35mm dotted #6b7280;
    font-family: Arial, "Noto Sans Khmer", sans-serif;
    font-size: 10pt;
    font-weight: 700;
    line-height: 4.2mm;
    text-align: start;
  }

  /* Fixed (not flex-sized) so every row's right-hand column is identical -
     otherwise each value sizes to its own content, the flexible .line before
     it absorbs a different amount, and the .label.tiny beside it starts at a
     different x on each row. */
  .line.time-term {
    flex: 0 0 48mm;
    max-width: 48mm;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }

  .line.wide {
    min-width: 116mm;
  }

  .fee-breakdown {
    font-size: 8.8pt;
    white-space: nowrap;
  }

  .payment-line {
    display: flex;
    min-width: 0;
    align-items: end;
    justify-content: space-between;
    gap: 2mm;
    overflow: visible;
    font-size: 8.8pt;
    white-space: nowrap;
  }

  .payment-line span {
    display: inline-block;
    flex: 0 1 auto;
  }

  .note {
    display: flex;
    min-height: 20mm;
    align-items: start;
    justify-content: space-between;
    gap: 6mm;
    margin-top: 3mm;
    padding-top: 1mm;
    border-top: 0.50mm solid #3d4048;
    padding-bottom: 2mm;
  }

  .note-left {
    display: flex;
    min-width: 0;
    flex: 1 1 auto;
    flex-direction: column;
    gap: 2mm;
  }

  .note-left > span {
    font-size: 8pt;
    font-weight: 700;
    white-space: nowrap;
    opacity: 0.85;
  }

  .note > div {
    flex: 0 0 58mm;
  }

  /* Right column: date on top, signature pinned to the bottom (margin-top:auto)
     so the QR, when present, sits in the gap between them instead of being
     crammed under Class Info on the left. */
  .note-right {
    display: flex;
    min-height: 40mm;
    flex-direction: column;
    align-items: center;
    gap: 2mm;
  }

  .note-right .date {
    align-self: stretch;
  }

  .date {
    display: flex;
    align-items: baseline;
    /* gap: 0.8mm; */
  }

  .date-value {
    min-width: 0;
    flex: 0 1 auto;
  }

  .date .label.tiny {
    flex-basis: auto;
    margin-left: 0;
    font-size: 8.5pt;
  }

  .date .line {
    display: block;
    flex: 0 0 auto;
    min-width: 0;
    margin-top: 0;
    border-bottom: 0;
    font-size: 8.5pt;
    font-weight: 600;
    line-height: 1.3;
    text-align: center;
    white-space: nowrap;
  }

  .cashier {
    display: block;
    margin-top: 0.5mm;
    font-size: 8.5pt;
    font-weight: 600;
    text-align: center;
  }

  .sigature {
    display: block;
    margin-top: auto;
    padding-top: 6mm;
    font-size: 10pt;
    font-weight: 700;
    text-align: center;
  }

  .class-info {
    box-sizing: border-box;
    width: 100%;
    padding: 0;
  }

  .class-info-title {
    margin: 0 0 1.5mm;
    font-size: 8pt;
    font-weight: 700;
    letter-spacing: 0.2mm;
    text-transform: uppercase;
    opacity: 0.75;
  }

  .class-info-grid {
    display: grid;
    grid-template-columns: 1fr;
    /* gap: 1mm; */
  }

  .class-info-item {
    display: flex;
    align-items: baseline;
    gap: 1.5mm;
    font-size: 8pt;
  }

  .class-info-item .label.tiny {
    flex: 0 0 auto;
    margin-left: 0;
    font-size: 7.5pt;
    font-weight: 700;
    opacity: 0.75;
  }

  .class-info-item strong {
    overflow: hidden;
    flex: 1 1 auto;
    min-width: 0;
    font-weight: 700;
    text-align: left;
    text-overflow: ellipsis;
    white-space: nowrap;
    /* line-height: 1; */
  }

  .receipt-qr {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1mm;
  }

  /* Keep the child SVG's modules visible (body * is hidden for print) and the
     black squares solid regardless of the browser's economy-ink setting. */
  .receipt-qr,
  .receipt-qr :deep(*) {
    visibility: visible;
    print-color-adjust: exact !important;
    -webkit-print-color-adjust: exact !important;
  }

  .receipt-qr :deep(svg) {
    width: 18mm;
    height: 18mm;
  }

  .receipt-qr span {
    font-size: 6pt;
    font-weight: 700;
    letter-spacing: 0.1mm;
    white-space: nowrap;
    opacity: 0.75;
  }

  .receipt-contact {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 3mm;
    margin: auto -10mm 0;
    min-height: 10mm;
    padding: 6mm 4mm;
    background: #000000bb;
    color: #f8fafc;
    font-size: 7.5pt;
    line-height: 1.25;
    text-align: center;
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
  }

  .receipt-contact span {
    white-space: nowrap;
  }
}
</style>
