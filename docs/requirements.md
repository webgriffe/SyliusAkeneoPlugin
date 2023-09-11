---
title: Requirements
layout: page
nav_order: 1
---

# Requirements

* PHP `^8.0`
* Sylius `^1.12`
* Symfony `^5.4` or `^6.0`
* Akeneo PIM CE or EE `>= 3.2`.
  The requirement for the version `3.2` is because the provided implementation of the product importer relies on
  the `family_variant` key in the
  Akeneo [GET Product model](https://api.akeneo.com/api-reference.html#get_product_models__code_) API response.
