/**
 * GNN IPinfo - Copy to Clipboard
 */
document.addEventListener('DOMContentLoaded', function() {
    const copyBtn = document.querySelector('.gnn-ipinfo-copy-btn');
    const ipDisplay = document.querySelector('.gnn-ipinfo-ip-text');

    if (copyBtn && ipDisplay) {
        copyBtn.addEventListener('click', function() {
            const ip = ipDisplay.textContent.trim();
            
            // Use Clipboard API
            navigator.clipboard.writeText(ip).then(function() {
                // Success feedback
                const originalTitle = copyBtn.getAttribute('title');
                copyBtn.classList.add('is-copied');
                
                // Show 'Copied!' text if needed or just change icon
                setTimeout(function() {
                    copyBtn.classList.remove('is-copied');
                }, 2000);
            }).catch(function(err) {
                console.error('Could not copy text: ', err);
            });
        });
    }
});
