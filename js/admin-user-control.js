document.addEventListener('DOMContentLoaded', () => {
	const wpAdminBarLoginMonitor = document.getElementById('wp-admin-bar-login-monitor');
	const AdminUserControlRefresh = () => {
		fetch(
			ADMIN_USER_CONTROL_CONST.url,
			{
				method: 'POST',
				headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				body: (
					new URLSearchParams({action: ADMIN_USER_CONTROL_CONST.action})
				).toString()
			}
		).then(response => {
			if (!response.ok) {
				switch (response.status) {
					case 400:
						throw Error('INVALID TOKEN');
					case 401:
						throw Error('UNAUTHORIZED');
					case 500:
						throw Error('INTERNAL SERVER ERROR');
					case 502:
						throw Error('BAD GATEWAY');
					case 404:
						throw Error('NOT FOUND');
					default:
						throw Error('UNHANDLED ERROR');
				}
			}

			const contentType = response.headers.get('content-type');

			if (!contentType || !contentType.includes('application/json')) {
				throw new TypeError('Not JSON');
			}

			return response.json();
		}).then(json => {
			// login-monitor
			if (wpAdminBarLoginMonitor) {
				document.getElementById('lm-cnt').innerText = json['login-monitor'].length;
				const ul = document.getElementById('lm-list');
				ul.innerHTML = '';

				for (let i in json['login-monitor']) {
					const li = document.createElement( 'li' ),
						image = document.createElement( 'img' ),
						name = document.createElement( 'span' );

					image.src = json[ 'login-monitor' ][ i ].avatar_url
					name.textContent = json[ 'login-monitor' ][ i ].display_name;

					if (json['login-monitor'][i].profile_url === null) {
						json['login-monitor'][i].profile_url = '#';
						li.classList.add( 'no-link' );
					}

					const profileLink = document.createElement('a');
					profileLink.setAttribute('href', json['login-monitor'][i].profile_url);
					profileLink.appendChild(image);
					profileLink.appendChild(name);
					li.appendChild(profileLink);

					ul.appendChild(li);
				}
			}

			// admin-notification
			if (json['admin-notification']['is_exist_notification']) {
				jQuery('#wp-admin-bar-notifications').show()
			} else {
				jQuery('#wp-admin-bar-notifications').hide()
			}

			// admin-maintenance
			if (json['admin-maintenance']['is_maintenance']) {
				let modal = document.getElementById('auc-modal');
				modal.style.display = 'block';
				setTimeout(function () {
					location.href = json['admin-maintenance']['logout_url'];
				}, 10000);
				jQuery('#auc-maintenanceEndTime')[0].innerText = ' : ' + json['admin-maintenance']['maintenance_end_time'];
			}
		}).catch(error => console.error(error));
	};

	setInterval(AdminUserControlRefresh, ADMIN_USER_CONTROL_CONST.lifetime * 1000);
	AdminUserControlRefresh();

	const ReadNotification = (notificationId) => {
		fetch(
			ADMIN_USER_CONTROL_CONST.url,
			{
				method: 'POST',
				headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				body: (
					new URLSearchParams({
						action: ADMIN_USER_CONTROL_CONST.readNotificationAction,
						notificationId: notificationId
					})
				).toString()
			}
		).then(response => {
			if (!response.ok) {
				switch (response.status) {
					case 400:
						throw Error('INVALID TOKEN');
					case 401:
						throw Error('UNAUTHORIZED');
					case 500:
						throw Error('INTERNAL SERVER ERROR');
					case 502:
						throw Error('BAD GATEWAY');
					case 404:
						throw Error('NOT FOUND');
					default:
						throw Error('UNHANDLED ERROR');
				}
			}

			const contentType = response.headers.get('content-type');

			if (!contentType || !contentType.includes('application/json')) {
				throw new TypeError('Not JSON');
			}

			return response.json();
		}).then(json => {
			if (json['result']) {
				jQuery('ul.auc-notification-list li.post[data-postid=' + notificationId + ']').removeClass('unread').addClass('read');
				if (jQuery('ul.auc-notification-list li.post.unread').length === 0) {
					jQuery('#wp-admin-bar-notifications').hide();
				}

			}
		}).catch(error => console.error(error));
	};

	// Open/Close maintenaces.
	jQuery('ul.auc-maintenance-list').on('click', 'li.post .title', function () {
		jQuery(this).parents('li.post').toggleClass('open');
	});

	// Open/Close notifications.
	jQuery('ul.auc-notification-list').on('click', 'li.post .title', function () {
		jQuery(this).parents('li.post').toggleClass('open');
	});

	// Read notification.
	jQuery('ul.auc-notification-list').on('click', 'li.post .content .read_btn', function () {
		const postid = jQuery(this).parents('li').attr("data-postid");
		const regex = /[^0-9]/g;
		if (null === postid.match(regex)) {
			ReadNotification(postid);
		}
	});

});
