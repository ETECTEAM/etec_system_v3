<script setup>
defineProps({
    certificate: {
        type: Object,
        required: true,
    },
})
</script>

<template>
    <article id="class-free-cert" class="certificate-free-wrapper certificate-preview">
        <div id="printable-certificate-free" class="certificate-free-wrap">
            <div class="certificate-free">
                <div class="cert-free-outer-border">
                    <div class="cert-free-inner-border">
                        <div class="cert-free-header">
                            <div class="cert-free-header-left">
                                <div class="cert-free-logo-box">
                                    <img :src="'/assets/Images/logo.png'" alt="ETEC">
                                </div>
                                <div class="cert-free-motto">"Build your IT Skill"</div>
                            </div>

                            <div class="cert-free-header-right">
                                <div class="cert-free-kingdom">
                                    <div>KINGDOM OF CAMBODIA</div>
                                    <div>NATION&nbsp; RELIGION &nbsp;KING</div>
                                    <img :src="'/assets/Images/border.png'" alt="">
                                </div>
                            </div>
                        </div>

                        <div class="cert-free-title">Certificate of Completion</div>
                        <div class="cert-free-certify">This is to certify that</div>
                        <div class="cert-free-student-name">{{ certificate.student_name }}</div>

                        <div class="cert-free-desc">
                            has successfully completed all requirements for completion of the<br>
                            I.T Training Courses in
                        </div>

                        <div class="cert-free-course">{{ certificate.course }}</div>
                        <div class="cert-free-granted">Granted: {{ certificate.granted_date }}</div>

                        <div class="cert-free-bottom">
                            <div class="cert-free-bottom-spacer"></div>
                            <div class="cert-free-id-bottom">
                                <span>ID:</span> {{ certificate.certificate_id }}
                            </div>
                            <div class="cert-free-signatur">
                                <div class="cert-free-sig-line"></div>
                                <div class="cert-free-sig-name">Mr. Heng Pheakna</div>
                                <div class="cert-free-sig-role">Director</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </article>

    <Teleport to="body">
        <article id="class-free-cert-print" class="free-print-root certificate-free-wrapper">
            <div class="certificate-free-wrap">
                <div class="certificate-free">
                    <div class="cert-free-outer-border">
                        <div class="cert-free-inner-border">
                            <div class="cert-free-header">
                                <div class="cert-free-header-left">
                                    <div class="cert-free-logo-box">
                                        <img :src="'/assets/Images/logo.png'" alt="ETEC">
                                    </div>
                                    <div class="cert-free-motto">"Build your IT Skill"</div>
                                </div>

                                <div class="cert-free-header-right">
                                    <div class="cert-free-kingdom">
                                        <div>KINGDOM OF CAMBODIA</div>
                                        <div>NATION&nbsp; RELIGION &nbsp;KING</div>
                                        <img :src="'/assets/Images/border.png'" alt="">
                                    </div>
                                </div>
                            </div>

                            <div class="cert-free-title">Certificate of Completion</div>
                            <div class="cert-free-certify">This is to certify that</div>
                            <div class="cert-free-student-name">{{ certificate.student_name }}</div>

                            <div class="cert-free-desc">
                                has successfully completed all requirements for completion of the<br>
                                I.T Training Courses in
                            </div>

                            <div class="cert-free-course">{{ certificate.course }}</div>
                            <div class="cert-free-granted">Granted: {{ certificate.granted_date }}</div>

                            <div class="cert-free-bottom">
                                <div class="cert-free-bottom-spacer"></div>
                                <div class="cert-free-id-bottom">
                                    <span>ID:</span> {{ certificate.certificate_id }}
                                </div>
                                <div class="cert-free-signatur">
                                    <div class="cert-free-sig-line"></div>
                                    <div class="cert-free-sig-name">Mr. Heng Pheakna</div>
                                    <div class="cert-free-sig-role">Director</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </article>
    </Teleport>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=UnifrakturCook:wght@700&family=Playpen+Sans+Deva:ital,wght@1,700&display=swap');

.certificate-free-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    width: 100%;
    min-height: 492px;
    border-radius: 16px;
    background: #fff;
    padding: 20px;
    box-shadow: 0 6px 24px rgba(15, 23, 42, .08);
}

