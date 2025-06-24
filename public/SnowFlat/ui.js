/*
    This file is part of PUPI - Flexible Notifications System, a
    Question2Answer plugin that allows users to receive notifications in a
    flexible and efficient way.

    Copyright (C) 2024 Gabriel Zanetti <https://github.com/pupi1985>

    PUPI - Flexible Notifications System is free software: you can redistribute
    it and/or modify it under the terms of the GNU General Public License as
    published by the Free Software Foundation, either version 3 of the License,
    or (at your option) any later version.

    PUPI - Flexible Notifications System is distributed in the hope that it
    will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty
    of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General
    Public License for more details.

    You should have received a copy of the GNU General Public License along
    with PUPI - Flexible Notifications System. If not, see
    <http://www.gnu.org/licenses/>.
*/

const createNode = (tagName, classes, parent) => {
    const node = document.createElement(tagName);

    if (classes) {
        node.classList.add(...classes);
    }

    if (parent) {
        parent.appendChild(node);
    }

    return node;
};

const createMainUi = () => {
    const notificationIconContainer = document.querySelector('.pupi_fns_notification-icon-container');
    if (!notificationIconContainer) return;

    const bellIconNode = notificationIconContainer.querySelector('.pupi-fns-icon-bell');

    const labelNode = createNode('span', ['pupi_fns_notification-icon-label'], notificationIconContainer);
    labelNode.style.display = 'none';

    const updateUnreadNotifications = notificationCount => {
        labelNode.innerText = notificationCount;
        labelNode.style.display = notificationCount > 0 ? 'flex' : 'none';
    };
    
    const notificationListNode = createNode('div', ['pupi_fns_notification-list'], null);
    notificationListNode.style.display = 'none';

    const setNotificationListVisible = isVisible => {
        notificationListNode.style.display = isVisible ? 'flex' : 'none';
    };

    updateUnreadNotifications(pupi_fns_options.notification_stats.unread_notifications);

    document.addEventListener('click', () => setNotificationListVisible(false));

    const notificationBellClickHandler = async e => {
        e.stopPropagation();

        const toggleBellIconLoading = isLoading => {
            bellIconNode.classList.toggle('pupi-fns-icon-bell', !isLoading);
            bellIconNode.classList.toggle('pupi-fns-icon-spin4', isLoading);
            bellIconNode.classList.toggle('animate-spin', isLoading);
            bellIconNode.dataset.fetchingData = isLoading ? 'true' : 'false';
        };

        const moveNotificationList = () => {
            if (window.screen.width >= 576) {
                notificationListNode.style.removeProperty('width');
                notificationIconContainer.appendChild(notificationListNode);
            } else {
                const mainNavWrapperNode = document.querySelector('.fns-mobile-container');
                notificationListNode.style.width = '100%';
                mainNavWrapperNode.appendChild(notificationListNode);
            }
        };

        const displayNoNotifications = lang => {
            notificationListNode.classList.add('pupi_fns_no-notifications');
            createNode('div', ['pupi_fns_notification-list-no-notifications-header'], notificationListNode);
            createNode('div', ['pupi_fns_notification-list-no-notifications-body'], notificationListNode).innerText = lang;
        };

        const displayNotifications = notifications => {
            for (const notification of notifications) {
                notificationListNode.appendChild(createNotificationItemNode(notification));
            }
        };

        const isFetchingData = bellIconNode.dataset.fetchingData === 'true';
        if (isFetchingData) {
            return;
        }

        const isVisible = notificationListNode.style.display === 'flex';
        if (isVisible) {
            setNotificationListVisible(false);
            return;
        }

        toggleBellIconLoading(true);
        moveNotificationList();

        try {
            const data = await fetchNotifications();

            notificationListNode.replaceChildren();

            if (data.notifications_stats.total_notifications === 0) {
                displayNoNotifications(data.lang);
            } else {
                updateUnreadNotifications(data.notifications_stats.unread_notifications);
                displayNotifications(data.notifications);
            }

            setNotificationListVisible(true);
            updateUnreadNotifications(0);
            
        } catch (error) {
            console.error('Error fetching notifications:', error);
        } finally {
            toggleBellIconLoading(false);
        }
    };

    notificationIconContainer.addEventListener('click', notificationBellClickHandler);
};

const createNotificationItemNode = notification => {
    const notificationItemNode = createNode('div', null, null);

    let notificationItemContainerNode = notificationItemNode;
    if (notification.url !== null) {
        notificationItemContainerNode = createNode('a', ['pupi_fns_notification-item'], notificationItemNode);
        notificationItemContainerNode.href = notification.url;
    } else {
        notificationItemContainerNode.classList.add('pupi_fns_notification-item');
    }

    if (notification.is_read) {
        notificationItemContainerNode.classList.add('pupi_fns_is-read');
    }

    const headerNode = createNode('div', ['pupi_fns_notification-item-header'], notificationItemContainerNode);

    createNode('div', ['pupi_fns_notification-item-name'], headerNode).innerText = notification.name;

    const notificationItemImageNode = createNode('div', ['pupi_fns_notification-item-image'], headerNode);
    const iconNode = createNode('i', [notification.icon], notificationItemImageNode);

    // Create the Points <span> directly as a sibling of <i> inside notificationItemImageNode
    const pointsSpanNode = createNode('span', [], notificationItemImageNode);

    createNode('div', ['pupi_fns_notification-item-date'], headerNode).innerText = formatDate(notification.created_at);

    const notificationItemIsRead = createNode('div', ['pupi_fns_notification-item-is-read'], headerNode);
    createNode('div', ['pupi_fns_notification-item-is-read-dot', notification.is_read ? 'pupi_fns_notification-item-is-read-dot-grey' : 'pupi_fns_notification-item-is-read-dot-red'], notificationItemIsRead);

    createNode('div', ['pupi_fns_notification-item-body'], notificationItemContainerNode).innerText = notification.text;

    // Call async fetch and update pointsSpanNode when done
    loadPointsConfigAndCompute(notification, pointsSpanNode);

    return notificationItemContainerNode;
};

