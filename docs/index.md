---
layout: default
title: AI Hub Documentation
lang: en
---

![Moodle](https://img.shields.io/badge/Moodle-4.5%2B-orange?style=flat&logo=moodle&logoColor=white)
![License](https://img.shields.io/badge/License-GPLv3-blue?style=flat)
![Status](https://img.shields.io/badge/Status-Stable-green?style=flat)
[![Latest Release](https://img.shields.io/github/v/release/jeanlucio/moodle-local_aihub?style=flat)](https://github.com/jeanlucio/moodle-local_aihub/releases)
[![PlayerGames Ecosystem](https://img.shields.io/badge/PlayerGames-Ecosystem-6f42c1?style=flat&logo=gamepad&logoColor=white)](https://jeanlucio.github.io/playergames/)
![Role](https://img.shields.io/badge/Role-Shared_Service-198754?style=flat)
[![Author](https://img.shields.io/badge/by-Jean_Lucio-6f42c1?style=flat)](https://marketplace.moodle.com/user/984)

[![Moodle Plugin CI](https://github.com/jeanlucio/moodle-local_aihub/actions/workflows/ci.yml/badge.svg)](https://github.com/jeanlucio/moodle-local_aihub/actions/workflows/ci.yml)
[![Last Commit](https://img.shields.io/github/last-commit/jeanlucio/moodle-local_aihub?style=flat)](https://github.com/jeanlucio/moodle-local_aihub/commits)
[![Open Issues](https://img.shields.io/github/issues/jeanlucio/moodle-local_aihub?style=flat)](https://github.com/jeanlucio/moodle-local_aihub/issues)

**AI Hub** is a small BYOK (bring your own key) broker for Moodle. It lets the institution's own
plugins generate text through shared AI API keys, without each plugin reimplementing the HTTP
transport, the SSRF guard, the provider ladder or a key store.

Use the sidebar to jump to any section on this page.

Source code: [github.com/jeanlucio/moodle-local_aihub](https://github.com/jeanlucio/moodle-local_aihub)

---

<span id="features"></span>
{% include_relative en/features.md %}

<span id="how-consumers-use-it"></span>
{% include_relative en/how-consumers-use-it.md %}

<span id="key-resolution"></span>
{% include_relative en/key-resolution.md %}

<span id="requirements"></span>
{% include_relative en/requirements.md %}

<span id="installation"></span>
{% include_relative en/installation.md %}

<span id="usage"></span>
{% include_relative en/usage.md %}

<span id="testing"></span>
{% include_relative en/testing.md %}

<span id="security"></span>
{% include_relative en/security.md %}

<span id="third-party-disclosure"></span>
{% include_relative en/third-party-disclosure.md %}

<span id="license"></span>
{% include_relative en/license.md %}
