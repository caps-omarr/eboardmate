<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    hideFab: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();

// Suppress exclusively on boarding house details page to eliminate UI collisions
const isSuppressed = computed(() => {
    return props.hideFab || page.component === 'Public/BoardingHouseDetail';
});

// Modal & Scroll State
const isOpen = ref(false);
const isScrolled = ref(false);
const currentStep = ref(1);
const totalSteps = 4;
const isSubmitting = ref(false);
const isSubmitted = ref(false);
const errorMessage = ref('');

const openSurveyFromEvent = () => {
    isOpen.value = true;
};

onMounted(() => {
    window.addEventListener('open-student-survey', openSurveyFromEvent);
});

onUnmounted(() => {
    window.removeEventListener('open-student-survey', openSurveyFromEvent);
});

// Form Data State
const form = reactive({
    name: '',
    gender: '',
    course: '',
    year_level: '',
    device_used: '',
    internet_connection: '',
    ratings: {},
    difficulties: '',
    suggestions: '',
});

// Part II: System Evaluation Questions Definition
const evaluationSections = [
    {
        code: 'A',
        title: 'Part II A. Functional Suitability',
        description: 'How accurately and effectively does E-BoardMate perform its core functions?',
        items: [
            { id: 'func_1', label: 'Interactive map accurately displays verified boarding houses.' },
            { id: 'func_2', label: 'Details page presents complete and relevant information.' },
            { id: 'func_3', label: 'Guest reservation requests can be submitted without login.' },
            { id: 'func_4', label: 'Generates a unique reservation reference code.' },
            { id: 'func_5', label: 'Sends automated email notifications.' },
            { id: 'func_6', label: 'Reservation process is simple and easy.' },
            { id: 'func_7', label: 'Easily search using different filters.' },
            { id: 'func_8', label: 'Features function correctly and produce expected results.' },
            { id: 'func_9', label: 'Reservation tracking page allows easy status checks.' },
            { id: 'func_10', label: 'Displays only verified boarding houses.' },
        ],
    },
    {
        code: 'B',
        title: 'Part II B. Reliability',
        description: 'How stable, error-free, and consistent is E-BoardMate?',
        items: [
            { id: 'rel_1', label: 'Performs functions consistently.' },
            { id: 'rel_2', label: 'Remains stable (no crash/freeze).' },
            { id: 'rel_3', label: 'Map and location features work reliably.' },
            { id: 'rel_4', label: 'Reservation process works without errors/data loss.' },
            { id: 'rel_5', label: 'Email notifications delivered accurately and without delay.' },
            { id: 'rel_6', label: 'Recovers gracefully with appropriate error messages.' },
            { id: 'rel_7', label: 'Reservation info remains accurate after refreshing.' },
        ],
    },
    {
        code: 'C',
        title: 'Part II C. Usability',
        description: 'How user-friendly, clear, and easy to navigate is E-BoardMate?',
        items: [
            { id: 'use_1', label: 'Interface is easy to understand/navigate.' },
            { id: 'use_2', label: 'Layout and menus allow finding features without difficulty.' },
            { id: 'use_3', label: 'Instructions and labels are clear.' },
            { id: 'use_4', label: 'Completed intended tasks with minimal effort.' },
            { id: 'use_5', label: 'Visually organized and pleasant to use.' },
            { id: 'use_6', label: 'Easy to use without prior training.' },
            { id: 'use_7', label: 'Interactive map is easy to understand.' },
        ],
    },
    {
        code: 'D',
        title: 'Part II D. Performance Efficiency',
        description: 'How fast and responsive is E-BoardMate during usage?',
        items: [
            { id: 'perf_1', label: 'System loads quickly.' },
            { id: 'perf_2', label: 'Map loads without noticeable delay.' },
            { id: 'perf_3', label: 'Search results appear immediately.' },
            { id: 'perf_4', label: 'Reservation processing is fast.' },
        ],
    },
];

// Initialize ratings object with 0
evaluationSections.forEach(section => {
    section.items.forEach(item => {
        form.ratings[item.id] = 0;
    });
});

// Likert Emoji Rating Scale Options
const likertOptions = [
    { value: 1, label: 'Very Dissatisfied', emoji: '🙁', class: 'btn-outline-danger' },
    { value: 2, label: 'Dissatisfied', emoji: '😐', class: 'btn-outline-warning' },
    { value: 3, label: 'Neutral', emoji: '🙂', class: 'btn-outline-secondary' },
    { value: 4, label: 'Satisfied', emoji: '😃', class: 'btn-outline-info' },
    { value: 5, label: 'Very Satisfied', emoji: '😍', class: 'btn-outline-success' },
];

