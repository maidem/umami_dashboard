..  include:: /Includes.rst.txt

.. _start:

===============
Umami Dashboard
===============

:Extension key:
    umami_dashboard

:Package name:
    maidem/umami-dashboard

:Version:
    |release|

:Language:
    en

:Author:
    Maik Demuth

:License:
    This document is published under the
    `Open Content License <https://www.openhub.net/licenses/opl>`__.

:Rendered:
    |today|

----

TYPO3 extension providing dashboard widgets for Umami web analytics. Shows
visitors, pageviews, their daily trend and the top countries directly in the
TYPO3 backend dashboard.

.. _overview:

Overview
========

All widgets show the last 14 days and are listed in the dashboard group
"System information":

*   **Visitors** — number widget
*   **Pageviews** — number widget
*   **Umami trend** — bar chart with pageviews and visitors per day
*   **Visitors by country** — top 10 countries with flag, country names in
    the language of the backend user

.. _requirements:

Requirements
============

*   TYPO3 14.1+
*   PHP 8.2+ with ``intl`` and ``mbstring``
*   A reachable Umami instance (self-hosted) with an API key or a user, and
    the website ID

.. _installation:

Installation
============

..  code-block:: bash

    composer require maidem/umami-dashboard

Afterwards, add the desired Umami widgets in the backend under
**Dashboard → Add widget**.

.. _configuration:

Configuration
=============

Set the values in the extension configuration (**System → Settings →
Extension Configuration → umami_dashboard**) or via environment variables,
which take precedence:

*   ``UMAMI_HOST`` — base URL, for example ``https://log.example.com``
*   ``UMAMI_WEBSITE_ID`` — website ID from the Umami settings
*   ``UMAMI_API_KEY`` — API key, replaces user name and password
*   ``UMAMI_USERNAME`` / ``UMAMI_PASSWORD`` — alternative to the API key, use
    a read-only user and set the password only via environment variable

The extension configuration is visible to backend administrators, so prefer
environment variables for secrets.

.. _technical-details:

Technical details
=================

*   **UmamiStatisticService** — authenticates with the API key (or logs in),
    requests ``/stats``, ``/pageviews`` and ``/metrics?type=country`` and
    caches the result for 5 minutes (cache ``umami_dashboard``)
*   A failed request is logged and not retried for 60 seconds; the widgets
    show 0 or an empty list in the meantime
*   The data providers prepare the data for ``NumberWithIconWidget``,
    ``BarChartWidget`` and ``ListWidget``
