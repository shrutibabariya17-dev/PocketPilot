// js/main.js - Interactive Scripts for PocketPilot

document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Menu Toggle & Navigation Handler
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const navMenu = document.getElementById('navMenu');

    if (mobileMenuBtn && navMenu) {
        const toggleMenu = (open) => {
            const shouldOpen = open !== undefined ? open : !navMenu.classList.contains('show');
            if (shouldOpen) {
                navMenu.classList.add('show');
                document.body.style.overflow = 'hidden';
            } else {
                navMenu.classList.remove('show');
                document.body.style.overflow = '';
            }
            const icon = mobileMenuBtn.querySelector('i');
            if (icon) {
                icon.className = navMenu.classList.contains('show') ? 'bi bi-x-lg' : 'bi bi-list';
            }
        };

        mobileMenuBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            toggleMenu();
        });

        // Close menu when clicking any nav link
        navMenu.querySelectorAll('.nav-link, .nav-cta').forEach(link => {
            link.addEventListener('click', () => toggleMenu(false));
        });

        // Close menu when clicking outside
        document.addEventListener('click', function (e) {
            if (navMenu.classList.contains('show') && !navMenu.contains(e.target) && e.target !== mobileMenuBtn) {
                toggleMenu(false);
            }
        });
    }

    // 2. Preset Pill Clickers for Amount Inputs
    const presetPills = document.querySelectorAll('.preset-pill');
    const amountInput = document.getElementById('amountInput');

    if (presetPills.length > 0 && amountInput) {
        presetPills.forEach(pill => {
            pill.addEventListener('click', function () {
                const val = this.getAttribute('data-amount');
                if (val) {
                    amountInput.value = parseFloat(val).toFixed(2);
                    amountInput.focus();
                }
            });
        });
    }

    // 3. Auto dismiss alerts after 5 seconds
    const flashAlert = document.querySelector('.flash-container .alert');
    if (flashAlert) {
        setTimeout(() => {
            flashAlert.style.transition = 'opacity 0.4s ease';
            flashAlert.style.opacity = '0';
            setTimeout(() => flashAlert.remove(), 400);
        }, 5000);
    }

    // User Profile Dropdown Toggle
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdownMenu = document.getElementById('userDropdownMenu');
    const userMenuWrapper = document.getElementById('userMenuWrapper');

    if (userMenuBtn && userDropdownMenu) {
        userMenuBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = userDropdownMenu.classList.contains('show');
            userDropdownMenu.classList.toggle('show');
            userMenuBtn.setAttribute('aria-expanded', !isOpen);
        });

        document.addEventListener('click', function (e) {
            if (userDropdownMenu.classList.contains('show') && userMenuWrapper && !userMenuWrapper.contains(e.target)) {
                userDropdownMenu.classList.remove('show');
                userMenuBtn.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && userDropdownMenu.classList.contains('show')) {
                userDropdownMenu.classList.remove('show');
                userMenuBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // Navbar "More" Dropdown Toggle
    const navMoreDropdown = document.getElementById('navMoreDropdown');
    if (navMoreDropdown) {
        const moreBtn = navMoreDropdown.querySelector('.nav-dropdown-btn');
        if (moreBtn) {
            moreBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                navMoreDropdown.classList.toggle('show');
            });
        }

        document.addEventListener('click', function (e) {
            if (!navMoreDropdown.contains(e.target)) {
                navMoreDropdown.classList.remove('show');
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                navMoreDropdown.classList.remove('show');
            }
        });
    }

    // 4. Global Modal Handlers
    window.openModal = function (modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            // Auto focus first input
            const firstInput = modal.querySelector('input:not([type="hidden"]), select, textarea');
            if (firstInput) setTimeout(() => firstInput.focus(), 100);
        }
    };

    window.closeModal = function (modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    };

    // Close modal when clicking on backdrop
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', function (e) {
            if (e.target === this) {
                this.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    });

    // Close modal on Escape key press
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-backdrop.show').forEach(m => {
                m.classList.remove('show');
                document.body.style.overflow = '';
            });
        }
    });

    // 5. Interactive Student Budget Calculator (on tips.php)
    const calcInput = document.getElementById('calcIncomeInput');
    if (calcInput) {
        const updateBudgetCalc = () => {
            const income = Math.max(0, parseFloat(calcInput.value) || 0);
            const needs = (income * 0.50).toFixed(2);
            const wants = (income * 0.30).toFixed(2);
            const savings = (income * 0.20).toFixed(2);

            const elemNeeds = document.getElementById('calcNeeds');
            const elemWants = document.getElementById('calcWants');
            const elemSavings = document.getElementById('calcSavings');

            if (elemNeeds) elemNeeds.textContent = '₹' + Number(needs).toLocaleString('en-IN', {minimumFractionDigits: 2});
            if (elemWants) elemWants.textContent = '₹' + Number(wants).toLocaleString('en-IN', {minimumFractionDigits: 2});
            if (elemSavings) elemSavings.textContent = '₹' + Number(savings).toLocaleString('en-IN', {minimumFractionDigits: 2});
        };

        calcInput.addEventListener('input', updateBudgetCalc);
        updateBudgetCalc();
    }

    // 6. Monthly Checklist Persistence (tips.php)
    const checklistItems = document.querySelectorAll('.checklist-item input[type="checkbox"]');
    if (checklistItems.length > 0) {
        checklistItems.forEach((cb, idx) => {
            const savedState = localStorage.getItem('pocketpilot_check_' + idx);
            if (savedState !== null) {
                cb.checked = savedState === 'true';
            }
            cb.addEventListener('change', function () {
                localStorage.setItem('pocketpilot_check_' + idx, this.checked ? 'true' : 'false');
            });
        });
    }
});

// Confirmation helper for deletion
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this record? This action cannot be undone.');
}
