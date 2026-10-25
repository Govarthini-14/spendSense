document.addEventListener('DOMContentLoaded', () => {
    const flashMessages = document.querySelectorAll('.toast');
    flashMessages.forEach((toast) => {
        setTimeout(() => {
            toast.classList.remove('show');
            toast.style.display = 'none';
        }, 4000);
    });

    const forms = document.querySelectorAll('form[data-validate="true"]');
    forms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            clearErrors(form);
            const errors = getFormErrors(form);
            if (errors.length > 0) {
                event.preventDefault();
                showErrors(form, errors);
            }
        });
    });

    const deleteLinks = document.querySelectorAll('[data-confirm-delete]');
    deleteLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            if (!window.confirm('Are you sure you want to delete this transaction?')) {
                event.preventDefault();
            }
        });
    });
});

function clearErrors(form) {
    form.querySelectorAll('.error-text').forEach((el) => el.remove());
    form.querySelectorAll('.input-error').forEach((el) => el.classList.remove('input-error'));
}

function showErrors(form, errors) {
    errors.forEach((error) => {
        if (!error.field) return;
        const field = form.querySelector('[name="' + error.field + '"]');
        if (!field) return;
        field.classList.add('input-error');
        const text = document.createElement('div');
        text.className = 'error-text';
        text.textContent = error.message;
        field.parentNode.appendChild(text);
    });
}

function getFormErrors(form) {
    const errors = [];
    const amount = form.querySelector('[name="amount"]');
    if (amount && (Number(amount.value) <= 0 || Number.isNaN(Number(amount.value)))) {
        errors.push({ field: 'amount', message: 'Amount must be greater than zero.' });
    }
    const dateField = form.querySelector('[name="expense_date"]');
    if (dateField && dateField.value) {
        const selectedDate = new Date(dateField.value + 'T00:00:00');
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (selectedDate > today) {
            errors.push({ field: 'expense_date', message: 'Expense date cannot be in the future.' });
        }
    }
    const title = form.querySelector('[name="title"]');
    if (title && title.value.trim() === '') {
        errors.push({ field: 'title', message: 'Expense title is required.' });
    }
    const category = form.querySelector('[name="category"]');
    if (category && category.value.trim() === '') {
        errors.push({ field: 'category', message: 'Please select a category.' });
    }
    const paymentMethod = form.querySelector('[name="payment_method"]');
    if (paymentMethod && paymentMethod.value.trim() === '') {
        errors.push({ field: 'payment_method', message: 'Please select a payment method.' });
    }
    return errors;
}

function formatCurrency(value) {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 2,
    }).format(value);
}

function getMonthValue(monthValue) {
    const monthNames = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    return monthNames[monthValue - 1] || monthValue;
}
