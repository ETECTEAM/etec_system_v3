<script setup>
defineProps({
    certificate: {
        type: Object,
        required: true,
    },
})
</script>

<template>
    <article class="certificate-preview printable-certificate">
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
@import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Dancing+Script:wght@400..700&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Kdam+Thmor+Pro&family=Koulen&family=Moul&family=Noto+Serif:ital,wght@0,100..900;1,100..900&family=Oswald:wght@200..700&family=Permanent+Marker&family=Playwrite+AU+QLD:wght@100..400&family=Roboto:ital,wght@0,100..900&family=UnifrakturCook:wght@700&display=swap');

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

.certificate-wrap {
    display: flex;
    justify-content: center;
    background: #e0e0e0;
    padding: 5px;
}

.certificate {
    width: 520px;
    background: #fff;
    padding: 10px;
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
    width: 98px;
    height: 98px;
    border-radius: 13px;
    object-fit: contain;
}

.cert-logo-fallback {
    display: none;
    align-items: center;
    justify-content: center;
    width: 88px;
    height: 88px;
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
    font-size: 18px;
    font-weight: bolder;
    line-height: 28px;
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

@media print {
    .certificate-preview {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 210mm !important;
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
        background: none !important;
        padding: 0 !important;
    }

    .certificate {
        display: flex !important;
        flex-direction: column !important;
        box-sizing: border-box !important;
        width: 205mm !important;
        height: 292mm !important;
        margin: 0 !important;
        padding: 18px !important;
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
        width: 130px !important;
        height: 130px !important;
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
        font-size: 1.6rem !important;
        line-height: 43px !important;
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
}
</style>
