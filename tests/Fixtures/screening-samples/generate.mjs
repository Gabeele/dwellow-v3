#!/usr/bin/env node
// Generates test screening documents (markdown -> PDF) for three applicant
// profiles, plus one image-based photo ID to exercise the UNREADABLE path.
// Edit the `applicants` data below to change the financial story each tells.
import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync, mkdtempSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { mdToHtml, htmlDoc } from './lib.mjs';

const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const ROOT = new URL('.', import.meta.url).pathname;
const money = (n) => '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// --- Applicant financial profiles (edit these to reshape the test) -----------
const applicants = [
  {
    key: 'strong', name: 'Jordan Mitchell', dob: 'July 22, 1989', dl: 'D182-4471-8820',
    address: '47 Birchwood Terrace, Apt 2, Columbus, OH 43215',
    employer: 'Northwind Logistics Inc.', title: 'Senior Operations Analyst',
    hired: 'March 14, 2021', tenureNote: '5 years, 3 months',
    grossBiweekly: 3721.16, netBiweekly: 2412.54, ytdGross: 46458.46,
    annual: 96750, creditScore: 762, creditBand: 'Very Good',
    derogatory: 'None', utilization: '11%', latePayments: '0 in the last 24 months',
    rentApplied: 1900,
  },
  {
    key: 'borderline', name: 'Alex Rivera', dob: 'March 11, 1996', dl: 'R556-9013-2245',
    address: '218 Maple Street, Unit B, Columbus, OH 43201',
    employer: 'BrightMart Retail', title: 'Shift Supervisor',
    hired: 'February 24, 2026', tenureNote: '4 months (prior 1-month gap after Eastside Grocers)',
    grossBiweekly: 1750.00, netBiweekly: 1421.30, ytdGross: 7000.00,
    annual: 45500, creditScore: 658, creditBand: 'Fair',
    derogatory: '1 account 30 days past due (Oct 2025), since current', utilization: '47%',
    latePayments: '1 in the last 24 months', rentApplied: 1550,
  },
  {
    key: 'redflag', name: 'Sam Carter', dob: 'November 2, 1994', dl: 'C771-2204-9930',
    address: '903 Hollis Ave, Columbus, OH 43205',
    employer: 'Self-employed / rideshare (RideNow, DashGo)', title: 'Independent contractor',
    hired: 'Irregular since 2023; employment gap Jan–Apr 2026', tenureNote: 'No steady employer',
    grossBiweekly: 1025.00, netBiweekly: 1025.00, ytdGross: 4100.00,
    annual: 24600, creditScore: 561, creditBand: 'Poor',
    derogatory: 'Collection: $1,240 medical (in collections); 2 accounts 60+ days late',
    utilization: '93%', latePayments: '4 in the last 24 months', rentApplied: 1500,
  },
];

// --- Document templates ------------------------------------------------------
function payStub(a) {
  const fed = a.grossBiweekly * 0.14, state = a.grossBiweekly * 0.037;
  const ss = a.grossBiweekly * 0.062, med = a.grossBiweekly * 0.0145;
  const ded = a.grossBiweekly - a.netBiweekly;

  return `# Earnings Statement

**${a.employer}**
1200 Cedar Park Way, Columbus, OH 43215

---

## Employee

| Field | Value |
| --- | --- |
| Name | ${a.name} |
| Position | ${a.title} |
| Pay Frequency | Bi-weekly |
| Hire Date | ${a.hired} |

## Pay Period

Pay period: **June 1, 2026 – June 14, 2026** · Pay date: **June 19, 2026**

## Earnings

| Description | Current | Year to Date |
| --- | --- | --- |
| Gross Pay | ${money(a.grossBiweekly)} | ${money(a.ytdGross)} |

## Deductions

| Description | Current |
| --- | --- |
| Federal Income Tax | ${money(fed)} |
| State Income Tax | ${money(state)} |
| Social Security | ${money(ss)} |
| Medicare | ${money(med)} |
| **Total Deductions** | **${money(ded)}** |

## Net Pay

| Description | Current |
| --- | --- |
| Gross Pay | ${money(a.grossBiweekly)} |
| Total Deductions | ${money(ded)} |
| **Net Pay** | **${money(a.netBiweekly)}** |

Estimated annual gross: **${money(a.annual)}**.`;
}