// Scroll detection to collapse floating button on mobile / scroll
const handleScroll = () => {
    isScrolled.value = window.scrollY > 80;
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});

// Step Validation Helpers
const isStep1Valid = computed(() => {
    return (
        form.gender !== '' &&
        form.course.trim() !== '' &&
        form.year_level !== '' &&
        form.device_used !== '' &&
        form.internet_connection !== ''
    );
});

const sectionAProgress = computed(() => {
    const items = evaluationSections[0].items;
    const answered = items.filter(i => form.ratings[i.id] > 0).length;
    return Math.round((answered / items.length) * 100);
});

const sectionBAndCProgress = computed(() => {
    const items = [...evaluationSections[1].items, ...evaluationSections[2].items];
    const answered = items.filter(i => form.ratings[i.id] > 0).length;
    return Math.round((answered / items.length) * 100);
});

const nextStep = () => {
    errorMessage.value = '';
    if (currentStep.value === 1 && !isStep1Valid.value) {
        errorMessage.value = 'Please fill out all required profile fields before proceeding.';
        return;
    }
    if (currentStep.value < totalSteps) {
        currentStep.value++;
        const modalBody = document.querySelector('.survey-modal-body');
        if (modalBody) modalBody.scrollTop = 0;
    }
};

const prevStep = () => {
    errorMessage.value = '';
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const submitSurvey = async () => {
    errorMessage.value = '';
    isSubmitting.value = true;

    try {
        await axios.post('/feedback', form);
        isSubmitted.value = true;
    } catch (err) {
        if (err.response && err.response.data && err.response.data.message) {
            errorMessage.value = err.response.data.message;
        } else {
            errorMessage.value = 'An error occurred while submitting feedback. Please try again.';
        }
    } finally {
        isSubmitting.value = false;
    }
};

const resetAndClose = () => {
    isOpen.value = false;
    setTimeout(() => {
        isSubmitted.value = false;
        currentStep.value = 1;
        errorMessage.value = '';
    }, 300);
};
</script>

<template>
    <!-- FLOATING ACTION BUTTON (FAB) (Suppressed on Detail Page) -->
    <button
        v-if="!isSuppressed"
        type="button"
        @click="isOpen = true"
        class="survey-fab btn btn-success shadow-lg rounded-pill d-flex align-items-center gap-2 transition-all"
        :class="{ 'fab-shrunk': isScrolled }"
        aria-label="Open Student System Feedback Survey"
        title="Share your feedback to improve E-BoardMate!"
    >
        <div class="fab-icon-box bg-white text-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
            <i class="bi bi-chat-heart-fill fs-5"></i>
        </div>
        <span class="fab-text fw-bold me-1 text-nowrap d-none d-sm-inline">
            System Survey
        </span>
    </button>

    <!-- SURVEY MODAL DIALOG -->
    <Teleport to="body">
        <Transition name="survey-fade">
            <div 
                v-if="isOpen" 
                class="modal fade show d-block" 
                tabindex="-1" 
                style="background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999;"
                role="dialog"
                aria-modal="true"
            >
                <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                    <div class="modal-content border-0 rounded-4 shadow-lg bg-body overflow-hidden">
                        
                        <!-- Modal Header -->
                        <div class="modal-header border-bottom border-secondary-subtle px-4 py-3 bg-body-tertiary">
                            <div class="d-flex align-items-center gap-2">
                                <div class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-bold">
                                    Student Evaluation
                                </div>
                                <h2 class="h6 fw-bold mb-0 text-body-emphasis">E-BoardMate System Feedback</h2>
                            </div>
                            <button type="button" class="btn-close shadow-none" @click="resetAndClose" aria-label="Close"></button>
                        </div>

                        <!-- Progress Bar (Stepper) -->
                        <div v-if="!isSubmitted" class="progress rounded-0" style="height: 4px;">
                            <div 
                                class="progress-bar bg-success transition-all" 
                                role="progressbar" 
                                :style="{ width: `${(currentStep / totalSteps) * 100}%` }"
                            ></div>
                        </div>

                        <!-- Modal Body -->
                        <div class="modal-body survey-modal-body p-4">
                            
                            <!-- ERROR ALERT -->
                            <div v-if="errorMessage" class="alert alert-danger rounded-3 small mb-4">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ errorMessage }}
                            </div>

                            <!-- STEP 1: RESPONDENT PROFILE -->
                            <div v-if="currentStep === 1 && !isSubmitted">
                                <div class="mb-4">
                                    <h3 class="h5 fw-bold text-body-emphasis mb-1">Part I. Respondent Profile</h3>
                                    <p class="small text-body-secondary">Please tell us a little bit about yourself to help evaluate system accessibility.</p>
                                </div>

                                <div class="row g-3">
                                    <!-- Name -->
                                    <div class="col-md-6">
                                        <label for="survey-name" class="form-label fw-bold small text-body-secondary">Full Name (Optional)</label>
                                        <input id="survey-name" v-model="form.name" type="text" class="form-control rounded-3" placeholder="Fullname">
                                    </div>

                                    <!-- Gender -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-body-secondary">Gender <span class="text-danger">*</span></label>
                                        <div class="d-flex gap-3 mt-1">
                                            <div class="form-check">
                                                <input id="g-male" v-model="form.gender" type="radio" value="Male" class="form-check-input">
                                                <label for="g-male" class="form-check-label small">Male</label>
                                            </div>
                                            <div class="form-check">
                                                <input id="g-female" v-model="form.gender" type="radio" value="Female" class="form-check-input">
                                                <label for="g-female" class="form-check-label small">Female</label>
                                            </div>
                                            <div class="form-check">
                                                <input id="g-prefer" v-model="form.gender" type="radio" value="Prefer not to say" class="form-check-input">
                                                <label for="g-prefer" class="form-check-label small">Prefer not to say</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Course -->
                                    <div class="col-md-6">
                                        <label for="survey-course" class="form-label fw-bold small text-body-secondary">Course / Program <span class="text-danger">*</span></label>
                                        <input id="survey-course" v-model="form.course" type="text" class="form-control rounded-3" placeholder="Your Course / Program" required>
                                    </div>

                                    <!-- Year Level -->
                                    <div class="col-md-6">
                                        <label for="survey-year" class="form-label fw-bold small text-body-secondary">Year Level <span class="text-danger">*</span></label>
                                        <select id="survey-year" v-model="form.year_level" class="form-select rounded-3" required>
                                            <option value="" disabled>Select Year Level</option>
                                            <option value="1st Year">1st Year</option>
                                            <option value="2nd Year">2nd Year</option>
                                            <option value="3rd Year">3rd Year</option>
                                            <option value="4th Year">4th Year</option>
                                        </select>
                                    </div>

                                    <!-- Device Used -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-body-secondary">Primary Device Used <span class="text-danger">*</span></label>
                                        <select v-model="form.device_used" class="form-select rounded-3" required>
                                            <option value="" disabled>Select Device</option>
                                            <option value="Mobile Phone">Mobile Phone (Browser)</option>
                                            <option value="Laptop">Laptop</option>
                                            <option value="Desktop">Desktop Computer</option>
                                        </select>
                                    </div>

                                    <!-- Internet Connection -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-body-secondary">Internet Connection <span class="text-danger">*</span></label>
                                        <select v-model="form.internet_connection" class="form-select rounded-3" required>
                                            <option value="" disabled>Select Connection</option>
                                            <option value="WiFi">Home / Campus WiFi</option>
                                            <option value="Mobile Data">Mobile Data (4G/5G)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- STEP 2: SYSTEM EVALUATION - FUNCTIONAL SUITABILITY -->
                            <div v-else-if="currentStep === 2 && !isSubmitted">
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h3 class="h5 fw-bold text-body-emphasis mb-0">{{ evaluationSections[0].title }}</h3>
                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill small">{{ sectionAProgress }}% Answered</span>
                                    </div>
                                    <p class="small text-body-secondary mb-0">{{ evaluationSections[0].description }}</p>
                                    <div class="small text-muted mt-1">(Rating Scale: 1 = Very Dissatisfied to 5 = Very Satisfied)</div>
                                </div>

                                <div class="d-flex flex-column gap-4">
                                    <div 
                                        v-for="(item, idx) in evaluationSections[0].items" 
                                        :key="item.id" 
                                        class="p-3 bg-body-tertiary rounded-3 border border-secondary-subtle"
                                    >
                                        <p class="fw-bold small text-body-emphasis mb-2">
                                            {{ idx + 1 }}. {{ item.label }}
                                        </p>
                                        <div class="d-flex flex-wrap gap-2">
                                            <button
                                                v-for="opt in likertOptions"
                                                :key="opt.value"
                                                type="button"
                                                @click="form.ratings[item.id] = opt.value"
                                                class="btn btn-sm flex-grow-1 rounded-pill py-2 px-3 transition-all d-flex align-items-center justify-content-center gap-1"
                                                :class="form.ratings[item.id] === opt.value ? 'btn-success text-white shadow-sm' : 'btn-outline-secondary opacity-75'"
                                            >
                                                <span>{{ opt.emoji }}</span>
                                                <span class="small fw-semibold">{{ opt.value }} - {{ opt.label }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- STEP 3: RELIABILITY & USABILITY -->
                            <div v-else-if="currentStep === 3 && !isSubmitted">
                                <div class="mb-4">
                                    <h3 class="h5 fw-bold text-body-emphasis mb-1">Parts II B & C. Reliability & Usability</h3>
                                    <p class="small text-body-secondary mb-0">Evaluate system stability, ease of navigation, and overall user interface clarity.</p>
                                </div>

                                <!-- Section B: Reliability -->
                                <div class="mb-5">
                                    <h4 class="h6 fw-bold text-success border-bottom pb-2 mb-3"><i class="bi bi-shield-check me-1"></i> Part II B. Reliability</h4>
                                    <div class="d-flex flex-column gap-3">
                                        <div 
                                            v-for="(item, idx) in evaluationSections[1].items" 
                                            :key="item.id" 
                                            class="p-3 bg-body-tertiary rounded-3 border border-secondary-subtle"
                                        >
                                            <p class="fw-bold small text-body-emphasis mb-2">{{ idx + 1 }}. {{ item.label }}</p>
                                            <div class="d-flex flex-wrap gap-2">
                                                <button
                                                    v-for="opt in likertOptions"
                                                    :key="opt.value"
                                                    type="button"
                                                    @click="form.ratings[item.id] = opt.value"
                                                    class="btn btn-sm flex-grow-1 rounded-pill py-2 px-3 transition-all d-flex align-items-center justify-content-center gap-1"
                                                    :class="form.ratings[item.id] === opt.value ? 'btn-success text-white shadow-sm' : 'btn-outline-secondary opacity-75'"
                                                >
                                                    <span>{{ opt.emoji }}</span>
                                                    <span class="small fw-semibold">{{ opt.value }}</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section C: Usability -->
                                <div>
                                    <h4 class="h6 fw-bold text-success border-bottom pb-2 mb-3"><i class="bi bi-magic me-1"></i> Part II C. Usability</h4>
                                    <div class="d-flex flex-column gap-3">
                                        <div 
                                            v-for="(item, idx) in evaluationSections[2].items" 
                                            :key="item.id" 
                                            class="p-3 bg-body-tertiary rounded-3 border border-secondary-subtle"
                                        >
                                            <p class="fw-bold small text-body-emphasis mb-2">{{ idx + 1 }}. {{ item.label }}</p>
                                            <div class="d-flex flex-wrap gap-2">
                                                <button
                                                    v-for="opt in likertOptions"
                                                    :key="opt.value"
                                                    type="button"
                                                    @click="form.ratings[item.id] = opt.value"
                                                    class="btn btn-sm flex-grow-1 rounded-pill py-2 px-3 transition-all d-flex align-items-center justify-content-center gap-1"
                                                    :class="form.ratings[item.id] === opt.value ? 'btn-success text-white shadow-sm' : 'btn-outline-secondary opacity-75'"
                                                >
                                                    <span>{{ opt.emoji }}</span>
                                                    <span class="small fw-semibold">{{ opt.value }}</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- STEP 4: PERFORMANCE & OPEN-ENDED -->
                            <div v-else-if="currentStep === 4 && !isSubmitted">
                                <div class="mb-4">
                                    <h3 class="h5 fw-bold text-body-emphasis mb-1">Part II D & Part III. Performance & Suggestions</h3>
                                    <p class="small text-body-secondary mb-0">Almost done! Rate system speed and share any comments or feature requests.</p>
                                </div>

                                <!-- Section D: Performance Efficiency -->
                                <div class="mb-4">
                                    <h4 class="h6 fw-bold text-success border-bottom pb-2 mb-3"><i class="bi bi-lightning-charge me-1"></i> Part II D. Performance Efficiency</h4>
                                    <div class="d-flex flex-column gap-3">
                                        <div 
                                            v-for="(item, idx) in evaluationSections[3].items" 
                                            :key="item.id" 
                                            class="p-3 bg-body-tertiary rounded-3 border border-secondary-subtle"
                                        >
                                            <p class="fw-bold small text-body-emphasis mb-2">{{ idx + 1 }}. {{ item.label }}</p>
                                            <div class="d-flex flex-wrap gap-2">
                                                <button
                                                    v-for="opt in likertOptions"
                                                    :key="opt.value"
                                                    type="button"
                                                    @click="form.ratings[item.id] = opt.value"
                                                    class="btn btn-sm flex-grow-1 rounded-pill py-2 px-3 transition-all d-flex align-items-center justify-content-center gap-1"
                                                    :class="form.ratings[item.id] === opt.value ? 'btn-success text-white shadow-sm' : 'btn-outline-secondary opacity-75'"
                                                >
                                                    <span>{{ opt.emoji }}</span>
                                                    <span class="small fw-semibold">{{ opt.value }}</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Part III: Open-Ended Feedback -->
                                <div class="p-3 bg-body-tertiary rounded-3 border border-secondary-subtle">
                                    <h4 class="h6 fw-bold text-body-emphasis mb-3"><i class="bi bi-chat-left-text me-1"></i> Part III. Open-Ended Feedback</h4>
                                    
                                    <div class="mb-3">
                                        <label for="difficulties" class="form-label small fw-bold text-body-secondary">
                                            What difficulties, if any, did you experience while searching for or reserving a boarding house using E-BoardMate?
                                        </label>
                                        <textarea id="difficulties" v-model="form.difficulties" class="form-control rounded-3" rows="3" placeholder="Share any issues or bugs encountered..."></textarea>
                                    </div>

                                    <div>
                                        <label for="suggestions" class="form-label small fw-bold text-body-secondary">
                                            What additional features would you like to see added to improve the system?
                                        </label>
                                        <textarea id="suggestions" v-model="form.suggestions" class="form-control rounded-3" rows="3" placeholder="Suggestions for new features or improvements..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- STEP 5: SUCCESS THANK YOU STATE -->
                            <div v-else-if="isSubmitted" class="text-center py-5">
                                <div class="success-icon-wrapper mb-3 mx-auto bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                    <i class="bi bi-check-circle-fill display-4"></i>
                                </div>
                                <h3 class="h4 fw-bold text-body-emphasis mb-2">Thank You for Your Feedback!</h3>
                                <p class="text-body-secondary max-w-md mx-auto small mb-4">
                                    Your response has been recorded and will help us continuously improve E-BoardMate for Talibon Polytechnic College students and guests.
                                </p>
                                <button type="button" @click="resetAndClose" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm">
                                    Close Survey
                                </button>
                            </div>

                        </div>

                        <!-- Modal Footer Controls -->
                        <div v-if="!isSubmitted" class="modal-footer border-top border-secondary-subtle px-4 py-3 bg-body-tertiary justify-content-between">
                            <button 
                                type="button" 
                                @click="prevStep" 
                                class="btn btn-outline-secondary rounded-pill px-4 fw-semibold"
                                :disabled="currentStep === 1 || isSubmitting"
                            >
                                <i class="bi bi-arrow-left me-1"></i> Back
                            </button>

                            <div class="small fw-semibold text-body-secondary">
                                Step {{ currentStep }} of {{ totalSteps }}
                            </div>

                            <button 
                                v-if="currentStep < totalSteps" 
                                type="button" 
                                @click="nextStep" 
                                class="btn btn-success rounded-pill px-4 fw-bold shadow-sm"
                            >
                                Next <i class="bi bi-arrow-right ms-1"></i>
                            </button>

                            <button 
                                v-else 
                                type="button" 
                                @click="submitSurvey" 
                                class="btn btn-success rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-2"
                                :disabled="isSubmitting"
                            >
                                <span v-if="isSubmitting" class="spinner-border spinner-border-sm"></span>
                                <span>{{ isSubmitting ? 'Submitting...' : 'Submit Feedback' }}</span>
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
/* Floating Action Button Styling */
.survey-fab {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    z-index: 1050;
    padding: 0.6rem 1rem 0.6rem 0.6rem;
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.35) !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.survey-fab:hover {
    transform: translateY(-3px) scale(1.03);
    box-shadow: 0 12px 28px rgba(16, 185, 129, 0.45) !important;
}

.fab-icon-box {
    width: 38px;
    height: 38px;
}

/* Scroll / Mobile Shrunk FAB state */
.survey-fab.fab-shrunk {
    padding: 0.6rem;
    border-radius: 50% !important;
}

.survey-fab.fab-shrunk .fab-text {
    display: none !important;
}

.survey-modal-body {
    max-height: calc(85vh - 130px);
    overflow-y: auto;
}

/* Modal Fade Animation */
.survey-fade-enter-active,
.survey-fade-leave-active {
    transition: opacity 0.25s ease;
}

.survey-fade-enter-from,
.survey-fade-leave-to {
    opacity: 0;
}
</style>
