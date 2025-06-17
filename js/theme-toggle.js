// Thema toggle functionaliteit - hoofdscript voor thema wisseling
document.addEventListener('DOMContentLoaded', function() {
    // DOM elementen ophalen voor thema toggle
    const themeToggle = document.querySelector('.theme-toggle');
    const themeIcon = themeToggle.querySelector('i');

    // Thema instellen - wisselt tussen light en dark mode
    function setTheme(theme) {
        if (theme === 'dark') {
            document.documentElement.classList.add('dark-mode');
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
        } else {
            document.documentElement.classList.remove('dark-mode');
            themeIcon.classList.remove('fa-sun');
            themeIcon.classList.add('fa-moon');
        }
        localStorage.setItem('theme', theme);
    }

    // Opgeslagen thema ophalen uit localStorage of standaard light mode
    const savedTheme = localStorage.getItem('theme') || 'light';
    setTheme(savedTheme);

    // Click event listener voor thema toggle button
    themeToggle.addEventListener('click', function() {
        const currentTheme = localStorage.getItem('theme') || 'light';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        setTheme(newTheme);
    });
});
