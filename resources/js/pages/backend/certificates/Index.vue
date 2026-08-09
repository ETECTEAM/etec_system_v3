<script setup>
import { computed, reactive } from 'vue'
import { Head } from '@inertiajs/vue3'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'

const certificate = reactive({
    recipient: 'IM RANY',
    course: 'Web Design + React.js',
    grantedDate: 'June 15, 2026',
    certificateId: '2080001 ETEC',
    director: 'Mr. HENG PHEAKNA',
})

const issueStatement = computed(() =>
    `has successfully completed all requirements for completion of the ${certificate.course} training courses in.`
)

function printCertificate() {
    window.print()
}
</script>

<template>
    <Head :title="$t('Certificates')" />

    <DashboardLayout>
        <section class="certificate-page mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
            <header class="no-print flex flex-col gap-4 rounded-2xl bg-gradient-to-r from-indigo-950 via-indigo-900 to-blue-800 p-5 text-white shadow-lg sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-indigo-200">{{ $t('ETEC documents') }}</p>
                    <h1 class="mt-1 text-2xl font-bold">{{ $t('Certificate of Completion') }}</h1>
                    <p class="mt-1 text-sm text-indigo-100">{{ $t('Check the details, then print a clean certificate.') }}</p>
                </div>
                <button type="button" class="rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-indigo-900 shadow transition hover:bg-indigo-50" @click="printCertificate">
                    Print certificate
                </button>
            </header>

            <div class="no-print grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-2 xl:grid-cols-3 dark:border-gray-800 dark:bg-gray-900">
                <label class="certificate-field">{{ $t('Recipient name') }}<input v-model="certificate.recipient" type="text" /></label>
                <label class="certificate-field">{{ $t('Course name') }}<input v-model="certificate.course" type="text" /></label>
                <label class="certificate-field">{{ $t('Granted date') }}<input v-model="certificate.grantedDate" type="text" /></label>
                <label class="certificate-field">{{ $t('Certificate ID') }}<input v-model="certificate.certificateId" type="text" /></label>
                <label class="certificate-field">{{ $t('Director') }}<input v-model="certificate.director" type="text" /></label>
            </div>

            <div class="certificate-preview-wrap overflow-auto rounded-2xl bg-slate-200 p-3 shadow-inner sm:p-6 dark:bg-gray-800">
                <article class="certificate-sheet" :aria-label="$t('Certificate preview')">
                    <div class="certificate-inner">
                        <header class="certificate-government">
                            <p>{{ $t('KINGDOM OF CAMBODIA') }}</p>
                            <p>{{ $t('NATION &nbsp; RELIGION &nbsp; KING') }}</p>
                            <div class="ornament" aria-hidden="true">{{ $t('&mdash; * * * &mdash;') }}</div>
                        </header>

                        <div class="etec-mark" :aria-label="$t('ETEC Center logo')">
                            <span class="etec-e">{{ $t('E') }}</span><span class="etec-s">{{ $t('S') }}</span><i></i>
                            <small>{{ $t('ETEC Center') }}</small>
                        </div>

                        <div class="certificate-organization">
                            <p class="khmer-title">វិស្វកម្មបច្ចេកវិទ្យា និងអេឡិចត្រូនិក</p>
                            <p>{{ $t('Engineering of Technology and Electronic Center') }}</p>
                        </div>

                        <h2>{{ $t('Certificate of Completion') }}</h2>
                        <p class="certificate-intro">{{ $t('This is to certify that') }}</p>
                        <p class="certificate-recipient">{{ certificate.recipient || '---' }}</p>
                        <p class="certificate-statement">{{ issueStatement }}</p>
                        <p class="certificate-course">{{ certificate.course || '---' }}</p>
                        <p class="certificate-granted">Granted: {{ certificate.grantedDate || '---' }}</p>

                        <footer class="certificate-footer">
                            <p class="certificate-id">ID: {{ certificate.certificateId || '---' }}</p>
                            <div class="signature">
                                <div class="signature-line"></div>
                                <p>{{ certificate.director || '---' }}</p>
                                <strong>{{ $t('Director') }}</strong>
                            </div>
                        </footer>
                    </div>
                </article>
            </div>
        </section>
    </DashboardLayout>
</template>

