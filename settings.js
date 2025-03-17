// Settings Page JavaScript

class SettingsManager {
    constructor() {
        this.initializeElements();
        this.setupEventListeners();
        this.loadSavedSettings();
    }

    initializeElements() {
        // Profile elements
        this.profileInput = document.getElementById('profileImage');
        this.profilePreview = document.getElementById('profilePreview');
        
        // Form elements
        this.fullNameInput = document.getElementById('fullName');
        this.emailInput = document.getElementById('email');
        
        // Notification toggles
        this.emailNotif = document.getElementById('emailNotif');
        this.systemNotif = document.getElementById('systemNotif');
        
        // Theme options
        this.themeOptions = document.querySelectorAll('.theme-option');
        
        // Buttons
        this.saveBtn = document.getElementById('saveSettings');
        this.cancelBtn = document.querySelector('.cancel-btn');
    }

    setupEventListeners() {
        // Profile image upload
        this.profileInput.addEventListener('change', this.handleProfileUpload.bind(this));
        
        // Theme selection
        this.themeOptions.forEach(option => {
            option.addEventListener('click', () => this.handleThemeChange(option));
        });
        
        // Save and cancel buttons
        this.saveBtn.addEventListener('click', this.saveSettings.bind(this));
        this.cancelBtn.addEventListener('click', () => window.history.back());
        
        // Form input animations
        const formInputs = document.querySelectorAll('.form-control');
        formInputs.forEach(input => {
            input.addEventListener('focus', () => this.handleInputFocus(input));
            input.addEventListener('blur', () => this.handleInputBlur(input));
        });
    }

    handleProfileUpload(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                this.profilePreview.src = e.target.result;
                this.profilePreview.style.animation = 'fadeIn 0.5s';
            };
            reader.readAsDataURL(file);
        }
    }

    handleThemeChange(selectedOption) {
        this.themeOptions.forEach(opt => opt.classList.remove('active'));
        selectedOption.classList.add('active');
        
        const theme = selectedOption.dataset.theme;
        document.body.className = `theme-${theme}`;
        localStorage.setItem('theme', theme);
    }

    handleInputFocus(input) {
        input.parentElement.classList.add('focused');
    }

    handleInputBlur(input) {
        if (!input.value) {
            input.parentElement.classList.remove('focused');
        }
    }

    loadSavedSettings() {
        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        const themeOption = document.querySelector(`[data-theme="${savedTheme}"]`);
        if (themeOption) {
            this.handleThemeChange(themeOption);
        }

        // Load saved notification preferences
        const emailNotif = localStorage.getItem('emailNotif') === 'true';
        const systemNotif = localStorage.getItem('systemNotif') === 'true';
        this.emailNotif.checked = emailNotif;
        this.systemNotif.checked = systemNotif;

        // Load saved profile data
        const savedName = localStorage.getItem('fullName');
        const savedEmail = localStorage.getItem('email');
        if (savedName) this.fullNameInput.value = savedName;
        if (savedEmail) this.emailInput.value = savedEmail;
    }

    async saveSettings() {
        try {
            // Save to localStorage for demo purposes
            // In production, this would be an API call
            localStorage.setItem('fullName', this.fullNameInput.value);
            localStorage.setItem('email', this.emailInput.value);
            localStorage.setItem('emailNotif', this.emailNotif.checked);
            localStorage.setItem('systemNotif', this.systemNotif.checked);

            // Animate save button
            this.saveBtn.classList.add('save-success');
            await new Promise(resolve => setTimeout(resolve, 500));
            this.saveBtn.classList.remove('save-success');

            // Show success message
            this.showNotification('Settings saved successfully!');
        } catch (error) {
            this.showNotification('Error saving settings', 'error');
        }
    }

    showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);

        // Animate notification
        notification.style.animation = 'slideIn 0.3s ease-out';
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}

// Initialize settings manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new SettingsManager();
});