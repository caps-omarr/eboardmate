import { ref, onMounted, onUnmounted } from 'vue';

/**
 * Vue 3 Composable for Lazy Viewport (On-Screen) Detection via IntersectionObserver
 * 
 * @param {Object} options Configuration options
 * @param {string} [options.rootMargin='200px 0px'] Margin around the root element (e.g. prefetch 200px before entering viewport)
 * @param {number|number[]} [options.threshold=0] Observer threshold ratio (0 to 1)
 * @param {boolean} [options.triggerOnce=true] If true, stops observing after first intersection
 * @returns {{ targetRef: import('vue').Ref<HTMLElement|null>, isVisible: import('vue').Ref<boolean> }}
 */
export function useInView(options = {}) {
    const {
        rootMargin = '200px 0px',
        threshold = 0,
        triggerOnce = true,
    } = options;

    const targetRef = ref(null);
    const isVisible = ref(false);
    let observer = null;

    onMounted(() => {
        // SSR Safety Guard
        if (typeof window === 'undefined' || !('IntersectionObserver' in window)) {
            isVisible.value = true;
            return;
        }

        observer = new IntersectionObserver(([entry]) => {
            if (entry.isIntersecting) {
                isVisible.value = true;
                if (triggerOnce && observer && targetRef.value) {
                    observer.unobserve(targetRef.value);
                    observer.disconnect();
                    observer = null;
                }
            } else if (!triggerOnce) {
                isVisible.value = false;
            }
        }, {
            rootMargin,
            threshold,
        });

        if (targetRef.value) {
            observer.observe(targetRef.value);
        }
    });

    onUnmounted(() => {
        if (observer) {
            observer.disconnect();
            observer = null;
        }
    });

    return {
        targetRef,
        isVisible,
    };
}