:global(.dark) .certificate-free-wrapper {
    background: #111827;
    box-shadow: 0 22px 50px rgba(0, 0, 0, .32);
}

:global(html.dark) .certificate-free-wrapper {
    background:
        radial-gradient(circle at 50% 0%, rgba(96, 165, 250, .10), transparent 34%),
        #111827 !important;
    box-shadow: 0 22px 50px rgba(0, 0, 0, .34) !important;
}

.free-print-root {
    display: none;
}

.certificate-free-wrap {
    display: flex;
    justify-content: center;
    width: 100%;
}

.certificate-free {
    width: 795px;
    min-height: 462px;
    background: #fff;
    color-scheme: light;
    padding: 6px;
}

:global(.dark) .certificate-free,
:global(.dark) .cert-free-inner-border {
    background: #fff;
}

:global(html.dark) .certificate-free,
:global(html.dark) .cert-free-inner-border {
    background: #fff !important;
    color-scheme: light;
}

.cert-free-outer-border {
    min-height: 450px;
    border: 16px solid #20215f;
    border-radius: 8px;
    padding: 5px;
}

.cert-free-inner-border {
    min-height: 408px;
    border: 12px solid rgba(190, 193, 26, .7);
    background: #fff;
    padding: 15px 25px 13px;
}

.cert-free-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
}

.cert-free-header-left {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.cert-free-logo-box {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 66px;
    height: 66px;
    overflow: hidden;
    border-radius: 4px;
}

.cert-free-logo-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.cert-free-motto {
    margin-top: 8px;
    color: #333;
    font-family: Arial, sans-serif;
    font-size: 13px;
    font-weight: 700;
    white-space: nowrap;
}

.cert-free-header-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.cert-free-kingdom {
    color: #222;
    font-family: "Courier New", monospace;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: .08em;
    line-height: 1.45;
    text-align: center;
    text-transform: uppercase;
    white-space: nowrap;
}

.cert-free-kingdom img {
    display: block;
    max-width: 132px;
    height: auto;
    margin: 4px auto 0;
    object-fit: contain;
}

.cert-free-title {
    margin-top: 20px;
    color: #20215f;
    font-family: "UnifrakturCook", "oldeng", cursive;
    font-size: 38px;
    font-weight: 700;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
}

.cert-free-certify {
    margin-top: 15px;
    color: #20215f;
    font-family: "Courier New", monospace;
    font-size: 19px;
    font-weight: 700;
    text-align: center;
    white-space: nowrap;
}

.cert-free-student-name {
    margin: 11px 0 12px;
    color: #1b2a6b;
    font-family: "Playpen Sans Deva", "Comic Sans MS", cursive;
    font-size: 26px;
    font-style: italic;
    font-weight: 700;
    letter-spacing: .05em;
    line-height: 1;
    text-align: center;
    text-transform: uppercase;
    -webkit-text-stroke: .7px #000;
}

.cert-free-desc {
    color: #20215f;
    font-family: "Times New Roman", Times, serif;
    font-size: 15px;
    letter-spacing: .01em;
    line-height: 1.45;
    text-align: center;
    white-space: nowrap;
}

.cert-free-course {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 500px;
    min-height: 29px;
    margin: 8px auto;
    color: #000;
    font-family: "Courier New", monospace;
    font-size: 16px;
    font-weight: 900;
    letter-spacing: .07em;
    line-height: 1.25;
    text-align: center;
    text-transform: uppercase;
    overflow-wrap: anywhere;
}

.cert-free-granted {
    margin-bottom: 16px;
    color: #2d2e81;
    font-family: Arial, sans-serif;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: .05em;
    text-align: center;
    white-space: nowrap;
}

.cert-free-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    margin-top: 16px;
}

.cert-free-bottom-spacer,
.cert-free-signatur {
    width: 20%;
}

.cert-free-id-bottom {
    width: 60%;
    color: red;
    font-family: "Courier New", monospace;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .04em;
    text-align: center;
    white-space: nowrap;
}

.cert-free-id-bottom span {
    color: #000;
}

.cert-free-sig-line {
    width: 136px;
    height: 1px;
    margin-left: auto;
    margin-bottom: 4px;
    background: #cea324;
}

