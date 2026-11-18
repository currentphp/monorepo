---
title: Introduction
label: Docs
description: What Current is, what it isn't, and where to start.
order: 0
---

Laravel gives you primitives for handling requests, but none for the experience around them: where users are, how they move around, and what they are looking at. That knowledge usually ends up scattered across route names, breadcrumb arrays, menu configuration, page headings, and frontend code, and it drifts.

Current gives your application **one description of its experience** that every part of the interface reads. You describe each page once, with a stable ID, a place in the hierarchy, a label, and an optional named route. Breadcrumbs, menus, your Blade views, and your Inertia frontend all read that description, so labels, links, and current and active states agree everywhere.

## Current describes; your application executes

Current records identities, relationships, labels, and availability. Routes, controllers, jobs, authorization, and rendering stay with your application. Current never creates routes or changes access rules: hiding a menu entry is presentation, never a replacement for an authorization check.

## The packages

| Package | What it does |
| --- | --- |
| [`current/current`](https://packagist.org/packages/current/current) | Pages, breadcrumbs, menus, subjects, inspection commands, and test assertions. Needs only Laravel. |
| [`current/inertia`](https://packagist.org/packages/current/inertia) | Shares the current page, subject, breadcrumbs, and menus with Inertia, with TypeScript types. |

Both support Laravel 13 and PHP 8.3 or later. See the [support policy](/docs/support-policy) for what that means while Current is on 0.x.

## Where to start

1. [Install Current](/docs/getting-started/installation) and describe your first [pages](/docs/getting-started/pages).
2. Render [breadcrumbs](/docs/getting-started/breadcrumbs) and [menus](/docs/getting-started/menus) from them.
3. Declare the [subjects](/docs/subjects) your pages show, and share everything with [Inertia](/docs/inertia) if you use it.
4. [Inspect and test](/docs/inspecting-and-testing) the description in CI.

> [!NOTE]
> This site is itself a Laravel application built with Current. Its sidebar, breadcrumbs, and top bar come from Current, and each guide is a Current page. [See its source](https://github.com/currentphp/monorepo/tree/main/apps/site).