<style scoped>
.certificate-field { display: flex; flex-direction: column; gap: 4px; color: #64748b; font-size: 12px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; }
.certificate-field input { border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; color: #1e293b; font-size: 14px; font-weight: 500; letter-spacing: normal; text-transform: none; outline: none; transition: border-color .15s ease, box-shadow .15s ease; }
.certificate-field input:focus { border-color: #6366f1; box-shadow: 0 0 0 2px #e0e7ff; }
.certificate-sheet { width: 216mm; min-height: 279mm; margin: 0 auto; background: #fff; padding: 5.5mm; color: #050864; }
.certificate-inner { position: relative; min-height: 268mm; box-sizing: border-box; border: 4mm solid #37338e; outline: 2.5mm solid #a5a5a5; outline-offset: -6.5mm; padding: 12mm 18mm 8mm; text-align: center; }
.certificate-government { font-family: 'Courier New', monospace; font-size: 11px; font-weight: 700; letter-spacing: .4px; line-height: 1.45; }
.certificate-government p { margin: 0; }
.ornament { margin-top: 5px; color: #161616; font-size: 13px; letter-spacing: -1px; }
.etec-mark { position: relative; display: flex; align-items: center; justify-content: center; width: 52mm; height: 52mm; margin: 13mm auto 5mm; overflow: hidden; border-radius: 8mm; background: #37338e; color: #fff; font-family: Arial, sans-serif; }
.etec-mark::before { position: absolute; width: 39mm; height: 39mm; border: 1.3mm solid #f2d21f; border-right-color: transparent; border-radius: 50%; content: ''; }
.etec-mark::after { position: absolute; width: 27mm; height: 19mm; border: 1.3mm solid #c9ccdb; border-radius: 2mm; content: ''; }
.etec-mark span { z-index: 1; font-size: 28mm; font-weight: 300; line-height: 1; }
.etec-mark .etec-e { margin-right: -4mm; color: #f3d31c; font-weight: 200; }
.etec-mark .etec-s { color: #d1d4e0; font-weight: 200; }
.etec-mark i { position: absolute; z-index: 2; right: 7mm; top: 16mm; width: 2mm; height: 2mm; border-radius: 50%; background: #fff; box-shadow: 0 7mm 0 #fff, -3mm 13mm 0 #fff; }
.etec-mark small { position: absolute; z-index: 3; bottom: 6mm; font-size: 7px; font-weight: 400; letter-spacing: .5px; }
.certificate-organization { font-family: Arial, 'Siemreap', sans-serif; font-size: 12px; font-weight: 700; }
.certificate-organization p { margin: 2px 0; }
.khmer-title { font-family: 'Siemreap', sans-serif; font-size: 18px; }
h2 { margin: 15mm 0 6mm; color: #111; font-family: 'Old English Text MT', 'Times New Roman', serif; font-size: 29px; font-weight: 700; }
.certificate-intro { margin: 0; font-family: 'Courier New', monospace; font-size: 16px; letter-spacing: 2px; }
.certificate-recipient { margin: 9mm 0 7mm; color: #080808; font-family: Arial, sans-serif; font-size: 23px; font-weight: 800; text-transform: uppercase; }
.certificate-statement { max-width: 145mm; margin: 0 auto; font-family: 'Times New Roman', serif; font-size: 14px; line-height: 1.45; }
.certificate-course { margin: 7mm 0 8mm; color: #070707; font-family: 'Courier New', monospace; font-size: 18px; font-weight: 700; }
.certificate-granted { margin: 0; font-family: 'Courier New', monospace; font-size: 14px; font-weight: 700; }
.certificate-footer { position: absolute; right: 18mm; bottom: 6mm; left: 18mm; display: flex; align-items: end; justify-content: space-between; }
.certificate-id { margin: 0 0 1mm 32mm; font-family: 'Courier New', monospace; font-size: 8px; font-weight: 700; color: #ae1010; }
.signature { width: 56mm; color: #050505; font-family: 'Courier New', monospace; font-size: 12px; font-weight: 700; }
.signature p { margin: 2mm 0 1mm; white-space: nowrap; }
.signature strong { font-family: Arial, sans-serif; font-size: 13px; }
.signature-line { border-top: 1px solid #111; }
@media (max-width: 900px) { .certificate-preview-wrap { margin-inline: -1rem; border-radius: 0; } }
@page { size: letter portrait; margin: 0; }
@media print { body { background: #fff !important; } .no-print { display: none !important; } .certificate-page { display: block; padding: 0 !important; } .certificate-preview-wrap { overflow: visible; padding: 0; background: #fff; box-shadow: none; } .certificate-sheet { box-shadow: none; } }
</style>