const fetchNotifications = async () => {
    const response = await fetch(pupi_fns_options.notifications_url_all);

    return response.json();
};

const formatDate = dateString => {
    const createdAtDate = new Date(dateString);

    const twoDigitFormat = value => ('0' + value).slice(-2);

    const year = createdAtDate.getFullYear();
    const month = twoDigitFormat(createdAtDate.getMonth() + 1);
    const day = twoDigitFormat(createdAtDate.getDate());
    const hours = twoDigitFormat(createdAtDate.getHours());
    const minutes = twoDigitFormat(createdAtDate.getMinutes());
    const seconds = twoDigitFormat(createdAtDate.getSeconds());

    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
};

createMainUi();

// Cache object to avoid fetching points config multiple times
const pointsCache = {
    config: null,
    promise: null
};

/**
 * Loads cached_points.json (if not already loaded) and updates the points display
 * for a specific notification and target span.
 *
 * @param {object} notification - The notification object.
 * @param {HTMLElement} pointsSpanNode - The DOM element where points should be shown.
 */
const loadPointsConfigAndCompute = (notification, pointsSpanNode) => {
    const container = document.querySelector('.pupi_fns_notification-icon-container');
    if (!container) return;

    let fetchUrl = container.dataset.pluginUrl;
    if (!fetchUrl.endsWith('/')) fetchUrl += '/';
    fetchUrl += 'cached_points.json';

    // If already cached, use it immediately
    if (pointsCache.config) {
        updatePointsDisplay(notification, pointsSpanNode, pointsCache.config);
        return;
    }

    // If a fetch is in progress, wait for it
    if (pointsCache.promise) {
        pointsCache.promise
            .then(config => updatePointsDisplay(notification, pointsSpanNode, config))
            .catch(() => pointsSpanNode.remove());
        return;
    }

    // First-time fetch
    pointsCache.promise = fetch(fetchUrl)
        .then(response => {
            if (!response.ok) throw new Error('Failed to load FNS points config');
            return response.json();
        })
        .then(config => {
            pointsCache.config = config;
            updatePointsDisplay(notification, pointsSpanNode, config);
            return config;
        })
        .catch(error => {
            console.error('Error loading or processing FNS points config:', error);
            pointsSpanNode.remove();
        });
};

/**
 * Updates the given points span element with the calculated points.
 *
 * @param {object} notification - The notification object.
 * @param {HTMLElement} pointsSpanNode - The element to update.
 * @param {object} pointsConfig - The points config from the JSON cache.
 */
const updatePointsDisplay = (notification, pointsSpanNode, pointsConfig) => {
    const points = getPointsForEvent(notification.event_name, pointsConfig);
    pointsSpanNode.innerHTML = ''; // clear previous content

    if (points !== null && points !== 0) {
        const gainedEvents = new Set([
            'q_vote_up',
            'a_vote_up',
            'c_vote_up',
            'a_select'
        ]);
        const lostEvents = new Set([
            'q_vote_down',
            'a_vote_down',
            'c_vote_down'
        ]);

        if (gainedEvents.has(notification.event_name)) {
            pointsSpanNode.className = 'fns-gained-points';
            pointsSpanNode.innerText = `+${points}`;
        } else if (lostEvents.has(notification.event_name)) {
            pointsSpanNode.className = 'fns-lost-points';
            pointsSpanNode.innerText = `-${points}`;
        } else {
            pointsSpanNode.remove();
        }
    } else {
        pointsSpanNode.remove();
    }
};

/**
 * Returns the number of points earned for a specific notification event.
 * @param {string} eventName - The event name (e.g., 'q_vote_up', 'a_vote_down')
 * @param {object} config - Parsed config object from cached_points.json
 * @returns {number|null} - The computed points (multiplied), or null if unknown event
 */
const getPointsForEvent = (eventName, config) => {
    const multiplier = parseInt(config.points_multiple, 10);

    const eventToOptionMap = {
        q_vote_up: 'points_per_q_voted_up',
        q_vote_down: 'points_per_q_voted_down',
        a_vote_up: 'points_per_a_voted_up',
        a_vote_down: 'points_per_a_voted_down',
        c_vote_up: 'points_per_c_voted_up',
        c_vote_down: 'points_per_c_voted_down',
        a_select: 'points_a_selected',
        q_vote_nil: null, // points removed
        a_vote_nil: null,
        c_vote_nil: null
    };

    const configKey = eventToOptionMap[eventName];

    if (!configKey || !(configKey in config)) {
        return null;
    }

    const basePoints = parseInt(config[configKey], 10);
    return basePoints * multiplier;
};
