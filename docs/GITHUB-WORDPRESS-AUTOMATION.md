# MUTQAN GitHub → WordPress Automation

## Purpose

GitHub is the source of truth for MUTQAN code, releases, tests, and deployment automation. WordPress remains the runtime platform. The deployment workflow synchronizes only the MUTQAN plugin directory and never replaces the WordPress installation.

## Required GitHub Actions secrets

Create these repository/environment secrets under the `production` environment:

- `MUTQAN_SSH_HOST` — Hostinger SSH hostname.
- `MUTQAN_SSH_USER` — SSH/SFTP user with write access to the WordPress plugin directory.
- `MUTQAN_SSH_KEY` — private SSH key whose public key is authorized on Hostinger.
- `MUTQAN_REMOTE_PATH` — absolute server path to `wp-content/plugins/mutqan`.

Do not put passwords, SSH private keys, WordPress credentials, or AI keys in source control.

## Deployment flow

1. A change is pushed to `main`.
2. GitHub Actions checks PHP syntax.
3. The activation loader smoke test runs.
4. The repository is copied into a clean deployment directory excluding GitHub metadata, tests, and build output.
5. The MUTQAN plugin directory on Hostinger is synchronized over SSH/rsync.
6. WordPress itself is not replaced or reinstalled.

## Safety model

Production deployment is separated from source editing. GitHub is the delivery pipeline; MUTQAN remains the application core inside WordPress. Sensitive application actions continue to require MUTQAN permissions and audit logging.

## Future control layer

A later MUTQAN AI Gateway can create or update controlled repository changes, open pull requests, run the same checks, and request production deployment without giving an AI system unrestricted WordPress administrator credentials.
