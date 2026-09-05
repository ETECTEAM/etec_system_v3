<script setup>
import { computed } from 'vue'

const props = defineProps({
    certificate: {
        type: Object,
        required: true,
    },
})

const isInternship = computed(() => props.certificate.certificate_type === 'internship')
</script>

<template>
    <article
        v-if="isInternship"
        class="certificate-preview printable-certificate internship-certificate-preview"
    >
        <div class="internship-certificate">
            <!-- Corner circuit-trace decorations -->
            <svg class="internship-circuit-svg circuit-top" viewBox="0 0 330 48" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <polyline points="0,31 138,31 148,14 210,14" fill="none" stroke="#fff" stroke-width="2"/>
                <polyline points="0,43 156,43 167,23 252,23" fill="none" stroke="#fff" stroke-width="2"/>
                <g stroke="#fff" stroke-width="5">
                    <line x1="176" y1="43" x2="186" y2="23"/>
                    <line x1="187" y1="43" x2="197" y2="23"/>
                    <line x1="198" y1="43" x2="208" y2="23"/>
                    <line x1="209" y1="43" x2="219" y2="23"/>
                    <line x1="220" y1="43" x2="230" y2="23"/>
                    <line x1="231" y1="43" x2="241" y2="23"/>
                    <line x1="242" y1="43" x2="252" y2="23"/>
                </g>
                <polyline points="257,14 305,14" fill="none" stroke="#fff" stroke-width="2"/>
                <circle cx="0" cy="31" r="3" fill="#fff"/>
                <circle cx="210" cy="14" r="3.5" fill="#fff"/>
                <circle cx="305" cy="14" r="4" fill="#fff"/>
            </svg>
            <svg class="internship-circuit-svg circuit-bottom" viewBox="0 0 330 48" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <polyline points="0,31 138,31 148,14 210,14" fill="none" stroke="#fff" stroke-width="2"/>
                <polyline points="0,43 156,43 167,23 252,23" fill="none" stroke="#fff" stroke-width="2"/>
                <g stroke="#fff" stroke-width="5">
                    <line x1="176" y1="43" x2="186" y2="23"/>
                    <line x1="187" y1="43" x2="197" y2="23"/>
                    <line x1="198" y1="43" x2="208" y2="23"/>
                    <line x1="209" y1="43" x2="219" y2="23"/>
                    <line x1="220" y1="43" x2="230" y2="23"/>
                    <line x1="231" y1="43" x2="241" y2="23"/>
                    <line x1="242" y1="43" x2="252" y2="23"/>
                </g>
                <polyline points="257,14 305,14" fill="none" stroke="#fff" stroke-width="2"/>
                <circle cx="0" cy="31" r="3" fill="#fff"/>
                <circle cx="210" cy="14" r="3.5" fill="#fff"/>
                <circle cx="305" cy="14" r="4" fill="#fff"/>
            </svg>

            <div class="internship-paper">
                <div class="internship-border">
                    <header class="internship-header">
                        <div class="internship-brand">
                            <div class="internship-logos">
                                <img :src="'/assets/Images/logo2.png'" alt="ETEC Center" class="internship-etec-logo">
                                <img :src="'/assets/KruITSulotion.png'" alt="KRU IT Solution" class="internship-kru-logo">
                            </div>
                            <div class="internship-khmer-caption">គ្រូអាយធីចិត្តល្អ រួមគ្នាដើម្បីបច្ចេកវិទ្យា</div>
                        </div>

                        <div class="internship-kingdom">
                            <div>KINGDOM OF CAMBODIA</div>
                            <div>NATION&nbsp; RELIGION&nbsp; KING</div>
                            <img :src="'/assets/Images/border.png'" alt="">
                        </div>
                    </header>

                    <section class="internship-body">
                        <h1>Certificate of Completion</h1>
                        <p class="internship-presented">is proudly presented to</p>
                        <h2>{{ certificate.student_name }}</h2>
                        <p class="internship-description">
                            has successfully completed an internship in <strong>{{ certificate.course }}</strong> at <strong>KRU IT Solution</strong>,
                            demonstrating dedication, teamwork, and commitment to professional growth.
                        </p>
                        <p class="internship-granted">Granted: {{ certificate.granted_date }}</p>
                    </section>

                    <footer class="internship-footer">
                        <div class="internship-signature">
                            <div class="internship-sig-line"></div>
                            <strong>{{ certificate.team_lead_name || 'Mr. SRIN NALEN' }}</strong>
                            <span>{{ certificate.team_lead_title || 'Team Leader, KRU IT Solution' }}</span>
                        </div>

                        <div class="internship-id">ID : {{ certificate.certificate_id }}</div>

                        <div class="internship-signature">
                            <div class="internship-sig-line"></div>
                            <strong>{{ certificate.director || 'Mr. HENG PHEAKNA' }}</strong>
                            <span>Director, ETEC CENTER</span>
                        </div>
                    </footer>
                </div>
            </div>
        </div>
    </article>

    <article v-else class="certificate-preview printable-certificate">
        <div class="certificate-wrap">
            <div class="certificate">
                <div class="cert-outer-border">
                    <div class="cert-inner-border">
                        <div class="cert-kingdom">
                            <div>KINGDOM OF CAMBODIA</div>
                            <div>NATION&nbsp; RELIGION &nbsp;KING</div>
                            <img :src="'/assets/Images/border.png'" alt="">
                        </div>

                        <div class="cert-logo-area">
                            <img
                                :src="'/assets/Images/logo2.png'"
                                alt="Logo"
                                class="cert-logo-img"
                                @error="$event.target.style.display = 'none'; $event.target.nextElementSibling.style.display = 'flex'"
                            >
                            <div class="cert-logo-fallback">E</div>
                        </div>

                        <div class="cert-school-kh">
                            មជ្ឈមណ្ឌលវិស្វកម្មបច្ចេកវិទ្យា និង<span>អេឡិចត្រូនិក</span>
                        </div>
                        <div class="cert-school-en">
                            <span>Engineering</span> of Technology and Electronic Center
                        </div>

                        <div class="cert-title">Certificate of Completion</div>
                        <div class="cert-certify">This is to certify that</div>
                        <h1 class="cert-student-name">{{ certificate.student_name }}</h1>

                        <div class="cert-desc">
                            has successfully completed all requirements for completion<br>
                            of the I.T Training Courses in
                        </div>
                        <h4 class="cert-course">{{ certificate.course }}</h4>
                        <div class="cert-granted">Granted: {{ certificate.granted_date }}</div>

                        <div class="cert-footer">
                            <div class="cert-id">
                                <span class="id_text">ID:</span> {{ certificate.certificate_id }}
                            </div>
                            <div class="cert-signature">
                                <div class="cert-sig-line"></div>
                                <div class="cert-sig-name">Mr.<span>HENG PHEAKNA</span></div>
                                <div class="cert-sig-role">Director</div>
                            </div>
                        </div>
                        <div class="cert-id cert-id-print-only">
                            <span class="id_text">ID:</span> {{ certificate.certificate_id }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </article>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Bayon&family=Bebas+Neue&family=Dancing+Script:wght@400..700&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Kdam+Thmor+Pro&family=Koulen&family=Libre+Baskerville:wght@400;700&family=Moul&family=Noto+Serif:ital,wght@0,100..900;1,100..900&family=Oswald:wght@200..700&family=Permanent+Marker&family=Playwrite+AU+QLD:wght@100..400&family=Roboto:ital,wght@0,100..900&family=UnifrakturCook:wght@700&family=UnifrakturMaguntia&display=swap');

@font-face {
    font-family: "oldeng";
    src: url("/assets/fonts/oldenglishtextmt.ttf") format("truetype");
}

@font-face {
    font-family: "Simp";
    src: url("/assets/fonts/simpfxo.ttf") format("truetype");
}

@font-face {
    font-family: "KhmerUI";
    src: url("/assets/fonts/KhmerUIb.ttf") format("truetype");
}

@font-face {
    font-family: "Battambang-Regular";
    src: url("/assets/fonts/Battambang-Regular.ttf") format("truetype");
}

.certificate-preview {
    display: grid;
    place-items: center;
    width: 100%;
    max-width: 100%;
    overflow: auto;
}

:global(.dark) .certificate-preview {
    background: transparent;
}

.certificate-wrap {
    display: flex;
    justify-content: center;
    width: 100%;
    background: #e0e0e0;
    padding: 5px;
    box-sizing: border-box;
}

:global(.dark) .certificate-wrap {
    background: #e0e0e0;
}

.certificate {
    width: 100%;
    max-width: 520px;
    background: #fff;
    padding: 10px;
    box-sizing: border-box;
}

:global(.dark) .certificate,
:global(.dark) .cert-inner-border {
    background: #fff;
}

.cert-outer-border {
    border: 15px solid #2d2e81;
    border-radius: 10px;
    padding: 4px;
}

.cert-inner-border {
    position: relative;
    min-height: 624px;
    border: 8px solid #a6a6a6;
    border-radius: 8px;
    background: #fff;
    padding: 15px 15px 3px;
}

.cert-kingdom {
    margin-bottom: 20px;
    color: #2d2e81;
    font-family: "Simp", sans-serif;
    font-size: .7rem;
    font-weight: bolder;
    line-height: 1.5;
    letter-spacing: .02em;
    text-align: center;
    text-shadow: 0 0 .3px #2d2e81;
    text-transform: uppercase;
    -webkit-text-stroke: .3px #2d2e81;
}

.cert-kingdom img {
    display: block;
    max-width: 120px;
    height: auto;
    margin: 4px auto 0;
    border: none;
    object-fit: contain;
}

.cert-logo-area {
    margin-bottom: 12px;
    text-align: center;
}

.cert-logo-img {
    display: inline-block;
    width: 112px;
    height: 112px;
    border-radius: 13px;
    object-fit: contain;
}

.cert-logo-fallback {
    display: none;
    align-items: center;
    justify-content: center;
    width: 102px;
    height: 102px;
    margin: 0 auto;
    border-radius: 12px;
    background: linear-gradient(135deg, #2d2e81, #1f2060);
    color: #fff;
    font-size: 2.4rem;
    font-weight: 900;
}

.cert-school-kh {
    margin-bottom: 4px;
    color: #2d2e81;
    font-family: "Moul", serif;
    font-size: .9rem;
    font-weight: 700;
    text-align: center;
}

.cert-school-kh span {
    color: #000;
}

.cert-school-en {
    margin-bottom: 14px;
    color: #2d2e81;
    font-family: Arial, sans-serif;
    font-size: 13px;
    font-weight: 550;
    text-align: center;
}

.cert-school-en span {
    color: #000;
}

.cert-title {
    margin: 2px 0 7px;
    color: #111;
    font-family: "oldeng", cursive;
    font-size: 1.8rem;
    font-weight: 700;
    text-align: center;
}

.cert-certify {
    margin-bottom: 16px;
    color: #2d2e81;
    font-family: "Courier New", monospace;
    font-size: 16px;
    font-weight: 700;
    letter-spacing: .08em;
    text-align: center;
}

.cert-student-name {
    margin: 0 0 14px;
    color: #111;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 1.4rem;
    font-weight: 950;
    letter-spacing: .01em;
    text-align: center;
    text-shadow: 0 0 .35px #000;
    text-transform: uppercase;
    -webkit-text-stroke: .2px #000;
}

.cert-desc {
    margin-bottom: 14px;
    color: #2d2e81;
    font-family: "Noto Serif", serif;
    font-size: 13px;
    letter-spacing: .01em;
    text-align: center;
}

.cert-course {
    max-width: 100%;
    margin: 0 0 10px;
    color: #000;
    font-family: "Simp", sans-serif;
    font-size: 22px;
    font-weight: 900;
    line-height: 34px;
    text-align: center;
    text-shadow: 0 0 2px #000;
    white-space: normal;
    word-break: break-word;
    -webkit-text-stroke: .5px #000;
    white-space: pre-line;
}

.cert-granted {
    margin-bottom: 36px;
    color: #2d2e81;
    font-family: Arial, sans-serif;
    font-size: .9rem;
    font-weight: 700;
    letter-spacing: .05em;
    text-align: center;
}

.cert-footer {
    position: relative;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    padding-top: 20px;
}

.cert-id {
    position: absolute;
    bottom: 0;
    left: 50%;
    color: #c00;
    font-family: Arial, sans-serif;
    font-size: 7px;
    font-weight: 700;
    letter-spacing: .08em;
    white-space: nowrap;
    transform: translateX(-50%);
}

.cert-id-print-only {
    display: none;
}

.id_text {
    color: #2d2e81;
    font-family: "Simp", sans-serif;
    font-size: 8px;
}

.cert-signature {
    margin-left: auto;
    text-align: right;
}

.cert-sig-line {
    width: 150px;
    height: 1px;
    margin-left: auto;
    margin-bottom: 2px;
    background: #222;
}

.cert-sig-name,
.cert-sig-role {
    color: #111;
    font-family: "Simp", sans-serif;
    font-size: .8rem;
    font-weight: 700;
    text-align: center;
    text-shadow: 0 0 .4px #000;
    -webkit-text-stroke: .5px #000;
}

.cert-sig-role {
    font-size: .78rem;
}

/* ============================================================
   INTERNSHIP CERTIFICATE — fully fluid via container queries.
   The design was authored at 1128x792. Instead of a fixed-px
   layout forced down with `transform: scale(.727)` (which only
   fit one exact container width and clipped everywhere else),
   every size below is expressed in `cqw` (% of this element's
   own container width). That makes the whole card scale
   smoothly to fill whatever box it's placed in — a narrow modal,
   a full-width page, a small phone screen — with no clipping,
   no scrollbars, and no manual scale factor to maintain.
   1128px (full design width) == 100cqw.
   ============================================================ */

.internship-certificate-preview {
    display: block;
    width: 100%;
    max-width: 1040px;
    margin: 0 auto;
    aspect-ratio: 1128 / 792;
    container-type: inline-size;
    border-radius: 8px;
    background: #e7e7e7;
    overflow: hidden;
}

.internship-certificate {
    position: relative;
    box-sizing: border-box;
    width: 100%;
    height: 100%;
    border: 0.35cqw solid #f7f7f7;
    border-radius: 0.7cqw;
    background: #08216d;
    padding: 2.72cqw;
    color: #111;
    font-family: "Libre Baskerville", "Times New Roman", serif;
    overflow: hidden;
}

.internship-paper {
    position: relative;
    z-index: 1;
    display: flex;
    height: 100%;
    border-radius: 0.62cqw;
    background: #fff;
    padding: 1.35cqw;
}

.internship-border {
    position: relative;
    display: flex;
    flex: 1;
    flex-direction: column;
    border: 0.18cqw solid #e8f000;
    border-radius: 0.62cqw;
    padding: 2.55cqw 5.9cqw 1.9cqw;
}

/* Corner circuit-trace decorations (dot -> line -> diagonal hash -> line -> dot) */
.internship-circuit-svg {
    position: absolute;
    z-index: 2;
    width: 18.17cqw;
    height: auto;
    pointer-events: none;
}

.circuit-top {
    top: 0.8cqw;
    left: 0.8cqw;
}

.circuit-bottom {
    right: 0.8cqw;
    bottom: 0.8cqw;
    transform: rotate(180deg);
}

.internship-header,
.internship-footer {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 2.4cqw;
}

.internship-header {
    min-height: 13.2cqw;
}

.internship-brand {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.internship-logos {
    display: flex;
    align-items: center;
    gap: 1.0cqw;
}

.internship-etec-logo {
    width: 9.05cqw;
    height: 9.05cqw;
    border-radius: 0.53cqw;
    object-fit: cover;
}

.internship-kru-logo {
    width: 16.05cqw;
    height: 9.65cqw;
    object-fit: contain;
}

.internship-khmer-caption {
    display: block;
    margin-top: 0.05cqw;
    color: #111;
    font-family: "KhmerUI", "Battambang-Regular", "Koulen", serif;
    font-size: 1.62cqw;
    font-weight: 700;
    line-height: 1.1;
    text-align: center;
    white-space: nowrap;
}

.internship-kingdom {
    min-width: 29.79cqw;
    padding-top: 0.1cqw;
    font-size: 2.02cqw;
    font-weight: 900;
    line-height: 1.42;
    text-align: center;
}

.internship-kingdom img {
    display: block;
    width: 17.2cqw;
    margin: 0.9cqw auto 0;
}

.internship-body {
    display: grid;
    justify-items: center;
    text-align: center;
}

.internship-body h1 {
    margin: 1.65cqw 0 2.75cqw;
    font-family: "oldeng", "UnifrakturMaguntia", "Times New Roman", serif;
    font-size: 5.75cqw;
    font-weight: 400;
    letter-spacing: .01em;
    line-height: 1;
}

.internship-presented {
    margin: 0 0 3.15cqw;
    font-size: 2.0cqw;
    font-weight: 700;
}

.internship-body h2 {
    min-width: 14.18cqw;
    margin: 0 0 5.15cqw;
    border-bottom: 0.18cqw solid #e8f000;
    color: #08216d;
    padding: 0 2.2cqw 0.6cqw;
    font-family: "Libre Baskerville", Georgia, "Times New Roman", serif;
    font-size: 3.25cqw;
    font-weight: 900;
    line-height: 1.1;
    text-transform: uppercase;
}

.internship-description {
    max-width: 78cqw;
    margin: 0;
    font-size: 1.78cqw;
    font-weight: 400;
    line-height: 1.22;
}

.internship-description strong {
    font-weight: 900;
}

.internship-granted {
    margin: 3.95cqw 0 0;
    color: #08216d;
    font-size: 1.6cqw;
    font-weight: 900;
}

.internship-footer {
    align-items: flex-end;
    margin-top: auto;
    padding-top: 0;
}

.internship-signature {
    min-width: 28.2cqw;
    text-align: center;
}

.internship-sig-line {
    width: 23.6cqw;
    height: 0.18cqw;
    margin: 0 auto 0.53cqw;
    background: #e8f000;
}

.internship-signature strong,
.internship-signature span {
    display: block;
    color: #111;
    font-size: 1.55cqw;
    line-height: 1.25;
}

.internship-signature strong {
    font-weight: 900;
}

.internship-id {
    color: red;
    font-family: Arial, sans-serif;
    align-self: flex-end;
    margin-bottom: 0.1cqw;
    font-size: 0.85cqw;
    font-weight: 900;
    white-space: nowrap;
}

@media print {
    .certificate-preview {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 210mm !important;
        height: 297mm !important;
        min-height: 297mm !important;
        overflow: hidden !important;
        background: #fff !important;
    }

    .certificate-wrap {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 100% !important;
        height: 100% !important;
        min-height: 297mm !important;
        background: none !important;
        padding: 0 !important;
    }

    .certificate {
        display: flex !important;
        flex-direction: column !important;
        box-sizing: border-box !important;
        width: 210mm !important;
        max-width: none !important;
        height: 297mm !important;
        margin: 0 !important;
        padding: 5mm !important;
        background: #fff !important;
        box-shadow: none !important;
    }

    .cert-outer-border {
        display: flex !important;
        flex: 1 !important;
        flex-direction: column !important;
        box-sizing: border-box !important;
        border: 20px solid #2d2e81 !important;
        border-radius: 10px !important;
        padding: 6px !important;
        print-color-adjust: exact !important;
        -webkit-print-color-adjust: exact !important;
    }

    .cert-inner-border {
        position: relative !important;
        display: flex !important;
        flex: 1 !important;
        flex-direction: column !important;
        box-sizing: border-box !important;
        border: 13px solid #a6a6a6 !important;
        border-radius: 8px !important;
        print-color-adjust: exact !important;
        -webkit-print-color-adjust: exact !important;
    }

    .cert-school-kh,
    .cert-certify,
    .cert-desc,
    .cert-granted {
        color: #2d2e81 !important;
        print-color-adjust: exact !important;
        -webkit-print-color-adjust: exact !important;
    }

    .cert-id {
        color: #cc0000 !important;
        print-color-adjust: exact !important;
        -webkit-print-color-adjust: exact !important;
    }

    .cert-kingdom {
        font-size: 1.3rem !important;
        text-shadow: 0 0 .3px #2d2e81 !important;
        -webkit-text-stroke: .3px #2d2e81 !important;
    }

    .cert-logo-img,
    .cert-logo-fallback {
        width: 150px !important;
        height: 150px !important;
    }

    .cert-school-kh {
        margin-bottom: 6px !important;
        font-size: 21px !important;
    }

    .cert-school-en {
        margin-bottom: 18px !important;
        font-size: 20px !important;
    }

    .cert-title {
        margin: 0 7px 13px !important;
        font-size: 2.6rem !important;
    }

    .cert-certify {
        margin-bottom: 20px !important;
        font-size: 1.5rem !important;
        letter-spacing: .04em !important;
    }

    .cert-student-name {
        margin-top: 15px !important;
        margin-bottom: 25px !important;
        font-size: 1.8rem !important;
        font-weight: 950 !important;
        letter-spacing: 0 !important;
        text-shadow: 0 0 .45px #000 !important;
        -webkit-text-stroke: .25px #000 !important;
    }

    .cert-desc {
        margin-bottom: 22px !important;
        font-size: 1.2rem !important;
    }

    .cert-course {
        margin-top: 10px !important;
        margin-bottom: 18px !important;
        font-size: 1.9rem !important;
        line-height: 48px !important;
        -webkit-text-stroke: 1.2px #000 !important;
    }

    .cert-granted {
        margin-bottom: auto !important;
        font-size: 1.5rem !important;
        font-weight: 700 !important;
        letter-spacing: 0 !important;
    }

    .cert-footer {
        position: relative !important;
        display: grid !important;
        grid-template-columns: 1fr auto 1fr !important;
        align-items: end !important;
        margin-top: auto !important;
        padding-top: 18mm !important;
    }

    .cert-footer .cert-id {
        display: none !important;
    }

    .cert-id {
        position: static !important;
        bottom: auto !important;
        left: auto !important;
        grid-column: 2 !important;
        justify-self: center !important;
        align-self: end !important;
        margin: 0 !important;
        font-size: .6rem !important;
        text-align: center !important;
        transform: none !important;
        white-space: nowrap !important;
    }

    .cert-id-print-only {
        position: absolute !important;
        bottom: 2.1mm !important;
        left: 50% !important;
        z-index: 3 !important;
        display: block !important;
        margin: 0 !important;
        text-align: center !important;
        transform: translateX(-50%) !important;
        white-space: nowrap !important;
    }

    .cert-signature {
        grid-column: 3 !important;
        justify-self: end !important;
        margin-left: 0 !important;
    }

    .cert-sig-line {
        width: 220px !important;
    }

    .cert-sig-name,
    .cert-sig-role {
        font-size: 1.2rem !important;
    }

    .id_text {
        color: #2d2e81 !important;
        font-size: .7rem !important;
        font-weight: 900 !important;
    }

    .internship-circuit-svg {
        width: 42mm !important;
    }

    .circuit-top {
        top: 3mm !important;
        left: 3mm !important;
    }

    .circuit-bottom {
        right: 3mm !important;
        bottom: 3mm !important;
    }

    .internship-certificate-preview {
        display: flex !important;
        width: 297mm !important;
        max-width: none !important;
        height: 210mm !important;
        min-height: 210mm !important;
        aspect-ratio: auto !important;
    }

    .internship-certificate {
        width: 297mm !important;
        height: 210mm !important;
        aspect-ratio: auto !important;
        border-width: 1.2mm !important;
        border-radius: 0 !important;
        padding: 8mm !important;
        box-shadow: none !important;
    }

    .internship-paper {
        padding: 4mm !important;
    }

    .internship-border {
        padding: 7mm 15mm 4mm !important;
    }

    .internship-etec-logo {
        width: 26mm !important;
        height: 26mm !important;
    }

    .internship-kru-logo {
        width: 43mm !important;
        height: 27mm !important;
    }

    .internship-khmer-caption {
        display: block !important;
        font-size: 4.7mm !important;
        font-weight: 700 !important;
    }

    .internship-body h1 {
        margin-top: 8mm !important;
        font-family: "oldeng", "UnifrakturMaguntia", "Times New Roman", serif !important;
        font-size: 17.1mm !important;
        font-weight: 400 !important;
        letter-spacing: .01em !important;
    }

    .internship-presented {
        font-size: 6mm !important;
    }

    .internship-body h2 {
        font-size: 9.2mm !important;
    }

    .internship-description {
        max-width: 250mm !important;
        font-size: 5.3mm !important;
        font-weight: 400 !important;
    }

    .internship-description strong {
        font-weight: 900 !important;
    }

    .internship-granted {
        font-size: 4.6mm !important;
    }

    .internship-signature strong,
    .internship-signature span {
        font-size: 4.6mm !important;
    }

    .internship-id {
        font-size: 2.55mm !important;
    }
}
</style>