.cert-free-sig-name {
    color: #111;
    font-family: "Courier New", monospace;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .05em;
    text-align: center;
    white-space: nowrap;
}

.cert-free-sig-role {
    color: #333;
    font-family: "Courier New", monospace;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .07em;
    text-align: center;
    white-space: nowrap;
}

@media print {
    @page {
        size: A4 landscape;
        margin: 0;
    }

    :global(html),
    :global(body) {
        width: 297mm !important;
        height: 210mm !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: hidden !important;
        background: #fff !important;
    }

    :global(body.free-certificate-print *) {
        visibility: hidden !important;
    }

    :global(body.free-certificate-print > *:not(#class-free-cert-print)) {
        display: none !important;
    }

    :global(body.free-certificate-print #class-free-cert-print),
    :global(body.free-certificate-print #class-free-cert-print *) {
        visibility: visible !important;
    }

    :global(body.free-certificate-print) #class-free-cert {
        display: none !important;
    }

    #class-free-cert-print,
    :global(body.free-certificate-print #class-free-cert-print) {
        position: fixed !important;
        inset: 0 !important;
        z-index: 999999 !important;
        width: 297mm !important;
        height: 210mm !important;
        min-height: 210mm !important;
        margin: 0 !important;
        border-radius: 0 !important;
        background: #fff !important;
        padding: 0 !important;
        box-shadow: none !important;
        overflow: hidden !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    #class-free-cert-print .certificate-free-wrap {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 297mm !important;
        height: 210mm !important;
    }

    #class-free-cert-print .certificate-free {
        box-sizing: border-box !important;
        width: 297mm !important;
        height: 210mm !important;
        min-height: 210mm !important;
        padding: 5mm !important;
        box-shadow: none !important;
    }

    #class-free-cert-print .cert-free-outer-border {
        display: flex !important;
        flex-direction: column !important;
        box-sizing: border-box !important;
        width: 100% !important;
        height: 100% !important;
    }

    #class-free-cert-print .cert-free-inner-border {
        display: flex !important;
        flex: 1 !important;
        flex-direction: column !important;
        justify-content: space-between !important;
        box-sizing: border-box !important;
        min-height: 0 !important;
        height: auto !important;
    }

    #class-free-cert-print .cert-free-logo-box {
        width: 110px !important;
        height: 110px !important;
    }

    #class-free-cert-print .cert-free-motto {
        margin-top: 4px !important;
        font-size: 19px !important;
    }

    #class-free-cert-print .cert-free-kingdom {
        font-size: 19px !important;
        line-height: 1.5 !important;
    }

    #class-free-cert-print .cert-free-kingdom img {
        max-width: 150px !important;
        margin-top: 4px !important;
    }

    #class-free-cert-print .cert-free-title {
        margin-top: 18px !important;
        font-size: 56px !important;
        line-height: 1 !important;
    }

    #class-free-cert-print .cert-free-certify {
        margin-top: 20px !important;
        font-size: 29px !important;
    }

    #class-free-cert-print .cert-free-student-name {
        margin: 18px 0 16px !important;
        font-size: 34px !important;
        -webkit-text-stroke: 1px #000 !important;
    }

    #class-free-cert-print .cert-free-desc {
        font-size: 24px !important;
        line-height: 1.45 !important;
    }

    #class-free-cert-print .cert-free-course {
        width: 500px !important;
        min-height: 40px !important;
        margin: 10px auto !important;
        font-size: 24px !important;
    }

    #class-free-cert-print .cert-free-granted {
        margin-bottom: 20px !important;
        font-size: 18px !important;
    }

    #class-free-cert-print .cert-free-bottom {
        margin-top: 5px !important;
    }

    #class-free-cert-print .cert-free-id-bottom {
        font-size: 16px !important;
    }

    #class-free-cert-print .cert-free-sig-line {
        width: 200px !important;
    }

    #class-free-cert-print .cert-free-sig-name,
    #class-free-cert-print .cert-free-sig-role {
        font-size: 18px !important;
    }

    * {
        print-color-adjust: exact !important;
        -webkit-print-color-adjust: exact !important;
    }
}
</style>
