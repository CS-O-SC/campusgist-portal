document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('cgSidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });
    }

    // 2. Desktop Vertical Icon-Rail Collapse (Like Browser Vertical Tabs)
    const appWrapper = document.getElementById('appWrapper');
    const railToggleBtn = document.getElementById('railToggleBtn');
    if (appWrapper && railToggleBtn) {
        if (localStorage.getItem('cg_sidebar_rail') === 'true') {
            appWrapper.classList.add('sidebar-rail');
        }

        railToggleBtn.addEventListener('click', () => {
            appWrapper.classList.toggle('sidebar-rail');
            const isRail = appWrapper.classList.contains('sidebar-rail');
            localStorage.setItem('cg_sidebar_rail', isRail);
        });
    }

    // 3. Collapsible Sidebar Sections (DISCOVER & WORKSPACE)
    const sectionToggles = document.querySelectorAll('.nav-section-toggle');
    sectionToggles.forEach(toggle => {
        const targetId = toggle.dataset.target;
        const targetEl = document.getElementById(targetId);
        if (!targetEl) return;

        // Restore saved section state
        if (localStorage.getItem('cg_section_' + targetId) === 'collapsed') {
            toggle.setAttribute('aria-expanded', 'false');
            targetEl.classList.add('collapsed');
        }

        toggle.addEventListener('click', () => {
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', !isExpanded);
            targetEl.classList.toggle('collapsed');
            localStorage.setItem('cg_section_' + targetId, isExpanded ? 'collapsed' : 'expanded');
        });
    });

    // 4. Live Search & Category Filter (Explore Events Page)
    const searchInput = document.getElementById('eventSearchInput');
    const categoryButtons = document.querySelectorAll('.category-filter-btn');
    const eventCards = document.querySelectorAll('.event-item-card');
    let activeCategory = 'ALL';

    function filterEvents() {
        if (!eventCards.length) return;
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';

        eventCards.forEach(card => {
            const title = card.dataset.title || '';
            const venue = card.dataset.venue || '';
            const category = card.dataset.category || '';

            const matchesSearch = title.includes(query) || venue.includes(query);
            const matchesCategory = activeCategory === 'ALL' || category === activeCategory;

            card.style.display = (matchesSearch && matchesCategory) ? '' : 'none';
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterEvents);
    }

    categoryButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            categoryButtons.forEach(b => b.classList.remove('btn-cg-primary', 'active'));
            categoryButtons.forEach(b => b.classList.add('btn-outline-secondary'));
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-cg-primary', 'active');
            activeCategory = btn.dataset.category;
            filterEvents();
        });
    });

    // 5. Client-Side Form Validation
    const validatedForms = document.querySelectorAll('.needs-validation');
    validatedForms.forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
});