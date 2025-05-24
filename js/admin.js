document.addEventListener('DOMContentLoaded', function() {
    // Product image preview
    const imageInput = document.getElementById('product-image');
    const imagePreview = document.getElementById('image-preview');
    
    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Confirm delete for products
    const deleteButtons = document.querySelectorAll('.delete-product');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
    
    // Rich text editor for product description
    const descriptionTextarea = document.getElementById('product-description');
    if (descriptionTextarea && typeof ClassicEditor !== 'undefined') {
        ClassicEditor
            .create(descriptionTextarea)
            .catch(error => {
                console.error('CKEditor error:', error);
            });
    }
    
    // Filter and sort products in admin panel
    const filterForm = document.getElementById('filter-form');
    
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const category = document.getElementById('filter-category').value;
            const search = document.getElementById('filter-search').value;
            const sort = document.getElementById('filter-sort').value;
            
            window.location.href = `/admin/products.php?category=${encodeURIComponent(category)}&search=${encodeURIComponent(search)}&sort=${encodeURIComponent(sort)}`;
        });
    }
    
    // Bulk actions
    const bulkActionForm = document.getElementById('bulk-action-form');
    
    if (bulkActionForm) {
        bulkActionForm.addEventListener('submit', function(e) {
            const action = document.getElementById('bulk-action').value;
            
            if (action === 'delete') {
                if (!confirm('Are you sure you want to delete the selected products? This action cannot be undone.')) {
                    e.preventDefault();
                }
            }
        });
        
        // Select all checkbox
        const selectAll = document.getElementById('select-all');
        const productCheckboxes = document.querySelectorAll('.product-checkbox');
        
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                productCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });
        }
    }
    
    // Order status update
    const statusUpdates = document.querySelectorAll('.update-status');
    
    statusUpdates.forEach(select => {
        select.addEventListener('change', function() {
            const orderId = this.getAttribute('data-order-id');
            const newStatus = this.value;
            
            fetch('/admin/orders.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update_status',
                    order_id: orderId,
                    status: newStatus
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Order status updated successfully', 'success');
                } else {
                    showNotification('Error updating order status', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Failed to update order status', 'error');
            });
        });
    });
    
    // Helper function to show notifications
    function showNotification(message, type) {
        const notificationContainer = document.getElementById('notification-container');
        
        if (!notificationContainer) {
            // Create notification container if it doesn't exist
            const container = document.createElement('div');
            container.id = 'notification-container';
            document.body.appendChild(container);
        }
        
        const notificationElement = document.createElement('div');
        notificationElement.classList.add('notification', type);
        notificationElement.textContent = message;
        
        document.getElementById('notification-container').appendChild(notificationElement);
        
        // Auto-remove notification after 3 seconds
        setTimeout(() => {
            notificationElement.classList.add('fade-out');
            setTimeout(() => {
                notificationElement.remove();
            }, 500);
        }, 3000);
    }
    
    // Date range picker for reports
    const startDate = document.getElementById('start-date');
    const endDate = document.getElementById('end-date');
    
    if (startDate && endDate) {
        // Set default end date to today
        if (!endDate.value) {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            endDate.value = `${year}-${month}-${day}`;
        }
        
        // Set default start date to 30 days ago
        if (!startDate.value) {
            const thirtyDaysAgo = new Date();
            thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
            const year = thirtyDaysAgo.getFullYear();
            const month = String(thirtyDaysAgo.getMonth() + 1).padStart(2, '0');
            const day = String(thirtyDaysAgo.getDate()).padStart(2, '0');
            startDate.value = `${year}-${month}-${day}`;
        }
    }
    
    // Dashboard charts (if Chart.js is available)
    if (typeof Chart !== 'undefined') {
        // Sales chart
        const salesChart = document.getElementById('sales-chart');
        if (salesChart) {
            fetch('/admin/api/sales-data.php')
                .then(response => response.json())
                .then(data => {
                    new Chart(salesChart, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Sales',
                                data: data.sales,
                                borderColor: '#4a6cf7',
                                backgroundColor: 'rgba(74, 108, 247, 0.1)',
                                tension: 0.3,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'top',
                                }
                            }
                        }
                    });
                })
                .catch(error => console.error('Error loading chart data:', error));
        }
        
        // Products chart
        const productsChart = document.getElementById('products-chart');
        if (productsChart) {
            fetch('/admin/api/products-data.php')
                .then(response => response.json())
                .then(data => {
                    new Chart(productsChart, {
                        type: 'doughnut',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                data: data.data,
                                backgroundColor: [
                                    '#4a6cf7', '#f7c948', '#f74a4a', '#4af78c', '#a64af7'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'right',
                                }
                            }
                        }
                    });
                })
                .catch(error => console.error('Error loading chart data:', error));
        }
    }
});
