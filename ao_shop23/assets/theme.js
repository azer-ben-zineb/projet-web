(function() {
    const savedTheme = localStorage.getItem('ao_theme') || 'default';
    if (savedTheme !== 'default') {
        document.documentElement.setAttribute('data-theme', savedTheme);
    }
})();

function cycleTheme() {
    const themes = ['default', 'theme1', 'theme2'];
    let currentTheme = localStorage.getItem('ao_theme') || 'default';
    let nextIndex = (themes.indexOf(currentTheme) + 1) % themes.length;
    let nextTheme = themes[nextIndex];
    
    if (nextTheme === 'default') {
        document.documentElement.removeAttribute('data-theme');
    } else {
        document.documentElement.setAttribute('data-theme', nextTheme);
    }
    localStorage.setItem('ao_theme', nextTheme);
}
