@props([
    'notifications' => [],
    'showAll' => false
])

<div class="watermark-notifications" x-data="watermarkNotifications()" x-init="init()">
    <!-- Notification Summary -->
    <div class="notification-summary mb-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">
                Watermark System Status
            </h3>
            <div class="flex items-center space-x-2">
                <span x-show="counts.critical > 0" 
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    <span class="w-2 h-2 bg-red-400 rounded-full mr-1"></span>
                    <span x-text="counts.critical"></span> Critical
                </span>
                <span x-show="counts.active > 0" 
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    <span x-text="counts.active"></span> Active Issues
                </span>
                <span x-show="counts.active === 0" 
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <span class="w-2 h-2 bg-green-400 rounded-full mr-1"></span>
                    All Good
                </span>
            </div>
        </div>
    </div>

    <!-- System Health Status -->
    <div class="system-health mb-6 p-4 bg-gray-50 rounded-lg">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="health-item">
                <div class="flex items-center">
                    <div class="w-3 h-3 rounded-full mr-2" 
                         :class="healthStatus.extensions.has_gd || healthStatus.extensions.has_imagick ? 'bg-green-400' : 'bg-red-400'"></div>
                    <span class="text-sm font-medium">Image Processing</span>
                </div>
                <p class="text-xs text-gray-600 mt-1">
                    <span x-show="healthStatus.extensions.has_gd">GD Available</span>
                    <span x-show="healthStatus.extensions.has_imagick && healthStatus.extensions.has_gd"> & </span>
                    <span x-show="healthStatus.extensions.has_imagick">Imagick Available</span>
                    <span x-show="!healthStatus.extensions.has_gd && !healthStatus.extensions.has_imagick">No Extensions</span>
                </p>
            </div>
            
            <div class="health-item">
                <div class="flex items-center">
                    <div class="w-3 h-3 rounded-full mr-2" 
                         :class="healthStatus.cache.cache_directory_exists ? 'bg-green-400' : 'bg-yellow-400'"></div>
                    <span class="text-sm font-medium">Cache System</span>
                </div>
                <p class="text-xs text-gray-600 mt-1">
                    <span x-text="healthStatus.cache.total_cached_files || 0"></span> cached files
                    (<span x-text="healthStatus.cache.total_cache_size_human || '0 B'"></span>)
                </p>
            </div>
            
            <div class="health-item">
                <div class="flex items-center">
                    <div class="w-3 h-3 rounded-full mr-2" 
                         :class="healthStatus.status === 'healthy' ? 'bg-green-400' : 'bg-yellow-400'"></div>
                    <span class="text-sm font-medium">Overall Status</span>
                </div>
                <p class="text-xs text-gray-600 mt-1 capitalize" x-text="healthStatus.status"></p>
            </div>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="notifications-list">
        <template x-for="notification in displayedNotifications" :key="notification.id">
            <div class="notification-item mb-3 p-4 rounded-lg border-l-4" 
                 :class="getNotificationClasses(notification)"
                 x-show="!notification.dismissed">
                
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center mb-2">
                            <h4 class="text-sm font-semibold" x-text="notification.title"></h4>
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                  :class="getSeverityClasses(notification.severity)"
                                  x-text="notification.severity.toUpperCase()"></span>
                            <span x-show="notification.count > 1" 
                                  class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                <span x-text="notification.count"></span>x
                            </span>
                        </div>
                        
                        <p class="text-sm text-gray-700 mb-3" x-text="notification.message"></p>
                        
                        <!-- Suggested Actions -->
                        <div x-show="notification.suggested_actions && notification.suggested_actions.length > 0" 
                             class="suggested-actions">
                            <p class="text-xs font-medium text-gray-600 mb-2">Suggested Actions:</p>
                            <ul class="text-xs text-gray-600 space-y-1">
                                <template x-for="action in notification.suggested_actions">
                                    <li class="flex items-start">
                                        <span class="w-1 h-1 bg-gray-400 rounded-full mt-2 mr-2 flex-shrink-0"></span>
                                        <span x-text="action"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                        
                        <div class="flex items-center justify-between mt-3 text-xs text-gray-500">
                            <span>
                                First: <span x-text="formatDate(notification.first_occurrence)"></span>
                                <span x-show="notification.count > 1">
                                    | Last: <span x-text="formatDate(notification.last_occurrence)"></span>
                                </span>
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-2 ml-4">
                        <button @click="dismissNotification(notification.id)" 
                                class="text-gray-400 hover:text-gray-600 transition-colors"
                                title="Dismiss notification">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>
        
        <!-- No notifications message -->
        <div x-show="displayedNotifications.length === 0" 
             class="text-center py-8 text-gray-500">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-sm">No active notifications</p>
            <p class="text-xs text-gray-400 mt-1">Your watermark system is running smoothly</p>
        </div>
    </div>

    <!-- Actions -->
    <div class="actions mt-6 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <button @click="refreshNotifications()" 
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refresh
            </button>
            
            <button @click="testErrorHandling()" 
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
                Test System
            </button>
        </div>
        
        <div class="flex items-center space-x-3">
            <button @click="clearOldNotifications()" 
                    class="text-sm text-gray-500 hover:text-gray-700">
                Clear Old
            </button>
        </div>
    </div>
