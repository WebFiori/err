# Changelog

## [2.1.0](https://github.com/WebFiori/err/compare/v2.0.3...v2.1.0) (2026-09-20)


### Features

* **logging:** add pluggable log callback (no PSR-3 dependency) ([ebfdcf4](https://github.com/WebFiori/err/commit/ebfdcf41e79c7f7b49bd1ecaa840ceecdd0a8b76))
* **logging:** add pluggable log callback (no PSR-3 dependency) ([8dad5fa](https://github.com/WebFiori/err/commit/8dad5fa6bbcacdab90f3d876cb0ca204314819d0)), closes [#13](https://github.com/WebFiori/err/issues/13)


### Bug Fixes

* **handler:** production trace building, dead code removal, and trace duplication ([b4d2195](https://github.com/WebFiori/err/commit/b4d21950088287084fc5eb07fda3a1df6fb77f6d))
* **handler:** reset max handler executions in Handler::reset() ([ad84792](https://github.com/WebFiori/err/commit/ad84792cadf52103046df145d0b9595e9823c279)), closes [#28](https://github.com/WebFiori/err/issues/28)
* **handler:** use $this-&gt;exception directly in setTrace() to fix production trace building ([382220b](https://github.com/WebFiori/err/commit/382220bc1921355c2e1e673a36f8ce50c8226822)), closes [#17](https://github.com/WebFiori/err/issues/17)
* **memory:** remove unused handlerWeakRefs dead code ([262f67e](https://github.com/WebFiori/err/commit/262f67e1425f642414b7c37547c4c5150d50602b)), closes [#16](https://github.com/WebFiori/err/issues/16)
* **trace:** reset traceArr in setTrace() to prevent duplication ([dec949f](https://github.com/WebFiori/err/commit/dec949fb8f739ae5fca60b4d4b80c4ee415b1f02)), closes [#15](https://github.com/WebFiori/err/issues/15)


### Miscellaneous Chores

* normalize line endings to LF ([5064134](https://github.com/WebFiori/err/commit/506413426200db5ea868aa5e0b49147aabd1f264))

## [2.0.3](https://github.com/WebFiori/err/compare/v2.0.2...v2.0.3) (2026-06-14)


### Bug Fixes

* respect @ suppression and add configurable throwable error levels ([adf5a79](https://github.com/WebFiori/err/commit/adf5a792f37e1d586bd15af9c7e58646c9b44185))


### Miscellaneous Chores

* Merge pull request [#25](https://github.com/WebFiori/err/issues/25) from WebFiori/dev ([28fb910](https://github.com/WebFiori/err/commit/28fb9107c5ed4060cbacb53fec9a640aaf0e3c94))

## [2.0.2](https://github.com/WebFiori/err/compare/v2.0.1...v2.0.2) (2026-06-02)


### Miscellaneous Chores

* align CI with ecosystem baseline ([6563764](https://github.com/WebFiori/err/commit/65637648f58cad53d837d35cc5eb3e9cc6f882be))
* align CI with ecosystem baseline ([90369f5](https://github.com/WebFiori/err/commit/90369f5ff6c3686a871a4e86cc76c4bde3a6f9f1))

## [2.0.1](https://github.com/WebFiori/err/compare/v2.0.0...v2.0.1) (2025-10-02)


### Miscellaneous Chores

* Update .gitattributes With Correct Release Info ([2f28bf8](https://github.com/WebFiori/err/commit/2f28bf89833367d5260303bd580abe25be0a94ad))

## [2.0.0](https://github.com/WebFiori/err/compare/v1.2.0...v2.0.0) (2025-09-17)


### Features

* Added Protection For Loops + Memory Optimization ([d05a634](https://github.com/WebFiori/err/commit/d05a6347982e5ed172c24d1588a81cf27cf0ace1))
* Error Reporting Configuration ([a66c362](https://github.com/WebFiori/err/commit/a66c3627ac75ca9285f442fd26f8253d7b2445f2))
* HTML and CLI in Default Handler ([8caa2f1](https://github.com/WebFiori/err/commit/8caa2f1377851d9566bbfed839a854b147709897))
* Multiple Features ([0ce686e](https://github.com/WebFiori/err/commit/0ce686efa92801b58c4362aa79fca5d5838b8e04))
* Multiple Features ([8774a3e](https://github.com/WebFiori/err/commit/8774a3e41ec1d045ade9abcfcffb678c4dd42954))


### Bug Fixes

* Check For Null Exception ([578ef4b](https://github.com/WebFiori/err/commit/578ef4ba707ca96fbb4758947a14bfb33d942e4e))
* Security Level Update ([ca5dea4](https://github.com/WebFiori/err/commit/ca5dea4cd8d57b9016a6c3332230705742960a3f))


### Miscellaneous Chores

* release v2.0.0 ([1f1623e](https://github.com/WebFiori/err/commit/1f1623e127e8c6824af5b1c25c52547fd98f9bf6))
* Updated Release Config ([8c067d8](https://github.com/WebFiori/err/commit/8c067d834b82bc46bc3c4bc19158d33ff425c9cc))
