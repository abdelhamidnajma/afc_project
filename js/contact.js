// Contact formulier handling - hoofdscript voor contact pagina
document.addEventListener('DOMContentLoaded', function() {
    // DOM elementen ophalen voor formulier en UI elementen
    const contactForm = document.getElementById('contactForm');
    const submitBtn = document.querySelector('.btn-primary');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoading = submitBtn.querySelector('.btn-loading');
    const formMessage = document.getElementById('formMessage');
    const currentYearSpan = document.getElementById('currentYear');

    // Huidige jaar instellen in footer
    if (currentYearSpan) {
        currentYearSpan.textContent = new Date().getFullYear();
    }

    // Formulier validatie functie - controleert alle verplichte velden en formaten
    function validateForm() {
        let isValid = true;
        const requiredFields = ['firstName', 'lastName', 'email', 'subject', 'message'];
        
        // Eerdere foutmeldingen wissen
        clearErrors();
        
        // Verplichte velden controleren
        requiredFields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            const errorSpan = document.getElementById(fieldName + '-error');
            
            if (!field.value.trim()) {
                showError(field, errorSpan, 'Dit veld is verplicht');
                isValid = false;
            }
        });
        
        // E-mail formaat validatie
        const emailField = document.getElementById('email');
        const emailError = document.getElementById('email-error');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (emailField.value.trim() && !emailRegex.test(emailField.value)) {
            showError(emailField, emailError, 'Voer een geldig e-mailadres in');
            isValid = false;
        }
        
        // Telefoonnummer validatie (optioneel veld)
        const phoneField = document.getElementById('phone');
        const phoneError = document.getElementById('phone-error');
        const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
        
        if (phoneField.value.trim() && !phoneRegex.test(phoneField.value)) {
            showError(phoneField, phoneError, 'Voer een geldig telefoonnummer in');
            isValid = false;
        }
        
        return isValid;
    }
    
    // Foutmelding weergeven voor een specifiek veld
    function showError(field, errorSpan, message) {
        field.classList.add('error');
        errorSpan.textContent = message;
        errorSpan.style.display = 'block';
    }
    
    // Alle foutmeldingen wissen en error classes verwijderen
    function clearErrors() {
        const errorSpans = document.querySelectorAll('.error-message');
        const errorFields = document.querySelectorAll('.form-group input, .form-group select, .form-group textarea');
        
        errorSpans.forEach(span => {
            span.textContent = '';
            span.style.display = 'none';
        });
        
        errorFields.forEach(field => {
            field.classList.remove('error');
        });
    }
    
    // Formulier bericht weergeven (success/error) met auto-hide functionaliteit
    function showFormMessage(message, type = 'success') {
        formMessage.textContent = message;
        formMessage.className = `form-message ${type}`;
        formMessage.style.display = 'block';
        
        // Automatisch verbergen na 5 seconden
        setTimeout(() => {
            formMessage.style.display = 'none';
        }, 5000);
    }
    
    // Loading state instellen voor submit button (spinner weergeven/verbergen)
    function setLoadingState(loading) {
        if (loading) {
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline-block';
            submitBtn.disabled = true;
        } else {
            btnText.style.display = 'inline-block';
            btnLoading.style.display = 'none';
            submitBtn.disabled = false;
        }
    }
    
    // Formulier submit handling - AJAX request naar backend
    contactForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Formulier valideren
        if (!validateForm()) {
            return;
        }
        
        // Loading state instellen
        setLoadingState(true);
        clearErrors();
        
        // Formulier data voorbereiden
        const formData = new FormData(contactForm);
        
        try {
            // Formulier data naar server sturen
            const response = await fetch('php/add-contact.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showFormMessage(result.message, 'success');
                contactForm.reset();
            } else {
                showFormMessage(result.message, 'error');
            }
        } catch (error) {
            console.error('Error submitting form:', error);
            showFormMessage('Er is een fout opgetreden. Probeer het later opnieuw.', 'error');
        } finally {
            setLoadingState(false);
        }
    });
    
    // Real-time validatie - controleert velden bij blur events
    const formFields = contactForm.querySelectorAll('input, select, textarea');
    formFields.forEach(field => {
        field.addEventListener('blur', function() {
            const fieldName = this.name;
            const errorSpan = document.getElementById(fieldName + '-error');
            
            // Eerdere fout wissen
            this.classList.remove('error');
            errorSpan.style.display = 'none';
            
            // Veld valideren
            if (this.hasAttribute('required') && !this.value.trim()) {
                showError(this, errorSpan, 'Dit veld is verplicht');
            } else if (fieldName === 'email' && this.value.trim()) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(this.value)) {
                    showError(this, errorSpan, 'Voer een geldig e-mailadres in');
                }
            } else if (fieldName === 'phone' && this.value.trim()) {
                const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
                if (!phoneRegex.test(this.value)) {
                    showError(this, errorSpan, 'Voer een geldig telefoonnummer in');
                }
            }
        });
    });
    
    // Telefoonnummer formatting - automatische formatting tijdens typen
    const phoneField = document.getElementById('phone');
    if (phoneField) {
        phoneField.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Niet-numerieke karakters verwijderen
            
            // Formatteren als Nederlands telefoonnummer
            if (value.length > 0) {
                if (value.startsWith('31')) {
                    value = '+' + value;
                } else if (value.startsWith('0')) {
                    value = '+31' + value.substring(1);
                } else if (!value.startsWith('+')) {
                    value = '+31' + value;
                }
                
                // Formatting toevoegen
                if (value.length > 4) {
                    value = value.replace(/(\d{2})(\d{3})(\d{3})(\d{2})/, '$1 $2 $3 $4');
                }
            }
            
            e.target.value = value;
        });
    }
}); 