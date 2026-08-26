<script setup>
import { ref, onMounted } from 'vue';
import { Modal, Carousel } from 'bootstrap';

const ONBOARDING_V2_KEY = 'eboardmate_onboarding_v2_completed';

const currentSlideIndex = ref(0);
const totalSlides = 6;
const onboardingCompleted = ref(
    localStorage.getItem(ONBOARDING_V2_KEY) === 'true'
);

let carouselInstance = null;
let modalInstance = null;

onMounted(() => {
    const carouselEl = document.getElementById('onboardingCarousel');
    if (carouselEl) {
        carouselInstance = Carousel.getOrCreateInstance(carouselEl, {
            interval: false,
            wrap: false,
            touch: true,
        });

        carouselEl.addEventListener('slid.bs.carousel', (event) => {
            currentSlideIndex.value = event.to;
        });
    }

    if (!onboardingCompleted.value) {
        const modalEl = document.getElementById('studentOnboardingModal');
        if (modalEl) {
            modalInstance = Modal.getOrCreateInstance(modalEl, {
                backdrop: 'static',
                keyboard: false,
            });
            setTimeout(() => {
                modalInstance.show();
            }, 600);
        }
    }
});

const nextSlide = () => {
    if (carouselInstance && currentSlideIndex.value < totalSlides - 1) {
        carouselInstance.next();
    }
};

const prevSlide = () => {
    if (carouselInstance && currentSlideIndex.value > 0) {
        carouselInstance.prev();
    }
};

const goToSlide = (index) => {
    if (carouselInstance) {
        carouselInstance.to(index);
    }
};

const completeOnboarding = () => {
    localStorage.setItem(ONBOARDING_V2_KEY, 'true');
    localStorage.setItem('hasSeenStudentTour', 'true');
    onboardingCompleted.value = true;

    const modalEl = document.getElementById('studentOnboardingModal');
    if (modalEl) {
        const bsModal = Modal.getOrCreateInstance(modalEl);
        bsModal.hide();
    }
};

const restartOnboarding = () => {
    currentSlideIndex.value = 0;
    goToSlide(0);
    const modalEl = document.getElementById('studentOnboardingModal');
    if (modalEl) {
        const bsModal = Modal.getOrCreateInstance(modalEl, {
            backdrop: 'static',
            keyboard: false,
        });
        bsModal.show();
    }
};
</script>

