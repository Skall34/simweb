/**
 * Script JavaScript pour l'installateur
 */

// Vérification dynamique de la connexion BDD (optionnel)
document.addEventListener('DOMContentLoaded', function() {
    
    // Gestion du toggle SMTP
    const smtpCheckbox = document.getElementById('smtp_enabled');
    if (smtpCheckbox) {
        smtpCheckbox.addEventListener('change', function() {
            const smtpConfig = document.getElementById('smtp-config');
            if (smtpConfig) {
                smtpConfig.style.display = this.checked ? 'block' : 'none';
            }
        });
    }
    
    // Animation de la barre de progression
    animateProgressBar();
    
    // Smooth scroll pour les ancres
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
    
});

// Animation de la barre de progression
function animateProgressBar() {
    const steps = document.querySelectorAll('.progress-step');
    steps.forEach((step, index) => {
        setTimeout(() => {
            step.style.opacity = '1';
            step.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

// Fonction pour valider un email
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Fonction pour valider une URL
function validateUrl(url) {
    try {
        new URL(url);
        return true;
    } catch (_) {
        return false;
    }
}

// Validation côté client des formulaires
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.install-form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const emailInputs = form.querySelectorAll('input[type="email"]');
            const urlInputs = form.querySelectorAll('input[type="url"]');
            
            let isValid = true;
            
            // Valider les emails
            emailInputs.forEach(input => {
                if (input.value && !validateEmail(input.value)) {
                    alert('Email invalide : ' + input.value);
                    input.focus();
                    isValid = false;
                    return;
                }
            });
            
            // Valider les URLs
            urlInputs.forEach(input => {
                if (input.value && !validateUrl(input.value)) {
                    alert('URL invalide : ' + input.value);
                    input.focus();
                    isValid = false;
                    return;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
});

// Auto-scroll vers le bas des logs pendant l'installation
if (document.querySelector('.logs-box')) {
    const logsBox = document.querySelector('.logs-box');
    const observer = new MutationObserver(() => {
        logsBox.scrollTop = logsBox.scrollHeight;
    });
    
    observer.observe(logsBox, { childList: true, subtree: true });
}
