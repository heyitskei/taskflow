import {onMounted, ref} from 'vue';

const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
const storedDarkMode = localStorage.getItem('darkMode');
const isDark = ref(storedDarkMode === null ? prefersDark : storedDarkMode === 'true');

export function useDarkMode() {
    function toggleDarkMode() {
        isDark.value = !isDark.value;
        localStorage.setItem('darkMode', isDark.value);
        updateTheme();
    }

    function updateTheme() {
        if (isDark.value) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }

    onMounted(() => {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addEventListener('change', (e) => {
            if (localStorage.getItem('darkMode') === null) {
                isDark.value = e.matches;
                updateTheme();
            }
        });
    });

    updateTheme();

    return {
        isDark,
        toggleDarkMode
    };
}