function photoId(a) {
  return `# State of Ohio — Driver License

**OHIO** · Class D

---

| Field | Value |
| --- | --- |
| License No. | ${a.dl} |
| Name | ${a.name} |
| Date of Birth | ${a.dob} |
| Address | ${a.address} |
| Issued | January 5, 2024 |
| Expires | July 22, 2030 |
| Sex | — |
| Height | 5'-09" |
| Eyes | BRN |

*This document is a sample generated for software testing. Not a real identity document.*`;
}

function employmentLetter(a) {
  return `# ${a.employer}

1200 Cedar Park Way, Columbus, OH 43215 · (614) 555-0142

June 22, 2026

**RE: Employment & Income Verification for ${a.name}**

To Whom It May Concern,

This letter confirms that **${a.name}** is employed by ${a.employer} as a **${a.title}**.

| Field | Value |
| --- | --- |
| Employment Start | ${a.hired} |
| Length of Service | ${a.tenureNote} |
| Annualized Gross Income | ${money(a.annual)} |
| Employment Status | Active |

Please contact our HR department at the number above with any questions.

Sincerely,

Pat Donnelly
Human Resources Manager
${a.employer}`;
}

function creditReport(a) {
  return `# Consumer Credit Report — Summary

Prepared: June 25, 2026 · Reference: CR-${a.dl.replace(/-/g, '')}

---

## Subject

| Field | Value |
| --- | --- |
| Name | ${a.name} |
| Date of Birth | ${a.dob} |
| Current Address | ${a.address} |

## Score

| Field | Value |
| --- | --- |
| Credit Score | **${a.creditScore}** |
| Rating | ${a.creditBand} |
| Score Range | 300–850 |

## Accounts & History

| Factor | Detail |
| --- | --- |
| Revolving Utilization | ${a.utilization} |
| Late Payments | ${a.latePayments} |
| Derogatory Marks | ${a.derogatory} |

*Sample report generated for software testing only.*`;
}

const docs = [
  ['01-pay-stub', payStub],
  ['02-photo-id', photoId],
  ['03-employment-letter', employmentLetter],
  ['04-credit-report', creditReport],
];

function toPdf(html, outPath) {
  const dir = mkdtempSync(join(tmpdir(), 'gen-'));
  const htmlPath = join(dir, 'd.html');
  writeFileSync(htmlPath, html);
  execFileSync(CHROME, ['--headless', '--disable-gpu', '--no-pdf-header-footer', `--print-to-pdf=${outPath}`, `file://${htmlPath}`], { stdio: 'pipe' });
}

for (const a of applicants) {
  const dir = join(ROOT, a.key);
  mkdirSync(dir, { recursive: true });

  for (const [slug, fn] of docs) {
    const md = fn(a);
    writeFileSync(join(dir, `${slug}.md`), md);
    toPdf(htmlDoc(mdToHtml(md)), join(dir, `${slug}.pdf`));
  }

  console.log(`${a.name.padEnd(16)} -> ${a.key}/ (4 PDFs)`);
}

// Unreadable case: render an ID card to a PNG image (no text layer for the
// PDF extractor -> UNREADABLE_MARKER). Reuses the strong applicant's ID.
const unreadDir = join(ROOT, 'unreadable');
mkdirSync(unreadDir, { recursive: true });
const idHtml = htmlDoc(mdToHtml(photoId(applicants[0])));
const tmp = mkdtempSync(join(tmpdir(), 'png-'));
const idHtmlPath = join(tmp, 'id.html');
writeFileSync(idHtmlPath, idHtml);
execFileSync(CHROME, ['--headless', '--disable-gpu', '--window-size=900,600', `--screenshot=${join(unreadDir, 'photo-id-scan.png')}`, `file://${idHtmlPath}`], { stdio: 'pipe' });
console.log('Unreadable image  -> unreadable/photo-id-scan.png');
