document.addEventListener('DOMContentLoaded', function() {
	const tabs = document.querySelectorAll('.tavc-tab-nav');
	const contents = document.querySelectorAll('.tavc-tab-content');
	
	tabs.forEach(tab => {
		tab.addEventListener('click', function(e) {
			e.preventDefault();
			const target = this.getAttribute('data-tab');
			
			tabs.forEach(t => t.classList.remove('active'));
			contents.forEach(c => c.classList.remove('active'));
			
			this.classList.add('active');
			const targetContent = document.getElementById('tavc-tab-' + target);
			if (targetContent) {
				targetContent.classList.add('active');
			}
			
			// Store active tab in URL hash/localStorage
			localStorage.setItem('tavc_active_tab', target);
			
			// Update URL hash without jumping page
			history.replaceState(null, null, '#' + target);
		});
	});
	
	// Restore active tab from hash or localStorage
	let activeTab = window.location.hash ? window.location.hash.substring(1) : localStorage.getItem('tavc_active_tab');
	
	if (activeTab) {
		const tabEl = document.querySelector(`.tavc-tab-nav[data-tab="${activeTab}"]`);
		if (tabEl) {
			// Trigger a click event
			tabEl.dispatchEvent(new Event('click'));
		}
	}
});
