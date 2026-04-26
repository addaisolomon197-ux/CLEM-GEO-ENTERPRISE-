/**
 * CLEM GEO ENTERPRISE - Main JavaScript
 * Handles: Navigation, Scroll Animations, Forms, UI Interactions
 */

document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    // ============================================
    // DOM Elements
    // ============================================
    const navbar = document.getElementById('navbar');
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    const navLinks = document.querySelectorAll('.nav-link');
    const backToTop = document.getElementById('backToTop');
    const orderForm = document.getElementById('orderForm');
    const sections = document.querySelectorAll('section[id]');
    const sectionHeaders = document.querySelectorAll('.section-header');
    const progressFill = document.getElementById('progressFill');
    const orderButtons = document.querySelectorAll('.order-btn');

    // ============================================
    // Navbar Scroll Effect
    // ============================================
    function handleNavbarScroll() {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    }

    // ============================================
    // Mobile Navigation Toggle
    // ============================================
    function toggleMobileMenu() {
        navToggle.classList.toggle('active');
        navMenu.classList.toggle('active');
        document.body.style.overflow = navMenu.classList.contains('active') ? 'hidden' : '';
    }

    function closeMobileMenu() {
        navToggle.classList.remove('active');
        navMenu.classList.remove('active');
        document.body.style.overflow = '';
    }

    // ============================================
    // Active Navigation Link on Scroll
    // ============================================
    function highlightActiveNavLink() {
        const scrollPosition = window.scrollY + 150;

        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.offsetHeight;
            const sectionId = section.getAttribute('id');

            if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                navLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === `#${sectionId}`) {
                        link.classList.add('active');
                    }
                });
            }
        });
    }

    // ============================================
    // Back to Top Button
    // ============================================
    function handleBackToTop() {
        if (window.scrollY > 500) {
            backToTop.classList.add('visible');
        } else {
            backToTop.classList.remove('visible');
        }
    }

    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    // ============================================
    // Scroll Reveal Animation (IntersectionObserver)
    // ============================================
    const revealObserverOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                const delay = entry.target.dataset.delay || index * 0.1;
                setTimeout(() => {
                    entry.target.classList.add('active');
                }, delay * 1000);
                observer.unobserve(entry.target);
            }
        });
    }, revealObserverOptions);

    function initScrollReveal() {
        sectionHeaders.forEach(header => {
            header.classList.add('reveal');
            revealObserver.observe(header);
        });

        const gridContainers = [
            '.services-grid',
            '.products-grid',
            '.steps-grid',
            '.why-us-grid',
            '.contact-details'
        ];

        gridContainers.forEach(selector => {
            const container = document.querySelector(selector);
            if (container) {
                const children = container.children;
                Array.from(children).forEach((child, index) => {
                    child.classList.add('reveal');
                    child.dataset.delay = (index * 0.15).toString();
                    revealObserver.observe(child);
                });
            }
        });

        const formWrapper = document.querySelector('.contact-form-wrapper');
        if (formWrapper) {
            formWrapper.classList.add('reveal');
            revealObserver.observe(formWrapper);
        }
    }

    // ============================================
    // Progress Line Animation
    // ============================================
    function initProgressLine() {
        if (!progressFill) return;

        const stepsSection = document.getElementById('how-it-works');
        if (!stepsSection) return;

        const progressObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        progressFill.style.height = '100%';
                    }, 500);
                    progressObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });

        progressObserver.observe(stepsSection);
    }

    // ============================================
    // Form Submission (AJAX to PHP Backend)
    // ============================================
    async function handleFormSubmit(e) {
        e.preventDefault();

        const submitBtn = orderForm.querySelector('button[type="submit"]');
        const originalBtnContent = submitBtn.innerHTML;

        const formData = new FormData(orderForm);
        const data = {
            name: formData.get('name'),
            phone: formData.get('phone'),
            orderDetails: formData.get('orderDetails')
        };

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

        try {
            const response = await fetch('submit-order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                showNotification('Order submitted! SMS sent to admin.', 'success');

                const whatsappMessage = 'Hello CLEM GEO ENTERPRISE!\n\n' +
                    '*New Order Request*\n\n' +
                    '*Name:* ' + data.name + '\n' +
                    '*Phone:* ' + data.phone + '\n' +
                    '*Order Details:* ' + data.orderDetails + '\n\n' +
                    'Please confirm my order. Thank you!';

                const whatsappUrl = 'https://wa.me/233247877429?text=' + encodeURIComponent(whatsappMessage);

                setTimeout(() => {
                    window.open(whatsappUrl, '_blank');
                }, 2000);

                orderForm.reset();
            } else {
                showNotification(result.error || 'Something went wrong. Please try again.', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Network error. Please check your connection.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnContent;
        }
    }

    // ============================================
    // Order Button Click Handler
    // ============================================
    function handleOrderButtonClick(e) {
        const productCard = e.target.closest('.product-card');
        const productName = productCard.querySelector('h3').textContent;

        document.getElementById('contact').scrollIntoView({ behavior: 'smooth' });

        const orderDetailsField = document.getElementById('orderDetails');
        if (orderDetailsField) {
            setTimeout(() => {
                orderDetailsField.value = 'I\'m interested in ordering ' + productName + '.\nPlease provide more details about pricing and availability.';
                orderDetailsField.focus();
            }, 800);
        }
    }

    // ============================================
    // Notification Toast
    // ============================================
    function showNotification(message, type) {
        const existingNotification = document.querySelector('.notification-toast');
        if (existingNotification) {
            existingNotification.remove();
        }

        const notification = document.createElement('div');
        notification.className = 'notification-toast notification-' + type;

        const iconClass = type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle');
        const bgColor = type === 'success' ? '#0F5C4D' : (type === 'error' ? '#dc3545' : '#1A1A2E');

        notification.innerHTML = '<i class="fas ' + iconClass + '"></i><span>' + message + '</span>';

        notification.style.cssText = 'position:fixed;top:100px;right:20px;background:' + bgColor + ';color:white;padding:16px 24px;border-radius:12px;display:flex;align-items:center;gap:12px;font-weight:500;box-shadow:0 8px 30px rgba(0,0,0,0.15);z-index:10000;animation:slideInRight 0.4s ease;';

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideInRight 0.4s ease reverse';
            setTimeout(() => notification.remove(), 400);
        }, 4000);
    }

    // ============================================
    // Smooth Scroll for Anchor Links
    // ============================================
    function handleSmoothScroll(e) {
        const href = e.currentTarget.getAttribute('href');
        if (href.startsWith('#')) {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                const offsetTop = target.offsetTop - 80;
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
                closeMobileMenu();
            }
        }
    }

    // ============================================
    // Parallax Effect for Hero Background
    // ============================================
    function handleParallax() {
        const scrolled = window.scrollY;
        const heroBg = document.querySelector('.hero-bg');
        if (heroBg && scrolled < window.innerHeight) {
            heroBg.style.transform = 'translateY(' + (scrolled * 0.3) + 'px)';
        }
    }

    // ============================================
    // Event Listeners
    // ============================================
    window.addEventListener('scroll', () => {
        handleNavbarScroll();
        highlightActiveNavLink();
        handleBackToTop();
        handleParallax();
    });

    if (navToggle) {
        navToggle.addEventListener('click', toggleMobileMenu);
    }

    navLinks.forEach(link => {
        link.addEventListener('click', handleSmoothScroll);
    });

    if (backToTop) {
        backToTop.addEventListener('click', scrollToTop);
    }

    if (orderForm) {
        orderForm.addEventListener('submit', handleFormSubmit);
    }

    orderButtons.forEach(btn => {
        btn.addEventListener('click', handleOrderButtonClick);
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) {
            closeMobileMenu();
        }
    });

    // ============================================
    // Initialize
    // ============================================
    handleNavbarScroll();
    initScrollReveal();
    initProgressLine();
    document.body.classList.add('loaded');
});

