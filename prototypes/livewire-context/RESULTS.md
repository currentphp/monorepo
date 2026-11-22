# Public-interface HTTP proof results

Recorded 2026-11-22. All 32 scenario assertions passed.

Versions: PHP 8.5.13, Laravel v13.42.0, Livewire v4.4.9.

Core source: `1fdce8726b191f0568f08aede8e816842b6a2294`
(`feat: add explicit page experience readers`).

These are real HTTP requests to the loopback PHP server using signed snapshots,
session cookies, and CSRF tokens. The application uses only supported Current
methods. Assertions observe ambient context loss and explicit-reader parity.

| Scenario | Result |
| --- | --- |
| initial ambient page resolves | passed |
| public explicit menus and actions match ambient output on initial render | passed |
| two local readers do not overwrite each other or the ambient request | passed |
| ambient Current loses page identity on update | passed |
| ambient actions and page-sensitive menu lose their context on update | passed |
| public explicit menu predicates and active states retain page context on update | passed |
| public subject and page actions preserve order context and logical GET current-state | passed |
| explicit local context preserves page and subject on parent update | passed |
| child-only update resolves without parent render | passed |
| child local reader retains subject | passed |
| child-only update resolves public menus page actions and logical current-state | passed |
| parent presentation refreshes from server state | passed |
| child presentation refreshes independently | passed |
| successful child mutation refreshes child but does not rerender parent shell | passed |
| parent observes child mutation when explicitly refreshed | passed |
| successful mutation refreshes presentation within the same update | passed |
| public explicit subject and page actions recompute availability and reasons | passed |
| action availability refreshes after permission revocation | passed |
| parent mutation reauthorizes after permission revocation | passed |
| child mutation reauthorizes after permission revocation | passed |
| denied mutations do not write | passed |
| client cannot change locked subject ID | passed |
| changed signed route context is rejected | passed |
| second subject initial render is isolated | passed |
| second subject update is isolated | passed |
| returning to first signed page does not inherit second subject | passed |
| two different page contexts remain isolated in one bundled HTTP request | passed |
| persistent route authorization rejects revoked page access on parent update | passed |
| persistent route authorization rejects revoked page access on child-only update | passed |
| real HTTP proof retains CSRF enforcement | passed |
| deleted subject fails with 404 on parent update | passed |
| deleted subject fails with 404 on child update | passed |

The runner regenerates verbose evidence in ignored `.runtime/results.json`.
See [limits](README.md#limits-and-handoff) before generalizing the result.