<template>
    <!-- 🚀 V2 PREMIUM & SECURE STUDENT ONBOARDING MODAL -->
    <div 
        id="studentOnboardingModal" 
        class="modal fade" 
        data-bs-backdrop="static" 
        data-bs-keyboard="false" 
        tabindex="-1" 
        aria-labelledby="studentOnboardingModalLabel" 
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-lg">
            <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden bg-body">
                
                <!-- MODAL HEADER -->
                <div class="modal-header border-bottom border-secondary-subtle px-4 py-3 bg-body-tertiary">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-bold">
                            🎓 Step {{ currentSlideIndex + 1 }} of {{ totalSlides }}
                        </span>
                        <span class="small text-body-secondary fw-semibold">
                            Freshmen Student Security Guide
                        </span>
                    </div>
                </div>

                <!-- MODAL BODY WITH BOOTSTRAP CAROUSEL -->
                <div class="modal-body p-4 p-md-5">
                    <div id="onboardingCarousel" class="carousel slide" data-bs-interval="false">
                        <div class="carousel-inner">
                            
                            <!-- SLIDE 1: WELCOME -->
                            <div class="carousel-item active">
                                <div class="text-center py-3">
                                    <div class="mb-3 d-inline-block p-4 rounded-circle bg-success-subtle text-success border border-success-subtle">
                                        <i class="bi bi-house-heart" style="font-size: 4rem; line-height: 1;"></i>
                                    </div>
                                    <h2 class="h3 fw-bold text-body-emphasis mb-2">Welcome to E-BoardMate</h2>
                                    <p class="text-body-secondary lead mb-4 mx-auto" style="max-width: 540px; font-size: 1.05rem;">
                                        Your official, verified student accommodations locator for <strong>Talibon Polytechnic College</strong>. Search accredited boarding houses with complete transparency.
                                    </p>
                                    <div class="badge bg-body-tertiary text-body-secondary border border-secondary-subtle rounded-pill px-3 py-2">
                                        <i class="bi bi-check-circle-fill text-success me-1"></i> 100% Free for TPC Students
                                    </div>
                                </div>
                            </div>

                            <!-- SLIDE 2: MAPPING & DISTANCES -->
                            <div class="carousel-item">
                                <div class="text-center py-3">
                                    <div class="mb-3 d-inline-block p-4 rounded-circle bg-primary-subtle text-primary border border-primary-subtle">
                                        <i class="bi bi-map-fill" style="font-size: 4rem; line-height: 1;"></i>
                                    </div>
                                    <h2 class="h3 fw-bold text-body-emphasis mb-2">Interactive Mapping & Distances</h2>
                                    <p class="text-body-secondary lead mb-3 mx-auto" style="max-width: 540px; font-size: 1.05rem;">
                                        Calculate real walking distances from TPC campus and view 3D satellite maps before visiting.
                                    </p>
                                    <div class="bg-body-tertiary p-3 rounded-4 border border-secondary-subtle mx-auto text-start small text-body-secondary" style="max-width: 500px;">
                                        <i class="bi bi-info-circle-fill text-primary me-2"></i>
                                        Walking route estimations are calculated based on road networks. Always verify physical access routes during your visit.
                                    </div>
                                </div>
                            </div>

                            <!-- SLIDE 3: ONE RESERVATION RULE -->
                            <div class="carousel-item">
                                <div class="text-center py-3">
                                    <div class="mb-3 d-inline-block p-4 rounded-circle bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                        <i class="bi bi-pin-angle-fill" style="font-size: 4rem; line-height: 1;"></i>
                                    </div>
                                    <h2 class="h3 fw-bold text-body-emphasis mb-2">One Active Reservation Rule</h2>
                                    <p class="text-body-secondary lead mb-3 mx-auto" style="max-width: 540px; font-size: 1.05rem;">
                                        To ensure equal availability for all students, you may only hold <strong>ONE active reservation request</strong> at a time.
                                    </p>
                                    <div class="bg-body-tertiary p-3 rounded-4 border border-secondary-subtle mx-auto text-start small text-body-secondary" style="max-width: 500px;">
                                        <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                                        Submitting a new request will replace or freeze pending submissions until resolved.
                                    </div>
                                </div>
                            </div>

                            <!-- SLIDE 4: CLEAR EXPECTATIONS & AUTOMATIC EXPIRATION -->
                            <div class="carousel-item">
                                <div class="text-center py-3">
                                    <div class="mb-3 d-inline-block p-4 rounded-circle bg-info-subtle text-info-emphasis border border-info-subtle">
                                        <i class="bi bi-hourglass-split" style="font-size: 4rem; line-height: 1;"></i>
                                    </div>
                                    <h2 class="h3 fw-bold text-body-emphasis mb-2">Automatic Request Expiration</h2>
                                    <p class="text-body-secondary lead mb-3 mx-auto" style="max-width: 540px; font-size: 1.05rem;">
                                        Never worry about getting stuck waiting. Landlords receive instant notifications for every request.
                                    </p>
                                    <div class="bg-body-tertiary p-3 rounded-4 border border-secondary-subtle mx-auto text-start small text-body-secondary" style="max-width: 500px;">
                                        <i class="bi bi-clock-history text-info me-2"></i>
                                        If an owner does not respond within the designated window, your request automatically expires—immediately freeing you to reserve another spot.
                                    </div>
                                </div>
                            </div>

                            <!-- SLIDE 5: MAXIMUM SECURITY & ANTI-SCAM -->
                            <div class="carousel-item">
                                <div class="text-center py-3">
                                    <div class="p-4 rounded-4 bg-danger bg-opacity-10 border border-danger border-opacity-25 mx-auto" style="max-width: 540px;">
                                        <div class="mb-2 text-danger">
                                            <i class="bi bi-shield-lock-fill" style="font-size: 4rem; line-height: 1;"></i>
                                        </div>
                                        <h2 class="h3 fw-bold text-danger mb-2">NO Online Payments Required</h2>
                                        <p class="text-body-emphasis fw-semibold mb-2" style="font-size: 1.05rem;">
                                            E-BoardMate will NEVER ask for online payments (GCash, Bank Transfer) on this website.
                                        </p>
                                        <p class="small text-body-secondary mb-0">
                                            All reservation confirmations and payments take place <strong>face-to-face with verified landlords</strong> during your physical visit.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- SLIDE 6: REFERENCE CODE TRACKING -->
                            <div class="carousel-item">
                                <div class="text-center py-3">
                                    <div class="mb-3 d-inline-block p-4 rounded-circle bg-success-subtle text-success border border-success-subtle">
                                        <i class="bi bi-patch-check-fill" style="font-size: 4rem; line-height: 1;"></i>
                                    </div>
                                    <h2 class="h3 fw-bold text-body-emphasis mb-2">Track Status Live</h2>
                                    <p class="text-body-secondary lead mb-4 mx-auto" style="max-width: 540px; font-size: 1.05rem;">
                                        After reserving, use your unique <strong>Reference Code</strong> on the Live Tracker to check landlord responses anytime.
                                    </p>
                                    <button 
                                        type="button" 
                                        @click="completeOnboarding" 
                                        class="btn btn-success btn-lg rounded-pill px-5 py-3 fw-bold shadow-sm hover-lift"
                                    >
                                        Get Started Now 🎉
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- MODAL FOOTER CONTROLS WITH DOT INDICATORS -->
                <div class="modal-footer border-top border-secondary-subtle px-4 py-3 bg-body-tertiary d-flex flex-column align-items-center gap-3">
                    
                    <!-- Visual Dot-Progress Indicator -->
                    <div class="d-flex align-items-center justify-content-center gap-2">
                        <button 
                            v-for="(n, idx) in totalSlides" 
                            :key="idx" 
                            type="button" 
                            @click="goToSlide(idx)" 
                            class="border-0 rounded-pill transition-all p-0" 
                            :class="currentSlideIndex === idx ? 'bg-success' : 'bg-secondary bg-opacity-30'" 
                            :style="{ width: currentSlideIndex === idx ? '24px' : '8px', height: '8px' }" 
                            :title="`Go to slide ${idx + 1}`"
                            :aria-label="`Slide ${idx + 1}`"
                        ></button>
                    </div>

                    <!-- Slide Navigation Buttons -->
                    <div class="w-100 d-flex align-items-center justify-content-between">
                        <button 
                            type="button" 
                            @click="prevSlide" 
                            class="btn btn-sm btn-outline-secondary rounded-pill px-4 py-2 fw-semibold" 
                            :disabled="currentSlideIndex === 0"
                        >
                            <i class="bi bi-arrow-left me-1"></i> Back
                        </button>

                        <button 
                            v-if="currentSlideIndex < totalSlides - 1" 
                            type="button" 
                            @click="nextSlide" 
                            class="btn btn-sm btn-success rounded-pill px-4 py-2 fw-bold shadow-sm"
                        >
                            Next <i class="bi bi-arrow-right ms-1"></i>
                        </button>

                        <button 
                            v-else 
                            type="button" 
                            @click="completeOnboarding" 
                            class="btn btn-sm btn-success rounded-pill px-4 py-2 fw-bold shadow-sm"
                        >
                            Get Started 🎉
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- FLOATING RE-OPEN GUIDE WIDGET -->
    <div class="position-fixed bottom-0 end-0 m-3 z-3">
        <button 
            type="button" 
            @click="restartOnboarding" 
            class="btn btn-sm bg-body text-body-emphasis border border-secondary-subtle rounded-pill shadow-sm d-flex align-items-center gap-2 px-3 py-2 transition-all hover-bg-tertiary"
            title="Re-open Student Security Guide"
        >
            <span class="badge bg-success-subtle text-success rounded-circle p-1">🎓</span>
            <span class="fw-semibold small">Student Guide</span>
        </button>
    </div>
</template>

<style scoped>
.transition-all {
    transition: all 0.3s ease;
}
.hover-lift {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hover-lift:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.25) !important;
}
</style>
