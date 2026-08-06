<script setup>
import { ref, onMounted } from 'vue';

const isDark = ref(false);

const toggleTheme = () => {
  isDark.value = !isDark.value;
  const theme = isDark.value ? 'dark' : 'light';
  
  // Bootstrap uses data-bs-theme on the root element
  document.documentElement.setAttribute('data-bs-theme', theme);
  localStorage.setItem('theme', theme);
};

onMounted(() => {
  // Check local storage or system preference on initial load
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    isDark.value = true;
    document.documentElement.setAttribute('data-bs-theme', 'dark');
  } else {
    isDark.value = false;
    document.documentElement.setAttribute('data-bs-theme', 'light');
  }
});
</script>

<template>
  <button
    @click="toggleTheme"
    type="button"
    class="btn btn-sm btn-outline-secondary border-0 d-flex align-items-center justify-content-center p-2"
    :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
  >
    <!-- Bootstrap Sun Icon (Visible in Dark Mode) -->
    <svg 
      v-if="isDark" 
      xmlns="http://www.w3.org/2000/svg" 
      width="20" 
      height="20" 
      fill="currentColor" 
      class="bi bi-sun-fill text-warning" 
      viewBox="0 0 16 16"
    >
      <path d="M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8M8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0m0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13m8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5M3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8m10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0m-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0m9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707M4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708"/>
    </svg>

    <!-- Bootstrap Moon Icon (Visible in Light Mode) -->
    <svg 
      v-else 
      xmlns="http://www.w3.org/2000/svg" 
      width="18" 
      height="18" 
      fill="currentColor" 
      class="bi bi-moon-fill text-secondary" 
      viewBox="0 0 16 16"
    >
      <path d="M6 .278a.77.77 0 0 1 .08.858 7.2 7.2 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277.527 0 1.04-.055 1.533-.16a.787.787 0 0 1 .81.316.733.733 0 0 1-.031.893A8.35 8.35 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.75.75 0 0 1 6 .278"/>
    </svg>
  </button>
</template>

<style scoped>

button, 
button:hover, 
button:focus, 
button:active, 
.btn:active {
    /* Removes the mobile tap flash */
    -webkit-tap-highlight-color: transparent !important;
    
    /* Removes the focus shadow ring */
    box-shadow: none !important; 
    outline: none !important;
    
    /* Forces the background and border to stay invisible when held */
    background-color: transparent !important; 
    border-color: transparent !important; 
}
</style>