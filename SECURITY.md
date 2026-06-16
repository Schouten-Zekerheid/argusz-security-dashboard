# Security Policy

## Reporting a Vulnerability

Do not open a public issue for suspected vulnerabilities.

Report security issues privately through GitHub's [private vulnerability
reporting](https://docs.github.com/en/code-security/security-advisories/guidance-on-reporting-and-writing-information-about-vulnerabilities/privately-reporting-a-security-vulnerability)
(the "Report a vulnerability" button under this repository's **Security** tab),
or by email to **security@example.com**.

Include:

- a description of the issue;
- affected versions or commits, if known;
- reproduction steps;
- impact and suggested remediation, if available.

The maintainers will review the report and coordinate a fix before public
disclosure when appropriate.

## Sensitive Data

Argusz handles security findings and integrations with external systems. Never
commit:

- `.env` files with real values;
- API tokens or webhook URLs;
- Azure tenant/client secrets;
- Jira credentials;
- database dumps;
- logs containing scan results or user data;
- screenshots containing internal repositories, users, customers, or findings.
