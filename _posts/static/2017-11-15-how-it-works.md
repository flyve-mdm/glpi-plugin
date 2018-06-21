---
layout: static-docs
code: false
howtos: true
published: true
title: How it works
permalink: howtos/how-it-works
description: What you need to know
category: user
---

## General architecture

The Mobile Device Management is composed of:

* a user interface server for the administrator
* a backend server
* a M2M server
* an agent installed in the managed device.

Any connection through an untrusted network is encrypted.

A mobile device cannot guarantee a permanent connection with the backend. The M2M protocol provides features to handle loss of connectivity and guarantee delivery of important messages in both directions. The agent takes control of the device to maintain a minimal connectivity with the backend server via the M2M protocol and execute requests from the backend.

<img src="{{ '/images/general-architecture.png' | absolute_url }}" alt="Flyve MDM Infrastructure">

The certificate delivery server needs a private key to complete its role. It must communicate only with the backend and no communication is allowed from internet or any other untrusted network. It must run on a distinct server from the backend, the M2M server and the web User Interface.

All communications must be TLS encrypted.

The M2M server is a gateway between the devices and the backend, providing some helpful features to handle the unstable connectivity with devices. These features are available in a messenging queue protocol.
