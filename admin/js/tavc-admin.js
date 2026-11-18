document.addEventListener('DOMContentLoaded', function() {
	const settings = window.tavcAdmin || {};
	const i18n = settings.i18n || {};
	const allowedTabs = Array.isArray(settings.tabs) ? settings.tabs : ['overview', 'bots', 'llmstxt', 'referrals', 'tools', 'status'];
	const activeNav = document.querySelector('.tavc-tab-nav.active');
	const activeTab = activeNav ? activeNav.getAttribute('data-tab') : '';

	// Tabs are server-rendered links (?tab=…). Links and bookmarks from earlier
	// versions used "#bots" etc.: send them to the matching tab.
	const hashTab = window.location.hash ? window.location.hash.substring(1) : '';
	if (hashTab && hashTab !== activeTab && allowedTabs.indexOf(hashTab) !== -1) {
		try {
			const url = new URL(window.location.href);
			url.searchParams.set('tab', hashTab);
			url.searchParams.delete('tavc_msg');
			url.searchParams.delete('settings-updated');
			url.hash = '';
			window.location.replace(url.toString());
			return;
		} catch (e) {
			// Old browsers: stay on the current tab.
		}
	}

	// A link to a section inside a closed <details> (e.g. #tavc-robots-preview): open it first.
	if (hashTab && allowedTabs.indexOf(hashTab) === -1) {
		const target = document.getElementById(hashTab);
		if (target) {
			let parent = target.parentElement;
			while (parent) {
				if (parent.tagName === 'DETAILS') {
					parent.open = true;
				}
				parent = parent.parentElement;
			}
			target.scrollIntoView();
			if (!target.hasAttribute('tabindex')) {
				target.setAttribute('tabindex', '-1');
			}
			target.focus({ preventScroll: true });
		}
	}

	// One-time notices (?tavc_msg=…, ?settings-updated=…) must not come back when a form
	// on this page is saved: WordPress returns to the URL in _wp_http_referer.
	document.querySelectorAll('input[name="_wp_http_referer"]').forEach(function(input) {
		try {
			const url = new URL(input.value, window.location.origin);
			url.searchParams.delete('tavc_msg');
			url.searchParams.delete('settings-updated');
			input.value = url.pathname + url.search;
		} catch (e) {
			// Keep the original value.
		}
	});

	// Health checks that were not cached are loaded after the page appears.
	const pendingHealth = document.querySelectorAll('[data-tavc-health-pending]');
	const liveRegion = document.getElementById('tavc-live');

	function announceLive(message) {
		if (!liveRegion) {
			return;
		}
		liveRegion.textContent = '';
		window.setTimeout(function() {
			liveRegion.textContent = message;
		}, 50);
	}

	if (pendingHealth.length && window.fetch && window.tavcAdmin && window.tavcAdmin.ajaxUrl) {
		const body = new FormData();
		body.append('action', 'tavc_health_check');
		body.append('nonce', window.tavcAdmin.healthNonce || '');

		window.fetch(window.tavcAdmin.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
			.then(function(response) {
				return response.json();
			})
			.then(function(result) {
				if (!result || !result.success || !result.data) {
					throw new Error('Health check failed');
				}
				document.querySelectorAll('[data-tavc-health="section"]').forEach(function(section) {
					section.innerHTML = result.data.section;
					section.removeAttribute('data-tavc-health-pending');
					section.removeAttribute('aria-busy');
				});
				document.querySelectorAll('[data-tavc-health="llms-tile"]').forEach(function(tile) {
					tile.outerHTML = result.data.llms_tile;
				});
				announceLive(i18n.healthDone || 'Health check complete.');
			})
			.catch(function() {
				document.querySelectorAll('[data-tavc-health-pending]').forEach(function(section) {
					section.removeAttribute('aria-busy');
					const message = document.createElement('p');
					message.className = 'tavc-notice-inline tavc-notice-warning';
					message.textContent = i18n.healthError || 'The health check could not be completed. Reload the page to try again.';
					section.innerHTML = '';
					section.appendChild(message);
				});
				announceLive(i18n.healthError || 'The health check could not be completed.');
			});
	}

	// Destructive tool links ask for confirmation with a message that states what will be deleted.
	document.querySelectorAll('[data-tavc-confirm]').forEach(function(link) {
		link.addEventListener('click', function(event) {
			if (!window.confirm(this.getAttribute('data-tavc-confirm'))) {
				event.preventDefault();
			}
		});
	});

	// Crawler switches: keep the visible "Allowed / Blocked" label in sync.
	function updateToggleState(checkbox) {
		const state = checkbox.parentNode ? checkbox.parentNode.querySelector('.tavc-toggle-state') : null;
		if (!state) {
			return;
		}
		state.textContent = checkbox.checked ? (i18n.blocked || 'Blocked') : (i18n.allowed || 'Allowed');
		state.classList.toggle('is-blocked', checkbox.checked);
	}

	const crawlerSwitches = document.querySelectorAll('.tavc-bot-toggle input[type="checkbox"][data-category]');
	const presetRadios = document.querySelectorAll('input[name="tavc_crawler_preset"]');
	const presetStatus = document.getElementById('tavc-preset-status');

	function formatCount(blocked, total) {
		return (i18n.groupCount || '%1$s of %2$s blocked').replace('%1$s', blocked).replace('%2$s', total);
	}

	// "X of Y blocked" in each group legend.
	function updateGroupCounts() {
		document.querySelectorAll('[data-tavc-group]').forEach(function(group) {
			const count = group.querySelector('[data-tavc-group-count]');
			if (!count) {
				return;
			}
			const blocked = group.querySelectorAll('.tavc-bot-toggle input[type="checkbox"]:checked').length;
			count.textContent = formatCount(blocked, count.getAttribute('data-total') || '0');
		});
	}

	// Select the quick-setup option that matches the switches, or "Custom".
	function syncPresetRadio() {
		let matched = null;
		presetRadios.forEach(function(radio) {
			if (matched || radio.hasAttribute('data-custom')) {
				return;
			}
			const blocked = (radio.getAttribute('data-block') || '').split(',').filter(Boolean);
			let same = true;
			crawlerSwitches.forEach(function(checkbox) {
				if (checkbox.checked !== (blocked.indexOf(checkbox.getAttribute('data-category')) !== -1)) {
					same = false;
				}
			});
			if (same) {
				matched = radio;
			}
		});
		presetRadios.forEach(function(radio) {
			radio.checked = matched ? radio === matched : radio.hasAttribute('data-custom');
		});
	}

	function announce(region) {
		if (!region) {
			return;
		}
		// Clear first so screen readers announce repeated messages.
		region.textContent = '';
		window.setTimeout(function() {
			region.textContent = region.getAttribute('data-message') || '';
		}, 50);
	}

	crawlerSwitches.forEach(function(checkbox) {
		checkbox.addEventListener('change', function() {
			updateToggleState(this);
			updateGroupCounts();
			syncPresetRadio();
		});
	});

	// Quick setup only changes the switches; nothing is published until the form is saved.
	presetRadios.forEach(function(radio) {
		radio.addEventListener('change', function() {
			if (!this.checked || this.hasAttribute('data-custom')) {
				return;
			}
			const blocked = (this.getAttribute('data-block') || '').split(',').filter(Boolean);
			crawlerSwitches.forEach(function(checkbox) {
				checkbox.checked = blocked.indexOf(checkbox.getAttribute('data-category')) !== -1;
				updateToggleState(checkbox);
			});
			updateGroupCounts();
			announce(presetStatus);
			// The change event then bubbles to the form, which refreshes the unsaved state.
		});
	});

	// Unsaved-changes guard for the settings forms (each form saves only its own section).
	const settingsForms = Array.prototype.slice.call(document.querySelectorAll('.tavc-wrap form[action$="options.php"]'));
	let submitting = false;

	function serializeForm(form) {
		const parts = [];
		new FormData(form).forEach(function(value, key) {
			if (key !== 'tavc_crawler_preset' && key !== '_wpnonce' && key !== '_wp_http_referer') {
				parts.push(key + '=' + value);
			}
		});
		return parts.join('&');
	}

	function getIndicator(form) {
		let indicator = form.querySelector('.tavc-unsaved');
		if (!indicator) {
			indicator = document.createElement('span');
			indicator.className = 'tavc-unsaved';
			indicator.setAttribute('role', 'status');
			const submit = form.querySelector('[type="submit"]');
			if (submit && submit.parentNode) {
				submit.parentNode.appendChild(indicator);
			} else {
				form.appendChild(indicator);
			}
		}
		return indicator;
	}

	function refreshDirty(form) {
		const dirty = serializeForm(form) !== form.getAttribute('data-tavc-initial');
		form.classList.toggle('tavc-is-dirty', dirty);
		const indicator = getIndicator(form);
		indicator.textContent = dirty ? (i18n.unsaved || 'Unsaved changes') : '';
	}

	settingsForms.forEach(function(form) {
		form.setAttribute('data-tavc-initial', serializeForm(form));
		getIndicator(form);
		form.addEventListener('input', function() {
			refreshDirty(form);
		});
		form.addEventListener('change', function() {
			refreshDirty(form);
		});
		form.addEventListener('submit', function(event) {
			const otherDirty = settingsForms.some(function(other) {
				return other !== form && other.classList.contains('tavc-is-dirty');
			});
			if (otherDirty && !window.confirm(i18n.confirmOther || 'You have unsaved changes in another section. Continue?')) {
				event.preventDefault();
				return;
			}
			submitting = true;
		});
	});

	window.addEventListener('beforeunload', function(event) {
		if (submitting) {
			return;
		}
		const dirty = settingsForms.some(function(form) {
			return form.classList.contains('tavc-is-dirty');
		});
		if (dirty) {
			event.preventDefault();
			event.returnValue = '';
		}
	});
});
