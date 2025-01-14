window.NotificationModal = class NotificationModal {
    static show(options = {}) {
        const {
            type = 'info',
            title = 'Notification',
            message = '',
            details = '',
            buttonText = 'OK',
            buttonClass = '',
            iconClass = '',
            showCancelButton = false,
            onConfirm = null,
            redirectUrl = null
        } = options;

        const modalElement = document.getElementById('notificationModal');
        if (!modalElement) {
            console.error('notificationModal not found in the DOM.');
            return;
        }

        const modal = new bootstrap.Modal(modalElement);
        const header = document.getElementById('notificationHeader');
        const icon = document.getElementById('notificationIcon');
        const mainIcon = document.getElementById('notificationMainIcon');
        const titleEl = document.getElementById('notificationTitle');
        const messageEl = document.getElementById('notificationMessage');
        const detailsEl = document.getElementById('notificationDetails');
        const buttonContainer = modalElement.querySelector('.modal-footer');

        // Define configurations for different types
        const config = {
            success: {
                headerClass: 'bg-success text-white',
                iconClass: 'fa-check-circle',
                mainIconClass: 'text-success fa-check-circle',
                buttonClass: 'btn-success'
            },
            error: {
                headerClass: 'bg-danger text-white',
                iconClass: 'fa-exclamation-circle',
                mainIconClass: 'text-danger fa-exclamation-circle',
                buttonClass: 'btn-danger'
            },
            warning: {
                headerClass: 'bg-warning text-dark',
                iconClass: 'fa-exclamation-triangle',
                mainIconClass: 'text-warning fa-exclamation-triangle',
                buttonClass: 'btn-warning'
            },
            info: {
                headerClass: 'bg-info text-white',
                iconClass: 'fa-info-circle',
                mainIconClass: 'text-info fa-info-circle',
                buttonClass: 'btn-info'
            }
        };

        // Apply styles based on type
        const currentConfig = config[type] || config['info'];
        header.className = `modal-header border-0 ${currentConfig.headerClass}`;
        icon.className = `fas ${iconClass || currentConfig.iconClass} me-2`;
        mainIcon.className = `fas ${iconClass || currentConfig.mainIconClass} animate__animated animate__bounceIn`;

        // Set content
        titleEl.textContent = title;
        messageEl.textContent = message;
        detailsEl.textContent = details;

        // Configure buttons
        buttonContainer.innerHTML = '';
        
        if (showCancelButton) {
            const cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.className = 'btn btn-light';
            cancelBtn.textContent = 'Cancel';
            cancelBtn.onclick = () => modal.hide();
            buttonContainer.appendChild(cancelBtn);
        }

        const confirmBtn = document.createElement('button');
        confirmBtn.type = 'button';
        confirmBtn.className = `btn ${buttonClass || currentConfig.buttonClass} px-4`;
        confirmBtn.textContent = buttonText;
        
        if (onConfirm) {
            confirmBtn.onclick = () => {
                modal.hide();
                onConfirm();
            };
        } else if (redirectUrl) {
            confirmBtn.onclick = () => window.location.href = redirectUrl;
        } else {
            confirmBtn.onclick = () => modal.hide();
        }
        
        buttonContainer.appendChild(confirmBtn);

        // Show the modal
        modal.show();
    }
};