---
layout: default
title: Documentação da Central de IA
lang: pt
---

[![Moodle Plugin CI](https://github.com/jeanlucio/moodle-local_aihub/actions/workflows/ci.yml/badge.svg)](https://github.com/jeanlucio/moodle-local_aihub/actions/workflows/ci.yml)
![Moodle](https://img.shields.io/badge/Moodle-4.5%2B-orange?style=flat-square&logo=moodle&logoColor=white)
![License](https://img.shields.io/badge/License-GPLv3-blue?style=flat-square)
![Status](https://img.shields.io/badge/Status-Stable-green?style=flat-square)

A **Central de IA** é um pequeno intermediário BYOK (traga sua própria chave) para o Moodle. Ela
permite que os plugins da própria instituição gerem texto através de chaves de API de IA
compartilhadas, sem que cada plugin reimplemente o transporte HTTP, o guard de SSRF, a escada de
provedores ou um armazenamento de chaves.

Use a barra lateral para pular direto a qualquer seção desta página.

Código-fonte: [github.com/jeanlucio/moodle-local_aihub](https://github.com/jeanlucio/moodle-local_aihub)

---

<span id="features"></span>
{% include_relative pt/features.md %}

<span id="how-consumers-use-it"></span>
{% include_relative pt/how-consumers-use-it.md %}

<span id="key-resolution"></span>
{% include_relative pt/key-resolution.md %}

<span id="requirements"></span>
{% include_relative pt/requirements.md %}

<span id="installation"></span>
{% include_relative pt/installation.md %}

<span id="usage"></span>
{% include_relative pt/usage.md %}

<span id="testing"></span>
{% include_relative pt/testing.md %}

<span id="security"></span>
{% include_relative pt/security.md %}

<span id="third-party-disclosure"></span>
{% include_relative pt/third-party-disclosure.md %}

<span id="license"></span>
{% include_relative pt/license.md %}
