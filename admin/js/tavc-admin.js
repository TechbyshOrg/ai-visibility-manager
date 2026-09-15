document.addEventListener('DOMContentLoaded', function() {
	const tabs = document.querySelectorAll('.tavc-tab-nav');
	const contents = document.querySelectorAll('.tavc-tab-content');
	const allowedTabs = ['overview', 'bots', 'llmstxt', 'referrals', 'tools', 'status'];

	function isAllowedTab(tab) {
		return allowedTabs.indexOf(tab) !== -1;
	}

	function activateTab(target, persistUrl) {
		if (!isAllowedTab(target)) {
			return;
		}

		tabs.forEach(function(t) {
			t.classList.remove('active');
		});
		contents.forEach(function(c) {
			c.classList.remove('active');
		});

		const tabEl = document.querySelector('.tavc-tab-nav[data-tab="' + target + '"]');
		const targetContent = document.getElementById('tavc-tab-' + target);

		if (tabEl) {
			tabEl.classList.add('active');
		}
		if (targetContent) {
			targetContent.classList.add('active');
		}

		localStorage.setItem('tavc_active_tab', target);

		if (persistUrl) {
			try {
				const url = new URL(window.location.href);
				url.hash = target;
				history.replaceState(null, '', url);
			} catch (e) {
				history.replaceState(null, null, '#' + target);
			}
		}
	}

	tabs.forEach(function(tab) {
		tab.addEventListener('click', function(e) {
			e.preventDefault();
			activateTab(this.getAttribute('data-tab'), true);
		});
	});

	const hashTab = window.location.hash ? window.location.hash.substring(1) : '';
	const queryTab = new URLSearchParams(window.location.search).get('tab') || '';
	const storedTab = localStorage.getItem('tavc_active_tab') || '';

	let activeTab = '';
	if (isAllowedTab(hashTab)) {
		activeTab = hashTab;
	} else if (isAllowedTab(queryTab)) {
		activeTab = queryTab;
	} else if (isAllowedTab(storedTab)) {
		activeTab = storedTab;
	}

	if (activeTab) {
		activateTab(activeTab, true);
	}
});
