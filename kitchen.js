/**
 * Kitchen Display System JavaScript
 * Auto-refresh every 30 seconds
 */

// Auto-refresh every 30 seconds
let refreshInterval = autoRefresh(() => {
    // Silent refresh without page reload
    console.log('Auto-refreshing kitchen display...');
}, 30000);

// Play sound when new order arrives (optional)
function playNotificationSound() {
    // You can add audio notification here
    console.log('New order notification');
}

// Clear interval when page is hidden
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        clearInterval(refreshInterval);
    } else {
        refreshInterval = autoRefresh(() => {
            location.reload();
        }, 30000);
    }
});