</div>

<script>
function watermarkNotifications() {
    return {
        notifications: @json($notifications),
        healthStatus: {},
        counts: {
            total: 0,
            active: 0,
            critical: 0
        },
        loading: false,
        
        get displayedNotifications() {
            return this.notifications.filter(n => !n.dismissed);
        },
        
        init() {
            this.refreshNotifications();
        },
        
        async refreshNotifications() {
            this.loading = true;
            try {
                const response = await fetch('/admin/watermark-notifications');
                const data = await response.json();
                
                this.notifications = data.notifications;
                this.healthStatus = data.health_status;
                this.counts = data.counts;
            } catch (error) {
                console.error('Failed to refresh notifications:', error);
            } finally {
                this.loading = false;
            }
        },
        
        async dismissNotification(notificationId) {
            try {
                const response = await fetch(`/admin/watermark-notifications/${notificationId}/dismiss`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    }
                });
                
                if (response.ok) {
                    const notification = this.notifications.find(n => n.id === notificationId);
                    if (notification) {
                        notification.dismissed = true;
                    }
                    this.counts.active = Math.max(0, this.counts.active - 1);
                }
            } catch (error) {
                console.error('Failed to dismiss notification:', error);
            }
        },
        
        async clearOldNotifications() {
            try {
                const response = await fetch('/admin/watermark-notifications/clear-old', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    }
                });
                
                if (response.ok) {
                    await this.refreshNotifications();
                }
            } catch (error) {
                console.error('Failed to clear old notifications:', error);
            }
        },
        
        async testErrorHandling() {
            try {
                const response = await fetch('/admin/watermark-notifications/test-error-handling', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    }
                });
                
                if (response.ok) {
                    setTimeout(() => this.refreshNotifications(), 1000);
                }
            } catch (error) {
                console.error('Failed to test error handling:', error);
            }
        },
        
        getNotificationClasses(notification) {
            const baseClasses = 'bg-white border';
            const severityClasses = {
                'critical': 'border-l-red-500 bg-red-50',
                'high': 'border-l-orange-500 bg-orange-50',
                'medium': 'border-l-yellow-500 bg-yellow-50',
                'low': 'border-l-blue-500 bg-blue-50'
            };
            
            return baseClasses + ' ' + (severityClasses[notification.severity] || severityClasses.medium);
        },
        
        getSeverityClasses(severity) {
            const classes = {
                'critical': 'bg-red-100 text-red-800',
                'high': 'bg-orange-100 text-orange-800',
                'medium': 'bg-yellow-100 text-yellow-800',
                'low': 'bg-blue-100 text-blue-800'
            };
            
            return classes[severity] || classes.medium;
        },
        
        formatDate(dateString) {
            if (!dateString) return 'Unknown';
            
            try {
                const date = new Date(dateString);
                return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            } catch (error) {
                return 'Invalid date';
            }
        }
    }
}
</script>